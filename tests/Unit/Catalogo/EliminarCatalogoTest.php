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
#[Group('RF-04')]
#[Group('ESC-04')]
class EliminarCatalogoTest extends TestCase
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

    // Caso 18: Eliminación exitosa mediante Stored Procedure cuando resultado es 1
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-18')]
    public function testEliminarCatalogoExitoso(): void
    {
        // Arrange
        $stmtSP = $this->createMock(PDOStatement::class);
        $stmtSP->method('execute')->willReturn(true);

        $stmtResQuery = $this->createMock(PDOStatement::class);
        $stmtResQuery->method('fetchColumn')->willReturn(1); // Éxito en SP

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('CALL EliminarCatalogoSeguro'))->willReturn($stmtSP);
        $pdoMock->method('query')->with($this->stringContains('SELECT @resultado AS res'))->willReturn($stmtResQuery);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'      => 'eliminar',
            'id_catalogo' => '5'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 19: Rechazo por integridad referencial cuando tiene artículos en inventario (-2)
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-19')]
    public function testEliminarRechazoPorArticulosInventarioAsociados(): void
    {
        // Arrange
        $stmtSP = $this->createMock(PDOStatement::class);
        $stmtSP->method('execute')->willReturn(true);

        $stmtResQuery = $this->createMock(PDOStatement::class);
        $stmtResQuery->method('fetchColumn')->willReturn(-2); // -2 indica artículos en inventario físico

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('CALL EliminarCatalogoSeguro'))->willReturn($stmtSP);
        $pdoMock->method('query')->with($this->stringContains('SELECT @resultado AS res'))->willReturn($stmtResQuery);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'      => 'eliminar',
            'id_catalogo' => '5'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(
            'No se puede eliminar el catálogo porque tiene artículos en el inventario físico asociados.',
            $resultado['codigo']
        );
    }

    // Caso 20: Rechazo cuando el ID es inválido o no existe en el Stored Procedure (ej. 0 o -1)
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-20')]
    public function testEliminarRechazoIdInvalidoOInexistente(): void
    {
        // Arrange
        $stmtSP = $this->createMock(PDOStatement::class);
        $stmtSP->method('execute')->willReturn(true);

        $stmtResQuery = $this->createMock(PDOStatement::class);
        $stmtResQuery->method('fetchColumn')->willReturn(0); // ID no encontrado

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('CALL EliminarCatalogoSeguro'))->willReturn($stmtSP);
        $pdoMock->method('query')->with($this->stringContains('SELECT @resultado AS res'))->willReturn($stmtResQuery);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'      => 'eliminar',
            'id_catalogo' => '999'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 21: Error de base de datos durante la invocación del procedimiento almacenado
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-21')]
    public function testEliminarErrorBaseDeDatosEnSP(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error al ejecutar procedimiento almacenado'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'      => 'eliminar',
            'id_catalogo' => '5'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al ejecutar procedimiento almacenado', $resultado['codigo']);
    }
}

