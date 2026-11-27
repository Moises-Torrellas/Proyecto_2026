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
#[Group('buscarUsuarios')]
class BuscarUsuariosTest extends TestCase
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

    public function testBuscarExitoso(): void
    {
        $fila = [
            'idUsuario' => 5, 'cedulaUsuario' => '27123456', 'nombreUsuario' => 'Carlos',
            'apellidoUsuario' => 'Mendoza', 'correo' => 'carlos@correo.com', 'id_rol' => 3, 'nivel_rol' => 3,
        ];
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM `usuarios` u' => ['fetchAll' => [$fila]],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos(['accion' => 'buscar', 'id' => 5]);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame($fila, $resultado['datos'][0]);

        $sentencia = $this->sentenciaEjecutada('FROM `usuarios` u');
        $this->assertStringContainsString('u.estatus=1', $sentencia['sql']);
        $this->assertSame(5, $sentencia['params'][':id']);
    }

    public function testBuscarNoEncontrado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM `usuarios` u' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos(['accion' => 'buscar', 'id' => 99]);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    public function testErrorBaseDatosAlBuscar(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM `usuarios` u' => ['error' => new Exception('Error de consulta')],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos(['accion' => 'buscar', 'id' => 5]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de consulta', $resultado['mensaje']);
    }

    public function testRegistrarUltimoIngreso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock());

        $resultado = (new ModeloUsuarios())->registrarUltimoIngreso(5);

        $this->assertSame('exito', $resultado['accion']);
        $sentencia = $this->sentenciaEjecutada('`ultimo_ingreso` = NOW()');
        $this->assertSame(5, $sentencia['params'][':id']);
    }

    public function testErrorRegistrarUltimoIngreso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'ultimo_ingreso' => ['error' => new Exception('Sin conexión')],
        ]));

        $resultado = (new ModeloUsuarios())->registrarUltimoIngreso(5);

        $this->assertSame('error', $resultado['accion']);
        $this->assertStringContainsString('Sin conexión', $resultado['mensaje']);
    }
}
