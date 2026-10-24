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
class RegistrarVueltoTest extends TestCase
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

            public function conex()
            {
                return self::$pdoTemporal;
            }
        };

        $modeloFake::$pdoTemporal = $pdoMock;
        return $modeloFake;
    }

    // Caso 1: Monto de vuelto con formato inválido
    public function testMontoVueltoInvalido(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'registrar_vuelto',
            'monto_vuelto' => 'monto_invalido'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Monto inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 2: Código de pago con formato inválido
    public function testCodigoPagoInvalido(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'registrar_vuelto',
            'codigo_pago' => 'invalido'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Pago inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 3: Código de método con formato inválido
    public function testCodigoMetodoInvalido(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'registrar_vuelto',
            'codigo_metodo' => 'metodo_invalido'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Método de pago inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 4: Código de moneda con formato inválido
    public function testCodigoMonedaInvalido(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'registrar_vuelto',
            'codigo_moneda' => 'moneda_invalida'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Moneda inválida.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 5: Fecha de vuelto futura
    public function testFechaVueltoFutura(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'registrar_vuelto',
            'fecha_vuelto' => '2099-12-31'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La fecha ingresada no puede ser futura.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 6: Fecha de vuelto con más de 2 meses de atraso
    public function testFechaVueltoAtrasada(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'registrar_vuelto',
            'fecha_vuelto' => '2020-01-01'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La fecha ingresada no puede tener más de 2 meses de atraso.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 7: Fecha de vuelto con formato inválido
    public function testFechaVueltoInvalida(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'registrar_vuelto',
            'fecha_vuelto' => '31/12/2026'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Formato de fecha inválido. Use AAAA-MM-DD.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 8: Referencia de vuelto con formato inválido
    public function testReferenciaVueltoInvalida(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'registrar_vuelto',
            'referencia_vuelto' => '12'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La referencia del vuelto debe tener entre 4 y 12 dígitos numéricos.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 9: Monto de vuelto menor o igual a cero
    public function testMontoVueltoMenorOIgualACero(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'registrar_vuelto',
            'monto_vuelto' => '0'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El monto del vuelto debe ser mayor a 0.', $resultado['mensaje']);
    }

    // Caso 10: Pago original no existe en BD
    public function testPagoOriginalNoExiste(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'registrar_vuelto',
            'codigo_pago' => '999',
            'codigo_metodo' => '1',
            'codigo_moneda' => '1',
            'monto_vuelto' => '20.00'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El pago original no existe.', $resultado['mensaje']);
    }

    // Caso 11: Pago sin saldo disponible para dar vuelto
    public function testSinSaldoDisponible(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturnOnConsecutiveCalls(
            ['monto_pago' => '100.00', 'codigo_moneda' => 1],
            ['total_abonado' => '100.00'],
            ['total_vueltos_base' => '0.00']
        );

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'registrar_vuelto',
            'codigo_pago' => '10',
            'codigo_metodo' => '1',
            'codigo_moneda' => '1',
            'monto_vuelto' => '15.00'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya no queda saldo disponible para dar vuelto en este pago.', $resultado['mensaje']);
    }

    // Caso 12: Vuelto solicitado excede el saldo disponible
    public function testVueltoExcedeSaldoDisponible(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturnOnConsecutiveCalls(
            ['monto_pago' => '100.00', 'codigo_moneda' => 1],
            ['total_abonado' => '80.00'],
            ['total_vueltos_base' => '0.00']
        );

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'registrar_vuelto',
            'codigo_pago' => '10',
            'codigo_metodo' => '1',
            'codigo_moneda' => '1',
            'monto_vuelto' => '50.00'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El vuelto solicitado excede el saldo disponible restante de este pago.', $resultado['mensaje']);
    }

    // Caso 13: Registro exitoso de vuelto en la misma moneda
    public function testRegistrarVueltoExitoso(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturnOnConsecutiveCalls(
            ['monto_pago' => '100.00', 'codigo_moneda' => 1],
            ['total_abonado' => '60.00'],
            ['total_vueltos_base' => '0.00']
        );

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'registrar_vuelto',
            'codigo_pago' => '10',
            'codigo_metodo' => '1',
            'codigo_moneda' => '1',
            'monto_vuelto' => '40.00',
            'referencia' => '123456',
            'fecha_vuelto' => date('Y-m-d')
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito_vuelto', $resultado['accion']);
        $this->assertEquals('Vuelto registrado exitosamente (SP)', $resultado['mensaje']);
    }

    // Caso 14: Registro exitoso de vuelto parcial
    public function testRegistrarVueltoParcialExitoso(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturnOnConsecutiveCalls(
            ['monto_pago' => '100.00', 'codigo_moneda' => 1],
            ['total_abonado' => '50.00'],
            ['total_vueltos_base' => '0.00']
        );

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'registrar_vuelto',
            'codigo_pago' => '10',
            'codigo_metodo' => '1',
            'codigo_moneda' => '1',
            'monto_vuelto' => '20.00',
            'referencia' => '123456',
            'fecha_vuelto' => date('Y-m-d')
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito_vuelto', $resultado['accion']);
        $this->assertEquals('Vuelto registrado exitosamente (SP)', $resultado['mensaje']);
    }
}
