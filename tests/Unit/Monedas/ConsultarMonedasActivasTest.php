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
#[Group('consultarMonedasActivas')]
class ConsultarMonedasActivasTest extends TestCase
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

    // Caso 1: Consulta exitosa de monedas con estatus activo (1)
    public function testConsultarMonedasActivasExitoso(): void
    {
        $filasEsperadas = [
            [
                'codigo_moneda' => 1,
                'nombre'        => 'Bolívar',
                'abreviatura'   => 'VES',
                'simbolo'       => 'Bs.',
                'estatus'       => 1
            ],
            [
                'codigo_moneda' => 2,
                'nombre'        => 'Dólar',
                'abreviatura'   => 'USD',
                'simbolo'       => '$',
                'estatus'       => 1
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'SELECT * FROM monedas WHERE estatus = 1');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ConsultarMonedas();

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('Bolívar', $resultado['datos'][0]['nombre']);
        $this->assertEquals('Dólar', $resultado['datos'][1]['nombre']);
    }

    // Caso 2: Consulta sin monedas activas registradas
    public function testConsultarMonedasActivasVacio(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ConsultarMonedas();

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

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ConsultarMonedas();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de conexión', $resultado['mensaje']);
    }
}

