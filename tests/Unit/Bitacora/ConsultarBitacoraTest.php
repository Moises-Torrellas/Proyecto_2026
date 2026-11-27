<?php

namespace tests\Unit\Bitacora;

use App\modelo\Conexion;
use PDO;
use PDOStatement;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloBitacora;
use Exception;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitBitacora')]
#[Group('consultarBitacora')]
class ConsultarBitacoraTest extends TestCase
{
    protected array $sentencias = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $_SESSION = [];
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->sentencias = [];
        parent::tearDown();
    }

    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoMock);
    }

    protected function crearPdoMock(array $respuestas = [], bool $enTransaccion = false): PDO
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn($enTransaccion);
        $pdoMock->method('rollBack')->willReturnCallback(function () {
            $this->sentencias[] = ['sql' => 'ROLLBACK', 'params' => []];
            return true;
        });
        $pdoMock->method('prepare')->willReturnCallback(
            fn(string $sql) => $this->crearSentencia($sql, $respuestas)
        );

        return $pdoMock;
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
            ['id_bitacora' => 2, 'nombreUsuario' => 'Juan', 'cedulaUsuario' => '12345678', 'nombre_modulo' => 'Roles', 'acciones' => 'Registró el Rol: Entrenador', 'fecha' => '2026-11-12', 'hora' => '10:30:00'],
            ['id_bitacora' => 1, 'nombreUsuario' => 'Juan', 'cedulaUsuario' => '12345678', 'nombre_modulo' => 'Roles', 'acciones' => 'Ingreso al Modulo', 'fecha' => '2026-11-12', 'hora' => '10:29:00'],
        ];
    }

    private function consultar(array $filtro, array $respuestas = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($respuestas ?: [
            'vista_consulta_bitacora' => ['fetchAll' => $this->filas()],
        ]));
        return (new ModeloBitacora())->Consultar($filtro);
    }

    private function sentencia(): array
    {
        return $this->sentenciaEjecutada('vista_consulta_bitacora');
    }

    public function testListadoInicial(): void
    {
        $resultado = $this->consultar([]);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        $sentencia = $this->sentencia();
        $this->assertStringContainsString('ORDER BY id_bitacora DESC', $sentencia['sql']);
        $this->assertStringContainsString('LIMIT :limit OFFSET :offset', $sentencia['sql']);
        $this->assertSame([':limit' => 100, ':offset' => 0], $sentencia['params']);
    }

    public function testListadoVacio(): void
    {
        $resultado = $this->consultar([], ['vista_consulta_bitacora' => ['fetchAll' => []]]);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    public function testPaginacion(): void
    {
        $this->consultar(['limit' => '50', 'offset' => '200']);

        $params = $this->sentencia()['params'];
        $this->assertSame(50, $params[':limit']);
        $this->assertSame(200, $params[':offset']);
    }

    public function testBusquedaGeneral(): void
    {
        $this->consultar(['filtro' => 'Rol']);

        $sentencia = $this->sentencia();
        foreach (['nombreUsuario LIKE :f1', 'cedulaUsuario LIKE :f2', 'acciones LIKE :f3', 'nombre_modulo LIKE :f4'] as $condicion) {
            $this->assertStringContainsString($condicion, $sentencia['sql']);
        }
        foreach ([':f1', ':f2', ':f3', ':f4'] as $param) {
            $this->assertSame('%Rol%', $sentencia['params'][$param]);
        }
    }

    public function testBusquedaVaciaNoFiltra(): void
    {
        $this->consultar(['filtro' => '']);

        $sentencia = $this->sentencia();
        $this->assertStringNotContainsString('LIKE', $sentencia['sql']);
    }

    public function testFiltroPorModulo(): void
    {
        $this->consultar(['id_modulo' => '103']);

        $sentencia = $this->sentencia();
        $this->assertStringContainsString('FROM modulos WHERE id_modulo = :id_modulo', $sentencia['sql']);
        $this->assertSame('103', $sentencia['params'][':id_modulo']);
    }

    public function testFiltroPorUsuario(): void
    {
        $this->consultar(['idUsuario' => '4']);

        $sentencia = $this->sentencia();
        $this->assertStringContainsString('FROM usuarios WHERE idUsuario = :idUsuario', $sentencia['sql']);
        $this->assertSame('4', $sentencia['params'][':idUsuario']);
    }

    public function testFiltroPorRangoFechas(): void
    {
        $this->consultar(['fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-12']);

        $sentencia = $this->sentencia();
        $this->assertStringContainsString('fecha BETWEEN :fecha_inicio AND :fecha_fin', $sentencia['sql']);
        $this->assertSame('2026-11-01', $sentencia['params'][':fecha_inicio']);
        $this->assertSame('2026-11-12', $sentencia['params'][':fecha_fin']);
    }

    public function testUnaSolaFechaNoFiltra(): void
    {
        $this->consultar(['fecha_inicio' => '2026-11-01']);

        $sentencia = $this->sentencia();
        $this->assertStringNotContainsString('BETWEEN', $sentencia['sql']);
        $this->assertArrayNotHasKey(':fecha_inicio', $sentencia['params']);
    }

    public function testFiltrosCombinados(): void
    {
        $this->consultar([
            'filtro'       => 'Elimin',
            'id_modulo'    => '103',
            'idUsuario'    => '4',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin'    => '2026-11-12',
        ]);

        $params = $this->sentencia()['params'];
        foreach ([':f1', ':f2', ':f3', ':f4', ':id_modulo', ':idUsuario', ':fecha_inicio', ':fecha_fin', ':limit', ':offset'] as $param) {
            $this->assertArrayHasKey($param, $params);
        }
    }

    public function testReporteSinPaginacion(): void
    {
        $resultado = $this->consultar(['accion' => 'reporte', 'limit' => 100]);

        $this->assertSame('consultar', $resultado['accion']);

        $sentencia = $this->sentencia();
        $this->assertStringContainsString('ORDER BY id_bitacora DESC', $sentencia['sql']);
        $this->assertStringNotContainsString('LIMIT', $sentencia['sql']);
        $this->assertArrayNotHasKey(':limit', $sentencia['params']);
    }

    public function testReporteConFiltros(): void
    {
        $this->consultar([
            'accion'       => 'reporte',
            'id_modulo'    => '103',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin'    => '2026-11-12',
        ]);

        $params = $this->sentencia()['params'];
        $this->assertSame([
            ':id_modulo'    => '103',
            ':fecha_inicio' => '2026-11-01',
            ':fecha_fin'    => '2026-11-12',
        ], $params);
    }

    public function testErrorBaseDatos(): void
    {
        $resultado = $this->consultar([], [
            'vista_consulta_bitacora' => ['error' => new Exception('Vista no encontrada')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al listar bitácora: Vista no encontrada', $resultado['mensaje']);
    }
}
