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
#[Group('eliminarPagos')]
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

            public function conex()
            {
                return self::$pdoTemporal;
            }
        };

        $modeloFake::$pdoTemporal = $pdoMock;
        return $modeloFake;
    }

    // Caso 1: Validación de campos con formato inválido (id y motivo de anulación)
    public function testCamposInvalidosLanzaExcepcion(): void
    {
        $modelo = new ModeloPagos();

        // 1. Id inválido
        try {
            $modelo->ProcesarDatos(['accion' => 'eliminar', 'id' => 'abc']);
            $this->fail('Se esperaba excepción por Id inválido');
        } catch (Exception $e) {
            $this->assertEquals('Id de pago inválido.', $e->getMessage());
        }

        // 2. Motivo inválido
        try {
            $modelo->ProcesarDatos(['accion' => 'eliminar', 'id' => '10', 'motivo_anulacion' => 'Motivo_Invalido_123!']);
            $this->fail('Se esperaba excepción por motivo inválido');
        } catch (Exception $e) {
            $this->assertEquals('Motivo de anulación inválido.', $e->getMessage());
        }
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

    // Caso 4: Anulación exitosa de pago (con/sin referencia, con/sin cargos asociados)
    public function testAnularPagoExitoso(): void
    {
        $casos = [
            'con referencia y con cargos' => [
                'id'         => '15',
                'referencia' => '123456',
                'cargos'     => [['codigo_cargo' => 5], ['codigo_cargo' => 6]],
                'motivo'     => 'Error en monto'
            ],
            'sin referencia' => [
                'id'         => '20',
                'referencia' => null,
                'cargos'     => [['codigo_cargo' => 3]],
                'motivo'     => null
            ],
            'sin cargos asociados' => [
                'id'         => '30',
                'referencia' => '987654',
                'cargos'     => [],
                'motivo'     => null
            ]
        ];

        foreach ($casos as $nombre => $caso) {
            $pagoDatos = [
                'monto_pago'  => '100.00',
                'fecha'       => '2026-10-01',
                'referencia'  => $caso['referencia'],
                'estatus'     => 1,
                'metodo_pago' => 'Transferencia',
                'simbolo'     => '$'
            ];

            $stmtMock = $this->createMock(PDOStatement::class);
            $stmtMock->method('execute')->willReturn(true);
            $stmtMock->method('fetch')->willReturn($pagoDatos);
            $stmtMock->method('fetchAll')->willReturn($caso['cargos']);

            $pdoMock = $this->createMock(PDO::class);
            $pdoMock->method('prepare')->willReturn($stmtMock);
            $pdoMock->method('beginTransaction')->willReturn(true);
            $pdoMock->method('commit')->willReturn(true);

            $modelo = $this->crearModeloConPdoMock($pdoMock);

            $datos = [
                'accion' => 'eliminar',
                'id'     => $caso['id']
            ];
            if ($caso['motivo'] !== null) {
                $datos['motivo_anulacion'] = $caso['motivo'];
            }

            $resultado = $modelo->ProcesarDatos($datos);

            $this->assertEquals('exito', $resultado['accion'], "Fallo en caso [$nombre]");
            $this->assertEquals('Pago anulado correctamente. Las cuentas han vuelto a estado Pendiente.', $resultado['mensaje'], "Mensaje incorrecto en caso [$nombre]");
        }
    }
}
