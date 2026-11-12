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
class ModificarTorneoIntegrationTest extends TestCase
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
        $fInicio1 = date('Y-m-d', strtotime('+5 days'));
        $fFin1    = date('Y-m-d', strtotime('+15 days'));

        $fInicio2 = date('Y-m-d', strtotime('+20 days'));
        $fFin2    = date('Y-m-d', strtotime('+30 days'));

        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (1, 'COPA APERTURA', '{$fInicio1}', '{$fFin1}', 'Cancha 1', 1),
            (2, 'COPA CLAUSURA', '{$fInicio2}', '{$fFin2}', 'Cancha 2', 1)");
    }

    public function testModificarTorneoExitoso(): void
    {
        $nuevaInicio = date('Y-m-d', strtotime('+8 days'));
        $nuevaFin    = date('Y-m-d', strtotime('+18 days'));

        $datos = [
            'accion'        => 'modificar',
            'codigo_torneo' => 1,
            'nombre'        => 'copa apertura modificada',
            'fecha_inicio'  => $nuevaInicio,
            'fecha_fin'     => $nuevaFin,
            'ubicacion'     => 'polideportivo central renovado',
            'estatus'       => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);

        // Verificar actualización en la base de datos
        $stmt = $this->pdo->query("SELECT * FROM torneos WHERE codigo_torneo = 1");
        $torneoDB = $stmt->fetch();

        $this->assertEquals('COPA APERTURA MODIFICADA', $torneoDB['nombre']);
        $this->assertEquals('Polideportivo Central Renovado', $torneoDB['ubicacion']);
        $this->assertEquals($nuevaInicio, $torneoDB['fecha_inicio']);
        $this->assertEquals($nuevaFin, $torneoDB['fecha_fin']);
    }

    public function testModificarTorneoConservandoPropioNombre(): void
    {
        $fInicio = date('Y-m-d', strtotime('+6 days'));
        $fFin    = date('Y-m-d', strtotime('+16 days'));

        $datos = [
            'accion'        => 'modificar',
            'codigo_torneo' => 1,
            'nombre'        => 'COPA APERTURA',
            'fecha_inicio'  => $fInicio,
            'fecha_fin'     => $fFin,
            'ubicacion'     => 'Estadio Metropolitano',
            'estatus'       => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT ubicacion FROM torneos WHERE codigo_torneo = 1");
        $this->assertEquals('Estadio Metropolitano', $stmt->fetchColumn());
    }

    public function testModificarTorneoRechazaNombreDuplicadoDeOtroTorneo(): void
    {
        $fInicio = date('Y-m-d', strtotime('+6 days'));
        $fFin    = date('Y-m-d', strtotime('+16 days'));

        // Intentar modificar el torneo 1 colocándole el nombre del torneo 2 ("COPA CLAUSURA")
        $datos = [
            'accion'        => 'modificar',
            'codigo_torneo' => 1,
            'nombre'        => 'copa clausura',
            'fecha_inicio'  => $fInicio,
            'fecha_fin'     => $fFin,
            'ubicacion'     => 'Cancha 1',
            'estatus'       => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe otro torneo registrado con este nombre.', $resultado['codigo']);

        // Verificar que el torneo 1 no cambió su nombre
        $stmt = $this->pdo->query("SELECT nombre FROM torneos WHERE codigo_torneo = 1");
        $this->assertEquals('COPA APERTURA', $stmt->fetchColumn());
    }

    public function testModificarTorneoRechazaInconsistenciaFechas(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La fecha de inicio no puede ser mayor que la fecha de fin.');

        $datos = [
            'accion'        => 'modificar',
            'codigo_torneo' => 1,
            'nombre'        => 'COPA APERTURA',
            'fecha_inicio'  => '2026-11-20',
            'fecha_fin'     => '2026-11-10',
            'ubicacion'     => 'Cancha 1',
            'estatus'       => 1
        ];

        $this->modelo->ProcesarDatos($datos);
    }

    public function testModificarTorneoRechazaCodigoTorneoInvalido(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Código de torneo inválido.');

        $datos = [
            'accion'        => 'modificar',
            'codigo_torneo' => 'codigo_invalido',
            'nombre'        => 'COPA APERTURA',
            'fecha_inicio'  => date('Y-m-d', strtotime('+5 days')),
            'fecha_fin'     => date('Y-m-d', strtotime('+15 days')),
            'ubicacion'     => 'Cancha 1',
            'estatus'       => 1
        ];

        $this->modelo->ProcesarDatos($datos);
    }
}

