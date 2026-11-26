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
 * RF-USU-04 (integración): Modificar usuarios en la BD de seguridad real.
 *
 * Escenarios:
 *  E1. Modificación válida sin cambiar contraseña ni rol.
 *  E2. Modificación con nueva contraseña.
 *  E3. Cambio de rol de un usuario de nivel 3 (se borran sus excepciones de permisos).
 *  E4. Intento de cambiar rol/contraseña a un usuario de nivel 1 o 2.
 *  E5. Cédula, correo o teléfono de un usuario eliminado.
 *  E6. Cédula, correo o teléfono de otro usuario activo.
 *  E7. Rol inexistente.
 */
#[Group('integ')]
#[Group('usuarios')]
#[Group('usuariosModificar')]
class ModificarUsuarioIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloUsuarios $modelo;

    /** Roles creados para la prueba: ['nivel1' => id, 'nivel2' => id, 'nivel3' => id, 'nivel3b' => id] */
    private array $roles = [];

    private string $claveInicial = 'ClaveInicial.123';

    private int $idUsuario;

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

    /**
     * Devuelve ids de permisos que se muestran en la pantalla de permisos de usuario
     * (los módulos 1, 2, 3, 4, 5 y 8 se excluyen en ModeloPermisos::permisosUsuarios).
     */
    private function obtenerPermisosVisibles(int $cantidad): array
    {
        $stmt = $this->pdo->query("SELECT id_permiso FROM permisos WHERE id_modulo NOT IN (1, 2, 3, 4, 5, 8) ORDER BY id_permiso ASC LIMIT {$cantidad}");
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        if (count($ids) < $cantidad) {
            $this->markTestSkipped("La BD de pruebas necesita al menos {$cantidad} permisos registrados.");
        }
        return $ids;
    }

    private function crearExcepcion(int $idUsuario, int $idPermiso, int $tipo): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO excepciones (id_permiso, id_usuario, tipo) VALUES (:permiso, :usuario, :tipo)");
        $stmt->execute([':permiso' => $idPermiso, ':usuario' => $idUsuario, ':tipo' => $tipo]);
    }

    /** @return array<int,int> id_permiso => tipo */
    private function obtenerExcepciones(int $idUsuario): array
    {
        $stmt = $this->pdo->prepare("SELECT id_permiso, tipo FROM excepciones WHERE id_usuario = :id ORDER BY id_permiso");
        $stmt->execute([':id' => $idUsuario]);
        $excepciones = [];
        foreach ($stmt->fetchAll() as $fila) {
            $excepciones[(int)$fila['id_permiso']] = (int)$fila['tipo'];
        }
        return $excepciones;
    }

    private function prepararUsuario(string $rol = 'nivel3'): void
    {
        $this->idUsuario = $this->crearUsuario([
            'cedula'   => '99990020',
            'nombre'   => 'Original',
            'apellido' => 'Apellido',
            'telefono' => '0414-9990020',
            'correo'   => 'original@integ.test',
            'rol'      => $this->roles[$rol],
        ]);
    }

    private function datosModificacion(array $sobrescribir = []): array
    {
        return array_merge([
            'accion'   => 'modificar',
            'id'       => $this->idUsuario,
            'cedula'   => '99990020',
            'nombre'   => 'nuevo nombre',
            'apellido' => 'nuevo apellido',
            'telefono' => '0414-9990021',
            'correo'   => 'Nuevo@Integ.test',
            'roles_id' => $this->roles['nivel3'],
            'foto'     => 'user_99990020.png',
        ], $sobrescribir);
    }

    // Caso 1 (E1): Se actualizan los datos y la contraseña se mantiene
    public function testModificacionExitosaSinContraseña(): void
    {
        $this->prepararUsuario();
        $hashAnterior = $this->obtenerUsuario($this->idUsuario)['pass_hash'];

        $resultado = $this->modelo->procesarDatos($this->datosModificacion());

        $this->assertSame(['accion' => 'exito'], $resultado);

        $usuario = $this->obtenerUsuario($this->idUsuario);
        $this->assertSame('Nuevo Nombre', $usuario['nombreUsuario']);
        $this->assertSame('Nuevo Apellido', $usuario['apellidoUsuario']);
        $this->assertSame('0414-9990021', $usuario['telefonoUsuario']);
        $this->assertSame('nuevo@integ.test', $usuario['correo']);
        $this->assertSame('user_99990020.png', $usuario['foto']);
        $this->assertSame($hashAnterior, $usuario['pass_hash'], 'La contraseña no debe cambiar si no se envía.');
    }

    // Caso 2 (E1): Se puede cambiar la cédula por una que nadie usa
    public function testCambioDeCedulaLibre(): void
    {
        $this->prepararUsuario();

        $resultado = $this->modelo->procesarDatos($this->datosModificacion(['cedula' => '99990029']));

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame('99990029', $this->obtenerUsuario($this->idUsuario)['cedulaUsuario']);
    }

    // Caso 3 (E2): La contraseña nueva queda cifrada en la BD
    public function testModificacionConNuevaContraseña(): void
    {
        $this->prepararUsuario();

        $resultado = $this->modelo->procesarDatos($this->datosModificacion(['contraseña' => 'Nueva.Clave456']));

        $this->assertSame('exito', $resultado['accion']);
        $usuario = $this->obtenerUsuario($this->idUsuario);
        $this->assertTrue(password_verify('Nueva.Clave456', $usuario['pass_hash']));
        $this->assertFalse(password_verify($this->claveInicial, $usuario['pass_hash']));
    }

    // Caso 4 (E3): Al cambiar el rol se eliminan las excepciones de permisos del usuario
    public function testCambioDeRolEliminaExcepciones(): void
    {
        $this->prepararUsuario();
        [$permiso] = $this->obtenerPermisosVisibles(1);
        $this->crearExcepcion($this->idUsuario, $permiso, 1);

        $resultado = $this->modelo->procesarDatos($this->datosModificacion(['roles_id' => $this->roles['nivel3b']]));

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame($this->roles['nivel3b'], (int)$this->obtenerUsuario($this->idUsuario)['id_rol']);
        $this->assertSame([], $this->obtenerExcepciones($this->idUsuario));
    }

    // Caso 5 (E3): Si el rol no cambia, las excepciones se conservan
    public function testSinCambioDeRolConservaExcepciones(): void
    {
        $this->prepararUsuario();
        [$permiso] = $this->obtenerPermisosVisibles(1);
        $this->crearExcepcion($this->idUsuario, $permiso, 1);

        $this->modelo->procesarDatos($this->datosModificacion());

        $this->assertSame([$permiso => 1], $this->obtenerExcepciones($this->idUsuario));
    }

    // Caso 6 (E4): A un usuario de nivel 1 no se le cambia ni el rol ni la contraseña
    public function testNivel1ConservaRolYContraseña(): void
    {
        $this->prepararUsuario('nivel1');
        $hashAnterior = $this->obtenerUsuario($this->idUsuario)['pass_hash'];

        $resultado = $this->modelo->procesarDatos($this->datosModificacion([
            'roles_id'   => $this->roles['nivel3'],
            'contraseña' => 'Nueva.Clave456',
        ]));

        $this->assertSame('exito', $resultado['accion']);
        $usuario = $this->obtenerUsuario($this->idUsuario);
        $this->assertSame($this->roles['nivel1'], (int)$usuario['id_rol']);
        $this->assertSame($hashAnterior, $usuario['pass_hash']);
        $this->assertSame('Nuevo Nombre', $usuario['nombreUsuario'], 'Los demás datos sí se actualizan.');
    }

    // Caso 7 (E4): A un usuario de nivel 2 no se le cambia ni el rol ni la contraseña
    public function testNivel2ConservaRolYContraseña(): void
    {
        $this->prepararUsuario('nivel2');
        $hashAnterior = $this->obtenerUsuario($this->idUsuario)['pass_hash'];

        $resultado = $this->modelo->procesarDatos($this->datosModificacion([
            'roles_id'   => $this->roles['nivel3'],
            'contraseña' => 'Nueva.Clave456',
        ]));

        $this->assertSame('exito', $resultado['accion']);
        $usuario = $this->obtenerUsuario($this->idUsuario);
        $this->assertSame($this->roles['nivel2'], (int)$usuario['id_rol']);
        $this->assertSame($hashAnterior, $usuario['pass_hash']);
    }

    // Caso 8 (E5): Cédula que pertenece a un usuario eliminado
    public function testRechazaCedulaDeUsuarioEliminado(): void
    {
        $this->prepararUsuario();
        $this->crearUsuario(['cedula' => '99990028', 'correo' => 'eliminado@integ.test', 'telefono' => '0414-9990028', 'estatus' => 0]);

        $resultado = $this->modelo->procesarDatos($this->datosModificacion(['cedula' => '99990028']));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_CEDULA . '0', $resultado['codigo']);
        $this->assertSame('99990020', $this->obtenerUsuario($this->idUsuario)['cedulaUsuario']);
    }

    // Caso 9 (E5): Correo que pertenece a un usuario eliminado
    public function testRechazaCorreoDeUsuarioEliminado(): void
    {
        $this->prepararUsuario();
        $this->crearUsuario(['cedula' => '99990028', 'correo' => 'nuevo@integ.test', 'telefono' => '0414-9990028', 'estatus' => 0]);

        $resultado = $this->modelo->procesarDatos($this->datosModificacion());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL . '0', $resultado['codigo']);
        $this->assertSame('original@integ.test', $this->obtenerUsuario($this->idUsuario)['correo']);
    }

    // Caso 10 (E5): Teléfono que pertenece a un usuario eliminado
    public function testRechazaTelefonoDeUsuarioEliminado(): void
    {
        $this->prepararUsuario();
        $this->crearUsuario(['cedula' => '99990028', 'correo' => 'eliminado@integ.test', 'telefono' => '0414-9990021', 'estatus' => 0]);

        $resultado = $this->modelo->procesarDatos($this->datosModificacion());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE . '0', $resultado['codigo']);
        $this->assertSame('0414-9990020', $this->obtenerUsuario($this->idUsuario)['telefonoUsuario']);
    }

    // Caso 11 (E6): Cédula de otro usuario activo
    public function testRechazaCedulaDeOtroUsuarioActivo(): void
    {
        $this->prepararUsuario();
        $this->crearUsuario(['cedula' => '99990028', 'correo' => 'otro@integ.test', 'telefono' => '0414-9990028']);

        $resultado = $this->modelo->procesarDatos($this->datosModificacion(['cedula' => '99990028']));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_CEDULA, $resultado['codigo']);
        $this->assertSame('Original', $this->obtenerUsuario($this->idUsuario)['nombreUsuario'], 'La transacción debe revertirse.');
    }

    // Caso 12 (E6): Correo de otro usuario activo
    public function testRechazaCorreoDeOtroUsuarioActivo(): void
    {
        $this->prepararUsuario();
        $this->crearUsuario(['cedula' => '99990028', 'correo' => 'nuevo@integ.test', 'telefono' => '0414-9990028']);

        $resultado = $this->modelo->procesarDatos($this->datosModificacion());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL, $resultado['codigo']);
        $this->assertSame('original@integ.test', $this->obtenerUsuario($this->idUsuario)['correo']);
    }

    // Caso 13 (E6): Teléfono de otro usuario activo
    public function testRechazaTelefonoDeOtroUsuarioActivo(): void
    {
        $this->prepararUsuario();
        $this->crearUsuario(['cedula' => '99990028', 'correo' => 'otro@integ.test', 'telefono' => '0414-9990021']);

        $resultado = $this->modelo->procesarDatos($this->datosModificacion());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE, $resultado['codigo']);
        $this->assertSame('0414-9990020', $this->obtenerUsuario($this->idUsuario)['telefonoUsuario']);
    }

    // Caso 14 (E7): Rol inexistente
    public function testRechazaRolInexistente(): void
    {
        $this->prepararUsuario();

        $resultado = $this->modelo->procesarDatos($this->datosModificacion(['roles_id' => 999999]));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $usuario = $this->obtenerUsuario($this->idUsuario);
        $this->assertSame($this->roles['nivel3'], (int)$usuario['id_rol']);
        $this->assertSame('Original', $usuario['nombreUsuario']);
    }
}
