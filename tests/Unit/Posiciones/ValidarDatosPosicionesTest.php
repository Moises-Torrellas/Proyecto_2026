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
    // Caso 1: Datos vacíos al procesar
    public function testDatosVacios(): void
    {
        $modelo = new ModeloPosiciones();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo->ProcesarDatos([]);
    }

    // Caso 2: Id con caracteres no numéricos
    public function testIdInvalido(): void
    {
        $modelo = new ModeloPosiciones();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'id' => '1a']);
    }

    // Caso 3: Nombre con menos de 3 caracteres
    public function testNombreCorto(): void
    {
        $modelo = new ModeloPosiciones();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'nombre' => 'Al']);
    }

    // Caso 4: Nombre con caracteres numéricos
    public function testNombreConNumeros(): void
    {
        $modelo = new ModeloPosiciones();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'nombre' => 'Base1']);
    }

    // Caso 5: Abreviatura menor a 2 caracteres
    public function testAbreviaturaCorta(): void
    {
        $modelo = new ModeloPosiciones();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Abreviatura inválida.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'abreviatura' => 'A']);
    }

    // Caso 6: Abreviatura mayor a 4 caracteres
    public function testAbreviaturaLarga(): void
    {
        $modelo = new ModeloPosiciones();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Abreviatura inválida.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'abreviatura' => 'ABCDE']);
    }

    // Caso 7: Abreviatura con números
    public function testAbreviaturaConNumeros(): void
    {
        $modelo = new ModeloPosiciones();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Abreviatura inválida.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'abreviatura' => 'B1']);
    }

    // Caso 8: Descripción menor a 3 caracteres
    public function testDescripcionCorta(): void
    {
        $modelo = new ModeloPosiciones();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Descripcion inválida.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'descripcion' => 'De']);
    }

    // Caso 9: Descripción con caracteres numéricos
    public function testDescripcionConNumeros(): void
    {
        $modelo = new ModeloPosiciones();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Descripcion inválida.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'descripcion' => 'Zona 1 del campo']);
    }

    // Caso 10: Acción no válida
    public function testAccionInvalida(): void
    {
        $modelo = new ModeloPosiciones();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        $modelo->ProcesarDatos(['accion' => 'accion_inexistente', 'nombre' => 'Receptor']);
    }
}

