<?php

declare(strict_types=1);

namespace tests\Integration\Permisos;

use App\modelo\Conexion;
use App\modelo\ModeloPermisos;
use PDO;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('integ')]
#[Group('permisos')]
#[Group('permisosConsultas')]
class ConsultasPermisoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloPermisos $modelo;

    private int $moduloActivo;
    private int $moduloInactivo;

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

    private function cargarPermisos(): void
    {
        $this->ids['ver'] = $this->crearPermiso('Integ Prueba Ver', 'integ_consulta_ver');
        $this->ids['crear'] = $this->crearPermiso('Integ Prueba Crear', 'integ_consulta_crear');
        $this->ids['bloqueado'] = $this->crearPermiso('Integ Prueba Bloqueado', 'integ_consulta_bloq', 2);
    }

    private function clavesDePrueba(array $resultado): array
    {
        $claves = array_values(array_filter(
            array_column($resultado['datos'], 'clave'),
            fn($c) => str_starts_with($c, 'integ_consulta')
        ));
        sort($claves);
        return $claves;
    }

    public function testListadoGeneralConDatosDelModulo(): void
    {
        $this->cargarPermisos();

        $resultado = $this->modelo->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame(['integ_consulta_bloq', 'integ_consulta_crear', 'integ_consulta_ver'], $this->clavesDePrueba($resultado));

        $ver = array_values(array_filter($resultado['datos'], fn($p) => $p['clave'] === 'integ_consulta_ver'))[0];
        $this->assertSame('Integ Prueba Ver', $ver['nombre_permiso']);
        $this->assertSame($this->moduloActivo, (int)$ver['id_modulo']);
        $this->assertSame('Integ Permisos Activo', $ver['nombre_modulo']);
        $this->assertSame(1, (int)$ver['estatus_permiso']);
        $this->assertSame(1, (int)$ver['estatus_modulo']);
    }

    public function testListadoOrdenadoPorModuloYPermiso(): void
    {
        $this->cargarPermisos();

        $pares = array_map(
            fn($p) => [(int)$p['id_modulo'], (int)$p['id_permiso']],
            $this->modelo->Consultar()['datos']
        );
        $ordenados = $pares;
        sort($ordenados);

        $this->assertSame($ordenados, $pares);
    }

    public function testBusquedaPorNombre(): void
    {
        $this->cargarPermisos();

        $resultado = $this->modelo->Consultar(['filtro' => 'Integ Prueba Cre']);

        $this->assertSame(['integ_consulta_crear'], $this->clavesDePrueba($resultado));
    }

    public function testBusquedaPorClave(): void
    {
        $this->cargarPermisos();

        $resultado = $this->modelo->Consultar(['filtro' => 'integ_consulta_v']);

        $this->assertSame(['integ_consulta_ver'], $this->clavesDePrueba($resultado));
    }

    public function testBusquedaSinCoincidencias(): void
    {
        $this->cargarPermisos();

        $resultado = $this->modelo->Consultar(['filtro' => 'zzz_no_existe_zzz']);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    public function testBuscarPorId(): void
    {
        $this->cargarPermisos();

        $resultado = $this->modelo->ProcesarDatos(['accion' => 'buscar', 'id' => (string)$this->ids['ver']]);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertSame('integ_consulta_ver', $resultado['datos'][0]['clave']);
        $this->assertSame('Descripcion De Prueba', $resultado['datos'][0]['descripcion']);
    }

    public function testBuscarPermisoBloqueado(): void
    {
        $this->cargarPermisos();

        $resultado = $this->modelo->ProcesarDatos(['accion' => 'buscar', 'id' => (string)$this->ids['bloqueado']]);

        $this->assertCount(1, $resultado['datos']);
        $this->assertSame(2, (int)$resultado['datos'][0]['estatus']);
    }

    public function testBuscarPermisoInexistente(): void
    {
        $resultado = $this->modelo->ProcesarDatos(['accion' => 'buscar', 'id' => '99999999']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }
}
