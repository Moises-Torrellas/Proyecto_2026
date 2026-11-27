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
#[Group('permisosModificar')]
class ModificarPermisoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloPermisos $modelo;

    private int $moduloActivo;
    private int $moduloInactivo;

    private int $idPermiso;

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

    private function obtenerPermiso(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM permisos WHERE id_permiso = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function contarPermisos(string $columna, string $valor): int
    {
        $columna = in_array($columna, ['clave', 'nombre'], true) ? $columna : 'clave';
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM permisos WHERE {$columna} = :valor");
        $stmt->execute([':valor' => $valor]);
        return (int)$stmt->fetchColumn();
    }

    private function prepararPermiso(int $estatus = 1): void
    {
        $this->idPermiso = $this->crearPermiso('Integ Prueba Original', 'integ_modificar', $estatus, null, 'Descripcion Original');
    }

    private function modificar(array $datos): array
    {
        return $this->modelo->ProcesarDatos(array_merge([
            'accion' => 'modificar',
            'id'     => (string)$this->idPermiso,
        ], $datos));
    }

    public function testModificacionExitosa(): void
    {
        $this->prepararPermiso();

        $resultado = $this->modificar(['nombre' => 'integ prueba cambiado', 'descripcion' => 'nueva descripcion']);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $permiso = $this->obtenerPermiso($this->idPermiso);
        $this->assertSame('Integ Prueba Cambiado', $permiso['nombre']);
        $this->assertSame('Nueva Descripcion', $permiso['descripcion']);
    }

    public function testNoModificaClaveNiModulo(): void
    {
        $this->prepararPermiso();
        $otroModulo = $this->crearModulo('Integ Permisos Otro', 1);

        $resultado = $this->modificar([
            'nombre' => 'Integ Prueba Cambiado',
            'clave'  => 'integ_clave_nueva',
            'modulo' => (string)$otroModulo,
        ]);

        $this->assertSame('exito', $resultado['accion']);
        $permiso = $this->obtenerPermiso($this->idPermiso);
        $this->assertSame('integ_modificar', $permiso['clave']);
        $this->assertSame($this->moduloActivo, (int)$permiso['id_modulo']);
    }

    public function testModificacionSinDescripcionConservaLaAnterior(): void
    {
        $this->prepararPermiso();

        $resultado = $this->modificar(['nombre' => 'Integ Prueba Cambiado', 'descripcion' => '']);

        $this->assertSame('exito', $resultado['accion']);
        $permiso = $this->obtenerPermiso($this->idPermiso);
        $this->assertSame('Integ Prueba Cambiado', $permiso['nombre']);
        $this->assertSame('Descripcion Original', $permiso['descripcion']);
    }

    public function testConservaSuPropioNombre(): void
    {
        $this->prepararPermiso();

        $resultado = $this->modificar(['nombre' => 'Integ Prueba Original', 'descripcion' => 'otra descripcion']);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame('Otra Descripcion', $this->obtenerPermiso($this->idPermiso)['descripcion']);
    }

    public function testRechazaPermisoInexistente(): void
    {
        $this->idPermiso = 99999999;

        $resultado = $this->modificar(['nombre' => 'Integ Prueba Cambiado']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('El permiso seleccionado no existe.', $resultado['codigo']);
        $this->assertSame(0, $this->contarPermisos('nombre', 'Integ Prueba Cambiado'));
    }

    public function testRechazaPermisoBloqueado(): void
    {
        $this->prepararPermiso(2);

        $resultado = $this->modificar(['nombre' => 'Integ Prueba Cambiado']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('No se puede modificar un permiso bloqueado.', $resultado['codigo']);
        $this->assertSame('Integ Prueba Original', $this->obtenerPermiso($this->idPermiso)['nombre']);
    }

    public function testRechazaNombreDeOtroPermiso(): void
    {
        $this->prepararPermiso();
        $this->crearPermiso('Integ Prueba Ocupado', 'integ_ocupado');

        $resultado = $this->modificar(['nombre' => 'Integ Prueba Ocupado']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Ya existe otro permiso con ese nombre.', $resultado['codigo']);
        $this->assertSame('Integ Prueba Original', $this->obtenerPermiso($this->idPermiso)['nombre'], 'La transacción debe revertirse.');
    }
}
