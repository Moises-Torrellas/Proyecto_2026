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
class ConsultasPagoIntegrationTest extends TestCase
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
            (2, 'Efectivo', 0, 1)");

        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) VALUES 
            (1, 'Mensualidad', 30.00, 'M', 0, 1),
            (2, 'Inscripción', 20.00, 'A', 0, 1)");

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg')");

        $this->pdo->exec("INSERT INTO cargos (codigo_cargo, codigo_concepto, codigo_atleta, monto_total, fecha_emision, estatus, codigo_moneda) VALUES 
            (1, 1, 1, 30.00, '2026-05-01', 1, 2),
            (2, 2, 1, 20.00, '2026-05-01', 1, 2)");
    }

    public function testConsultarListaPagosAgrupados(): void
    {
        // Registrar un pago con múltiples cargos
        $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1, 2],
            'metodo'     => 1,
            'moneda'     => 2,
            'monto'      => 40.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '112233'
        ]);

        $resultado = $this->modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $pago = $resultado['datos'][0];
        $this->assertEquals(40.00, (float)$pago['monto_pagado']);
        $this->assertEquals('Pago Múltiple', $pago['concepto_pago']);
        $this->assertCount(2, $pago['detalles'], 'Debe agrupar los 2 detalles en el mismo registro de pago.');
    }

    public function testConsultarReporteFiltros(): void
    {
        // Pago 1: Efectivo
        $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 2, // Efectivo
            'moneda'     => 2,
            'monto'      => 15.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d')
        ]);

        // Pago 2: Transferencia
        $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 1, // Transferencia
            'moneda'     => 2,
            'monto'      => 15.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d'),
            'referencia' => '445566'
        ]);

        // Generar reporte filtrando solo método 2 (Efectivo)
        $resReporte = $this->modelo->ProcesarDatos([
            'accion' => 'generar',
            'metodo' => 2
        ]);

        $this->assertEquals('consultar', $resReporte['accion']);
        $this->assertCount(1, $resReporte['datos']);
        $this->assertEquals(15.00, (float)$resReporte['datos'][0]['monto_pagado']);
        $this->assertEquals('Efectivo', $resReporte['datos'][0]['nombre_metodo_pago']);
    }

    public function testConsultarReporteExcluyeAnuladosPorDefecto(): void
    {
        // 1. Pago activo
        $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 2,
            'moneda'     => 2,
            'monto'      => 10.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d')
        ]);

        // 2. Pago que luego se anula
        $res2 = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [2],
            'metodo'     => 2,
            'moneda'     => 2,
            'monto'      => 20.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d')
        ]);
        $idPago2 = $res2['id_pago'];

        $this->modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => $idPago2
        ]);

        // Reporte por defecto (excluye anulados)
        $reporteActivos = $this->modelo->ProcesarDatos([
            'accion' => 'generar'
        ]);
        $this->assertCount(1, $reporteActivos['datos']);

        // Reporte incluyendo anulados
        $reporteTodos = $this->modelo->ProcesarDatos([
            'accion'   => 'generar',
            'anulados' => 1
        ]);
        $this->assertCount(2, $reporteTodos['datos']);
    }

    public function testConsultarMontoAbonadoFuncionSQL(): void
    {
        // 1. Pago de 10.00 activo
        $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 2,
            'moneda'     => 2,
            'monto'      => 10.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d')
        ]);

        $monto1 = $this->modelo->ConsultarMontoAbonado(1, $this->pdo);
        $this->assertEquals(10.00, $monto1);

        // 2. Pago de 15.00 que se anula
        $resAnular = $this->modelo->ProcesarDatos([
            'accion'     => 'incluir',
            'cuenta'     => [1],
            'metodo'     => 2,
            'moneda'     => 2,
            'monto'      => 15.00,
            'tasa'       => 1.0,
            'fecha'      => date('Y-m-d')
        ]);
        $this->modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => $resAnular['id_pago']
        ]);

        // El monto abonado debe seguir siendo 10.00 (el pago anulado no se suma)
        $monto2 = $this->modelo->ConsultarMontoAbonado(1, $this->pdo);
        $this->assertEquals(10.00, $monto2);
    }
}

