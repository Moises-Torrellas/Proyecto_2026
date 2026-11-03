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
#[Group('eliminarConceptos')]
class EliminarConceptosTest extends TestCase
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
            'accion' => 'eliminar',
            'id'     => '5'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Eliminación física exitosa del concepto de cobro
    public function testEliminarExitoso(): void
    {
        $stmtExistenciaConcepto = $this->createMock(PDOStatement::class);
        $stmtExistenciaConcepto->method('bindValue')->willReturn(true);
        $stmtExistenciaConcepto->method('execute')->willReturn(true);
        $stmtExistenciaConcepto->method('fetchColumn')->willReturn(1);

        $stmtExistenciaCargos = $this->createMock(PDOStatement::class);
        $stmtExistenciaCargos->method('bindValue')->willReturn(true);
        $stmtExistenciaCargos->method('execute')->willReturn(true);
        $stmtExistenciaCargos->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('bindParam')->willReturn(true);
        $stmtDelete->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaConcepto,
            $stmtExistenciaCargos,
            $stmtDelete
        ) {
            if (str_contains($sql, 'FROM `conceptos`')) {
                return $stmtExistenciaConcepto;
            }
            if (str_contains($sql, 'FROM `cargos`')) {
                return $stmtExistenciaCargos;
            }
            return $stmtDelete;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: El concepto a eliminar no existe
    public function testConceptoNoExiste(): void
    {
        $stmtExistenciaConcepto = $this->createMock(PDOStatement::class);
        $stmtExistenciaConcepto->method('bindValue')->willReturn(true);
        $stmtExistenciaConcepto->method('execute')->willReturn(true);
        $stmtExistenciaConcepto->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExistenciaConcepto);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 3: El concepto tiene cargos asociados en la base de datos
    public function testConceptoConCargosAsociados(): void
    {
        $stmtExistenciaConcepto = $this->createMock(PDOStatement::class);
        $stmtExistenciaConcepto->method('bindValue')->willReturn(true);
        $stmtExistenciaConcepto->method('execute')->willReturn(true);
        $stmtExistenciaConcepto->method('fetchColumn')->willReturn(1);

        $stmtExistenciaCargos = $this->createMock(PDOStatement::class);
        $stmtExistenciaCargos->method('bindValue')->willReturn(true);
        $stmtExistenciaCargos->method('execute')->willReturn(true);
        $stmtExistenciaCargos->method('fetchColumn')->willReturn(1); // Tiene cargos asociados

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaConcepto,
            $stmtExistenciaCargos
        ) {
            if (str_contains($sql, 'FROM `conceptos`')) {
                return $stmtExistenciaConcepto;
            }
            return $stmtExistenciaCargos;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo']);
    }

    // Caso 4: Error en base de datos durante la ejecución de DELETE
    public function testErrorBaseDatosAlEliminar(): void
    {
        $stmtExistenciaConcepto = $this->createMock(PDOStatement::class);
        $stmtExistenciaConcepto->method('bindValue')->willReturn(true);
        $stmtExistenciaConcepto->method('execute')->willReturn(true);
        $stmtExistenciaConcepto->method('fetchColumn')->willReturn(1);

        $stmtExistenciaCargos = $this->createMock(PDOStatement::class);
        $stmtExistenciaCargos->method('bindValue')->willReturn(true);
        $stmtExistenciaCargos->method('execute')->willReturn(true);
        $stmtExistenciaCargos->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('bindParam')->willReturn(true);
        $stmtDelete->method('execute')->willThrowException(new Exception('Error al eliminar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaConcepto,
            $stmtExistenciaCargos,
            $stmtDelete
        ) {
            if (str_contains($sql, 'FROM `conceptos`')) {
                return $stmtExistenciaConcepto;
            }
            if (str_contains($sql, 'FROM `cargos`')) {
                return $stmtExistenciaCargos;
            }
            return $stmtDelete;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al eliminar', $resultado['codigo']);
    }
}

