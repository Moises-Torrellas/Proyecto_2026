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

    // Caso 1: Datos vacíos al procesar
    public function testDatosVacios(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo->ProcesarDatos([]);
    }

    // Caso 2: Id con caracteres inválidos
    public function testIdInvalido(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'id' => 'abc']);
    }

    // Caso 3: Cédula con menos de 7 dígitos
    public function testCedulaCorta(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cédula inválida.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'cedula' => '123456']);
    }

    // Caso 4: Cédula con letras
    public function testCedulaConLetras(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cédula inválida.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'cedula' => '1234567A']);
    }

    // Caso 5: Cédula corta permitida en acción generar
    public function testCedulaValidaGenerar(): void
    {
        $modelo = new ModeloRepresentantes();
        $this->invocarValidarExpresiones($modelo, ['accion' => 'generar', 'cedula' => '123']);

        $this->assertTrue(true);
    }

    // Caso 6: Nombre con menos de 3 caracteres
    public function testNombreCorto(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'nombre' => 'Al']);
    }

    // Caso 7: Nombre con números
    public function testNombreConNumeros(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'nombre' => 'Carlos123']);
    }

    // Caso 8: Nombre de un solo carácter permitido en generar
    public function testNombreValidoGenerar(): void
    {
        $modelo = new ModeloRepresentantes();
        $this->invocarValidarExpresiones($modelo, ['accion' => 'generar', 'nombre' => 'C']);

        $this->assertTrue(true);
    }

    // Caso 9: Apellido con menos de 3 caracteres
    public function testApellidoCorto(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Apellido inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'apellido' => 'Pe']);
    }

    // Caso 10: Apellido con caracteres numéricos
    public function testApellidoConNumeros(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Apellido inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'apellido' => 'Perez99']);
    }

    // Caso 11: Apellido de un solo carácter permitido en generar
    public function testApellidoValidoGenerar(): void
    {
        $modelo = new ModeloRepresentantes();
        $this->invocarValidarExpresiones($modelo, ['accion' => 'generar', 'apellido' => 'P']);

        $this->assertTrue(true);
    }

    // Caso 12: Teléfono sin formato requerido
    public function testTelefonoInvalido(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Teléfono inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'telefono' => '04141234567']);
    }

    // Caso 13: Teléfono con formato parcial permitido en generar
    public function testTelefonoValidoGenerar(): void
    {
        $modelo = new ModeloRepresentantes();
        $this->invocarValidarExpresiones($modelo, ['accion' => 'generar', 'telefono' => '0414']);

        $this->assertTrue(true);
    }

    // Caso 14: Dirección menor a 5 caracteres
    public function testDireccionCorta(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Dirección inválida.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'direccion' => 'Casa']);
    }

    // Caso 15: Nacionalidad distinta a V, E o P
    public function testNacionalidadInvalida(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nacionalidad inválida. Solo se permite V, E o P.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'nacionalidad' => 'X']);
    }

    // Caso 16: Correo con formato inválido
    public function testCorreoInvalido(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Correo inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'correo' => 'correo_sin_arroba']);
    }

    // Caso 17: Instagram con caracteres inválidos
    public function testInstagramInvalido(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Instagram inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'instagram' => '@usuario invalido!']);
    }

    // Caso 18: Acción no válida
    public function testAccionInvalida(): void
    {
        $modelo = new ModeloRepresentantes();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        $modelo->ProcesarDatos(['accion' => 'accion_inexistente']);
    }
}

