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
#[Group('RF-06')]
#[Group('ESC-06')]
class BuscarEstadisticasTest extends TestCase
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

    private function obtenerFilaEstadisticaBuscar(array $sobrescribir = []): array
    {
        $base = [
            'id_estadisticas'       => 15,
            'id_torneo'             => 2,
            'id_atleta'             => 7,
            'goles'                 => 6,
            'asistencias'           => 4,
            'penalizaciones'        => 1,
            'partidos_jugados'      => 5,
            'average'               => 1.20,
            'goles_contra'          => 0,
            'cedula'                => 'V-12345678',
            'nombres'               => 'Andrés',
            'apellidos'             => 'Giménez',
            'total_torneos_jugados' => 3,
            'torneo_nombre'         => 'Torneo Clausura 2026',
            'fecha_inicio'          => '2026-06-15'
        ];

        return array_merge($base, $sobrescribir);
    }

    // ==========================================
    // ESCENARIO 06: Búsqueda Individual de Estadísticas Deportivas
    // ==========================================

    // Caso 29: Búsqueda exitosa pasando el identificador por parámetro directo a Buscar(int $id)
    #[Group('RF-06')]
    #[Group('ESC-06')]
    #[Group('CP-29')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarRegistroEstadistica_cuando_seBuscaPorParametroDirecto(): void
    {
        // Arrange
        $filas = [$this->obtenerFilaEstadisticaBuscar()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('bindValue')
            ->with(':id', 15, PDO::PARAM_INT)
            ->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')
            ->with($this->stringContains('WHERE dp.codigo_dtll_prtc = :id'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->Buscar(15);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(15, $resultado['datos'][0]['id_estadisticas']);
        $this->assertEquals('Andrés', $resultado['datos'][0]['nombres']);
    }

    // Caso 30: Búsqueda exitosa invocada a través de ProcesarDatos
    #[Group('RF-06')]
    #[Group('ESC-06')]
    #[Group('CP-30')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarRegistroEstadistica_cuando_seBuscaMedianteProcesarDatos(): void
    {
        // Arrange
        $filas = [$this->obtenerFilaEstadisticaBuscar(['id_estadisticas' => 20])];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('bindValue')
            ->with(':id', 20, PDO::PARAM_INT)
            ->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'buscar',
            'id'     => 20
        ]);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(20, $resultado['datos'][0]['id_estadisticas']);
    }

    // Caso 31: Búsqueda fallida cuando el registro de estadísticas no es encontrado
    #[Group('RF-06')]
    #[Group('ESC-06')]
    #[Group('CP-31')]
    #[Group('escenario_error')]
    public function test_deberia_retornarEstructuraError_cuando_registroNoEsEncontrado(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindValue')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([]); // Vacío

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->Buscar(888);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Registro de estadísticas no encontrado.', $resultado['mensaje']);
    }

    // Caso 32: Error de base de datos durante la ejecución de la búsqueda
    #[Group('RF-06')]
    #[Group('ESC-06')]
    #[Group('CP-32')]
    #[Group('escenario_error')]
    public function test_deberia_retornarEstructuraError_cuando_fallaSentenciaSQLDeBusqueda(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Fallo al ejecutar consulta de búsqueda'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->Buscar(10);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Fallo al ejecutar consulta de búsqueda', $resultado['mensaje']);
    }
}
