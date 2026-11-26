<?php

declare(strict_types=1);

namespace tests\Unit\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use PDO;
use PDOStatement;
use Exception;
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
#[Group('RF-06')]
#[Group('ESC-06')]
class BuscarPremiosTest extends TestCase
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

    // ==========================================
    // ESCENARIO 06: Búsqueda de Galardones por Identificador
    // ==========================================

    // Caso 23: Retornar registro de premio cuando se busca por ID existente
    #[Group('RF-06')]
    #[Group('ESC-06')]
    #[Group('CP-23')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarRegistroPremio_cuando_seBuscaPorIdExistente(): void
    {
        // Arrange
        $filasEsperadas = [$this->crearFilaPremio(['codigo_premio' => 1, 'nombre' => 'Copa Oro Hockey'])];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Buscar(1);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['codigo_premio']);
        $this->assertEquals('Copa Oro Hockey', $resultado['datos'][0]['nombre']);
    }

    // Caso 24: Retornar colección vacía cuando el ID buscado no existe
    #[Group('RF-06')]
    #[Group('ESC-06')]
    #[Group('CP-24')]
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
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 25: Manejar límite numérico cuando el ID es el valor entero máximo (PHP_INT_MAX)
    #[Group('RF-06')]
    #[Group('ESC-06')]
    #[Group('CP-25')]
    #[Group('escenario_limite')]
    public function test_deberia_manejarLimiteNumerico_cuando_idEsMaximo(): void
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
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 26: Retornar estructura de error cuando falla la sentencia SQL de búsqueda
    #[Group('RF-06')]
    #[Group('ESC-06')]
    #[Group('CP-26')]
    #[Group('escenario_error')]
    public function test_deberia_retornarEstructuraError_cuando_fallaSentenciaSQL(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de base de datos'));
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Buscar(1);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de base de datos', $resultado['mensaje']);
    }
}
