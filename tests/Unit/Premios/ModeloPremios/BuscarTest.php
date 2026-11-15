<?php

declare(strict_types=1);

namespace tests\Unit\Premios\ModeloPremios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use PDO;
use PDOStatement;
use Exception;
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

class BuscarTest extends TestCase
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
            'nombre'        => 'Copa Oro Hockey',
            'tipo'          => 'G'
        ];

        return array_merge($base, $sobrescribir);
    }

    #[Group('cu_premios_buscar')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarRegistroPremio_cuando_seBuscaPorIdExistente(): void
    {
        // Arrange
        $filaEsperada = [$this->crearFilaPremio(['codigo_premio' => 1, 'nombre' => 'Copa Oro Hockey'])];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filaEsperada);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Buscar(1);

        // Assert
        $this->assertIsArray($resultado);
        $this->assertSame('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertSame(1, $resultado['datos'][0]['codigo_premio']);
        $this->assertSame('Copa Oro Hockey', $resultado['datos'][0]['nombre']);
    }

    #[Group('cu_premios_buscar')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarRegistroPremio_cuando_seBuscaSinArgumentoPeroConCodigoPremioPreviamenteAsignado(): void
    {
        // Arrange
        $filaEsperada = [$this->crearFilaPremio(['codigo_premio' => 5, 'nombre' => 'Medalla Plata', 'tipo' => 'I'])];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filaEsperada);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Asignamos el código de premio interno mediante reflexión
        $reflector = new ReflectionClass($modelo);
        $propCodigo = $reflector->getProperty('codigo_premio');
        $propCodigo->setValue($modelo, 5);

        // Act
        $resultado = $modelo->Buscar();

        // Assert
        $this->assertSame('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertSame(5, $resultado['datos'][0]['codigo_premio']);
        $this->assertSame('Medalla Plata', $resultado['datos'][0]['nombre']);
    }

    #[Group('cu_premios_buscar')]
    #[Group('escenario_limite')]
    public function test_deberia_retornarColeccionVacia_cuando_idNoExiste(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Buscar(99999);

        // Assert
        $this->assertSame('buscar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    #[Group('cu_premios_buscar')]
    #[Group('escenario_limite')]
    public function test_deberia_manejarLimiteNumericoMaximo_cuando_seConsultaLimite(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Buscar(PHP_INT_MAX);

        // Assert
        $this->assertSame('buscar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    #[Group('cu_premios_buscar')]
    #[Group('escenario_limite')]
    public function test_deberia_retornarColeccionVacia_cuando_idEsNuloYNoExisteCodigoPremioAsignado(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Buscar(null);

        // Assert
        $this->assertSame('buscar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    #[Group('cu_premios_buscar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarEstructuraError_cuando_fallaPreparacionDeSentenciaSQL(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de comunicacion con el motor'));
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Buscar(1);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de comunicacion con el motor', $resultado['mensaje']);
    }

    #[Group('cu_premios_buscar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarEstructuraError_cuando_fallaEjecucionDeSentenciaSQL(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willThrowException(new Exception('Error al ejecutar query de busqueda'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Buscar(1);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al ejecutar query de busqueda', $resultado['mensaje']);
    }
}

