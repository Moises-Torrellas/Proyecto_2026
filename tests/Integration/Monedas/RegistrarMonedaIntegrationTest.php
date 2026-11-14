<?php

declare(strict_types=1);

namespace tests\Integration\Monedas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloMonedas;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('DUPLICATE_NAME')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("monedas")]
class RegistrarMonedaIntegrationTest extends TestCase
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
     * Inserción exitosa de una nueva moneda.
     */
    public function testRegistroExitoso(): void
    {
        $datos = [
            'accion'      => 'incluir',
            'nombre'      => 'euro europeo',
            'abreviatura' => 'eur',
            'simbolo'     => '€'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD el formateo Title Case y mayúsculas
        $stmt = $this->pdo->query("SELECT * FROM monedas WHERE abreviatura = 'EUR'");
        $moneda = $stmt->fetch();

        $this->assertNotEmpty($moneda, 'La moneda debió insertarse en la tabla monedas.');
        $this->assertEquals('Euro Europeo', $moneda['nombre']);
        $this->assertEquals('EUR', $moneda['abreviatura']);
        $this->assertEquals('€', $moneda['simbolo']);
        $this->assertEquals(2, (int)$moneda['base']); // Por defecto no base (2)
        $this->assertEquals(1, (int)$moneda['estatus']); // Por defecto activo (1)
    }

    /**
     * Rechazo por nombre duplicado (DUPLICATE_NAME).
     */
    public function testErrorNombreDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO monedas (nombre, abreviatura, simbolo, base, estatus) 
                          VALUES ('Dólar', 'USD', '$', 1, 1)");

        $datos = [
            'accion'      => 'incluir',
            'nombre'      => 'dólar', // Mismo nombre
            'abreviatura' => 'USDT',
            'simbolo'     => '₮'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM monedas");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * Rechazo por abreviatura duplicada (VALIDATION . '1').
     */
    public function testErrorAbreviaturaDuplicada(): void
    {
        $this->pdo->exec("INSERT INTO monedas (nombre, abreviatura, simbolo, base, estatus) 
                          VALUES ('Dólar', 'USD', '$', 1, 1)");

        $datos = [
            'accion'      => 'incluir',
            'nombre'      => 'Dólar Paralelo',
            'abreviatura' => 'usd', // Misma abreviatura
            'simbolo'     => 'US$'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION . '1', $resultado['codigo']);
    }

    /**
     * Rechazo por símbolo duplicado (VALIDATION . '2').
     */
    public function testErrorSimboloDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO monedas (nombre, abreviatura, simbolo, base, estatus) 
                          VALUES ('Dólar', 'USD', '$', 1, 1)");

        $datos = [
            'accion'      => 'incluir',
            'nombre'      => 'Peso Colombiano',
            'abreviatura' => 'COP',
            'simbolo'     => '$' // Mismo símbolo
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION . '2', $resultado['codigo']);
    }
}

