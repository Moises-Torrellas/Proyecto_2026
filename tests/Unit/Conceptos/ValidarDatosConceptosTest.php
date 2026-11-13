<?php

namespace tests\Unit\Conceptos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloConceptos;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitConceptos')]
#[Group('validarDatosConceptos')]
class ValidarDatosConceptosTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    public function testDatosVacios(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo = new ModeloConceptos();
        $modelo->ProcesarDatos([]);
    }

    public function testAccionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        $modelo = new ModeloConceptos();
        $modelo->ProcesarDatos(['accion' => 'accion_invalida']);
    }

    public function testValidarCamposInvalidosLanzaExcepcion(): void
    {
        $modelo = new ModeloConceptos();
        $casos = [
            'id invalido'         => [['id' => 'abc', 'accion' => 'buscar'], 'Id inválido.'],
            'nombre invalido'     => [['nombre' => 'No#123', 'accion' => 'incluir'], 'Nombre inválido.'],
            'monto invalido'      => [['monto' => '10.999', 'accion' => 'incluir'], 'Monto inválido.'],
            'frecuencia invalida' => [['frecuencia' => 'Z', 'accion' => 'incluir'], 'Frecuencia inválido.'],
            'dias invalido'       => [['dias' => '1000', 'accion' => 'incluir'], 'dias de cargo inválido.']
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

    public function testAsignacionValoresValidos(): void
    {
        $modelo = new ModeloConceptos();

        try {
            $modelo->ProcesarDatos([
                'id'         => '10',
                'nombre'     => '  mensualidad marzo  ',
                'monto'      => '50.00',
                'estatus'    => 1,
                'frecuencia' => 'M',
                'dias'       => '5',
                'accion'     => 'invalida'
            ]);
        } catch (Exception $e) {
        }

        $reflector = new ReflectionClass($modelo);

        $this->assertEquals('10', $reflector->getProperty('id')->getValue($modelo));
        $this->assertEquals('Mensualidad Marzo', $reflector->getProperty('nombre')->getValue($modelo));
        $this->assertEquals(50.0, $reflector->getProperty('monto')->getValue($modelo));
        $this->assertEquals(1, $reflector->getProperty('estatus')->getValue($modelo));
        $this->assertEquals('M', $reflector->getProperty('frecuencia')->getValue($modelo));
        $this->assertEquals('5', $reflector->getProperty('dias')->getValue($modelo));
    }
}
