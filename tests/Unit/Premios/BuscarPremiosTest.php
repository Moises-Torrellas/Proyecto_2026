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

    private function obtenerFilaPremio(array $sobrescribir = []): array
    {
        $base = [
            'codigo_premio' => 3,
            'nombre'        => 'Mvp',
            'tipo'          => 'I'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 27: Búsqueda exitosa pasando el código de premio como argumento directo
    #[Group('RF-06')]
    #[Group('ESC-06')]
    #[Group('CP-27')]
    public function testBuscarPorArgumentoExitoso(): void
    {
        // Arrange
        $filas = [$this->obtenerFilaPremio()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('WHERE codigo_premio = :codigo_premio'))->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Buscar(3);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(3, $resultado['datos'][0]['codigo_premio']);
        $this->assertEquals('Mvp', $resultado['datos'][0]['nombre']);
    }

    // Caso 28: Búsqueda exitosa invocada a través de ProcesarDatos
    #[Group('RF-06')]
    #[Group('ESC-06')]
    #[Group('CP-28')]
    public function testBuscarPorProcesarDatosExitoso(): void
    {
        // Arrange
        $filas = [$this->obtenerFilaPremio(['codigo_premio' => 1, 'nombre' => 'Campeón', 'tipo' => 'G'])];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('WHERE codigo_premio = :codigo_premio'))->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'buscar',
            'codigo_premio' => 1
        ]);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Campeón', $resultado['datos'][0]['nombre']);
    }

    // Caso 29: Búsqueda de un premio inexistente devuelve lista vacía
    #[Group('RF-06')]
    #[Group('ESC-06')]
    #[Group('CP-29')]
    public function testBuscarPremioInexistente(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Buscar(999);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 30: Captura de error de base de datos durante la búsqueda
    #[Group('RF-06')]
    #[Group('ESC-06')]
    #[Group('CP-30')]
    public function testBuscarErrorBaseDeDatos(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willThrowException(new PDOException('Error de lectura en tabla premios'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Buscar(5);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error de lectura en tabla premios', $resultado['mensaje']);
    }
}
