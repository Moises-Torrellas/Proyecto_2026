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
#[Group('registrarCategorias')]
class RegistrarCategoriasTest extends TestCase
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
            'accion'   => 'incluir',
            'nombre'   => 'Sub-15',
            'edad_min' => '13',
            'edad_max' => '15'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Registro exitoso de una nueva categoría
    public function testRegistroExitoso(): void
    {
        $stmtNombre = $this->createMock(PDOStatement::class);
        $stmtNombre->method('bindValue')->willReturn(true);
        $stmtNombre->method('execute')->willReturn(true);
        $stmtNombre->method('fetchColumn')->willReturn(0);

        $stmtRango = $this->createMock(PDOStatement::class);
        $stmtRango->method('bindParam')->willReturn(true);
        $stmtRango->method('execute')->willReturn(true);
        $stmtRango->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtNombre, $stmtRango, $stmtInsert) {
            if (str_contains($sql, 'WHERE nombre = :valor')) {
                return $stmtNombre;
            }
            if (str_contains($sql, 'edad_min <= :max')) {
                return $stmtRango;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: Nombre de categoría duplicado
    public function testNombreDuplicado(): void
    {
        $stmtNombre = $this->createMock(PDOStatement::class);
        $stmtNombre->method('bindValue')->willReturn(true);
        $stmtNombre->method('execute')->willReturn(true);
        $stmtNombre->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtNombre);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una categoría registrada con este nombre.', $resultado['codigo']);
    }

    // Caso 3: Rango de edad solapado con una categoría existente
    public function testRangoEdadSolapado(): void
    {
        $stmtNombre = $this->createMock(PDOStatement::class);
        $stmtNombre->method('bindValue')->willReturn(true);
        $stmtNombre->method('execute')->willReturn(true);
        $stmtNombre->method('fetchColumn')->willReturn(0);

        $stmtRango = $this->createMock(PDOStatement::class);
        $stmtRango->method('bindParam')->willReturn(true);
        $stmtRango->method('execute')->willReturn(true);
        $stmtRango->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtNombre, $stmtRango) {
            if (str_contains($sql, 'WHERE nombre = :valor')) {
                return $stmtNombre;
            }
            return $stmtRango;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una categoría que cubre este rango de edad.', $resultado['codigo']);
    }

    // Caso 4: Error en base de datos al insertar
    public function testErrorBaseDatos(): void
    {
        $stmtNombre = $this->createMock(PDOStatement::class);
        $stmtNombre->method('bindValue')->willReturn(true);
        $stmtNombre->method('execute')->willReturn(true);
        $stmtNombre->method('fetchColumn')->willReturn(0);

        $stmtRango = $this->createMock(PDOStatement::class);
        $stmtRango->method('bindParam')->willReturn(true);
        $stmtRango->method('execute')->willReturn(true);
        $stmtRango->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willThrowException(new Exception('Error de base de datos'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtNombre, $stmtRango, $stmtInsert) {
            if (str_contains($sql, 'WHERE nombre = :valor')) {
                return $stmtNombre;
            }
            if (str_contains($sql, 'edad_min <= :max')) {
                return $stmtRango;
            }
            return $stmtInsert;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de base de datos', $resultado['codigo']);
    }
}

