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
#[Group('consultarPermisos')]
class ConsultarPermisosTest extends TestCase
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
            ['id_permiso' => 1, 'nombre_permiso' => 'Ingresar Atletas', 'clave' => 'ingresar_atletas', 'id_modulo' => 6, 'nombre_modulo' => 'Atletas'],
            ['id_permiso' => 2, 'nombre_permiso' => 'Registrar Atleta', 'clave' => 'registrar_atleta', 'id_modulo' => 6, 'nombre_modulo' => 'Atletas'],
        ];
    }

    public function testConsultaExitosa(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_permisos' => ['fetchAll' => $this->filas()],
        ]));

        $resultado = (new ModeloPermisos())->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_permisos');
        $this->assertStringContainsString('ORDER BY id_modulo ASC, id_permiso ASC', $sentencia['sql']);
        $this->assertSame([], $sentencia['params']);
    }

    public function testBusqueda(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_permisos' => ['fetchAll' => [$this->filas()[1]]],
        ]));

        $resultado = (new ModeloPermisos())->Consultar(['filtro' => 'registrar']);

        $this->assertCount(1, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_permisos');
        $this->assertStringContainsString('nombre_permiso LIKE :f1 OR clave LIKE :f2', $sentencia['sql']);
        $this->assertSame([':f1' => '%registrar%', ':f2' => '%registrar%'], $sentencia['params']);
    }

    public function testConsultaError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_permisos' => ['error' => new Exception('Vista no encontrada')],
        ]));

        $resultado = (new ModeloPermisos())->Consultar();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Vista no encontrada', $resultado['mensaje']);
    }

    public function testBuscarExitoso(): void
    {
        $fila = ['id_permiso' => 2, 'id_modulo' => 6, 'nombre' => 'Registrar Atleta', 'clave' => 'registrar_atleta', 'estatus' => 1];
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM permisos WHERE id_permiso' => ['fetchAll' => [$fila]],
        ]));

        $resultado = (new ModeloPermisos())->ProcesarDatos(['accion' => 'buscar', 'id' => '2']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([$fila], $resultado['datos']);
        $this->assertSame([':id' => '2'], $this->sentenciaEjecutada('FROM permisos WHERE id_permiso')['params']);
    }

    public function testBuscarNoEncontrado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM permisos WHERE id_permiso' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloPermisos())->ProcesarDatos(['accion' => 'buscar', 'id' => '999']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    public function testBuscarError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM permisos WHERE id_permiso' => ['error' => new Exception('Error de consulta')],
        ]));

        $resultado = (new ModeloPermisos())->ProcesarDatos(['accion' => 'buscar', 'id' => '2']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de consulta', $resultado['mensaje']);
    }
}
