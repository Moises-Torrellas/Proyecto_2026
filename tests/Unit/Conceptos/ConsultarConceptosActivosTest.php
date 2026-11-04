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
#[Group('consultarConceptosActivos')]
class ConsultarConceptosActivosTest extends TestCase
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

    // Caso 1: Consulta exitosa de conceptos activos excluyendo multas (frecuencia != 'T')
    public function testConsultarConceptosActivosExitoso(): void
    {
        $filasEsperadas = [
            [
                'codigo_concepto' => 1,
                'nombre'          => 'Mensualidad General',
                'monto'           => 35.00,
                'frecuencia'      => 'M',
                'estatus'         => 1
            ],
            [
                'codigo_concepto' => 2,
                'nombre'          => 'Inscripción Anual',
                'monto'           => 50.00,
                'frecuencia'      => 'A',
                'estatus'         => 1
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'SELECT * FROM conceptos WHERE estatus = 1 AND frecuencia != \'T\'');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ConsultarConcepto();

        $this->assertEquals('consultarCo', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('Mensualidad General', $resultado['datos'][0]['nombre']);
        $this->assertEquals('Inscripción Anual', $resultado['datos'][1]['nombre']);
    }

    // Caso 2: Consulta sin conceptos activos disponibles
    public function testConsultarConceptosActivosVacio(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ConsultarConcepto();

        $this->assertEquals('consultarCo', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 3: Error de base de datos durante la consulta
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de base de datos'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ConsultarConcepto();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de base de datos', $resultado['mensaje']);
    }
}

