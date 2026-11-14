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

    // Caso 1: Arreglo de datos vacío lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    public function testDatosVaciosLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloTorneos();
        $datos = [];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 2: Acción desconocida o inválida lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    public function testAccionInvalidaLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloTorneos();
        $datos = [
            'accion' => 'accion_no_valida',
            'nombre' => 'Copa Cannibals'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción no es válida');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 3: Código de torneo inválido con caracteres alfanuméricos lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    public function testCodigoTorneoInvalidoLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloTorneos();
        $datos = [
            'accion'        => 'buscar',
            'codigo_torneo' => '10abc',
            'nombre'        => 'Copa Cannibals'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Código de torneo inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 4: Nombre no cumple con la expresión regular (menor a 2 caracteres)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-04')]
    public function testNombreInvalidoLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloTorneos();
        $datos = [
            'accion' => 'incluir',
            'nombre' => 'A'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre de torneo inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 5: Fecha de inicio con formato inválido lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-05')]
    public function testFechaInicioInvalidaLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloTorneos();
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Copa Cannibals',
            'fecha_inicio' => '12/05/2026'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Fecha de inicio inválida.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 6: Fecha de fin con formato inválido lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-06')]
    public function testFechaFinInvalidaLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloTorneos();
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Copa Cannibals',
            'fecha_inicio' => '2026-10-01',
            'fecha_fin'    => '30-10-2026'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Fecha de fin inválida.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 7: Ubicación con longitud menor a 5 caracteres lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-07')]
    public function testUbicacionInvalidaLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloTorneos();
        $datos = [
            'accion'    => 'incluir',
            'nombre'    => 'Copa Cannibals',
            'ubicacion' => 'Gym'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Ubicación inválida.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 8: Estatus con valor fuera del rango 1-3 lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-08')]
    public function testEstatusInvalidoLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloTorneos();
        $datos = [
            'accion'  => 'incluir',
            'nombre'  => 'Copa Cannibals',
            'estatus' => '5'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Estatus inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 9: Fecha de inicio cronológicamente mayor que la fecha de fin lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-09')]
    public function testFechaInicioMayorQueFechaFinLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloTorneos();
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Copa Cannibals',
            'fecha_inicio' => '2026-12-10',
            'fecha_fin'    => '2026-12-05'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La fecha de inicio no puede ser mayor que la fecha de fin.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 10: Torneo 'Por Disputarse' (estatus = 1) con fecha no futura lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-10')]
    public function testEstatusPorDisputarseConFechaNoFuturaLanzaExcepcion(): void
    {
        // Arrange
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

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 11: Torneo 'En Curso' (estatus = 2) fuera de fecha actual lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-11')]
    public function testEstatusEnCursoFueraDeRangoLanzaExcepcion(): void
    {
        // Arrange
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

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 12: Torneo 'Finalizado' (estatus = 3) con fecha de fin no pasada lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-12')]
    public function testEstatusFinalizadoConFechaNoPasadaLanzaExcepcion(): void
    {
        // Arrange
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

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 13: Normalización de nombre a Uppercase y ubicación a Title Case
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-13')]
    public function testNormalizacionNombreYUbicacion(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $reflectorConex = new ReflectionClass(Conexion::class);
        $propiedad = $reflectorConex->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        $modelo = new ModeloTorneos();
        $datos = [
            'accion'        => 'buscar',
            'codigo_torneo' => '1',
            'nombre'        => '   torneo nacional clausura   ',
            'ubicacion'     => '   complejo polideportivo lara   ',
            'fecha_inicio'  => date('Y-m-d', strtotime('+5 days')),
            'fecha_fin'     => date('Y-m-d', strtotime('+10 days')),
            'estatus'       => '1'
        ];

        // Act
        $modelo->ProcesarDatos($datos);

        // Assert
        $reflector = new ReflectionClass(ModeloTorneos::class);
        $propNombre = $reflector->getProperty('nombre');
        $propUbicacion = $reflector->getProperty('ubicacion');

        $this->assertEquals('TORNEO NACIONAL CLAUSURA', $propNombre->getValue($modelo));
        $this->assertEquals('Complejo Polideportivo Lara', $propUbicacion->getValue($modelo));
    }
}

