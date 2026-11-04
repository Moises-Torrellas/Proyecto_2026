<?php

namespace tests\Unit\Cargos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCargos;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitCargos')]
#[Group('validarDatosCargos')]
class ValidarDatosCargosTest extends TestCase
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

        $modelo = new ModeloCargos();
        $modelo->ProcesarDatos([]);
    }

    // Caso 2: Acción inválida o no reconocida lanza excepción
    public function testAccionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        $modelo = new ModeloCargos();
        $modelo->ProcesarDatos(['accion' => 'accion_inexistente']);
    }

    // Caso 3: Asignación de valores por defecto cuando no se proporcionan fechas ni montos
    public function testValoresPorDefecto(): void
    {
        $modelo = new ModeloCargos();

        try {
            $modelo->ProcesarDatos([
                'id' => 10,
                'id_concepto' => 2,
                'id_atleta' => [5],
                'accion' => 'invalida'
            ]);
        } catch (Exception $e) {
            // Ignoramos la excepción de acción inválida para comprobar propiedades asignadas
        }

        $reflector = new ReflectionClass($modelo);

        $propCargo = $reflector->getProperty('codigo_cargo');
        $propConcepto = $reflector->getProperty('codigo_concepto');
        $propAtleta = $reflector->getProperty('codigo_atleta');
        $propMonto = $reflector->getProperty('monto_total');
        $propFecha = $reflector->getProperty('fecha_emision');

        $this->assertEquals(10, $propCargo->getValue($modelo));
        $this->assertEquals(2, $propConcepto->getValue($modelo));
        $this->assertEquals([5], $propAtleta->getValue($modelo));
        $this->assertEquals(0, $propMonto->getValue($modelo));
        $this->assertEquals(date('Y-m-d'), $propFecha->getValue($modelo));
    }
}

