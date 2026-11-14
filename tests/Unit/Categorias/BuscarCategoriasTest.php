<?php

namespace tests\Unit\Categorias;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCategorias;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitCategorias')]
#[Group('buscarCategorias')]
class BuscarCategoriasTest extends TestCase
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

    private function obtenerFilaCategoria(array $sobrescribir = []): array
    {
        $base = [
            'codigo_categoria' => 1,
            'nombre'           => 'Sub-15',
            'edad_min'         => 13,
            'edad_max'         => 15
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Búsqueda exitosa pasando el id como argumento
    public function testBuscarPorArgumentoExitoso(): void
    {
        $filas = [$this->obtenerFilaCategoria()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['codigo_categoria']);
    }

    // Caso 2: Búsqueda de una categoría inexistente
    public function testBuscarNoEncontrado(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->Buscar(999);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 4: Error en base de datos al buscar
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error al buscar categoría'));
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->Buscar(1);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al buscar categoría', $resultado['mensaje']);
    }
}

