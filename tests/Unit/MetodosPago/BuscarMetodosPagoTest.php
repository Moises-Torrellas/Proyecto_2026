<?php

namespace tests\Unit\MetodosPago;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloMetodosPago;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitMetodosPago')]
#[Group('buscarMetodosPago')]
class BuscarMetodosPagoTest extends TestCase
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
                'codigo_metodo'  => 1,
                'nombre'         => 'Efectivo Bolívares',
                'nec_referencia' => 0,
                'estatus'        => 1
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'SELECT * FROM metodos_pago WHERE codigo_metodo = :id');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Efectivo Bolívares', $resultado['datos'][0]['nombre']);
    }

    // Caso 2: Búsqueda cuando el método de pago no existe
    public function testBuscarNoEncontrado(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->Buscar(999);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 4: Error en base de datos al buscar el método de pago
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de base de datos'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->Buscar(1);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de base de datos', $resultado['mensaje']);
    }
}

