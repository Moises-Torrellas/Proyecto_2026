<?php

namespace tests\Unit\Posiciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPosiciones;
use Exception;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('unitPosiciones')]
#[Group('validarPosiciones')]
class ValidarDatosPosicionesTest extends TestCase
{
    public function testDatosVacios(): void
    {
        $modelo = new ModeloPosiciones();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo->ProcesarDatos([]);
    }

    public function testAccionInvalida(): void
    {
        $modelo = new ModeloPosiciones();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        $modelo->ProcesarDatos(['accion' => 'accion_inexistente', 'nombre' => 'Receptor']);
    }

    public function testValidarCamposInvalidosLanzaExcepcion(): void
    {
        $modelo = new ModeloPosiciones();
        $casos = [
            'id no numerico'          => [['accion' => 'incluir', 'id' => '1a'], 'Id inválido.'],
            'nombre menor a 3 car.'   => [['accion' => 'incluir', 'nombre' => 'Al'], 'Nombre inválido.'],
            'nombre con numeros'      => [['accion' => 'incluir', 'nombre' => 'Base1'], 'Nombre inválido.'],
            'abreviatura corta'       => [['accion' => 'incluir', 'abreviatura' => 'A'], 'Abreviatura inválida.'],
            'abreviatura larga'       => [['accion' => 'incluir', 'abreviatura' => 'ABCDE'], 'Abreviatura inválida.'],
            'abreviatura con numeros' => [['accion' => 'incluir', 'abreviatura' => 'B1'], 'Abreviatura inválida.'],
            'descripcion corta'       => [['accion' => 'incluir', 'descripcion' => 'De'], 'Descripcion inválida.'],
            'descripcion con numeros' => [['accion' => 'incluir', 'descripcion' => 'Zona 1 del campo'], 'Descripcion inválida.']
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
}
