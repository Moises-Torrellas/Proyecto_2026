<?php

declare(strict_types=1);

namespace tests\Unit\Premios\ModeloPremios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use PDO;
use PDOStatement;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    $configPath = dirname(__DIR__, 4) . '/config/config.php';
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

class ConsultarTest extends TestCase
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

    #[Group('cu_premios_consultar')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarListadoGeneralPremios_cuando_seConsultaSinFiltros(): void
    {
        // Arrange
        $filasEsperadas = [
            $this->crearFilaPremio(['codigo_premio' => 1, 'nombre' => 'Trofeo Campeon', 'tipo' => 'G']),
            $this->crearFilaPremio(['codigo_premio' => 2, 'nombre' => 'Medalla Mejor Jugador', 'tipo' => 'I'])
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
        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertSame('Trofeo Campeon', $resultado['datos'][0]['nombre']);
        $this->assertSame('Medalla Mejor Jugador', $resultado['datos'][1]['nombre']);
    }

    #[Group('cu_premios_consultar')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarPremiosFiltrados_cuando_seAplicaFiltroTexto(): void
    {
        // Arrange
        $filasEsperadas = [
            $this->crearFilaPremio(['codigo_premio' => 1, 'nombre' => 'Copa Oro', 'tipo' => 'G'])
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->with($this->callback(function (array $params) {
            return isset($params[':f1']) && $params[':f1'] === '%Oro%'
                && isset($params[':f2']) && $params[':f2'] === '%Oro%';
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
        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertSame('Copa Oro', $resultado['datos'][0]['nombre']);
    }

    #[Group('cu_premios_consultar')]
    #[Group('escenario_exito')]
    public function test_deberia_filtrarPorPropiedadesNombreYTipo_cuando_estanDefinidasEnElModelo(): void
    {
        // Arrange
        $filasEsperadas = [
            $this->crearFilaPremio(['codigo_premio' => 1, 'nombre' => 'Trofeo', 'tipo' => 'G'])
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->with($this->callback(function (array $params) {
            return isset($params[':nombre']) && $params[':nombre'] === 'Trofeo%'
                && isset($params[':tipo']) && $params[':tipo'] === 'G';
        }));
        $stmtMock->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtMock) {
            $this->assertStringContainsString('AND nombre LIKE :nombre', $sql);
            $this->assertStringContainsString('AND tipo = :tipo', $sql);
            return $stmtMock;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        $reflector = new ReflectionClass($modelo);
        $propNombre = $reflector->getProperty('nombre');
        $propNombre->setValue($modelo, 'Trofeo');
        $propTipo = $reflector->getProperty('tipo');
        $propTipo->setValue($modelo, 'G');

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    #[Group('cu_premios_consultar')]
    #[Group('escenario_limite')]
    public function test_deberia_retornarColeccionVacia_cuando_noExistenPremiosRegistrados(): void
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
        $this->assertSame('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    #[Group('cu_premios_consultar')]
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
        $this->assertSame('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    #[Group('cu_premios_consultar')]
    #[Group('escenario_limite')]
    public function test_deberia_manejarFiltroConCaracteresEspeciales_cuando_seBuscanSimbolos(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->with($this->callback(function (array $params) {
            return isset($params[':f1']) && $params[':f1'] === '%@#$%^&*()_%';
        }));
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Consultar(['filtro' => '@#$%^&*()_']);

        // Assert
        $this->assertSame('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    #[Group('cu_premios_consultar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarColeccionVacia_cuando_ejecucionDeConsultaNoEncuentraCoincidencias(): void
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
        $resultado = $modelo->Consultar(['filtro' => 'CriterioTotalmenteInexistente']);

        // Assert
        $this->assertSame('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }
}

