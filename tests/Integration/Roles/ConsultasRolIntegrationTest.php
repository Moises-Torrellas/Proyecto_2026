<?php

declare(strict_types=1);

namespace tests\Integration\Roles;

use App\modelo\Conexion;
use App\modelo\ModeloRoles;
use PDO;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('integ')]
#[Group('roles')]
#[Group('rolesConsultas')]
class ConsultasRolIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloRoles $modelo;

    private int $modulo;
    private array $permisos = [];

    private array $ids = [];

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

    private function cargarRoles(): void
    {
        $this->ids['entrenador'] = $this->crearRol('Integ Roles Entrenador');
        $this->ids['delegado'] = $this->crearRol('Integ Roles Delegado');
        $this->ids['nivel1'] = $this->crearRol('Integ Roles Soporte', 1);
        $this->ids['nivel2'] = $this->crearRol('Integ Roles Admin', 2);
    }

    private function rolesDePrueba(array $resultado): array
    {
        $nombres = array_values(array_filter(
            array_column($resultado['datos'], 'nombre_rol'),
            fn($n) => str_starts_with($n, 'Integ Roles')
        ));
        sort($nombres);
        return $nombres;
    }

    public function testListadoOcultaRolesProtegidos(): void
    {
        $this->cargarRoles();

        $resultado = $this->modelo->consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame(['Integ Roles Delegado', 'Integ Roles Entrenador'], $this->rolesDePrueba($resultado));
        foreach ($resultado['datos'] as $rol) {
            $this->assertNotContains((int)$rol['nivel_rol'], [1, 2], 'No deben listarse roles protegidos.');
        }
    }

    public function testListadoOrdenadoPorId(): void
    {
        $this->cargarRoles();

        $ids = array_map('intval', array_column($this->modelo->consultar()['datos'], 'id_rol'));
        $ordenados = $ids;
        sort($ordenados);

        $this->assertSame($ordenados, $ids);
    }

    public function testBusquedaPorNombre(): void
    {
        $this->cargarRoles();

        $resultado = $this->modelo->consultar(['filtro' => 'Integ Roles Entre']);

        $this->assertSame(['Integ Roles Entrenador'], $this->rolesDePrueba($resultado));
    }

    public function testBusquedaNoMuestraRolesProtegidos(): void
    {
        $this->cargarRoles();

        $resultado = $this->modelo->consultar(['filtro' => 'Integ Roles Soporte']);

        $this->assertSame([], $resultado['datos']);
    }

    public function testBusquedaSinCoincidencias(): void
    {
        $this->cargarRoles();

        $resultado = $this->modelo->consultar(['filtro' => 'zzz no existe zzz']);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    public function testReporteFiltradoPorNombre(): void
    {
        $this->cargarRoles();

        $resultado = $this->modelo->procesarDatos(['accion' => 'reporte', 'nombre' => 'delegado']);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame(['Integ Roles Delegado'], $this->rolesDePrueba($resultado));
    }

    public function testBuscarPorId(): void
    {
        $this->cargarRoles();

        $resultado = $this->modelo->procesarDatos(['accion' => 'buscar', 'id' => (string)$this->ids['entrenador']]);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([[
            'nombre_rol'  => 'Integ Roles Entrenador',
            'id_rol'      => $this->ids['entrenador'],
            'descripcion' => 'Descripcion De Prueba',
        ]], $resultado['datos']);
    }

    public function testBuscarRolInexistente(): void
    {
        $resultado = $this->modelo->procesarDatos(['accion' => 'buscar', 'id' => '99999999']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }
}
