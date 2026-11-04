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
#[Group('modificarCategorias')]
class ModificarCategoriasTest extends TestCase
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
            'accion'   => 'modificar',
            'id'       => '1',
            'nombre'   => 'Sub-15',
            'edad_min' => '13',
            'edad_max' => '15'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Modificación exitosa conservando el nombre actual
    public function testModificarExitoso(): void
    {
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('bindValue')->willReturn(true);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(1);

        $stmtRango = $this->createMock(PDOStatement::class);
        $stmtRango->method('bindParam')->willReturn(true);
        $stmtRango->method('execute')->willReturn(true);
        $stmtRango->method('fetchColumn')->willReturn(0);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropio, $stmtRango, $stmtUpdate) {
            if (str_contains($sql, 'UPDATE')) {
                return $stmtUpdate;
            }
            if (str_contains($sql, 'codigo_categoria != :id')) {
                return $stmtRango;
            }
            return $stmtPropio;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: Modificación exitosa cambiando a un nuevo nombre disponible
    public function testModificarCambiandoNombre(): void
    {
        $stmtDisponible = $this->createMock(PDOStatement::class);
        $stmtDisponible->method('bindValue')->willReturn(true);
        $stmtDisponible->method('execute')->willReturn(true);
        $stmtDisponible->method('fetchColumn')->willReturn(0);

        $stmtRango = $this->createMock(PDOStatement::class);
        $stmtRango->method('bindParam')->willReturn(true);
        $stmtRango->method('execute')->willReturn(true);
        $stmtRango->method('fetchColumn')->willReturn(0);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtDisponible, $stmtRango, $stmtUpdate) {
            if (str_contains($sql, 'UPDATE')) {
                return $stmtUpdate;
            }
            if (str_contains($sql, 'codigo_categoria != :id')) {
                return $stmtRango;
            }
            return $stmtDisponible;
        });

        $this->inyectarPdoMock($pdoMock);

        $datos = $this->obtenerDatosValidos(['nombre' => 'Sub-16']);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 3: Nombre duplicado por pertenecer a otra categoría
    public function testNombreDuplicadoOtro(): void
    {
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('bindValue')->willReturn(true);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(0);

        $stmtOtro = $this->createMock(PDOStatement::class);
        $stmtOtro->method('bindValue')->willReturn(true);
        $stmtOtro->method('execute')->willReturn(true);
        $stmtOtro->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropio, $stmtOtro) {
            if (str_contains($sql, 'codigo_categoria = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtPropio;
            }
            return $stmtOtro;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe otra categoría registrada con este nombre.', $resultado['codigo']);
    }

    // Caso 4: Rango de edad solapado con otra categoría existente
    public function testRangoEdadSolapadoOtro(): void
    {
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('bindValue')->willReturn(true);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(1);

        $stmtRango = $this->createMock(PDOStatement::class);
        $stmtRango->method('bindParam')->willReturn(true);
        $stmtRango->method('execute')->willReturn(true);
        $stmtRango->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropio, $stmtRango) {
            if (str_contains($sql, 'codigo_categoria != :id')) {
                return $stmtRango;
            }
            return $stmtPropio;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe otra categoría que cubre este rango de edad.', $resultado['codigo']);
    }

    // Caso 5: Error de base de datos durante la actualización
    public function testErrorBaseDatos(): void
    {
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('bindValue')->willReturn(true);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(1);

        $stmtRango = $this->createMock(PDOStatement::class);
        $stmtRango->method('bindParam')->willReturn(true);
        $stmtRango->method('execute')->willReturn(true);
        $stmtRango->method('fetchColumn')->willReturn(0);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willThrowException(new Exception('Error de base de datos'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtPropio, $stmtRango, $stmtUpdate) {
            if (str_contains($sql, 'UPDATE')) {
                return $stmtUpdate;
            }
            if (str_contains($sql, 'codigo_categoria != :id')) {
                return $stmtRango;
            }
            return $stmtPropio;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCategorias();
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de base de datos', $resultado['codigo']);
    }
}

