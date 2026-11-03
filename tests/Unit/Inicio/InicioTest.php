<?php

namespace tests\Unit\Inicio;

use App\modelo\Conexion;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloInicio;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use Exception;

#[Group("unit")]
#[Group("unitInicio")]
class InicioTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedadSG = $reflector->getProperty('instanciaSG');
        $propiedadSG->setValue(null, null);

        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);

        parent::tearDown();
    }

    private function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedadSG = $reflector->getProperty('instanciaSG');
        $propiedadSG->setValue(null, $pdoMock);

        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);
    }

    private function obtenerFilaUsuario(array $sobrescribir = []): array
    {
        $clavePrueba = 'MiClave123$';
        $hash = password_hash($clavePrueba, PASSWORD_DEFAULT);

        $base = [
            'idUsuario'         => 1,
            'nombreUsuario'     => 'Juan',
            'apellidoUsuario'   => 'Perez',
            'foto'              => 'default.png',
            'pass_hash'         => $hash,
            'bloqueo'           => 1, // 1 = permitido
            'intentos_fallidos' => 0,
            'nombre_rol'        => 'Administrador',
            'id_rol'            => 1,
            'nivel_rol'         => 1,
            'correo'            => 'admin@test.com',
            'telefonoUsuario'   => '04140000000',
            'cedulaUsuario'     => '12345678'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Cédula y clave vacías
    public function testDatosVacios(): void
    {
        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '', 'clave' => '']);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertEquals('La cédula y la contraseña son obligatorias', $resultado['mensaje']);
    }

    // Caso 2: Cédula vacía
    public function testCedulaVacia(): void
    {
        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '', 'clave' => 'MiClave123$']);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertEquals('La cédula y la contraseña son obligatorias', $resultado['mensaje']);
    }

    // Caso 3: Clave vacía
    public function testClaveVacia(): void
    {
        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '12345678', 'clave' => '']);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertEquals('La cédula y la contraseña son obligatorias', $resultado['mensaje']);
    }

    // Caso 4: Usuario no existe en base de datos
    public function testUsuarioNoExiste(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '99999999', 'clave' => 'Cualquiera123$']);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertEquals('Las credenciales proporcionadas no son correctas.', $resultado['mensaje']);
    }

    // Caso 5: Usuario con acceso bloqueado
    public function testUsuarioBloqueado(): void
    {
        $filaUsuario = $this->obtenerFilaUsuario(['bloqueo' => 2]);

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn($filaUsuario);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '12345678', 'clave' => 'MiClave123$']);

        $this->assertEquals('denegado', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertStringContainsString('bloqueado el acceso', $resultado['mensaje']);
    }

    // Caso 6: Contraseña incorrecta (primer intento)
    public function testClaveIncorrecta(): void
    {
        $filaUsuario = $this->obtenerFilaUsuario(['intentos_fallidos' => 0, 'id_rol' => 2]);

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn($filaUsuario);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '12345678', 'clave' => 'ClaveErronea123$']);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertEquals('Las credenciales proporcionadas no son correctas.', $resultado['mensaje']);
    }

    // Caso 7: Bloqueo al superar tres intentos fallidos (usuario regular)
    public function testBloqueoTresIntentos(): void
    {
        $filaUsuario = $this->obtenerFilaUsuario(['intentos_fallidos' => 2, 'id_rol' => 2]);

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn($filaUsuario);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '12345678', 'clave' => 'ClaveErronea123$']);

        $this->assertEquals('bloqueado', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertStringContainsString('superado el límite de intentos', $resultado['mensaje']);
        $this->assertEquals(1, $resultado['idUsuario']);
    }

    // Caso 8: Super Usuario (id_rol = 1) no se bloquea tras tres intentos fallidos
    public function testSuperUsuarioNoSeBloquea(): void
    {
        $filaUsuario = $this->obtenerFilaUsuario(['intentos_fallidos' => 2, 'id_rol' => 1]);

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn($filaUsuario);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '12345678', 'clave' => 'ClaveErronea123$']);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertEquals('Las credenciales proporcionadas no son correctas.', $resultado['mensaje']);
    }

    // Caso 9: Reseteo de intentos fallidos al iniciar sesión con éxito
    public function testReseteoIntentos(): void
    {
        $filaUsuario = $this->obtenerFilaUsuario(['intentos_fallidos' => 2, 'nivel_rol' => 1]);
        $permisos = [
            ['id_modulo' => 1, 'clave' => 'mod_usuarios', 'nombre_permiso' => 'Usuarios', 'asignado' => 1]
        ];

        $stmtUsuario = $this->createMock(PDOStatement::class);
        $stmtUsuario->method('bindParam')->willReturn(true);
        $stmtUsuario->method('execute')->willReturn(true);
        $stmtUsuario->method('fetch')->willReturn($filaUsuario);

        $stmtPermisos = $this->createMock(PDOStatement::class);
        $stmtPermisos->method('bindParam')->willReturn(true);
        $stmtPermisos->method('execute')->willReturn(true);
        $stmtPermisos->method('fetchAll')->willReturn($permisos);

        $stmtGenerico = $this->createMock(PDOStatement::class);
        $stmtGenerico->method('bindParam')->willReturn(true);
        $stmtGenerico->method('execute')->willReturn(true);
        $stmtGenerico->method('fetch')->willReturn(false);
        $stmtGenerico->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtUsuario, $stmtPermisos, $stmtGenerico) {
            if (str_contains($sql, 'FROM `usuarios`') || str_contains($sql, 'FROM usuarios')) {
                return $stmtUsuario;
            }
            if (str_contains($sql, 'FROM modulos')) {
                return $stmtPermisos;
            }
            return $stmtGenerico;
        });
        $pdoMock->method('query')->willReturn($stmtGenerico);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '12345678', 'clave' => 'MiClave123$']);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(1, $resultado['resultado']);
        $this->assertEquals('BIENVENIDO', $resultado['mensaje']);
    }

    // Caso 10: Inicio de sesión exitoso como Super Usuario (nivel_rol = 1)
    public function testInicioSuperUsuario(): void
    {
        $filaUsuario = $this->obtenerFilaUsuario(['nivel_rol' => 1]);
        $permisos = [
            ['id_modulo' => 1, 'clave' => 'mod_usuarios', 'nombre_permiso' => 'Usuarios', 'asignado' => 1],
            ['id_modulo' => 2, 'clave' => 'mod_atletas', 'nombre_permiso' => 'Atletas', 'asignado' => 1]
        ];

        $stmtUsuario = $this->createMock(PDOStatement::class);
        $stmtUsuario->method('bindParam')->willReturn(true);
        $stmtUsuario->method('execute')->willReturn(true);
        $stmtUsuario->method('fetch')->willReturn($filaUsuario);

        $stmtPermisos = $this->createMock(PDOStatement::class);
        $stmtPermisos->method('bindParam')->willReturn(true);
        $stmtPermisos->method('execute')->willReturn(true);
        $stmtPermisos->method('fetchAll')->willReturn($permisos);

        $stmtGenerico = $this->createMock(PDOStatement::class);
        $stmtGenerico->method('bindParam')->willReturn(true);
        $stmtGenerico->method('execute')->willReturn(true);
        $stmtGenerico->method('fetch')->willReturn(false);
        $stmtGenerico->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtUsuario, $stmtPermisos, $stmtGenerico) {
            if (str_contains($sql, 'FROM `usuarios`') || str_contains($sql, 'FROM usuarios')) {
                return $stmtUsuario;
            }
            if (str_contains($sql, 'FROM modulos')) {
                return $stmtPermisos;
            }
            return $stmtGenerico;
        });
        $pdoMock->method('query')->willReturn($stmtGenerico);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '12345678', 'clave' => 'MiClave123$']);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(1, $resultado['resultado']);
        $this->assertEquals('BIENVENIDO', $resultado['mensaje']);
        $this->assertEquals('Principal', $resultado['url']);
        $this->assertCount(2, $resultado['permisos']);
    }

    // Caso 11: Inicio de sesión exitoso como rol nivel 2
    public function testInicioNivelDos(): void
    {
        $filaUsuario = $this->obtenerFilaUsuario(['nivel_rol' => 2, 'id_rol' => 2]);
        $permisos = [
            ['id_modulo' => 2, 'clave' => 'mod_atletas', 'nombre_permiso' => 'Atletas', 'asignado' => 1]
        ];

        $stmtUsuario = $this->createMock(PDOStatement::class);
        $stmtUsuario->method('bindParam')->willReturn(true);
        $stmtUsuario->method('execute')->willReturn(true);
        $stmtUsuario->method('fetch')->willReturn($filaUsuario);

        $stmtPermisos = $this->createMock(PDOStatement::class);
        $stmtPermisos->method('bindParam')->willReturn(true);
        $stmtPermisos->method('execute')->willReturn(true);
        $stmtPermisos->method('fetchAll')->willReturn($permisos);

        $stmtGenerico = $this->createMock(PDOStatement::class);
        $stmtGenerico->method('bindParam')->willReturn(true);
        $stmtGenerico->method('execute')->willReturn(true);
        $stmtGenerico->method('fetch')->willReturn(false);
        $stmtGenerico->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtUsuario, $stmtPermisos, $stmtGenerico) {
            if (str_contains($sql, 'FROM `usuarios`') || str_contains($sql, 'FROM usuarios')) {
                return $stmtUsuario;
            }
            if (str_contains($sql, 'FROM modulos')) {
                return $stmtPermisos;
            }
            return $stmtGenerico;
        });
        $pdoMock->method('query')->willReturn($stmtGenerico);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '12345678', 'clave' => 'MiClave123$']);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(1, $resultado['resultado']);
        $this->assertEquals('BIENVENIDO', $resultado['mensaje']);
    }

    // Caso 12: Inicio de sesión exitoso como Usuario Estándar (nivel_rol > 2)
    public function testInicioUsuarioEstandar(): void
    {
        $filaUsuario = $this->obtenerFilaUsuario(['nivel_rol' => 3, 'id_rol' => 3]);
        $permisos = [
            ['id_modulo' => 2, 'clave' => 'mod_atletas', 'nombre_permiso' => 'Atletas', 'asignado' => 1],
            ['id_modulo' => 3, 'clave' => 'mod_equipos', 'nombre_permiso' => 'Equipos', 'asignado' => 0]
        ];

        $stmtUsuario = $this->createMock(PDOStatement::class);
        $stmtUsuario->method('bindParam')->willReturn(true);
        $stmtUsuario->method('execute')->willReturn(true);
        $stmtUsuario->method('fetch')->willReturn($filaUsuario);

        $stmtPermisos = $this->createMock(PDOStatement::class);
        $stmtPermisos->method('bindParam')->willReturn(true);
        $stmtPermisos->method('execute')->willReturn(true);
        $stmtPermisos->method('fetchAll')->willReturn($permisos);

        $stmtGenerico = $this->createMock(PDOStatement::class);
        $stmtGenerico->method('bindParam')->willReturn(true);
        $stmtGenerico->method('execute')->willReturn(true);
        $stmtGenerico->method('fetch')->willReturn(false);
        $stmtGenerico->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtUsuario, $stmtPermisos, $stmtGenerico) {
            if (str_contains($sql, 'FROM `usuarios`') || str_contains($sql, 'FROM usuarios')) {
                return $stmtUsuario;
            }
            if (str_contains($sql, 'FROM modulos')) {
                return $stmtPermisos;
            }
            return $stmtGenerico;
        });
        $pdoMock->method('query')->willReturn($stmtGenerico);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '12345678', 'clave' => 'MiClave123$']);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(1, $resultado['resultado']);
        $this->assertCount(1, $resultado['permisos']);
        $this->assertEquals('mod_atletas', $resultado['permisos'][0]['clave']);
    }

    // Caso 13: Error de base de datos captura excepción
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de conexión'));
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloInicio();
        $resultado = $modelo->ProcesarDatos(['cedula' => '12345678', 'clave' => 'MiClave123$']);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de conexión', $resultado['mensaje']);
    }
}
