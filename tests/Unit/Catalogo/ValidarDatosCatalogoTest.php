<?php

namespace tests\Unit\Catalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCatalogo;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use PDO;
use PDOStatement;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitCatalogo')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosCatalogoTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    // Caso 1: Arreglo de datos vacío lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    public function testDatosVaciosLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloCatalogo();
        $datos = [];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 2: Acción desconocida o inválida lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    public function testAccionInvalidaLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloCatalogo();
        $datos = [
            'accion' => 'accion_no_valida',
            'nombre' => 'Casco Hockey'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 3: Id de catálogo inválido con caracteres alfanuméricos lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    public function testIdCatalogoInvalidoLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloCatalogo();
        $datos = [
            'accion'      => 'buscar',
            'id_catalogo' => '10abc',
            'nombre'      => 'Casco Hockey'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 4: Nombre no cumple con la expresión regular (menor a 3 caracteres)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-04')]
    public function testNombreInvalidoLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloCatalogo();
        $datos = [
            'accion' => 'incluir',
            'nombre' => 'AB'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 5: Id de categoría inválido con caracteres no numéricos lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-05')]
    public function testIdCategoriaInvalidoLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloCatalogo();
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Casco Hockey',
            'id_categoria' => 'cat-1'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Categoría inválida.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 6: Stock mínimo no entero lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-06')]
    public function testStockMinimoInvalidoLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloCatalogo();
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Casco Hockey',
            'id_categoria' => '1',
            'stock_minimo' => 'cinco'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Stock mínimo debe ser un número entero.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 7: Talla inválida con caracteres especiales prohibidos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-07')]
    public function testTallaInvalidaLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloCatalogo();
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Casco Hockey',
            'id_categoria' => '1',
            'talla'        => 'Talla@Pro!'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Talla inválida.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 8: Código de posición inválido con caracteres alfabéticos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-08')]
    public function testCodigoPosicionInvalidoLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloCatalogo();
        $datos = [
            'accion'          => 'incluir',
            'nombre'          => 'Casco Hockey',
            'id_categoria'    => '1',
            'codigo_posicion' => 'portero_1'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Posición inválida.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 9: Normalización de nombre a Title Case y de talla a Uppercase con trim
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-09')]
    public function testNormalizacionNombreYTalla(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $reflectorConex = new ReflectionClass(Conexion::class);
        $propiedad = $reflectorConex->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        $modelo = new ModeloCatalogo();
        $datos = [
            'accion'          => 'buscar',
            'id_catalogo'     => '1',
            'nombre'          => '   casco de arquero cannibals   ',
            'id_categoria'    => '2',
            'stock_minimo'    => '5',
            'talla'           => '   xl / l   ',
            'codigo_posicion' => '3'
        ];

        // Act
        $modelo->ProcesarDatos($datos);

        // Assert
        $reflector = new ReflectionClass(ModeloCatalogo::class);
        $propNombre = $reflector->getProperty('nombre');
        $propTalla = $reflector->getProperty('talla');

        $this->assertEquals('Casco De Arquero Cannibals', $propNombre->getValue($modelo));
        $this->assertEquals('XL / L', $propTalla->getValue($modelo));
    }
}

