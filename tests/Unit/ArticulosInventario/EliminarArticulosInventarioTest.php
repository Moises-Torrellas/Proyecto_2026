<?php

namespace tests\Unit\ArticulosInventario;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloArticulosInventario;
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
#[Group('unitArticulosInventario')]
#[Group('RF-04')]
#[Group('ESC-04')]
class EliminarArticulosInventarioTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloArticulosInventario
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloArticulosInventario();
    }

    // Caso 14: Retiro exitoso del inventario mediante procedimiento almacenado (resultado = 1)
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-14')]
    public function testEliminarArticuloExitoso(): void
    {
        // Arrange
        $stmtSP = $this->createMock(PDOStatement::class);
        $stmtSP->method('execute')->with([10])->willReturn(true);

        $stmtResQuery = $this->createMock(PDOStatement::class);
        $stmtResQuery->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('CALL RetirarArticuloSeguro(?, @resultado)'))->willReturn($stmtSP);
        $pdoMock->method('query')->with($this->stringContains('SELECT @resultado AS res'))->willReturn($stmtResQuery);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'          => 'eliminar',
            'codigo_articulo' => 10
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('El artículo ha sido retirado del inventario.', $resultado['mensaje']);
    }

    // Caso 15: Rechazo de retiro cuando el artículo está asignado/en uso o ya fue dado de baja (-2)
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-15')]
    public function testEliminarArticuloEnUsoRechazado(): void
    {
        // Arrange
        $stmtSP = $this->createMock(PDOStatement::class);
        $stmtSP->method('execute')->willReturn(true);

        $stmtResQuery = $this->createMock(PDOStatement::class);
        $stmtResQuery->method('fetchColumn')->willReturn(-2);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtSP);
        $pdoMock->method('query')->willReturn($stmtResQuery);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'          => 'eliminar',
            'codigo_articulo' => 10
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(
            'El artículo no puede ser retirado porque está en uso o ya fue dado de baja.',
            $resultado['mensaje']
        );
    }

    // Caso 16: Rechazo de retiro cuando el resultado del SP es 0 o código no contemplado
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-16')]
    public function testEliminarArticuloCodigoFalloSP(): void
    {
        // Arrange
        $stmtSP = $this->createMock(PDOStatement::class);
        $stmtSP->method('execute')->willReturn(true);

        $stmtResQuery = $this->createMock(PDOStatement::class);
        $stmtResQuery->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtSP);
        $pdoMock->method('query')->willReturn($stmtResQuery);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'          => 'eliminar',
            'codigo_articulo' => 999
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DB_CONNECTION, $resultado['codigo']);
    }

    // Caso 17: Rechazo de retiro si no se proporciona el ID de artículo
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-17')]
    public function testEliminarArticuloIdVacio(): void
    {
        // Arrange
        $modelo = new ModeloArticulosInventario();
        $datos = [
            'accion' => 'eliminar'
            // Sin codigo_articulo
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(EMPTY_SELECTION, $resultado['codigo']);
    }

    // Caso 18: Error de base de datos durante la ejecución del procedimiento almacenado
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-18')]
    public function testEliminarArticuloErrorPDOException(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error de conexión o fallo en SP'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'          => 'eliminar',
            'codigo_articulo' => 10
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DB_CONNECTION, $resultado['codigo']);
    }

    // Caso 19: Reincorporación exitosa de un artículo en condición de retirado (estatus = 3 a 1)
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-19')]
    public function testReincorporarArticuloExitoso(): void
    {
        // Arrange
        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->with([12])->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->with($this->stringContains('UPDATE articulos_inventario SET estatus = 1 WHERE codigo_articulo = ? AND estatus = 3'))
                ->willReturn($stmtUpdate);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'          => 'reincorporar',
            'codigo_articulo' => 12
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Artículo reincorporado como Disponible.', $resultado['mensaje']);
    }

    // Caso 20: Rechazo de reincorporación si el ID está vacío
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-20')]
    public function testReincorporarArticuloIdVacio(): void
    {
        // Arrange
        $modelo = new ModeloArticulosInventario();
        $datos = [
            'accion' => 'reincorporar'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(EMPTY_SELECTION, $resultado['codigo']);
    }

    // Caso 21: Error de base de datos durante la reincorporación con rollback transaccional
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-21')]
    public function testReincorporarArticuloErrorPDOException(): void
    {
        // Arrange
        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Error al actualizar estatus'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtUpdate);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'          => 'reincorporar',
            'codigo_articulo' => 12
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DB_CONNECTION, $resultado['codigo']);
    }
}

