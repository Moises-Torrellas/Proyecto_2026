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
#[Group('seleccionarMonedaBase')]
class SeleccionarMonedaBaseTest extends TestCase
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
            'accion' => 'select',
            'id'     => 2
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Cambio exitoso de la moneda base del sistema
    public function testSeleccionarMonedaBaseExitoso(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->with($this->callback(function (array $params) {
            return ($params[':id_case'] ?? null) === 2 && ($params[':id_where'] ?? null) === 2;
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

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: La moneda a seleccionar como base no existe
    public function testMonedaNoExiste(): void
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

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 3: Error de base de datos durante la actualización de la moneda base
    public function testErrorBaseDatos(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willThrowException(new Exception('Error al actualizar moneda base'));

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

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al actualizar moneda base', $resultado['codigo']);
    }
}

