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
#[Group('RF-04')]
#[Group('ESC-04')]
class EliminarCategoriaCatalogoTest extends TestCase
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

    // Caso 15: Eliminación exitosa de categoría sin artículos asociados
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-15')]
    public function testEliminarCategoriaCatalogoExitoso(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1); // La categoría sí existe

        $stmtCatalogo = $this->createMock(PDOStatement::class);
        $stmtCatalogo->method('execute')->willReturn(true);
        $stmtCatalogo->method('fetchColumn')->willReturn(0); // Cero artículos asociados en catálogo

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExiste, $stmtCatalogo, $stmtDelete) {
            if (str_contains($sql, 'categoria_catalogo') && str_contains($sql, 'COUNT(*)')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'catalogo') && str_contains($sql, 'COUNT(*)')) {
                return $stmtCatalogo;
            }
            return $stmtDelete;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'       => 'eliminar',
            'id_categoria' => '2'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('eliminar', $resultado['accion']);
        $this->assertEquals('Categoría eliminada exitosamente.', $resultado['mensaje']);
    }

    // Caso 16: Rechazo si la categoría no existe
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-16')]
    public function testEliminarCategoriaNoExiste(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0); // No existe en base de datos

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtExiste);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'       => 'eliminar',
            'id_categoria' => '999'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('La categoría no existe.', $resultado['mensaje']);
    }

    // Caso 17: Rechazo de eliminación por integridad referencial con catálogo
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-17')]
    public function testEliminarCategoriaConArticulosAsociados(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1); // La categoría sí existe

        $stmtCatalogo = $this->createMock(PDOStatement::class);
        $stmtCatalogo->method('execute')->willReturn(true);
        $stmtCatalogo->method('fetchColumn')->willReturn(3); // Tiene 3 artículos asociados en catálogo

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExiste, $stmtCatalogo) {
            if (str_contains($sql, 'categoria_catalogo') && str_contains($sql, 'COUNT(*)')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'catalogo') && str_contains($sql, 'COUNT(*)')) {
                return $stmtCatalogo;
            }
            return null;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'       => 'eliminar',
            'id_categoria' => '2'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('No se puede eliminar: la categoría tiene artículos del catálogo asociados.', $resultado['mensaje']);
    }

    // Caso 18: Error de base de datos durante el DELETE
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-18')]
    public function testEliminarErrorBaseDeDatos(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtCatalogo = $this->createMock(PDOStatement::class);
        $stmtCatalogo->method('execute')->willReturn(true);
        $stmtCatalogo->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('execute')->willThrowException(new PDOException('Error fatal de clave foránea o motor SQL'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExiste, $stmtCatalogo, $stmtDelete) {
            if (str_contains($sql, 'categoria_catalogo') && str_contains($sql, 'COUNT(*)')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'catalogo') && str_contains($sql, 'COUNT(*)')) {
                return $stmtCatalogo;
            }
            return $stmtDelete;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'       => 'eliminar',
            'id_categoria' => '2'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Hubo un error al eliminar la categoría.', $resultado['mensaje']);
    }
}

