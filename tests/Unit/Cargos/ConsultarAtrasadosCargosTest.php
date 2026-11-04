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
#[Group('consultarAtrasadosCargos')]
class ConsultarAtrasadosCargosTest extends TestCase
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

    // Caso 1: Consulta exitosa de cargos atrasados con saldo pendiente
    public function testConsultarAtrasadosExitoso(): void
    {
        $filasEsperadas = [
            [
                'codigo_cargo'    => 3,
                'p_nombre'        => 'Gabriel',
                'p_apellidos'     => 'Moreno',
                'concepto'        => 'Mensualidad Vencida',
                'fecha_emision'   => '2025-12-01',
                'monto_pendiente' => 75.00
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'c.fecha_emision < CURDATE()')
                && str_contains($sql, 'HAVING monto_pendiente > 0');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ConsultarAtrasados();

        $this->assertCount(1, $resultado);
        $this->assertEquals(3, $resultado[0]['codigo_cargo']);
        $this->assertEquals('Gabriel', $resultado[0]['p_nombre']);
        $this->assertEquals(75.00, $resultado[0]['monto_pendiente']);
    }

    // Caso 2: No existen cargos atrasados en el sistema
    public function testConsultarAtrasadosVacio(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ConsultarAtrasados();

        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }

    // Caso 3: Error de base de datos retorna arreglo vacío
    public function testErrorBaseDatosRetornaVacio(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de base de datos'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ConsultarAtrasados();

        $this->assertIsArray($resultado);
        $this->assertEmpty($resultado);
    }
}

