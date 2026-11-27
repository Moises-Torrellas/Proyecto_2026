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
#[Group('bloquearUsuarios')]
class BloquearUsuariosTest extends TestCase
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

    private function ejecutar(int $id, $bloqueo, array $conteos, array $respuestas = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloUsuarios())->procesarDatos(['accion' => 'bloquear', 'id' => $id, 'bloqueo' => $bloqueo]);
    }

    private function nivel(int $nivel, array $extra = []): array
    {
        return array_merge($extra, ['SELECT r.nivel_rol' => ['fetchColumn' => $nivel]]);
    }

    public function testBloquearUsuario(): void
    {
        $resultado = $this->ejecutar(5, 1, ['usuarios.idUsuario.1' => 1], $this->nivel(3));

        $this->assertSame(['accion' => 'exito'], $resultado);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios` SET `bloqueo`');
        $this->assertSame([':estado' => 2, ':id' => 5], $update['params']);
        $this->assertStringNotContainsString('intentos_fallidos', $update['sql']);
    }

    public function testDesbloquearUsuario(): void
    {
        $resultado = $this->ejecutar(5, 2, ['usuarios.idUsuario.1' => 1], $this->nivel(3));

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios` SET `bloqueo`');
        $this->assertSame([':estado' => 1, ':id' => 5], $update['params']);
        $this->assertStringContainsString('`intentos_fallidos` = 0', $update['sql']);
    }

    public function testNoBloqueaSuperUsuario(): void
    {
        $resultado = $this->ejecutar(1, 1, ['usuarios.idUsuario.1' => 1], $this->nivel(1));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(ASSOCIATES, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    public function testUsuarioNoExiste(): void
    {
        $resultado = $this->ejecutar(50, 1, ['usuarios.idUsuario.1' => 0]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
    }

    public function testNoBloqueaNivel1(): void
    {
        $resultado = $this->ejecutar(5, 1, ['usuarios.idUsuario.1' => 1], $this->nivel(1));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('ROLE_RULES_5', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    public function testNoBloqueaNivel2(): void
    {
        $resultado = $this->ejecutar(5, 1, ['usuarios.idUsuario.1' => 1], $this->nivel(2));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('ROLE_RULES_5', $resultado['codigo']);
    }

    public function testErrorBaseDatosAlBloquear(): void
    {
        $resultado = $this->ejecutar(
            5,
            1,
            ['usuarios.idUsuario.1' => 1],
            $this->nivel(3, ['UPDATE `usuarios`' => ['error' => new Exception('Error al bloquear')]])
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al bloquear', $resultado['codigo']);
    }
}
