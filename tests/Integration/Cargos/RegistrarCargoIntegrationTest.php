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
class RegistrarCargoIntegrationTest extends TestCase
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
            (2, 'Inscripción Anual', 25.00, 'A', 0, 1),
            (3, 'Uniforme Único', 50.00, 'U', 0, 1)");
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg'),
            (2, 'Carlos', 'Perez', 'H', '2010-05-15', 'foto2.jpg')");
    }

    public function testRegistroIndividualExitoso(): void
    {
        $datos = [
            'accion'        => 'incluir',
            'id_concepto'   => 1,
            'id_atleta'     => [1],
            'monto_total'   => 30.00,
            'fecha_emision' => '2026-05-01'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertNotEmpty($resultado['desc_bitacora']);

        $stmt = $this->pdo->query("SELECT * FROM cargos WHERE codigo_atleta = 1 AND codigo_concepto = 1");
        $cargoDB = $stmt->fetch();

        $this->assertNotEmpty($cargoDB, 'El cargo debe insertarse en la base de datos.');
        $this->assertEquals(1, (int)$cargoDB['estatus']);
        $this->assertEquals(30.00, (float)$cargoDB['monto_total']);
        $this->assertEquals(2, (int)$cargoDB['codigo_moneda'], 'Debe tomar la moneda base configurada (USD = 2).');
    }

    public function testRegistroMasivoExitoso(): void
    {
        $datos = [
            'accion'        => 'incluir',
            'id_concepto'   => 1,
            'id_atleta'     => [1, 2],
            'monto_total'   => 30.00,
            'fecha_emision' => '2026-06-01'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertStringContainsString('2 atleta(s)', $resultado['desc_bitacora']);

        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM cargos WHERE codigo_concepto = 1 AND fecha_emision = '2026-06-01'");
        $total = (int)$stmt->fetchColumn();
        $this->assertEquals(2, $total, 'Debieron insertarse 2 cargos en la base de datos.');
    }

    public function testRechazoPorConceptoInexistente(): void
    {
        $datos = [
            'accion'        => 'incluir',
            'id_concepto'   => 9999,
            'id_atleta'     => [1],
            'monto_total'   => 30.00,
            'fecha_emision' => '2026-05-01'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('concepto de cobro seleccionado no existe', $resultado['codigo']);
    }

    public function testRechazoPorAtletaInexistente(): void
    {
        $datos = [
            'accion'        => 'incluir',
            'id_concepto'   => 1,
            'id_atleta'     => [9999],
            'monto_total'   => 30.00,
            'fecha_emision' => '2026-05-01'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('atleta seleccionado no existe o está inactivo', $resultado['codigo']);
    }

    public function testRechazoPorFrecuenciaMensualDuplicada(): void
    {
        $datos1 = [
            'accion'        => 'incluir',
            'id_concepto'   => 1, // Mensual
            'id_atleta'     => [1],
            'monto_total'   => 30.00,
            'fecha_emision' => '2026-04-05'
        ];
        $res1 = $this->modelo->ProcesarDatos($datos1);
        $this->assertEquals('exito', $res1['accion']);

        // Intentar registrar de nuevo para el mismo mes (abril 2026)
        $datos2 = [
            'accion'        => 'incluir',
            'id_concepto'   => 1,
            'id_atleta'     => [1],
            'monto_total'   => 30.00,
            'fecha_emision' => '2026-04-20'
        ];
        $res2 = $this->modelo->ProcesarDatos($datos2);

        $this->assertEquals('error', $res2['accion']);
        $this->assertStringContainsString('ya tiene asignado este concepto para el periodo correspondiente', $res2['codigo']);
    }

    public function testRechazoPorFrecuenciaAnualDuplicada(): void
    {
        $datos1 = [
            'accion'        => 'incluir',
            'id_concepto'   => 2, // Anual
            'id_atleta'     => [1],
            'monto_total'   => 25.00,
            'fecha_emision' => '2026-01-15'
        ];
        $res1 = $this->modelo->ProcesarDatos($datos1);
        $this->assertEquals('exito', $res1['accion']);

        // Intentar registrar en otro mes pero del mismo año (2026)
        $datos2 = [
            'accion'        => 'incluir',
            'id_concepto'   => 2,
            'id_atleta'     => [1],
            'monto_total'   => 25.00,
            'fecha_emision' => '2026-11-20'
        ];
        $res2 = $this->modelo->ProcesarDatos($datos2);

        $this->assertEquals('error', $res2['accion']);
        $this->assertStringContainsString('ya tiene asignado este concepto para el periodo correspondiente', $res2['codigo']);
    }

    public function testRechazoPorFrecuenciaUnicaDuplicada(): void
    {
        $datos1 = [
            'accion'        => 'incluir',
            'id_concepto'   => 3, // Único
            'id_atleta'     => [1],
            'monto_total'   => 50.00,
            'fecha_emision' => '2025-05-10'
        ];
        $res1 = $this->modelo->ProcesarDatos($datos1);
        $this->assertEquals('exito', $res1['accion']);

        // Intentar registrar de nuevo en cualquier fecha futura
        $datos2 = [
            'accion'        => 'incluir',
            'id_concepto'   => 3,
            'id_atleta'     => [1],
            'monto_total'   => 50.00,
            'fecha_emision' => '2026-05-10'
        ];
        $res2 = $this->modelo->ProcesarDatos($datos2);

        $this->assertEquals('error', $res2['accion']);
        $this->assertStringContainsString('ya tiene asignado este concepto para el periodo correspondiente', $res2['codigo']);
    }

    public function testPermiteRegistroSiCargoPrevioEstaAnulado(): void
    {
        // 1. Registrar cargo mensual
        $datos = [
            'accion'        => 'incluir',
            'id_concepto'   => 1,
            'id_atleta'     => [1],
            'monto_total'   => 30.00,
            'fecha_emision' => '2026-07-01'
        ];
        $res1 = $this->modelo->ProcesarDatos($datos);
        $this->assertEquals('exito', $res1['accion']);

        // 2. Anular el cargo anterior (estatus = 3)
        $this->pdo->exec("UPDATE cargos SET estatus = 3 WHERE codigo_atleta = 1 AND codigo_concepto = 1 AND fecha_emision = '2026-07-01'");

        // 3. Registrar nuevamente en el mismo periodo
        $res2 = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $res2['accion'], 'Debe permitir emitir el cargo si el previo está anulado.');
    }
}

