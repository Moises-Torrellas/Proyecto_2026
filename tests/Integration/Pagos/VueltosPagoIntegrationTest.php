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
class VueltosPagoIntegrationTest extends TestCase
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
            (1, 'Mensualidad', 30.00, 'M', 0, 1)");

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg')");

        // Cargo 1: $30 USD pendiente
        $this->pdo->exec("INSERT INTO cargos (codigo_cargo, codigo_concepto, codigo_atleta, monto_total, fecha_emision, estatus, codigo_moneda) VALUES 
            (1, 1, 1, 30.00, CURDATE(), 1, 2)");
    }

    public function testPagoConVueltoIntegrado(): void
    {
        // Se pagan 40.00 USD para una deuda de 30.00 USD, con 10.00 USD de vuelto en efectivo
        $datos = [
            'accion'               => 'incluir',
            'cuenta'               => [1],
            'metodo'               => 1,
            'moneda'               => 2,
            'monto'                => 40.00,
            'tasa'                 => 1.0,
            'fecha'                => date('Y-m-d'),
            'referencia'           => '112233',
            'monto_vuelto'         => 10.00,
            'codigo_metodo_vuelto' => 3, // Efectivo
            'codigo_moneda_vuelto' => 2, // USD
            'referencia_vuelto'    => '998877',
            'fecha_vuelto'         => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $idPago = $resultado['id_pago'];

        // 1. Cargo queda pagado (2)
        $stmtCargo = $this->pdo->query("SELECT estatus FROM cargos WHERE codigo_cargo = 1");
        $this->assertEquals(2, (int)$stmtCargo->fetchColumn());

        // 2. Registro en tabla vueltos insertado por SP RegistrarVueltoSeguro
        $stmtVuelto = $this->pdo->query("SELECT * FROM vueltos WHERE codigo_pago = $idPago");
        $vueltoDB = $stmtVuelto->fetch();

        $this->assertNotEmpty($vueltoDB);
        $this->assertEquals(10.00, (float)$vueltoDB['monto_vuelto']);
        $this->assertEquals(10.00, (float)$vueltoDB['monto_base']);
        $this->assertEquals(3, (int)$vueltoDB['codigo_metodo']);
    }

    public function testRegistroVueltoPosterior(): void
    {
        // 1. Se registra el pago de 40.00 para la deuda de 30.00 sin entregar el vuelto de inmediato
        $resPago = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 40.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '445566'
        ]);
        $idPago = $resPago['id_pago'];

        // 2. Posteriormente se registra la entrega del vuelto mediante 'registrar_vuelto'
        $datosVuelto = [
            'accion'               => 'registrar_vuelto',
            'codigo_pago'          => $idPago,
            'codigo_metodo'        => 3,
            'codigo_moneda'        => 2,
            'monto_vuelto'         => 10.00,
            'referencia'           => '889900',
            'fecha_vuelto'         => date('Y-m-d')
        ];

        $resVuelto = $this->modelo->ProcesarDatos($datosVuelto);

        $this->assertEquals('exito_vuelto', $resVuelto['accion']);

        $stmtVuelto = $this->pdo->query("SELECT * FROM vueltos WHERE codigo_pago = $idPago");
        $vueltoDB = $stmtVuelto->fetch();
        $this->assertEquals(10.00, (float)$vueltoDB['monto_vuelto']);
    }

    public function testRechazoSiVueltoExcedeSaldoDisponible(): void
    {
        // Pago de 40 para deuda de 30 (exceso disponible = 10.00)
        $resPago = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 40.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '778899'
        ]);
        $idPago = $resPago['id_pago'];

        // Intentar registrar vuelto de 15.00
        $datosVuelto = [
            'accion'        => 'registrar_vuelto',
            'codigo_pago'   => $idPago,
            'codigo_metodo' => 3,
            'codigo_moneda' => 2,
            'monto_vuelto'  => 15.00,
            'fecha_vuelto'  => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datosVuelto);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('excede el saldo disponible restante', $resultado['mensaje']);
    }

    public function testRechazoSiNoQuedaSaldoDisponible(): void
    {
        // 1. Pago de 40 para deuda de 30 con vuelto de 10 ya entregado
        $resPago = $this->modelo->ProcesarDatos([
            'accion'               => 'incluir',
            'cuenta'               => [1],
            'metodo'               => 1,
            'moneda'               => 2,
            'monto'                => 40.00,
            'tasa'                 => 1.0,
            'fecha'                => date('Y-m-d'),
            'referencia'           => '121212',
            'monto_vuelto'         => 10.00,
            'codigo_metodo_vuelto' => 3,
            'codigo_moneda_vuelto' => 2,
            'referencia_vuelto'    => '998877',
            'fecha_vuelto'         => date('Y-m-d')
        ]);
        $idPago = $resPago['id_pago'];

        // 2. Intentar entregar otro vuelto adicional sobre ese mismo pago
        $datosVueltoExtra = [
            'accion'        => 'registrar_vuelto',
            'codigo_pago'   => $idPago,
            'codigo_metodo' => 3,
            'codigo_moneda' => 2,
            'monto_vuelto'  => 5.00,
            'referencia'    => '554433',
            'fecha_vuelto'  => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datosVueltoExtra);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Ya no queda saldo disponible para dar vuelto', $resultado['mensaje']);
    }

    public function testRechazoPorMontoVueltoInvalido(): void
    {
        // Vuelto con monto 0
        $datosVuelto = [
            'accion'        => 'registrar_vuelto',
            'codigo_pago'   => 1,
            'codigo_metodo' => 3,
            'codigo_moneda' => 2,
            'monto_vuelto'  => 0.00,
            'fecha_vuelto'  => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datosVuelto);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('monto del vuelto debe ser mayor a 0', $resultado['mensaje']);
    }
}
