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

class AsignarPremioAtletaTest extends TestCase
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

    #[Group('cu_premios_asignar')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarExito_cuando_datosDeAsignacionSonValidos(): void
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
        $this->assertIsArray($resultado);
        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame('Premio registrado exitosamente para el atleta.', $resultado['mensaje']);
    }

    #[Group('cu_premios_asignar')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarExito_cuando_seAsignaAMultiplesAtletasSecuencialmente(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado1 = $modelo->AsignarPremioAtleta(1, 'Premio 1', 100.0);
        $resultado2 = $modelo->AsignarPremioAtleta(2, 'Premio 2', 200.0);

        // Assert
        $this->assertSame('exito', $resultado1['accion']);
        $this->assertSame('exito', $resultado2['accion']);
    }

    #[Group('cu_premios_asignar')]
    #[Group('escenario_limite')]
    public function test_deberia_permitirMontoCero_cuando_seAsignaReconocimientoSinCompensacion(): void
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
        $this->assertSame('exito', $resultado['accion']);
    }

    #[Group('cu_premios_asignar')]
    #[Group('escenario_limite')]
    public function test_deberia_permitirDescripcionVacia_cuando_seAsignaSinDetalle(): void
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
        $this->assertSame('exito', $resultado['accion']);
    }

    #[Group('cu_premios_asignar')]
    #[Group('escenario_limite')]
    public function test_deberia_manejarMontosGrandesYLimites_cuando_seAsignaMontoElevado(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([PHP_INT_MAX, 'Gran Premio Nacional', 999999.99])
            ->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->AsignarPremioAtleta(PHP_INT_MAX, 'Gran Premio Nacional', 999999.99);

        // Assert
        $this->assertSame('exito', $resultado['accion']);
    }

    #[Group('cu_premios_asignar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarEstructuraError_cuando_fallaEjecucionDeProcedimientoAlmacenado(): void
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
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('El atleta especificado no existe o se encuentra inactivo', $resultado['mensaje']);
    }

    #[Group('cu_premios_asignar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarEstructuraError_cuando_fallaConexionEnPreparacion(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')
            ->willThrowException(new Exception('Error de comunicacion con el servidor SQL'));
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->AsignarPremioAtleta(1, 'Premio', 50.0);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de comunicacion con el servidor SQL', $resultado['mensaje']);
    }
}

