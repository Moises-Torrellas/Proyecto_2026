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
#[Group('consultarCargosPendientes')]
class ConsultarCargosPendientesTest extends TestCase
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

    // Caso 1: Consulta exitosa de cargos pendientes con sus montos y saldos
    public function testConsultarCargosPendientesExitoso(): void
    {
        $filasEsperadas = [
            [
                'codigo_cargo'       => 10,
                'p_nombre'           => 'Miguel',
                'p_apellidos'        => 'Cabrera',
                'concepto'           => 'Mensualidad Enero',
                'fecha_emision'      => '2026-01-15',
                'monto_pendiente'    => 50.00,
                'simbolo_moneda'     => '$',
                'moneda_abreviatura' => 'USD'
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'cargos c')
                && str_contains($sql, 'monto_pendiente')
                && str_contains($sql, 'c.estatus = 1');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ConsultarCargos();

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Miguel', $resultado['datos'][0]['p_nombre']);
        $this->assertEquals(50.00, $resultado['datos'][0]['monto_pendiente']);
    }

    // Caso 2: Consulta sin cargos pendientes registrados
    public function testConsultarCargosPendientesVacio(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ConsultarCargos();

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 3: Error de base de datos durante la consulta de cargos pendientes
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Fallo de conexión'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ConsultarCargos();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Fallo de conexión', $resultado['mensaje']);
    }
}

