<?php

namespace tests\Unit\Asignaciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloAsignaciones;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitAsignaciones')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosAsignacionesTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    // Caso 1: Arreglo de datos vacío
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    public function testDatosVacios(): void
    {
        // Arrange
        $modelo = new ModeloAsignaciones();
        $datosVacios = [];

        // Act
        $resultado = $modelo->ProcesarDatos($datosVacios);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_VACIO', $resultado['codigo']);
    }

    // Caso 2: Acción no permitida
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    public function testAccionInvalida(): void
    {
        // Arrange
        $modelo = new ModeloAsignaciones();
        $datos = [
            'accion' => 'accion_invalida'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_ACCION', $resultado['codigo']);
    }

    // Caso 3: Validación de campos con formato o valor inválido
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    public function testValidarCamposInvalidosLanzaExcepcion(): void
    {
        $modelo = new ModeloAsignaciones();
        $casos = [
            'id asignacion'        => [['accion' => 'buscar', 'id_asignacion' => 'ABC'], 'ID de asignación inválido.'],
            'codigo atleta'        => [['accion' => 'incluir', 'codigo_atleta' => 'XYZ'], 'Atleta inválido.'],
            'codigo articulo'      => [['accion' => 'incluir', 'codigo_articulo' => 'ART-1'], 'Artículo inválido.'],
            'articulos no array'   => [['accion' => 'incluir', 'articulos' => 'no_es_arreglo'], 'La lista de artículos es inválida.'],
            'item lista invalido'  => [['accion' => 'incluir', 'articulos' => ['10', 'invalido']], 'Uno de los artículos seleccionados es inválido.']
        ];

        foreach ($casos as $nombre => [$datos, $mensajeEsperado]) {
            try {
                $modelo->ProcesarDatos($datos);
                $this->fail("Se esperaba excepción en caso [$nombre] y no se lanzó.");
            } catch (Exception $e) {
                $this->assertEquals($mensajeEsperado, $e->getMessage(), "Mensaje incorrecto en [$nombre]");
            }
        }
    }

    // Caso 8: Formato de fecha de asignación inválido
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-08')]
    public function testFechaAsignacionFormatoInvalido(): void
    {
        // Arrange
        $modelo = new ModeloAsignaciones();
        $datos = [
            'accion'           => 'consultar',
            'fecha_asignacion' => '15/10/2026'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Formato de fecha de asignación inválido. Use AAAA-MM-DD.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 9: Fecha de asignación anterior al año 2024
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-09')]
    public function testFechaAsignacionAnterior2024(): void
    {
        // Arrange
        $modelo = new ModeloAsignaciones();
        $datos = [
            'accion'           => 'consultar',
            'fecha_asignacion' => '2023-12-31'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Fecha inválida. El sistema no admite registros anteriores al 2024.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 10: Fecha menor a la actual en registro nuevo (acción incluir)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-10')]
    public function testFechaAsignacionPasadaEnIncluir(): void
    {
        // Arrange
        $modelo = new ModeloAsignaciones();
        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '10',
            'fecha_asignacion' => '2024-01-01' // Menor a la fecha de hoy
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La fecha de la nueva asignación no puede ser menor al día actual.');

        // Act
        $modelo->ProcesarDatos($datos);
    }
}

