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

if (!defined('DUPLICATE_NAME')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitMonedas')]
#[Group('registrarMonedas')]
class RegistrarMonedasTest extends TestCase
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
            'accion'      => 'incluir',
            'nombre'      => 'Euro',
            'abreviatura' => 'EUR',
            'simbolo'     => '€'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Registro exitoso de una nueva moneda
    public function testRegistroExitoso(): void
    {
        $stmtExistenciaNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaNombre->method('execute')->willReturn(true);
        $stmtExistenciaNombre->method('fetchColumn')->willReturn(0);

        $stmtExistenciaAbreviatura = $this->createMock(PDOStatement::class);
        $stmtExistenciaAbreviatura->method('bindValue')->willReturn(true);
        $stmtExistenciaAbreviatura->method('execute')->willReturn(true);
        $stmtExistenciaAbreviatura->method('fetchColumn')->willReturn(0);

        $stmtExistenciaSimbolo = $this->createMock(PDOStatement::class);
        $stmtExistenciaSimbolo->method('bindValue')->willReturn(true);
        $stmtExistenciaSimbolo->method('execute')->willReturn(true);
        $stmtExistenciaSimbolo->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaNombre,
            $stmtExistenciaAbreviatura,
            $stmtExistenciaSimbolo,
            $stmtInsert
        ) {
            if (str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaNombre;
            }
            if (str_contains($sql, 'abreviatura = :valor')) {
                return $stmtExistenciaAbreviatura;
            }
            if (str_contains($sql, 'simbolo = :valor')) {
                return $stmtExistenciaSimbolo;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: Campos únicos duplicados (Nombre, Abreviatura, Símbolo)
    public function testCamposUnicosDuplicados(): void
    {
        $campos = [
            ['campo' => 'nombre', 'codigo' => DUPLICATE_NAME],
            ['campo' => 'abreviatura', 'codigo' => VALIDATION . '1'],
            ['campo' => 'simbolo', 'codigo' => VALIDATION . '2']
        ];

        foreach ($campos as $caso) {
            $stmtDuplicado = $this->createMock(PDOStatement::class);
            $stmtDuplicado->method('bindValue')->willReturn(true);
            $stmtDuplicado->method('execute')->willReturn(true);
            $stmtDuplicado->method('fetchColumn')->willReturn(1);

            $stmtOk = $this->createMock(PDOStatement::class);
            $stmtOk->method('bindValue')->willReturn(true);
            $stmtOk->method('execute')->willReturn(true);
            $stmtOk->method('fetchColumn')->willReturn(0);

            $pdoMock = $this->createMock(PDO::class);
            $pdoMock->method('beginTransaction')->willReturn(true);
            $pdoMock->method('inTransaction')->willReturn(true);
            $pdoMock->method('rollBack')->willReturn(true);
            $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($caso, $stmtDuplicado, $stmtOk) {
                if (str_contains($sql, $caso['campo'] . ' = :valor')) {
                    return $stmtDuplicado;
                }
                return $stmtOk;
            });

            $this->inyectarPdoMock($pdoMock);

            $modelo = new ModeloMonedas();
            $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

            $this->assertEquals('error', $resultado['accion']);
            $this->assertEquals($caso['codigo'], $resultado['codigo']);
        }
    }

    // Caso 5: Error de base de datos durante la inserción
    public function testErrorBaseDatosAlInsertar(): void
    {
        $stmtExistenciaNombre = $this->createMock(PDOStatement::class);
        $stmtExistenciaNombre->method('bindValue')->willReturn(true);
        $stmtExistenciaNombre->method('execute')->willReturn(true);
        $stmtExistenciaNombre->method('fetchColumn')->willReturn(0);

        $stmtExistenciaAbreviatura = $this->createMock(PDOStatement::class);
        $stmtExistenciaAbreviatura->method('bindValue')->willReturn(true);
        $stmtExistenciaAbreviatura->method('execute')->willReturn(true);
        $stmtExistenciaAbreviatura->method('fetchColumn')->willReturn(0);

        $stmtExistenciaSimbolo = $this->createMock(PDOStatement::class);
        $stmtExistenciaSimbolo->method('bindValue')->willReturn(true);
        $stmtExistenciaSimbolo->method('execute')->willReturn(true);
        $stmtExistenciaSimbolo->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willThrowException(new Exception('Error al insertar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistenciaNombre,
            $stmtExistenciaAbreviatura,
            $stmtExistenciaSimbolo,
            $stmtInsert
        ) {
            if (str_contains($sql, 'nombre = :valor')) {
                return $stmtExistenciaNombre;
            }
            if (str_contains($sql, 'abreviatura = :valor')) {
                return $stmtExistenciaAbreviatura;
            }
            if (str_contains($sql, 'simbolo = :valor')) {
                return $stmtExistenciaSimbolo;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloMonedas();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al insertar', $resultado['codigo']);
    }
}

