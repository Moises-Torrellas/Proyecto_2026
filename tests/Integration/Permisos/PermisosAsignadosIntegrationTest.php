<?php

declare(strict_types=1);

namespace tests\Integration\Permisos;

use App\modelo\Conexion;
use App\modelo\ModeloPermisos;
use PDO;
use ReflectionClass;
use App\modelo\ModeloRoles;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('integ')]
#[Group('permisos')]
#[Group('permisosAsignados')]
class PermisosAsignadosIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloPermisos $modelo;

    private int $moduloActivo;
    private int $moduloInactivo;

    private int $idRol;
    private array $permisos = [];

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
        $this->moduloActivo = $this->crearModulo('Integ Permisos Activo', 1);
        $this->moduloInactivo = $this->crearModulo('Integ Permisos Inactivo', 2);

        $this->modelo = new ModeloPermisos();
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

        $permisos = "SELECT id_permiso FROM (SELECT id_permiso FROM permisos
                        WHERE clave LIKE 'integ\\_%' OR nombre LIKE 'Integ Prueba%'
                        OR id_modulo IN (SELECT id_modulo FROM modulos WHERE nombre_modulo LIKE 'Integ Permisos%')) AS t";
        $usuarios = "SELECT idUsuario FROM (SELECT idUsuario FROM usuarios WHERE cedulaUsuario LIKE '9999%') AS t";
        $roles = "SELECT id_rol FROM (SELECT id_rol FROM roles WHERE nombre_rol LIKE 'Integ Permisos%') AS t";

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("DELETE FROM excepciones WHERE id_permiso IN ($permisos) OR id_usuario IN ($usuarios);");
        $this->pdo->exec("DELETE FROM permisos_rol WHERE id_permiso IN ($permisos) OR id_rol IN ($roles);");
        $this->pdo->exec("DELETE FROM bitacora WHERE idUsuario IN ($usuarios) OR acciones LIKE 'Registro al usuario: 9999%';");
        $this->pdo->exec("DELETE FROM usuarios WHERE cedulaUsuario LIKE '9999%';");
        $this->pdo->exec("DELETE FROM roles WHERE nombre_rol LIKE 'Integ Permisos%';");
        $this->pdo->exec("DELETE FROM permisos WHERE id_permiso IN ($permisos);");
        $this->pdo->exec("DELETE FROM modulos WHERE nombre_modulo LIKE 'Integ Permisos%';");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function crearModulo(string $nombre, int $estatus): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO modulos (nombre_modulo, descripcion, icono, estatus) VALUES (:nombre, 'Modulo de prueba', 'circle-minus', :estatus)");
        $stmt->execute([':nombre' => $nombre, ':estatus' => $estatus]);
        return (int)$this->pdo->lastInsertId();
    }

    private function crearPermiso(string $nombre, string $clave, int $estatus = 1, ?int $modulo = null, string $descripcion = 'Descripcion De Prueba'): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO permisos (id_modulo, nombre, clave, descripcion, estatus) VALUES (:modulo, :nombre, :clave, :descripcion, :estatus)");
        $stmt->execute([
            ':modulo'      => $modulo ?? $this->moduloActivo,
            ':nombre'      => $nombre,
            ':clave'       => $clave,
            ':descripcion' => $descripcion,
            ':estatus'     => $estatus,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    private function obtenerPermisoPorClave(string $clave): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM permisos WHERE clave = :clave");
        $stmt->execute([':clave' => $clave]);
        return $stmt->fetch();
    }

    private function crearRol(string $nombre, int $nivel = 3): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO roles (nombre_rol, descripcion, nivel_rol, estatus) VALUES (:nombre, 'Rol de prueba', :nivel, 1)");
        $stmt->execute([':nombre' => $nombre, ':nivel' => $nivel]);
        return (int)$this->pdo->lastInsertId();
    }

    private function asignarPermisosRol(int $idRol, array $idsPermisos): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO permisos_rol (id_permiso, id_rol) VALUES (:permiso, :rol)");
        foreach ($idsPermisos as $idPermiso) {
            $stmt->execute([':permiso' => $idPermiso, ':rol' => $idRol]);
        }
    }

    private function crearUsuario(int $idRol): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO usuarios
            (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos)
            VALUES ('99990501', 'Usuario', 'Permisos', 'default.png', '0414-9990501', :pass, 'usuario.permisos@integ.test', :rol, 1, 1, 0)");
        $stmt->execute([':pass' => password_hash('Clave.Prueba123', PASSWORD_BCRYPT), ':rol' => $idRol]);
        return (int)$this->pdo->lastInsertId();
    }

    private function crearExcepcion(int $idUsuario, int $idPermiso, int $tipo): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO excepciones (id_permiso, id_usuario, tipo) VALUES (:permiso, :usuario, :tipo)");
        $stmt->execute([':permiso' => $idPermiso, ':usuario' => $idUsuario, ':tipo' => $tipo]);
    }

    private function preparar(): void
    {
        $this->permisos = [
            $this->crearPermiso('Integ Prueba Uno', 'integ_matriz_uno'),
            $this->crearPermiso('Integ Prueba Dos', 'integ_matriz_dos'),
            $this->crearPermiso('Integ Prueba Tres', 'integ_matriz_tres'),
        ];
        $this->idRol = $this->crearRol('Integ Permisos Rol');
        $this->asignarPermisosRol($this->idRol, [$this->permisos[0], $this->permisos[1]]);
    }

    private function matrizRol(int $idRol): array
    {
        $resultado = $this->modelo->permisosRoles($idRol);
        $this->assertSame('CargarPermisos', $resultado['accion']);
        return array_column($resultado['datos'], null, 'id_permiso');
    }

    private function matrizUsuario(int $idUsuario): array
    {
        $resultado = $this->modelo->permisosUsuarios($idUsuario);
        $this->assertSame('CargarPermisosUsuario', $resultado['accion']);
        return array_column($resultado['datos'], null, 'id_permiso');
    }

    public function testMatrizRolMarcaPermisosAsignados(): void
    {
        $this->preparar();

        $matriz = $this->matrizRol($this->idRol);

        $this->assertSame(1, (int)$matriz[$this->permisos[0]]['asignado']);
        $this->assertSame(1, (int)$matriz[$this->permisos[1]]['asignado']);
        $this->assertSame(0, (int)$matriz[$this->permisos[2]]['asignado']);
        $this->assertSame('Integ Permisos Rol', $matriz[$this->permisos[0]]['nombre_rol']);
        $this->assertSame('Integ Permisos Activo', $matriz[$this->permisos[0]]['nombre_modulo']);
    }

    public function testMatrizRolExcluyeModulosInternos(): void
    {
        $this->preparar();

        $modulos = array_unique(array_map('intval', array_column($this->matrizRol($this->idRol), 'id_modulo')));

        $this->assertEmpty(array_intersect($modulos, [1, 2, 3, 4, 5, 8]));
        $this->assertContains($this->moduloActivo, $modulos);
    }

    public function testMatrizRolReflejaLoGuardadoPorRoles(): void
    {
        $this->preparar();

        $roles = new ModeloRoles();
        $resultado = $roles->procesarDatos([
            'accion'   => 'guardar_permisos',
            'id'       => (string)$this->idRol,
            'permisos' => [$this->permisos[1] => 1, $this->permisos[2] => 1],
        ]);
        $this->assertSame('exito', $resultado['accion']);

        $matriz = $this->matrizRol($this->idRol);
        $this->assertSame(0, (int)$matriz[$this->permisos[0]]['asignado']);
        $this->assertSame(1, (int)$matriz[$this->permisos[1]]['asignado']);
        $this->assertSame(1, (int)$matriz[$this->permisos[2]]['asignado']);
    }

    public function testMatrizUsuarioHeredaPermisosDelRol(): void
    {
        $this->preparar();
        $idUsuario = $this->crearUsuario($this->idRol);

        $matriz = $this->matrizUsuario($idUsuario);

        $this->assertSame(1, (int)$matriz[$this->permisos[0]]['asignado']);
        $this->assertSame(1, (int)$matriz[$this->permisos[1]]['asignado']);
        $this->assertSame(0, (int)$matriz[$this->permisos[2]]['asignado']);
        $this->assertSame(2, (int)$matriz[$this->permisos[0]]['excepcion'], '2 = sin excepción.');
        $this->assertSame($this->idRol, (int)$matriz[$this->permisos[0]]['id_rol']);
    }

    public function testMatrizUsuarioAplicaExcepciones(): void
    {
        $this->preparar();
        $idUsuario = $this->crearUsuario($this->idRol);
        $this->crearExcepcion($idUsuario, $this->permisos[0], 0);
        $this->crearExcepcion($idUsuario, $this->permisos[2], 1);

        $matriz = $this->matrizUsuario($idUsuario);

        $this->assertSame(0, (int)$matriz[$this->permisos[0]]['asignado']);
        $this->assertSame(0, (int)$matriz[$this->permisos[0]]['excepcion']);
        $this->assertSame(1, (int)$matriz[$this->permisos[1]]['asignado']);
        $this->assertSame(1, (int)$matriz[$this->permisos[2]]['asignado']);
        $this->assertSame(1, (int)$matriz[$this->permisos[2]]['excepcion']);
    }

    public function testExcepcionesNoAfectanMatrizDelRol(): void
    {
        $this->preparar();
        $idUsuario = $this->crearUsuario($this->idRol);
        $this->crearExcepcion($idUsuario, $this->permisos[0], 0);

        $matriz = $this->matrizRol($this->idRol);

        $this->assertSame(1, (int)$matriz[$this->permisos[0]]['asignado']);
    }

    public function testPermisoNuevoApareceSinAsignar(): void
    {
        $this->preparar();

        $this->modelo->ProcesarDatos([
            'accion' => 'incluir',
            'modulo' => (string)$this->moduloActivo,
            'nombre' => 'Integ Prueba Nuevo',
            'clave'  => 'integ_matriz_nuevo',
        ]);
        $idNuevo = (int)$this->obtenerPermisoPorClave('integ_matriz_nuevo')['id_permiso'];

        $matriz = $this->matrizRol($this->idRol);

        $this->assertArrayHasKey($idNuevo, $matriz);
        $this->assertSame(0, (int)$matriz[$idNuevo]['asignado']);
    }
}
