<?php

namespace tests\Unit\Monedas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloMonedas;
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
#[Group('unitMonedas')]
#[Group('eliminarMonedas')]
class EliminarMonedasTest extends TestCase
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

    // Caso 1: Eliminación física exitosa de una moneda secundaria
    public function testEliminarExitoso(): void
    {
        $stmtSinAsociados = $this->createMock(PDOStatement::class);
        $stmtSinAsociados->method('bindValue')->willReturn(true);
        $stmtSinAsociados->method('execute')->willReturn(true);
        $stmtSinAsociados->method('fetchColumn')->willReturn(0);

        $stmtDatosMoneda = $this->createMock(PDOStatement::class);
        $stmtDatosMoneda->method('execute')->willReturn(true);
        $stmtDatosMoneda->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['base' => 2, 'estatus' => 1]);

        $stmtTotalMonedas = $this->createMock(PDOStatement::class);
        $stmtTotalMonedas->method('fetchColumn')->willReturn(4);

        $stmtMonedasActivas = $this->createMock(PDOStatement::class);
        $stmtMonedasActivas->method('fetchColumn')->willReturn(3);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('bindParam')->willReturn(true);
        $stmtDelete->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtSinAsociados,
            $stmtDatosMoneda,
            $stmtDelete
        ) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtSinAsociados;
            }
            if (str_contains($sql, 'SELECT base, estatus FROM monedas')) {
                return $stmtDatosMoneda;
            }
            return $stmtDelete;
        });

        $pdoMock->method('query')->willReturnCallback(function (string $sql) use (
            $stmtTotalMonedas,
            $stmtMonedasActivas
        ) {
            if (str_contains($sql, 'WHERE estatus = 1')) {
                return $stmtMonedasActivas;
            }
            return $stmtTotalMonedas;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: La moneda tiene pagos asociados
    public function testMonedaConPagosAsociados(): void
    {
        $stmtAsociados = $this->createMock(PDOStatement::class);
        $stmtAsociados->method('bindValue')->willReturn(true);
        $stmtAsociados->method('execute')->willReturn(true);
        $stmtAsociados->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtAsociados);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo']);
    }

    // Caso 3: La moneda tiene vueltos asociados
    public function testMonedaConVueltosAsociados(): void
    {
        $stmtPagos = $this->createMock(PDOStatement::class);
        $stmtPagos->method('bindValue')->willReturn(true);
        $stmtPagos->method('execute')->willReturn(true);
        $stmtPagos->method('fetchColumn')->willReturn(0);

        $stmtVueltos = $this->createMock(PDOStatement::class);
        $stmtVueltos->method('bindValue')->willReturn(true);
        $stmtVueltos->method('execute')->willReturn(true);
        $stmtVueltos->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPagos, $stmtVueltos) {
            if (str_contains($sql, 'FROM `pagos`')) {
                return $stmtPagos;
            }
            return $stmtVueltos;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES . '2', $resultado['codigo']);
    }

    // Caso 4: La moneda tiene tasas de cambio asociadas
    public function testMonedaConTasasAsociadas(): void
    {
        $stmtSinAsociados = $this->createMock(PDOStatement::class);
        $stmtSinAsociados->method('bindValue')->willReturn(true);
        $stmtSinAsociados->method('execute')->willReturn(true);
        $stmtSinAsociados->method('fetchColumn')->willReturn(0);

        $stmtTasas = $this->createMock(PDOStatement::class);
        $stmtTasas->method('bindValue')->willReturn(true);
        $stmtTasas->method('execute')->willReturn(true);
        $stmtTasas->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtSinAsociados, $stmtTasas) {
            if (str_contains($sql, 'FROM `tasa_cambios`')) {
                return $stmtTasas;
            }
            return $stmtSinAsociados;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES . '3', $resultado['codigo']);
    }

    // Caso 5: La moneda a eliminar no existe en la base de datos
    public function testMonedaNoExiste(): void
    {
        $stmtSinAsociados = $this->createMock(PDOStatement::class);
        $stmtSinAsociados->method('bindValue')->willReturn(true);
        $stmtSinAsociados->method('execute')->willReturn(true);
        $stmtSinAsociados->method('fetchColumn')->willReturn(0);

        $stmtDatosMoneda = $this->createMock(PDOStatement::class);
        $stmtDatosMoneda->method('execute')->willReturn(true);
        $stmtDatosMoneda->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false); // No existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtSinAsociados, $stmtDatosMoneda) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtSinAsociados;
            }
            return $stmtDatosMoneda;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 6: No se permite eliminar la moneda base del sistema
    public function testMonedaEsBaseNoSePuedeEliminar(): void
    {
        $stmtSinAsociados = $this->createMock(PDOStatement::class);
        $stmtSinAsociados->method('bindValue')->willReturn(true);
        $stmtSinAsociados->method('execute')->willReturn(true);
        $stmtSinAsociados->method('fetchColumn')->willReturn(0);

        $stmtDatosMoneda = $this->createMock(PDOStatement::class);
        $stmtDatosMoneda->method('execute')->willReturn(true);
        $stmtDatosMoneda->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['base' => 1, 'estatus' => 1]); // Es base

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtSinAsociados, $stmtDatosMoneda) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtSinAsociados;
            }
            return $stmtDatosMoneda;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION, $resultado['codigo']);
    }

    // Caso 7: Límite mínimo de monedas totales alcanzado (máximo permitido eliminar hasta 2)
    public function testLimiteMinimoTotalMonedas(): void
    {
        $stmtSinAsociados = $this->createMock(PDOStatement::class);
        $stmtSinAsociados->method('bindValue')->willReturn(true);
        $stmtSinAsociados->method('execute')->willReturn(true);
        $stmtSinAsociados->method('fetchColumn')->willReturn(0);

        $stmtDatosMoneda = $this->createMock(PDOStatement::class);
        $stmtDatosMoneda->method('execute')->willReturn(true);
        $stmtDatosMoneda->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['base' => 2, 'estatus' => 1]);

        $stmtTotalMonedas = $this->createMock(PDOStatement::class);
        $stmtTotalMonedas->method('fetchColumn')->willReturn(2); // Total <= 2

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtSinAsociados, $stmtDatosMoneda) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtSinAsociados;
            }
            return $stmtDatosMoneda;
        });

        $pdoMock->method('query')->willReturn($stmtTotalMonedas);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION . '2', $resultado['codigo']);
    }

    // Caso 8: Límite mínimo de monedas activas alcanzado
    public function testLimiteMinimoMonedasActivas(): void
    {
        $stmtSinAsociados = $this->createMock(PDOStatement::class);
        $stmtSinAsociados->method('bindValue')->willReturn(true);
        $stmtSinAsociados->method('execute')->willReturn(true);
        $stmtSinAsociados->method('fetchColumn')->willReturn(0);

        $stmtDatosMoneda = $this->createMock(PDOStatement::class);
        $stmtDatosMoneda->method('execute')->willReturn(true);
        $stmtDatosMoneda->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['base' => 2, 'estatus' => 1]);

        $stmtTotalMonedas = $this->createMock(PDOStatement::class);
        $stmtTotalMonedas->method('fetchColumn')->willReturn(4);

        $stmtMonedasActivas = $this->createMock(PDOStatement::class);
        $stmtMonedasActivas->method('fetchColumn')->willReturn(2); // Activas <= 2

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtSinAsociados, $stmtDatosMoneda) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtSinAsociados;
            }
            return $stmtDatosMoneda;
        });

        $pdoMock->method('query')->willReturnCallback(function (string $sql) use ($stmtTotalMonedas, $stmtMonedasActivas) {
            if (str_contains($sql, 'WHERE estatus = 1')) {
                return $stmtMonedasActivas;
            }
            return $stmtTotalMonedas;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION . '3', $resultado['codigo']);
    }

    // Caso 9: Error en base de datos al ejecutar DELETE
    public function testErrorBaseDatosAlEliminar(): void
    {
        $stmtSinAsociados = $this->createMock(PDOStatement::class);
        $stmtSinAsociados->method('bindValue')->willReturn(true);
        $stmtSinAsociados->method('execute')->willReturn(true);
        $stmtSinAsociados->method('fetchColumn')->willReturn(0);

        $stmtDatosMoneda = $this->createMock(PDOStatement::class);
        $stmtDatosMoneda->method('execute')->willReturn(true);
        $stmtDatosMoneda->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['base' => 2, 'estatus' => 1]);

        $stmtTotalMonedas = $this->createMock(PDOStatement::class);
        $stmtTotalMonedas->method('fetchColumn')->willReturn(4);

        $stmtMonedasActivas = $this->createMock(PDOStatement::class);
        $stmtMonedasActivas->method('fetchColumn')->willReturn(3);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('bindParam')->willReturn(true);
        $stmtDelete->method('execute')->willThrowException(new Exception('Error al eliminar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtSinAsociados,
            $stmtDatosMoneda,
            $stmtDelete
        ) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtSinAsociados;
            }
            if (str_contains($sql, 'SELECT base, estatus FROM monedas')) {
                return $stmtDatosMoneda;
            }
            return $stmtDelete;
        });

        $pdoMock->method('query')->willReturnCallback(function (string $sql) use ($stmtTotalMonedas, $stmtMonedasActivas) {
            if (str_contains($sql, 'WHERE estatus = 1')) {
                return $stmtMonedasActivas;
            }
            return $stmtTotalMonedas;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al eliminar', $resultado['codigo']);
    }
}

