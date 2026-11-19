<?php

namespace tests\Unit\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitPremios')]
#[Group('RF-08')]
#[Group('ESC-08')]
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

    // Caso 34: Asignación exitosa de premio a un atleta mediante el procedimiento almacenado
    #[Group('RF-08')]
    #[Group('ESC-08')]
    #[Group('CP-34')]
    public function testAsignarPremioAtletaExitoso(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->with([12, 'Bono Rendimiento Especial', 150.00])->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('CALL RegistrarPremioSeguro(?, ?, ?)'))->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->AsignarPremioAtleta(12, 'Bono Rendimiento Especial', 150.00);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Premio registrado exitosamente para el atleta.', $resultado['mensaje']);
    }

    // Caso 35: Asignación de premio a atleta captura excepción y retorna estructura con estado de error
    #[Group('RF-08')]
    #[Group('ESC-08')]
    #[Group('CP-35')]
    public function testAsignarPremioAtletaFalloCapturaExcepcion(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willThrowException(new PDOException('Error al invocar procedimiento almacenado'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->AsignarPremioAtleta(15, 'Premio Inválido', 50.0);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error al invocar procedimiento almacenado', $resultado['mensaje']);
    }
}
