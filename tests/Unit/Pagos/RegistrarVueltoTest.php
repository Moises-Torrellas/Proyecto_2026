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
#[Group('registrarVuelto')]
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

    // Caso 1: Validación de campos de vuelto con formato o valor inválido
    public function testValidarCamposInvalidosLanzaExcepcion(): void
    {
        $modelo = new ModeloPagos();
        $casos = [
            'monto invalido'      => [['accion' => 'registrar_vuelto', 'monto_vuelto' => 'monto_invalido'], 'Monto inválido.'],
            'codigo pago'         => [['accion' => 'registrar_vuelto', 'codigo_pago' => 'invalido'], 'Pago inválido.'],
            'codigo metodo'       => [['accion' => 'registrar_vuelto', 'codigo_metodo' => 'metodo_invalido'], 'Método de pago inválido.'],
            'codigo moneda'       => [['accion' => 'registrar_vuelto', 'codigo_moneda' => 'moneda_invalida'], 'Moneda inválida.'],
            'fecha futura'        => [['accion' => 'registrar_vuelto', 'fecha_vuelto' => '2099-12-31'], 'La fecha ingresada no puede ser futura.'],
            'fecha atrasada'      => [['accion' => 'registrar_vuelto', 'fecha_vuelto' => '2020-01-01'], 'La fecha ingresada no puede tener más de 2 meses de atraso.'],
            'fecha formato'       => [['accion' => 'registrar_vuelto', 'fecha_vuelto' => '31/12/2026'], 'Formato de fecha inválido. Use AAAA-MM-DD.'],
            'referencia invalida' => [['accion' => 'registrar_vuelto', 'referencia_vuelto' => '12'], 'La referencia del vuelto debe tener entre 4 y 12 dígitos numéricos.']
        ];

        foreach ($casos as $nombre => [$datos, $mensajeEsperado]) {
            try {
                $modelo->ProcesarDatos($datos);
                $this->fail("Se esperaba excepción en caso [$nombre] y no se lanzó.");
            } catch (Exception $e) {
                $this->assertEquals($mensajeEsperado, $e->getMessage(), "Mensaje incorrecto en [$nombre]");
            }
        }
    }

    // Caso 2: Monto de vuelto menor o igual a cero
    public function testMontoVueltoMenorOIgualACero(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $modelo = $this->crearModeloConPdoMock($pdoMock);
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

    // Caso 4: Saldo insuficiente o inexistente para vuelto
    public function testSaldoInsuficienteParaVuelto(): void
    {
        $casos = [
            'sin saldo' => [
                'fetchAbonado'    => ['total_abonado' => '100.00'],
                'montoVuelto'     => '15.00',
                'mensajeEsperado' => 'Ya no queda saldo disponible para dar vuelto en este pago.'
            ],
            'excede saldo' => [
                'fetchAbonado'    => ['total_abonado' => '80.00'],
                'montoVuelto'     => '50.00',
                'mensajeEsperado' => 'El vuelto solicitado excede el saldo disponible restante de este pago.'
            ]
        ];

        foreach ($casos as $nombre => $caso) {
            $stmtMock = $this->createMock(PDOStatement::class);
            $stmtMock->method('execute')->willReturn(true);
            $stmtMock->method('fetch')->willReturnOnConsecutiveCalls(
                ['monto_pago' => '100.00', 'codigo_moneda' => 1],
                $caso['fetchAbonado'],
                ['total_vueltos_base' => '0.00']
            );

            $pdoMock = $this->createMock(PDO::class);
            $pdoMock->method('prepare')->willReturn($stmtMock);

            $modelo = $this->crearModeloConPdoMock($pdoMock);

            $datos = [
                'accion'        => 'registrar_vuelto',
                'codigo_pago'   => '10',
                'codigo_metodo' => '1',
                'codigo_moneda' => '1',
                'monto_vuelto'  => $caso['montoVuelto']
            ];

            $resultado = $modelo->ProcesarDatos($datos);

            $this->assertEquals('error', $resultado['accion'], "Fallo en caso [$nombre]");
            $this->assertEquals($caso['mensajeEsperado'], $resultado['mensaje'], "Mensaje incorrecto en caso [$nombre]");
        }
    }

    // Caso 5: Registro exitoso de vuelto (total y parcial)
    public function testRegistrarVueltoExitoso(): void
    {
        $casos = [
            'vuelto total' => [
                'totalAbonado' => '60.00',
                'montoVuelto'  => '40.00'
            ],
            'vuelto parcial' => [
                'totalAbonado' => '50.00',
                'montoVuelto'  => '20.00'
            ]
        ];

        foreach ($casos as $nombre => $caso) {
            $stmtMock = $this->createMock(PDOStatement::class);
            $stmtMock->method('execute')->willReturn(true);
            $stmtMock->method('fetch')->willReturnOnConsecutiveCalls(
                ['monto_pago' => '100.00', 'codigo_moneda' => 1],
                ['total_abonado' => $caso['totalAbonado']],
                ['total_vueltos_base' => '0.00']
            );

            $pdoMock = $this->createMock(PDO::class);
            $pdoMock->method('prepare')->willReturn($stmtMock);

            $modelo = $this->crearModeloConPdoMock($pdoMock);

            $datos = [
                'accion'        => 'registrar_vuelto',
                'codigo_pago'   => '10',
                'codigo_metodo' => '1',
                'codigo_moneda' => '1',
                'monto_vuelto'  => $caso['montoVuelto'],
                'referencia'    => '123456',
                'fecha_vuelto'  => date('Y-m-d')
            ];

            $resultado = $modelo->ProcesarDatos($datos);

            $this->assertEquals('exito_vuelto', $resultado['accion'], "Fallo en caso [$nombre]");
            $this->assertEquals('Vuelto registrado exitosamente (SP)', $resultado['mensaje'], "Mensaje incorrecto en caso [$nombre]");
        }
    }
}
