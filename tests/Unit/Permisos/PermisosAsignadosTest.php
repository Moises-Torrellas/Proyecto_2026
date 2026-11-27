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

#[Group('unit')]
#[Group('unitPermisos')]
#[Group('permisosAsignados')]
class PermisosAsignadosTest extends TestCase
{
    protected array $sentencias = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $this->sentencias = [];
        parent::tearDown();
    }

    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoMock);
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

    public function testPermisosRoles(): void
    {
        $filas = [['id_rol' => 3, 'id_modulo' => 6, 'id_permiso' => 1, 'asignado' => 1]];
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos m' => ['fetchAll' => $filas],
        ]));

        $resultado = (new ModeloPermisos())->permisosRoles(3);

        $this->assertSame('CargarPermisos', $resultado['accion']);
        $this->assertSame($filas, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('FROM modulos m');
        $this->assertStringContainsString('NOT IN (4, 5, 8, 1, 2, 3)', $sentencia['sql']);
        $this->assertStringContainsString('LEFT JOIN permisos_rol', $sentencia['sql']);
        $this->assertSame([':id1' => 3, ':id2' => 3, ':id3' => 3], $sentencia['params']);
    }

    public function testPermisosRolesError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos m' => ['error' => new Exception('Error de consulta')],
        ]));

        $resultado = (new ModeloPermisos())->permisosRoles(3);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de consulta', $resultado['mensaje']);
    }

    public function testPermisosUsuarios(): void
    {
        $filas = [
            ['idUsuario' => 5, 'id_permiso' => 1, 'asignado' => 0, 'excepcion' => 0],
            ['idUsuario' => 5, 'id_permiso' => 2, 'asignado' => 1, 'excepcion' => 2],
        ];
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos m' => ['fetchAll' => $filas],
        ]));

        $resultado = (new ModeloPermisos())->permisosUsuarios(5);

        $this->assertSame('CargarPermisosUsuario', $resultado['accion']);
        $this->assertSame($filas, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('FROM modulos m');
        $this->assertStringContainsString('NOT IN (4, 5, 8, 1, 2, 3)', $sentencia['sql']);
        $this->assertStringContainsString('LEFT JOIN permisos_rol', $sentencia['sql']);
        $this->assertStringContainsString('LEFT JOIN excepciones', $sentencia['sql']);
        $this->assertSame([':id1' => 5, ':id2' => 5, ':id3' => 5, ':id4' => 5], $sentencia['params']);
    }

    public function testPermisosUsuariosError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos m' => ['error' => new Exception('Error de consulta')],
        ]));

        $resultado = (new ModeloPermisos())->permisosUsuarios(5);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de consulta', $resultado['mensaje']);
    }
}
