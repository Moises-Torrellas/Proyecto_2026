<?php

namespace tests\Unit\Torneos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloTorneos;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use PDO;
use PDOStatement;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitTorneos')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosTorneosTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    public function testDatosVaciosLanzaExcepcion(): void
    {
        $modelo = new ModeloTorneos();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo->ProcesarDatos([]);
    }

    public function testAccionInvalidaLanzaExcepcion(): void
    {
        $modelo = new ModeloTorneos();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción no es válida');

        $modelo->ProcesarDatos(['accion' => 'accion_no_valida', 'nombre' => 'Copa Cannibals']);
    }

    public function testValidarCamposInvalidosLanzaExcepcion(): void
    {
        $modelo = new ModeloTorneos();
        $casos = [
            'codigo torneo invalido' => [['accion' => 'buscar', 'codigo_torneo' => '10abc', 'nombre' => 'Copa Cannibals'], 'Código de torneo inválido.'],
            'nombre menor a 2 car'   => [['accion' => 'incluir', 'nombre' => 'A'], 'Nombre de torneo inválido.'],
            'fecha inicio invalida'  => [['accion' => 'incluir', 'nombre' => 'Copa Cannibals', 'fecha_inicio' => '12/05/2026'], 'Fecha de inicio inválida.'],
            'fecha fin invalida'     => [['accion' => 'incluir', 'nombre' => 'Copa Cannibals', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '30-10-2026'], 'Fecha de fin inválida.'],
            'ubicacion corta'        => [['accion' => 'incluir', 'nombre' => 'Copa Cannibals', 'ubicacion' => 'Gym'], 'Ubicación inválida.'],
            'estatus fuera de rango' => [['accion' => 'incluir', 'nombre' => 'Copa Cannibals', 'estatus' => '5'], 'Estatus inválido.']
        ];

        foreach ($casos as $nombre => [$datos, $mensajeEsperado]) {
            try {
                $modelo->ProcesarDatos($datos);
                $this->fail("Se esperaba excepción en caso [$nombre] y no se lanzó.");
            } catch (Exception $e) {
                $this->assertEquals($mensajeEsperado, $e->getMessage(), "Mensaje incorrecto en [$nombre]");
            }
        }
    }

    public function testFechaInicioMayorQueFechaFinLanzaExcepcion(): void
    {
        $modelo = new ModeloTorneos();
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Copa Cannibals',
            'fecha_inicio' => '2026-12-10',
            'fecha_fin'    => '2026-12-05'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La fecha de inicio no puede ser mayor que la fecha de fin.');

        $modelo->ProcesarDatos($datos);
    }

    public function testEstatusPorDisputarseConFechaNoFuturaLanzaExcepcion(): void
    {
        $modelo = new ModeloTorneos();
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Copa Futura',
            'fecha_inicio' => date('Y-m-d', strtotime('-1 day')),
            'fecha_fin'    => date('Y-m-d', strtotime('+5 days')),
            'ubicacion'    => 'Pista de Hielo Barquisimeto',
            'estatus'      => '1'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Un torneo "Por Disputarse" debe tener un rango de fecha futura.');

        $modelo->ProcesarDatos($datos);
    }

    public function testEstatusEnCursoFueraDeRangoLanzaExcepcion(): void
    {
        $modelo = new ModeloTorneos();
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Copa En Curso',
            'fecha_inicio' => date('Y-m-d', strtotime('+3 days')),
            'fecha_fin'    => date('Y-m-d', strtotime('+10 days')),
            'ubicacion'    => 'Pista de Hielo Barquisimeto',
            'estatus'      => '2'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Un torneo "En Curso" debe tener un rango de fecha que esté transcurriendo.');

        $modelo->ProcesarDatos($datos);
    }

    public function testEstatusFinalizadoConFechaNoPasadaLanzaExcepcion(): void
    {
        $modelo = new ModeloTorneos();
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Copa Finalizada',
            'fecha_inicio' => date('Y-m-d', strtotime('-10 days')),
            'fecha_fin'    => date('Y-m-d', strtotime('+2 days')),
            'ubicacion'    => 'Pista de Hielo Barquisimeto',
            'estatus'      => '3'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Un torneo "Finalizado" debe tener un rango de fecha que ya pasó.');

        $modelo->ProcesarDatos($datos);
    }

    public function testNormalizacionNombreYUbicacion(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $reflectorConex = new ReflectionClass(Conexion::class);
        $propiedad = $reflectorConex->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        $modelo = new ModeloTorneos();
        $modelo->ProcesarDatos([
            'accion'        => 'buscar',
            'codigo_torneo' => '1',
            'nombre'        => '   torneo nacional clausura   ',
            'ubicacion'     => '   complejo polideportivo lara   ',
            'fecha_inicio'  => date('Y-m-d', strtotime('+5 days')),
            'fecha_fin'     => date('Y-m-d', strtotime('+10 days')),
            'estatus'       => '1'
        ]);

        $reflector = new ReflectionClass(ModeloTorneos::class);
        $this->assertEquals('TORNEO NACIONAL CLAUSURA', $reflector->getProperty('nombre')->getValue($modelo));
        $this->assertEquals('Complejo Polideportivo Lara', $reflector->getProperty('ubicacion')->getValue($modelo));
    }
}
