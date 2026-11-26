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

/**
 * RF-BIT-01: El sistema debe registrar en la bitácora cada acción relevante que realizan
 * los usuarios: módulo, descripción de la acción, usuario, datos previos, datos nuevos y
 * entorno (navegador). Una falla al registrar la bitácora no debe interrumpir la
 * operación que el usuario estaba realizando.
 *
 * Escenarios:
 *  E1. Registro de una acción con todos sus datos.
 *  E2. Registro de una acción sin datos previos ni nuevos.
 *  E3. Falla de la base de datos al registrar.
 */
#[Group('unit')]
#[Group('unitBitacora')]
#[Group('registrarAccionBitacora')]
class RegistrarAccionBitacoraTest extends TestCase
{
    /** Registro de las sentencias ejecutadas: ['sql' => string, 'params' => array] */
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

    /**
     * La bitácora vive en la base de datos de seguridad (conexSG), por lo que el PDO
     * simulado se inyecta en la propiedad estática "instanciaSG" de Conexion.
     */
    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoMock);
    }

    /**
     * @param array $respuestas Fragmento de SQL => ['fetchAll' => , 'error' => Exception]
     *                          Se usa el primer fragmento contenido en la sentencia (el orden importa).
     */
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

    // Caso 1 (E1): El modelo implementa el contrato de bitácora usado por los demás módulos
    public function testImplementaInterfaz(): void
    {
        $this->assertInstanceOf(InterBitacora::class, new ModeloBitacora());
    }

    // Caso 2 (E1): Registro exitoso envía todos los datos al procedimiento almacenado
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

    // Caso 3 (E2): Registro sin datos previos, nuevos ni entorno usa los valores por defecto
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

    // Caso 4 (E3): Una falla de la BD no se propaga a la operación del usuario
    public function testErrorBaseDatosNoInterrumpe(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'CALL pa_incluir_bitacora' => ['error' => new Exception('Procedimiento no encontrado')],
        ]));

        $resultado = (new ModeloBitacora())->RegistrarAccion(103, 'Ingreso al Modulo', 4);

        $this->assertNull($resultado);
        $this->assertNotNull($this->sentenciaEjecutada('CALL pa_incluir_bitacora'));
    }

    // Caso 5 (E3): Si la falla ocurre dentro de una transacción abierta, se revierte
    public function testErrorConTransaccionHaceRollback(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'CALL pa_incluir_bitacora' => ['error' => new Exception('Error al registrar')],
        ], enTransaccion: true));

        (new ModeloBitacora())->RegistrarAccion(103, 'Ingreso al Modulo', 4);

        $this->assertNotNull($this->sentenciaEjecutada('ROLLBACK'));
    }

    // Caso 6 (E3): Si no hay transacción abierta, no se intenta revertir
    public function testErrorSinTransaccionNoHaceRollback(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'CALL pa_incluir_bitacora' => ['error' => new Exception('Error al registrar')],
        ], enTransaccion: false));

        (new ModeloBitacora())->RegistrarAccion(103, 'Ingreso al Modulo', 4);

        $this->assertNull($this->sentenciaEjecutada('ROLLBACK'));
    }
}
