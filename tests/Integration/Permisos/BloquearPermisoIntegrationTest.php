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
#[Group('permisosBloquear')]
class BloquearPermisoIntegrationTest extends TestCase
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

    private function obtenerPermiso(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM permisos WHERE id_permiso = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function cambiarEstado(int $id, int $estadoActual): array
    {
        return $this->modelo->ProcesarDatos(['accion' => 'bloquear', 'id' => (string)$id, 'bloqueo' => (string)$estadoActual]);
    }

    public function testBloquearPermiso(): void
    {
        $id = $this->crearPermiso('Integ Prueba Bloqueo', 'integ_bloqueo');

        $resultado = $this->cambiarEstado($id, 1);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame(2, (int)$this->obtenerPermiso($id)['estatus']);
    }

    public function testDesbloquearPermiso(): void
    {
        $id = $this->crearPermiso('Integ Prueba Bloqueo', 'integ_bloqueo', 2);

        $resultado = $this->cambiarEstado($id, 2);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame(1, (int)$this->obtenerPermiso($id)['estatus']);
    }

    public function testCicloCompletoBloqueoDesbloqueo(): void
    {
        $id = $this->crearPermiso('Integ Prueba Bloqueo', 'integ_bloqueo');

        $this->assertSame('exito', $this->cambiarEstado($id, 1)['accion']);
        $this->assertSame('exito', $this->cambiarEstado($id, 2)['accion']);

        $this->assertSame(1, (int)$this->obtenerPermiso($id)['estatus']);
    }

    public function testRechazaPermisoInexistente(): void
    {
        $resultado = $this->cambiarEstado(99999999, 1);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('No se encontró el permiso o el estatus ya era el solicitado.', $resultado['codigo']);
    }

    public function testRechazaEstadoQueYaTiene(): void
    {
        $id = $this->crearPermiso('Integ Prueba Bloqueo', 'integ_bloqueo', 2);

        $resultado = $this->cambiarEstado($id, 1);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(2, (int)$this->obtenerPermiso($id)['estatus']);
    }

    public function testEstadoSeReflejaEnConsulta(): void
    {
        $id = $this->crearPermiso('Integ Prueba Bloqueo', 'integ_bloqueo');

        $this->cambiarEstado($id, 1);

        $datos = $this->modelo->Consultar(['filtro' => 'integ_bloqueo'])['datos'];
        $this->assertCount(1, $datos, 'Los permisos bloqueados siguen apareciendo en el listado.');
        $this->assertSame(2, (int)$datos[0]['estatus_permiso']);
    }

    public function testPermisoBloqueadoNoSePuedeModificar(): void
    {
        $id = $this->crearPermiso('Integ Prueba Bloqueo', 'integ_bloqueo');
        $this->cambiarEstado($id, 1);

        $modificar = fn() => $this->modelo->ProcesarDatos(['accion' => 'modificar', 'id' => (string)$id, 'nombre' => 'Integ Prueba Editado']);

        $rechazo = $modificar();
        $this->assertSame('error', $rechazo['accion']);
        $this->assertSame('No se puede modificar un permiso bloqueado.', $rechazo['codigo']);

        $this->cambiarEstado($id, 2);
        $this->assertSame('exito', $modificar()['accion']);
        $this->assertSame('Integ Prueba Editado', $this->obtenerPermiso($id)['nombre']);
    }
}
