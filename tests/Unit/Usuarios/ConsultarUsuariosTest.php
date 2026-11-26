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

/**
 * RF-USU-02: El sistema debe listar los usuarios registrados y permitir filtrarlos
 * (buscador general de la tabla y filtros específicos del reporte).
 *
 * Escenarios:
 *  E1. Listado general sin filtros.
 *  E2. Búsqueda general desde la tabla.
 *  E3. Filtros específicos del reporte (cédula, nombre, apellido, rol).
 *  E4. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('consultarUsuarios')]
class ConsultarUsuariosTest extends TestCase
{
    /** Registro de las sentencias ejecutadas: ['sql' => string, 'params' => array] */
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

    /**
     * ModeloUsuarios trabaja sobre la base de datos de seguridad (conexSG), por lo que
     * el PDO simulado se inyecta en la propiedad estática "instanciaSG" de Conexion.
     */
    protected function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdoMock);
        $reflector->getProperty('instancia')->setValue(null, $pdoMock);
    }

    /**
     * Crea un PDO simulado.
     *
     * @param array $conteos   Resultados de las verificaciones de existencia (trait VerificarExistencia):
     *                         'tabla.columna.estatus' => int   (usar 'null' cuando no se filtra por estatus)
     *                         'propia.columna'        => int   (verificarExistenciaPropia)
     *                         Cualquier verificación no indicada devuelve 0 (no existe).
     * @param array $respuestas Fragmento de SQL => ['fetch' => , 'fetchAll' => , 'fetchColumn' => , 'error' => Exception]
     *                         Se usa el primer fragmento contenido en la sentencia (el orden importa).
     */
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
            // verificarExistenciaPropia: ... WHERE idUsuario = :id AND <columna> = :valor
            if (preg_match('/SELECT COUNT\(\*\) FROM `\w+`\s+WHERE\s+\w+\s*=\s*:id\s+AND\s+(\w+)\s*=\s*:valor/', $sql, $m)) {
                return $this->crearSentenciaConteo(fn() => $conteos['propia.' . $m[1]] ?? 0);
            }
            // verificarExistencia: SELECT COUNT(*) FROM `<tabla>` WHERE <columna> = :valor [AND estatus = :estatus]
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

    /** Devuelve la primera sentencia ejecutada que contenga el fragmento indicado, o null. */
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

    // Caso 1 (E1): Listado exitoso sin filtros
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

    // Caso 2 (E1): Listado vacío cuando no hay usuarios
    public function testConsultaSinRegistros(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloUsuarios())->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    // Caso 3 (E2): La búsqueda general filtra por cédula, nombre, apellido, correo y rol
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

    // Caso 4 (E3): Reporte filtrado por cédula, nombre y apellido
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

    // Caso 5 (E3): Reporte filtrado por rol (el id se convierte a entero)
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

    // Caso 6 (E3): El rol "0" (todos) no se aplica como filtro
    public function testReporteRolTodosNoFiltra(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => $this->filas()],
        ]));

        (new ModeloUsuarios())->procesarDatos(['accion' => 'reporte', 'roles_id' => '0']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_usuarios');
        $this->assertStringNotContainsString(':rol', $sentencia['sql']);
    }

    // Caso 7 (E4): Error de base de datos devuelve mensaje controlado
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
