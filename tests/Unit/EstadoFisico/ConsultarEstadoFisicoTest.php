<?php

namespace tests\Unit\EstadoFisico;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadoFisico;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitEstadoFisico')]
#[Group('RF-05')]
#[Group('ESC-05')]
class ConsultarEstadoFisicoTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloEstadoFisico
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloEstadoFisico();
    }

    // Caso 16: Consulta general sin filtros exitosa
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-16')]
    public function testConsultarSinFiltrosExitoso(): void
    {
        // Arrange
        $estadosSimulados = [
            ['id_estado' => '1', 'nombre' => 'Excelente', 'nivel_estado' => '1'],
            ['id_estado' => '2', 'nombre' => 'Regular', 'nivel_estado' => '2']
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($estadosSimulados);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('Excelente', $resultado['datos'][0]['nombre']);
    }

    // Caso 17: Consulta con filtro de texto
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-17')]
    public function testConsultarConFiltroTexto(): void
    {
        // Arrange
        $sqlCapturado = '';
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$sqlCapturado, $stmtMock) {
            $sqlCapturado = $sql;
            return $stmtMock;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar(['filtro' => 'Bueno']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertStringContainsString('AND (nombre LIKE :f1 OR nivel_estado LIKE :f2)', $sqlCapturado);
    }

    // Caso 18: Consulta con filtro por nivel de estado
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-18')]
    public function testConsultarConFiltroNivelEstado(): void
    {
        // Arrange
        $sqlCapturado = '';
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$sqlCapturado, $stmtMock) {
            $sqlCapturado = $sql;
            return $stmtMock;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar(['nivel_estado' => '2']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertStringContainsString('AND nivel_estado = :filtro_nivel', $sqlCapturado);
    }

    // Caso 19: Búsqueda individual pasando el ID por parámetro
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-19')]
    public function testBuscarPorIdIndividualExitoso(): void
    {
        // Arrange
        $estadoSimulado = [
            ['id_estado' => '1', 'nombre' => 'Excelente', 'nivel_estado' => '1']
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($estadoSimulado);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Buscar(1);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Excelente', $resultado['datos'][0]['nombre']);
    }

    // Caso 20: Búsqueda individual a través del atributo interno del modelo (ProcesarDatos)
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-20')]
    public function testBuscarPorAtributoModelo(): void
    {
        // Arrange
        $estadoSimulado = [
            ['id_estado' => '2', 'nombre' => 'Regular', 'nivel_estado' => '2']
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($estadoSimulado);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos(['accion' => 'buscar', 'id_estado' => '2']);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEquals('Regular', $resultado['datos'][0]['nombre']);
    }

    // Caso 21: Manejo de error o excepción en la consulta
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-21')]
    public function testConsultarManejoDeExcepcion(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de base de datos simulado'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error al listar:', $resultado['mensaje']);
    }
}

