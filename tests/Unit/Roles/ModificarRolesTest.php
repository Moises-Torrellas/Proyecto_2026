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
#[Group('modificarRoles')]
class ModificarRolesTest extends TestCase
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

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'accion'      => 'modificar',
            'id'          => '5',
            'nombre'      => 'preparador fisico',
            'descripcion' => 'planifica la preparación física',
        ], $sobrescribir);
    }

    private function ejecutar(array $conteos, array $respuestas = [], array $datos = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloRoles())->procesarDatos($this->obtenerDatosValidos($datos));
    }

    public function testModificacionExitosa(): void
    {
        $resultado = $this->ejecutar(['roles.id_rol.1' => 1]);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $update = $this->sentenciaEjecutada('UPDATE `roles`');
        $this->assertStringContainsString('`descripcion` = :descripcion', $update['sql']);
        $this->assertSame([
            ':id'          => 5,
            ':nombre'      => 'Preparador Fisico',
            ':descripcion' => 'Planifica La Preparación Física',
        ], $update['params']);
    }

    public function testModificacionSinDescripcion(): void
    {
        $resultado = $this->ejecutar(['roles.id_rol.1' => 1], [], ['descripcion' => '']);

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `roles`');
        $this->assertStringNotContainsString('descripcion', $update['sql']);
        $this->assertSame([':id' => 5, ':nombre' => 'Preparador Fisico'], $update['params']);
    }

    public function testConservaSuPropioNombre(): void
    {
        $resultado = $this->ejecutar([
            'roles.id_rol.1'      => 1,
            'propia.nombre_rol'   => 1,
            'roles.nombre_rol.1'  => 1,
        ]);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertNotNull($this->sentenciaEjecutada('UPDATE `roles`'));
    }

    public function testRolNoExiste(): void
    {
        $resultado = $this->ejecutar(['roles.id_rol.1' => 0]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `roles`'));
    }

    public function testNombreDuplicado(): void
    {
        $resultado = $this->ejecutar([
            'roles.id_rol.1'     => 1,
            'propia.nombre_rol'  => 0,
            'roles.nombre_rol.1' => 1,
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `roles`'));
    }

    public function testErrorBaseDatosAlModificar(): void
    {
        $resultado = $this->ejecutar(['roles.id_rol.1' => 1], [
            'UPDATE `roles`' => ['error' => new Exception('Error al actualizar rol')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al actualizar rol', $resultado['codigo']);
    }
}
