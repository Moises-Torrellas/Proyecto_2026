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
#[Group('consultarRoles')]
class ConsultarRolesTest extends TestCase
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

    private function filas(): array
    {
        return [
            ['id_rol' => 3, 'nombre_rol' => 'Entrenador', 'descripcion' => 'Dirige los entrenamientos', 'nivel_rol' => 3],
            ['id_rol' => 4, 'nombre_rol' => 'Secretaria', 'descripcion' => 'Gestiona los pagos', 'nivel_rol' => 3],
        ];
    }

    public function testConsultaExitosa(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['fetchAll' => $this->filas()],
        ]));

        $resultado = (new ModeloRoles())->consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('FROM roles');
        $this->assertStringContainsString('nivel_rol != 1', $sentencia['sql']);
        $this->assertStringContainsString('nivel_rol != 2', $sentencia['sql']);
        $this->assertStringContainsString('ORDER BY id_rol ASC', $sentencia['sql']);
        $this->assertSame([], $sentencia['params']);
    }

    public function testConsultaSinRegistros(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloRoles())->consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    public function testBusquedaPorNombre(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['fetchAll' => [$this->filas()[0]]],
        ]));

        $resultado = (new ModeloRoles())->consultar(['filtro' => 'Entre']);

        $this->assertCount(1, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('FROM roles');
        $this->assertStringContainsString('nombre_rol LIKE :f1', $sentencia['sql']);
        $this->assertSame('%Entre%', $sentencia['params'][':f1']);
    }

    public function testReporteSinFiltros(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['fetchAll' => $this->filas()],
        ]));

        $resultado = (new ModeloRoles())->procesarDatos(['accion' => 'reporte']);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
    }

    public function testReporteFiltradoPorNombre(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['fetchAll' => [$this->filas()[0]]],
        ]));

        (new ModeloRoles())->procesarDatos(['accion' => 'reporte', 'nombre' => 'Entrenador']);

        $sentencia = $this->sentenciaEjecutada('FROM roles');
        $this->assertStringContainsString(
            'nombre_rol LIKE',
            $sentencia['sql'],
            'El filtro "nombre" del reporte no se aplica en la consulta.'
        );
    }

    public function testErrorBaseDatos(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['error' => new Exception('Tabla no encontrada')],
        ]));

        $resultado = (new ModeloRoles())->consultar();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al listar roles: Tabla no encontrada', $resultado['mensaje']);
    }
}
