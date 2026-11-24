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
#[Group('registrarPagos')]
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

            public function conex()
            {
                return self::$pdoTemporal;
            }
        };

        $modeloFake::$pdoTemporal = $pdoMock;
        return $modeloFake;
    }

    // Caso 1: Validación de estructura de datos de entrada y acción inválida
    public function testEstructuraDatosYAccionInvalida(): void
    {
        $modelo = new ModeloPagos();

        // 1. Datos vacíos
        try {
            $modelo->ProcesarDatos([]);
            $this->fail('Se esperaba excepción por datos vacíos');
        } catch (Exception $e) {
            $this->assertEquals('No se proporcionaron datos para procesar.', $e->getMessage());
        }

        // 2. Acción inválida
        try {
            $modelo->ProcesarDatos(['accion' => 'accion_invalida']);
            $this->fail('Se esperaba excepción por acción no válida');
        } catch (Exception $e) {
            $this->assertEquals('La accion solicitada para el pago no es valida.', $e->getMessage());
        }
    }

    // Caso 2: Validación de campos con formato o valor inválido (Reglas y Expresiones)
    public function testValidarCamposInvalidosLanzaExcepcion(): void
    {
        $modelo = new ModeloPagos();
        $casos = [
            'monto invalido'      => [['monto' => 'monto_invalido'], 'Monto del pago inválido.'],
            'referencia invalida' => [['referencia' => '12'], 'La referencia del pago debe tener entre 4 y 12 dígitos numéricos.'],
            'fecha futura'        => [['accion' => 'incluir', 'fecha' => '2099-12-31'], 'La fecha ingresada no puede ser futura.'],
            'fecha atrasada'      => [['accion' => 'incluir', 'fecha' => '2020-01-01'], 'La fecha ingresada no puede tener más de 2 meses de atraso.'],
            'fecha formato'       => [['fecha' => '31/12/2026'], 'Formato de fecha inválido. Use AAAA-MM-DD.'],
            'tasa invalida'       => [['tasa' => 'tasa_invalida'], 'Tasa de cambio inválida.'],
            'metodo invalido'     => [['metodo' => 'metodo_invalido'], 'Método de pago inválido.'],
            'moneda invalida'     => [['moneda' => 'moneda_invalida'], 'Moneda inválida.'],
            'cuenta invalida'     => [['cuenta' => ['cuenta_invalida']], 'Cuenta por cobrar inválida.']
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

    // Caso 3: Precondiciones de registro de pago inválidas (sin cuentas, monto <= 0, tasa <= 0)
    public function testPrecondicionesDePagoInvalidas(): void
    {
        $modelo = new ModeloPagos();
        $casos = [
            'sin cuentas' => [
                'datos'          => ['accion' => 'incluir', 'cuenta' => []],
                'codigoEsperado' => EMPTY_SELECTION
            ],
            'monto menor o igual a cero' => [
                'datos'          => [
                    'accion' => 'incluir',
                    'cuenta' => ['1'],
                    'monto'  => '0',
                    'tasa'   => '1',
                    'fecha'  => date('Y-m-d')
                ],
                'codigoEsperado' => 'El monto del pago debe ser mayor a 0.'
            ],
            'tasa menor o igual a cero' => [
                'datos'          => [
                    'accion' => 'incluir',
                    'cuenta' => ['1'],
                    'monto'  => '100.00',
                    'tasa'   => '0',
                    'fecha'  => date('Y-m-d')
                ],
                'codigoEsperado' => 'La tasa de cambio debe ser mayor a 0.'
            ]
        ];

        foreach ($casos as $nombre => $caso) {
            $resultado = $modelo->ProcesarDatos($caso['datos']);
            $this->assertEquals('error', $resultado['accion'], "Fallo de acción en caso [$nombre]");
            $this->assertEquals($caso['codigoEsperado'], $resultado['codigo'], "Código incorrecto en caso [$nombre]");
        }
    }

    // Caso 4: Entidades relacionadas inexistentes en BD (método de pago o moneda)
    public function testEntidadesRelacionadasInexistentes(): void
    {
        $casos = [
            'metodo inexistente' => [
                'metodo'             => '99',
                'moneda'             => '1',
                'retornosExistencia' => [0],
                'codigoEsperado'     => INVALID_ID
            ],
            'moneda inexistente' => [
                'metodo'             => '1',
                'moneda'             => '99',
                'retornosExistencia' => [1, 0],
                'codigoEsperado'     => INVALID_ID . '0'
            ]
        ];

        foreach ($casos as $nombre => $caso) {
            $stmtMock = $this->createMock(PDOStatement::class);
            $stmtMock->method('fetchColumn')->willReturnOnConsecutiveCalls(...$caso['retornosExistencia']);
            $stmtMock->method('execute')->willReturn(true);

            $pdoMock = $this->createMock(PDO::class);
            $pdoMock->method('prepare')->willReturn($stmtMock);
            $pdoMock->method('beginTransaction')->willReturn(true);
            $pdoMock->method('rollBack')->willReturn(true);
            $pdoMock->method('inTransaction')->willReturn(true);

            $modelo = $this->crearModeloConPdoMock($pdoMock);

            $datos = [
                'accion'     => 'incluir',
                'cuenta'     => ['1'],
                'metodo'     => $caso['metodo'],
                'moneda'     => $caso['moneda'],
                'monto'      => '100.00',
                'tasa'       => '1',
                'fecha'      => date('Y-m-d'),
                'referencia' => '123456'
            ];

            $resultado = $modelo->ProcesarDatos($datos);
            $this->assertEquals('error', $resultado['accion'], "Fallo en caso [$nombre]");
            $this->assertEquals($caso['codigoEsperado'], $resultado['codigo'], "Código incorrecto en caso [$nombre]");
        }
    }

    // Caso 5: Cuentas sin deuda pendiente
    public function testCuentasSinDeuda(): void
    {
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
            'accion'     => 'incluir',
            'cuenta'     => ['1'],
            'metodo'     => '1',
            'moneda'     => '1',
            'monto'      => '50.00',
            'tasa'       => '1',
            'fecha'      => date('Y-m-d'),
            'referencia' => '123456'
        ];

        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Las cuentas seleccionadas no poseen deuda pendiente.', $resultado['codigo']);
    }

    // Caso 6: Distribución de monto y registro exitoso (monto exacto, parcial, con vuelto y sin referencia)
    public function testDistribucionDeMontoYPagoExitoso(): void
    {
        $casos = [
            'monto exacto con referencia' => [
                'montoTotalCuenta' => '100.00',
                'montoPago'        => '100.00',
                'referencia'       => '123456',
                'vueltoEsperado'   => 0
            ],
            'pago parcial abono' => [
                'montoTotalCuenta' => '100.00',
                'montoPago'        => '40.00',
                'referencia'       => '123456',
                'vueltoEsperado'   => 0
            ],
            'sobrepago genera vuelto' => [
                'montoTotalCuenta' => '60.00',
                'montoPago'        => '100.00',
                'referencia'       => '123456',
                'vueltoEsperado'   => 40.00
            ],
            'monto exacto sin referencia' => [
                'montoTotalCuenta' => '100.00',
                'montoPago'        => '100.00',
                'referencia'       => null,
                'vueltoEsperado'   => 0
            ]
        ];

        foreach ($casos as $nombre => $caso) {
            $stmtMock = $this->createMock(PDOStatement::class);
            $stmtMock->method('execute')->willReturn(true);
            $stmtMock->method('fetchColumn')->willReturn(1);
            $stmtMock->method('fetchAll')->willReturn([
                ['codigo_moneda' => 1, 'nombre' => 'Dolar', 'simbolo' => '$', 'monto_total' => $caso['montoTotalCuenta']]
            ]);
            $stmtMock->method('fetch')->willReturn([
                'total_abonado' => '0.00'
            ]);

            $pdoMock = $this->createMock(PDO::class);
            $pdoMock->method('prepare')->willReturn($stmtMock);
            $pdoMock->method('beginTransaction')->willReturn(true);
            $pdoMock->method('commit')->willReturn(true);
            $pdoMock->method('lastInsertId')->willReturn('100');

            $modelo = $this->crearModeloConPdoMock($pdoMock);

            $datos = [
                'accion' => 'incluir',
                'cuenta' => ['1'],
                'metodo' => '1',
                'moneda' => '1',
                'monto'  => $caso['montoPago'],
                'tasa'   => '1',
                'fecha'  => date('Y-m-d')
            ];
            if ($caso['referencia'] !== null) {
                $datos['referencia'] = $caso['referencia'];
            }

            $resultado = $modelo->ProcesarDatos($datos);

            $this->assertEquals('exito', $resultado['accion'], "Fallo en caso [$nombre]");
            $this->assertEquals('100', $resultado['id_pago'], "ID incorrecto en caso [$nombre]");
            $this->assertEquals($caso['vueltoEsperado'], $resultado['vuelto'], "Vuelto incorrecto en caso [$nombre]");
        }
    }

    // Caso 7: Pago a múltiples cuentas con distribución secuencial de saldo
    public function testPagoMultiplesCuentas(): void
    {
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
            'accion'     => 'incluir',
            'cuenta'     => ['1', '2'],
            'metodo'     => '1',
            'moneda'     => '1',
            'monto'      => '80.00',
            'tasa'       => '1',
            'fecha'      => date('Y-m-d'),
            'referencia' => '123456'
        ];

        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('40', $resultado['id_pago']);
        $this->assertEquals(0, $resultado['vuelto']);
    }

    // Caso 8: Pago con tasa de cambio y conversión de moneda
    public function testPagoConTasaCambio(): void
    {
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
            'accion'     => 'incluir',
            'cuenta'     => ['1'],
            'metodo'     => '1',
            'moneda'     => '2',
            'monto'      => '365.00',
            'tasa'       => '36.5',
            'fecha'      => date('Y-m-d'),
            'referencia' => '123456'
        ];

        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('45', $resultado['id_pago']);
        $this->assertEquals(0, $resultado['vuelto']);
    }
}
