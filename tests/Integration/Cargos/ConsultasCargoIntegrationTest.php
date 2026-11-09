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
class ConsultasCargoIntegrationTest extends TestCase
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
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES 
            (1, 'Bolívar', 'VES', 'Bs', 2, 1), 
            (2, 'Dólar', 'USD', '$', 1, 1)");

        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) VALUES 
            (1, 'Transferencia', 1, 1)");

        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) VALUES 
            (1, 'Mensualidad', 30.00, 'M', 5, 1), 
            (2, 'Inscripción', 25.00, 'A', 0, 1)");

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg'),
            (2, 'Carlos', 'Perez', 'H', '2010-05-15', 'foto2.jpg')");

        $this->pdo->exec("INSERT INTO cargos (codigo_cargo, codigo_concepto, codigo_atleta, monto_total, fecha_emision, estatus, codigo_moneda) VALUES 
            (1, 1, 1, 30.00, '2026-05-01', 1, 2),
            (2, 2, 2, 25.00, '2026-05-10', 1, 2),
            (3, 1, 2, 30.00, '2026-05-20', 2, 2)"); // Cargo pagado
    }

    public function testBuscarPorId(): void
    {
        $resultado = $this->modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(1, (int)$resultado['datos'][0]['id_cobrar']);
        $this->assertEquals('Diego', $resultado['datos'][0]['atleta_nombre']);
        $this->assertEquals('Mensualidad', $resultado['datos'][0]['concepto_nombre']);
        $this->assertEquals(30.00, (float)$resultado['datos'][0]['monto_total']);
    }

    public function testConsultarConFiltros(): void
    {
        // Filtrar por atleta 1
        $resAtleta = $this->modelo->Consultar(['atleta' => '1']);
        $this->assertEquals('consultar', $resAtleta['accion']);
        $this->assertCount(1, $resAtleta['datos']);
        $this->assertEquals(1, (int)$resAtleta['datos'][0]['id_atleta']);

        // Filtrar por concepto 2 (Inscripción)
        $resConcepto = $this->modelo->Consultar(['concepto' => 2]);
        $this->assertCount(1, $resConcepto['datos']);
        $this->assertEquals('Inscripción', $resConcepto['datos'][0]['concepto_nombre']);

        // Filtrar por rango de fechas
        $resFechas = $this->modelo->Consultar([
            'fecha_inicio' => '2026-05-05',
            'fecha_fin'    => '2026-05-15'
        ]);
        $this->assertCount(1, $resFechas['datos']);
        $this->assertEquals(2, (int)$resFechas['datos'][0]['id_cobrar']);
    }

    public function testConsultarPorTexto(): void
    {
        $resTexto = $this->modelo->Consultar(['filtro' => 'Carlos']);
        $this->assertCount(2, $resTexto['datos']);

        $resConcepto = $this->modelo->Consultar(['filtro' => 'Inscripción']);
        $this->assertCount(1, $resConcepto['datos']);
    }

    public function testConsultarCargosPendientes(): void
    {
        // Registramos un abono parcial de 10.00 para el cargo 1
        $this->pdo->exec("INSERT INTO pagos (codigo_pago, codigo_metodo, codigo_moneda, monto_pago, fecha, estatus, referencia) 
            VALUES (1, 1, 2, 10.00, '2026-05-02', 1, '123456')");
        $this->pdo->exec("INSERT INTO detalles_pagos (codigo_detalles_pagos, codigo_pago, codigo_cargo, monto_abonado, tasa_cambio) 
            VALUES (1, 1, 1, 10.00, 1.00)");

        $res = $this->modelo->ConsultarCargos();

        $this->assertEquals('buscar', $res['accion']);
        // Solo debe traer cargos pendientes (estatus = 1), cargo 3 tiene estatus = 2 por lo que no debe aparecer
        $this->assertCount(2, $res['datos']);

        // Verificar el cálculo de monto pendiente para cargo 1: 30 - 10 = 20
        $cargo1 = null;
        foreach ($res['datos'] as $item) {
            if ((int)$item['codigo_cargo'] === 1) {
                $cargo1 = $item;
                break;
            }
        }
        $this->assertNotNull($cargo1);
        $this->assertEquals(20.00, (float)$cargo1['monto_pendiente']);
    }

    public function testConsultarCargosAtrasados(): void
    {
        // Insertamos un cargo emitido en fecha pasada anterior a CURDATE()
        $this->pdo->exec("INSERT INTO cargos (codigo_cargo, codigo_concepto, codigo_atleta, monto_total, fecha_emision, estatus, codigo_moneda) 
            VALUES (4, 1, 1, 30.00, '2020-01-01', 1, 2)");

        $atrasados = $this->modelo->ConsultarAtrasados();

        $this->assertNotEmpty($atrasados);
        $codigos = array_column($atrasados, 'codigo_cargo');
        $this->assertContains(4, array_map('intval', $codigos));
    }
}

