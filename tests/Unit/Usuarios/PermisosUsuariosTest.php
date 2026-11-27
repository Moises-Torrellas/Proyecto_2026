<?php

namespace tests\Unit\Usuarios;

use App\modelo\Conexion;
use PDO;
use PDOStatement;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloUsuarios;
use Exception;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('permisosUsuarios')]
class PermisosUsuariosTest extends TestCase
{
    protected array $sentencias = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $_SESSION = [];
        $this->sentencias = [];
        parent::tearDown();
    }

    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoMock);
    }

    protected function crearPdoMock(array $conteos = [], array $respuestas = [], string $lastInsertId = '25'): PDO
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn($lastInsertId);
        $pdoMock->method('exec')->willReturnCallback(function (string $sql) {
            $this->sentencias[] = ['sql' => $sql, 'params' => []];
            return 0;
        });
        $pdoMock->method('query')->willReturnCallback(
            fn(string $sql) => $this->crearSentencia($sql, $respuestas, true)
        );
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

    private function crearSentencia(string $sql, array $respuestas, bool $ejecutadaDirecto = false): PDOStatement
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
        $stmt->method('fetch')->willReturn($config['fetch'] ?? false);
        $stmt->method('fetchAll')->willReturn($config['fetchAll'] ?? []);
        $stmt->method('fetchColumn')->willReturn($config['fetchColumn'] ?? false);

        if ($ejecutadaDirecto) {
            $this->sentencias[] = ['sql' => $sql, 'params' => []];
            if (isset($config['error'])) {
                throw $config['error'];
            }
        }

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

    protected function sentenciasEjecutadas(string $fragmento): array
    {
        return array_values(array_filter(
            $this->sentencias,
            fn($s) => str_contains($s['sql'], $fragmento)
        ));
    }

    private function respuestasPermisos(array $extra = []): array
    {
        return array_merge($extra, [
            'FROM permisos_rol' => ['fetchAll' => [1, 2]],
            'SELECT id_permiso FROM permisos' => ['fetchAll' => [1, 2, 3]],
        ]);
    }

    private function guardar(array $permisos, array $extra = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock([], $this->respuestasPermisos($extra)));
        return (new ModeloUsuarios())->procesarDatos([
            'accion'   => 'guardar_permisos_usuario',
            'id'       => 5,
            'permisos' => $permisos,
        ]);
    }

    public function testCargarPermisosUsuario(): void
    {
        $filas = [
            ['id_modulo' => 6, 'id_permiso' => 1, 'clave' => 'ingresar_atletas', 'asignado' => 1, 'excepcion' => 2],
            ['id_modulo' => 6, 'id_permiso' => 2, 'clave' => 'registrar_atleta', 'asignado' => 0, 'excepcion' => 0],
        ];
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos m' => ['fetchAll' => $filas],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos(['accion' => 'CargarPermisosUsuario', 'id' => 5]);

        $this->assertSame('CargarPermisosUsuario', $resultado['accion']);
        $this->assertSame($filas, $resultado['datos']);
        $this->assertSame(5, $this->sentenciaEjecutada('FROM modulos m')['params'][':id1']);
    }

    public function testGuardarPermisosConExcepciones(): void
    {
        $resultado = $this->guardar([2 => 1, 3 => 1]);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $this->assertSame([':id' => 5], $this->sentenciaEjecutada('DELETE FROM `excepciones`')['params']);

        $inserts = $this->sentenciasEjecutadas('INSERT INTO `excepciones`');
        $this->assertCount(2, $inserts);
        $this->assertSame([':id_usuario' => 5, ':id_permiso' => 1, ':tipo' => 0], $inserts[0]['params']);
        $this->assertSame([':id_usuario' => 5, ':id_permiso' => 3, ':tipo' => 1], $inserts[1]['params']);
    }

    public function testGuardarPermisosIgualesAlRol(): void
    {
        $resultado = $this->guardar([1 => 1, 2 => 1]);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertNotNull($this->sentenciaEjecutada('DELETE FROM `excepciones`'));
        $this->assertCount(0, $this->sentenciasEjecutadas('INSERT INTO `excepciones`'));
    }

    public function testGuardarSinPermisosDeniegaLosDelRol(): void
    {
        $resultado = $this->guardar([]);

        $this->assertSame('exito', $resultado['accion']);

        $inserts = $this->sentenciasEjecutadas('INSERT INTO `excepciones`');
        $this->assertCount(2, $inserts);
        foreach ($inserts as $insert) {
            $this->assertSame(0, $insert['params'][':tipo']);
        }
    }

    public function testErrorBaseDatosAlGuardarPermisos(): void
    {
        $resultado = $this->guardar([3 => 1], [
            'INSERT INTO `excepciones`' => ['error' => new Exception('Error al guardar excepciones')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al guardar excepciones', $resultado['codigo']);
    }
}
