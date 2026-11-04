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
#[Group('consultarMetodosActivos')]
class ConsultarMetodosActivosTest extends TestCase
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

    // Caso 1: Consulta exitosa de métodos de pago activos (estatus = 1)
    public function testConsultarMetodosActivosExitoso(): void
    {
        $filasEsperadas = [
            [
                'codigo_metodo'  => 1,
                'nombre'         => 'Efectivo',
                'nec_referencia' => 0,
                'estatus'        => 1
            ],
            [
                'codigo_metodo'  => 2,
                'nombre'         => 'Pago Móvil',
                'nec_referencia' => 1,
                'estatus'        => 1
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'SELECT * FROM metodos_pago WHERE estatus = 1');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ConsultarMetodos();

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('Efectivo', $resultado['datos'][0]['nombre']);
        $this->assertEquals('Pago Móvil', $resultado['datos'][1]['nombre']);
    }

    // Caso 2: Consulta sin métodos de pago activos registrados
    public function testConsultarMetodosActivosVacio(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ConsultarMetodos();

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 3: Error de base de datos durante la consulta
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de conexión'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ConsultarMetodos();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de conexión', $resultado['mensaje']);
    }
}

