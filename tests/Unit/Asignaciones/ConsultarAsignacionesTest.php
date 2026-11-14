<?php

namespace tests\Unit\Asignaciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloAsignaciones;
use App\modelo\Conexion;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitAsignaciones')]
#[Group('RF-05')]
#[Group('ESC-05')]
class ConsultarAsignacionesTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloAsignaciones
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloAsignaciones();
    }

    // Caso 28: Consulta general con agrupación por atleta
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-28')]
    public function testConsultaGeneralAgrupadaPorAtleta(): void
    {
        // Arrange
        $filasVista = [
            [
                'id_asignacion'      => 1,
                'fecha_vista'        => '10/05/2026',
                'fecha_real'         => '2026-05-10',
                'estatus_asignacion' => 1,
                'codigo_atleta'      => 1,
                'atleta'             => 'Diego Reinoso',
                'doc_identidad'      => 'V-12345678',
                'articulo'           => 'Casco',
                'codigo_club'        => 'CL-CASCO-01',
                'codigo_articulo'    => 10
            ],
            [
                'id_asignacion'      => 2,
                'fecha_vista'        => '10/05/2026',
                'fecha_real'         => '2026-05-10',
                'estatus_asignacion' => 1,
                'codigo_atleta'      => 1,
                'atleta'             => 'Diego Reinoso',
                'doc_identidad'      => 'V-12345678',
                'articulo'           => 'Pads',
                'codigo_club'        => 'CL-PADS-01',
                'codigo_articulo'    => 11
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filasVista);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion' => 'consultar'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos'], 'Debe agrupar ambos artículos en un solo atleta.');
        $this->assertEquals('Diego Reinoso', $resultado['datos'][0]['nombre_completo']);
        $this->assertCount(2, $resultado['datos'][0]['asignaciones']);
    }

    // Caso 29: Consulta con filtro de texto
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-29')]
    public function testConsultaConFiltroTexto(): void
    {
        // Arrange
        $sqlEjecutado = '';
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$sqlEjecutado, $stmtMock) {
            $sqlEjecutado = $sql;
            return $stmtMock;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion' => 'consultar',
            'filtro' => 'Casco'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertStringContainsString('atleta LIKE :filtro1', $sqlEjecutado);
    }

    // Caso 30: Consulta con filtro por rango de fechas
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-30')]
    public function testConsultaConRangoFechas(): void
    {
        // Arrange
        $sqlEjecutado = '';
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$sqlEjecutado, $stmtMock) {
            $sqlEjecutado = $sql;
            return $stmtMock;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'           => 'consultar',
            'fecha_asignacion' => '2026-01-01',
            'fecha_f'          => '2026-12-31'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertStringContainsString('BETWEEN :fecha_inicio AND :fecha_fin', $sqlEjecutado);
    }

    // Caso 31: Búsqueda directa por ID individual
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-31')]
    public function testBuscarPorIdIndividual(): void
    {
        // Arrange
        $registroAsignacion = [
            [
                'id_asignacion'    => 5,
                'codigo_atleta'    => 1,
                'codigo_articulo'  => 10,
                'fecha_asignacion' => '2026-05-10',
                'estatus'          => 1
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($registroAsignacion);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Buscar(5);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(5, $resultado['datos'][0]['id_asignacion']);
    }

    // Caso 32: Consulta de asignaciones para módulo de devoluciones
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-32')]
    public function testConsultarAsignacionesDevolucion(): void
    {
        // Arrange
        $registrosDevolucion = [
            [
                'id_asignacion'      => 1,
                'estatus_asignacion' => 1,
                'atleta'             => 'Diego Reinoso',
                'articulo'           => 'Casco'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($registrosDevolucion);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ConsultarAsignacionesDevolucion();

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Diego Reinoso', $resultado['datos'][0]['atleta']);
        $this->assertEquals('Casco', $resultado['datos'][0]['articulo']);
    }
}

