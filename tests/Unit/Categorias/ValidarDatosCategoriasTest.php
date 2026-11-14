<?php

namespace tests\Unit\Categorias;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCategorias;
use Exception;
use PHPUnit\Framework\Attributes\Group;

#[Group('unit')]
#[Group('unitCategorias')]
#[Group('validarCategorias')]
class ValidarDatosCategoriasTest extends TestCase
{
    public function testDatosVacios(): void
    {
        $modelo = new ModeloCategorias();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo->ProcesarDatos([]);
    }

    public function testAccionInvalida(): void
    {
        $modelo = new ModeloCategorias();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción no es válida');

        $modelo->ProcesarDatos(['accion' => 'invalida', 'nombre' => 'Sub-15']);
    }

    public function testValidarCamposInvalidosLanzaExcepcion(): void
    {
        $modelo = new ModeloCategorias();
        $casos = [
            'id no numerico'          => [['accion' => 'incluir', 'id' => '1a'], 'Id inválido.'],
            'nombre menor a 2 car.'   => [['accion' => 'incluir', 'nombre' => 'A'], 'Nombre de categoría inválido.'],
            'nombre mayor a 30 car.'  => [['accion' => 'incluir', 'nombre' => str_repeat('A', 31)], 'Nombre de categoría inválido.'],
            'nombre con caracteres'   => [['accion' => 'incluir', 'nombre' => 'Sub-15!'], 'Nombre de categoría inválido.'],
            'edad min no numerica'    => [['accion' => 'incluir', 'edad_min' => 'abc'], 'Edad mínima inválida.'],
            'edad max no numerica'    => [['accion' => 'incluir', 'edad_max' => 'xyz'], 'Edad máxima inválida.'],
            'edad min mayor que max'  => [['accion' => 'incluir', 'edad_min' => '15', 'edad_max' => '12'], 'La edad mínima no puede ser mayor que la edad máxima.']
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
