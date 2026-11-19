<?php

namespace tests\Unit\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitPremios')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosPremiosTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    // Caso 01: Rechazo de datos vacíos en ProcesarDatos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    public function testDatosVaciosLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloPremios();

        // Assert & Act
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo->ProcesarDatos([]);
    }

    // Caso 02: Rechazo de acción no permitida o desconocida
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    public function testAccionInvalidaLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloPremios();

        // Assert & Act
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada no es válida para Premios.');

        $modelo->ProcesarDatos([
            'accion' => 'accion_inexistente',
            'nombre' => 'Mejor Jugador',
            'tipo'   => 'I'
        ]);
    }

    // Caso 03: Normalización del nombre a Title Case y aplicación de trim
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    public function testNormalizacionNombreFormatoTitleCase(): void
    {
        // Arrange
        $modelo = new ModeloPremios();
        $reflector = new ReflectionClass($modelo);
        $propNombre = $reflector->getProperty('nombre');
        $propTipo = $reflector->getProperty('tipo');

        // Act - Se simula la captura previa a ejecutar una acción
        try {
            $modelo->ProcesarDatos([
                'accion' => 'accion_invalida',
                'nombre' => '   campeón de goleo individual   ',
                'tipo'   => '  I  '
            ]);
        } catch (Exception $e) {
            // Se espera la excepción de acción inválida, pero las propiedades ya fueron procesadas
        }

        // Assert
        $this->assertEquals('Campeón De Goleo Individual', $propNombre->getValue($modelo));
        $this->assertEquals('I', $propTipo->getValue($modelo));
    }
}
