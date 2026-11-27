<?php

namespace tests\Unit\Modulos;

use App\modelo\Conexion;
use PDO;
use PDOStatement;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloModulos;
use Exception;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitModulos')]
#[Group('consultarModulos')]
class ConsultarModulosTest extends TestCase
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

    private function filas(): array
    {
        return [
            ['id_modulo' => 6, 'nombre_modulo' => 'Atletas', 'descripcion' => 'Gestión de atletas', 'icono' => 'user', 'estatus' => 1],
            ['id_modulo' => 7, 'nombre_modulo' => 'Representantes', 'descripcion' => 'Gestión de representantes', 'icono' => 'users', 'estatus' => 1],
        ];
    }

    public function testConsultaExitosa(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos' => ['fetchAll' => $this->filas()],
        ]));

        $resultado = (new ModeloModulos())->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('FROM modulos');
        $this->assertStringContainsString('id_modulo NOT IN (4, 5, 8)', $sentencia['sql']);
        $this->assertStringContainsString('ORDER BY id_modulo ASC', $sentencia['sql']);
        $this->assertSame([], $sentencia['params']);
    }

    public function testBusquedaPorNombre(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos' => ['fetchAll' => [$this->filas()[0]]],
        ]));

        $resultado = (new ModeloModulos())->Consultar(['filtro' => 'Atle']);

        $this->assertCount(1, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('FROM modulos');
        $this->assertStringContainsString('nombre_modulo LIKE :f1', $sentencia['sql']);
        $this->assertSame([':f1' => '%Atle%'], $sentencia['params']);
    }

    public function testConsultaError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos' => ['error' => new Exception('Tabla no encontrada')],
        ]));

        $resultado = (new ModeloModulos())->Consultar();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Tabla no encontrada', $resultado['mensaje']);
    }

    public function testBuscarExitoso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos WHERE id_modulo' => ['fetchAll' => [$this->filas()[0]]],
        ]));

        $resultado = (new ModeloModulos())->ProcesarDatos(['accion' => 'buscar', 'id' => '6']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([$this->filas()[0]], $resultado['datos']);
        $this->assertSame([':id' => '6'], $this->sentenciaEjecutada('FROM modulos WHERE id_modulo')['params']);
    }

    public function testBuscarNoEncontrado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos WHERE id_modulo' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloModulos())->ProcesarDatos(['accion' => 'buscar', 'id' => '999']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    public function testBuscarError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos WHERE id_modulo' => ['error' => new Exception('Error de consulta')],
        ]));

        $resultado = (new ModeloModulos())->ProcesarDatos(['accion' => 'buscar', 'id' => '6']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de consulta', $resultado['mensaje']);
    }
}
