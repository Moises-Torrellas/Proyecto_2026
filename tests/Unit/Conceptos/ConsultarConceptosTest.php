<?php

namespace tests\Unit\Conceptos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloConceptos;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitConceptos')]
#[Group('consultarConceptos')]
class ConsultarConceptosTest extends TestCase
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

    // Caso 1: Consulta general sin filtros con obtención del símbolo de moneda base
    public function testConsultarGeneral(): void
    {
        $filasEsperadas = [
            [
                'codigo_concepto' => 1,
                'nombre'          => 'Mensualidad',
                'monto'           => 30.00,
                'frecuencia'      => 'M',
                'estatus'         => 1
            ]
        ];

        $stmtConceptos = $this->createMock(PDOStatement::class);
        $stmtConceptos->method('execute')->willReturn(true);
        $stmtConceptos->method('fetchAll')->willReturn($filasEsperadas);

        $stmtMoneda = $this->createMock(PDOStatement::class);
        $stmtMoneda->method('execute')->willReturn(true);
        $stmtMoneda->method('fetchColumn')->willReturn('Bs.');

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtConceptos, $stmtMoneda) {
            if (str_contains($sql, 'SELECT * FROM conceptos')) {
                return $stmtConceptos;
            }
            return $stmtMoneda;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Mensualidad', $resultado['datos'][0]['nombre']);
        $this->assertEquals('Bs.', $resultado['datos'][0]['simbolo_base']);
    }

    // Caso 2: Consulta con filtro de texto global (búsqueda por nombre y monto)
    public function testConsultarConFiltroTexto(): void
    {
        $stmtConceptos = $this->createMock(PDOStatement::class);
        $stmtConceptos->method('execute')->with($this->callback(function (array $params) {
            return ($params[':f1'] ?? null) === '%Matrícula%' && ($params[':f2'] ?? null) === '%Matrícula%';
        }))->willReturn(true);
        $stmtConceptos->method('fetchAll')->willReturn([
            ['codigo_concepto' => 2, 'nombre' => 'Matrícula', 'monto' => 50.00]
        ]);

        $stmtMoneda = $this->createMock(PDOStatement::class);
        $stmtMoneda->method('execute')->willReturn(true);
        $stmtMoneda->method('fetchColumn')->willReturn('$');

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtConceptos, $stmtMoneda) {
            if (str_contains($sql, 'SELECT * FROM conceptos')) {
                return $stmtConceptos;
            }
            return $stmtMoneda;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->Consultar(['filtro' => 'Matrícula']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 3: Consulta con filtros específicos de nombre y monto en el objeto
    public function testConsultarConPropiedadesObjeto(): void
    {
        $stmtConceptos = $this->createMock(PDOStatement::class);
        $stmtConceptos->method('execute')->with($this->callback(function (array $params) {
            return ($params[':nombre'] ?? null) === '%Uniforme%' && ($params[':monto'] ?? null) == 25;
        }))->willReturn(true);
        $stmtConceptos->method('fetchAll')->willReturn([
            ['codigo_concepto' => 3, 'nombre' => 'Uniforme', 'monto' => 25.00]
        ]);

        $stmtMoneda = $this->createMock(PDOStatement::class);
        $stmtMoneda->method('execute')->willReturn(true);
        $stmtMoneda->method('fetchColumn')->willReturn('$');

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtConceptos, $stmtMoneda) {
            if (str_contains($sql, 'SELECT * FROM conceptos')) {
                return $stmtConceptos;
            }
            return $stmtMoneda;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $reflector = new ReflectionClass($modelo);
        $propNombre = $reflector->getProperty('nombre');
        $propMonto = $reflector->getProperty('monto');
        $propNombre->setValue($modelo, 'Uniforme');
        $propMonto->setValue($modelo, 25);

        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 4: Consulta sin registros encontrados
    public function testConsultarSinRegistros(): void
    {
        $stmtConceptos = $this->createMock(PDOStatement::class);
        $stmtConceptos->method('execute')->willReturn(true);
        $stmtConceptos->method('fetchAll')->willReturn([]);

        $stmtMoneda = $this->createMock(PDOStatement::class);
        $stmtMoneda->method('execute')->willReturn(true);
        $stmtMoneda->method('fetchColumn')->willReturn(false); // Retorna falso, usa '$'

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtConceptos, $stmtMoneda) {
            if (str_contains($sql, 'SELECT * FROM conceptos')) {
                return $stmtConceptos;
            }
            return $stmtMoneda;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 5: Error en base de datos durante la consulta
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de base de datos'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->Consultar();

        $this->assertEquals('error', $resultado['accion']);
    }
}

