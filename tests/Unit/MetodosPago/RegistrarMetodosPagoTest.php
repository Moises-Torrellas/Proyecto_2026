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

if (!defined('DUPLICATE_NAME')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitMetodosPago')]
#[Group('registrarMetodosPago')]
class RegistrarMetodosPagoTest extends TestCase
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
            'accion'         => 'incluir',
            'nombre'         => 'Transferencia Bancaria',
            'nec_referencia' => '1'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Registro exitoso de un nuevo método de pago
    public function testRegistroExitoso(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistencia, $stmtInsert) {
            if (str_contains($sql, 'SELECT COUNT(*) FROM `metodos_pago`') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistencia;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: Nombre de método de pago duplicado
    public function testNombreDuplicado(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExistencia);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);
    }

    // Caso 3: Error de base de datos durante la inserción
    public function testErrorBaseDatosAlInsertar(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willThrowException(new Exception('Error de inserción'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistencia, $stmtInsert) {
            if (str_contains($sql, 'SELECT COUNT(*) FROM `metodos_pago`')) {
                return $stmtExistencia;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de inserción', $resultado['codigo']);
    }
}

