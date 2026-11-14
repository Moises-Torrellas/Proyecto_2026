<?php

namespace tests\Unit\Atletas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloAtletas;
use App\modelo\Conexion;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitAtletas')]
#[Group('verificarDorsalAtletas')]
class VerificarDorsalCategoriaTest extends TestCase
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

    /**
     * Invoca el método privado verificarDorsalCategoria mediante Reflection.
     */
    private function invocarVerificarDorsalCategoria(ModeloAtletas $modelo, $dorsal, $categoria, $id_atleta = null): bool
    {
        $reflector = new ReflectionClass(ModeloAtletas::class);
        $metodo = $reflector->getMethod('verificarDorsalCategoria');
        return $metodo->invoke($modelo, $dorsal, $categoria, $id_atleta);
    }

    // Caso 1: Parámetros vacíos (dorsal o categoría) retornan true inmediatamente sin consultar BD
    public function testParametrosVaciosRetornanTrue(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->never())->method('prepare');
        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $this->assertTrue($this->invocarVerificarDorsalCategoria($modelo, '', '1'));
        $this->assertTrue($this->invocarVerificarDorsalCategoria($modelo, '10', ''));
    }

    // Caso 2: Dorsal disponible (con y sin id_atleta) retorna true
    public function testDorsalDisponibleRetornaTrue(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $this->assertTrue($this->invocarVerificarDorsalCategoria($modelo, '10', '1', null));
        $this->assertTrue($this->invocarVerificarDorsalCategoria($modelo, '10', '1', '5'));
    }

    // Caso 3: Dorsal ocupado (con y sin id_atleta) retorna false
    public function testDorsalOcupadoRetornaFalse(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $this->assertFalse($this->invocarVerificarDorsalCategoria($modelo, '10', '1', null));
        $this->assertFalse($this->invocarVerificarDorsalCategoria($modelo, '10', '1', '5'));
    }
}
