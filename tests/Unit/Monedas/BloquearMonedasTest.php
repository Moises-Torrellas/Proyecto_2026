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
#[Group('bloquearMonedas')]
class BloquearMonedasTest extends TestCase
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
            'accion'  => 'bloquear',
            'id'      => 4,
            'bloqueo' => 1
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Bloqueo exitoso cambiando estatus a inactivo (2)
    public function testBloquearExitoso(): void
    {
        $stmtDatosMoneda = $this->createMock(PDOStatement::class);
        $stmtDatosMoneda->method('execute')->willReturn(true);
        $stmtDatosMoneda->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['base' => 2, 'estatus' => 1]);

        $stmtActivas = $this->createMock(PDOStatement::class);
        $stmtActivas->method('fetchColumn')->willReturn(3);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->with($this->callback(function (array $params) {
            return ($params[':estado'] ?? null) === 2 && ($params[':id'] ?? null) === 4;
        }))->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtDatosMoneda, $stmtUpdate) {
            if (str_contains($sql, 'SELECT base, estatus FROM monedas')) {
                return $stmtDatosMoneda;
            }
            return $stmtUpdate;
        });
        $pdoMock->method('query')->willReturn($stmtActivas);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: Desbloqueo exitoso cambiando estatus a activo (1)
    public function testDesbloquearExitoso(): void
    {
        $stmtDatosMoneda = $this->createMock(PDOStatement::class);
        $stmtDatosMoneda->method('execute')->willReturn(true);
        $stmtDatosMoneda->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['base' => 2, 'estatus' => 2]);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->with($this->callback(function (array $params) {
            return ($params[':estado'] ?? null) === 1 && ($params[':id'] ?? null) === 4;
        }))->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtDatosMoneda, $stmtUpdate) {
            if (str_contains($sql, 'SELECT base, estatus FROM monedas')) {
                return $stmtDatosMoneda;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'bloqueo' => 2
        ]));

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 3: La moneda a bloquear no existe
    public function testMonedaNoExiste(): void
    {
        $stmtDatosMoneda = $this->createMock(PDOStatement::class);
        $stmtDatosMoneda->method('execute')->willReturn(true);
        $stmtDatosMoneda->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtDatosMoneda);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 4: No se permite bloquear la moneda base del sistema
    public function testBloquearMonedaBaseNoPermitido(): void
    {
        $stmtDatosMoneda = $this->createMock(PDOStatement::class);
        $stmtDatosMoneda->method('execute')->willReturn(true);
        $stmtDatosMoneda->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['base' => 1, 'estatus' => 1]); // Es base

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtDatosMoneda);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION, $resultado['codigo']);
    }

    // Caso 5: No se permite bloquear si solo quedan 2 monedas activas
    public function testBloquearConMinimoActivasNoPermitido(): void
    {
        $stmtDatosMoneda = $this->createMock(PDOStatement::class);
        $stmtDatosMoneda->method('execute')->willReturn(true);
        $stmtDatosMoneda->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['base' => 2, 'estatus' => 1]);

        $stmtActivas = $this->createMock(PDOStatement::class);
        $stmtActivas->method('fetchColumn')->willReturn(2); // <= 2 activas

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtDatosMoneda);
        $pdoMock->method('query')->willReturn($stmtActivas);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION . '2', $resultado['codigo']);
    }

    // Caso 6: Error en base de datos al actualizar el estatus
    public function testErrorBaseDatosAlBloquear(): void
    {
        $stmtDatosMoneda = $this->createMock(PDOStatement::class);
        $stmtDatosMoneda->method('execute')->willReturn(true);
        $stmtDatosMoneda->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['base' => 2, 'estatus' => 1]);

        $stmtActivas = $this->createMock(PDOStatement::class);
        $stmtActivas->method('fetchColumn')->willReturn(3);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willThrowException(new Exception('Error al actualizar estatus'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtDatosMoneda, $stmtUpdate) {
            if (str_contains($sql, 'SELECT base, estatus FROM monedas')) {
                return $stmtDatosMoneda;
            }
            return $stmtUpdate;
        });
        $pdoMock->method('query')->willReturn($stmtActivas);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al actualizar estatus', $resultado['codigo']);
    }
}

