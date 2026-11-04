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

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitCargos')]
#[Group('eliminarCargos')]
class EliminarCargosTest extends TestCase
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
            'id'     => 5
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Eliminación (anulación) exitosa del cargo
    public function testEliminarExitoso(): void
    {
        $stmtExistenciaCargo = $this->createMock(PDOStatement::class);
        $stmtExistenciaCargo->method('bindValue')->willReturn(true);
        $stmtExistenciaCargo->method('execute')->willReturn(true);
        $stmtExistenciaCargo->method('fetchColumn')->willReturn(1);

        $stmtBuscar = $this->createMock(PDOStatement::class);
        $stmtBuscar->method('bindParam')->willReturn(true);
        $stmtBuscar->method('execute')->willReturn(true);
        $stmtBuscar->method('fetchAll')->willReturn([
            [
                0 => 5, 'id_cobrar' => 5,
                1 => 'Inscripción', 'concepto_nombre' => 'Inscripción',
                2 => 'Pedro', 'atleta_nombre' => 'Pedro',
                3 => 'Torres', 'atleta_apellido' => 'Torres',
                4 => '2026-02-01', 'fecha_emision' => '2026-02-01'
            ]
        ]);

        $stmtDetallesPagos = $this->createMock(PDOStatement::class);
        $stmtDetallesPagos->method('bindValue')->willReturn(true);
        $stmtDetallesPagos->method('execute')->willReturn(true);
        $stmtDetallesPagos->method('fetchColumn')->willReturn(0);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaCargo,
            $stmtBuscar,
            $stmtDetallesPagos,
            $stmtUpdate
        ) {
            if (str_contains($sql, 'FROM `cargos`') && str_contains($sql, 'codigo_cargo = :valor')) {
                return $stmtExistenciaCargo;
            }
            if (str_contains($sql, 'FROM vista_cargos')) {
                return $stmtBuscar;
            }
            if (str_contains($sql, 'FROM `detalles_pagos`')) {
                return $stmtDetallesPagos;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertStringContainsString('Inscripción', $resultado['desc_bitacora']);
        $this->assertStringContainsString('Pedro Torres', $resultado['desc_bitacora']);
        $this->assertNotEmpty($resultado['datos_previos']);
    }

    // Caso 2: El cargo a anular no existe
    public function testCargoNoExiste(): void
    {
        $stmtExistenciaCargo = $this->createMock(PDOStatement::class);
        $stmtExistenciaCargo->method('bindValue')->willReturn(true);
        $stmtExistenciaCargo->method('execute')->willReturn(true);
        $stmtExistenciaCargo->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExistenciaCargo);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 3: El cargo tiene pagos asociados y no puede anularse
    public function testCargoConPagosAsociados(): void
    {
        $stmtExistenciaCargo = $this->createMock(PDOStatement::class);
        $stmtExistenciaCargo->method('bindValue')->willReturn(true);
        $stmtExistenciaCargo->method('execute')->willReturn(true);
        $stmtExistenciaCargo->method('fetchColumn')->willReturn(1);

        $stmtBuscar = $this->createMock(PDOStatement::class);
        $stmtBuscar->method('bindParam')->willReturn(true);
        $stmtBuscar->method('execute')->willReturn(true);
        $stmtBuscar->method('fetchAll')->willReturn([
            ['id_cobrar' => 5, 'concepto_nombre' => 'Inscripción']
        ]);

        $stmtDetallesPagos = $this->createMock(PDOStatement::class);
        $stmtDetallesPagos->method('bindValue')->willReturn(true);
        $stmtDetallesPagos->method('execute')->willReturn(true);
        $stmtDetallesPagos->method('fetchColumn')->willReturn(1); // Tiene pagos asociados

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaCargo,
            $stmtBuscar,
            $stmtDetallesPagos
        ) {
            if (str_contains($sql, 'FROM `cargos`')) {
                return $stmtExistenciaCargo;
            }
            if (str_contains($sql, 'FROM vista_cargos')) {
                return $stmtBuscar;
            }
            return $stmtDetallesPagos;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo']);
    }

    // Caso 4: Descripción por defecto cuando no se encuentran datos previos en la vista
    public function testEliminarSinDatosEnVista(): void
    {
        $stmtExistenciaCargo = $this->createMock(PDOStatement::class);
        $stmtExistenciaCargo->method('bindValue')->willReturn(true);
        $stmtExistenciaCargo->method('execute')->willReturn(true);
        $stmtExistenciaCargo->method('fetchColumn')->willReturn(1);

        $stmtBuscar = $this->createMock(PDOStatement::class);
        $stmtBuscar->method('bindParam')->willReturn(true);
        $stmtBuscar->method('execute')->willReturn(true);
        $stmtBuscar->method('fetchAll')->willReturn([]);

        $stmtDetallesPagos = $this->createMock(PDOStatement::class);
        $stmtDetallesPagos->method('bindValue')->willReturn(true);
        $stmtDetallesPagos->method('execute')->willReturn(true);
        $stmtDetallesPagos->method('fetchColumn')->willReturn(0);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaCargo,
            $stmtBuscar,
            $stmtDetallesPagos,
            $stmtUpdate
        ) {
            if (str_contains($sql, 'FROM `cargos`')) {
                return $stmtExistenciaCargo;
            }
            if (str_contains($sql, 'FROM vista_cargos')) {
                return $stmtBuscar;
            }
            if (str_contains($sql, 'FROM `detalles_pagos`')) {
                return $stmtDetallesPagos;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertStringContainsString('Anuló el Cargo ID 5', $resultado['desc_bitacora']);
    }

    // Caso 5: Error en base de datos al anular el cargo
    public function testErrorBaseDatosAlEliminar(): void
    {
        $stmtExistenciaCargo = $this->createMock(PDOStatement::class);
        $stmtExistenciaCargo->method('bindValue')->willReturn(true);
        $stmtExistenciaCargo->method('execute')->willReturn(true);
        $stmtExistenciaCargo->method('fetchColumn')->willReturn(1);

        $stmtBuscar = $this->createMock(PDOStatement::class);
        $stmtBuscar->method('bindParam')->willReturn(true);
        $stmtBuscar->method('execute')->willReturn(true);
        $stmtBuscar->method('fetchAll')->willReturn([
            ['id_cobrar' => 5, 'concepto_nombre' => 'Inscripción']
        ]);

        $stmtDetallesPagos = $this->createMock(PDOStatement::class);
        $stmtDetallesPagos->method('bindValue')->willReturn(true);
        $stmtDetallesPagos->method('execute')->willReturn(true);
        $stmtDetallesPagos->method('fetchColumn')->willReturn(0);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willThrowException(new Exception('Error al actualizar estatus'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaCargo,
            $stmtBuscar,
            $stmtDetallesPagos,
            $stmtUpdate
        ) {
            if (str_contains($sql, 'FROM `cargos`')) {
                return $stmtExistenciaCargo;
            }
            if (str_contains($sql, 'FROM vista_cargos')) {
                return $stmtBuscar;
            }
            if (str_contains($sql, 'FROM `detalles_pagos`')) {
                return $stmtDetallesPagos;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al actualizar estatus', $resultado['codigo']);
    }
}

