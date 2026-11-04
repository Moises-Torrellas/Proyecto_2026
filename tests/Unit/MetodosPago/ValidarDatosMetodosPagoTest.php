<?php

namespace tests\Unit\MetodosPago;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloMetodosPago;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitMetodosPago')]
#[Group('validarDatosMetodosPago')]
class ValidarDatosMetodosPagoTest extends TestCase
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

        $modelo = new ModeloMetodosPago();
        $modelo->ProcesarDatos([]);
    }

    // Caso 2: Acción inválida o no reconocida lanza excepción
    public function testAccionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        $modelo = new ModeloMetodosPago();
        $modelo->ProcesarDatos(['accion' => 'accion_invalida']);
    }

    // Caso 3: Formateo automático del nombre en formato título (Title Case)
    public function testFormateoNombreTitleCase(): void
    {
        $modelo = new ModeloMetodosPago();

        try {
            $modelo->ProcesarDatos([
                'id'             => 1,
                'nombre'         => '  pago móvil interbancario  ',
                'nec_referencia' => '1',
                'bloqueo'        => 1,
                'accion'         => 'invalida'
            ]);
        } catch (Exception $e) {
            // Se ignora la excepción de acción inválida para comprobar la propiedad
        }

        $reflector = new ReflectionClass($modelo);
        $propNombre = $reflector->getProperty('nombre');
        $propId = $reflector->getProperty('id');
        $propRef = $reflector->getProperty('nec_referencia');
        $propBloqueo = $reflector->getProperty('bloqueo');

        $this->assertEquals('Pago Móvil Interbancario', $propNombre->getValue($modelo));
        $this->assertEquals(1, $propId->getValue($modelo));
        $this->assertEquals('1', $propRef->getValue($modelo));
        $this->assertEquals(1, $propBloqueo->getValue($modelo));
    }
}

