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
#[Group('consultarUsuarios')]
class ConsultarUsuariosTest extends TestCase
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

    private function filas(): array
    {
        return [
            ['idUsuario' => 1, 'cedulaUsuario' => '12345678', 'nombreUsuario' => 'Juan', 'apellidoUsuario' => 'Pérez', 'nombre_rol' => 'Administrador'],
            ['idUsuario' => 2, 'cedulaUsuario' => '87654321', 'nombreUsuario' => 'Ana', 'apellidoUsuario' => 'Gómez', 'nombre_rol' => 'Entrenador'],
        ];
    }

    public function testConsultaExitosaSinFiltros(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => $this->filas()],
        ]));

        $resultado = (new ModeloUsuarios())->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_usuarios');
        $this->assertStringContainsString('ORDER BY idUsuario ASC', $sentencia['sql']);
        $this->assertSame([], $sentencia['params']);
    }

    public function testConsultaSinRegistros(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloUsuarios())->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    public function testBusquedaGeneral(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => [$this->filas()[1]]],
        ]));

        $resultado = (new ModeloUsuarios())->Consultar(['filtro' => 'Ana']);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_usuarios');
        $this->assertStringContainsString('nombre_rol LIKE :f5', $sentencia['sql']);
        foreach ([':f1', ':f2', ':f3', ':f4', ':f5'] as $param) {
            $this->assertSame('%Ana%', $sentencia['params'][$param]);
        }
    }

    public function testReporteConFiltrosEspecificos(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => [$this->filas()[0]]],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos([
            'accion'   => 'reporte',
            'cedula'   => '1234',
            'nombre'   => 'juan',
            'apellido' => 'pérez',
        ]);

        $this->assertSame('consultar', $resultado['accion']);

        $params = $this->sentenciaEjecutada('vista_consulta_usuarios')['params'];
        $this->assertSame('1234%', $params[':cedula']);
        $this->assertSame('%Juan%', $params[':nombre']);
        $this->assertSame('%Pérez%', $params[':apellido']);
        $this->assertArrayNotHasKey(':rol', $params);
    }

    public function testReporteFiltradoPorRol(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => $this->filas()],
        ]));

        (new ModeloUsuarios())->procesarDatos(['accion' => 'reporte', 'roles_id' => '2']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_usuarios');
        $this->assertStringContainsString('id_rol = :rol', $sentencia['sql']);
        $this->assertSame(2, $sentencia['params'][':rol']);
    }

    public function testReporteRolTodosNoFiltra(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => $this->filas()],
        ]));

        (new ModeloUsuarios())->procesarDatos(['accion' => 'reporte', 'roles_id' => '0']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_usuarios');
        $this->assertStringNotContainsString(':rol', $sentencia['sql']);
    }

    public function testErrorBaseDatos(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['error' => new Exception('Tabla no encontrada')],
        ]));

        $resultado = (new ModeloUsuarios())->Consultar();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Ocurrio un error al listar los usuarios', $resultado['mensaje']);
    }
}
