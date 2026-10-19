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
class EliminarPagoTest extends TestCase
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

    // Caso 1: Id con formato inválido
    public function testIdInvalido(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'eliminar',
            'id' => 'abc'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id de pago inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 2: Motivo de anulación con formato inválido
    public function testMotivoInvalido(): void
    {
        // Arrange
        $modelo = new ModeloPagos();
        $datos = [
            'accion' => 'eliminar',
            'id' => '10',
            'motivo_anulacion' => 'Motivo_Invalido_123!'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Motivo de anulación inválido.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 3: Pago no existe en BD
    public function testPagoNoExiste(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'eliminar',
            'id' => '999'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El pago seleccionado no existe.', $resultado['codigo']);
        $this->assertEquals('El pago seleccionado no existe.', $resultado['mensaje']);
    }

    // Caso 4: Pago ya se encuentra anulado
    public function testPagoYaAnulado(): void
    {
        // Arrange
        $pagoDatos = [
            'monto_pago' => '100.00',
            'fecha' => '2026-10-01',
            'referencia' => '123456',
            'estatus' => 2,
            'metodo_pago' => 'Transferencia',
            'simbolo' => '$'
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn($pagoDatos);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'eliminar',
            'id' => '10'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Este pago ya se encuentra anulado.', $resultado['codigo']);
        $this->assertEquals('Este pago ya se encuentra anulado.', $resultado['mensaje']);
    }

    // Caso 5: Anulación exitosa de pago con cuentas asociadas
    public function testAnularPagoExitoso(): void
    {
        // Arrange
        $pagoDatos = [
            'monto_pago' => '100.00',
            'fecha' => '2026-10-01',
            'referencia' => '123456',
            'estatus' => 1,
            'metodo_pago' => 'Transferencia',
            'simbolo' => '$'
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn($pagoDatos);
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_cargo' => 5],
            ['codigo_cargo' => 6]
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'eliminar',
            'id' => '15',
            'motivo_anulacion' => 'Error en monto'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Pago anulado correctamente. Las cuentas han vuelto a estado Pendiente.', $resultado['mensaje']);
        $this->assertStringContainsString('100.00', $resultado['desc_bitacora']);
        $this->assertStringContainsString('123456', $resultado['desc_bitacora']);
    }

    // Caso 6: Anulación exitosa de pago sin referencia
    public function testAnularPagoSinReferencia(): void
    {
        // Arrange
        $pagoDatos = [
            'monto_pago' => '50.00',
            'fecha' => '2026-10-05',
            'referencia' => null,
            'estatus' => 1,
            'metodo_pago' => 'Efectivo',
            'simbolo' => '$'
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn($pagoDatos);
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_cargo' => 3]
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'eliminar',
            'id' => '20'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertStringNotContainsString('con referencia', $resultado['desc_bitacora']);
        $datosPrevios = json_decode($resultado['datos_previos'], true);
        $this->assertEquals('No aplica', $datosPrevios['referencia']);
    }

    // Caso 7: Anulación exitosa de pago sin cuentas asociadas
    public function testAnularPagoSinCargosAsociados(): void
    {
        // Arrange
        $pagoDatos = [
            'monto_pago' => '75.00',
            'fecha' => '2026-10-10',
            'referencia' => '987654',
            'estatus' => 1,
            'metodo_pago' => 'Punto de Venta',
            'simbolo' => 'Bs'
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn($pagoDatos);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);

        $modelo = $this->crearModeloConPdoMock($pdoMock);

        $datos = [
            'accion' => 'eliminar',
            'id' => '30'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Pago anulado correctamente. Las cuentas han vuelto a estado Pendiente.', $resultado['mensaje']);
    }
}
