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

if (!defined('DUPLICATE_NAME')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitConceptos')]
#[Group('registrarConceptos')]
class RegistrarConceptosTest extends TestCase
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
            'accion'     => 'incluir',
            'nombre'     => 'Mensualidad',
            'monto'      => '40.00',
            'frecuencia' => 'M',
            'dias'       => '5'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Registro exitoso de un nuevo concepto de cobro estándar
    public function testRegistroExitoso(): void
    {
        $stmtExistenciaNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaNombre->method('execute')->willReturn(true);
        $stmtExistenciaNombre->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistenciaNombre, $stmtInsert) {
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaNombre;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: Nombre de concepto de cobro duplicado
    public function testNombreDuplicado(): void
    {
        $stmtExistenciaNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaNombre->method('execute')->willReturn(true);
        $stmtExistenciaNombre->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExistenciaNombre);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);
    }

    // Caso 3: Registro de concepto tipo multa ('T') cuando ya existe una multa activa
    public function testMultaExistente(): void
    {
        $stmtExistenciaNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaNombre->method('execute')->willReturn(true);
        $stmtExistenciaNombre->method('fetchColumn')->willReturn(0);

        $stmtExistenciaMulta = $this->createMock(PDOStatement::class);
        $stmtExistenciaMulta->method('bindValue')->willReturn(true);
        $stmtExistenciaMulta->method('execute')->willReturn(true);
        $stmtExistenciaMulta->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistenciaNombre, $stmtExistenciaMulta) {
            if (str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaNombre;
            }
            return $stmtExistenciaMulta;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'nombre'     => 'Multa por Atraso',
            'frecuencia' => 'T'
        ]));

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('MULTA_EXISTENTE', $resultado['codigo']);
    }

    // Caso 4: Registro exitoso de concepto tipo multa ('T') sin multa previa activa
    public function testRegistroMultaExitoso(): void
    {
        $stmtExistenciaNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaNombre->method('execute')->willReturn(true);
        $stmtExistenciaNombre->method('fetchColumn')->willReturn(0);

        $stmtExistenciaMulta = $this->createMock(PDOStatement::class);
        $stmtExistenciaMulta->method('bindValue')->willReturn(true);
        $stmtExistenciaMulta->method('execute')->willReturn(true);
        $stmtExistenciaMulta->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaNombre,
            $stmtExistenciaMulta,
            $stmtInsert
        ) {
            if (str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaNombre;
            }
            if (str_contains($sql, 'frecuencia = :valor')) {
                return $stmtExistenciaMulta;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'nombre'     => 'Recargo Administrativo',
            'frecuencia' => 'T'
        ]));

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 5: Error de base de datos durante la inserción
    public function testErrorBaseDatosAlInsertar(): void
    {
        $stmtExistenciaNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaNombre->method('execute')->willReturn(true);
        $stmtExistenciaNombre->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willThrowException(new Exception('Error de inserción'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistenciaNombre, $stmtInsert) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExistenciaNombre;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de inserción', $resultado['codigo']);
    }
}

