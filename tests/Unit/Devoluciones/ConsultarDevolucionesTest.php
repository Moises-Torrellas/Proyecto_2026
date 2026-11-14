<?php

namespace tests\Unit\Devoluciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloDevoluciones;
use App\modelo\Conexion;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitDevoluciones')]
#[Group('RF-05')]
#[Group('ESC-05')]
class ConsultarDevolucionesTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloDevoluciones
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloDevoluciones();
    }

    // Caso 16: Consulta general agrupada por atleta
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-16')]
    public function testConsultaGeneralAgrupadaPorAtleta(): void
    {
        // Arrange
        $filasVista = [
            [
                'id_devolucion'             => 1,
                'fecha_vista'               => '15/05/2026',
                'fecha_devolucion'          => '2026-05-15',
                'id_asignacion'             => 10,
                'id_estado'                 => 1,
                'observacion'               => 'Devuelto sin daños',
                'estado_fisico'             => 'Excelente',
                'nivel_estado'              => 1,
                'codigo_atleta'             => 1,
                'atleta_nombre'             => 'Diego',
                'atleta_apellido'           => 'Reinoso',
                'doc_identidad'             => 'V-12345678',
                'articulo_nombre'           => 'Casco',
                'codigo_club'               => 'CL-CASCO-01',
                'total_devoluciones_atleta' => 2
            ],
            [
                'id_devolucion'             => 2,
                'fecha_vista'               => '15/05/2026',
                'fecha_devolucion'          => '2026-05-15',
                'id_asignacion'             => 11,
                'id_estado'                 => 2,
                'observacion'               => 'Uso regular',
                'estado_fisico'             => 'Regular',
                'nivel_estado'              => 2,
                'codigo_atleta'             => 1,
                'atleta_nombre'             => 'Diego',
                'atleta_apellido'           => 'Reinoso',
                'doc_identidad'             => 'V-12345678',
                'articulo_nombre'           => 'Pads',
                'codigo_club'               => 'CL-PADS-01',
                'total_devoluciones_atleta' => 2
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filasVista);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos(['accion' => 'consultar']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos'], 'Debe agrupar las devoluciones bajo el mismo atleta.');
        $this->assertEquals('Diego Reinoso', $resultado['datos'][0]['nombre_completo']);
        $this->assertEquals(2, $resultado['datos'][0]['total_devoluciones_atleta']);
        $this->assertCount(2, $resultado['datos'][0]['devoluciones']);
    }

    // Caso 17: Consulta con filtro de texto
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-17')]
    public function testConsultaConFiltroTexto(): void
    {
        // Arrange
        $sqlCapturado = '';
        $paramsCapturados = [];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturnCallback(function ($params) use (&$paramsCapturados) {
            $paramsCapturados = $params;
            return true;
        });
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$sqlCapturado, $stmtMock) {
            $sqlCapturado = $sql;
            return $stmtMock;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion' => 'consultar',
            'filtro' => 'Pads'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertStringContainsString('atleta_nombre LIKE ? OR atleta_apellido LIKE ?', $sqlCapturado);
        $this->assertContains('%Pads%', $paramsCapturados);
    }

    // Caso 18: Consulta con filtros específicos por atleta, asignación y estado
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-18')]
    public function testConsultaConFiltrosAtletaAsignacionEstado(): void
    {
        // Arrange
        $sqlCapturado = '';
        $paramsCapturados = [];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturnCallback(function ($params) use (&$paramsCapturados) {
            $paramsCapturados = $params;
            return true;
        });
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$sqlCapturado, $stmtMock) {
            $sqlCapturado = $sql;
            return $stmtMock;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $filtros = [
            'accion'        => 'consultar',
            'codigo_atleta' => '1',
            'id_asignacion' => '10',
            'id_estado'     => '2'
        ];

        // Act
        $resultado = $modelo->ConsultarDevoluciones($filtros);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertStringContainsString('AND codigo_atleta = ?', $sqlCapturado);
        $this->assertStringContainsString('AND id_asignacion = ?', $sqlCapturado);
        $this->assertStringContainsString('AND id_estado = ?', $sqlCapturado);
        $this->assertEquals(['1', '10', '2'], $paramsCapturados);
    }

    // Caso 19: Consulta con filtro de rango de fechas
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-19')]
    public function testConsultaConRangoFechas(): void
    {
        // Arrange
        $sqlCapturado = '';
        $paramsCapturados = [];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturnCallback(function ($params) use (&$paramsCapturados) {
            $paramsCapturados = $params;
            return true;
        });
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$sqlCapturado, $stmtMock) {
            $sqlCapturado = $sql;
            return $stmtMock;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $filtros = [
            'accion'      => 'consultar',
            'fecha_desde' => '2026-01-01',
            'fecha_hasta' => '2026-12-31'
        ];

        // Act
        $resultado = $modelo->ConsultarDevoluciones($filtros);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertStringContainsString('AND fecha_devolucion >= ?', $sqlCapturado);
        $this->assertStringContainsString('AND fecha_devolucion <= ?', $sqlCapturado);
        $this->assertEquals(['2026-01-01 00:00:00', '2026-12-31 23:59:59'], $paramsCapturados);
    }

    // Caso 20: Búsqueda directa por ID individual
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-20')]
    public function testBuscarPorIdIndividual(): void
    {
        // Arrange
        $registroDevolucion = [
            [
                'fecha_devolucion' => '2026-05-15',
                'observacion'      => 'Ninguna',
                'atleta'           => 'Diego Reinoso',
                'doc_identidad'    => 'V-12345678',
                'articulo'         => 'Pads',
                'estado_fisico'    => 'Excelente'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($registroDevolucion);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Buscar(1);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Diego Reinoso', $resultado['datos'][0]['atleta']);
        $this->assertEquals('Pads', $resultado['datos'][0]['articulo']);
    }

    // Caso 21: Manejo de error o excepción en la consulta
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-21')]
    public function testConsultaManejoDeExcepcion(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Fallo al consultar vista de devoluciones'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ConsultarDevoluciones();

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Fallo al consultar vista de devoluciones', $resultado['codigo']);
    }
}

