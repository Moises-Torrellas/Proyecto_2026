<?php

namespace tests\Unit\Permisos;

use App\modelo\Conexion;
use PDO;
use PDOStatement;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPermisos;
use Exception;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

/**
 * RF-PER-05: El sistema debe permitir bloquear y desbloquear permisos. Se envía el estado
 * actual (1 = activo, 2 = bloqueado) y el sistema lo alterna. Si el permiso no existe se
 * informa el error, en la clave "codigo" que lee el controlador.
 *
 * Escenarios:
 *  E1. Bloquear un permiso activo.
 *  E2. Desbloquear un permiso bloqueado.
 *  E3. Permiso inexistente.
 *  E4. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitPermisos')]
#[Group('bloquearPermisos')]
class BloquearPermisosTest extends TestCase
{
    /** Registro de las sentencias ejecutadas: ['sql' => string, 'params' => array] */
    protected array $sentencias = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $this->sentencias = [];
        parent::tearDown();
    }

    /**
     * Los permisos viven en la base de datos de seguridad (conexSG), por lo que el PDO
     * simulado se inyecta en la propiedad estática "instanciaSG" de Conexion.
     */
    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoMock);
    }

    /**
     * @param array $conteos   Resultados de las verificaciones de existencia (trait VerificarExistencia):
     *                         'tabla.columna.estatus' => int   (usar 'null' cuando no se filtra por estatus)
     *                         'propia.columna'        => int   (verificarExistenciaPropia)
     *                         Cualquier verificación no indicada devuelve 0 (no existe).
     * @param array $respuestas Fragmento de SQL => ['fetchAll' => , 'rowCount' => , 'error' => Exception]
     *                         Se usa el primer fragmento contenido en la sentencia (el orden importa).
     */
    protected function crearPdoMock(array $conteos = [], array $respuestas = []): PDO
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($conteos, $respuestas) {
            if (preg_match('/SELECT COUNT\(\*\) FROM `\w+`\s+WHERE\s+\w+\s*=\s*:id\s+AND\s+(\w+)\s*=\s*:valor/', $sql, $m)) {
                return $this->crearSentenciaConteo(fn() => $conteos['propia.' . $m[1]] ?? 0);
            }
            if (preg_match('/SELECT COUNT\(\*\) FROM `(\w+)` WHERE (\w+) = :valor/', $sql, $m)) {
                return $this->crearSentenciaConteo(
                    fn($estatus) => $conteos[$m[1] . '.' . $m[2] . '.' . ($estatus ?? 'null')] ?? 0
                );
            }
            return $this->crearSentencia($sql, $respuestas);
        });

        return $pdoMock;
    }

    private function crearSentenciaConteo(callable $resolver): PDOStatement
    {
        $estatus = null;
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindValue')->willReturnCallback(function ($param, $valor) use (&$estatus) {
            if ($param === ':estatus') {
                $estatus = $valor;
            }
            return true;
        });
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchColumn')->willReturnCallback(function () use (&$estatus, $resolver) {
            return $resolver($estatus);
        });
        return $stmt;
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
        $stmt->method('rowCount')->willReturn($config['rowCount'] ?? 1);

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

    /** Mensaje de error devuelto por el modelo, venga en 'codigo' o en 'mensaje'. */
    protected function textoError(array $resultado): ?string
    {
        return $resultado['codigo'] ?? $resultado['mensaje'] ?? null;
    }

    private function ejecutar(string $bloqueo, array $respuestas = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock([], $respuestas));
        return (new ModeloPermisos())->ProcesarDatos(['accion' => 'bloquear', 'id' => '9', 'bloqueo' => $bloqueo]);
    }

    // Caso 1 (E1): Bloquear un permiso activo (1 -> 2)
    public function testBloquear(): void
    {
        $resultado = $this->ejecutar('1');

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame(
            [':estado' => 2, ':id' => '9'],
            $this->sentenciaEjecutada('UPDATE `permisos` SET `estatus`')['params']
        );
    }

    // Caso 2 (E2): Desbloquear un permiso bloqueado (2 -> 1)
    public function testDesbloquear(): void
    {
        $resultado = $this->ejecutar('2');

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame(
            [':estado' => 1, ':id' => '9'],
            $this->sentenciaEjecutada('UPDATE `permisos` SET `estatus`')['params']
        );
    }

    // Caso 3 (E3): Permiso inexistente (ninguna fila actualizada)
    public function testPermisoNoExiste(): void
    {
        $resultado = $this->ejecutar('1', ['UPDATE `permisos`' => ['rowCount' => 0]]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(
            'No se encontró el permiso o el estatus ya era el solicitado.',
            $this->textoError($resultado)
        );
    }

    // Caso 4 (E3): El error se devuelve en la clave "codigo" que lee el controlador
    public function testErrorEnClaveCodigo(): void
    {
        $resultado = $this->ejecutar('1', ['UPDATE `permisos`' => ['rowCount' => 0]]);

        $this->assertArrayHasKey(
            'codigo',
            $resultado,
            'Bloqueo() devuelve el error en "mensaje", pero el controlador lee "codigo".'
        );
    }

    // Caso 5 (E4): Error de base de datos al bloquear
    public function testErrorBaseDatosAlBloquear(): void
    {
        $resultado = $this->ejecutar('1', [
            'UPDATE `permisos`' => ['error' => new Exception('Error al bloquear permiso')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al bloquear permiso', $this->textoError($resultado));
    }
}
