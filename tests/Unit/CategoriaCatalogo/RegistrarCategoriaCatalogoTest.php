<?php

namespace tests\Unit\CategoriaCatalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCategoriaCatalogo;
use App\modelo\Conexion;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitCategoriaCatalogo')]
#[Group('RF-02')]
#[Group('ESC-02')]
class RegistrarCategoriaCatalogoTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloCategoriaCatalogo
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloCategoriaCatalogo();
    }

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $datos = [
            'accion'      => 'incluir',
            'nombre'      => 'Rodilleras',
            'descripcion' => 'Protección para rodillas',
            'tipo_talla'  => 'Numerico'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 7: Inclusión exitosa de categoría de catálogo
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-07')]
    public function testIncluirCategoriaCatalogoExitoso(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0); // No existe categoría duplicada

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsert) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'categoria_catalogo')) {
                return $stmtCheck;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('incluir', $resultado['accion']);
        $this->assertEquals('Categoría registrada exitosamente.', $resultado['mensaje']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        $datosNuevosDecoded = json_decode($resultado['datos_nuevos'], true);
        $this->assertEquals('Rodilleras', $datosNuevosDecoded['nombre']);
        $this->assertEquals('Protección para rodillas', $datosNuevosDecoded['descripcion']);
        $this->assertEquals('Numerico', $datosNuevosDecoded['tipo_talla']);
    }

    // Caso 8: Rechazo por nombre de categoría duplicado
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-08')]
    public function testIncluirNombreDuplicado(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(1); // Ya existe una categoría con ese nombre

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtCheck);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una categoría registrada con este nombre.', $resultado['mensaje']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);
    }

    // Caso 9: Error de base de datos durante la inserción
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-09')]
    public function testIncluirErrorBaseDeDatos(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willThrowException(new PDOException('Error de conexión con el motor SQL'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsert) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'categoria_catalogo')) {
                return $stmtCheck;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error al incluir:', $resultado['mensaje']);
    }

    // Caso 10: Asignación por defecto de tipo_talla como 'Numerico'
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-10')]
    public function testIncluirTipoTallaPorDefectoNumerico(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsert) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'categoria_catalogo')) {
                return $stmtCheck;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion' => 'incluir',
            'nombre' => 'Pantalones'
            // Sin tipo_talla ni descripcion
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('incluir', $resultado['accion']);
        $datosNuevosDecoded = json_decode($resultado['datos_nuevos'], true);
        $this->assertEquals('Numerico', $datosNuevosDecoded['tipo_talla']);
    }
}

