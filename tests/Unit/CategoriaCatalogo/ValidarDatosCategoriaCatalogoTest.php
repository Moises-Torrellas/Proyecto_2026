<?php

namespace tests\Unit\CategoriaCatalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCategoriaCatalogo;
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
#[Group('unitCategoriaCatalogo')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosCategoriaCatalogoTest extends TestCase
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
        $modelo = new ModeloCategoriaCatalogo();
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
        $modelo = new ModeloCategoriaCatalogo();
        $datos = [
            'accion' => 'accion_no_valida',
            'nombre' => 'Cascos'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 3: Id de categoría inválido con caracteres alfabéticos lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    public function testIdCategoriaInvalidoLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloCategoriaCatalogo();
        $datos = [
            'accion'       => 'buscar',
            'id_categoria' => '12abc',
            'nombre'       => 'Cascos'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 4: Nombre de categoría no cumple formato de expresión regular lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-04')]
    public function testNombreInvalidoLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloCategoriaCatalogo();
        $datos = [
            'accion' => 'incluir',
            'nombre' => 'C' // Longitud menor a 2 caracteres
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre de categoría inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 5: Descripción inválida con caracteres no permitidos lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-05')]
    public function testDescripcionInvalidaLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloCategoriaCatalogo();
        $datos = [
            'accion'      => 'incluir',
            'nombre'      => 'Cascos',
            'descripcion' => 'Descripción #1 con @ símbolos'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Descripción inválida.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 6: Normalización de nombre a Title Case y remoción de espacios
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-06')]
    public function testConversionNombreTitleCaseYTrim(): void
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

        $modelo = new ModeloCategoriaCatalogo();
        $datos = [
            'accion'       => 'buscar',
            'id_categoria' => '1',
            'nombre'       => '   cascos de portero   ',
            'tipo_talla'   => 'Letras'
        ];

        // Act
        $modelo->ProcesarDatos($datos);

        // Assert
        $reflector = new ReflectionClass(ModeloCategoriaCatalogo::class);
        $propNombre = $reflector->getProperty('nombre');
        $valorNombre = $propNombre->getValue($modelo);

        $this->assertEquals('Cascos De Portero', $valorNombre);
    }
}

