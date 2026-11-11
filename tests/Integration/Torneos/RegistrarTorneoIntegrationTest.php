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
class RegistrarTorneoIntegrationTest extends TestCase
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
        $this->pdo->exec("TRUNCATE TABLE equipos;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    public function testRegistrarTorneoPorDisputarseExitoso(): void
    {
        $fechaInicio = date('Y-m-d', strtotime('+10 days'));
        $fechaFin    = date('Y-m-d', strtotime('+20 days'));

        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'torneo apertura 2026',
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'ubicacion'    => 'complejo deportivo central',
            'estatus'      => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);

        // Verificar persistencia en base de datos
        $stmt = $this->pdo->prepare("SELECT * FROM torneos WHERE nombre = :nombre");
        $stmt->execute([':nombre' => 'TORNEO APERTURA 2026']);
        $torneoDB = $stmt->fetch();

        $this->assertNotEmpty($torneoDB, 'El torneo debe persistirse en la tabla torneos.');
        $this->assertEquals('TORNEO APERTURA 2026', $torneoDB['nombre'], 'El nombre debe almacenarse en mayúsculas.');
        $this->assertEquals('Complejo Deportivo Central', $torneoDB['ubicacion'], 'La ubicación debe almacenarse en Title Case.');
        $this->assertEquals($fechaInicio, $torneoDB['fecha_inicio']);
        $this->assertEquals($fechaFin, $torneoDB['fecha_fin']);
        $this->assertEquals(1, $torneoDB['estatus']);
    }

    public function testRegistrarTorneoEnCursoExitoso(): void
    {
        $fechaInicio = date('Y-m-d', strtotime('-2 days'));
        $fechaFin    = date('Y-m-d', strtotime('+3 days'));

        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Copa Cannibals Live',
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'ubicacion'    => 'Cancha Principal Cannibals',
            'estatus'      => 2
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->prepare("SELECT * FROM torneos WHERE nombre = :nombre");
        $stmt->execute([':nombre' => 'COPA CANNIBALS LIVE']);
        $torneoDB = $stmt->fetch();

        $this->assertNotEmpty($torneoDB);
        $this->assertEquals(2, $torneoDB['estatus']);
    }

    public function testRegistrarTorneoFinalizadoExitoso(): void
    {
        $fechaInicio = date('Y-m-d', strtotime('-15 days'));
        $fechaFin    = date('Y-m-d', strtotime('-3 days'));

        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Torneo Clausura Pasado',
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'ubicacion'    => 'Estadio Bicentenario',
            'estatus'      => 3
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->prepare("SELECT * FROM torneos WHERE nombre = :nombre");
        $stmt->execute([':nombre' => 'TORNEO CLAUSURA PASADO']);
        $torneoDB = $stmt->fetch();

        $this->assertNotEmpty($torneoDB);
        $this->assertEquals(3, $torneoDB['estatus']);
    }

    public function testRegistrarTorneoRechazaNombreDuplicado(): void
    {
        // Sembrar torneo inicial
        $fechaInicio = date('Y-m-d', strtotime('+10 days'));
        $fechaFin    = date('Y-m-d', strtotime('+20 days'));

        $this->pdo->exec("INSERT INTO torneos (nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            ('TORNEO COPA DE ORO', '{$fechaInicio}', '{$fechaFin}', 'Estadio Olímpico', 1)");

        $datosDuplicado = [
            'accion'       => 'incluir',
            'nombre'       => 'torneo copa de oro',
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'ubicacion'    => 'Otra Sede Deportiva',
            'estatus'      => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datosDuplicado);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe un torneo registrado con este nombre.', $resultado['codigo']);

        $stmtCount = $this->pdo->query("SELECT COUNT(*) FROM torneos WHERE nombre = 'TORNEO COPA DE ORO'");
        $this->assertEquals(1, $stmtCount->fetchColumn(), 'No deben existir registros duplicados en BD.');
    }

    public function testRegistrarTorneoRechazaFechaInicioMayorQueFechaFin(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La fecha de inicio no puede ser mayor que la fecha de fin.');

        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Torneo Fecha Invalida',
            'fecha_inicio' => '2026-12-25',
            'fecha_fin'    => '2026-12-10',
            'ubicacion'    => 'Cancha Techada',
            'estatus'      => 1
        ];

        $this->modelo->ProcesarDatos($datos);
    }

    public function testRegistrarTorneoRechazaInconsistenciaFechaEstatusPorDisputarse(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Un torneo "Por Disputarse" debe tener un rango de fecha futura.');

        // Estatus 1 pero fecha_inicio es hoy o en el pasado
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Torneo Fecha Pasada',
            'fecha_inicio' => date('Y-m-d', strtotime('-5 days')),
            'fecha_fin'    => date('Y-m-d', strtotime('+5 days')),
            'ubicacion'    => 'Polideportivo Norte',
            'estatus'      => 1
        ];

        $this->modelo->ProcesarDatos($datos);
    }

    public function testRegistrarTorneoRechazaInconsistenciaFechaEstatusEnCurso(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Un torneo "En Curso" debe tener un rango de fecha que esté transcurriendo.');

        // Estatus 2 pero fechas totalmente futuras
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Torneo Acelerado',
            'fecha_inicio' => date('Y-m-d', strtotime('+10 days')),
            'fecha_fin'    => date('Y-m-d', strtotime('+20 days')),
            'ubicacion'    => 'Polideportivo Sur',
            'estatus'      => 2
        ];

        $this->modelo->ProcesarDatos($datos);
    }

    public function testRegistrarTorneoRechazaInconsistenciaFechaEstatusFinalizado(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Un torneo "Finalizado" debe tener un rango de fecha que ya pasó.');

        // Estatus 3 pero fecha_fin futura
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Torneo No Finalizado',
            'fecha_inicio' => date('Y-m-d', strtotime('-5 days')),
            'fecha_fin'    => date('Y-m-d', strtotime('+5 days')),
            'ubicacion'    => 'Gimnasio Cubierto',
            'estatus'      => 3
        ];

        $this->modelo->ProcesarDatos($datos);
    }
}

