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
#[Group('modificarMetodosPago')]
class ModificarMetodosPagoTest extends TestCase
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
            'accion'         => 'modificar',
            'id'             => 2,
            'nombre'         => 'Pago Móvil BDV',
            'nec_referencia' => '1'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Modificación exitosa conservando el mismo nombre propio
    public function testModificarExitosoMismoNombre(): void
    {
        $stmtExistenciaPropia = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropia->method('bindValue')->willReturn(true);
        $stmtExistenciaPropia->method('execute')->willReturn(true);
        $stmtExistenciaPropia->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistenciaPropia, $stmtUpdate) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExistenciaPropia;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: Modificación exitosa con un nuevo nombre disponible
    public function testModificarExitosoNuevoNombre(): void
    {
        $stmtExistenciaPropia = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropia->method('bindValue')->willReturn(true);
        $stmtExistenciaPropia->method('execute')->willReturn(true);
        $stmtExistenciaPropia->method('fetchColumn')->willReturn(0);

        $stmtExistenciaGeneral = $this->createMock(PDOStatement::class);
        $stmtExistenciaGeneral->method('bindValue')->willReturn(true);
        $stmtExistenciaGeneral->method('execute')->willReturn(true);
        $stmtExistenciaGeneral->method('fetchColumn')->willReturn(0);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaPropia,
            $stmtExistenciaGeneral,
            $stmtUpdate
        ) {
            if (str_contains($sql, 'nombre = :valor') && str_contains($sql, 'codigo_metodo = :id')) {
                return $stmtExistenciaPropia;
            }
            if (str_contains($sql, 'SELECT COUNT(*) FROM `metodos_pago`') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaGeneral;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'nombre' => 'Transferencia Banesco'
        ]));

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 3: Nombre duplicado perteneciente a otro método de pago
    public function testNombreDuplicadoEnOtroMetodo(): void
    {
        $stmtExistenciaPropia = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropia->method('bindValue')->willReturn(true);
        $stmtExistenciaPropia->method('execute')->willReturn(true);
        $stmtExistenciaPropia->method('fetchColumn')->willReturn(0);

        $stmtExistenciaGeneral = $this->createMock(PDOStatement::class);
        $stmtExistenciaGeneral->method('bindValue')->willReturn(true);
        $stmtExistenciaGeneral->method('execute')->willReturn(true);
        $stmtExistenciaGeneral->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistenciaPropia, $stmtExistenciaGeneral) {
            if (str_contains($sql, 'codigo_metodo = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaPropia;
            }
            return $stmtExistenciaGeneral;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'nombre' => 'Efectivo Dólares'
        ]));

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);
    }

    // Caso 4: Error de base de datos durante la actualización
    public function testErrorBaseDatosAlModificar(): void
    {
        $stmtExistenciaPropia = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropia->method('bindValue')->willReturn(true);
        $stmtExistenciaPropia->method('execute')->willReturn(true);
        $stmtExistenciaPropia->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willThrowException(new Exception('Error al actualizar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistenciaPropia, $stmtUpdate) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExistenciaPropia;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMetodosPago();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al actualizar', $resultado['codigo']);
    }
}
