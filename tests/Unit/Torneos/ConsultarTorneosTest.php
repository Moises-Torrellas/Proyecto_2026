<?php

namespace tests\Unit\Torneos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloTorneos;
use App\modelo\Conexion;
use PDO;
use PDOException;
use PDOStatement;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitTorneos')]
#[Group('RF-05')]
#[Group('ESC-05')]
class ConsultarTorneosTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloTorneos
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloTorneos();
    }

    // Caso 25: Consulta general de torneos sin filtros
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-25')]
    public function testConsultarTorneosSinFiltros(): void
    {
        // Arrange
        $datosEsperados = [
            ['codigo_torneo' => 1, 'nombre' => 'COPA ORO', 'estatus' => 1],
            ['codigo_torneo' => 2, 'nombre' => 'TORNEO CLAUSURA', 'estatus' => 2]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([]);
        $stmt->method('fetchAll')->willReturn($datosEsperados);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('SELECT * FROM torneos WHERE 1=1', $sql);
            $this->assertStringContainsString('ORDER BY codigo_torneo ASC', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar([]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
    }

    // Caso 26: Consulta con filtro dinámico por texto de búsqueda
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-26')]
    public function testConsultarTorneosConFiltroTexto(): void
    {
        // Arrange
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with($this->callback(function ($params) {
            return isset($params[':f1']) && $params[':f1'] === '%Nacional%';
        }));
        $stmt->method('fetchAll')->willReturn([
            ['codigo_torneo' => 3, 'nombre' => 'TORNEO NACIONAL', 'estatus' => 1]
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('AND nombre LIKE :f1', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar(['filtro' => 'Nacional']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 27: Consulta con filtros específicos de fecha de inicio, fecha de fin y estatus
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-27')]
    public function testConsultarTorneosConFiltrosEstatusYFechas(): void
    {
        // Arrange
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with($this->callback(function ($params) {
            return isset($params[':estatus']) && $params[':estatus'] === '1'
                && isset($params[':fecha_inicio']) && $params[':fecha_inicio'] === '2026-06-01'
                && isset($params[':fecha_fin']) && $params[':fecha_fin'] === '2026-06-30';
        }));
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('AND estatus = :estatus', $sql);
            $this->assertStringContainsString('AND fecha_inicio >= :fecha_inicio', $sql);
            $this->assertStringContainsString('AND fecha_fin <= :fecha_fin', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar([
            'estatus'      => '1',
            'fecha_inicio' => '2026-06-01',
            'fecha_fin'    => '2026-06-30'
        ]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
    }

    // Caso 28: Consulta con atributo de nombre configurado mediante ProcesarDatos
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-28')]
    public function testConsultarTorneosConFiltroNombrePropio(): void
    {
        // Arrange
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with($this->callback(function ($params) {
            return isset($params[':nombre']) && $params[':nombre'] === 'COPA%';
        }));
        $stmt->method('fetchAll')->willReturn([
            ['codigo_torneo' => 1, 'nombre' => 'COPA ORO', 'estatus' => 1]
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('AND nombre LIKE :nombre', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'consultar',
            'nombre' => 'Copa'
        ]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 29: Captura de excepción en base de datos durante Consultar
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-29')]
    public function testConsultarTorneosFalloBaseDeDatos(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error de conexión simulado'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar([]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
    }

    // Caso 30: Búsqueda individual directa por ID con parámetro
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-30')]
    public function testBuscarTorneoPorIdDirecto(): void
    {
        // Arrange
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([
            ['codigo_torneo' => 5, 'nombre' => 'TORNEO APERTURA', 'estatus' => 1]
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('WHERE codigo_torneo = :codigo_torneo', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Buscar(5);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEquals(5, $resultado['datos'][0]['codigo_torneo']);
    }

    // Caso 31: Búsqueda individual enrutada mediante ProcesarDatos
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-31')]
    public function testBuscarTorneoEnrutadoPorProcesarDatos(): void
    {
        // Arrange
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([
            ['codigo_torneo' => 8, 'nombre' => 'COPA REGIONAL', 'estatus' => 2]
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'buscar',
            'codigo_torneo' => 8
        ]);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEquals(8, $resultado['datos'][0]['codigo_torneo']);
    }

    // Caso 32: Generación de reporte enrutada mediante ProcesarDatos
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-32')]
    public function testGenerarReporteEnrutadoPorProcesarDatos(): void
    {
        // Arrange
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([
            ['codigo_torneo' => 1, 'nombre' => 'TORNEO 1'],
            ['codigo_torneo' => 2, 'nombre' => 'TORNEO 2']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'generar'
        ]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
    }

    // Caso 33: Captura de excepción en base de datos durante Buscar
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-33')]
    public function testBuscarTorneoFalloBaseDeDatos(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Fallo al buscar torneo'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Buscar(1);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Fallo al buscar torneo', $resultado['mensaje']);
    }

    // Caso 34: Consulta de torneos próximos a disputarse exitosa
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-34')]
    public function testConsultarProximosTorneosExitoso(): void
    {
        // Arrange
        $datosProximos = [
            ['nombre' => 'COPA INICIAL', 'fecha_inicio' => date('Y-m-d', strtotime('+1 day'))],
            ['nombre' => 'COPA SEGUNDA', 'fecha_inicio' => date('Y-m-d', strtotime('+2 days'))]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($datosProximos);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('WHERE estatus = 1', $sql);
            $this->assertStringContainsString('INTERVAL 1 DAY', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ConsultarProximos();

        // Assert
        $this->assertCount(2, $resultado);
        $this->assertEquals('COPA INICIAL', $resultado[0]['nombre']);
    }

    // Caso 35: Captura de excepción en base de datos durante ConsultarProximos
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-35')]
    public function testConsultarProximosTorneosFalloBaseDeDatos(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error de consulta en torneos próximos'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ConsultarProximos();

        // Assert
        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }
}

