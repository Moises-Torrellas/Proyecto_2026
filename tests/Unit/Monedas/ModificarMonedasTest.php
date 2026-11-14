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
#[Group('modificarMonedas')]
class ModificarMonedasTest extends TestCase
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
            'accion'      => 'modificar',
            'id'          => 2,
            'nombre'      => 'Dólar Americano',
            'abreviatura' => 'USD',
            'simbolo'     => '$'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Modificación exitosa conservando los mismos datos propios
    public function testModificarExitosoMismosDatos(): void
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
            if (str_contains($sql, 'codigo_moneda = :id')) {
                return $stmtExistenciaPropia;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'codigo_moneda = :valor')) {
                return $stmtExistenciaId;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: Modificación exitosa cambiando a nuevos valores únicos disponibles
    public function testModificarExitosoNuevosDatos(): void
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
            if (str_contains($sql, 'codigo_moneda = :id')) {
                return $stmtExistenciaPropia;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'codigo_moneda = :valor')) {
                return $stmtExistenciaId;
            }
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExistenciaGeneral;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'nombre'      => 'Libra Esterlina',
            'abreviatura' => 'GBP',
            'simbolo'     => '£'
        ]));

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 3: La moneda a modificar no existe
    public function testMonedaNoExiste(): void
    {
        $stmtExistenciaId = $this->createMock(PDOStatement::class);
        $stmtExistenciaId->method('bindValue')->willReturn(true);
        $stmtExistenciaId->method('execute')->willReturn(true);
        $stmtExistenciaId->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExistenciaId);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 4: Campos únicos duplicados pertenecientes a otra moneda (Nombre, Abreviatura, Símbolo)
    public function testCamposUnicosDuplicadosEnOtraMoneda(): void
    {
        $campos = [
            ['campo' => 'nombre', 'valor' => 'Bolívar Digital', 'codigo' => DUPLICATE_NAME],
            ['campo' => 'abreviatura', 'valor' => 'VES', 'codigo' => VALIDATION . '1'],
            ['campo' => 'simbolo', 'valor' => 'Bs.', 'codigo' => VALIDATION . '2']
        ];

        foreach ($campos as $caso) {
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
            $pdoMock->method('rollBack')->willReturn(true);
            $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
                $caso,
                $stmtExistenciaId,
                $stmtExistenciaPropia,
                $stmtExistenciaGeneral
            ) {
                if (str_contains($sql, 'codigo_moneda = :id') && str_contains($sql, "{$caso['campo']} = :valor")) {
                    return $stmtExistenciaPropia;
                }
                if (str_contains($sql, 'codigo_moneda = :valor')) {
                    return $stmtExistenciaId;
                }
                return $stmtExistenciaGeneral;
            });

            $this->inyectarPdoMock($pdoMock);

            $modelo = new ModeloMonedas();
            $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
                $caso['campo'] => $caso['valor']
            ]));

            $this->assertEquals('error', $resultado['accion']);
            $this->assertEquals($caso['codigo'], $resultado['codigo']);
        }
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
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaId,
            $stmtExistenciaPropia,
            $stmtUpdate
        ) {
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'codigo_moneda = :id')) {
                return $stmtExistenciaPropia;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'codigo_moneda = :valor')) {
                return $stmtExistenciaId;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al actualizar', $resultado['codigo']);
    }
}

