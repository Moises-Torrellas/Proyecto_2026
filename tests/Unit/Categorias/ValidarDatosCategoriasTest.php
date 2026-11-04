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
    // Caso 1: Datos vacíos al procesar
    public function testDatosVacios(): void
    {
        $modelo = new ModeloCategorias();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo->ProcesarDatos([]);
    }

    // Caso 2: Id con caracteres no numéricos
    public function testIdInvalido(): void
    {
        $modelo = new ModeloCategorias();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'id' => '1a']);
    }

    // Caso 3: Nombre con menos de 2 caracteres
    public function testNombreCorto(): void
    {
        $modelo = new ModeloCategorias();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre de categoría inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'nombre' => 'A']);
    }

    // Caso 4: Nombre con más de 30 caracteres
    public function testNombreLargo(): void
    {
        $modelo = new ModeloCategorias();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre de categoría inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'nombre' => str_repeat('A', 31)]);
    }

    // Caso 5: Nombre con caracteres no permitidos
    public function testNombreCaracteresInvalidos(): void
    {
        $modelo = new ModeloCategorias();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre de categoría inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'nombre' => 'Sub-15!']);
    }

    // Caso 6: Edad mínima con caracteres no numéricos
    public function testEdadMinInvalida(): void
    {
        $modelo = new ModeloCategorias();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Edad mínima inválida.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'edad_min' => 'abc']);
    }

    // Caso 7: Edad máxima con caracteres no numéricos
    public function testEdadMaxInvalida(): void
    {
        $modelo = new ModeloCategorias();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Edad máxima inválida.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'edad_max' => 'xyz']);
    }

    // Caso 8: Edad mínima mayor que la edad máxima
    public function testEdadMinMayorQueMax(): void
    {
        $modelo = new ModeloCategorias();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La edad mínima no puede ser mayor que la edad máxima.');

        $modelo->ProcesarDatos([
            'accion'   => 'incluir',
            'edad_min' => '15',
            'edad_max' => '12'
        ]);
    }

    // Caso 9: Acción no válida
    public function testAccionInvalida(): void
    {
        $modelo = new ModeloCategorias();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción no es válida');

        $modelo->ProcesarDatos(['accion' => 'invalida', 'nombre' => 'Sub-15']);
    }
}

