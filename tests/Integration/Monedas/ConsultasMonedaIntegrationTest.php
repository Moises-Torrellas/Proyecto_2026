<?php

declare(strict_types=1);

namespace tests\Integration\Monedas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloMonedas;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("monedas")]
class ConsultasMonedaIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloMonedas $modelo;

    protected function setUp(): void
    {
        $dsn = 'mysql:host=' . (_DB_HOST_ ?: '127.0.0.1') . ';dbname=' . (_DB_NAME_ ?: 'cannibalsbd2') . ';charset=utf8';
        $this->pdo = new PDO($dsn, _DB_USER_ ?: 'root', _DB_PASS_ ?: '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $this->pdo);

        $this->modelo = new ModeloMonedas();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE monedas;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function sembrarMonedas(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES 
                          (1, 'Dólar', 'USD', '$', 1, 1),
                          (2, 'Bolívar', 'VES', 'Bs', 2, 1),
                          (3, 'Euro', 'EUR', '€', 2, 2)"); // Euro inactivo/bloqueado
    }

    /**
     * Búsqueda de moneda por ID.
     */
    public function testBuscarMonedaPorId(): void
    {
        $this->sembrarMonedas();

        $resultado = $this->modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $moneda = $resultado['datos'][0];
        $this->assertEquals(1, (int)$moneda['codigo_moneda']);
        $this->assertEquals('Dólar', $moneda['nombre']);
        $this->assertEquals('USD', $moneda['abreviatura']);
        $this->assertEquals('$', $moneda['simbolo']);
        $this->assertEquals(1, (int)$moneda['base']);
        $this->assertEquals(1, (int)$moneda['estatus']);
    }

    /**
     * Consulta con filtro de búsqueda por nombre, abreviatura o símbolo.
     */
    public function testConsultarConFiltro(): void
    {
        $this->sembrarMonedas();

        // 1. Filtrar por nombre 'Dólar'
        $resDolar = $this->modelo->Consultar(['filtro' => 'Dólar']);
        $this->assertEquals('consultar', $resDolar['accion']);
        $this->assertCount(1, $resDolar['datos']);
        $this->assertEquals('Dólar', $resDolar['datos'][0]['nombre']);

        // 2. Filtrar por abreviatura 'VES'
        $resVes = $this->modelo->Consultar(['filtro' => 'VES']);
        $this->assertCount(1, $resVes['datos']);
        $this->assertEquals('Bolívar', $resVes['datos'][0]['nombre']);

        // 3. Filtrar por símbolo '€'
        $resEuro = $this->modelo->Consultar(['filtro' => '€']);
        $this->assertCount(1, $resEuro['datos']);
        $this->assertEquals('Euro', $resEuro['datos'][0]['nombre']);

        // 4. Consulta sin filtro devuelve todas las monedas
        $resTodas = $this->modelo->Consultar([]);
        $this->assertCount(3, $resTodas['datos']);
    }

    /**
     * Consulta exclusiva de monedas activas (ConsultarMonedas).
     */
    public function testConsultarMonedasActivas(): void
    {
        $this->sembrarMonedas();

        $resultado = $this->modelo->ConsultarMonedas();

        $this->assertEquals('buscar', $resultado['accion']);
        // Solo deben retornar 2 monedas activas (USD y VES), excluyendo Euro (estatus 2)
        $this->assertCount(2, $resultado['datos']);

        foreach ($resultado['datos'] as $moneda) {
            $this->assertEquals(1, (int)$moneda['estatus']);
        }
    }

    /**
     * Obtención de la moneda base activa del sistema (obtenerMonedaBase).
     */
    public function testObtenerMonedaBase(): void
    {
        $this->sembrarMonedas();

        $resultado = $this->modelo->obtenerMonedaBase();

        $this->assertEquals('consultarMoneda', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);
        $this->assertEquals(1, (int)$resultado['datos']['codigo_moneda']);
        $this->assertEquals('Dólar', $resultado['datos']['nombre']);
        $this->assertEquals('USD', $resultado['datos']['abreviatura']);
        $this->assertEquals('$', $resultado['datos']['simbolo']);
    }
}

