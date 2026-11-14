<?php

declare(strict_types=1);

namespace tests\Unit\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    $configPath = dirname(__DIR__, 3) . '/config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
}

if (!defined('DUPLICATE_NAME')) {
    define('DUPLICATE_NAME', '004');
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
if (!defined('DB_CONNECTION')) {
    define('DB_CONNECTION', '500');
}

#[Group('unit')]
#[Group('unitPremios')]
#[Group('cu_premios_consultar')]
class ConsultarPremiosTest extends TestCase
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

    private function crearFilaPremio(array $sobrescribir = []): array
    {
        $base = [
            'codigo_premio' => 1,
            'nombre'        => 'Trofeo Oro',
            'tipo'          => 'G'
        ];

        return array_merge($base, $sobrescribir);
    }

    #[Group('escenario_exito')]
    public function test_deberia_retornarListadoGeneralPremios_cuando_seConsultaSinFiltros(): void
    {
        // Arrange
        $filasEsperadas = [
            $this->crearFilaPremio(['codigo_premio' => 1, 'nombre' => 'Trofeo Campeon', 'tipo' => 'G']),
            $this->crearFilaPremio(['codigo_premio' => 2, 'nombre' => 'Medalla Mejor Portero', 'tipo' => 'I'])
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->with([]);
        $stmtMock->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtMock) {
            $this->assertStringContainsString('SELECT * FROM premios WHERE 1=1', $sql);
            $this->assertStringContainsString('ORDER BY codigo_premio ASC', $sql);
            return $stmtMock;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Consultar([]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('Trofeo Campeon', $resultado['datos'][0]['nombre']);
    }

    #[Group('escenario_exito')]
    public function test_deberia_retornarPremiosFiltrados_cuando_seAplicaFiltroTexto(): void
    {
        // Arrange
        $filasEsperadas = [
            $this->crearFilaPremio(['codigo_premio' => 1, 'nombre' => 'Copa Oro', 'tipo' => 'G'])
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->with($this->callback(function (array $params) {
            return isset($params[':f1']) && $params[':f1'] === '%Oro%';
        }));
        $stmtMock->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtMock) {
            $this->assertStringContainsString('AND (nombre LIKE :f1 OR tipo LIKE :f2)', $sql);
            return $stmtMock;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Consultar(['filtro' => 'Oro']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    #[Group('escenario_limite')]
    public function test_deberia_retornarColeccionVacia_cuando_noExistenPremiosEnBaseDeDatos(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Consultar([]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    #[Group('escenario_limite')]
    public function test_deberia_ignorarFiltroVacio_cuando_sePasaCadenaVacia(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->with([]);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtMock) {
            $this->assertStringNotContainsString(':f1', $sql);
            return $stmtMock;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Consultar(['filtro' => '']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
    }

    #[Group('escenario_error')]
    public function test_deberia_retornarColeccionVacia_cuando_filtroDeBusquedaNoEncuentraCoincidencias(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Consultar(['filtro' => 'Inexistente_12345']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }
}
