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
 * RF-USU-07: El sistema debe permitir consultar el detalle de un usuario activo
 * (incluyendo el nivel de su rol) y registrar la fecha de su último ingreso.
 *
 * Escenarios:
 *  E1. Buscar un usuario activo existente.
 *  E2. Buscar un usuario inexistente o eliminado.
 *  E3. Falla de la base de datos al buscar.
 *  E4. Registrar el último ingreso del usuario.
 */
#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('buscarUsuarios')]
class BuscarUsuariosTest extends TestCase
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

    // Caso 1 (E1): Buscar usuario activo devuelve sus datos y el nivel de rol
    public function testBuscarExitoso(): void
    {
        $fila = [
            'idUsuario' => 5, 'cedulaUsuario' => '27123456', 'nombreUsuario' => 'Carlos',
            'apellidoUsuario' => 'Mendoza', 'correo' => 'carlos@correo.com', 'id_rol' => 3, 'nivel_rol' => 3,
        ];
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM `usuarios` u' => ['fetchAll' => [$fila]],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos(['accion' => 'buscar', 'id' => 5]);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame($fila, $resultado['datos'][0]);

        $sentencia = $this->sentenciaEjecutada('FROM `usuarios` u');
        $this->assertStringContainsString('u.estatus=1', $sentencia['sql']);
        $this->assertSame(5, $sentencia['params'][':id']);
    }

    // Caso 2 (E2): Usuario inexistente o eliminado devuelve datos vacíos
    public function testBuscarNoEncontrado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM `usuarios` u' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos(['accion' => 'buscar', 'id' => 99]);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    // Caso 3 (E3): Error de base de datos al buscar
    public function testErrorBaseDatosAlBuscar(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM `usuarios` u' => ['error' => new Exception('Error de consulta')],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos(['accion' => 'buscar', 'id' => 5]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de consulta', $resultado['mensaje']);
    }

    // Caso 4 (E4): Registrar el último ingreso actualiza la fecha del usuario
    public function testRegistrarUltimoIngreso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock());

        $resultado = (new ModeloUsuarios())->registrarUltimoIngreso(5);

        $this->assertSame('exito', $resultado['accion']);
        $sentencia = $this->sentenciaEjecutada('`ultimo_ingreso` = NOW()');
        $this->assertSame(5, $sentencia['params'][':id']);
    }

    // Caso 5 (E4): Error al registrar el último ingreso
    public function testErrorRegistrarUltimoIngreso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'ultimo_ingreso' => ['error' => new Exception('Sin conexión')],
        ]));

        $resultado = (new ModeloUsuarios())->registrarUltimoIngreso(5);

        $this->assertSame('error', $resultado['accion']);
        $this->assertStringContainsString('Sin conexión', $resultado['mensaje']);
    }
}
