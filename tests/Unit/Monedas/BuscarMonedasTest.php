<?php

namespace tests\Unit\Monedas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloMonedas;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitMonedas')]
#[Group('buscarMonedas')]
class BuscarMonedasTest extends TestCase
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
                'codigo_moneda' => 1,
                'nombre'        => 'Bolívar',
                'abreviatura'   => 'VES',
                'simbolo'       => 'Bs.',
                'base'          => 1,
                'estatus'       => 1
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'SELECT * FROM monedas WHERE codigo_moneda = :id');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Bolívar', $resultado['datos'][0]['nombre']);
    }

    // Caso 2: Búsqueda cuando la moneda no es encontrada
    public function testBuscarNoEncontrado(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->Buscar(999);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 4: Error en base de datos al buscar la moneda
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de base de datos'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->Buscar(1);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de base de datos', $resultado['mensaje']);
    }
}

