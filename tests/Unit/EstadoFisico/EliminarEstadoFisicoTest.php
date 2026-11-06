<?php

namespace tests\Unit\EstadoFisico;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadoFisico;
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
#[Group('unitEstadoFisico')]
#[Group('RF-04')]
#[Group('ESC-04')]
class EliminarEstadoFisicoTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloEstadoFisico
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloEstadoFisico();
    }

    // Caso 11: Eliminación exitosa sin asociaciones
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-11')]
    public function testEliminarEstadoFisicoExitoso(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1); // El estado existe

        $stmtArticulos = $this->createMock(PDOStatement::class);
        $stmtArticulos->method('execute')->willReturn(true);
        $stmtArticulos->method('fetchColumn')->willReturn(0); // Sin artículos asociados

        $stmtDevoluciones = $this->createMock(PDOStatement::class);
        $stmtDevoluciones->method('execute')->willReturn(true);
        $stmtDevoluciones->method('fetchColumn')->willReturn(0); // Sin devoluciones asociadas

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtExiste, $stmtArticulos, $stmtDevoluciones, $stmtDelete
        ) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'estado_fisico')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'articulos_inventario')) {
                return $stmtArticulos;
            }
            if (str_contains($sql, 'devoluciones')) {
                return $stmtDevoluciones;
            }
            return $stmtDelete;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'    => 'eliminar',
            'id_estado' => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('eliminar', $resultado['accion']);
        $this->assertEquals('Estado físico eliminado exitosamente.', $resultado['mensaje']);
    }

    // Caso 12: Rechazo si el estado físico no existe
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-12')]
    public function testEliminarEstadoFisicoNoExiste(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0); // No existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtExiste);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'    => 'eliminar',
            'id_estado' => '999'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El estado físico no existe.', $resultado['mensaje']);
    }

    // Caso 13: Rechazo por artículos de inventario asociados
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-13')]
    public function testEliminarRechazoPorArticulosAsociados(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtArticulos = $this->createMock(PDOStatement::class);
        $stmtArticulos->method('execute')->willReturn(true);
        $stmtArticulos->method('fetchColumn')->willReturn(5); // 5 artículos asociados (> 0)

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExiste, $stmtArticulos) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'estado_fisico')) {
                return $stmtExiste;
            }
            return $stmtArticulos;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'    => 'eliminar',
            'id_estado' => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(
            'No se puede eliminar: existen artículos de inventario asociados a este estado.',
            $resultado['mensaje']
        );
    }

    // Caso 14: Rechazo por devoluciones asociadas
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-14')]
    public function testEliminarRechazoPorDevolucionesAsociadas(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtArticulos = $this->createMock(PDOStatement::class);
        $stmtArticulos->method('execute')->willReturn(true);
        $stmtArticulos->method('fetchColumn')->willReturn(0);

        $stmtDevoluciones = $this->createMock(PDOStatement::class);
        $stmtDevoluciones->method('execute')->willReturn(true);
        $stmtDevoluciones->method('fetchColumn')->willReturn(3); // 3 devoluciones asociadas (> 0)

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtExiste, $stmtArticulos, $stmtDevoluciones
        ) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'estado_fisico')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'articulos_inventario')) {
                return $stmtArticulos;
            }
            return $stmtDevoluciones;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'    => 'eliminar',
            'id_estado' => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(
            'No se puede eliminar: existen devoluciones asociadas a este estado.',
            $resultado['mensaje']
        );
    }

    // Caso 15: Error de base de datos durante la eliminación
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-15')]
    public function testErrorBaseDeDatosEnEliminar(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtArticulos = $this->createMock(PDOStatement::class);
        $stmtArticulos->method('execute')->willReturn(true);
        $stmtArticulos->method('fetchColumn')->willReturn(0);

        $stmtDevoluciones = $this->createMock(PDOStatement::class);
        $stmtDevoluciones->method('execute')->willReturn(true);
        $stmtDevoluciones->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('execute')->willThrowException(new PDOException('Fallo al ejecutar DELETE'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtExiste, $stmtArticulos, $stmtDevoluciones, $stmtDelete
        ) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'estado_fisico')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'articulos_inventario')) {
                return $stmtArticulos;
            }
            if (str_contains($sql, 'devoluciones')) {
                return $stmtDevoluciones;
            }
            return $stmtDelete;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'    => 'eliminar',
            'id_estado' => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Hubo un error al eliminar el estado físico.', $resultado['mensaje']);
    }
}

