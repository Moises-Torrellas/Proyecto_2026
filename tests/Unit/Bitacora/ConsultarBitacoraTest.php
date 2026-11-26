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

/**
 * RF-BIT-03: El sistema debe mostrar los registros de la bitácora del más reciente al más
 * antiguo, paginados (100 por página por defecto), y permitir filtrarlos por texto
 * (usuario, cédula, acción o módulo), por módulo, por usuario y por rango de fechas.
 * El reporte incluye todos los registros que cumplan los filtros, sin paginar.
 *
 * Escenarios:
 *  E1. Listado inicial sin filtros.
 *  E2. Navegación entre páginas.
 *  E3. Búsqueda general por texto.
 *  E4. Filtros por módulo, usuario y rango de fechas.
 *  E5. Generación del reporte.
 *  E6. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitBitacora')]
#[Group('consultarBitacora')]
class ConsultarBitacoraTest extends TestCase
{
    /** Registro de las sentencias ejecutadas: ['sql' => string, 'params' => array] */
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

    /**
     * La bitácora vive en la base de datos de seguridad (conexSG), por lo que el PDO
     * simulado se inyecta en la propiedad estática "instanciaSG" de Conexion.
     */
    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoMock);
    }

    /**
     * @param array $respuestas Fragmento de SQL => ['fetchAll' => , 'error' => Exception]
     *                          Se usa el primer fragmento contenido en la sentencia (el orden importa).
     */
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

    // Caso 1 (E1): Listado inicial: del más reciente al más antiguo, primera página de 100
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

    // Caso 2 (E1): Bitácora vacía devuelve un listado vacío
    public function testListadoVacio(): void
    {
        $resultado = $this->consultar([], ['vista_consulta_bitacora' => ['fetchAll' => []]]);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    // Caso 3 (E2): Paginación con límite y desplazamiento indicados (convertidos a entero)
    public function testPaginacion(): void
    {
        $this->consultar(['limit' => '50', 'offset' => '200']);

        $params = $this->sentencia()['params'];
        $this->assertSame(50, $params[':limit']);
        $this->assertSame(200, $params[':offset']);
    }

    // Caso 4 (E3): La búsqueda general filtra por usuario, cédula, acción y módulo
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

    // Caso 5 (E3): Una búsqueda vacía no agrega condiciones
    public function testBusquedaVaciaNoFiltra(): void
    {
        $this->consultar(['filtro' => '']);

        $sentencia = $this->sentencia();
        $this->assertStringNotContainsString('LIKE', $sentencia['sql']);
    }

    // Caso 6 (E4): Filtro por módulo
    public function testFiltroPorModulo(): void
    {
        $this->consultar(['id_modulo' => '103']);

        $sentencia = $this->sentencia();
        $this->assertStringContainsString('FROM modulos WHERE id_modulo = :id_modulo', $sentencia['sql']);
        $this->assertSame('103', $sentencia['params'][':id_modulo']);
    }

    // Caso 7 (E4): Filtro por usuario
    public function testFiltroPorUsuario(): void
    {
        $this->consultar(['idUsuario' => '4']);

        $sentencia = $this->sentencia();
        $this->assertStringContainsString('FROM usuarios WHERE idUsuario = :idUsuario', $sentencia['sql']);
        $this->assertSame('4', $sentencia['params'][':idUsuario']);
    }

    // Caso 8 (E4): Filtro por rango de fechas
    public function testFiltroPorRangoFechas(): void
    {
        $this->consultar(['fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-12']);

        $sentencia = $this->sentencia();
        $this->assertStringContainsString('fecha BETWEEN :fecha_inicio AND :fecha_fin', $sentencia['sql']);
        $this->assertSame('2026-11-01', $sentencia['params'][':fecha_inicio']);
        $this->assertSame('2026-11-12', $sentencia['params'][':fecha_fin']);
    }

    // Caso 9 (E4): Con una sola fecha no se aplica el filtro de rango
    public function testUnaSolaFechaNoFiltra(): void
    {
        $this->consultar(['fecha_inicio' => '2026-11-01']);

        $sentencia = $this->sentencia();
        $this->assertStringNotContainsString('BETWEEN', $sentencia['sql']);
        $this->assertArrayNotHasKey(':fecha_inicio', $sentencia['params']);
    }

    // Caso 10 (E4): Todos los filtros combinados
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

    // Caso 11 (E5): El reporte incluye todos los registros, sin paginar
    public function testReporteSinPaginacion(): void
    {
        $resultado = $this->consultar(['accion' => 'reporte', 'limit' => 100]);

        $this->assertSame('consultar', $resultado['accion']);

        $sentencia = $this->sentencia();
        $this->assertStringContainsString('ORDER BY id_bitacora DESC', $sentencia['sql']);
        $this->assertStringNotContainsString('LIMIT', $sentencia['sql']);
        $this->assertArrayNotHasKey(':limit', $sentencia['params']);
    }

    // Caso 12 (E5): El reporte aplica los filtros seleccionados
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

    // Caso 13 (E6): Error de base de datos devuelve mensaje controlado
    public function testErrorBaseDatos(): void
    {
        $resultado = $this->consultar([], [
            'vista_consulta_bitacora' => ['error' => new Exception('Vista no encontrada')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al listar bitácora: Vista no encontrada', $resultado['mensaje']);
    }
}
