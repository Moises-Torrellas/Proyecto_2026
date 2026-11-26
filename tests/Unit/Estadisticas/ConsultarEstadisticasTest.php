<?php

declare(strict_types=1);

namespace tests\Unit\Estadisticas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadisticas;
use App\modelo\Conexion;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    $configPath = dirname(__DIR__, 3) . '/config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
}

if (!defined('DUPLICATE')) {
    define('DUPLICATE', '010');
}
if (!defined('INVALID_ID')) {
    define('INVALID_ID', '003');
}
if (!defined('ASSOCIATES')) {
    define('ASSOCIATES', '006');
}
if (!defined('VALIDATION')) {
    define('VALIDATION', '007');
}
if (!defined('EMPTY_SELECTION')) {
    define('EMPTY_SELECTION', '009');
}
if (!defined('DB_CONNECTION')) {
    define('DB_CONNECTION', '500');
}

#[Group('unit')]
#[Group('unitEstadisticas')]
#[Group('RF-05')]
#[Group('ESC-05')]
class ConsultarEstadisticasTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);
    }

    private function obtenerFilaEstadistica(array $sobrescribir = []): array
    {
        $base = [
            'id_estadisticas'       => 1,
            'goles'                 => 5,
            'asistencias'           => 3,
            'penalizaciones'        => 0,
            'partidos_jugados'      => 4,
            'average'               => 1.25,
            'goles_contra'          => 0,
            'id_atleta'             => 10,
            'nombres'               => 'Carlos',
            'apellidos'             => 'Pérez',
            'total_torneos_jugados' => 2,
            'torneo_nombre'         => 'Copa Apertura 2026',
            'fecha_inicio'          => '2026-03-01'
        ];

        return array_merge($base, $sobrescribir);
    }

    // ==========================================
    // ESCENARIO 05: Consulta General y Filtrado de Estadísticas
    // ==========================================

    // Caso 23: Consulta general de estadísticas activas sin filtros
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-23')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarListadoGeneralEstadisticas_cuando_seConsultaSinFiltros(): void
    {
        // Arrange
        $filas = [
            $this->obtenerFilaEstadistica(),
            $this->obtenerFilaEstadistica([
                'id_estadisticas' => 2,
                'id_atleta'       => 11,
                'nombres'         => 'Luis',
                'apellidos'       => 'González',
                'goles'           => 8
            ])
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([])
            ->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')
            ->with($this->stringContains('WHERE dp.estatus_estadistica = 1'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->Consultar([]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['id_estadisticas']);
        $this->assertEquals('Carlos', $resultado['datos'][0]['nombres']);
    }

    // Caso 24: Consulta con filtro dinámico por coincidencia textual en nombre o torneo
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-24')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarEstadisticasFiltradas_cuando_seAplicaFiltroTexto(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                ':f1' => '%Carlos%',
                ':f2' => '%Carlos%',
                ':f3' => '%Carlos%'
            ])
            ->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([$this->obtenerFilaEstadistica()]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')
            ->with($this->stringContains('a.p_nombre LIKE :f1'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->Consultar(['filtro' => 'Carlos']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 25: Consulta con filtro específico por participación
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-25')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarEstadisticasFiltradas_cuando_seAplicaFiltroParticipacion(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([':participacion' => 7])
            ->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([$this->obtenerFilaEstadistica()]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')
            ->with($this->stringContains('AND dp.codigo_participacion = :participacion'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->Consultar(['participacion' => 7]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 26: Consulta con filtro específico por atleta
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-26')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarEstadisticasFiltradas_cuando_seAplicaFiltroAtleta(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([':atleta' => 10])
            ->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([$this->obtenerFilaEstadistica()]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')
            ->with($this->stringContains('AND dp.codigo_atleta = :atleta'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->Consultar(['atleta' => 10]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 27: Consulta combinando filtros de texto, participación y atleta
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-27')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarEstadisticasFiltradas_cuando_seCombinanMultiplesFiltros(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                ':f1'            => '%Apertura%',
                ':f2'            => '%Apertura%',
                ':f3'            => '%Apertura%',
                ':participacion' => 7,
                ':atleta'        => 10
            ])
            ->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([$this->obtenerFilaEstadistica()]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->Consultar([
            'filtro'        => 'Apertura',
            'participacion' => 7,
            'atleta'        => 10
        ]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 28: Error de base de datos durante la consulta retornando estado de error
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-28')]
    #[Group('escenario_error')]
    public function test_deberia_retornarEstructuraError_cuando_fallaSentenciaSQLDeConsulta(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error de sintaxis o conexión'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->Consultar([]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
    }
}
