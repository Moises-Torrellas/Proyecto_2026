<?php

namespace tests\Unit\ArticulosInventario;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloArticulosInventario;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionMethod;
use PDO;
use PDOStatement;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitArticulosInventario')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosArticulosInventarioTest extends TestCase
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

    // Caso 1: Arreglo de datos vacío retorna código de error de datos vacíos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    public function testDatosVaciosRetornaError(): void
    {
        // Arrange
        $modelo = new ModeloArticulosInventario();
        $datos = [];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(EMPTY_SELECTION, $resultado['codigo']);
    }

    // Caso 2: Acción desconocida o inválida retorna código de error de acción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    public function testAccionInvalidaRetornaError(): void
    {
        // Arrange
        $modelo = new ModeloArticulosInventario();
        $datos = [
            'accion' => 'accion_no_permitida'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION, $resultado['codigo']);
    }

    // Caso 3: Carga exitosa de combos para selección de catálogos y estados físicos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    public function testCargarCombosExitoso(): void
    {
        // Arrange
        $catalogosMock = [
            ['id_catalogo' => 1, 'nombre' => 'Casco Bauer', 'talla' => 'L']
        ];
        $estadosMock = [
            ['id_estado' => 1, 'nombre' => 'Excelente']
        ];

        $stmtCat = $this->createMock(PDOStatement::class);
        $stmtCat->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($catalogosMock);

        $stmtEst = $this->createMock(PDOStatement::class);
        $stmtEst->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($estadosMock);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('query')->willReturnCallback(function ($sql) use ($stmtCat, $stmtEst) {
            if (str_contains($sql, 'catalogo')) {
                return $stmtCat;
            }
            return $stmtEst;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos(['accion' => 'cargar_combos']);

        // Assert
        $this->assertEquals('cargar_combos', $resultado['accion']);
        $this->assertCount(1, $resultado['catalogos']);
        $this->assertCount(1, $resultado['estados']);
        $this->assertEquals('Casco Bauer', $resultado['catalogos'][0]['nombre']);
        $this->assertEquals('Excelente', $resultado['estados'][0]['nombre']);
    }

    // Caso 4: Manejo de error de base de datos durante la carga de combos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-04')]
    public function testCargarCombosErrorBaseDeDatos(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('query')->willThrowException(new Exception('Error de conexión a la base de datos'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos(['accion' => 'cargar_combos']);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DB_CONNECTION, $resultado['codigo']);
    }

    // Caso 5: Generación y formateo correlativo de código club (CL-XXXX)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-05')]
    public function testGenerarCodigoClubCorrelativo(): void
    {
        // Arrange
        $stmtQuery = $this->createMock(PDOStatement::class);
        $stmtQuery->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn(['max_num' => 24]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('query')->willReturn($stmtQuery);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act - Invocamos GenerarCodigoClub vía Reflection
        $reflector = new ReflectionClass(ModeloArticulosInventario::class);
        $metodoGenerar = $reflector->getMethod('GenerarCodigoClub');
        $codigoGenerado = $metodoGenerar->invoke($modelo);

        $metodoFormatear = $reflector->getMethod('FormatearCodigoClub');
        $codigoFormateadoUno = $metodoFormatear->invoke($modelo, 1);

        // Assert
        $this->assertEquals('CL-0025', $codigoGenerado);
        $this->assertEquals('CL-0001', $codigoFormateadoUno);
    }
}

