<?php

declare(strict_types=1);

namespace tests\Unit\Palmares;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPalmares;
use App\modelo\ModeloPremios;
use App\modelo\ModeloParticipaciones;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    $configPath = dirname(__DIR__, 3) . '/config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
}

if (!defined('DUPLICATE')) {
    define('DUPLICATE', '004');
}
if (!defined('INVALID_ID')) {
    define('INVALID_ID', '003');
}
if (!defined('ASSOCIATES')) {
    define('ASSOCIATES', '006');
}
if (!defined('VALIDATION')) {
    define('VALIDATION', '007');
}
if (!defined('DB_CONNECTION')) {
    define('DB_CONNECTION', '500');
}

#[Group('unit')]
#[Group('unitPalmares')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosPalmaresTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    // ==========================================
    // ESCENARIO 01: Control de Integridad y Validación de Datos de Entrada
    // ==========================================

    // Caso 01: Lanzar excepción cuando los datos son vacíos al procesar
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    #[Group('escenario_error')]
    public function test_deberia_lanzarExcepcion_cuando_datosSonVaciosAlProcesar(): void
    {
        // Arrange
        $modelo = new ModeloPalmares();

        // Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        // Act
        $modelo->ProcesarDatos([]);
    }

    // Caso 02: Lanzar excepción cuando la acción solicitada no es válida para Palmarés
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    #[Group('escenario_error')]
    public function test_deberia_lanzarExcepcion_cuando_accionNoEsValida(): void
    {
        // Arrange
        $modelo = new ModeloPalmares();

        // Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada para el palmarés no es válida.');

        // Act
        $modelo->ProcesarDatos([
            'accion' => 'accion_inexistente',
            'torneo' => 1,
            'premio' => 2
        ]);
    }

    // Caso 03: Asignar y almacenar correctamente las propiedades internas del modelo y verificar getId()
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    #[Group('escenario_exito')]
    public function test_deberia_asignarPropiedadesInternasYRetornarId_cuando_datosSonProcesados(): void
    {
        // Arrange
        $modelo = new ModeloPalmares();
        $reflector = new ReflectionClass($modelo);

        // Act
        try {
            $modelo->ProcesarDatos([
                'id'            => 10,
                'torneo'        => 5,
                'atleta'        => 8,
                'equipo'        => 12,
                'premio'        => 3,
                'tipo_palmares' => 'individual',
                'accion'        => 'accion_desconocida'
            ]);
        } catch (Exception $e) {
            // Se espera la excepción de acción inválida, pero las propiedades ya fueron procesadas
        }

        // Assert
        $this->assertEquals(10, $modelo->getId());
        $this->assertEquals(5, $reflector->getProperty('id_torneo')->getValue($modelo));
        $this->assertEquals(8, $reflector->getProperty('id_atleta')->getValue($modelo));
        $this->assertEquals(12, $reflector->getProperty('id_equipo')->getValue($modelo));
        $this->assertEquals(3, $reflector->getProperty('id_premio')->getValue($modelo));
        $this->assertEquals('individual', $reflector->getProperty('tipo_palmares')->getValue($modelo));
    }

    // Caso 04: Retornar código de error al incluir cuando el modelo auxiliar de Premios no está inyectado
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-04')]
    #[Group('escenario_error')]
    public function test_deberia_retornarError_cuando_modeloPremiosNoEstaConfiguradoAlIncluir(): void
    {
        // Arrange
        $modelo = new ModeloPalmares();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'torneo'        => 1,
            'premio'        => 2,
            'tipo_palmares' => 'individual',
            'atleta'        => 3
        ]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Modelos requeridos no configurados.', $resultado['codigo']);
    }

    // Caso 05: Retornar código de error al modificar cuando el modelo auxiliar de Premios no está inyectado
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-05')]
    #[Group('escenario_error')]
    public function test_deberia_retornarError_cuando_modeloPremiosNoEstaConfiguradoAlModificar(): void
    {
        // Arrange
        $modelo = new ModeloPalmares();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 1,
            'premio'        => 2,
            'tipo_palmares' => 'individual',
            'atleta'        => 3
        ]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Modelos requeridos no configurados.', $resultado['codigo']);
    }

    // Caso 06: Inyectar correctamente modelos auxiliares requeridos mediante sus setters
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-06')]
    #[Group('escenario_exito')]
    public function test_deberia_inyectarModelosAuxiliares_cuando_seLlamanSetters(): void
    {
        // Arrange
        $modelo = new ModeloPalmares();
        $reflector = new ReflectionClass($modelo);

        $mockParticipaciones = $this->createMock(ModeloParticipaciones::class);
        $mockPremios = $this->createMock(ModeloPremios::class);

        // Act
        $modelo->setModeloParticipaciones($mockParticipaciones);
        $modelo->setModeloPremios($mockPremios);

        $propPart = $reflector->getProperty('modeloParticipaciones')->getValue($modelo);
        $propPrem = $reflector->getProperty('modeloPremios')->getValue($modelo);

        // Assert
        $this->assertSame($mockParticipaciones, $propPart);
        $this->assertSame($mockPremios, $propPrem);
    }
}
