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

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitConceptos')]
#[Group('cambiarEstatusConceptos')]
class CambiarEstatusConceptosTest extends TestCase
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

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $base = [
            'accion'  => 'estatus',
            'id'      => '6',
            'estatus' => 1
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Cambio de estatus a inactivo (2) cuando el concepto está activo (1)
    public function testDesactivarExitoso(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistencia, $stmtUpdate) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExistencia;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: Cambio de estatus a activo (1) cuando el concepto está inactivo (2)
    public function testActivarExitoso(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistencia, $stmtUpdate) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExistencia;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'estatus' => 2
        ]));

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 3: El concepto de cobro cuyo estatus se desea cambiar no existe
    public function testConceptoNoExiste(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExistencia);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 4: Error en base de datos durante la actualización de estatus
    public function testErrorBaseDatosAlCambiarEstatus(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willThrowException(new Exception('Error al actualizar estatus'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistencia, $stmtUpdate) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExistencia;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al actualizar estatus', $resultado['codigo']);
    }
}

