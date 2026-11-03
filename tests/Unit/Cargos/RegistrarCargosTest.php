<?php

namespace tests\Unit\Cargos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCargos;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitCargos')]
#[Group('registrarCargos')]
class RegistrarCargosTest extends TestCase
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
            'accion'        => 'incluir',
            'id_concepto'   => 1,
            'id_atleta'     => [10],
            'monto_total'   => 150.00,
            'fecha_emision' => '2026-03-15'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Registro exitoso con un solo atleta
    public function testRegistroExitoso(): void
    {
        $stmtExistenciaConcepto = $this->createMock(PDOStatement::class);
        $stmtExistenciaConcepto->method('bindValue')->willReturn(true);
        $stmtExistenciaConcepto->method('execute')->willReturn(true);
        $stmtExistenciaConcepto->method('fetchColumn')->willReturn(1);

        $stmtMonedaBase = $this->createMock(PDOStatement::class);
        $stmtMonedaBase->method('execute')->willReturn(true);
        $stmtMonedaBase->method('fetchColumn')->willReturn(1);

        $stmtExistenciaAtleta = $this->createMock(PDOStatement::class);
        $stmtExistenciaAtleta->method('bindValue')->willReturn(true);
        $stmtExistenciaAtleta->method('execute')->willReturn(true);
        $stmtExistenciaAtleta->method('fetchColumn')->willReturn(1);

        $stmtFrecuencia = $this->createMock(PDOStatement::class);
        $stmtFrecuencia->method('bindParam')->willReturn(true);
        $stmtFrecuencia->method('execute')->willReturn(true);
        $stmtFrecuencia->method('fetch')->willReturn(['colisiones' => 0]);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindValue')->willReturn(true);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $stmtNombreConcepto = $this->createMock(PDOStatement::class);
        $stmtNombreConcepto->method('bindParam')->willReturn(true);
        $stmtNombreConcepto->method('execute')->willReturn(true);
        $stmtNombreConcepto->method('fetchColumn')->willReturn('Mensualidad Marzo');

        $stmtNombreAtleta = $this->createMock(PDOStatement::class);
        $stmtNombreAtleta->method('bindParam')->willReturn(true);
        $stmtNombreAtleta->method('execute')->willReturn(true);
        $stmtNombreAtleta->method('fetch')->willReturn(['p_nombre' => 'Carlos', 'p_apellidos' => 'Pérez']);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaConcepto,
            $stmtMonedaBase,
            $stmtExistenciaAtleta,
            $stmtFrecuencia,
            $stmtInsert,
            $stmtNombreConcepto,
            $stmtNombreAtleta
        ) {
            if (str_contains($sql, 'FROM `conceptos`') && str_contains($sql, 'codigo_concepto = :valor')) {
                return $stmtExistenciaConcepto;
            }
            if (str_contains($sql, 'FROM monedas')) {
                return $stmtMonedaBase;
            }
            if (str_contains($sql, 'FROM `atletas`') && str_contains($sql, 'codigo_atleta = :valor')) {
                return $stmtExistenciaAtleta;
            }
            if (str_contains($sql, 'colisiones')) {
                return $stmtFrecuencia;
            }
            if (str_contains($sql, 'INSERT INTO cargos')) {
                return $stmtInsert;
            }
            if (str_contains($sql, 'SELECT nombre FROM conceptos')) {
                return $stmtNombreConcepto;
            }
            if (str_contains($sql, 'SELECT p_nombre, p_apellidos FROM atletas')) {
                return $stmtNombreAtleta;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertStringContainsString('Carlos Pérez', $resultado['desc_bitacora']);
        $this->assertStringContainsString('Mensualidad Marzo', $resultado['desc_bitacora']);
    }

    // Caso 2: Registro exitoso con múltiples atletas
    public function testRegistroMultiplesAtletas(): void
    {
        $stmtExistenciaConcepto = $this->createMock(PDOStatement::class);
        $stmtExistenciaConcepto->method('bindValue')->willReturn(true);
        $stmtExistenciaConcepto->method('execute')->willReturn(true);
        $stmtExistenciaConcepto->method('fetchColumn')->willReturn(1);

        $stmtMonedaBase = $this->createMock(PDOStatement::class);
        $stmtMonedaBase->method('execute')->willReturn(true);
        $stmtMonedaBase->method('fetchColumn')->willReturn(1);

        $stmtExistenciaAtleta = $this->createMock(PDOStatement::class);
        $stmtExistenciaAtleta->method('bindValue')->willReturn(true);
        $stmtExistenciaAtleta->method('execute')->willReturn(true);
        $stmtExistenciaAtleta->method('fetchColumn')->willReturn(1);

        $stmtFrecuencia = $this->createMock(PDOStatement::class);
        $stmtFrecuencia->method('bindParam')->willReturn(true);
        $stmtFrecuencia->method('execute')->willReturn(true);
        $stmtFrecuencia->method('fetch')->willReturn(['colisiones' => 0]);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindValue')->willReturn(true);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $stmtNombreConcepto = $this->createMock(PDOStatement::class);
        $stmtNombreConcepto->method('bindParam')->willReturn(true);
        $stmtNombreConcepto->method('execute')->willReturn(true);
        $stmtNombreConcepto->method('fetchColumn')->willReturn('Torneo Anual');

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaConcepto,
            $stmtMonedaBase,
            $stmtExistenciaAtleta,
            $stmtFrecuencia,
            $stmtInsert,
            $stmtNombreConcepto
        ) {
            if (str_contains($sql, 'FROM `conceptos`') && str_contains($sql, 'codigo_concepto = :valor')) {
                return $stmtExistenciaConcepto;
            }
            if (str_contains($sql, 'FROM monedas')) {
                return $stmtMonedaBase;
            }
            if (str_contains($sql, 'FROM `atletas`') && str_contains($sql, 'codigo_atleta = :valor')) {
                return $stmtExistenciaAtleta;
            }
            if (str_contains($sql, 'colisiones')) {
                return $stmtFrecuencia;
            }
            if (str_contains($sql, 'INSERT INTO cargos')) {
                return $stmtInsert;
            }
            if (str_contains($sql, 'SELECT nombre FROM conceptos')) {
                return $stmtNombreConcepto;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'id_atleta' => [10, 20]
        ]));

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertStringContainsString('2 atleta(s)', $resultado['desc_bitacora']);
    }

    // Caso 3: Concepto de cobro no existe
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

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El concepto de cobro seleccionado no existe.', $resultado['codigo']);
    }

    // Caso 4: Atleta seleccionado no existe o está inactivo
    public function testAtletaNoExiste(): void
    {
        $stmtExistenciaConcepto = $this->createMock(PDOStatement::class);
        $stmtExistenciaConcepto->method('bindValue')->willReturn(true);
        $stmtExistenciaConcepto->method('execute')->willReturn(true);
        $stmtExistenciaConcepto->method('fetchColumn')->willReturn(1);

        $stmtMonedaBase = $this->createMock(PDOStatement::class);
        $stmtMonedaBase->method('execute')->willReturn(true);
        $stmtMonedaBase->method('fetchColumn')->willReturn(1);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindValue')->willReturn(true);

        $stmtExistenciaAtleta = $this->createMock(PDOStatement::class);
        $stmtExistenciaAtleta->method('bindValue')->willReturn(true);
        $stmtExistenciaAtleta->method('execute')->willReturn(true);
        $stmtExistenciaAtleta->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaConcepto,
            $stmtMonedaBase,
            $stmtInsert,
            $stmtExistenciaAtleta
        ) {
            if (str_contains($sql, 'FROM `conceptos`')) {
                return $stmtExistenciaConcepto;
            }
            if (str_contains($sql, 'FROM monedas')) {
                return $stmtMonedaBase;
            }
            if (str_contains($sql, 'INSERT INTO cargos')) {
                return $stmtInsert;
            }
            return $stmtExistenciaAtleta;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El atleta seleccionado no existe o está inactivo.', $resultado['codigo']);
    }

    // Caso 5: Colisión de frecuencia para el atleta en el periodo
    public function testColisionFrecuencia(): void
    {
        $stmtExistenciaConcepto = $this->createMock(PDOStatement::class);
        $stmtExistenciaConcepto->method('bindValue')->willReturn(true);
        $stmtExistenciaConcepto->method('execute')->willReturn(true);
        $stmtExistenciaConcepto->method('fetchColumn')->willReturn(1);

        $stmtMonedaBase = $this->createMock(PDOStatement::class);
        $stmtMonedaBase->method('execute')->willReturn(true);
        $stmtMonedaBase->method('fetchColumn')->willReturn(1);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindValue')->willReturn(true);

        $stmtExistenciaAtleta = $this->createMock(PDOStatement::class);
        $stmtExistenciaAtleta->method('bindValue')->willReturn(true);
        $stmtExistenciaAtleta->method('execute')->willReturn(true);
        $stmtExistenciaAtleta->method('fetchColumn')->willReturn(1);

        $stmtFrecuencia = $this->createMock(PDOStatement::class);
        $stmtFrecuencia->method('bindParam')->willReturn(true);
        $stmtFrecuencia->method('execute')->willReturn(true);
        $stmtFrecuencia->method('fetch')->willReturn(['colisiones' => 1]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaConcepto,
            $stmtMonedaBase,
            $stmtInsert,
            $stmtExistenciaAtleta,
            $stmtFrecuencia
        ) {
            if (str_contains($sql, 'FROM `conceptos`')) {
                return $stmtExistenciaConcepto;
            }
            if (str_contains($sql, 'FROM monedas')) {
                return $stmtMonedaBase;
            }
            if (str_contains($sql, 'INSERT INTO cargos')) {
                return $stmtInsert;
            }
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtExistenciaAtleta;
            }
            return $stmtFrecuencia;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El atleta ya tiene asignado este concepto para el periodo correspondiente.', $resultado['codigo']);
    }

    // Caso 6: Moneda base por defecto cuando no se encuentra en monedas
    public function testMonedaBasePorDefecto(): void
    {
        $stmtExistenciaConcepto = $this->createMock(PDOStatement::class);
        $stmtExistenciaConcepto->method('bindValue')->willReturn(true);
        $stmtExistenciaConcepto->method('execute')->willReturn(true);
        $stmtExistenciaConcepto->method('fetchColumn')->willReturn(1);

        $stmtMonedaBase = $this->createMock(PDOStatement::class);
        $stmtMonedaBase->method('execute')->willReturn(true);
        $stmtMonedaBase->method('fetchColumn')->willReturn(false); // Retorna false, usa default 2

        $stmtExistenciaAtleta = $this->createMock(PDOStatement::class);
        $stmtExistenciaAtleta->method('bindValue')->willReturn(true);
        $stmtExistenciaAtleta->method('execute')->willReturn(true);
        $stmtExistenciaAtleta->method('fetchColumn')->willReturn(1);

        $stmtFrecuencia = $this->createMock(PDOStatement::class);
        $stmtFrecuencia->method('bindParam')->willReturn(true);
        $stmtFrecuencia->method('execute')->willReturn(true);
        $stmtFrecuencia->method('fetch')->willReturn(['colisiones' => 0]);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindValue')->with(':codigo_moneda', 2)->willReturn(true);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $stmtNombreConcepto = $this->createMock(PDOStatement::class);
        $stmtNombreConcepto->method('bindParam')->willReturn(true);
        $stmtNombreConcepto->method('execute')->willReturn(true);
        $stmtNombreConcepto->method('fetchColumn')->willReturn('Concepto Base');

        $stmtNombreAtleta = $this->createMock(PDOStatement::class);
        $stmtNombreAtleta->method('bindParam')->willReturn(true);
        $stmtNombreAtleta->method('execute')->willReturn(true);
        $stmtNombreAtleta->method('fetch')->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaConcepto,
            $stmtMonedaBase,
            $stmtExistenciaAtleta,
            $stmtFrecuencia,
            $stmtInsert,
            $stmtNombreConcepto,
            $stmtNombreAtleta
        ) {
            if (str_contains($sql, 'FROM `conceptos`') && str_contains($sql, 'codigo_concepto = :valor')) {
                return $stmtExistenciaConcepto;
            }
            if (str_contains($sql, 'FROM monedas')) {
                return $stmtMonedaBase;
            }
            if (str_contains($sql, 'FROM `atletas`') && str_contains($sql, 'codigo_atleta = :valor')) {
                return $stmtExistenciaAtleta;
            }
            if (str_contains($sql, 'colisiones')) {
                return $stmtFrecuencia;
            }
            if (str_contains($sql, 'INSERT INTO cargos')) {
                return $stmtInsert;
            }
            if (str_contains($sql, 'SELECT nombre FROM conceptos')) {
                return $stmtNombreConcepto;
            }
            return $stmtNombreAtleta;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 7: Error en base de datos al ejecutar la inserción
    public function testErrorBaseDatosAlInsertar(): void
    {
        $stmtExistenciaConcepto = $this->createMock(PDOStatement::class);
        $stmtExistenciaConcepto->method('bindValue')->willReturn(true);
        $stmtExistenciaConcepto->method('execute')->willReturn(true);
        $stmtExistenciaConcepto->method('fetchColumn')->willReturn(1);

        $stmtMonedaBase = $this->createMock(PDOStatement::class);
        $stmtMonedaBase->method('execute')->willReturn(true);
        $stmtMonedaBase->method('fetchColumn')->willReturn(1);

        $stmtExistenciaAtleta = $this->createMock(PDOStatement::class);
        $stmtExistenciaAtleta->method('bindValue')->willReturn(true);
        $stmtExistenciaAtleta->method('execute')->willReturn(true);
        $stmtExistenciaAtleta->method('fetchColumn')->willReturn(1);

        $stmtFrecuencia = $this->createMock(PDOStatement::class);
        $stmtFrecuencia->method('bindParam')->willReturn(true);
        $stmtFrecuencia->method('execute')->willReturn(true);
        $stmtFrecuencia->method('fetch')->willReturn(['colisiones' => 0]);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindValue')->willReturn(true);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willThrowException(new Exception('Error de inserción'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaConcepto,
            $stmtMonedaBase,
            $stmtExistenciaAtleta,
            $stmtFrecuencia,
            $stmtInsert
        ) {
            if (str_contains($sql, 'FROM `conceptos`')) {
                return $stmtExistenciaConcepto;
            }
            if (str_contains($sql, 'FROM monedas')) {
                return $stmtMonedaBase;
            }
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtExistenciaAtleta;
            }
            if (str_contains($sql, 'colisiones')) {
                return $stmtFrecuencia;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de inserción', $resultado['codigo']);
    }
}

