<?php

declare(strict_types=1);

namespace tests\Integration\Monedas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloMonedas;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("monedas")]
class SeleccionarMonedaBaseIntegrationTest extends TestCase
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

    /**
     * Cambio atómico de moneda base del sistema.
     */
    public function testSeleccionarMonedaBaseExitosa(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES 
                          (1, 'Dólar', 'USD', '$', 1, 1),
                          (2, 'Bolívar', 'VES', 'Bs', 2, 1)");

        $datos = [
            'accion' => 'select',
            'id'     => '2' // Convertir Bolívar en moneda base
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos que ahora Bolívar es base 1 y Dólar es base 2
        $stmtBolivar = $this->pdo->query("SELECT base FROM monedas WHERE codigo_moneda = 2");
        $this->assertEquals(1, (int)$stmtBolivar->fetchColumn(), 'Bolívar debe ser la nueva moneda base (base = 1).');

        $stmtDolar = $this->pdo->query("SELECT base FROM monedas WHERE codigo_moneda = 1");
        $this->assertEquals(2, (int)$stmtDolar->fetchColumn(), 'Dólar debió pasar a moneda secundaria (base = 2).');
    }

    /**
     * Rechazo al intentar seleccionar como base un ID inexistente (INVALID_ID).
     */
    public function testErrorSeleccionarIdInexistente(): void
    {
        $datos = [
            'accion' => 'select',
            'id'     => '99999'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }
}

