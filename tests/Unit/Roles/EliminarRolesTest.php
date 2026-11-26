<?php

namespace tests\Unit\Roles;

use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRoles;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

/**
 * RF-ROL-05: El sistema debe permitir eliminar roles. Los roles protegidos (id 1 y 2)
 * no se pueden eliminar, tampoco un rol que tenga usuarios activos asociados, y no se
 * puede eliminar un rol que no existe. Todas las verificaciones se hacen sobre la base
 * de datos de seguridad, donde están las tablas de roles y usuarios.
 *
 * Escenarios:
 *  E1. Eliminación de un rol existente sin usuarios.
 *  E2. Intento de eliminar un rol protegido.
 *  E3. Rol con usuarios activos asociados.
 *  E4. Rol inexistente.
 *  E5. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitRoles')]
#[Group('eliminarRoles')]
class EliminarRolesTest extends TestCase
{
    /** Registro de las sentencias ejecutadas: ['sql' => string, 'params' => array] */
    protected array $sentencias = [];

    /** Sentencias que el modelo intentó preparar sobre la base de datos general. */
    protected array $sentenciasBdGeneral = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $this->sentencias = [];
        $this->sentenciasBdGeneral = [];
        parent::tearDown();
    }

    /**
     * Los roles viven en la base de datos de seguridad (conexSG). El PDO simulado se inyecta
     * en "instanciaSG"; la conexión general ("instancia") se reemplaza por un PDO que falla,
     * de modo que si el modelo consulta por error la base de datos general la prueba lo detecta.
     */
    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $pdoGeneral = $this->createMock(PDO::class);
        $pdoGeneral->method('prepare')->willReturnCallback(function (string $sql) {
            $this->sentenciasBdGeneral[] = $sql;
            throw new Exception('El módulo de roles no debe consultar la base de datos general.');
        });

        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoGeneral);
    }

    /**
     * Crea el PDO simulado de la base de datos de seguridad.
     *
     * @param array $conteos   Resultados de las verificaciones de existencia (trait VerificarExistencia):
     *                         'tabla.columna.estatus' => int   (usar 'null' cuando no se filtra por estatus)
     *                         'propia.columna'        => int   (verificarExistenciaPropia)
     *                         Cualquier verificación no indicada devuelve 0 (no existe).
     * @param array $respuestas Fragmento de SQL => ['fetchAll' => , 'error' => Exception]
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

    private function ejecutar(int $id, array $conteos, array $respuestas = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloRoles())->procesarDatos(['accion' => 'eliminar', 'id' => (string) $id]);
    }

    // Caso 1 (E1): Eliminación exitosa de un rol existente sin usuarios asociados
    public function testEliminarExitoso(): void
    {
        $resultado = $this->ejecutar(5, [
            'usuarios.id_rol.1'  => 0,
            'roles.id_rol.null'  => 1,
        ]);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame([':id' => 5], $this->sentenciaEjecutada('DELETE FROM roles')['params']);
    }

    // Caso 2 (E2): El rol protegido id 1 (Super Usuario) no se puede eliminar
    public function testNoEliminaRolProtegido1(): void
    {
        $resultado = $this->ejecutar(1, ['roles.id_rol.null' => 1]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM roles'));
    }

    // Caso 3 (E2): El rol protegido id 2 no se puede eliminar
    public function testNoEliminaRolProtegido2(): void
    {
        $resultado = $this->ejecutar(2, ['roles.id_rol.null' => 1]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM roles'));
    }

    // Caso 4 (E3): Rol con usuarios activos asociados no se puede eliminar
    public function testRolConUsuariosAsociados(): void
    {
        $resultado = $this->ejecutar(5, [
            'usuarios.id_rol.1' => 3,
            'roles.id_rol.null' => 1,
        ]);

        $this->assertSame('error', $resultado['accion'], 'Se eliminó un rol que tiene usuarios activos asociados.');
        $this->assertSame(ASSOCIATES, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM roles'));
    }

    // Caso 5 (E4): Rol inexistente
    public function testRolNoExiste(): void
    {
        $resultado = $this->ejecutar(50, [
            'usuarios.id_rol.1' => 0,
            'roles.id_rol.null' => 0,
        ]);

        $this->assertSame('error', $resultado['accion'], 'Se intentó eliminar un rol que no existe.');
        $this->assertSame(INVALID_ID . '0', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM roles'));
    }

    // Caso 6 (E1): Las verificaciones se hacen solo sobre la base de datos de seguridad
    public function testNoConsultaBaseDatosGeneral(): void
    {
        $this->ejecutar(5, ['usuarios.id_rol.1' => 0, 'roles.id_rol.null' => 1]);

        $this->assertSame(
            [],
            $this->sentenciasBdGeneral,
            'Eliminar() consultó la base de datos general en lugar de la de seguridad.'
        );
    }

    // Caso 7 (E5): Error de base de datos al eliminar
    public function testErrorBaseDatosAlEliminar(): void
    {
        $resultado = $this->ejecutar(
            5,
            ['usuarios.id_rol.1' => 0, 'roles.id_rol.null' => 1],
            ['DELETE FROM roles' => ['error' => new Exception('Error al eliminar rol')]]
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al eliminar rol', $resultado['codigo']);
    }
}
