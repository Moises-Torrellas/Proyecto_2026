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

    // Caso 4: Nombre duplicado perteneciente a otra moneda
    public function testNombreDuplicadoEnOtraMoneda(): void
    {
        $stmtExistenciaId = $this->createMock(PDOStatement::class);
        $stmtExistenciaId->method('bindValue')->willReturn(true);
        $stmtExistenciaId->method('execute')->willReturn(true);
        $stmtExistenciaId->method('fetchColumn')->willReturn(1);

        $stmtExistenciaPropiaNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropiaNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaPropiaNombre->method('execute')->willReturn(true);
        $stmtExistenciaPropiaNombre->method('fetchColumn')->willReturn(0);

        $stmtExistenciaGeneralNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaGeneralNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaGeneralNombre->method('execute')->willReturn(true);
        $stmtExistenciaGeneralNombre->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaId,
            $stmtExistenciaPropiaNombre,
            $stmtExistenciaGeneralNombre
        ) {
            if (str_contains($sql, 'codigo_moneda = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaPropiaNombre;
            }
            if (str_contains($sql, 'codigo_moneda = :valor')) {
                return $stmtExistenciaId;
            }
            return $stmtExistenciaGeneralNombre;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'nombre' => 'Bolívar Digital'
        ]));

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);
    }

    // Caso 5: Abreviatura duplicada perteneciente a otra moneda
    public function testAbreviaturaDuplicadaEnOtraMoneda(): void
    {
        $stmtExistenciaId = $this->createMock(PDOStatement::class);
        $stmtExistenciaId->method('bindValue')->willReturn(true);
        $stmtExistenciaId->method('execute')->willReturn(true);
        $stmtExistenciaId->method('fetchColumn')->willReturn(1);

        $stmtExistenciaPropiaNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropiaNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaPropiaNombre->method('execute')->willReturn(true);
        $stmtExistenciaPropiaNombre->method('fetchColumn')->willReturn(1);

        $stmtExistenciaPropiaAbreviatura = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropiaAbreviatura->method('bindValue')->willReturn(true);
        $stmtExistenciaPropiaAbreviatura->method('execute')->willReturn(true);
        $stmtExistenciaPropiaAbreviatura->method('fetchColumn')->willReturn(0);

        $stmtExistenciaGeneralAbreviatura = $this->createMock(PDOStatement::class);
        $stmtExistenciaGeneralAbreviatura->method('bindValue')->willReturn(true);
        $stmtExistenciaGeneralAbreviatura->method('execute')->willReturn(true);
        $stmtExistenciaGeneralAbreviatura->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaId,
            $stmtExistenciaPropiaNombre,
            $stmtExistenciaPropiaAbreviatura,
            $stmtExistenciaGeneralAbreviatura
        ) {
            if (str_contains($sql, 'codigo_moneda = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaPropiaNombre;
            }
            if (str_contains($sql, 'codigo_moneda = :valor')) {
                return $stmtExistenciaId;
            }
            if (str_contains($sql, 'codigo_moneda = :id') && str_contains($sql, 'abreviatura = :valor')) {
                return $stmtExistenciaPropiaAbreviatura;
            }
            return $stmtExistenciaGeneralAbreviatura;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'abreviatura' => 'VES'
        ]));

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION . '1', $resultado['codigo']);
    }

    // Caso 6: Símbolo duplicado perteneciente a otra moneda
    public function testSimboloDuplicadoEnOtraMoneda(): void
    {
        $stmtExistenciaId = $this->createMock(PDOStatement::class);
        $stmtExistenciaId->method('bindValue')->willReturn(true);
        $stmtExistenciaId->method('execute')->willReturn(true);
        $stmtExistenciaId->method('fetchColumn')->willReturn(1);

        $stmtExistenciaPropiaNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropiaNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaPropiaNombre->method('execute')->willReturn(true);
        $stmtExistenciaPropiaNombre->method('fetchColumn')->willReturn(1);

        $stmtExistenciaPropiaAbreviatura = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropiaAbreviatura->method('bindValue')->willReturn(true);
        $stmtExistenciaPropiaAbreviatura->method('execute')->willReturn(true);
        $stmtExistenciaPropiaAbreviatura->method('fetchColumn')->willReturn(1);

        $stmtExistenciaPropiaSimbolo = $this->createMock(PDOStatement::class);
        $stmtExistenciaPropiaSimbolo->method('bindValue')->willReturn(true);
        $stmtExistenciaPropiaSimbolo->method('execute')->willReturn(true);
        $stmtExistenciaPropiaSimbolo->method('fetchColumn')->willReturn(0);

        $stmtExistenciaGeneralSimbolo = $this->createMock(PDOStatement::class);
        $stmtExistenciaGeneralSimbolo->method('bindValue')->willReturn(true);
        $stmtExistenciaGeneralSimbolo->method('execute')->willReturn(true);
        $stmtExistenciaGeneralSimbolo->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaId,
            $stmtExistenciaPropiaNombre,
            $stmtExistenciaPropiaAbreviatura,
            $stmtExistenciaPropiaSimbolo,
            $stmtExistenciaGeneralSimbolo
        ) {
            if (str_contains($sql, 'codigo_moneda = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaPropiaNombre;
            }
            if (str_contains($sql, 'codigo_moneda = :id') && str_contains($sql, 'abreviatura = :valor')) {
                return $stmtExistenciaPropiaAbreviatura;
            }
            if (str_contains($sql, 'codigo_moneda = :valor')) {
                return $stmtExistenciaId;
            }
            if (str_contains($sql, 'codigo_moneda = :id') && str_contains($sql, 'simbolo = :valor')) {
                return $stmtExistenciaPropiaSimbolo;
            }
            return $stmtExistenciaGeneralSimbolo;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'simbolo' => 'Bs.'
        ]));

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION . '2', $resultado['codigo']);
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

