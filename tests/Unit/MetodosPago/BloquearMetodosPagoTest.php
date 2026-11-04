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
#[Group('bloquearMetodosPago')]
class BloquearMetodosPagoTest extends TestCase
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
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->with($this->callback(function (array $params) {
            return ($params[':estado'] ?? null) === 2 && ($params[':id'] ?? null) === 4;
        }))->willReturn(true);

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

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: Desbloqueo exitoso cambiando estatus a activo (1)
    public function testDesbloquearExitoso(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->with($this->callback(function (array $params) {
            return ($params[':estado'] ?? null) === 1 && ($params[':id'] ?? null) === 4;
        }))->willReturn(true);

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

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'bloqueo' => 2
        ]));

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 3: El método de pago a bloquear/desbloquear no existe
    public function testMetodoNoExiste(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExistencia);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 4: Error en base de datos al actualizar el estado
    public function testErrorBaseDatosAlBloquear(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willThrowException(new Exception('Error al actualizar estatus'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistencia, $stmtUpdate) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExistencia;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al actualizar estatus', $resultado['codigo']);
    }
}

