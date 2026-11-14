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
#[Group('RF-02')]
#[Group('ESC-02')]
class RegistrarArticulosInventarioTest extends TestCase
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

    // Caso 6: Inclusión exitosa de un artículo individual con código club correlativo
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-06')]
    public function testIncluirArticuloIndividualExitoso(): void
    {
        // Arrange
        $stmtMax = $this->createMock(PDOStatement::class);
        $stmtMax->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['max_num' => 0]); // Inicia en 1

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('query')->willReturn($stmtMax);
        $pdoMock->method('prepare')->with($this->stringContains('INSERT INTO articulos_inventario'))->willReturn($stmtInsert);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'      => 'incluir',
            'id_catalogo' => '2',
            'id_estado'   => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Artículo registrado con el código CL-0001.', $resultado['mensaje']);
        $this->assertEquals(['CL-0001'], $resultado['codigos']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        $datosDecoded = json_decode($resultado['datos_nuevos'], true);
        $this->assertEquals('CL-0001', $datosDecoded['codigo_club']);
        $this->assertEquals(1, $datosDecoded['estatus']);
    }

    // Caso 7: Inclusión exitosa por lote de múltiples artículos con correlativo consecutivo
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-07')]
    public function testIncluirArticulosLoteExitoso(): void
    {
        // Arrange
        $stmtMax = $this->createMock(PDOStatement::class);
        $stmtMax->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['max_num' => 4]); // Siguiente es 5

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('query')->willReturn($stmtMax);
        $pdoMock->method('prepare')->willReturn($stmtInsert);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'    => 'incluir',
            'articulos' => [
                ['id_catalogo' => 2, 'id_estado' => 1],
                ['id_catalogo' => 2, 'id_estado' => 1],
                ['id_catalogo' => 3, 'id_estado' => 2]
            ]
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Se registraron 3 artículos (CL-0005 al CL-0007).', $resultado['mensaje']);
        $this->assertEquals(['CL-0005', 'CL-0006', 'CL-0007'], $resultado['codigos']);

        $datosDecoded = json_decode($resultado['datos_nuevos'], true);
        $this->assertCount(3, $datosDecoded);
        $this->assertEquals('CL-0007', $datosDecoded[2]['codigo_club']);
    }

    // Caso 8: Rechazo de inclusión cuando el arreglo de artículos está vacío
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-08')]
    public function testIncluirArticulosArregloVacio(): void
    {
        // Arrange
        $modelo = new ModeloArticulosInventario();
        $datos = [
            'accion'    => 'incluir',
            'articulos' => []
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $codigoEsperado = defined('_ERR_VACIO_') ? _ERR_VACIO_ : 'ERR_VACIO';
        $this->assertEquals($codigoEsperado, $resultado['codigo']);
    }

    // Caso 9: Error de base de datos durante la inserción con rollback transaccional
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-09')]
    public function testIncluirErrorBaseDeDatosEnInsercion(): void
    {
        // Arrange
        $stmtMax = $this->createMock(PDOStatement::class);
        $stmtMax->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['max_num' => 10]);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willThrowException(new PDOException('Error fatal en INSERT SQL'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('query')->willReturn($stmtMax);
        $pdoMock->method('prepare')->willReturn($stmtInsert);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'      => 'incluir',
            'id_catalogo' => '2',
            'id_estado'   => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $codigoEsperado = defined('_ERR_BD_') ? _ERR_BD_ : 'ERR_BD';
        $this->assertEquals($codigoEsperado, $resultado['codigo']);
    }
}

