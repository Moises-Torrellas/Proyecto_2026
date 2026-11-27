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

#[Group('unit')]
#[Group('unitRoles')]
#[Group('eliminarRoles')]
class EliminarRolesTest extends TestCase
{
    protected array $sentencias = [];

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

    public function testEliminarExitoso(): void
    {
        $resultado = $this->ejecutar(5, [
            'usuarios.id_rol.1'  => 0,
            'roles.id_rol.null'  => 1,
        ]);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame([':id' => 5], $this->sentenciaEjecutada('DELETE FROM roles')['params']);
    }

    public function testNoEliminaRolProtegido1(): void
    {
        $resultado = $this->ejecutar(1, ['roles.id_rol.null' => 1]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM roles'));
    }

    public function testNoEliminaRolProtegido2(): void
    {
        $resultado = $this->ejecutar(2, ['roles.id_rol.null' => 1]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM roles'));
    }

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

    public function testNoConsultaBaseDatosGeneral(): void
    {
        $this->ejecutar(5, ['usuarios.id_rol.1' => 0, 'roles.id_rol.null' => 1]);

        $this->assertSame(
            [],
            $this->sentenciasBdGeneral,
            'Eliminar() consultó la base de datos general en lugar de la de seguridad.'
        );
    }

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
