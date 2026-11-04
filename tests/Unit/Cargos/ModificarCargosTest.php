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
#[Group('modificarCargos')]
class ModificarCargosTest extends TestCase
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
            'accion'        => 'modificar',
            'id'            => 5,
            'id_concepto'   => 2,
            'id_atleta'     => 12,
            'monto_total'   => 200.00,
            'fecha_emision' => '2026-04-10',
            'estatus'       => 1
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Modificación exitosa del cargo
    public function testModificarExitoso(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $stmtBuscar = $this->createMock(PDOStatement::class);
        $stmtBuscar->method('bindParam')->willReturn(true);
        $stmtBuscar->method('execute')->willReturn(true);
        $stmtBuscar->method('fetchAll')->willReturn([
            [
                0 => 5, 'id_cobrar' => 5,
                1 => 'Uniforme', 'concepto_nombre' => 'Uniforme',
                2 => 'Luis', 'atleta_nombre' => 'Luis',
                3 => 'García', 'atleta_apellido' => 'García',
                4 => '2026-04-10', 'fecha_emision' => '2026-04-10'
            ]
        ]);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistencia, $stmtBuscar, $stmtUpdate) {
            if (str_contains($sql, 'FROM `cargos`') && str_contains($sql, 'codigo_cargo = :valor')) {
                return $stmtExistencia;
            }
            if (str_contains($sql, 'FROM vista_cargos WHERE id_cobrar = :id')) {
                return $stmtBuscar;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertStringContainsString('Uniforme', $resultado['desc_bitacora']);
        $this->assertStringContainsString('Luis García', $resultado['desc_bitacora']);
        $this->assertNotEmpty($resultado['datos_previos']);
        $this->assertNotEmpty($resultado['datos_nuevos']);
    }

    // Caso 2: Modificación con atleta enviado como arreglo
    public function testModificarConAtletaComoArray(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $stmtBuscar = $this->createMock(PDOStatement::class);
        $stmtBuscar->method('bindParam')->willReturn(true);
        $stmtBuscar->method('execute')->willReturn(true);
        $stmtBuscar->method('fetchAll')->willReturn([
            [
                'id_cobrar' => 5,
                'concepto_nombre' => 'Matrícula',
                'atleta_nombre' => 'Ana',
                'atleta_apellido' => 'Gómez',
                'fecha_emision' => '2026-04-10'
            ]
        ]);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistencia, $stmtBuscar, $stmtUpdate) {
            if (str_contains($sql, 'FROM `cargos`')) {
                return $stmtExistencia;
            }
            if (str_contains($sql, 'FROM vista_cargos')) {
                return $stmtBuscar;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'id_atleta' => [15]
        ]));

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertStringContainsString('Ana Gómez', $resultado['desc_bitacora']);
    }

    // Caso 3: El cargo a modificar no existe
    public function testCargoNoExiste(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExistencia);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 4: Descripción por defecto cuando la vista no retorna registros
    public function testModificarSinDatosEnVista(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $stmtBuscar = $this->createMock(PDOStatement::class);
        $stmtBuscar->method('bindParam')->willReturn(true);
        $stmtBuscar->method('execute')->willReturn(true);
        $stmtBuscar->method('fetchAll')->willReturn([]);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistencia, $stmtBuscar, $stmtUpdate) {
            if (str_contains($sql, 'FROM `cargos`')) {
                return $stmtExistencia;
            }
            if (str_contains($sql, 'FROM vista_cargos')) {
                return $stmtBuscar;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertStringContainsString('Modificó el Cargo ID 5', $resultado['desc_bitacora']);
    }

    // Caso 5: Error en base de datos al actualizar
    public function testErrorBaseDatosAlModificar(): void
    {
        $stmtExistencia = $this->createMock(PDOStatement::class);
        $stmtExistencia->method('bindValue')->willReturn(true);
        $stmtExistencia->method('execute')->willReturn(true);
        $stmtExistencia->method('fetchColumn')->willReturn(1);

        $stmtBuscar = $this->createMock(PDOStatement::class);
        $stmtBuscar->method('bindParam')->willReturn(true);
        $stmtBuscar->method('execute')->willReturn(true);
        $stmtBuscar->method('fetchAll')->willReturn([
            ['id_cobrar' => 5, 'concepto_nombre' => 'Test']
        ]);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willThrowException(new Exception('Error al actualizar cargo'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollback')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistencia, $stmtBuscar, $stmtUpdate) {
            if (str_contains($sql, 'FROM `cargos`')) {
                return $stmtExistencia;
            }
            if (str_contains($sql, 'FROM vista_cargos')) {
                return $stmtBuscar;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al actualizar cargo', $resultado['codigo']);
    }
}
