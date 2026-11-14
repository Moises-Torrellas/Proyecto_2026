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
#[Group('RF-03')]
#[Group('ESC-03')]
class ModificarArticulosInventarioTest extends TestCase
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

    // Caso 10: Modificación exitosa del artículo de inventario con commit transaccional
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-10')]
    public function testModificarArticuloExitoso(): void
    {
        // Arrange
        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->with([2, 3, 15])->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->with($this->stringContains('UPDATE articulos_inventario SET id_catalogo = ?, id_estado = ? WHERE codigo_articulo = ?'))
                ->willReturn($stmtUpdate);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'          => 'modificar',
            'codigo_articulo' => 15,
            'id_catalogo'     => 2,
            'id_estado'       => 3
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Artículo actualizado correctamente.', $resultado['mensaje']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);
    }

    // Caso 11: Verificación de estructura y consistencia de datos_nuevos en JSON
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-11')]
    public function testModificarArticuloVerificacionDatosNuevos(): void
    {
        // Arrange
        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtUpdate);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'          => 'modificar',
            'codigo_articulo' => '20',
            'id_catalogo'     => '4',
            'id_estado'       => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $datosDecoded = json_decode($resultado['datos_nuevos'], true);
        $this->assertEquals('20', $datosDecoded['codigo_articulo']);
        $this->assertEquals('4', $datosDecoded['id_catalogo']);
        $this->assertEquals('1', $datosDecoded['id_estado']);
    }

    // Caso 12: Error de base de datos durante el UPDATE con rollback transaccional
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-12')]
    public function testModificarArticuloErrorBaseDeDatos(): void
    {
        // Arrange
        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Error de conexión al actualizar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtUpdate);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'          => 'modificar',
            'codigo_articulo' => 15,
            'id_catalogo'     => 2,
            'id_estado'       => 3
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DB_CONNECTION, $resultado['codigo']);
    }

    // Caso 13: Cambio de estatus individual mediante CambiarEstatus()
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-13')]
    public function testCambiarEstatusExitoso(): void
    {
        // Arrange
        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->expects($this->once())
            ->method('execute')
            ->with([2, 15]) // estatus = 2 (Asignado/Prestado), codigo = 15
            ->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('UPDATE articulos_inventario SET estatus = ? WHERE codigo_articulo = ?'))
                ->willReturn($stmtUpdate);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->CambiarEstatus(15, 2);

        // Assert
        $this->assertTrue($resultado);
    }
}

