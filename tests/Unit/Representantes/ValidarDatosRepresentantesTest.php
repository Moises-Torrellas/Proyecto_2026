<?php

namespace tests\Unit\Representantes;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRepresentantes;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitRepresentantes')]
#[Group('validarRepresentantes')]
class ValidarDatosRepresentantesTest extends TestCase
{
    private function invocarValidarExpresiones(ModeloRepresentantes $modelo, array $datos): void
    {
        $reflector = new ReflectionClass(ModeloRepresentantes::class);
        $metodo = $reflector->getMethod('ValidarExpresiones');
        $metodo->invoke($modelo, $datos);
    }

    public function testDatosVacios(): void
    {
        $modelo = new ModeloRepresentantes();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo->ProcesarDatos([]);
    }

    public function testAccionInvalida(): void
    {
        $modelo = new ModeloRepresentantes();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        $modelo->ProcesarDatos(['accion' => 'accion_inexistente']);
    }

    public function testValidarCamposInvalidosLanzaExcepcion(): void
    {
        $modelo = new ModeloRepresentantes();
        $casos = self::obtenerCasosInvalidos();

        foreach ($casos as $nombre => [$datos, $mensajeEsperado]) {
            try {
                $modelo->ProcesarDatos($datos);
                $this->fail("Se esperaba excepción en caso [$nombre] y no se lanzó.");
            } catch (Exception $e) {
                $this->assertEquals($mensajeEsperado, $e->getMessage(), "Mensaje incorrecto en [$nombre]");
            }
        }
    }

    public function testValidarCamposValidosEnGenerar(): void
    {
        $modelo = new ModeloRepresentantes();
        $casos = self::obtenerCasosValidosGenerar();

        foreach ($casos as $nombre => [$datos]) {
            $this->invocarValidarExpresiones($modelo, $datos);
            $this->addToAssertionCount(1);
        }
    }

    private static function obtenerCasosInvalidos(): array
    {
        return [
            'id invalido'           => [['accion' => 'incluir', 'id' => 'abc'], 'Id inválido.'],
            'cedula corta'          => [['accion' => 'incluir', 'cedula' => '123456'], 'Cédula inválida.'],
            'cedula con letras'     => [['accion' => 'incluir', 'cedula' => '1234567A'], 'Cédula inválida.'],
            'nombre corto'          => [['accion' => 'incluir', 'nombre' => 'Al'], 'Nombre inválido.'],
            'nombre con numeros'    => [['accion' => 'incluir', 'nombre' => 'Carlos123'], 'Nombre inválido.'],
            'apellido corto'        => [['accion' => 'incluir', 'apellido' => 'Pe'], 'Apellido inválido.'],
            'apellido con numeros'  => [['accion' => 'incluir', 'apellido' => 'Perez99'], 'Apellido inválido.'],
            'telefono sin guion'    => [['accion' => 'incluir', 'telefono' => '04141234567'], 'Teléfono inválido.'],
            'direccion corta'       => [['accion' => 'incluir', 'direccion' => 'Casa'], 'Dirección inválida.'],
            'nacionalidad invalida' => [['accion' => 'incluir', 'nacionalidad' => 'X'], 'Nacionalidad inválida. Solo se permite V, E o P.'],
            'correo invalido'       => [['accion' => 'incluir', 'correo' => 'correo_sin_arroba'], 'Correo inválido.'],
            'instagram invalido'    => [['accion' => 'incluir', 'instagram' => '@usuario invalido!'], 'Instagram inválido.']
        ];
    }

    private static function obtenerCasosValidosGenerar(): array
    {
        return [
            'cedula en generar'    => [['accion' => 'generar', 'cedula' => '123']],
            'nombre en generar'    => [['accion' => 'generar', 'nombre' => 'C']],
            'apellido en generar'  => [['accion' => 'generar', 'apellido' => 'P']],
            'telefono en generar'  => [['accion' => 'generar', 'telefono' => '0414']]
        ];
    }
}
