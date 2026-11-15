<?php

declare(strict_types=1);

namespace tests\Unit\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use PDO;
use PDOStatement;
use PDOException;
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
#[Group('cu_premios_validar_tipo')]
class ValidarTipoPremiosTest extends TestCase
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

    #[Group('escenario_exito')]
    public function test_deberia_completarExitosamente_cuando_tipoCoincideConElEsperado(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('bindValue')->with(':codigo_premio', 1, PDO::PARAM_INT);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())->method('fetch')->willReturn(['tipo' => 'I']);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act & Assert
        $modelo->validarTipoPremio(1, 'I');
        $this->assertTrue(true);
    }

    #[Group('escenario_limite')]
    public function test_deberia_evaluarCorrectamente_cuando_codigoEsLimiteNumerico(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('bindValue')->with(':codigo_premio', PHP_INT_MAX, PDO::PARAM_INT);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())->method('fetch')->willReturn(['tipo' => 'G']);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act & Assert
        $modelo->validarTipoPremio(PHP_INT_MAX, 'G');
        $this->assertTrue(true);
    }

    #[Group('escenario_error')]
    public function test_deberia_lanzarExcepcionInvalidId_cuando_premioNoExiste(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(INVALID_ID);

        // Act
        $modelo->validarTipoPremio(999, 'G');
    }

    #[Group('escenario_error')]
    public function test_deberia_lanzarExcepcionValidation_cuando_tipoNoCoincide(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(['tipo' => 'G']);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(VALIDATION);

        // Act
        $modelo->validarTipoPremio(1, 'I');
    }
}
