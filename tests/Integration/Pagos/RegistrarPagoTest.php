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
class RegistrarPagoTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function cargarDatosBaseParaPrueba(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES (1, 'Bolívar', 'VES', 'Bs', 2, 1), (2, 'Dólar', 'USD', '$', 1, 1)");
        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) VALUES (1, 'Transferencia', 1, 1), (2, 'Pago Movil', 1, 1), (3, 'Efectivo', 0, 1)");
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) VALUES (1, 'Mensualidad', 30.00, 'M', 0, 1), (2, 'Inscripción', 25.00, 'A', 0, 1)");
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto.jpg')");
        
        // Cargo pendiente de 30.00 USD (estatus 1 = pendiente)
        $this->pdo->exec("INSERT INTO cargos (codigo_cargo, codigo_concepto, codigo_atleta, monto_total, fecha_emision, estatus, codigo_moneda) VALUES (1, 1, 1, 30.00, CURDATE(), 1, 2)");
    }

    public function testPagoCompletoLiquidaCargoMedianteTriggerEnBaseDeDatosReal(): void
    {
        $this->cargarDatosBaseParaPrueba();

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

        // 1. Verificamos que el pago maestro se insertó correctamente
        $stmtPago = $this->pdo->query("SELECT * FROM pagos WHERE codigo_pago = $idPago");
        $pagoDB = $stmtPago->fetch();
        $this->assertNotEmpty($pagoDB, 'El pago no se registró en la tabla pagos.');
        $this->assertEquals(30.00, (float)$pagoDB['monto_pago']);
        $this->assertEquals(1, $pagoDB['estatus']);

        // 2. Verificamos el detalle del pago insertado
        $stmtDetalle = $this->pdo->query("SELECT * FROM detalles_pagos WHERE codigo_pago = $idPago");
        $detalleDB = $stmtDetalle->fetch();
        $this->assertNotEmpty($detalleDB, 'El detalle del pago no se registró.');
        $this->assertEquals(1, $detalleDB['codigo_cargo']);
        $this->assertEquals(30.00, (float)$detalleDB['monto_abonado']);

        // 3. Verificamos el efecto del trigger trg_despues_insertar_detalle_pago:
        // Al cubrir la totalidad de la deuda (30.00), el cargo debe cambiar automáticamente a estatus 2 (pagado)
        $stmtCargo = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $cargoEstatus = (int)$stmtCargo->fetchColumn();
        $this->assertEquals(2, $cargoEstatus, 'El trigger de base de datos debió actualizar el cargo a estatus 2 (pagado).');
    }

    public function testPagoParcialMantieneCargoPendienteYActualizaMontoAbonado(): void
    {
        $this->cargarDatosBaseParaPrueba();

        // Abono de 10.00 de una deuda de 30.00
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

        // 1. El cargo debe continuar en estatus 1 (pendiente) porque no se cubrió el total
        $stmtCargo = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $cargoEstatus = (int)$stmtCargo->fetchColumn();
        $this->assertEquals(1, $cargoEstatus, 'El cargo debe mantenerse en estatus 1 (pendiente) tras un pago parcial.');

        // 2. La función SQL ObtenerMontoAbonado debe reflejar los 10.00 abonados
        $stmtAbono = $this->pdo->query("SELECT ObtenerMontoAbonado(1) as total_abonado");
        $totalAbonado = (float)$stmtAbono->fetchColumn();
        $this->assertEquals(10.00, $totalAbonado, 'El monto abonado calculado en base de datos debe ser 10.00.');
    }

    public function testPagoConExcedenteRegistraVueltoSeguroMedianteProcedimientoAlmacenado(): void
    {
        $this->cargarDatosBaseParaPrueba();

        // Se pagan 40.00 para una deuda de 30.00, generando 10.00 de vuelto
        $datos = [
            'accion'                => 'incluir',
            'cuenta'                => [1],
            'metodo'                => 1,
            'moneda'                => 2,
            'monto'                 => 40.00,
            'tasa'                  => 1.0,
            'fecha'                 => date('Y-m-d'),
            'referencia'            => '112233',
            'monto_vuelto'          => 10.00,
            'codigo_metodo_vuelto'  => 3, // Efectivo
            'codigo_moneda_vuelto'  => 2, // USD
            'referencia_vuelto'     => '998877',
            'fecha_vuelto'          => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $idPago = $resultado['id_pago'];

        // 1. El cargo se liquida
        $stmtCargo = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(2, (int)$stmtCargo->fetchColumn());

        // 2. El SP RegistrarVueltoSeguro inserta físicamente el registro en la tabla vueltos
        $stmtVuelto = $this->pdo->query("SELECT * FROM vueltos WHERE codigo_pago = $idPago");
        $vueltoDB = $stmtVuelto->fetch();

        $this->assertNotEmpty($vueltoDB, 'El vuelto no se registró en la base de datos.');
        $this->assertEquals(10.00, (float)$vueltoDB['monto_vuelto']);
        $this->assertEquals(10.00, (float)$vueltoDB['monto_base']);
        $this->assertEquals(3, (int)$vueltoDB['codigo_metodo']);
    }

    public function testAnularPagoTransaccionalRestauraEstatusPendienteDeCargos(): void
    {
        $this->cargarDatosBaseParaPrueba();

        // 1. Registramos primero un pago completo de 30.00
        $datosPago = [
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 30.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '778899'
        ];
        $resPago = $this->modelo->ProcesarDatos($datosPago);
        $this->assertEquals('exito', $resPago['accion']);
        $idPago = $resPago['id_pago'];

        // Confirmamos que el cargo se liquidó (estatus 2)
        $stmtCargo1 = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(2, (int)$stmtCargo1->fetchColumn());

        // 2. Anulamos el pago
        $datosAnular = [
            'accion' => 'eliminar',
            'id'     => $idPago
        ];
        $resAnular = $this->modelo->ProcesarDatos($datosAnular);

        $this->assertEquals('exito', $resAnular['accion']);

        // 3. Verificamos que el pago pasó a estatus 2 (anulado)
        $stmtPago = $this->pdo->query("SELECT estatus FROM pagos WHERE codigo_pago = $idPago");
        $this->assertEquals(2, (int)$stmtPago->fetchColumn(), 'El pago no se marcó como anulado (estatus 2).');

        // 4. Verificamos que la transacción restauró el cargo a estatus 1 (pendiente)
        $stmtCargo2 = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(1, (int)$stmtCargo2->fetchColumn(), 'El cargo debió volver a estatus 1 (pendiente) tras anular el pago.');
    }

    public function testPagoFallaSiCuentasSeleccionadasNoPoseenDeudaPendiente(): void
    {
        $this->cargarDatosBaseParaPrueba();

        // 1. Pagamos la totalidad de la deuda de la cuenta 1 (30.00 USD)
        $datosPrimerPago = [
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 30.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '554433'
        ];
        $res1 = $this->modelo->ProcesarDatos($datosPrimerPago);
        $this->assertEquals('exito', $res1['accion']);

        // 2. Intentamos realizar otro pago sobre la misma cuenta que ya no posee deuda
        $datosSegundoPago = [
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 30.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '990011'
        ];

        $resultado = $this->modelo->ProcesarDatos($datosSegundoPago);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('no poseen deuda pendiente', $resultado['codigo']);
    }
}
