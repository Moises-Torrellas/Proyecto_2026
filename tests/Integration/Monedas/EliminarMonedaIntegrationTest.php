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
class EliminarMonedaIntegrationTest extends TestCase
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
        $this->pdo->exec("TRUNCATE TABLE vueltos;");
        $this->pdo->exec("TRUNCATE TABLE detalles_pagos;");
        $this->pdo->exec("TRUNCATE TABLE pagos;");
        $this->pdo->exec("TRUNCATE TABLE tasa_cambios;");
        $this->pdo->exec("TRUNCATE TABLE metodos_pago;");
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

    private function sembrarMonedasBase(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES 
                          (1, 'Dólar', 'USD', '$', 1, 1),
                          (2, 'Bolívar', 'VES', 'Bs', 2, 1),
                          (3, 'Euro', 'EUR', '€', 2, 1)");
    }

    /**
     * Eliminación física exitosa de una moneda secundaria cuando hay más de 2 monedas en el sistema.
     */
    public function testEliminarExitoso(): void
    {
        $this->sembrarMonedasBase();

        $datos = [
            'accion' => 'eliminar',
            'id'     => '3'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos que se eliminó de la base de datos
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM monedas WHERE codigo_moneda = 3");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'La moneda debió eliminarse de la tabla monedas.');
    }

    /**
     * Bloqueo al intentar eliminar la moneda base del sistema (VALIDATION).
     */
    public function testErrorEliminarMonedaBase(): void
    {
        $this->sembrarMonedasBase();

        $datos = [
            'accion' => 'eliminar',
            'id'     => '1' // Moneda base (base = 1)
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION, $resultado['codigo'], 'No se permite eliminar la moneda base del sistema.');

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM monedas WHERE codigo_moneda = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * Bloqueo si el sistema solo tiene 2 monedas registradas (VALIDATION . '2').
     */
    public function testErrorEliminarMinimoMonedas(): void
    {
        // Solo 2 monedas registradas en el sistema
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES 
                          (1, 'Dólar', 'USD', '$', 1, 1),
                          (2, 'Bolívar', 'VES', 'Bs', 2, 1)");

        $datos = [
            'accion' => 'eliminar',
            'id'     => '2'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION . '2', $resultado['codigo'], 'Debe haber al menos 2 monedas en total en el sistema.');
    }

    /**
     * Bloqueo si la moneda tiene pagos asociados (ASSOCIATES).
     */
    public function testErrorEliminarConPagosAsociados(): void
    {
        $this->sembrarMonedasBase();
        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) VALUES (1, 'Transferencia', 1, 1)");

        // Insertamos pago con la moneda 3 (Euro)
        $this->pdo->exec("INSERT INTO pagos (codigo_pago, codigo_metodo, codigo_moneda, monto_pago, fecha, referencia, estatus) 
                          VALUES (1, 1, 3, 50.00, CURDATE(), '112233', 1)");

        $datos = [
            'accion' => 'eliminar',
            'id'     => '3'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo'], 'Debe bloquearse con ASSOCIATES por pagos vinculados.');
    }

    /**
     * Bloqueo si la moneda tiene vueltos asociados (ASSOCIATES . '2').
     */
    public function testErrorEliminarConVueltosAsociados(): void
    {
        $this->sembrarMonedasBase();
        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) VALUES (1, 'Efectivo', 0, 1)");
        $this->pdo->exec("INSERT INTO pagos (codigo_pago, codigo_metodo, codigo_moneda, monto_pago, fecha, estatus) 
                          VALUES (1, 1, 1, 100.00, CURDATE(), 1)");

        // Vuelto asociado a la moneda 3 (Euro)
        $this->pdo->exec("INSERT INTO vueltos (codigo_pago, codigo_metodo, codigo_moneda, monto_vuelto, fecha_vuelto, referencia, monto_base) 
                          VALUES (1, 1, 3, 10.00, CURDATE(), '', 11.00)");

        $datos = [
            'accion' => 'eliminar',
            'id'     => '3'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES . '2', $resultado['codigo'], 'Debe bloquearse con ASSOCIATES . 2 por vueltos vinculados.');
    }

    /**
     * Bloqueo si la moneda tiene tasas de cambio asociadas (ASSOCIATES . '3').
     */
    public function testErrorEliminarConTasasAsociadas(): void
    {
        $this->sembrarMonedasBase();

        // Tasa de cambio asociada a la moneda 3
        $this->pdo->exec("INSERT INTO tasa_cambios (codigo_moneda, fecha, valor_tasa, tipo) 
                          VALUES (3, CURDATE(), 1.08, 'Oficial')");

        $datos = [
            'accion' => 'eliminar',
            'id'     => '3'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES . '3', $resultado['codigo'], 'Debe bloquearse con ASSOCIATES . 3 por tasas vinculadas.');
    }

    /**
     * Rechazo ante ID inexistente (INVALID_ID).
     */
    public function testErrorEliminarIdInexistente(): void
    {
        $datos = [
            'accion' => 'eliminar',
            'id'     => '99999'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }
}
