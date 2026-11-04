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

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitMetodosPago')]
#[Group('eliminarMetodosPago')]
class EliminarMetodosPagoTest extends TestCase
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
            'id'     => 3
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Eliminación física exitosa del método de pago
    public function testEliminarExitoso(): void
    {
        $stmtExistenciaMetodo = $this->createMock(PDOStatement::class);
        $stmtExistenciaMetodo->method('bindValue')->willReturn(true);
        $stmtExistenciaMetodo->method('execute')->willReturn(true);
        $stmtExistenciaMetodo->method('fetchColumn')->willReturn(1);

        $stmtExistenciaPagos = $this->createMock(PDOStatement::class);
        $stmtExistenciaPagos->method('bindValue')->willReturn(true);
        $stmtExistenciaPagos->method('execute')->willReturn(true);
        $stmtExistenciaPagos->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('bindParam')->willReturn(true);
        $stmtDelete->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaMetodo,
            $stmtExistenciaPagos,
            $stmtDelete
        ) {
            if (str_contains($sql, 'FROM `metodos_pago`')) {
                return $stmtExistenciaMetodo;
            }
            if (str_contains($sql, 'FROM `pagos`')) {
                return $stmtExistenciaPagos;
            }
            return $stmtDelete;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: El método de pago no existe en el sistema
    public function testMetodoNoExiste(): void
    {
        $stmtExistenciaMetodo = $this->createMock(PDOStatement::class);
        $stmtExistenciaMetodo->method('bindValue')->willReturn(true);
        $stmtExistenciaMetodo->method('execute')->willReturn(true);
        $stmtExistenciaMetodo->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExistenciaMetodo);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 3: El método de pago tiene pagos asociados y no puede eliminarse
    public function testMetodoConPagosAsociados(): void
    {
        $stmtExistenciaMetodo = $this->createMock(PDOStatement::class);
        $stmtExistenciaMetodo->method('bindValue')->willReturn(true);
        $stmtExistenciaMetodo->method('execute')->willReturn(true);
        $stmtExistenciaMetodo->method('fetchColumn')->willReturn(1);

        $stmtExistenciaPagos = $this->createMock(PDOStatement::class);
        $stmtExistenciaPagos->method('bindValue')->willReturn(true);
        $stmtExistenciaPagos->method('execute')->willReturn(true);
        $stmtExistenciaPagos->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaMetodo,
            $stmtExistenciaPagos
        ) {
            if (str_contains($sql, 'FROM `metodos_pago`')) {
                return $stmtExistenciaMetodo;
            }
            return $stmtExistenciaPagos;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo']);
    }

    // Caso 4: Error en base de datos al ejecutar el DELETE
    public function testErrorBaseDatosAlEliminar(): void
    {
        $stmtExistenciaMetodo = $this->createMock(PDOStatement::class);
        $stmtExistenciaMetodo->method('bindValue')->willReturn(true);
        $stmtExistenciaMetodo->method('execute')->willReturn(true);
        $stmtExistenciaMetodo->method('fetchColumn')->willReturn(1);

        $stmtExistenciaPagos = $this->createMock(PDOStatement::class);
        $stmtExistenciaPagos->method('bindValue')->willReturn(true);
        $stmtExistenciaPagos->method('execute')->willReturn(true);
        $stmtExistenciaPagos->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('bindParam')->willReturn(true);
        $stmtDelete->method('execute')->willThrowException(new Exception('Error al eliminar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaMetodo,
            $stmtExistenciaPagos,
            $stmtDelete
        ) {
            if (str_contains($sql, 'FROM `metodos_pago`')) {
                return $stmtExistenciaMetodo;
            }
            if (str_contains($sql, 'FROM `pagos`')) {
                return $stmtExistenciaPagos;
            }
            return $stmtDelete;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al eliminar', $resultado['codigo']);
    }
}

