<?php

declare(strict_types=1);

namespace tests\Unit\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
use Exception;
use PHPUnit\Framework\Attributes\Group;

if (!defined('EMPTY_SELECTION')) {
    $configPath = dirname(__DIR__, 3) . '/config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
}

if (!defined('DUPLICATE_NAME')) {
    define('DUPLICATE_NAME', '004');
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
#[Group('unitPremios')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosPremiosTest extends TestCase
{
    // ==========================================
    // ESCENARIO 01: Validación de Datos de Entrada en ProcesarDatos
    // ==========================================

    // Caso 01: Lanzar excepción cuando los datos son vacíos al procesar
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    #[Group('escenario_error')]
    public function test_deberia_lanzarExcepcion_cuando_datosSonVaciosAlProcesar(): void
    {
        // Arrange
        $modelo = new ModeloPremios();

        // Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        // Act
        $modelo->ProcesarDatos([]);
    }

    // Caso 02: Lanzar excepción cuando la acción solicitada no es válida para Premios
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    #[Group('escenario_error')]
    public function test_deberia_lanzarExcepcion_cuando_accionNoEsValida(): void
    {
        // Arrange
        $modelo = new ModeloPremios();

        // Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada no es válida para Premios.');

        // Act
        $modelo->ProcesarDatos(['accion' => 'accion_desconocida']);
    }

    // Caso 03: Lanzar excepción cuando no se especifica ninguna acción en los datos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    #[Group('escenario_error')]
    public function test_deberia_lanzarExcepcion_cuando_noSeEspecificaAccionEnDatos(): void
    {
        // Arrange
        $modelo = new ModeloPremios();

        // Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada no es válida para Premios.');

        // Act
        $modelo->ProcesarDatos(['nombre' => 'Copa']);
    }
}
