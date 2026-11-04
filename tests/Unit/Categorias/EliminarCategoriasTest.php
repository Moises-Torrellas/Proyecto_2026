<?php

namespace tests\Unit\Categorias;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCategorias;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitCategorias')]
#[Group('eliminarCategorias')]
class EliminarCategoriasTest extends TestCase
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

    // Caso 1: Eliminación exitosa de una categoría
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
            if (str_contains($sql, 'FROM `categorias`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'FROM `inscripciones`')) {
                return $stmtInscripciones;
            }
            return $stmtDelete;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '1'
        ]);

        $this->assertEquals('eliminar', $resultado['accion']);
        $this->assertEquals('Categoría eliminada exitosamente.', $resultado['mensaje']);
    }

    // Caso 2: La categoría a eliminar no existe
    public function testCategoriaNoExiste(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtExiste);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '99'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('La categoría no existe.', $resultado['codigo']);
    }

    // Caso 3: La categoría tiene atletas asociados en inscripciones
    public function testCategoriaConAtletasAsociados(): void
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
            if (str_contains($sql, 'FROM `categorias`')) {
                return $stmtExiste;
            }
            return $stmtInscripciones;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '1'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('No se puede eliminar: la categoría tiene atletas asociados.', $resultado['codigo']);
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
        $stmtDelete->method('execute')->willThrowException(new Exception('Error al eliminar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtInscripciones, $stmtDelete) {
            if (str_contains($sql, 'FROM `categorias`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'FROM `inscripciones`')) {
                return $stmtInscripciones;
            }
            return $stmtDelete;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '1'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al eliminar', $resultado['codigo']);
    }
}

