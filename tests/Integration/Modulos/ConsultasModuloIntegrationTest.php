<?php

declare(strict_types=1);

namespace tests\Integration\Modulos;

use App\modelo\Conexion;
use App\modelo\ModeloModulos;
use PDO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('integ')]
#[Group('modulos')]
#[Group('modulosConsultar')]
class ConsultasModuloIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloModulos $modelo;

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

        $this->modelo = new ModeloModulos();
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

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("DELETE FROM modulos WHERE nombre_modulo LIKE 'Integ Modulos%';");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function crearModulo(string $nombre, int $estatus = 1): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO modulos (nombre_modulo, descripcion, icono, estatus) VALUES (:nombre, 'Modulo De Prueba', 'circle-minus', :estatus)");
        $stmt->execute([':nombre' => $nombre, ':estatus' => $estatus]);
        return (int)$this->pdo->lastInsertId();
    }

    private function buscar(string $id): array
    {
        return $this->modelo->ProcesarDatos(['accion' => 'buscar', 'id' => $id]);
    }

    public function testListadoExcluyeModulosDeSesion(): void
    {
        $resultado = $this->modelo->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $ids = array_map('intval', array_column($resultado['datos'], 'id_modulo'));
        $this->assertNotContains(4, $ids);
        $this->assertNotContains(5, $ids);
        $this->assertNotContains(8, $ids);
        $this->assertContains(1, $ids, 'El módulo de Usuarios debe aparecer.');
    }

    public function testListadoOrdenadoPorId(): void
    {
        $id = $this->crearModulo('Integ Modulos Alfa');

        $ids = array_map('intval', array_column($this->modelo->Consultar()['datos'], 'id_modulo'));

        $ordenados = $ids;
        sort($ordenados);
        $this->assertSame($ordenados, $ids);
        $this->assertSame($id, end($ids));
    }

    public function testListadoIncluyeInactivos(): void
    {
        $id = $this->crearModulo('Integ Modulos Inactivo', 0);

        $porId = array_column($this->modelo->Consultar()['datos'], null, 'id_modulo');

        $this->assertArrayHasKey($id, $porId);
        $this->assertSame(0, (int)$porId[$id]['estatus']);
        $this->assertSame('circle-minus', $porId[$id]['icono']);
    }

    public function testBusquedaPorNombre(): void
    {
        $this->crearModulo('Integ Modulos Alfa');
        $this->crearModulo('Integ Modulos Beta');

        $datos = $this->modelo->Consultar(['filtro' => 'Modulos Bet'])['datos'];

        $this->assertSame(['Integ Modulos Beta'], array_column($datos, 'nombre_modulo'));
    }

    public function testBusquedaNoMuestraModulosDeSesion(): void
    {
        $datos = $this->modelo->Consultar(['filtro' => 'Sesion'])['datos'];

        $this->assertSame([], array_values(array_filter(
            array_map('intval', array_column($datos, 'id_modulo')),
            fn($id) => in_array($id, [4, 5, 8], true)
        )));
    }

    public function testBusquedaSinCoincidencias(): void
    {
        $this->assertSame(['accion' => 'consultar', 'datos' => []], $this->modelo->Consultar(['filtro' => 'Integ Modulos No Existe']));
        $this->assertSame(['accion' => 'consultar', 'datos' => []], $this->modelo->Consultar(['filtro' => "' OR 1=1 --"]));
    }

    public function testBuscarPorId(): void
    {
        $id = $this->crearModulo('Integ Modulos Alfa');

        $resultado = $this->buscar((string)$id);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertSame('Integ Modulos Alfa', $resultado['datos'][0]['nombre_modulo']);
        $this->assertSame('Modulo De Prueba', $resultado['datos'][0]['descripcion']);
    }

    public function testBuscarModuloInactivo(): void
    {
        $id = $this->crearModulo('Integ Modulos Inactivo', 0);

        $resultado = $this->buscar((string)$id);

        $this->assertCount(1, $resultado['datos']);
        $this->assertSame(0, (int)$resultado['datos'][0]['estatus']);
    }

    public function testBuscarInexistente(): void
    {
        $this->assertSame(['accion' => 'buscar', 'datos' => []], $this->buscar('99999999'));
    }
}
