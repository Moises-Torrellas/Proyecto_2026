<?php

namespace tests\Unit\Pagos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPagos;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitPagos')]
class ConsultarPagosTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPdoMock(PDO $pdoMock): ModeloPagos
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        $modeloFake = new class extends ModeloPagos {
            public static $pdoTemporal;

            public static function conex()
            {
                return self::$pdoTemporal;
            }

            public function getConexion(string $db = 'general')
            {
                return self::$pdoTemporal;
            }
        };

        $modeloFake::$pdoTemporal = $pdoMock;
        return $modeloFake;
    }

    // Caso 1: Fecha final con formato inválido
    public function testFechaFinalInvalida(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'generar',
            'fecha_f' => '31/12/2026'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Formato de fecha inválido. Use AAAA-MM-DD.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 2: Filtro de anulados con formato inválido
    public function testFiltroAnuladosInvalido(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'generar',
            'anulados' => '5'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Filtro de anulados inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 3: Consulta general sin filtros
    public function testConsultarGeneral(): void
    {
        // Arrange
        $filaPago = [
            'id_pago' => 1,
            'fecha_pago' => '2026-10-01',
            'monto_pagado' => '100.00',
            'monto_vuelto' => '0.00',
            'referencia' => '123456',
            'estatus' => 1,
            'simbolo' => '$',
            'abre' => 'USD',
            'moneda' => 'Dolar',
            'concepto_pago' => 'Mensualidad',
            'nombre_metodo_pago' => 'Transferencia',
            'fecha_cargo' => null,
            'id_detalle_pago' => 10,
            'nombre_atleta' => 'Carlos',
            'nombre_apellido' => 'Perez',
            'monto_abonado' => '100.00',
            'tasa_cambio' => '1.0',
            'simbolo_cuenta' => '$',
            'abre_cuenta' => 'USD'
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturnOnConsecutiveCalls([$filaPago], []);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['id_pago']);
        $this->assertEquals('Mensualidad', $resultado['datos'][0]['concepto_pago']);
    }

    // Caso 4: Consulta con filtro de búsqueda por texto
    public function testConsultarConFiltroTexto(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturnOnConsecutiveCalls([], []);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar(['filtro' => 'Carlos']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
    }

    // Caso 5: Error de base de datos en consulta general
    public function testConsultarFallaBaseDatos(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willThrowException(new Exception('Error de base de datos'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de base de datos', $resultado['mensaje']);
    }

    // Caso 6: Consulta de reporte con rango de fechas
    public function testReporteRangoFechas(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturnOnConsecutiveCalls([], []);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'generar',
            'fecha' => '2026-10-01',
            'fecha_f' => '2026-10-15'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
    }

    // Caso 7: Consulta de reporte con filtro por método y moneda
    public function testReporteMetodoYMoneda(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturnOnConsecutiveCalls([], []);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'generar',
            'metodo' => '1',
            'moneda' => '2'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
    }

    // Caso 8: Consulta de reporte incluyendo pagos anulados
    public function testReporteIncluyendoAnulados(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturnOnConsecutiveCalls([], []);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'generar',
            'anulados' => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
    }

    // Caso 9: Error de base de datos en consulta de reporte
    public function testReporteFallaBaseDatos(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willThrowException(new Exception('Error en reporte'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'generar'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error en reporte', $resultado['mensaje']);
    }

    // Caso 10: Agrupación de pago múltiple con varios cargos
    public function testAgruparPagoMultiple(): void
    {
        // Arrange
        $filas = [
            [
                'id_pago' => 5,
                'fecha_pago' => '2026-10-01',
                'monto_pagado' => '100.00',
                'monto_vuelto' => '0.00',
                'referencia' => '123456',
                'estatus' => 1,
                'simbolo' => '$',
                'abre' => 'USD',
                'moneda' => 'Dolar',
                'concepto_pago' => 'Mensualidad',
                'nombre_metodo_pago' => 'Transferencia',
                'fecha_cargo' => null,
                'id_detalle_pago' => 10,
                'nombre_atleta' => 'Carlos',
                'nombre_apellido' => 'Perez',
                'monto_abonado' => '50.00',
                'tasa_cambio' => '1.0',
                'simbolo_cuenta' => '$',
                'abre_cuenta' => 'USD'
            ],
            [
                'id_pago' => 5,
                'fecha_pago' => '2026-10-01',
                'monto_pagado' => '100.00',
                'monto_vuelto' => '0.00',
                'referencia' => '123456',
                'estatus' => 1,
                'simbolo' => '$',
                'abre' => 'USD',
                'moneda' => 'Dolar',
                'concepto_pago' => 'Torneo',
                'nombre_metodo_pago' => 'Transferencia',
                'fecha_cargo' => null,
                'id_detalle_pago' => 11,
                'nombre_atleta' => 'Carlos',
                'nombre_apellido' => 'Perez',
                'monto_abonado' => '50.00',
                'tasa_cambio' => '1.0',
                'simbolo_cuenta' => '$',
                'abre_cuenta' => 'USD'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturnOnConsecutiveCalls($filas, []);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Pago Múltiple', $resultado['datos'][0]['concepto_pago']);
        $this->assertCount(2, $resultado['datos'][0]['detalles']);
    }

    // Caso 11: Cálculo de vuelto esperado con tasa de cambio
    public function testCalculoVueltoConTasa(): void
    {
        // Arrange
        $filas = [
            [
                'id_pago' => 2,
                'fecha_pago' => '2026-10-01',
                'monto_pagado' => '400.00',
                'monto_vuelto' => '0.00',
                'referencia' => '123456',
                'estatus' => 1,
                'simbolo' => 'Bs',
                'abre' => 'VES',
                'moneda' => 'Bolivar',
                'concepto_pago' => 'Mensualidad',
                'nombre_metodo_pago' => 'Transferencia',
                'fecha_cargo' => null,
                'id_detalle_pago' => 20,
                'nombre_atleta' => 'Ana',
                'nombre_apellido' => 'Gomez',
                'monto_abonado' => '10.00',
                'tasa_cambio' => '36.5',
                'simbolo_cuenta' => '$',
                'abre_cuenta' => 'USD'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturnOnConsecutiveCalls($filas, []);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals(35.00, $resultado['datos'][0]['vuelto_esperado']);
    }

    // Caso 12: Formateo de concepto con fecha de cargo en español
    public function testFormateoFechaCargo(): void
    {
        // Arrange
        $filas = [
            [
                'id_pago' => 1,
                'fecha_pago' => '2026-10-01',
                'monto_pagado' => '50.00',
                'monto_vuelto' => '0.00',
                'referencia' => '123456',
                'estatus' => 1,
                'simbolo' => '$',
                'abre' => 'USD',
                'moneda' => 'Dolar',
                'concepto_pago' => 'Inscripción',
                'nombre_metodo_pago' => 'Efectivo',
                'fecha_cargo' => '2026-05-15',
                'id_detalle_pago' => 30,
                'nombre_atleta' => 'Luis',
                'nombre_apellido' => 'Diaz',
                'monto_abonado' => '50.00',
                'tasa_cambio' => '1.0',
                'simbolo_cuenta' => '$',
                'abre_cuenta' => 'USD'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturnOnConsecutiveCalls($filas, []);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertStringContainsString('15 de Mayo de 2026', $resultado['datos'][0]['concepto_pago']);
    }

    // Caso 13: Consulta de tasas disponibles
    public function testConsultarTasasDisponibles(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_tasa' => 1, 'valor_tasa' => '36.50', 'moneda' => 'Dolar', 'simbolo' => '$']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'consultar_tasas_disponibles',
            'codigo_moneda' => '1',
            'fecha' => date('Y-m-d')
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);
    }

    // Caso 14: Consulta de monto abonado exitoso
    public function testConsultarMontoAbonado(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(['total_abonado' => '45.50']);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = new ModeloPagos();

        // Act
        $resultado = $modelo->ConsultarMontoAbonado(10, $pdoMock);

        // Assert
        $this->assertEquals(45.50, $resultado);
    }

    // Caso 15: Consulta de monto abonado retorna cero ante error
    public function testConsultarMontoAbonadoFalla(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willThrowException(new Exception('Error BD'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = new ModeloPagos();

        // Act
        $resultado = $modelo->ConsultarMontoAbonado(10, $pdoMock);

        // Assert
        $this->assertEquals(0.0, $resultado);
    }
}
