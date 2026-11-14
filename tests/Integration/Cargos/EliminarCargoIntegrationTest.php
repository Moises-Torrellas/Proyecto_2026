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
class EliminarCargoIntegrationTest extends TestCase
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
        $this->pdo->exec("TRUNCATE TABLE metodos_pago;");
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
        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) VALUES (1, 'Transferencia', 1, 1)");
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) VALUES 
            (1, 'Mensualidad', 30.00, 'M', 0, 1)");
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg')");

        $this->pdo->exec("INSERT INTO cargos (codigo_cargo, codigo_concepto, codigo_atleta, monto_total, fecha_emision, estatus, codigo_moneda) 
            VALUES (1, 1, 1, 30.00, '2026-05-01', 1, 2)");
    }

    public function testAnulacionExitosa(): void
    {
        $datos = [
            'accion' => 'eliminar',
            'id'     => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertNotEmpty($resultado['desc_bitacora']);

        $stmt = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(3, (int)$stmt->fetchColumn(), 'El cargo anulado debe quedar con estatus = 3.');
    }

    public function testRechazoPorPagosAsociados(): void
    {
        // Asociar un pago al cargo 1
        $this->pdo->exec("INSERT INTO pagos (codigo_pago, codigo_metodo, codigo_moneda, monto_pago, fecha, estatus, referencia) 
            VALUES (1, 1, 2, 15.00, '2026-05-02', 1, '123456')");
        $this->pdo->exec("INSERT INTO detalles_pagos (codigo_detalles_pagos, codigo_pago, codigo_cargo, monto_abonado, tasa_cambio) 
            VALUES (1, 1, 1, 15.00, 1.00)");

        $datos = [
            'accion' => 'eliminar',
            'id'     => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo']);

        // El cargo debe permanecer activo (estatus 1)
        $stmt = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    public function testRechazoPorIdInexistente(): void
    {
        $datos = [
            'accion' => 'eliminar',
            'id'     => 9999
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }
}

