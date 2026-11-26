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

/**
 * RF-ROL-02: El sistema debe listar los roles registrados, ocultando los roles protegidos
 * (nivel 1 Super Usuario y nivel 2), y permitir filtrarlos por nombre tanto desde el
 * buscador de la tabla como desde el reporte.
 *
 * Escenarios:
 *  E1. Listado general sin filtros.
 *  E2. Búsqueda por nombre desde la tabla.
 *  E3. Reporte filtrado por nombre.
 *  E4. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitRoles')]
#[Group('consultarRoles')]
class ConsultarRolesTest extends TestCase
{
    /** Registro de las sentencias ejecutadas: ['sql' => string, 'params' => array] */
    protected array $sentencias = [];

    /** Sentencias que el modelo intentó preparar sobre la base de datos general. */
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

    /**
     * Los roles viven en la base de datos de seguridad (conexSG). El PDO simulado se inyecta
     * en "instanciaSG"; la conexión general ("instancia") se reemplaza por un PDO que falla,
     * de modo que si el modelo consulta por error la base de datos general la prueba lo detecta.
     */
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

    /**
     * Crea el PDO simulado de la base de datos de seguridad.
     *
     * @param array $conteos   Resultados de las verificaciones de existencia (trait VerificarExistencia):
     *                         'tabla.columna.estatus' => int   (usar 'null' cuando no se filtra por estatus)
     *                         'propia.columna'        => int   (verificarExistenciaPropia)
     *                         Cualquier verificación no indicada devuelve 0 (no existe).
     * @param array $respuestas Fragmento de SQL => ['fetchAll' => , 'error' => Exception]
     *                         Se usa el primer fragmento contenido en la sentencia (el orden importa).
     */
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

    // Caso 1 (E1): Listado exitoso que excluye los roles protegidos de nivel 1 y 2
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

    // Caso 2 (E1): Listado vacío cuando solo existen roles protegidos
    public function testConsultaSinRegistros(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloRoles())->consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    // Caso 3 (E2): La búsqueda desde la tabla filtra por nombre del rol
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

    // Caso 4 (E3): El reporte sin filtros lista todos los roles visibles
    public function testReporteSinFiltros(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['fetchAll' => $this->filas()],
        ]));

        $resultado = (new ModeloRoles())->procesarDatos(['accion' => 'reporte']);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
    }

    // Caso 5 (E3): El reporte filtrado por nombre aplica el filtro en la consulta
    // (el controlador envía el campo "nombre" desde el modal de reporte)
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

    // Caso 6 (E4): Error de base de datos devuelve mensaje controlado
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
