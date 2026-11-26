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
 * RF-USU-03: El sistema debe permitir registrar usuarios con cédula, nombre, apellido,
 * teléfono, correo, contraseña, rol y foto. La cédula, el correo y el teléfono deben ser
 * únicos entre los usuarios activos, el rol debe existir y la contraseña es obligatoria.
 * Si la cédula pertenece a un usuario eliminado, el usuario se reactiva con los nuevos datos.
 *
 * Escenarios:
 *  E1. Registro de un usuario nuevo con datos válidos.
 *  E2. Registro de una cédula que pertenecía a un usuario eliminado (reactivación).
 *  E3. Registro sin contraseña.
 *  E4. Registro con un rol inexistente.
 *  E5. Registro con cédula, correo o teléfono ya usados por un usuario activo.
 *  E6. Falla de la base de datos (incluido el procedimiento que informa @resultado = 0).
 *  E7. Registro con correo o teléfono de un usuario eliminado.
 */
#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('registrarUsuarios')]
class RegistrarUsuariosTest extends TestCase
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

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $base = [
            'accion'     => 'incluir',
            'cedula'     => '27123456',
            'nombre'     => 'carlos',
            'apellido'   => 'mendoza',
            'telefono'   => '0424-1234567',
            'correo'     => 'Carlos.Mendoza@Correo.com',
            'contraseña' => 'Clave.Segura123',
            'roles_id'   => 3,
            'foto'       => 'user_27123456.png',
        ];

        return array_merge($base, $sobrescribir);
    }

    private function conteosValidos(array $sobrescribir = []): array
    {
        return array_merge(['roles.id_rol.null' => 1], $sobrescribir);
    }

    /** Respuesta de pa_incluir_usuario cuando inserta correctamente (@resultado = 1). */
    private function procedimientoExitoso(): array
    {
        return ['SELECT @resultado' => ['fetchColumn' => 1]];
    }

    // Caso 1 (E1): Registro exitoso de un usuario nuevo mediante el procedimiento almacenado
    public function testRegistroExitoso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(), $this->procedimientoExitoso()));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame(['accion' => 'exito'], $resultado);

        $llamada = $this->sentenciaEjecutada('CALL pa_incluir_usuario');
        $this->assertNotNull($llamada);
        $this->assertSame('27123456', $llamada['params'][':cedula']);
        $this->assertSame('Carlos', $llamada['params'][':nombre']);
        $this->assertSame('Mendoza', $llamada['params'][':apellido']);
        $this->assertSame('carlos.mendoza@correo.com', $llamada['params'][':correo']);
        $this->assertSame(3, $llamada['params'][':rol']);
        $this->assertTrue(password_verify('Clave.Segura123', $llamada['params'][':contra']));
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    // Caso 2 (E1): Se registra en la BD el usuario de sesión que realiza la operación (auditoría)
    public function testRegistraUsuarioDeSesionParaAuditoria(): void
    {
        $_SESSION['idUsuario'] = 4;
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(), $this->procedimientoExitoso()));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('exito', $resultado['accion']);
        $this->assertNotNull($this->sentenciaEjecutada('SET @usuario_actual = 4'));
    }

    // Caso 3 (E2): Reactivación de un usuario eliminado con la misma cédula
    public function testReactivacionUsuarioEliminado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock(
            $this->conteosValidos(['usuarios.cedulaUsuario.0' => 1]),
            ['SELECT idUsuario FROM usuarios' => ['fetchColumn' => 9]]
        ));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertNotNull($update);
        $this->assertStringContainsString('`estatus` = 1', $update['sql']);
        $this->assertSame('27123456', $update['params'][':cedula']);
        $this->assertNull($this->sentenciaEjecutada('CALL pa_incluir_usuario'));
    }

    // Caso 4 (E3): Registro sin contraseña es rechazado
    public function testRegistroSinContraseña(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos()));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos(['contraseña' => '']));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(VALIDATION, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('CALL pa_incluir_usuario'));
    }

    // Caso 5 (E4): Registro con un rol inexistente
    public function testRolNoExiste(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock(['roles.id_rol.null' => 0]));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos(['roles_id' => 99]));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
    }

    // Caso 6 (E5): Cédula duplicada en un usuario activo
    public function testCedulaDuplicada(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(['usuarios.cedulaUsuario.1' => 1])));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_CEDULA, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('CALL pa_incluir_usuario'));
    }

    // Caso 7 (E5): Correo duplicado en un usuario activo
    public function testCorreoDuplicado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(['usuarios.correo.1' => 1])));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL, $resultado['codigo']);
    }

    // Caso 8 (E5): Teléfono duplicado en un usuario activo
    public function testTelefonoDuplicado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(['usuarios.telefonoUsuario.1' => 1])));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE, $resultado['codigo']);
    }

    // Caso 9 (E6): Error de base de datos al ejecutar el registro
    public function testErrorBaseDatosAlRegistrar(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(), [
            'CALL pa_incluir_usuario' => ['error' => new Exception('Error en el procedimiento')],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error en el procedimiento', $resultado['codigo']);
    }

    // Caso 10 (E6): El procedimiento atrapa un error SQL y devuelve @resultado = 0
    public function testProcedimientoSinInsertarReportaError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(), [
            'SELECT @resultado' => ['fetchColumn' => 0],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertNotNull($this->sentenciaEjecutada('CALL pa_incluir_usuario'));
    }

    // Caso 11 (E7): Correo de un usuario eliminado
    public function testCorreoDeUsuarioEliminado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(['usuarios.correo.0' => 1])));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL . '0', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('CALL pa_incluir_usuario'));
    }

    // Caso 12 (E7): Teléfono de un usuario eliminado
    public function testTelefonoDeUsuarioEliminado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(['usuarios.telefonoUsuario.0' => 1])));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE . '0', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('CALL pa_incluir_usuario'));
    }
}
