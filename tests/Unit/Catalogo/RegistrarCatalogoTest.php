<?php

namespace tests\Unit\Catalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCatalogo;
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
#[Group('unitCatalogo')]
#[Group('RF-02')]
#[Group('ESC-02')]
class RegistrarCatalogoTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloCatalogo
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloCatalogo();
    }

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $datos = [
            'accion'          => 'incluir',
            'nombre'          => 'Casco Bauer Re-Akt',
            'stock_minimo'    => '3',
            'id_categoria'    => '2',
            'talla'           => 'L',
            'codigo_posicion' => '1'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 10: Inclusión exitosa de un artículo de catálogo
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-10')]
    public function testIncluirCatalogoExitoso(): void
    {
        // Arrange
        $stmtCheckCat = $this->createMock(PDOStatement::class);
        $stmtCheckCat->method('execute')->willReturn(true);
        $stmtCheckCat->method('fetchColumn')->willReturn(1); // La categoría sí existe

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckCat, $stmtInsert) {
            if (str_contains($sql, 'categoria_catalogo') && str_contains($sql, 'COUNT(*)')) {
                return $stmtCheckCat;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        $datosDecoded = json_decode($resultado['datos_nuevos'], true);
        $this->assertEquals('Casco Bauer Re-Akt', $datosDecoded['nombre']);
        $this->assertEquals('3', $datosDecoded['stock_minimo']);
        $this->assertEquals('2', $datosDecoded['id_categoria']);
        $this->assertEquals('L', $datosDecoded['talla']);
        $this->assertEquals('1', $datosDecoded['codigo_posicion']);
    }

    // Caso 11: Rechazo de registro cuando la categoría seleccionada no existe
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-11')]
    public function testIncluirCategoriaNoExiste(): void
    {
        // Arrange
        $stmtCheckCat = $this->createMock(PDOStatement::class);
        $stmtCheckCat->method('execute')->willReturn(true);
        $stmtCheckCat->method('fetchColumn')->willReturn(0); // La categoría NO existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturn($stmtCheckCat);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'id_categoria' => '999'
        ]));

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('La categoría seleccionada no existe.', $resultado['codigo']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);
    }

    // Caso 12: Error de base de datos durante el INSERT
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-12')]
    public function testIncluirErrorBaseDeDatosEnInsercion(): void
    {
        // Arrange
        $stmtCheckCat = $this->createMock(PDOStatement::class);
        $stmtCheckCat->method('execute')->willReturn(true);
        $stmtCheckCat->method('fetchColumn')->willReturn(1);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willThrowException(new PDOException('Error de clave foránea o motor SQL'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckCat, $stmtInsert) {
            if (str_contains($sql, 'categoria_catalogo') && str_contains($sql, 'COUNT(*)')) {
                return $stmtCheckCat;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de clave foránea o motor SQL', $resultado['codigo']);
    }

    // Caso 13: Inclusión con campos opcionales omitidos asignando valores por defecto
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-13')]
    public function testIncluirConCamposOpcionalesVacios(): void
    {
        // Arrange
        $stmtCheckCat = $this->createMock(PDOStatement::class);
        $stmtCheckCat->method('execute')->willReturn(true);
        $stmtCheckCat->method('fetchColumn')->willReturn(1);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckCat, $stmtInsert) {
            if (str_contains($sql, 'categoria_catalogo') && str_contains($sql, 'COUNT(*)')) {
                return $stmtCheckCat;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Disco Oficial Hockey',
            'id_categoria' => '1'
            // Omitiendo stock_minimo, talla y codigo_posicion
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $datosDecoded = json_decode($resultado['datos_nuevos'], true);
        $this->assertEquals(0, $datosDecoded['stock_minimo']);
        $this->assertEquals('', $datosDecoded['talla']);
        $this->assertNull($datosDecoded['codigo_posicion']);
    }
}

