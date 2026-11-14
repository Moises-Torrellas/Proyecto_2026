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
class ModificarMetodoPagoIntegrationTest extends TestCase
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
     * Modificación exitosa de un método de pago existente.
     */
    public function testModificacionExitosa(): void
    {
        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) 
                          VALUES (1, 'Zelle Personal', 1, 1)");

        $datos = [
            'accion'         => 'modificar',
            'id'             => '1',
            'nombre'         => 'Zelle Corporativo',
            'nec_referencia' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD
        $stmt = $this->pdo->query("SELECT * FROM metodos_pago WHERE codigo_metodo = 1");
        $metodo = $stmt->fetch();

        $this->assertEquals('Zelle Corporativo', $metodo['nombre']);
        $this->assertEquals(1, (int)$metodo['nec_referencia']);
    }

    /**
     * Auto-exclusión: conservar sus propios datos sin error de duplicado.
     */
    public function testModificacionConservarPropiosDatos(): void
    {
        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) 
                          VALUES (1, 'Pago Movil', 1, 1)");

        $datos = [
            'accion'         => 'modificar',
            'id'             => '1',
            'nombre'         => 'Pago Movil', // Mismo nombre
            'nec_referencia' => '0'           // Solo cambia nec_referencia
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT nec_referencia FROM metodos_pago WHERE codigo_metodo = 1");
        $this->assertEquals(0, (int)$stmt->fetchColumn());
    }

    /**
     * Rechazo al intentar modificar hacia el nombre de otro método existente.
     */
    public function testErrorModificarNombreAjeno(): void
    {
        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) VALUES 
                          (1, 'Transferencia', 1, 1),
                          (2, 'Pago Movil', 1, 1)");

        // Método 2 intenta usar el nombre del método 1
        $datos = [
            'accion'         => 'modificar',
            'id'             => '2',
            'nombre'         => 'Transferencia', // Conflicto
            'nec_referencia' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);

        // Verificamos que el nombre en BD no fue alterado
        $stmt = $this->pdo->query("SELECT nombre FROM metodos_pago WHERE codigo_metodo = 2");
        $this->assertEquals('Pago Movil', $stmt->fetchColumn());
    }
}

