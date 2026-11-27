<?php

namespace tests\Unit\Roles;

use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRoles;
use App\modelo\ModeloPermisos;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitRoles')]
#[Group('permisosRoles')]
class PermisosRolesTest extends TestCase
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

    protected function sentenciasEjecutadas(string $fragmento): array
    {
        return array_values(array_filter(
            $this->sentencias,
            fn($s) => str_contains($s['sql'], $fragmento)
        ));
    }

    private function modeloConPermisos(array|Exception $respuesta): ModeloRoles
    {
        $permisoMock = $this->createMock(ModeloPermisos::class);
        if ($respuesta instanceof Exception) {
            $permisoMock->method('permisosRoles')->willThrowException($respuesta);
        } else {
            $permisoMock->method('permisosRoles')->willReturn($respuesta);
        }

        $modelo = new ModeloRoles();
        $modelo->setPermiso($permisoMock);
        return $modelo;
    }

    private function guardar(array $permisos, array $respuestas = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock([], $respuestas));
        return (new ModeloRoles())->procesarDatos([
            'accion'   => 'guardar_permisos',
            'id'       => '5',
            'permisos' => $permisos,
        ]);
    }

    public function testCargarPermisos(): void
    {
        $filas = [
            ['id_modulo' => 6, 'id_permiso' => 1, 'clave' => 'ingresar_atletas', 'asignado' => 1],
            ['id_modulo' => 6, 'id_permiso' => 2, 'clave' => 'registrar_atleta', 'asignado' => 0],
        ];
        $modelo = $this->modeloConPermisos(['accion' => 'CargarPermisos', 'datos' => $filas]);

        $resultado = $modelo->CargarPermisos(5);

        $this->assertSame('CargarPermisos', $resultado['accion']);
        $this->assertSame($filas, $resultado['datos']);
    }

    public function testCargarPermisosExcepcion(): void
    {
        $modelo = $this->modeloConPermisos(new Exception('Sin conexión'));

        $resultado = $modelo->CargarPermisos(5);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Sin conexión', $resultado['mensaje']);
    }

    public function testCargarPermisosRespuestaError(): void
    {
        $modelo = $this->modeloConPermisos(['accion' => 'error', 'mensaje' => 'Error de consulta']);

        $resultado = $modelo->CargarPermisos(5);

        $this->assertSame(
            'error',
            $resultado['accion'],
            'CargarPermisos() no informa el error devuelto por ModeloPermisos::permisosRoles().'
        );
    }

    public function testGuardarPermisos(): void
    {
        $resultado = $this->guardar(['1' => 1, '4' => '1', '7' => 0]);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame([':id' => 5], $this->sentenciaEjecutada('DELETE FROM `permisos_rol`')['params']);

        $inserts = $this->sentenciasEjecutadas('INSERT INTO `permisos_rol`');
        $this->assertCount(2, $inserts);
        $this->assertSame([':id_rol' => 5, ':id_permiso' => 1], $inserts[0]['params']);
        $this->assertSame([':id_rol' => 5, ':id_permiso' => 4], $inserts[1]['params']);
    }

    public function testGuardarSinPermisos(): void
    {
        $resultado = $this->guardar([]);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertNotNull($this->sentenciaEjecutada('DELETE FROM `permisos_rol`'));
        $this->assertCount(0, $this->sentenciasEjecutadas('INSERT INTO `permisos_rol`'));
    }

    public function testErrorBaseDatosAlGuardarPermisos(): void
    {
        $resultado = $this->guardar([1 => 1], [
            'INSERT INTO `permisos_rol`' => ['error' => new Exception('Error al guardar permisos')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al guardar permisos', $resultado['codigo']);
    }
}
