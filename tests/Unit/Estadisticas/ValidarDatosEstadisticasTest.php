<?php

declare(strict_types=1);

namespace tests\Unit\Estadisticas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadisticas;
use App\modelo\ModeloHistorial;
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
    define('DUPLICATE', '010');
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
if (!defined('EMPTY_SELECTION')) {
    define('EMPTY_SELECTION', '009');
}
if (!defined('DB_CONNECTION')) {
    define('DB_CONNECTION', '500');
}

#[Group('unit')]
#[Group('unitEstadisticas')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosEstadisticasTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    // ==========================================
    // ESCENARIO 01: Validación de Datos y Preprocesamiento
    // ==========================================

    // Caso 01: Lanzar excepción cuando los datos son vacíos al procesar
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    #[Group('escenario_error')]
    public function test_deberia_lanzarExcepcion_cuando_datosSonVaciosAlProcesar(): void
    {
        // Arrange
        $modelo = new ModeloEstadisticas();

        // Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        // Act
        $modelo->ProcesarDatos([]);
    }

    // Caso 02: Lanzar excepción cuando la acción solicitada no es válida para estadísticas
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    #[Group('escenario_error')]
    public function test_deberia_lanzarExcepcion_cuando_accionNoEsValida(): void
    {
        // Arrange
        $modelo = new ModeloEstadisticas();

        // Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada para estadísticas no es válida.');

        // Act
        $modelo->ProcesarDatos([
            'accion'        => 'accion_inexistente',
            'atleta'        => 5,
            'participacion' => 2
        ]);
    }

    // Caso 03: Asignar, aplicar valores por defecto y casteos correctos de los datos de entrada
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    #[Group('escenario_exito')]
    public function test_deberia_asignarPropiedadesInternasYTipos_cuando_datosSonProcesados(): void
    {
        // Arrange
        $modelo = new ModeloEstadisticas();
        $reflector = new ReflectionClass($modelo);

        $propId             = $reflector->getProperty('id');
        $propAtleta         = $reflector->getProperty('atleta');
        $propParticipacion  = $reflector->getProperty('participacion');
        $propGoles          = $reflector->getProperty('goles');
        $propAsistencias    = $reflector->getProperty('asistencias');
        $propPenalizaciones = $reflector->getProperty('penalizaciones');
        $propGolesContra    = $reflector->getProperty('goles_c');
        $propPartidos       = $reflector->getProperty('partidos');
        $propAverage        = $reflector->getProperty('average');

        // Act - Procesamos con acción desconocida tras la asignación de propiedades
        try {
            $modelo->ProcesarDatos([
                'accion'         => 'invalido',
                'id'             => '15',
                'atleta'         => '8',
                'participacion'  => '4',
                'goles'          => '3',
                'asistencias'    => '2',
                'penalizaciones' => '1',
                'goles_c'        => '0',
                'partido'        => '5',
                'average'        => '0.60'
            ]);
        } catch (Exception $e) {
            // Se espera la excepción de acción inválida, pero las propiedades ya fueron procesadas
        }

        // Assert
        $this->assertSame(15, $propId->getValue($modelo));
        $this->assertSame(8, $propAtleta->getValue($modelo));
        $this->assertSame(4, $propParticipacion->getValue($modelo));
        $this->assertSame(3, $propGoles->getValue($modelo));
        $this->assertSame(2, $propAsistencias->getValue($modelo));
        $this->assertSame(1, $propPenalizaciones->getValue($modelo));
        $this->assertSame(0, $propGolesContra->getValue($modelo));
        $this->assertSame(5, $propPartidos->getValue($modelo));
        $this->assertSame(0.60, $propAverage->getValue($modelo));
    }

    // Caso 04: Admitir soporte transicional del campo 'torneo' como respaldo de 'participacion'
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-04')]
    #[Group('escenario_exito')]
    public function test_deberia_admitirCampoTorneoComoTransicional_cuando_noSeEnviaParticipacion(): void
    {
        // Arrange
        $modelo = new ModeloEstadisticas();
        $reflector = new ReflectionClass($modelo);
        $propParticipacion = $reflector->getProperty('participacion');

        // Act
        try {
            $modelo->ProcesarDatos([
                'accion' => 'invalido',
                'torneo' => '12',
                'atleta' => '5'
            ]);
        } catch (Exception $e) {
            // Ignorado, acción inválida
        }

        // Assert
        $this->assertSame(12, $propParticipacion->getValue($modelo));
    }

    // Caso 05: Inyectar modelos dependientes mediante setters y verificar lectura de getId
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-05')]
    #[Group('escenario_exito')]
    public function test_deberia_inyectarModelosAuxiliaresYRetornarId_cuando_seInvocanSetters(): void
    {
        // Arrange
        $modelo = new ModeloEstadisticas();
        $mockHistorial = $this->createMock(ModeloHistorial::class);
        $mockParticipaciones = $this->createMock(ModeloParticipaciones::class);

        $reflector = new ReflectionClass($modelo);
        $propHistorial = $reflector->getProperty('ModeloHistorial');
        $propParticipaciones = $reflector->getProperty('ModeloParticipaciones');
        $propId = $reflector->getProperty('id');
        $propId->setValue($modelo, 99);

        // Act
        $modelo->setHistorial($mockHistorial);
        $modelo->setParticipaciones($mockParticipaciones);

        // Assert
        $this->assertSame($mockHistorial, $propHistorial->getValue($modelo));
        $this->assertSame($mockParticipaciones, $propParticipaciones->getValue($modelo));
        $this->assertSame(99, $modelo->getId());
    }
}
