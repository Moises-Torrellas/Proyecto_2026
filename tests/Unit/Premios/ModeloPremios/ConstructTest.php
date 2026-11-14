<?php

declare(strict_types=1);

namespace tests\Unit\Premios\ModeloPremios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    $configPath = dirname(__DIR__, 4) . '/config/config.php';
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

class ConstructTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    #[Group('cu_premios_inicializar')]
    #[Group('escenario_exito')]
    public function test_deberia_inicializarPropiedadesCorrectamente_cuando_seInstanciaElModelo(): void
    {
        // Arrange & Act
        $modelo = new ModeloPremios();

        // Assert
        $reflector = new ReflectionClass($modelo);

        $propWhitelist = $reflector->getProperty('campoWhitelist');
        $whitelist = $propWhitelist->getValue($modelo);

        $propLlave = $reflector->getProperty('llavePrimaria');
        $llave = $propLlave->getValue($modelo);

        $this->assertInstanceOf(ModeloPremios::class, $modelo);
        $this->assertIsArray($whitelist);
        $this->assertArrayHasKey('nombre', $whitelist);
        $this->assertArrayHasKey('codigo_premio', $whitelist);
        $this->assertSame('nombre', $whitelist['nombre']);
        $this->assertSame('codigo_premio', $whitelist['codigo_premio']);
        $this->assertSame('codigo_premio', $llave);
    }

    #[Group('cu_premios_inicializar')]
    #[Group('escenario_limite')]
    public function test_deberia_mantenerInstanciasIndependientes_cuando_seInstancianMultiplesModelos(): void
    {
        // Arrange
        $modeloPrimero = new ModeloPremios();

        // Act
        $modeloSegundo = new ModeloPremios();

        // Assert
        $this->assertNotSame($modeloPrimero, $modeloSegundo);
        $this->assertInstanceOf(ModeloPremios::class, $modeloPrimero);
        $this->assertInstanceOf(ModeloPremios::class, $modeloSegundo);
    }

    #[Group('cu_premios_inicializar')]
    #[Group('escenario_error')]
    public function test_deberia_garantizarCamposRestringidosEnWhitelist_cuando_seVerificaConfiguracion(): void
    {
        // Arrange
        $modelo = new ModeloPremios();
        $reflector = new ReflectionClass($modelo);
        $propWhitelist = $reflector->getProperty('campoWhitelist');

        // Act
        $whitelist = $propWhitelist->getValue($modelo);

        // Assert
        $this->assertArrayNotHasKey('descripcion', $whitelist);
        $this->assertArrayNotHasKey('monto', $whitelist);
        $this->assertArrayNotHasKey('campo_invalido', $whitelist);
        $this->assertCount(2, $whitelist);
    }
}

