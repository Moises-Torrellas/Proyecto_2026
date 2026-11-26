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

/**
 * RF-PER-02: El sistema debe listar los permisos agrupados por módulo (ordenados por módulo
 * y luego por permiso), permitir buscarlos por nombre o clave y consultar el detalle de uno.
 *
 * Escenarios:
 *  E1. Listado general.
 *  E2. Búsqueda por nombre o clave.
 *  E3. Detalle de un permiso.
 *  E4. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitPermisos')]
#[Group('consultarPermisos')]
class ConsultarPermisosTest extends TestCase
{
    /** Registro de las sentencias ejecutadas: ['sql' => string, 'params' => array] */
    protected array $sentencias = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $this->sentencias = [];
        parent::tearDown();
    }

    /**
     * Los permisos viven en la base de datos de seguridad (conexSG), por lo que el PDO
     * simulado se inyecta en la propiedad estática "instanciaSG" de Conexion.
     */
    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoMock);
    }

    /**
     * @param array $conteos   Resultados de las verificaciones de existencia (trait VerificarExistencia):
     *                         'tabla.columna.estatus' => int   (usar 'null' cuando no se filtra por estatus)
     *                         'propia.columna'        => int   (verificarExistenciaPropia)
     *                         Cualquier verificación no indicada devuelve 0 (no existe).
     * @param array $respuestas Fragmento de SQL => ['fetchAll' => , 'rowCount' => , 'error' => Exception]
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

    // Caso 1 (E1): Listado ordenado por módulo y permiso
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

    // Caso 2 (E2): La búsqueda filtra por nombre o clave del permiso
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

    // Caso 3 (E4): Error de base de datos al listar
    public function testConsultaError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_permisos' => ['error' => new Exception('Vista no encontrada')],
        ]));

        $resultado = (new ModeloPermisos())->Consultar();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Vista no encontrada', $resultado['mensaje']);
    }

    // Caso 4 (E3): Detalle de un permiso existente
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

    // Caso 5 (E3): Detalle de un permiso inexistente devuelve datos vacíos
    public function testBuscarNoEncontrado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM permisos WHERE id_permiso' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloPermisos())->ProcesarDatos(['accion' => 'buscar', 'id' => '999']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    // Caso 6 (E4): Error de base de datos al buscar
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
