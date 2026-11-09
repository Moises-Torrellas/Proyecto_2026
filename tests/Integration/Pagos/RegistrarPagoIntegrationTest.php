<?php

namespace tests\Integration\Pagos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPagos;
use App\modelo\Conexion;
use PDO;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
class RegistrarPagoIntegrationTest extends TestCase
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

        // Cargos de prueba: Cargo 1 ($30 USD), Cargo 2 ($20 USD)
        $this->pdo->exec("INSERT INTO cargos (codigo_cargo, codigo_concepto, codigo_atleta, monto_total, fecha_emision, estatus, codigo_moneda) VALUES 
            (1, 1, 1, 30.00, CURDATE(), 1, 2),
            (2, 2, 1, 20.00, CURDATE(), 1, 2)");
    }

    public function testPagoCompletoLiquidaCargo(): void
    {
        $datos = [
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 30.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '123456'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertArrayHasKey('id_pago', $resultado);

        $idPago = $resultado['id_pago'];

        // Verificar inserción en pagos
        $stmtPago = $this->pdo->query("SELECT * FROM pagos WHERE codigo_pago = $idPago");
        $pagoDB = $stmtPago->fetch();
        $this->assertEquals(30.00, (float)$pagoDB['monto_pago']);
        $this->assertEquals(1, (int)$pagoDB['estatus']);

        // Verificar inserción en detalles_pagos
        $stmtDetalle = $this->pdo->query("SELECT * FROM detalles_pagos WHERE codigo_pago = $idPago");
        $detalleDB = $stmtDetalle->fetch();
        $this->assertEquals(1, (int)$detalleDB['codigo_cargo']);
        $this->assertEquals(30.00, (float)$detalleDB['monto_abonado']);

        // Verificar que el trigger trg_despues_insertar_detalle_pago liquidó el cargo (estatus = 2)
        $stmtCargo = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(2, (int)$stmtCargo->fetchColumn(), 'El cargo debe pasar a estatus = 2 (pagado).');
    }

    public function testPagoParcialMantienePendiente(): void
    {
        $datos = [
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 10.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '654321'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Cargo debe continuar en estatus 1 (pendiente)
        $stmtCargo = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(1, (int)$stmtCargo->fetchColumn());

        // Función SQL ObtenerMontoAbonado debe reflejar 10.00
        $stmtAbono = $this->pdo->query("SELECT ObtenerMontoAbonado(1) as total");
        $this->assertEquals(10.00, (float)$stmtAbono->fetchColumn());

        // Vista_cargos debe reflejar monto_pendiente = 20.00
        $stmtVista = $this->pdo->query("SELECT monto_pendiente FROM vista_cargos WHERE id_cobrar = 1");
        $this->assertEquals(20.00, (float)$stmtVista->fetchColumn());
    }

    public function testPagosAcumuladosLiquidanCargo(): void
    {
        // 1er abono: 10.00
        $res1 = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 10.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '111111'
        ]);
        $this->assertEquals('exito', $res1['accion']);

        $stmt1 = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(1, (int)$stmt1->fetchColumn(), 'Aún debe estar pendiente tras el primer abono.');

        // 2do abono: 20.00 (completa los 30.00)
        $res2 = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 20.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '222222'
        ]);
        $this->assertEquals('exito', $res2['accion']);

        $stmt2 = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(2, (int)$stmt2->fetchColumn(), 'El trigger debió actualizar a estatus = 2 al cubrir la deuda total.');
    }

    public function testPagoMultipleCargos(): void
    {
        // Pago de 45.00 cubriendo Cargo 1 ($30) y parte de Cargo 2 ($20)
        $datos = [
            'accion'     => 'incluir',
            'cuenta'     => [1, 2],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 45.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '333333'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);
        $this->assertEquals('exito', $resultado['accion']);

        $idPago = $resultado['id_pago'];

        // Se deben haber insertado 2 detalles asociados al mismo pago
        $stmtDetalles = $this->pdo->query("SELECT * FROM detalles_pagos WHERE codigo_pago = $idPago ORDER BY codigo_cargo ASC");
        $detalles = $stmtDetalles->fetchAll();

        $this->assertCount(2, $detalles);
        $this->assertEquals(30.00, (float)$detalles[0]['monto_abonado']);
        $this->assertEquals(15.00, (float)$detalles[1]['monto_abonado']);

        // Cargo 1 debe estar pagado (2)
        $stmtC1 = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(2, (int)$stmtC1->fetchColumn());

        // Cargo 2 debe seguir pendiente (1) con 5.00 de deuda restante
        $stmtC2 = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 2");
        $this->assertEquals(1, (int)$stmtC2->fetchColumn());

        $stmtAbonoC2 = $this->pdo->query("SELECT ObtenerMontoAbonado(2)");
        $this->assertEquals(15.00, (float)$stmtAbonoC2->fetchColumn());
    }

    public function testPagoMultimonedaConTasa(): void
    {
        // Cargo 2 es de $20.00 USD. Pagamos en Bs (moneda 1) con tasa 50.0.
        // Monto a pagar en Bs = 20 * 50 = 1000.00 Bs.
        $datos = [
            'accion'     => 'incluir',
            'cuenta'     => [2],
            'metodo'     => 1,
            'moneda'     => 1, // Bs
            'monto'      => 1000.00,
            'tasa'       => 50.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '444444'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);
        $this->assertEquals('exito', $resultado['accion']);

        // El monto abonado en el detalle debe ser en moneda base (20.00 USD)
        $idPago = $resultado['id_pago'];
        $stmtDetalle = $this->pdo->query("SELECT * FROM detalles_pagos WHERE codigo_pago = $idPago");
        $det = $stmtDetalle->fetch();

        $this->assertEquals(20.00, (float)$det['monto_abonado']);
        $this->assertEquals(50.0, (float)$det['tasa_cambio']);

        // Y el cargo 2 debe quedar liquidado (2)
        $stmtC2 = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 2");
        $this->assertEquals(2, (int)$stmtC2->fetchColumn());
    }

    public function testRechazoSinDeudaPendiente(): void
    {
        // Pagamos primero la totalidad de cargo 1
        $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 30.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '555555'
        ]);

        // Intentamos pagar nuevamente sobre cargo 1
        $res = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 30.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '666666'
        ]);

        $this->assertEquals('error', $res['accion']);
        $this->assertStringContainsString('no poseen deuda pendiente', $res['codigo']);
    }

    public function testRechazoPorMontoOTasaInvalida(): void
    {
        // Monto 0
        $resMontoCero = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 0.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '777777'
        ]);
        $this->assertEquals('error', $resMontoCero['accion']);
        $this->assertStringContainsString('monto del pago debe ser mayor a 0', $resMontoCero['codigo']);

        // Tasa 0
        $resTasaCero = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 10.00,
            'tasa'       => 0.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '777777'
        ]);
        $this->assertEquals('error', $resTasaCero['accion']);
        $this->assertStringContainsString('tasa de cambio debe ser mayor a 0', $resTasaCero['codigo']);
    }

    public function testRechazoPorMetodoOMonedaInexistente(): void
    {
        $resMetodo = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 9999,
            'moneda'     => 2,
            'monto'      => 10.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '888888'
        ]);
        $this->assertEquals('error', $resMetodo['accion']);

        $resMoneda = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 9999,
            'monto'      => 10.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '888888'
        ]);
        $this->assertEquals('error', $resMoneda['accion']);
    }

    public function testRechazoPorFechaFutura(): void
    {
        $fechaFutura = date('Y-m-d', strtotime('+10 days'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La fecha ingresada no puede ser futura.');

        $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 10.00,
            'tasa'       => 1.0,
            'fecha'      => $fechaFutura,
            'referencia' => '999999'
        ]);
    }

    public function testRechazoPorFechaMuyAntigua(): void
    {
        $fechaAntigua = date('Y-m-d', strtotime('-4 months'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La fecha ingresada no puede tener más de 2 meses de atraso.');

        $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 10.00,
            'tasa'       => 1.0,
            'fecha'      => $fechaAntigua,
            'referencia' => '999999'
        ]);
    }
}

