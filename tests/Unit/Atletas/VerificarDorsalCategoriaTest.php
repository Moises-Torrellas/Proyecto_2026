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

    // Caso 1: Dorsal vacío retorna true inmediatamente sin consultar la base de datos
    public function testDorsalVacioRetornaTrue(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->never())->method('prepare');
        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $this->invocarVerificarDorsalCategoria($modelo, '', '1');

        $this->assertTrue($resultado);
    }

    // Caso 2: Categoría vacía retorna true inmediatamente sin consultar la base de datos
    public function testCategoriaVaciaRetornaTrue(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->never())->method('prepare');
        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo;
        $this->assertTrue($this->invocarVerificarDorsalCategoria($modelo, '10', ''));
    }

    // Caso 3: Dorsal disponible sin id_atleta (caso registro nuevo)
    public function testDorsalDisponibleSinIdAtletaRetornaTrue(): void
    {
        $sqlEjecutado = null;
        $paramsEjecutados = null;

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturnCallback(function ($params) use (&$paramsEjecutados) {
            $paramsEjecutados = $params;
            return true;
        });
        $stmtMock->method('fetchColumn')->willReturn(0); // 0 inscripciones activas con ese dorsal

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$sqlEjecutado, $stmtMock) {
            $sqlEjecutado = $sql;
            return $stmtMock;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $this->invocarVerificarDorsalCategoria($modelo, '10', '1', null);

        $this->assertTrue($resultado);
        $this->assertStringNotContainsString('codigo_atleta != :id', $sqlEjecutado);
        $this->assertEquals([':dorsal' => '10', ':categoria' => '1'], $paramsEjecutados);
    }

    // Caso 4: Dorsal ocupado sin id_atleta (caso registro nuevo)
    public function testDorsalOcupadoSinIdAtletaRetornaFalse(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1); // Ya existe una inscripción activa

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $this->invocarVerificarDorsalCategoria($modelo, '10', '1', null);

        $this->assertFalse($resultado);
    }

    // Caso 5: Dorsal disponible con id_atleta (excluye al atleta actual)
    public function testDorsalDisponibleConIdAtletaRetornaTrue(): void
    {
        $sqlEjecutado = null;
        $paramsEjecutados = null;

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturnCallback(function ($params) use (&$paramsEjecutados) {
            $paramsEjecutados = $params;
            return true;
        });
        $stmtMock->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$sqlEjecutado, $stmtMock) {
            $sqlEjecutado = $sql;
            return $stmtMock;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $this->invocarVerificarDorsalCategoria($modelo, '10', '1', '5');

        $this->assertTrue($resultado);
        $this->assertStringContainsString('codigo_atleta != :id', $sqlEjecutado);
        $this->assertEquals([':dorsal' => '10', ':categoria' => '1', ':id' => '5'], $paramsEjecutados);
    }

    // Caso 6: Dorsal ocupado por otro atleta con id_atleta
    public function testDorsalOcupadoPorOtroAtletaRetornaFalse(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1); // Ocupado por otro atleta

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $this->invocarVerificarDorsalCategoria($modelo, '10', '1', '5');

        $this->assertFalse($resultado);
    }
}
