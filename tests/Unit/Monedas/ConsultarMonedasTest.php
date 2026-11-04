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
#[Group('consultarMonedas')]
class ConsultarMonedasTest extends TestCase
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

    // Caso 1: Consulta general sin filtros
    public function testConsultarGeneral(): void
    {
        $filasEsperadas = [
            [
                'codigo_moneda' => 1,
                'nombre'        => 'Bolívar Digital',
                'abreviatura'   => 'VES',
                'simbolo'       => 'Bs.',
                'base'          => 1,
                'estatus'       => 1
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'SELECT * FROM monedas WHERE 1=1')
                && !str_contains($sql, 'LIKE :f1');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Bolívar Digital', $resultado['datos'][0]['nombre']);
    }

    // Caso 2: Consulta con filtro de texto global
    public function testConsultarConFiltroTexto(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->with($this->callback(function (array $params) {
            return ($params[':f1'] ?? null) === '%Dólar%'
                && ($params[':f2'] ?? null) === '%Dólar%'
                && ($params[':f3'] ?? null) === '%Dólar%';
        }))->willReturn(true);
        $stmt->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([
            ['codigo_moneda' => 2, 'nombre' => 'Dólar', 'abreviatura' => 'USD']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'nombre LIKE :f1 OR')
                && str_contains($sql, 'abreviatura LIKE :f2 OR')
                && str_contains($sql, 'simbolo LIKE :f3');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->Consultar(['filtro' => 'Dólar']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 3: Consulta sin registros retornados
    public function testConsultarSinRegistros(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 4: Error en base de datos durante la consulta
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de base de datos'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->Consultar();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al listar las monedas.', $resultado['mensaje']);
    }
}

