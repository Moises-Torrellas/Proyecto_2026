<?php

namespace tests\Unit\Posiciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPosiciones;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitPosiciones')]
#[Group('eliminarPosiciones')]
class EliminarPosicionesTest extends TestCase
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

    // Caso 1: Eliminación exitosa de una posición
    public function testEliminarExitoso(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtInscripciones = $this->createMock(PDOStatement::class);
        $stmtInscripciones->method('bindValue')->willReturn(true);
        $stmtInscripciones->method('execute')->willReturn(true);
        $stmtInscripciones->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('bindParam')->willReturn(true);
        $stmtDelete->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtInscripciones, $stmtDelete) {
            if (str_contains($sql, 'FROM `posiciones`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'FROM `inscripciones`')) {
                return $stmtInscripciones;
            }
            return $stmtDelete;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '1'
        ]);

        $this->assertEquals('eliminar', $resultado['accion']);
        $this->assertEquals('Posición eliminada exitosamente.', $resultado['mensaje']);
    }

    // Caso 2: La posición a eliminar no existe
    public function testPosicionNoExiste(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtExiste);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '99'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('La posicion no existe.', $resultado['mensaje']);
    }

    // Caso 3: La posición tiene atletas asociados en inscripciones
    public function testPosicionConAtletasAsociados(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtInscripciones = $this->createMock(PDOStatement::class);
        $stmtInscripciones->method('bindValue')->willReturn(true);
        $stmtInscripciones->method('execute')->willReturn(true);
        $stmtInscripciones->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtInscripciones) {
            if (str_contains($sql, 'FROM `posiciones`')) {
                return $stmtExiste;
            }
            return $stmtInscripciones;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '1'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('La posicion tiene atletas asociados.', $resultado['mensaje']);
    }

    // Caso 4: Error en base de datos al ejecutar DELETE
    public function testErrorBaseDatos(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtInscripciones = $this->createMock(PDOStatement::class);
        $stmtInscripciones->method('bindValue')->willReturn(true);
        $stmtInscripciones->method('execute')->willReturn(true);
        $stmtInscripciones->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('bindParam')->willReturn(true);
        $stmtDelete->method('execute')->willThrowException(new Exception('Error de base de datos'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtInscripciones, $stmtDelete) {
            if (str_contains($sql, 'FROM `posiciones`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'FROM `inscripciones`')) {
                return $stmtInscripciones;
            }
            return $stmtDelete;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '1'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Hubo un error al eliminar la posición.', $resultado['mensaje']);
    }
}

