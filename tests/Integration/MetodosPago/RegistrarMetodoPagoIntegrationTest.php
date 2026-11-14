<?php

declare(strict_types=1);

namespace tests\Integration\MetodosPago;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloMetodosPago;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('DUPLICATE_NAME')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("metodos_pago")]
class RegistrarMetodoPagoIntegrationTest extends TestCase
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
        $this->pdo->exec("TRUNCATE TABLE metodos_pago;");
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
     * Inserción exitosa de un método de pago que requiere referencia.
     */
    public function testRegistroExitoso(): void
    {
        $datos = [
            'accion'         => 'incluir',
            'nombre'         => 'transferencia bancaria',
            'nec_referencia' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD que se guardó en formato Title Case
        $stmt = $this->pdo->query("SELECT * FROM metodos_pago WHERE nombre = 'Transferencia Bancaria'");
        $metodo = $stmt->fetch();

        $this->assertNotEmpty($metodo, 'El método de pago debió registrarse en la tabla metodos_pago.');
        $this->assertEquals('Transferencia Bancaria', $metodo['nombre']);
        $this->assertEquals(1, (int)$metodo['nec_referencia']);
        $this->assertEquals(1, (int)$metodo['estatus']); // Estatus activo por defecto
    }

    /**
     * Inserción exitosa de un método de pago que no requiere referencia (ej. Efectivo).
     */
    public function testRegistroSinReferencia(): void
    {
        $datos = [
            'accion'         => 'incluir',
            'nombre'         => 'Efectivo Divisas',
            'nec_referencia' => '0'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT * FROM metodos_pago WHERE nombre = 'Efectivo Divisas'");
        $metodo = $stmt->fetch();

        $this->assertNotEmpty($metodo);
        $this->assertEquals(0, (int)$metodo['nec_referencia']);
    }

    /**
     * Rechazo por nombre duplicado en la base de datos.
     */
    public function testErrorNombreDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO metodos_pago (nombre, nec_referencia, estatus) VALUES ('Pago Movil', 1, 1)");

        $datos = [
            'accion'         => 'incluir',
            'nombre'         => 'pago movil', // Mismo nombre en minúsculas
            'nec_referencia' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM metodos_pago");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'No debe crearse un duplicado.');
    }
}

