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
class ModificarMonedaIntegrationTest extends TestCase
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
     * Modificación exitosa de una moneda existente.
     */
    public function testModificacionExitosa(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) 
                          VALUES (1, 'Dólar', 'USD', '$', 1, 1)");

        $datos = [
            'accion'      => 'modificar',
            'id'          => '1',
            'nombre'      => 'Dólar Estadounidense',
            'abreviatura' => 'USD',
            'simbolo'     => 'US$'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD
        $stmt = $this->pdo->query("SELECT * FROM monedas WHERE codigo_moneda = 1");
        $moneda = $stmt->fetch();

        $this->assertEquals('Dólar Estadounidense', $moneda['nombre']);
        $this->assertEquals('USD', $moneda['abreviatura']);
        $this->assertEquals('Us$', $moneda['simbolo']);
    }

    /**
     * Auto-exclusión: conservar sus propios datos sin error de duplicado.
     */
    public function testModificacionConservarPropiosDatos(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) 
                          VALUES (1, 'Bolívar', 'VES', 'Bs', 2, 1)");

        // Conserva nombre, abreviatura y símbolo
        $datos = [
            'accion'      => 'modificar',
            'id'          => '1',
            'nombre'      => 'Bolívar',
            'abreviatura' => 'VES',
            'simbolo'     => 'Bs'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
    }

    /**
     * Rechazo al intentar modificar hacia el nombre de otra moneda.
     */
    public function testErrorModificarNombreAjeno(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES 
                          (1, 'Dólar', 'USD', '$', 1, 1),
                          (2, 'Bolívar', 'VES', 'Bs', 2, 1)");

        // Moneda 2 intenta usar el nombre de moneda 1
        $datos = [
            'accion'      => 'modificar',
            'id'          => '2',
            'nombre'      => 'Dólar', // Conflicto
            'abreviatura' => 'VES',
            'simbolo'     => 'Bs'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT nombre FROM monedas WHERE codigo_moneda = 2");
        $this->assertEquals('Bolívar', $stmt->fetchColumn());
    }

    /**
     * Rechazo al intentar modificar hacia la abreviatura de otra moneda.
     */
    public function testErrorModificarAbreviaturaAjena(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES 
                          (1, 'Dólar', 'USD', '$', 1, 1),
                          (2, 'Bolívar', 'VES', 'Bs', 2, 1)");

        // Moneda 2 intenta usar la abreviatura de moneda 1
        $datos = [
            'accion'      => 'modificar',
            'id'          => '2',
            'nombre'      => 'Bolívar Digital',
            'abreviatura' => 'USD', // Conflicto
            'simbolo'     => 'Bs'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION . '1', $resultado['codigo']);
    }

    /**
     * Rechazo al intentar modificar hacia el símbolo de otra moneda.
     */
    public function testErrorModificarSimboloAjeno(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES 
                          (1, 'Dólar', 'USD', '$', 1, 1),
                          (2, 'Bolívar', 'VES', 'Bs', 2, 1)");

        // Moneda 2 intenta usar el símbolo de moneda 1
        $datos = [
            'accion'      => 'modificar',
            'id'          => '2',
            'nombre'      => 'Bolívar Digital',
            'abreviatura' => 'VES',
            'simbolo'     => '$' // Conflicto
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION . '2', $resultado['codigo']);
    }

    /**
     * Rechazo al intentar modificar un ID inexistente (INVALID_ID).
     */
    public function testErrorModificarIdInexistente(): void
    {
        $datos = [
            'accion'      => 'modificar',
            'id'          => '99999',
            'nombre'      => 'Moneda Fantasma',
            'abreviatura' => 'FAN',
            'simbolo'     => 'F'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }
}

