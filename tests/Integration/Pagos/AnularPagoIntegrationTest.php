<?php

namespace tests\Integration\Pagos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPagos;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
class AnularPagoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloPagos $modelo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=cannibalsbd2;charset=utf8', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $this->pdo);

        $this->modelo = new ModeloPagos();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE vueltos;");
        $this->pdo->exec("TRUNCATE TABLE detalles_pagos;");
        $this->pdo->exec("TRUNCATE TABLE pagos;");
        $this->pdo->exec("TRUNCATE TABLE cargos;");
        $this->pdo->exec("TRUNCATE TABLE conceptos;");
        $this->pdo->exec("TRUNCATE TABLE metodos_pago;");
        $this->pdo->exec("TRUNCATE TABLE monedas;");
        $this->pdo->exec("TRUNCATE TABLE tasa_cambios;");
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
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES 
            (1, 'Bolívar', 'VES', 'Bs', 2, 1), 
            (2, 'Dólar', 'USD', '$', 1, 1)");

        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) VALUES 
            (1, 'Transferencia', 1, 1), 
            (2, 'Pago Móvil', 1, 1), 
            (3, 'Efectivo', 0, 1)");

        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) VALUES 
            (1, 'Mensualidad', 30.00, 'M', 0, 1), 
            (2, 'Inscripción', 20.00, 'A', 0, 1)");

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg')");

        $this->pdo->exec("INSERT INTO cargos (codigo_cargo, codigo_concepto, codigo_atleta, monto_total, fecha_emision, estatus, codigo_moneda) VALUES 
            (1, 1, 1, 30.00, CURDATE(), 1, 2),
            (2, 2, 1, 20.00, CURDATE(), 1, 2)");
    }

    public function testAnularPagoExitoso(): void
    {
        // 1. Registramos pago de $30 USD
        $resPago = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 30.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '123456'
        ]);
        $idPago = $resPago['id_pago'];

        // 2. Anulamos el pago
        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => $idPago
        ]);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertNotEmpty($resultado['desc_bitacora']);

        // Verificamos estatus 2 en pagos
        $stmtPago = $this->pdo->query("SELECT estatus FROM pagos WHERE codigo_pago = $idPago");
        $this->assertEquals(2, (int)$stmtPago->fetchColumn(), 'El pago debe quedar con estatus = 2 (anulado).');
    }

    public function testAnularPagoRestauraCargoAPendiente(): void
    {
        // 1. Pago liquida cargo 1
        $resPago = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 30.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '223344'
        ]);
        $idPago = $resPago['id_pago'];

        // Confirmamos que el cargo fue liquidado (estatus = 2)
        $stmtCargo = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(2, (int)$stmtCargo->fetchColumn());

        // 2. Anulamos el pago
        $this->modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => $idPago
        ]);

        // 3. Verificamos que el cargo volvió a estatus = 1 (pendiente)
        $stmtCargoPost = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(1, (int)$stmtCargoPost->fetchColumn(), 'El cargo debió volver a estatus = 1.');

        // 4. Verificamos que la deuda pendiente en la vista vuelve a ser 30.00
        $stmtVista = $this->pdo->query("SELECT monto_pendiente FROM vista_cargos WHERE id_cobrar = 1");
        $this->assertEquals(30.00, (float)$stmtVista->fetchColumn());
    }

    public function testAnularPagoMultipleRestauraTodosLosCargos(): void
    {
        // Pago que cubrió Cargo 1 ($30) y Cargo 2 ($20) por un total de $50 USD
        $resPago = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1, 2],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 50.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '556677'
        ]);
        $idPago = $resPago['id_pago'];

        // Ambos cargos están pagados (2)
        $stmt1 = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(2, (int)$stmt1->fetchColumn());
        $stmt2 = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 2");
        $this->assertEquals(2, (int)$stmt2->fetchColumn());

        // Anular el pago
        $resAnular = $this->modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => $idPago
        ]);
        $this->assertEquals('exito', $resAnular['accion']);

        // Ambos cargos deben volver a estatus 1 (pendiente)
        $stmt1Post = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(1, (int)$stmt1Post->fetchColumn());

        $stmt2Post = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 2");
        $this->assertEquals(1, (int)$stmt2Post->fetchColumn());
    }

    public function testRechazoAlAnularPagoYaAnulado(): void
    {
        $resPago = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 30.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '998877'
        ]);
        $idPago = $resPago['id_pago'];

        // Primera anulación exitosa
        $res1 = $this->modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => $idPago
        ]);
        $this->assertEquals('exito', $res1['accion']);

        // Segunda anulación debe fallar
        $res2 = $this->modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => $idPago
        ]);

        $this->assertEquals('error', $res2['accion']);
        $this->assertStringContainsString('ya se encuentra anulado', $res2['codigo']);
    }

    public function testRechazoPorIdInexistente(): void
    {
        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => 9999
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('pago seleccionado no existe', $resultado['codigo']);
    }
}

