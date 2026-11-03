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
#[Group('modificarConceptos')]
class ModificarConceptosTest extends TestCase
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
            'accion'     => 'modificar',
            'id'         => '3',
            'nombre'     => 'Mensualidad Abril',
            'monto'      => '45.00',
            'frecuencia' => 'M',
            'dias'       => '5'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Modificación exitosa conservando el mismo nombre del concepto
    public function testModificarExitosoMismoNombre(): void
    {
        $stmtExistenciaId = $this->createMock(PDOStatement::class);
        $stmtExistenciaId->method('bindValue')->willReturn(true);
        $stmtExistenciaId->method('execute')->willReturn(true);
        $stmtExistenciaId->method('fetchColumn')->willReturn(1);

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
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaId,
            $stmtExistenciaPropia,
            $stmtUpdate
        ) {
            if (str_contains($sql, 'codigo_concepto = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaPropia;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'codigo_concepto = :valor')) {
                return $stmtExistenciaId;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: Modificación exitosa cambiando el nombre por uno nuevo disponible
    public function testModificarExitosoNuevoNombre(): void
    {
        $stmtExistenciaId = $this->createMock(PDOStatement::class);
        $stmtExistenciaId->method('bindValue')->willReturn(true);
        $stmtExistenciaId->method('execute')->willReturn(true);
        $stmtExistenciaId->method('fetchColumn')->willReturn(1);

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
            $stmtExistenciaId,
            $stmtExistenciaPropia,
            $stmtExistenciaGeneral,
            $stmtUpdate
        ) {
            if (str_contains($sql, 'codigo_concepto = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaPropia;
            }
            if (str_contains($sql, 'codigo_concepto = :valor')) {
                return $stmtExistenciaId;
            }
            if (str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaGeneral;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'nombre' => 'Matrícula Nueva'
        ]));

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 3: El concepto a modificar no existe
    public function testConceptoNoExiste(): void
    {
        $stmtExistenciaId = $this->createMock(PDOStatement::class);
        $stmtExistenciaId->method('bindValue')->willReturn(true);
        $stmtExistenciaId->method('execute')->willReturn(true);
        $stmtExistenciaId->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExistenciaId);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 4: Nombre duplicado perteneciente a otro concepto
    public function testNombreDuplicadoEnOtroConcepto(): void
    {
        $stmtExistenciaId = $this->createMock(PDOStatement::class);
        $stmtExistenciaId->method('bindValue')->willReturn(true);
        $stmtExistenciaId->method('execute')->willReturn(true);
        $stmtExistenciaId->method('fetchColumn')->willReturn(1);

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
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaId,
            $stmtExistenciaPropia,
            $stmtExistenciaGeneral
        ) {
            if (str_contains($sql, 'codigo_concepto = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaPropia;
            }
            if (str_contains($sql, 'codigo_concepto = :valor')) {
                return $stmtExistenciaId;
            }
            return $stmtExistenciaGeneral;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'nombre' => 'Inscripción General'
        ]));

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);
    }

    // Caso 5: Modificar a frecuencia multa ('T') cuando ya existe otra multa activa
    public function testMultaExistenteEnOtroConcepto(): void
    {
        $stmtExistenciaId = $this->createMock(PDOStatement::class);
        $stmtExistenciaId->method('bindValue')->willReturn(true);
        $stmtExistenciaId->method('execute')->willReturn(true);
        $stmtExistenciaId->method('fetchColumn')->willReturn(1);

        $stmtExistenciaPropiaNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropiaNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaPropiaNombre->method('execute')->willReturn(true);
        $stmtExistenciaPropiaNombre->method('fetchColumn')->willReturn(1);

        $stmtExistenciaMulta = $this->createMock(PDOStatement::class);
        $stmtExistenciaMulta->method('bindValue')->willReturn(true);
        $stmtExistenciaMulta->method('execute')->willReturn(true);
        $stmtExistenciaMulta->method('fetchColumn')->willReturn(1); // Existe multa activa

        $stmtExistenciaPropiaMulta = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropiaMulta->method('bindValue')->willReturn(true);
        $stmtExistenciaPropiaMulta->method('execute')->willReturn(true);
        $stmtExistenciaPropiaMulta->method('fetchColumn')->willReturn(0); // No es la multa de este concepto

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaId,
            $stmtExistenciaPropiaNombre,
            $stmtExistenciaMulta,
            $stmtExistenciaPropiaMulta
        ) {
            if (str_contains($sql, 'codigo_concepto = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaPropiaNombre;
            }
            if (str_contains($sql, 'codigo_concepto = :valor')) {
                return $stmtExistenciaId;
            }
            if (str_contains($sql, 'codigo_concepto = :id') && str_contains($sql, 'frecuencia = :valor')) {
                return $stmtExistenciaPropiaMulta;
            }
            return $stmtExistenciaMulta;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'frecuencia' => 'T'
        ]));

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('MULTA_EXISTENTE', $resultado['codigo']);
    }

    // Caso 6: Modificar datos de una multa propia existente ('T') con éxito
    public function testModificarMultaPropiaExitoso(): void
    {
        $stmtExistenciaId = $this->createMock(PDOStatement::class);
        $stmtExistenciaId->method('bindValue')->willReturn(true);
        $stmtExistenciaId->method('execute')->willReturn(true);
        $stmtExistenciaId->method('fetchColumn')->willReturn(1);

        $stmtExistenciaPropiaNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropiaNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaPropiaNombre->method('execute')->willReturn(true);
        $stmtExistenciaPropiaNombre->method('fetchColumn')->willReturn(1);

        $stmtExistenciaMulta = $this->createMock(PDOStatement::class);
        $stmtExistenciaMulta->method('bindValue')->willReturn(true);
        $stmtExistenciaMulta->method('execute')->willReturn(true);
        $stmtExistenciaMulta->method('fetchColumn')->willReturn(1);

        $stmtExistenciaPropiaMulta = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropiaMulta->method('bindValue')->willReturn(true);
        $stmtExistenciaPropiaMulta->method('execute')->willReturn(true);
        $stmtExistenciaPropiaMulta->method('fetchColumn')->willReturn(1); // Es la misma multa propia

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaId,
            $stmtExistenciaPropiaNombre,
            $stmtExistenciaMulta,
            $stmtExistenciaPropiaMulta,
            $stmtUpdate
        ) {
            if (str_contains($sql, 'codigo_concepto = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaPropiaNombre;
            }
            if (str_contains($sql, 'codigo_concepto = :valor')) {
                return $stmtExistenciaId;
            }
            if (str_contains($sql, 'codigo_concepto = :id') && str_contains($sql, 'frecuencia = :valor')) {
                return $stmtExistenciaPropiaMulta;
            }
            if (str_contains($sql, 'frecuencia = :valor')) {
                return $stmtExistenciaMulta;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'frecuencia' => 'T'
        ]));

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 7: Error de base de datos durante la actualización
    public function testErrorBaseDatosAlModificar(): void
    {
        $stmtExistenciaId = $this->createMock(PDOStatement::class);
        $stmtExistenciaId->method('bindValue')->willReturn(true);
        $stmtExistenciaId->method('execute')->willReturn(true);
        $stmtExistenciaId->method('fetchColumn')->willReturn(1);

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
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaId,
            $stmtExistenciaPropia,
            $stmtUpdate
        ) {
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaPropia;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'codigo_concepto = :valor')) {
                return $stmtExistenciaId;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloConceptos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al actualizar', $resultado['codigo']);
    }
}
