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

#[Group('integ')]
#[Group('usuarios')]
#[Group('usuariosEliminar')]
class EliminarUsuarioIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloUsuarios $modelo;

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

    private function eliminar(int $id): array
    {
        return $this->modelo->procesarDatos(['accion' => 'eliminar', 'id' => $id]);
    }

    public function testEliminacionLogicaExitosa(): void
    {
        $id = $this->crearUsuario();
        [$permiso] = $this->obtenerPermisosVisibles(1);
        $this->crearExcepcion($id, $permiso, 1);

        $resultado = $this->eliminar($id);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $usuario = $this->obtenerUsuario($id);
        $this->assertNotFalse($usuario, 'La eliminación es lógica: el registro debe seguir en la BD.');
        $this->assertSame(0, (int)$usuario['estatus']);
        $this->assertSame([], $this->obtenerExcepciones($id));
    }

    public function testUsuarioEliminadoNoApareceEnConsultas(): void
    {
        $id = $this->crearUsuario();

        $this->eliminar($id);

        $this->assertCount(0, $this->modelo->Consultar(['filtro' => '99990001'])['datos']);
        $busqueda = $this->modelo->procesarDatos(['accion' => 'buscar', 'id' => $id]);
        $this->assertSame([], $busqueda['datos']);
    }

    public function testNoEliminaSuperusuario(): void
    {
        $estatusAntes = $this->obtenerUsuario(1)['estatus'] ?? null;

        $resultado = $this->eliminar(1);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(ASSOCIATES, $resultado['codigo']);
        $this->assertSame($estatusAntes, $this->obtenerUsuario(1)['estatus'] ?? null);
    }

    public function testNoEliminaUsuarioInexistente(): void
    {
        $resultado = $this->eliminar(99999999);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
    }

    public function testNoEliminaUsuarioYaEliminado(): void
    {
        $id = $this->crearUsuario(['estatus' => 0]);

        $resultado = $this->eliminar($id);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
    }

    public function testNoEliminaUsuarioNivel1(): void
    {
        $id = $this->crearUsuario(['rol' => $this->roles['nivel1']]);

        $resultado = $this->eliminar($id);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('ROLE_RULES_4', $resultado['codigo']);
        $this->assertSame(1, (int)$this->obtenerUsuario($id)['estatus']);
    }

    public function testNoEliminaUsuarioNivel2(): void
    {
        $id = $this->crearUsuario(['rol' => $this->roles['nivel2']]);

        $resultado = $this->eliminar($id);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('ROLE_RULES_4', $resultado['codigo']);
        $this->assertSame(1, (int)$this->obtenerUsuario($id)['estatus']);
    }
}
