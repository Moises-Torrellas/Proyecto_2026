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
#[Group('modificarEstatusCargos')]
class ModificarEstatusCargosTest extends TestCase
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

    // Caso 1: Modificación de estatus exitosa gestionando transacción propia
    public function testModificarEstatusConTransaccionPropia(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'UPDATE cargos SET estatus = :estatus WHERE codigo_cargo = :id');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ModificarEstatus(15, 2);

        $this->assertTrue($resultado);
    }

    // Caso 2: Modificación de estatus utilizando una conexión externa (dentro de otra transacción)
    public function testModificarEstatusConConexionExterna(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willReturn(true);

        $pdoExternaMock = $this->createMock(PDO::class);
        $pdoExternaMock->expects($this->never())->method('beginTransaction');
        $pdoExternaMock->expects($this->never())->method('commit');
        $pdoExternaMock->method('prepare')->willReturn($stmt);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ModificarEstatus(15, 1, $pdoExternaMock);

        $this->assertTrue($resultado);
    }

    // Caso 3: Error de base de datos con transacción propia revierte los cambios
    public function testErrorBaseDatosConTransaccionPropia(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willThrowException(new Exception('Error al actualizar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ModificarEstatus(15, 2);

        $this->assertFalse($resultado);
    }

    // Caso 4: Error de base de datos con conexión externa retorna falso
    public function testErrorBaseDatosConConexionExterna(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindParam')->willReturn(true);
        $stmt->method('execute')->willThrowException(new Exception('Error externo'));

        $pdoExternaMock = $this->createMock(PDO::class);
        $pdoExternaMock->method('prepare')->willReturn($stmt);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ModificarEstatus(15, 2, $pdoExternaMock);

        $this->assertFalse($resultado);
    }
}

