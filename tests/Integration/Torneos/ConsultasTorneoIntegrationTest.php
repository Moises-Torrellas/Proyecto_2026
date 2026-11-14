<?php

namespace tests\Integration\Torneos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloTorneos;
use App\modelo\Conexion;
use PDO;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("torneos")]
class ConsultasTorneoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloTorneos $modelo;

    protected function setUp(): void
    {
        $dsn = 'mysql:host=' . (_DB_HOST_ ?: '127.0.0.1') . ';dbname=' . (_DB_NAME_ ?: 'cannibalsbd2') . ';charset=utf8';
        $this->pdo = new PDO($dsn, _DB_USER_ ?: 'root', _DB_PASS_ ?: '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $this->pdo);

        $this->modelo = new ModeloTorneos();

        $this->limpiarTablas();
        $this->sembrarDatosBase();
    }

    protected function tearDown(): void
    {
        if ($this->pdo) {
            $this->limpiarTablas();
            $reflector = new ReflectionClass(Conexion::class);
            $propiedad = $reflector->getProperty('instancia');
            $propiedad->setValue(null, null);
            $this->pdo = null;
        }
        parent::tearDown();
    }

    private function limpiarTablas(): void
    {
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE participaciones;");
        $this->pdo->exec("TRUNCATE TABLE torneos;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        $fManana  = date('Y-m-d', strtotime('+1 day'));
        $fPasado  = date('Y-m-d', strtotime('+2 days'));
        $fMes     = date('Y-m-d', strtotime('+30 days'));

        $fPasada1 = date('Y-m-d', strtotime('-20 days'));
        $fPasada2 = date('Y-m-d', strtotime('-10 days'));

        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (1, 'TORNEO NACIONAL ELITE', '{$fManana}', '{$fMes}', 'Complejo Barquisimeto', 1),
            (2, 'COPA INTERNA CANNIBALS', '{$fPasado}', '{$fMes}', 'Cancha Principal', 1),
            (3, 'LIGA REGIONAL SENIOR', '{$fPasada1}', '{$fPasada2}', 'Polideportivo Valencia', 3)");
    }

    public function testConsultarTorneosGeneralSinFiltros(): void
    {
        $datos = ['accion' => 'consultar'];
        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(3, $resultado['datos'], 'Deben listarse los 3 torneos sembrados.');
        $this->assertEquals(1, $resultado['datos'][0]['codigo_torneo']);
        $this->assertEquals(2, $resultado['datos'][1]['codigo_torneo']);
        $this->assertEquals(3, $resultado['datos'][2]['codigo_torneo']);
    }

    public function testConsultarTorneosConFiltroTexto(): void
    {
        $datos = [
            'accion' => 'consultar',
            'filtro' => 'Elite'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('TORNEO NACIONAL ELITE', $resultado['datos'][0]['nombre']);
    }

    public function testConsultarTorneosConFiltroEstatus(): void
    {
        // Filtrar solo estatus 3 (Finalizados)
        $datos = [
            'accion'  => 'consultar',
            'estatus' => 3
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('LIGA REGIONAL SENIOR', $resultado['datos'][0]['nombre']);
    }

    public function testConsultarTorneosConRangoDeFechas(): void
    {
        $fInicio = date('Y-m-d', strtotime('-30 days'));
        $fFin    = date('Y-m-d', strtotime('-5 days'));

        $datos = [
            'accion'       => 'consultar',
            'fecha_inicio' => $fInicio,
            'fecha_fin'    => $fFin
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('LIGA REGIONAL SENIOR', $resultado['datos'][0]['nombre']);
    }

    public function testBuscarTorneoPorCodigoExistente(): void
    {
        // Vía ProcesarDatos acción buscar
        $resProcesar = $this->modelo->ProcesarDatos(['accion' => 'buscar', 'codigo_torneo' => 1]);
        $this->assertEquals('buscar', $resProcesar['accion']);
        $this->assertNotEmpty($resProcesar['datos']);
        $this->assertEquals('TORNEO NACIONAL ELITE', $resProcesar['datos'][0]['nombre']);

        // Vía método directo Buscar(1)
        $resDirecto = $this->modelo->Buscar(1);
        $this->assertEquals('buscar', $resDirecto['accion']);
        $this->assertEquals('TORNEO NACIONAL ELITE', $resDirecto['datos'][0]['nombre']);
    }

    public function testConsultarTorneosProximosEn1o2Dias(): void
    {
        // El torneo 1 inicia mañana (+1 día) y el torneo 2 inicia pasado mañana (+2 días), ambos estatus 1
        $proximos = $this->modelo->ConsultarProximos();

        $this->assertIsArray($proximos);
        $this->assertCount(2, $proximos, 'Deben encontrarse exactamente 2 torneos próximos a iniciar en 1 o 2 días.');
        $nombres = array_column($proximos, 'nombre');
        $this->assertContains('TORNEO NACIONAL ELITE', $nombres);
        $this->assertContains('COPA INTERNA CANNIBALS', $nombres);
    }

    public function testProcesarDatosRechazaDatosVacios(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $this->modelo->ProcesarDatos([]);
    }

    public function testProcesarDatosRechazaAccionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción no es válida');

        $this->modelo->ProcesarDatos(['accion' => 'accion_inexistente']);
    }
}

