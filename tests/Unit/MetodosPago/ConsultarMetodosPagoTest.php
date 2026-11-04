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
#[Group('consultarMetodosPago')]
class ConsultarMetodosPagoTest extends TestCase
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
                'codigo_metodo'  => 1,
                'nombre'         => 'Efectivo',
                'nec_referencia' => 0,
                'estatus'        => 1
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'SELECT * FROM metodos_pago WHERE 1=1')
                && str_contains($sql, 'ORDER BY codigo_metodo ASC')
                && !str_contains($sql, 'LIKE :f1');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Efectivo', $resultado['datos'][0]['nombre']);
    }

    // Caso 2: Consulta con filtro de texto global
    public function testConsultarConFiltroTexto(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->with($this->callback(function (array $params) {
            return ($params[':f1'] ?? null) === '%Transferencia%';
        }))->willReturn(true);
        $stmt->method('fetchAll')->willReturn([
            ['codigo_metodo' => 2, 'nombre' => 'Transferencia']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'AND (nombre LIKE :f1)');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->Consultar(['filtro' => 'Transferencia']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 3: Consulta con filtro por propiedad interna de nombre
    public function testConsultarConPropiedadNombre(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->with($this->callback(function (array $params) {
            return ($params[':nombre'] ?? null) === '%Pago%';
        }))->willReturn(true);
        $stmt->method('fetchAll')->willReturn([
            ['codigo_metodo' => 3, 'nombre' => 'Pago Móvil']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'AND nombre LIKE :nombre');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();

        $reflector = new ReflectionClass($modelo);
        $prop = $reflector->getProperty('nombre');
        $prop->setValue($modelo, 'Pago');

        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 4: Consulta sin registros encontrados
    public function testConsultarSinRegistros(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 5: Error en base de datos durante la consulta
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de consulta'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->Consultar();

        $this->assertEquals('error', $resultado['accion']);
    }
}

