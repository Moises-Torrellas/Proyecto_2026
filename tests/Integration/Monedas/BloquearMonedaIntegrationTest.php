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
class BloquearMonedaIntegrationTest extends TestCase
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

    private function sembrarTresMonedasActivas(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES 
                          (1, 'Dólar', 'USD', '$', 1, 1),
                          (2, 'Bolívar', 'VES', 'Bs', 2, 1),
                          (3, 'Euro', 'EUR', '€', 2, 1)");
    }

    /**
     * Bloqueo exitoso de una moneda secundaria cuando hay más de 2 monedas activas.
     */
    public function testBloquearMonedaExitoso(): void
    {
        $this->sembrarTresMonedasActivas();

        $datos = [
            'accion'  => 'bloquear',
            'id'      => '3',
            'bloqueo' => 1 // Estatus actual es 1 (debe pasar a 2)
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD que pasó a estatus 2
        $stmt = $this->pdo->query("SELECT estatus FROM monedas WHERE codigo_moneda = 3");
        $this->assertEquals(2, (int)$stmt->fetchColumn(), 'La moneda debió pasar a estatus 2 (bloqueada).');
    }

    /**
     * Desbloqueo exitoso reactivando la moneda de estatus 2 a 1.
     */
    public function testDesbloquearMonedaExitoso(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES 
                          (1, 'Dólar', 'USD', '$', 1, 1),
                          (2, 'Bolívar', 'VES', 'Bs', 2, 1),
                          (3, 'Euro', 'EUR', '€', 2, 2)"); // Moneda 3 bloqueada

        $datos = [
            'accion'  => 'bloquear',
            'id'      => '3',
            'bloqueo' => 2 // Estatus actual es 2 (debe pasar a 1)
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT estatus FROM monedas WHERE codigo_moneda = 3");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'La moneda debió reactivarse con estatus 1.');
    }

    /**
     * Bloqueo rechazado si la moneda es la moneda base del sistema (VALIDATION).
     */
    public function testErrorBloquearMonedaBase(): void
    {
        $this->sembrarTresMonedasActivas();

        $datos = [
            'accion'  => 'bloquear',
            'id'      => '1', // Moneda base (base = 1)
            'bloqueo' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION, $resultado['codigo'], 'No se puede bloquear la moneda base.');
    }

    /**
     * Bloqueo rechazado si solo quedan 2 monedas activas en el sistema (VALIDATION . '2').
     */
    public function testErrorBloquearMinimoActivas(): void
    {
        // Solo 2 monedas activas
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES 
                          (1, 'Dólar', 'USD', '$', 1, 1),
                          (2, 'Bolívar', 'VES', 'Bs', 2, 1)");

        $datos = [
            'accion'  => 'bloquear',
            'id'      => '2',
            'bloqueo' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION . '2', $resultado['codigo'], 'Debe haber al menos 2 monedas activas en el sistema.');
    }

    /**
     * Rechazo ante ID inexistente (INVALID_ID).
     */
    public function testErrorBloquearIdInexistente(): void
    {
        $datos = [
            'accion'  => 'bloquear',
            'id'      => '99999',
            'bloqueo' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }
}

