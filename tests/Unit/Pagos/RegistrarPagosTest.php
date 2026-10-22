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
class RegistrarPagosTest extends TestCase
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
        };

        $modeloFake::$pdoTemporal = $pdoMock;
        return $modeloFake;
    }

    // Caso 1: Datos vacíos
    public function testDatosVacios(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datosVacios = [];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        // Act
        $modelo->ProcesarDatos($datosVacios);
    }

    // Caso 2: Monto con formato inválido
    public function testMontoInvalido(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datosInvalidos = [
            'monto' => 'monto_invalido'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Monto del pago inválido.');

        // Act
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 3: Referencia con formato inválido
    public function testReferenciaInvalida(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datosInvalidos = [
            'referencia' => '12'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La referencia del pago debe tener entre 4 y 12 dígitos numéricos.');

        // Act
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 4: Fecha futura
    public function testFechaFutura(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datosInvalidos = [
            'accion' => 'incluir',
            'fecha' => '2099-12-31'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La fecha ingresada no puede ser futura.');

        // Act
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 5: Fecha con más de 2 meses de atraso
    public function testFechaAtrasada(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datosInvalidos = [
            'accion' => 'incluir',
            'fecha' => '2020-01-01'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La fecha ingresada no puede tener más de 2 meses de atraso.');

        // Act
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 6: Fecha con formato inválido
    public function testFechaInvalida(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datosInvalidos = [
            'fecha' => '31/12/2026'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Formato de fecha inválido. Use AAAA-MM-DD.');

        // Act
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 7: Tasa de cambio con formato inválido
    public function testTasaInvalida(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datosInvalidos = [
            'tasa' => 'tasa_invalida'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tasa de cambio inválida.');

        // Act
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 8: Método de pago con formato inválido
    public function testMetodoInvalido(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datosInvalidos = [
            'metodo' => 'metodo_invalido'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Método de pago inválido.');

        // Act
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 9: Moneda con formato inválido
    public function testMonedaInvalida(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datosInvalidos = [
            'moneda' => 'moneda_invalida'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Moneda inválida.');

        // Act
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 10: Cuenta por cobrar con formato inválido
    public function testCuentaInvalida(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datosInvalidos = [
            'cuenta' => ['cuenta_invalida']
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cuenta por cobrar inválida.');

        // Act
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 11: Acción no válida
    public function testAccionInvalida(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datosInvalidos = [
            'accion' => 'accion_invalida'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion solicitada para el pago no es valida.');

        // Act
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 12: Sin cuentas seleccionadas
    public function testSinCuentasSeleccionadas(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'incluir',
            'cuenta' => []
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(EMPTY_SELECTION, $resultado['codigo']);
    }

    // Caso 13: Monto menor o igual a cero
    public function testMontoMenorOIgualACero(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'monto' => '0',
            'tasa' => '1',
            'fecha' => date('Y-m-d')
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El monto del pago debe ser mayor a 0.', $resultado['codigo']);
    }

    // Caso 14: Tasa de cambio menor o igual a cero
    public function testTasaMenorOIgualACero(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'monto' => '100.00',
            'tasa' => '0',
            'fecha' => date('Y-m-d')
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('La tasa de cambio debe ser mayor a 0.', $resultado['codigo']);
    }

    // Caso 15: Método de pago no existe en BD
    public function testMetodoNoExiste(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchColumn')->willReturn(0);
        $stmtMock->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '99',
            'moneda' => '1',
            'monto' => '100.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 16: Moneda no existe en BD
    public function testMonedaNoExiste(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('fetchColumn')->willReturnOnConsecutiveCalls(1, 0);
        $stmtMock->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '1',
            'moneda' => '99',
            'monto' => '100.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '0', $resultado['codigo']);
    }

    // Caso 17: Cuentas sin deuda pendiente
    public function testCuentasSinDeuda(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1);
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_moneda' => 1, 'nombre' => 'Dolar', 'simbolo' => '$', 'monto_total' => '100.00']
        ]);
        $stmtMock->method('fetch')->willReturn([
            'total_abonado' => '100.00'
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('10');

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '1',
            'moneda' => '1',
            'monto' => '50.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Las cuentas seleccionadas no poseen deuda pendiente.', $resultado['codigo']);
    }

    // Caso 18: Pago con monto exacto
    public function testPagoMontoExacto(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1);
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_moneda' => 1, 'nombre' => 'Dolar', 'simbolo' => '$', 'monto_total' => '100.00']
        ]);
        $stmtMock->method('fetch')->willReturn([
            'total_abonado' => '0.00'
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('25');

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '1',
            'moneda' => '1',
            'monto' => '100.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('25', $resultado['id_pago']);
        $this->assertEquals(0, $resultado['vuelto']);
    }

    // Caso 19: Pago con generación de vuelto
    public function testPagoGeneraVuelto(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1);
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_moneda' => 1, 'nombre' => 'Dolar', 'simbolo' => '$', 'monto_total' => '60.00']
        ]);
        $stmtMock->method('fetch')->willReturn([
            'total_abonado' => '0.00'
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('30');

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '1',
            'moneda' => '1',
            'monto' => '100.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('30', $resultado['id_pago']);
        $this->assertEquals(40.00, $resultado['vuelto']);
    }

    // Caso 20: Pago parcial (abono)
    public function testPagoParcial(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1);
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_moneda' => 1, 'nombre' => 'Dolar', 'simbolo' => '$', 'monto_total' => '100.00']
        ]);
        $stmtMock->method('fetch')->willReturn([
            'total_abonado' => '0.00'
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('35');

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '1',
            'moneda' => '1',
            'monto' => '40.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('35', $resultado['id_pago']);
        $this->assertEquals(0, $resultado['vuelto']);
    }

    // Caso 21: Pago a múltiples cuentas
    public function testPagoMultiplesCuentas(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1);
        $stmtMock->method('fetchAll')->willReturnOnConsecutiveCalls(
            [['codigo_moneda' => 1, 'nombre' => 'Dolar', 'simbolo' => '$']],
            [['monto_total' => '50.00']],
            [['monto_total' => '50.00']]
        );
        $stmtMock->method('fetch')->willReturn([
            'total_abonado' => '0.00'
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('40');

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1', '2'],
            'metodo' => '1',
            'moneda' => '1',
            'monto' => '80.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('40', $resultado['id_pago']);
        $this->assertEquals(0, $resultado['vuelto']);
    }

    // Caso 22: Pago con tasa de cambio
    public function testPagoConTasaCambio(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1);
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_moneda' => 2, 'nombre' => 'Bolivar', 'simbolo' => 'Bs', 'monto_total' => '10.00']
        ]);
        $stmtMock->method('fetch')->willReturn([
            'total_abonado' => '0.00'
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('45');

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '1',
            'moneda' => '2',
            'monto' => '365.00',
            'tasa' => '36.5',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('45', $resultado['id_pago']);
        $this->assertEquals(0, $resultado['vuelto']);
    }

    // Caso 23: Pago sin referencia
    public function testPagoSinReferencia(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1);
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_moneda' => 1, 'nombre' => 'Dolar', 'simbolo' => '$', 'monto_total' => '100.00']
        ]);
        $stmtMock->method('fetch')->willReturn([
            'total_abonado' => '0.00'
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('50');

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '1',
            'moneda' => '1',
            'monto' => '100.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d')
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('50', $resultado['id_pago']);
        $this->assertEquals(0, $resultado['vuelto']);
    }
}
