<?php

declare(strict_types=1);

namespace tests\Integration\Permisos;

use App\modelo\Conexion;
use App\modelo\ModeloPermisos;
use PDO;
use ReflectionClass;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('integ')]
#[Group('permisos')]
#[Group('permisosRegistrar')]
class RegistrarPermisoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloPermisos $modelo;

    private int $moduloActivo;
    private int $moduloInactivo;

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

    private function contarPermisos(string $columna, string $valor): int
    {
        $columna = in_array($columna, ['clave', 'nombre'], true) ? $columna : 'clave';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM permisos WHERE {$columna} = :valor");
        $stmt->execute([':valor' => $valor]);
        return (int)$stmt->fetchColumn();
    }

    private function datosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'accion'      => 'incluir',
            'modulo'      => (string)$this->moduloActivo,
            'nombre'      => '  integ prueba registrar ',
            'clave'       => 'integ_registrar',
            'descripcion' => 'permite registrar en pruebas',
        ], $sobrescribir);
    }

    public function testRegistroExitosoGuardaPermiso(): void
    {
        $resultado = $this->modelo->ProcesarDatos($this->datosValidos());

        $this->assertSame(['accion' => 'exito'], $resultado);

        $permiso = $this->obtenerPermisoPorClave('integ_registrar');
        $this->assertNotFalse($permiso, 'El permiso debe existir en la BD.');
        $this->assertSame($this->moduloActivo, (int)$permiso['id_modulo']);
        $this->assertSame('Integ Prueba Registrar', $permiso['nombre']);
        $this->assertSame('Permite Registrar En Pruebas', $permiso['descripcion']);
        $this->assertSame(1, (int)$permiso['estatus']);
    }

    public function testPermisoRegistradoApareceEnConsulta(): void
    {
        $this->modelo->ProcesarDatos($this->datosValidos());

        $resultado = $this->modelo->Consultar(['filtro' => 'integ_registrar']);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertSame('Integ Permisos Activo', $resultado['datos'][0]['nombre_modulo']);
    }

    public function testRegistroSinDescripcionUsaValorPorDefecto(): void
    {
        $resultado = $this->modelo->ProcesarDatos($this->datosValidos(['descripcion' => '']));

        $this->assertSame('exito', $resultado['accion']);
        $permiso = $this->obtenerPermisoPorClave('integ_registrar');
        $this->assertNotFalse($permiso);
        $this->assertStringStartsWith('Sin Descripci', $permiso['descripcion']);
    }

    public function testRechazaModuloInexistente(): void
    {
        $resultado = $this->modelo->ProcesarDatos($this->datosValidos(['modulo' => '999999']));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(ASSOCIATES, $resultado['codigo']);
        $this->assertSame(0, $this->contarPermisos('clave', 'integ_registrar'));
    }

    public function testRechazaModuloInactivo(): void
    {
        $resultado = $this->modelo->ProcesarDatos($this->datosValidos(['modulo' => (string)$this->moduloInactivo]));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(ASSOCIATES, $resultado['codigo']);
        $this->assertSame(0, $this->contarPermisos('clave', 'integ_registrar'));
    }

    public function testRechazaClaveRepetida(): void
    {
        $this->crearPermiso('Integ Prueba Existente', 'integ_registrar');

        $resultado = $this->modelo->ProcesarDatos($this->datosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('La clave técnica del permiso ya está registrada.', $resultado['codigo']);
        $this->assertSame(1, $this->contarPermisos('clave', 'integ_registrar'));
    }

    public function testRechazaClaveDePermisoBloqueado(): void
    {
        $this->crearPermiso('Integ Prueba Bloqueado', 'integ_registrar', 2);

        $resultado = $this->modelo->ProcesarDatos($this->datosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('La clave técnica del permiso ya está registrada.', $resultado['codigo']);
        $this->assertSame(1, $this->contarPermisos('clave', 'integ_registrar'));
    }

    public function testRechazaNombreRepetido(): void
    {
        $this->crearPermiso('Integ Prueba Registrar', 'integ_otra_clave');

        $resultado = $this->modelo->ProcesarDatos($this->datosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
        $this->assertSame(0, $this->contarPermisos('clave', 'integ_registrar'));
    }

    public function testClaveInvalidaNoInserta(): void
    {
        try {
            $this->modelo->ProcesarDatos($this->datosValidos(['clave' => 'Integ-Clave1']));
            $this->fail('Se esperaba una excepción por clave inválida.');
        } catch (Exception $e) {
            $this->assertStringContainsString('clave del permiso es inválida', $e->getMessage());
        }

        $this->assertSame(0, $this->contarPermisos('nombre', 'Integ Prueba Registrar'));
    }

    public function testNombreInvalidoNoInserta(): void
    {
        try {
            $this->modelo->ProcesarDatos($this->datosValidos(['nombre' => 'Integ Prueba 123']));
            $this->fail('Se esperaba una excepción por nombre inválido.');
        } catch (Exception $e) {
            $this->assertSame('Nombre inválido.', $e->getMessage());
        }

        $this->assertSame(0, $this->contarPermisos('clave', 'integ_registrar'));
    }
}
