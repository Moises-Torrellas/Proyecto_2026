<?php

declare(strict_types=1);

namespace tests\Integration\Roles;

use App\modelo\Conexion;
use App\modelo\ModeloRoles;
use PDO;
use ReflectionClass;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('integ')]
#[Group('roles')]
#[Group('rolesRegistrar')]
class RegistrarRolIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloRoles $modelo;

    private int $modulo;
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
        $this->crearPermisosPrueba();

        $this->modelo = new ModeloRoles();
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

        $permisos = "SELECT id_permiso FROM (SELECT id_permiso FROM permisos WHERE clave LIKE 'integ\\_rol\\_%') AS t";
        $usuarios = "SELECT idUsuario FROM (SELECT idUsuario FROM usuarios WHERE cedulaUsuario LIKE '9999%') AS t";
        $roles = "SELECT id_rol FROM (SELECT id_rol FROM roles WHERE nombre_rol LIKE 'Integ Roles%') AS t";

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("DELETE FROM excepciones WHERE id_permiso IN ($permisos) OR id_usuario IN ($usuarios);");
        $this->pdo->exec("DELETE FROM permisos_rol WHERE id_permiso IN ($permisos) OR id_rol IN ($roles);");
        $this->pdo->exec("DELETE FROM bitacora WHERE idUsuario IN ($usuarios) OR acciones LIKE 'Registro al usuario: 9999%';");
        $this->pdo->exec("DELETE FROM usuarios WHERE cedulaUsuario LIKE '9999%';");
        $this->pdo->exec("DELETE FROM roles WHERE nombre_rol LIKE 'Integ Roles%';");
        $this->pdo->exec("DELETE FROM permisos WHERE id_permiso IN ($permisos);");
        $this->pdo->exec("DELETE FROM modulos WHERE nombre_modulo LIKE 'Integ Roles%';");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function crearPermisosPrueba(): void
    {
        $this->pdo->exec("INSERT INTO modulos (nombre_modulo, descripcion, icono, estatus) VALUES ('Integ Roles Modulo', 'Modulo de prueba', 'circle-minus', 1)");
        $this->modulo = (int)$this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("INSERT INTO permisos (id_modulo, nombre, clave, descripcion, estatus) VALUES (:modulo, :nombre, :clave, 'Permiso de prueba', 1)");
        foreach (['uno', 'dos', 'tres'] as $sufijo) {
            $stmt->execute([':modulo' => $this->modulo, ':nombre' => "Integ Rol Permiso {$sufijo}", ':clave' => "integ_rol_{$sufijo}"]);
            $this->permisos[] = (int)$this->pdo->lastInsertId();
        }
    }

    private function crearRol(string $nombre, int $nivel = 3, string $descripcion = 'Descripcion De Prueba'): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO roles (nombre_rol, descripcion, nivel_rol, estatus) VALUES (:nombre, :descripcion, :nivel, 1)");
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':nivel' => $nivel]);
        return (int)$this->pdo->lastInsertId();
    }

    private function obtenerRolPorNombre(string $nombre): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE nombre_rol = :nombre");
        $stmt->execute([':nombre' => $nombre]);
        return $stmt->fetch();
    }

    private function contarRoles(string $nombre): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM roles WHERE nombre_rol = :nombre");
        $stmt->execute([':nombre' => $nombre]);
        return (int)$stmt->fetchColumn();
    }

    private function permisosDelRol(int $idRol): array
    {
        $stmt = $this->pdo->prepare("SELECT id_permiso FROM permisos_rol WHERE id_rol = :rol ORDER BY id_permiso");
        $stmt->execute([':rol' => $idRol]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function registrar(array $datos): array
    {
        return $this->modelo->procesarDatos(array_merge(['accion' => 'incluir'], $datos));
    }

    public function testRegistroExitosoGuardaRol(): void
    {
        $resultado = $this->registrar(['nombre' => '  integ roles alfa ', 'descripcion' => 'rol para pruebas']);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $rol = $this->obtenerRolPorNombre('Integ Roles Alfa');
        $this->assertNotFalse($rol, 'El rol debe existir en la BD.');
        $this->assertSame('Rol Para Pruebas', $rol['descripcion']);
        $this->assertSame(3, (int)$rol['nivel_rol'], 'Los roles nuevos son de nivel 3.');
        $this->assertSame(1, (int)$rol['estatus']);
    }

    public function testRolRegistradoApareceEnConsulta(): void
    {
        $this->registrar(['nombre' => 'Integ Roles Alfa', 'descripcion' => 'Rol Para Pruebas']);

        $datos = $this->modelo->consultar(['filtro' => 'Integ Roles Alfa'])['datos'];

        $this->assertCount(1, $datos);
        $this->assertSame('Integ Roles Alfa', $datos[0]['nombre_rol']);
    }

    public function testRolNuevoNaceSinPermisos(): void
    {
        $this->registrar(['nombre' => 'Integ Roles Alfa']);
        $idRol = (int)$this->obtenerRolPorNombre('Integ Roles Alfa')['id_rol'];

        $this->assertSame([], $this->permisosDelRol($idRol));

        $matriz = array_column($this->modelo->CargarPermisos($idRol)['datos'], null, 'id_permiso');
        $this->assertSame(0, (int)$matriz[$this->permisos[0]]['asignado']);
    }

    public function testRegistroSinDescripcion(): void
    {
        $resultado = $this->registrar(['nombre' => 'Integ Roles Alfa', 'descripcion' => '']);

        $this->assertSame('exito', $resultado['accion']);
        $rol = $this->obtenerRolPorNombre('Integ Roles Alfa');
        $this->assertNotFalse($rol);
        $this->assertStringStartsWith('Sin Descrip', $rol['descripcion']);
    }

    public function testRechazaNombreRepetido(): void
    {
        $this->crearRol('Integ Roles Alfa');

        $resultado = $this->registrar(['nombre' => 'integ roles alfa', 'descripcion' => 'Otro']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
        $this->assertSame(1, $this->contarRoles('Integ Roles Alfa'));
    }

    public function testNombreInvalidoNoInserta(): void
    {
        try {
            $this->registrar(['nombre' => 'Integ Roles 2']);
            $this->fail('Se esperaba una excepción por nombre inválido.');
        } catch (Exception $e) {
            $this->assertSame('Nombre inválido.', $e->getMessage());
        }

        $this->assertSame(0, $this->contarRoles('Integ Roles 2'));
    }

    public function testNombreDemasiadoLargoNoInserta(): void
    {
        $nombre = 'Integ Roles ' . str_repeat('a', 25);

        try {
            $this->registrar(['nombre' => $nombre]);
            $this->fail('Se esperaba una excepción por nombre demasiado largo.');
        } catch (Exception $e) {
            $this->assertSame('Nombre inválido.', $e->getMessage());
        }

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM roles WHERE nombre_rol LIKE 'Integ Roles aaaa%'");
        $this->assertSame(0, (int)$stmt->fetchColumn());
    }
}
