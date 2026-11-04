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

    // Caso 1: Arreglo de datos vacío lanza excepción
    public function testDatosVacios(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo = new ModeloConceptos();
        $modelo->ProcesarDatos([]);
    }

    // Caso 2: Acción inválida o no reconocida lanza excepción
    public function testAccionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        $modelo = new ModeloConceptos();
        $modelo->ProcesarDatos(['accion' => 'accion_invalida']);
    }

    // Caso 3: Identificador inválido lanza excepción
    public function testIdInvalido(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        $modelo = new ModeloConceptos();
        $modelo->ProcesarDatos([
            'id'     => 'abc',
            'accion' => 'buscar'
        ]);
    }

    // Caso 4: Nombre con caracteres inválidos o longitud incorrecta lanza excepción
    public function testNombreInvalido(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        $modelo = new ModeloConceptos();
        $modelo->ProcesarDatos([
            'nombre' => 'No#123',
            'accion' => 'incluir'
        ]);
    }

    // Caso 5: Monto con formato no numérico lanza excepción
    public function testMontoInvalido(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Monto inválido.');

        $modelo = new ModeloConceptos();
        $modelo->ProcesarDatos([
            'monto'  => '10.999',
            'accion' => 'incluir'
        ]);
    }

    // Caso 6: Frecuencia con valor no admitido lanza excepción
    public function testFrecuenciaInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Frecuencia inválido.');

        $modelo = new ModeloConceptos();
        $modelo->ProcesarDatos([
            'frecuencia' => 'Z',
            'accion'     => 'incluir'
        ]);
    }

    // Caso 7: Días de gracia con formato inválido lanza excepción
    public function testDiasInvalido(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('dias de cargo inválido.');

        $modelo = new ModeloConceptos();
        $modelo->ProcesarDatos([
            'dias'   => '1000',
            'accion' => 'incluir'
        ]);
    }

    // Caso 8: Asignación y transformación correcta de valores válidos
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
            // Se ignora acción inválida para comprobar propiedades
        }

        $reflector = new ReflectionClass($modelo);

        $propId = $reflector->getProperty('id');
        $propNombre = $reflector->getProperty('nombre');
        $propMonto = $reflector->getProperty('monto');
        $propEstatus = $reflector->getProperty('estatus');
        $propFrecuencia = $reflector->getProperty('frecuencia');
        $propDias = $reflector->getProperty('dias');

        $this->assertEquals('10', $propId->getValue($modelo));
        $this->assertEquals('Mensualidad Marzo', $propNombre->getValue($modelo));
        $this->assertEquals(50.0, $propMonto->getValue($modelo));
        $this->assertEquals(1, $propEstatus->getValue($modelo));
        $this->assertEquals('M', $propFrecuencia->getValue($modelo));
        $this->assertEquals('5', $propDias->getValue($modelo));
    }
}

