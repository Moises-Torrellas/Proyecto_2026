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
#[Group('bloquearPermisos')]
class BloquearPermisosTest extends TestCase
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

    protected function textoError(array $resultado): ?string
    {
        return $resultado['codigo'] ?? $resultado['mensaje'] ?? null;
    }

    private function ejecutar(string $bloqueo, array $respuestas = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock([], $respuestas));
        return (new ModeloPermisos())->ProcesarDatos(['accion' => 'bloquear', 'id' => '9', 'bloqueo' => $bloqueo]);
    }

    public function testBloquear(): void
    {
        $resultado = $this->ejecutar('1');

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame(
            [':estado' => 2, ':id' => '9'],
            $this->sentenciaEjecutada('UPDATE `permisos` SET `estatus`')['params']
        );
    }

    public function testDesbloquear(): void
    {
        $resultado = $this->ejecutar('2');

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame(
            [':estado' => 1, ':id' => '9'],
            $this->sentenciaEjecutada('UPDATE `permisos` SET `estatus`')['params']
        );
    }

    public function testPermisoNoExiste(): void
    {
        $resultado = $this->ejecutar('1', ['UPDATE `permisos`' => ['rowCount' => 0]]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(
            'No se encontró el permiso o el estatus ya era el solicitado.',
            $this->textoError($resultado)
        );
    }

    public function testErrorEnClaveCodigo(): void
    {
        $resultado = $this->ejecutar('1', ['UPDATE `permisos`' => ['rowCount' => 0]]);

        $this->assertArrayHasKey(
            'codigo',
            $resultado,
            'Bloqueo() devuelve el error en "mensaje", pero el controlador lee "codigo".'
        );
    }

    public function testErrorBaseDatosAlBloquear(): void
    {
        $resultado = $this->ejecutar('1', [
            'UPDATE `permisos`' => ['error' => new Exception('Error al bloquear permiso')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al bloquear permiso', $this->textoError($resultado));
    }
}
