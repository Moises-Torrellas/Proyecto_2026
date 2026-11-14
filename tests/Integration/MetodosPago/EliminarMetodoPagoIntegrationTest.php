<?php

declare(strict_types=1);

namespace tests\Integration\MetodosPago;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloMetodosPago;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("metodos_pago")]
class EliminarMetodoPagoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloMetodosPago $modelo;

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

        $this->modelo = new ModeloMetodosPago();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE vueltos;");
        $this->pdo->exec("TRUNCATE TABLE detalles_pagos;");
        $this->pdo->exec("TRUNCATE TABLE pagos;");
        $this->pdo->exec("TRUNCATE TABLE cargos;");
        $this->pdo->exec("TRUNCATE TABLE conceptos;");
        $this->pdo->exec("TRUNCATE TABLE metodos_pago;");
        $this->pdo->exec("TRUNCATE TABLE monedas;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
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
     * Eliminación física exitosa de un método de pago sin pagos asociados.
     */
    public function testEliminarExitoso(): void
    {
        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) 
                          VALUES (1, 'Cheque', 1, 1)");

        $datos = [
            'accion' => 'eliminar',
            'id'     => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD que fue eliminado
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM metodos_pago WHERE codigo_metodo = 1");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'El método de pago debió ser eliminado de la tabla.');
    }

    /**
     * Bloqueo de eliminación si el método de pago está referenciado en la tabla pagos (ASSOCIATES).
     */
    public function testErrorEliminarConPagosAsociados(): void
    {
        // 1. Insertamos método de pago y moneda
        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) VALUES (1, 'Transferencia', 1, 1)");
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES (2, 'Dólar', 'USD', '$', 1, 1)");

        // 2. Insertamos un pago asociado a este método
        $this->pdo->exec("INSERT INTO pagos (codigo_pago, codigo_metodo, codigo_moneda, monto_pago, fecha, referencia, estatus) 
                          VALUES (1, 1, 2, 30.00, CURDATE(), '123456', 1)");

        // 3. Intentamos eliminar el método de pago
        $datos = [
            'accion' => 'eliminar',
            'id'     => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo'], 'Debe bloquearse con ASSOCIATES por pagos asociados.');

        // Verificamos que sigue existiendo en BD
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM metodos_pago WHERE codigo_metodo = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * Rechazo si el ID del método no existe en BD (INVALID_ID).
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
