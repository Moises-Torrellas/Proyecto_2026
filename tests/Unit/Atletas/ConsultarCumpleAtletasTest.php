<?php

namespace tests\Unit\Atletas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloAtletas;
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
#[Group('unitAtletas')]
#[Group('cumpleAtletas')]
class ConsultarCumpleAtletasTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    /**
     * Inyecta el mock de PDO en la instancia singleton de Conexion.
     */
    private function crearModeloConPDOMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);
    }

    // Caso 1: Consulta exitosa cuando hay atletas de cumpleaños hoy
    public function testConsultarCumpleExitoso(): void
    {
        $cumpleanerosSimulados = [
            ['nombres' => 'Carlos Alberto', 'apellidos' => 'Perez Gomez'],
            ['nombres' => 'Ana Maria', 'apellidos' => 'Torres Ramos']
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn($cumpleanerosSimulados);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('query')
            ->with($this->stringContains('MONTH(fecha_nac) = MONTH(NOW())'))
            ->willReturn($stmtMock);

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ConsultarCumple();

        $this->assertIsArray($resultado);
        $this->assertCount(2, $resultado);
        $this->assertEquals('Carlos Alberto', $resultado[0]['nombres']);
    }

    // Caso 2: Consulta cuando ningún atleta cumple años hoy
    public function testConsultarCumpleSinCumpleaneros(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('query')->willReturn($stmtMock);

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ConsultarCumple();

        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    // Caso 3: Error en la base de datos es capturado y retorna array vacío
    public function testConsultarCumpleErrorBaseDeDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('query')->willThrowException(new Exception('Error de consulta en base de datos'));

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ConsultarCumple();

        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }
}
