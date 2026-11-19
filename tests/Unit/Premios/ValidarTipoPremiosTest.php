<?php

namespace tests\Unit\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitPremios')]
#[Group('RF-07')]
#[Group('ESC-07')]
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

    // Caso 31: Validación exitosa del tipo de premio cuando coincide con el tipo esperado
    #[Group('RF-07')]
    #[Group('ESC-07')]
    #[Group('CP-31')]
    public function testValidarTipoPremioExitoso(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindValue')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(['tipo' => 'I']);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('SELECT tipo FROM premios WHERE codigo_premio = :codigo_premio'))->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act & Assert (No debe lanzar excepción)
        $modelo->validarTipoPremio(3, 'I');
        $this->assertTrue(true, 'La validación del tipo de premio concluyó exitosamente.');
    }

    // Caso 32: Validación de tipo lanza excepción INVALID_ID cuando el premio no existe
    #[Group('RF-07')]
    #[Group('ESC-07')]
    #[Group('CP-32')]
    public function testValidarTipoPremioInexistenteLanzaExcepcion(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindValue')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(false); // No encontrado

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Assert & Act
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(INVALID_ID);

        $modelo->validarTipoPremio(999, 'G');
    }

    // Caso 33: Validación de tipo lanza excepción VALIDATION cuando el tipo no coincide con el esperado
    #[Group('RF-07')]
    #[Group('ESC-07')]
    #[Group('CP-33')]
    public function testValidarTipoPremioDiscrepanciaTipoLanzaExcepcion(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindValue')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(['tipo' => 'G']); // En BD es Grupal

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Assert & Act - Se valida esperando 'I' pero es 'G'
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(VALIDATION);

        $modelo->validarTipoPremio(1, 'I');
    }
}
