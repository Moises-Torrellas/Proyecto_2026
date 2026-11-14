<?php

declare(strict_types=1);

namespace tests\Integration\Conceptos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloConceptos;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("conceptos")]
class EliminarConceptoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloConceptos $modelo;

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

        $this->modelo = new ModeloConceptos();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE vueltos;");
        $this->pdo->exec("TRUNCATE TABLE detalles_pagos;");
        $this->pdo->exec("TRUNCATE TABLE pagos;");
        $this->pdo->exec("TRUNCATE TABLE cargos;");
        $this->pdo->exec("TRUNCATE TABLE conceptos;");
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
     * Eliminación física exitosa de un concepto sin cargos asociados.
     */
    public function testEliminarExitoso(): void
    {
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) 
                          VALUES (1, 'Uniforme Alternativo', 40.00, 'U', 0, 1)");

        $datos = [
            'accion' => 'eliminar',
            'id'     => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD que fue eliminado
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM conceptos WHERE codigo_concepto = 1");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'El concepto debió eliminarse de la tabla conceptos.');
    }

    /**
     * Bloqueo de eliminación si el concepto está referenciado en la tabla cargos (ASSOCIATES).
     */
    public function testErrorEliminarConCargosAsociados(): void
    {
        // 1. Insertamos concepto, moneda y atleta
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) VALUES (1, 'Mensualidad', 30.00, 'M', 5, 1)");
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES (2, 'Dólar', 'USD', '$', 1, 1)");
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto.jpg')");

        // 2. Insertamos un cargo asociado al concepto
        $this->pdo->exec("INSERT INTO cargos (codigo_cargo, codigo_concepto, codigo_atleta, monto_total, fecha_emision, estatus, codigo_moneda) 
                          VALUES (1, 1, 1, 30.00, CURDATE(), 1, 2)");

        // 3. Intentamos eliminar el concepto
        $datos = [
            'accion' => 'eliminar',
            'id'     => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo'], 'Debe bloquear la eliminación por cargos asociados (ASSOCIATES).');

        // Verificamos que sigue existiendo en BD
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM conceptos WHERE codigo_concepto = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * Rechazo si el ID no existe en BD (INVALID_ID).
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

