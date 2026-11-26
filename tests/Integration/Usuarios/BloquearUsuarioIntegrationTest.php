<?php

declare(strict_types=1);

namespace tests\Integration\Usuarios;

use App\modelo\Conexion;
use App\modelo\ModeloUsuarios;
use PDO;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

/**
 * RF-USU-06 (integración): Bloquear y desbloquear usuarios en la BD de seguridad real.
 * En la columna `bloqueo`, 1 = desbloqueado y 2 = bloqueado. El parámetro `bloqueo`
 * recibido es el estado actual: si vale 1 se bloquea, en otro caso se desbloquea y
 * se reinician los intentos fallidos.
 *
 * Escenarios:
 *  E1. Bloqueo de un usuario activo.
 *  E2. Desbloqueo de un usuario bloqueado.
 *  E3. Intento sobre el superusuario (id 1).
 *  E4. Intento sobre un usuario inexistente o eliminado.
 *  E5. Intento sobre un usuario de nivel 1 o 2.
 */
#[Group('integ')]
#[Group('usuarios')]
#[Group('usuariosBloquear')]
class BloquearUsuarioIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloUsuarios $modelo;

    /** Roles creados para la prueba: ['nivel1' => id, 'nivel2' => id, 'nivel3' => id, 'nivel3b' => id] */
    private array $roles = [];

    private string $claveInicial = 'ClaveInicial.123';

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];

        $host = _DB_HOST_SG_ ?: '127.0.0.1';
        $nombre = _DB_NAME_SG_ ?: 'bds2';
        $usuario = _DB_USER_SG_ ?: 'root';

        $this->pdo = new PDO("mysql:host={$host};dbname={$nombre};charset=utf8mb4", $usuario, _DB_PASS_SG_, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        $this->inyectarConexion($this->pdo);

        $this->limpiarDatosPrueba();
        $this->crearRolesPrueba();

        $this->modelo = new ModeloUsuarios();
    }

    protected function tearDown(): void
    {
        $this->limpiarDatosPrueba();
        $this->pdo = null;
        $this->inyectarConexion(null);
        $_SESSION = [];
        parent::tearDown();
    }

    private function inyectarConexion(?PDO $pdo): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdo);
        $reflector->getProperty('instancia')->setValue(null, $pdo);
    }

    /**
     * Las pruebas trabajan contra la base de datos de seguridad real (_DB_NAME_SG_) sin vaciar tablas:
     * todo lo que crean las pruebas se identifica con la cédula "9999xxxx" y con roles cuyo
     * nombre empieza por "Integ Usuarios", y se elimina antes y después de cada prueba.
     * Así los usuarios propios del sistema (Admin, Soporte, ...) nunca se modifican.
     */
    private function limpiarDatosPrueba(): void
    {
        if (!$this->pdo) {
            return;
        }

        $idsUsuarios = "SELECT idUsuario FROM (SELECT idUsuario FROM usuarios WHERE cedulaUsuario LIKE '9999%') AS t";
        $idsRoles = "SELECT id_rol FROM (SELECT id_rol FROM roles WHERE nombre_rol LIKE 'Integ Usuarios%') AS t";

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("DELETE FROM excepciones WHERE id_usuario IN ($idsUsuarios);");
        $this->pdo->exec("DELETE FROM notificaciones WHERE id_usuario IN ($idsUsuarios);");
        $this->pdo->exec("DELETE FROM bitacora WHERE idUsuario IN ($idsUsuarios) OR acciones LIKE 'Registro al usuario: 9999%';");
        $this->pdo->exec("DELETE FROM usuarios WHERE cedulaUsuario LIKE '9999%';");
        $this->pdo->exec("DELETE FROM permisos_rol WHERE id_rol IN ($idsRoles);");
        $this->pdo->exec("DELETE FROM roles WHERE nombre_rol LIKE 'Integ Usuarios%';");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function crearRolesPrueba(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO roles (nombre_rol, descripcion, nivel_rol, estatus) VALUES (:nombre, 'Rol de prueba', :nivel, 1)");

        foreach (['nivel1' => 1, 'nivel2' => 2, 'nivel3' => 3, 'nivel3b' => 3] as $clave => $nivel) {
            $stmt->execute([':nombre' => "Integ Usuarios {$clave}", ':nivel' => $nivel]);
            $this->roles[$clave] = (int)$this->pdo->lastInsertId();
        }
    }

    /**
     * Inserta un usuario directamente en la BD (sin pasar por el modelo) y devuelve su id.
     */
    private function crearUsuario(array $datos = []): int
    {
        $base = [
            'cedula'   => '99990001',
            'nombre'   => 'Usuario',
            'apellido' => 'Prueba',
            'foto'     => 'default.png',
            'telefono' => '0414-9990001',
            'correo'   => 'usuario.prueba@integ.test',
            'rol'      => $this->roles['nivel3'],
            'estatus'  => 1,
            'bloqueo'  => 1,
        ];
        $d = array_merge($base, $datos);

        $stmt = $this->pdo->prepare("INSERT INTO usuarios
            (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos)
            VALUES (:cedula, :nombre, :apellido, :foto, :telefono, :pass, :correo, :rol, :estatus, :bloqueo, 0)");
        $stmt->execute([
            ':cedula'   => $d['cedula'],
            ':nombre'   => $d['nombre'],
            ':apellido' => $d['apellido'],
            ':foto'     => $d['foto'],
            ':telefono' => $d['telefono'],
            ':pass'     => password_hash($this->claveInicial, PASSWORD_BCRYPT),
            ':correo'   => $d['correo'],
            ':rol'      => $d['rol'],
            ':estatus'  => $d['estatus'],
            ':bloqueo'  => $d['bloqueo'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function obtenerUsuario(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE idUsuario = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function cambiarBloqueo(int $id, int $estadoActual): array
    {
        return $this->modelo->procesarDatos(['accion' => 'bloquear', 'id' => $id, 'bloqueo' => $estadoActual]);
    }

    // Caso 1 (E1): Un usuario desbloqueado pasa a bloqueado
    public function testBloquearUsuario(): void
    {
        $id = $this->crearUsuario(['bloqueo' => 1]);

        $resultado = $this->cambiarBloqueo($id, 1);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame(2, (int)$this->obtenerUsuario($id)['bloqueo']);
    }

    // Caso 2 (E2): Un usuario bloqueado pasa a desbloqueado y se reinician sus intentos fallidos
    public function testDesbloquearUsuarioReiniciaIntentos(): void
    {
        $id = $this->crearUsuario(['bloqueo' => 2]);
        $this->pdo->exec("UPDATE usuarios SET intentos_fallidos = 3 WHERE idUsuario = {$id}");

        $resultado = $this->cambiarBloqueo($id, 2);

        $this->assertSame('exito', $resultado['accion']);
        $usuario = $this->obtenerUsuario($id);
        $this->assertSame(1, (int)$usuario['bloqueo']);
        $this->assertSame(0, (int)$usuario['intentos_fallidos']);
    }

    // Caso 3 (E1): Al bloquear no se tocan los intentos fallidos
    public function testBloquearConservaIntentos(): void
    {
        $id = $this->crearUsuario(['bloqueo' => 1]);
        $this->pdo->exec("UPDATE usuarios SET intentos_fallidos = 2 WHERE idUsuario = {$id}");

        $this->cambiarBloqueo($id, 1);

        $this->assertSame(2, (int)$this->obtenerUsuario($id)['intentos_fallidos']);
    }

    // Caso 4 (E1/E2): El estado de bloqueo se refleja en la consulta del módulo
    public function testBloqueoSeReflejaEnConsulta(): void
    {
        $id = $this->crearUsuario(['bloqueo' => 1]);

        $this->cambiarBloqueo($id, 1);

        $datos = $this->modelo->Consultar(['filtro' => '99990001'])['datos'];
        $this->assertCount(1, $datos);
        $this->assertSame(2, (int)$datos[0]['bloqueo']);
    }

    // Caso 5 (E3): El superusuario (id 1) no se puede bloquear
    public function testNoBloqueaSuperusuario(): void
    {
        $bloqueoAntes = $this->obtenerUsuario(1)['bloqueo'] ?? null;

        $resultado = $this->cambiarBloqueo(1, 1);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(ASSOCIATES, $resultado['codigo']);
        $this->assertSame($bloqueoAntes, $this->obtenerUsuario(1)['bloqueo'] ?? null);
    }

    // Caso 6 (E4): Usuario inexistente
    public function testNoBloqueaUsuarioInexistente(): void
    {
        $resultado = $this->cambiarBloqueo(99999999, 1);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
    }

    // Caso 7 (E4): Usuario eliminado
    public function testNoBloqueaUsuarioEliminado(): void
    {
        $id = $this->crearUsuario(['estatus' => 0, 'bloqueo' => 1]);

        $resultado = $this->cambiarBloqueo($id, 1);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertSame(1, (int)$this->obtenerUsuario($id)['bloqueo']);
    }

    // Caso 8 (E5): Usuario de nivel 1
    public function testNoBloqueaUsuarioNivel1(): void
    {
        $id = $this->crearUsuario(['rol' => $this->roles['nivel1']]);

        $resultado = $this->cambiarBloqueo($id, 1);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('ROLE_RULES_5', $resultado['codigo']);
        $this->assertSame(1, (int)$this->obtenerUsuario($id)['bloqueo']);
    }

    // Caso 9 (E5): Usuario de nivel 2
    public function testNoBloqueaUsuarioNivel2(): void
    {
        $id = $this->crearUsuario(['rol' => $this->roles['nivel2']]);

        $resultado = $this->cambiarBloqueo($id, 1);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('ROLE_RULES_5', $resultado['codigo']);
        $this->assertSame(1, (int)$this->obtenerUsuario($id)['bloqueo']);
    }
}
