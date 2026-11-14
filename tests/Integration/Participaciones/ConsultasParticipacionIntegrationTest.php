<?php

namespace tests\Integration\Participaciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloParticipaciones;
use App\modelo\Conexion;
use PDO;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("participaciones")]
class ConsultasParticipacionIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloParticipaciones $modelo;

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

        $this->modelo = new ModeloParticipaciones();

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
        $this->pdo->exec("TRUNCATE TABLE palmares_individual;");
        $this->pdo->exec("TRUNCATE TABLE palmares_grupal;");
        $this->pdo->exec("TRUNCATE TABLE detalles_participacion;");
        $this->pdo->exec("TRUNCATE TABLE participaciones;");
        $this->pdo->exec("TRUNCATE TABLE detalles_equipos;");
        $this->pdo->exec("TRUNCATE TABLE equipos;");
        $this->pdo->exec("TRUNCATE TABLE torneos;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        $fInicio1 = date('Y-m-d', strtotime('+10 days'));
        $fFin1    = date('Y-m-d', strtotime('+20 days'));

        $fInicio2 = date('Y-m-d', strtotime('-20 days'));
        $fFin2    = date('Y-m-d', strtotime('-10 days'));

        // Torneo 1 (Por disputarse, estatus 1) y Torneo 2 (Finalizado, estatus 3)
        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (1, 'COPA ORO 2026', '{$fInicio1}', '{$fFin1}', 'Polideportivo Central', 1),
            (2, 'LIGA DE VERANO PASADA', '{$fInicio2}', '{$fFin2}', 'Cancha 2', 3)");

        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES 
            (1, 'Cannibals Alfa'),
            (2, 'Cannibals Beta')");

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2009-05-10', 'foto1.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2009-08-15', 'foto2.jpg')");

        // Participaciones:
        // Part 1: Torneo 1, Equipo 1 (con 2 atletas en detalles_participacion)
        // Part 2: Torneo 2, Equipo 2 (con 0 atletas)
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES 
            (1, 1, 1),
            (2, 2, 2)");

        $this->pdo->exec("INSERT INTO detalles_participacion (codigo_participacion, codigo_atleta, goles, asistencias) VALUES 
            (1, 1, 3, 1),
            (1, 2, 1, 2)");
    }

    public function testConsultarGeneralConCalculoAtletasYDatosTorneoEquipo(): void
    {
        $resultado = $this->modelo->Consultar([]);

        $this->assertIsArray($resultado);
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        // Encontrar participación 1
        $part1 = null;
        foreach ($resultado['datos'] as $p) {
            if ($p['codigo_participacion'] == 1) {
                $part1 = $p;
                break;
            }
        }

        $this->assertNotNull($part1);
        $this->assertEquals('COPA ORO 2026', $part1['torneo_nombre']);
        $this->assertEquals('Cannibals Alfa', $part1['equipo_nombre']);
        $this->assertEquals(2, $part1['cantidad_atletas']);
    }

    public function testConsultarConFiltroTextoTorneoYEquipo(): void
    {
        $resultado = $this->modelo->Consultar(['filtro' => 'Alfa']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Cannibals Alfa', $resultado['datos'][0]['equipo_nombre']);
    }

    public function testConsultarConFiltroPalabrasClaveEstatus(): void
    {
        // Buscar por texto de estatus "por disputarse"
        $resultado = $this->modelo->Consultar(['filtro' => 'por disputarse']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('COPA ORO 2026', $resultado['datos'][0]['torneo_nombre']);
    }

    public function testConsultarConFiltroPorTorneoEspecifico(): void
    {
        $resultado = $this->modelo->Consultar(['codigo_torneo' => 2]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(2, $resultado['datos'][0]['codigo_torneo']);
        $this->assertEquals('LIGA DE VERANO PASADA', $resultado['datos'][0]['torneo_nombre']);
    }

    public function testConsultarConFiltroPorEquipoEspecifico(): void
    {
        $resultado = $this->modelo->Consultar(['codigo_equipo' => 1]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['codigo_equipo']);
    }

    public function testBuscarParticipacionPorId(): void
    {
        $resultado = $this->modelo->ProcesarDatos(['accion' => 'buscar', 'codigo_participacion' => 1]);

        $this->assertIsArray($resultado);
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['codigo_participacion']);
        $this->assertEquals(1, $resultado['datos'][0]['codigo_torneo']);
        $this->assertEquals(1, $resultado['datos'][0]['codigo_equipo']);
    }

    public function testBuscarParticipacionNoEncontrada(): void
    {
        $resultado = $this->modelo->ProcesarDatos(['accion' => 'buscar', 'codigo_participacion' => 9999]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Registro no encontrado', $resultado['mensaje']);
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
        $this->expectExceptionMessage('La acción solicitada para participaciones no es válida.');

        $this->modelo->ProcesarDatos(['accion' => 'accion_no_valida']);
    }
}
