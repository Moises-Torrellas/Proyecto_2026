<?php

namespace tests\Unit\Bitacora;

use App\modelo\Conexion;
use PDO;
use PDOStatement;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloBitacora;
use App\interface\InterBitacora;
use Exception;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitBitacora')]
#[Group('registrarAccionBitacora')]
class RegistrarAccionBitacoraTest extends TestCase
{
    protected array $sentencias = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $_SESSION = [];
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->sentencias = [];
        parent::tearDown();
    }

    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoMock);
    }

    protected function crearPdoMock(array $respuestas = [], bool $enTransaccion = false): PDO
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn($enTransaccion);
        $pdoMock->method('rollBack')->willReturnCallback(function () {
            $this->sentencias[] = ['sql' => 'ROLLBACK', 'params' => []];
            return true;
        });
        $pdoMock->method('prepare')->willReturnCallback(
            fn(string $sql) => $this->crearSentencia($sql, $respuestas)
        );

        return $pdoMock;
    }

    private function crearSentencia(string $sql, array $respuestas): PDOStatement
    {
        $config = [];
        foreach ($respuestas as $fragmento => $respuesta) {
            if (str_contains($sql, $fragmento)) {
                $config = $respuesta;
                break;
            }
        }

        $binds = new \ArrayObject();
        $stmt = $this->createMock(PDOStatement::class);
        $registrarBind = function ($param, $valor) use ($binds) {
            $binds[$param] = $valor;
            return true;
        };
        $stmt->method('bindValue')->willReturnCallback($registrarBind);
        $stmt->method('bindParam')->willReturnCallback($registrarBind);
        $stmt->method('execute')->willReturnCallback(function (?array $params = null) use ($sql, $binds, $config) {
            $this->sentencias[] = ['sql' => $sql, 'params' => $params ?? $binds->getArrayCopy()];
            if (isset($config['error'])) {
                throw $config['error'];
            }
            return true;
        });
        $stmt->method('fetchAll')->willReturn($config['fetchAll'] ?? []);

        return $stmt;
    }

    protected function sentenciaEjecutada(string $fragmento): ?array
    {
        foreach ($this->sentencias as $sentencia) {
            if (str_contains($sentencia['sql'], $fragmento)) {
                return $sentencia;
            }
        }
        return null;
    }

    public function testImplementaInterfaz(): void
    {
        $this->assertInstanceOf(InterBitacora::class, new ModeloBitacora());
    }

    public function testRegistroExitoso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock());

        $datosPrevios = json_encode(['nombre' => 'Entrenador']);
        $datosNuevos  = json_encode(['nombre' => 'Entrenador Principal']);

        (new ModeloBitacora())->RegistrarAccion(
            103,
            'Modificó el Rol: Entrenador Principal',
            4,
            $datosPrevios,
            $datosNuevos,
            'Mozilla/5.0 (Windows NT 10.0)'
        );

        $llamada = $this->sentenciaEjecutada('CALL pa_incluir_bitacora');
        $this->assertNotNull($llamada);
        $this->assertSame([
            ':modulo'        => 103,
            ':accion'        => 'Modificó el Rol: Entrenador Principal',
            ':datos_previos' => $datosPrevios,
            ':datos_nuevos'  => $datosNuevos,
            ':entorno'       => 'Mozilla/5.0 (Windows NT 10.0)',
            ':usuario'       => 4,
        ], $llamada['params']);
    }

    public function testRegistroSoloAccion(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock());

        (new ModeloBitacora())->RegistrarAccion(103, 'Ingreso al Modulo', 4);

        $params = $this->sentenciaEjecutada('CALL pa_incluir_bitacora')['params'];
        $this->assertSame('Ninguno', $params[':datos_previos'], 'Las columnas no admiten NULL.');
        $this->assertSame('Ninguno', $params[':datos_nuevos'], 'Las columnas no admiten NULL.');
        $this->assertSame('', $params[':entorno']);
        $this->assertSame('Ingreso al Modulo', $params[':accion']);
    }

    public function testErrorBaseDatosNoInterrumpe(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'CALL pa_incluir_bitacora' => ['error' => new Exception('Procedimiento no encontrado')],
        ]));

        $resultado = (new ModeloBitacora())->RegistrarAccion(103, 'Ingreso al Modulo', 4);

        $this->assertNull($resultado);
        $this->assertNotNull($this->sentenciaEjecutada('CALL pa_incluir_bitacora'));
    }

    public function testErrorConTransaccionHaceRollback(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'CALL pa_incluir_bitacora' => ['error' => new Exception('Error al registrar')],
        ], enTransaccion: true));

        (new ModeloBitacora())->RegistrarAccion(103, 'Ingreso al Modulo', 4);

        $this->assertNotNull($this->sentenciaEjecutada('ROLLBACK'));
    }

    public function testErrorSinTransaccionNoHaceRollback(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'CALL pa_incluir_bitacora' => ['error' => new Exception('Error al registrar')],
        ], enTransaccion: false));

        (new ModeloBitacora())->RegistrarAccion(103, 'Ingreso al Modulo', 4);

        $this->assertNull($this->sentenciaEjecutada('ROLLBACK'));
    }
}
