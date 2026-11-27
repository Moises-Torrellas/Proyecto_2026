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
#[Group('modificarUsuarios')]
class ModificarUsuariosTest extends TestCase
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

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $base = [
            'accion'   => 'modificar',
            'id'       => 5,
            'cedula'   => '27123456',
            'nombre'   => 'carlos',
            'apellido' => 'mendoza',
            'telefono' => '0424-1234567',
            'correo'   => 'carlos@correo.com',
            'roles_id' => 3,
            'foto'     => 'user_27123456.png',
        ];

        return array_merge($base, $sobrescribir);
    }

    private function conteosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'roles.id_rol.null'       => 1,
            'propia.cedulaUsuario'    => 1,
            'propia.correo'           => 1,
            'propia.telefonoUsuario'  => 1,
        ], $sobrescribir);
    }

    private function respuestasRolActual(int $idRol = 3, int $nivel = 3, array $extra = []): array
    {
        return array_merge($extra, [
            'SELECT u.id_rol, r.nivel_rol' => ['fetch' => ['id_rol' => $idRol, 'nivel_rol' => $nivel]],
        ]);
    }

    private function ejecutar(array $conteos, array $respuestas, array $datos = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos($datos));
    }

    public function testModificacionExitosa(): void
    {
        $resultado = $this->ejecutar($this->conteosValidos(), $this->respuestasRolActual());

        $this->assertSame(['accion' => 'exito'], $resultado);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertNotNull($update);
        $this->assertStringNotContainsString('pass_hash', $update['sql']);
        $this->assertSame(5, $update['params'][':id']);
        $this->assertSame('Carlos', $update['params'][':nombre']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM excepciones'));
    }

    public function testModificacionConContraseña(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(),
            $this->respuestasRolActual(),
            ['contraseña' => 'Nueva.Clave456']
        );

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertStringContainsString('`pass_hash` = :contra', $update['sql']);
        $this->assertTrue(password_verify('Nueva.Clave456', $update['params'][':contra']));
    }

    public function testCambioRolEliminaExcepciones(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(),
            $this->respuestasRolActual(3, 3),
            ['roles_id' => 4]
        );

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame(4, $this->sentenciaEjecutada('UPDATE `usuarios`')['params'][':rol']);

        $delete = $this->sentenciaEjecutada('DELETE FROM excepciones');
        $this->assertNotNull($delete);
        $this->assertSame(5, $delete['params'][':idUsuario']);
    }

    public function testNivel1NoCambiaRolNiContraseña(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(),
            $this->respuestasRolActual(1, 1),
            ['roles_id' => 4, 'contraseña' => 'Nueva.Clave456']
        );

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertSame(1, $update['params'][':rol']);
        $this->assertStringNotContainsString('pass_hash', $update['sql']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM excepciones'));
    }

    public function testNivel2NoCambiaRolNiContraseña(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(),
            $this->respuestasRolActual(2, 2),
            ['roles_id' => 4, 'contraseña' => 'Nueva.Clave456']
        );

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertSame(2, $update['params'][':rol']);
        $this->assertStringNotContainsString('pass_hash', $update['sql']);
    }

    public function testCedulaDeUsuarioEliminado(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['usuarios.cedulaUsuario.0' => 1]),
            $this->respuestasRolActual()
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_CEDULA . '0', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    public function testCorreoDeUsuarioEliminado(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['usuarios.correo.0' => 1]),
            $this->respuestasRolActual()
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL . '0', $resultado['codigo']);
    }

    public function testTelefonoDeUsuarioEliminado(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['usuarios.telefonoUsuario.0' => 1]),
            $this->respuestasRolActual()
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE . '0', $resultado['codigo']);
    }

    public function testCedulaDuplicada(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['propia.cedulaUsuario' => 0, 'usuarios.cedulaUsuario.1' => 1]),
            $this->respuestasRolActual()
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_CEDULA, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    public function testCorreoDuplicado(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['propia.correo' => 0, 'usuarios.correo.1' => 1]),
            $this->respuestasRolActual()
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL, $resultado['codigo']);
    }

    public function testTelefonoDuplicado(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['propia.telefonoUsuario' => 0, 'usuarios.telefonoUsuario.1' => 1]),
            $this->respuestasRolActual()
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE, $resultado['codigo']);
    }

    public function testCambioCedulaLibre(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['propia.cedulaUsuario' => 0]),
            $this->respuestasRolActual(),
            ['cedula' => '30111222']
        );

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame('30111222', $this->sentenciaEjecutada('UPDATE `usuarios`')['params'][':cedula']);
    }

    public function testRolNoExiste(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['roles.id_rol.null' => 0]),
            $this->respuestasRolActual(),
            ['roles_id' => 99]
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
    }

    public function testErrorBaseDatosAlModificar(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(),
            $this->respuestasRolActual(3, 3, ['UPDATE `usuarios`' => ['error' => new Exception('Error al actualizar')]])
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al actualizar', $resultado['codigo']);
    }

    public function testUsuarioNoExiste(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(),
            ['SELECT u.id_rol, r.nivel_rol' => ['fetch' => false]],
            ['id' => 999]
        );

        $this->assertSame(['accion' => 'error', 'codigo' => 'USER_NOT_FOUND'], $resultado);
        $this->assertStringContainsString('u.estatus = 1', $this->sentenciaEjecutada('SELECT u.id_rol, r.nivel_rol')['sql']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }
}
