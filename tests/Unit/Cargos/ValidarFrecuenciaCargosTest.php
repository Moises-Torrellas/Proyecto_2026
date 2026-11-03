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
#[Group('validarFrecuenciaCargos')]
class ValidarFrecuenciaCargosTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    // Caso 1: No existe colisión de frecuencia para el periodo
    public function testSinColision(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['colisiones' => 0]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'colisiones')
                && str_contains($sql, 'FROM conceptos c')
                && str_contains($sql, 'codigo_concepto = :id_concepto');
        }))->willReturn($stmt);

        $modelo = new ModeloCargos();
        $resultado = $modelo->validarFrecuencia($pdoMock, 1, 10, '2026-03-15');

        $this->assertFalse($resultado);
    }

    // Caso 2: Existe colisión de frecuencia para el atleta en el periodo
    public function testConColision(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['colisiones' => 1]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $modelo = new ModeloCargos();
        $resultado = $modelo->validarFrecuencia($pdoMock, 1, 10, '2026-03-15');

        $this->assertTrue($resultado);
    }

    // Caso 3: La consulta no retorna fila de resultado (falso)
    public function testResultadoVacioRetornaFalso(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $modelo = new ModeloCargos();
        $resultado = $modelo->validarFrecuencia($pdoMock, 1, 10, '2026-03-15');

        $this->assertFalse($resultado);
    }

    // Caso 4: Error en base de datos relanza la excepción con mensaje descriptivo
    public function testErrorBaseDatosLanzaExcepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Error al validar la frecuencia del concepto en la base de datos.');

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willThrowException(new Exception('Error de ejecución'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $modelo = new ModeloCargos();
        $modelo->validarFrecuencia($pdoMock, 1, 10, '2026-03-15');
    }
}
