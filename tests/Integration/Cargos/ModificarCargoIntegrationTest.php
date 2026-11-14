<?php

namespace tests\Integration\Cargos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCargos;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
class ModificarCargoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloCargos $modelo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=cannibalsbd2;charset=utf8', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $this->pdo);

        $this->modelo = new ModeloCargos();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE vueltos;");
        $this->pdo->exec("TRUNCATE TABLE detalles_pagos;");
        $this->pdo->exec("TRUNCATE TABLE pagos;");
        $this->pdo->exec("TRUNCATE TABLE cargos;");
        $this->pdo->exec("TRUNCATE TABLE conceptos;");
        $this->pdo->exec("TRUNCATE TABLE monedas;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        $this->cargarDatosBase();
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function cargarDatosBase(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES (1, 'Bolívar', 'VES', 'Bs', 2, 1), (2, 'Dólar', 'USD', '$', 1, 1)");
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) VALUES 
            (1, 'Mensualidad', 30.00, 'M', 0, 1), 
            (2, 'Inscripción', 25.00, 'A', 0, 1)");
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg'),
            (2, 'Carlos', 'Perez', 'H', '2010-05-15', 'foto2.jpg')");

        $this->pdo->exec("INSERT INTO cargos (codigo_cargo, codigo_concepto, codigo_atleta, monto_total, fecha_emision, estatus, codigo_moneda) 
            VALUES (1, 1, 1, 30.00, '2026-05-01', 1, 2)");
    }

    public function testModificacionExitosa(): void
    {
        $datos = [
            'accion'        => 'modificar',
            'id'            => 1,
            'id_concepto'   => 2,
            'id_atleta'     => 2,
            'monto_total'   => 45.00,
            'fecha_emision' => '2026-05-15',
            'estatus'       => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertNotEmpty($resultado['desc_bitacora']);

        $stmt = $this->pdo->query("SELECT * FROM cargos WHERE codigo_cargo = 1");
        $cargoDB = $stmt->fetch();

        $this->assertEquals(2, (int)$cargoDB['codigo_concepto']);
        $this->assertEquals(2, (int)$cargoDB['codigo_atleta']);
        $this->assertEquals(45.00, (float)$cargoDB['monto_total']);
        $this->assertEquals('2026-05-15', $cargoDB['fecha_emision']);
    }

    public function testRechazoPorIdInexistente(): void
    {
        $datos = [
            'accion'        => 'modificar',
            'id'            => 9999,
            'id_concepto'   => 1,
            'id_atleta'     => 1,
            'monto_total'   => 30.00,
            'fecha_emision' => '2026-05-01',
            'estatus'       => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    public function testModificarEstatusDirecto(): void
    {
        $ok = $this->modelo->ModificarEstatus(1, 2);
        $this->assertTrue($ok);

        $stmt = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(2, (int)$stmt->fetchColumn());
    }

    public function testModificarEstatusConConexionExterna(): void
    {
        $ok = $this->modelo->ModificarEstatus(1, 3, $this->pdo);
        $this->assertTrue($ok);

        $stmt = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(3, (int)$stmt->fetchColumn());
    }
}

