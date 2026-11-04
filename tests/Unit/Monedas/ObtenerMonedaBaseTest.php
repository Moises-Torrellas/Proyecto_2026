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
#[Group('obtenerMonedaBase')]
class ObtenerMonedaBaseTest extends TestCase
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

    // Caso 1: Obtención exitosa de la moneda base activa del sistema
    public function testObtenerMonedaBaseExitoso(): void
    {
        $datoEsperado = [
            'codigo_moneda' => 1,
            'nombre'        => 'Bolívar Digital',
            'simbolo'       => 'Bs.',
            'abreviatura'   => 'VES'
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($datoEsperado);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'WHERE base = 1 AND estatus = 1')
                && str_contains($sql, 'LIMIT 1');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->obtenerMonedaBase();

        $this->assertEquals('consultarMoneda', $resultado['accion']);
        $this->assertEquals(1, $resultado['datos']['codigo_moneda']);
        $this->assertEquals('Bolívar Digital', $resultado['datos']['nombre']);
        $this->assertEquals('Bs.', $resultado['datos']['simbolo']);
        $this->assertEquals('VES', $resultado['datos']['abreviatura']);
    }

    // Caso 2: No hay moneda base activa configurada en el sistema
    public function testMonedaBaseNoConfigurada(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->obtenerMonedaBase();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('No hay moneda base configurada en el sistema.', $resultado['mensaje']);
    }

    // Caso 3: Error de base de datos durante la consulta de la moneda base
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de base de datos'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->obtenerMonedaBase();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de base de datos', $resultado['mensaje']);
    }
}

