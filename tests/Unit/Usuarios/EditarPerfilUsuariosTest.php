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
 * RF-USU-09: El usuario debe poder editar su propio perfil en tres secciones:
 *  - personal: cédula, nombre, apellido y foto (la cédula debe ser única).
 *  - contacto: teléfono y correo (deben ser únicos).
 *  - seguridad: contraseña (obligatoria, se guarda cifrada y nunca en la sesión).
 * Los datos de la sesión se actualizan en tiempo real.
 *
 * Escenarios:
 *  E1. Edición de datos personales.
 *  E2. Edición de datos de contacto.
 *  E3. Cambio de contraseña.
 *  E4. Datos duplicados con otro usuario.
 *  E5. Petición incompleta o tipo de edición inválido.
 */
#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('editarPerfilUsuarios')]
class EditarPerfilUsuariosTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            // Sesión sin cookies ni cabeceras para poder iniciarla en CLI
            ini_set('session.use_cookies', '0');
            ini_set('session.cache_limiter', '');
            session_start();
        }
        $_SESSION = [];
    }

    private function editar(string $tipo, array $datos, array $conteos = [], array $respuestas = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloUsuarios())->procesarDatos(array_merge([
            'accion'       => 'editar_perfil',
            'tipo_edicion' => $tipo,
            'id'           => 5,
        ], $datos));
    }

    // Caso 1 (E1): Edición de datos personales actualiza la BD y la sesión
    public function testEditarDatosPersonales(): void
    {
        $resultado = $this->editar(
            'personal',
            ['cedula' => '27123456', 'nombre' => 'carlos', 'apellido' => 'mendoza'],
            ['propia.cedulaUsuario' => 1]
        );

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame('Perfil actualizado correctamente', $resultado['mensaje']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertStringNotContainsString('`foto`', $update['sql']);
        $this->assertSame('Carlos', $update['params'][':nombre']);

        $this->assertSame('27123456', $_SESSION['cedula']);
        $this->assertSame('Carlos', $_SESSION['nombre']);
        $this->assertSame('Mendoza', $_SESSION['apellido']);
        $this->assertArrayNotHasKey('foto', $_SESSION);
    }

    // Caso 2 (E1): Edición de datos personales con nueva foto
    public function testEditarDatosPersonalesConFoto(): void
    {
        $resultado = $this->editar(
            'personal',
            ['cedula' => '27123456', 'nombre' => 'Carlos', 'apellido' => 'Mendoza', 'foto' => 'user_27123456.png'],
            ['propia.cedulaUsuario' => 1]
        );

        $this->assertSame('exito', $resultado['accion']);
        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertStringContainsString('`foto` = :foto', $update['sql']);
        $this->assertSame('user_27123456.png', $_SESSION['foto']);
    }

    // Caso 3 (E4): Cédula usada por otro usuario activo
    public function testEditarPersonalCedulaDuplicada(): void
    {
        $resultado = $this->editar(
            'personal',
            ['cedula' => '11111111', 'nombre' => 'Carlos', 'apellido' => 'Mendoza'],
            ['propia.cedulaUsuario' => 0, 'usuarios.cedulaUsuario.1' => 1]
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_CEDULA, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
        $this->assertArrayNotHasKey('cedula', $_SESSION);
    }

    // Caso 4 (E2): Edición de datos de contacto actualiza la BD y la sesión
    public function testEditarDatosContacto(): void
    {
        $resultado = $this->editar(
            'contacto',
            ['telefono' => '04149876543', 'correo' => 'Nuevo@Correo.com'],
            ['propia.correo' => 0, 'propia.telefonoUsuario' => 0]
        );

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertSame('04149876543', $update['params'][':telefono']);
        $this->assertSame('nuevo@correo.com', $update['params'][':correo']);
        $this->assertSame('04149876543', $_SESSION['telefono']);
        $this->assertSame('nuevo@correo.com', $_SESSION['correo']);
    }

    // Caso 5 (E4): Correo usado por otro usuario activo
    public function testEditarContactoCorreoDuplicado(): void
    {
        $resultado = $this->editar(
            'contacto',
            ['telefono' => '04149876543', 'correo' => 'otro@correo.com'],
            ['usuarios.correo.1' => 1]
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL, $resultado['codigo']);
    }

    // Caso 6 (E4): Teléfono usado por otro usuario activo
    public function testEditarContactoTelefonoDuplicado(): void
    {
        $resultado = $this->editar(
            'contacto',
            ['telefono' => '04140000000', 'correo' => 'carlos@correo.com'],
            ['propia.correo' => 1, 'usuarios.telefonoUsuario.1' => 1]
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE, $resultado['codigo']);
    }

    // Caso 7 (E3): Cambio de contraseña la guarda cifrada y no la expone en la sesión
    public function testCambiarContraseña(): void
    {
        $resultado = $this->editar('seguridad', ['contraseña' => 'Nueva.Clave456']);

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios` SET `pass_hash`');
        $this->assertTrue(password_verify('Nueva.Clave456', $update['params'][':contra']));
        $this->assertArrayNotHasKey('contraseña', $_SESSION);
    }

    // Caso 8 (E3): Cambio de contraseña sin enviar la nueva contraseña
    public function testCambiarContraseñaVacia(): void
    {
        $resultado = $this->editar('seguridad', ['contraseña' => '']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('No se proporcionó una nueva contraseña válida.', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    // Caso 9 (E5): Tipo de edición no válido
    public function testTipoEdicionInvalido(): void
    {
        $resultado = $this->editar('otro', ['nombre' => 'Carlos']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('El tipo de edición especificado no es válido.', $resultado['codigo']);
    }

    // Caso 10 (E5): Edición de perfil sin id de usuario
    public function testEditarSinId(): void
    {
        $resultado = $this->editar('personal', ['id' => null, 'nombre' => 'Carlos']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('ID de usuario no proporcionado.', $resultado['codigo']);
    }

    // Caso 11 (E1): Error de base de datos al actualizar el perfil
    public function testErrorBaseDatosAlEditar(): void
    {
        $resultado = $this->editar(
            'contacto',
            ['telefono' => '04149876543', 'correo' => 'carlos@correo.com'],
            ['propia.correo' => 1, 'propia.telefonoUsuario' => 1],
            ['UPDATE `usuarios`' => ['error' => new Exception('Error al actualizar perfil')]]
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al actualizar perfil', $resultado['codigo']);
        $this->assertArrayNotHasKey('telefono', $_SESSION);
    }
}
