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
#[Group('cu_premios_asignar')]
class AsignarPremiosTest extends TestCase
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
    public function test_deberia_asignarPremioExitosamente_cuando_datosSonValidos(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([10, 'Premio MVP Temporada Regular', 250.75])
            ->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')
            ->with("CALL RegistrarPremioSeguro(?, ?, ?)")
            ->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->AsignarPremioAtleta(10, 'Premio MVP Temporada Regular', 250.75);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Premio registrado exitosamente para el atleta.', $resultado['mensaje']);
    }

    #[Group('escenario_limite')]
    public function test_deberia_permitirMontoCero_cuando_seAsignaReconocimientoHonorifico(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([5, 'Diploma de reconocimiento', 0.0])
            ->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->AsignarPremioAtleta(5, 'Diploma de reconocimiento', 0.0);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    #[Group('escenario_limite')]
    public function test_deberia_permitirDescripcionVacia_cuando_seAsignaSinDetalles(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([8, '', 50.0])
            ->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->AsignarPremioAtleta(8, '', 50.0);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    #[Group('escenario_error')]
    public function test_deberia_retornarEstructuraError_cuando_fallaProcedimientoAlmacenado(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')
            ->willThrowException(new Exception('El atleta especificado no existe o se encuentra inactivo'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->AsignarPremioAtleta(999, 'Premio Especial', 75.0);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El atleta especificado no existe o se encuentra inactivo', $resultado['mensaje']);
    }
}
