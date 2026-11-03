<?php

namespace tests\Unit\Monedas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloMonedas;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitMonedas')]
#[Group('validarDatosMonedas')]
class ValidarDatosMonedasTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    // Caso 1: Arreglo de datos vacío lanza excepción
    public function testDatosVacios(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo = new ModeloMonedas();
        $modelo->ProcesarDatos([]);
    }

    // Caso 2: Acción inválida o no reconocida lanza excepción
    public function testAccionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        $modelo = new ModeloMonedas();
        $modelo->ProcesarDatos(['accion' => 'accion_invalida']);
    }

    // Caso 3: Formateo correcto de campos de texto (Title Case y Mayúsculas)
    public function testFormateoCampos(): void
    {
        $modelo = new ModeloMonedas();

        try {
            $modelo->ProcesarDatos([
                'id'          => 1,
                'nombre'      => '  dólar estadounidense  ',
                'abreviatura' => '  usd  ',
                'simbolo'     => '  $  ',
                'bloqueo'     => 1,
                'accion'      => 'invalida'
            ]);
        } catch (Exception $e) {
            // Se ignora la excepción de acción inválida para verificar los atributos asignados
        }

        $reflector = new ReflectionClass($modelo);

        $propId = $reflector->getProperty('id');
        $propNombre = $reflector->getProperty('nombre');
        $propAbreviatura = $reflector->getProperty('abreviatura');
        $propSimbolo = $reflector->getProperty('simbolo');
        $propBloqueo = $reflector->getProperty('bloqueo');

        $this->assertEquals(1, $propId->getValue($modelo));
        $this->assertEquals('Dólar Estadounidense', $propNombre->getValue($modelo));
        $this->assertEquals('USD', $propAbreviatura->getValue($modelo));
        $this->assertEquals('$', $propSimbolo->getValue($modelo));
        $this->assertEquals(1, $propBloqueo->getValue($modelo));
    }
}

