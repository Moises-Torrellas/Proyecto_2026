<?php

namespace tests\Unit\Cargos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCargos;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitCargos')]
#[Group('buscarCargos')]
class BuscarCargosTest extends TestCase
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

    // Caso 1: Búsqueda exitosa pasando el identificador como argumento directo
    public function testBuscarPorArgumentoExitoso(): void
    {
        $filasEsperadas = [
            [
                'id_cobrar'       => 8,
                'atleta_nombre'   => 'Andrés',
                'atleta_apellido' => 'Giménez',
                'concepto_nombre' => 'Matrícula',
                'monto_total'     => 120.00
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'SELECT * FROM vista_cargos WHERE id_cobrar = :id');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->Buscar(8);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(8, $resultado['datos'][0]['id_cobrar']);
        $this->assertEquals('Andrés', $resultado['datos'][0]['atleta_nombre']);
    }

    // Caso 2: Búsqueda cuando el cargo no es encontrado en la vista
    public function testBuscarNoEncontrado(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->Buscar(999);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 4: Error en base de datos al buscar
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de base de datos'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->Buscar(8);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de base de datos', $resultado['mensaje']);
    }
}

