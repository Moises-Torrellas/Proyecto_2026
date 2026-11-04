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
#[Group('consultarCategorias')]
class ConsultarCategoriasTest extends TestCase
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

    // Caso 1: Consulta general sin filtros
    public function testConsultarGeneral(): void
    {
        $filas = [$this->obtenerFilaCategoria()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Sub-15', $resultado['datos'][0]['nombre']);
    }

    // Caso 2: Consulta aplicando filtro por nombre
    public function testConsultarConFiltro(): void
    {
        $filas = [$this->obtenerFilaCategoria(['nombre' => 'Juvenil'])];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->with($this->callback(function ($params) {
            return isset($params[':f1']) && $params[':f1'] === '%Juvenil%';
        }))->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->Consultar(['filtro' => 'Juvenil']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 3: Consulta invocada mediante ProcesarDatos con acción generar
    public function testConsultarPorGenerar(): void
    {
        $filas = [$this->obtenerFilaCategoria()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos([
            'accion'   => 'generar',
            'nombre'   => 'Sub-15',
            'edad_min' => '13',
            'edad_max' => '15'
        ]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 4: Consulta invocada mediante ProcesarDatos con acción consultar
    public function testConsultarPorAccionConsultar(): void
    {
        $filas = [$this->obtenerFilaCategoria()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos(['accion' => 'consultar']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 5: Consulta que no encuentra ningún resultado
    public function testConsultarVacio(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->Consultar(['filtro' => 'Inexistente']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 6: Error de base de datos durante la consulta
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error al consultar'));
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->Consultar();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al consultar', $resultado['mensaje']);
    }
}

