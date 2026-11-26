<?php

declare(strict_types=1);

namespace tests\Integration\Inicio;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloInicio;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("inicio")]
class InicioIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloInicio $modelo;
    private string $passwordOriginal = 'ClaveValida123$';

    protected function setUp(): void
    {
        parent::setUp();

        $host = defined('_DB_HOST_SG_') && _DB_HOST_SG_ ? _DB_HOST_SG_ : '127.0.0.1';
        $dbname = defined('_DB_NAME_SG_') && _DB_NAME_SG_ ? _DB_NAME_SG_ : 'bds2';
        $user = defined('_DB_USER_SG_') && _DB_USER_SG_ ? _DB_USER_SG_ : 'root';
        $pass = defined('_DB_PASS_SG_') ? _DB_PASS_SG_ : '';

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $this->pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        $reflector = new ReflectionClass(Conexion::class);
        $propSG = $reflector->getProperty('instanciaSG');
        $propSG->setValue(null, $this->pdo);

        $prop = $reflector->getProperty('instancia');
        $prop->setValue(null, $this->pdo);

        $this->modelo = new ModeloInicio();

        $this->limpiarDatosPrueba();
        $this->cargarDatosPrueba();
    }

    protected function tearDown(): void
    {
        $this->limpiarDatosPrueba();
        $this->pdo = null;

        $reflector = new ReflectionClass(Conexion::class);
        $propSG = $reflector->getProperty('instanciaSG');
        $propSG->setValue(null, null);

        $prop = $reflector->getProperty('instancia');
        $prop->setValue(null, null);

        $_SESSION = [];
        parent::tearDown();
    }

    private function limpiarDatosPrueba(): void
    {
        if (!$this->pdo) {
            return;
        }
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("DELETE FROM bitacora WHERE idUsuario IN (SELECT idUsuario FROM usuarios WHERE cedulaUsuario LIKE '9999%');");
        $this->pdo->exec("DELETE FROM usuarios WHERE cedulaUsuario LIKE '9999%';");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function cargarDatosPrueba(): void
    {
        $hash = password_hash($this->passwordOriginal, PASSWORD_BCRYPT);

        // 1. Superusuario activo (id_rol = 1)
        $stmt = $this->pdo->prepare("INSERT INTO usuarios 
            (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos) 
            VALUES (:cedula, 'Super', 'Admin', 'default.png', '0414-1111111', :hash, 'super_integ@test.com', 1, 1, 1, 0)");
        $stmt->execute([':cedula' => '99990001', ':hash' => $hash]);

        // 2. Usuario regular activo (Entrenador, id_rol = 6)
        $stmt = $this->pdo->prepare("INSERT INTO usuarios 
            (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos) 
            VALUES (:cedula, 'Carlos', 'Entrenador', 'default.png', '0414-2222222', :hash, 'carlos_integ@test.com', 6, 1, 1, 0)");
        $stmt->execute([':cedula' => '99990002', ':hash' => $hash]);

        // 3. Usuario bloqueado (bloqueo = 2)
        $stmt = $this->pdo->prepare("INSERT INTO usuarios 
            (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos) 
            VALUES (:cedula, 'Bloqueado', 'Usuario', 'default.png', '0414-3333333', :hash, 'bloqueado_integ@test.com', 6, 1, 2, 0)");
        $stmt->execute([':cedula' => '99990003', ':hash' => $hash]);

        // 4. Usuario al borde del bloqueo (5 intentos fallidos previos)
        $stmt = $this->pdo->prepare("INSERT INTO usuarios 
            (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos) 
            VALUES (:cedula, 'Limite', 'Intentos', 'default.png', '0414-4444444', :hash, 'limite_integ@test.com', 6, 1, 1, 5)");
        $stmt->execute([':cedula' => '99990004', ':hash' => $hash]);
    }

    /**
     * Prueba que un Superusuario inicia sesión correctamente consultando permisos en BD real.
     */
    public function testInicioSesionExitosoSuperUsuario(): void
    {
        $datos = [
            'cedula' => '99990001',
            'clave'  => $this->passwordOriginal
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(1, $resultado['resultado']);
        $this->assertEquals('99990001', $resultado['datos']['cedulaUsuario']);
        $this->assertNotEmpty($resultado['permisos'], 'El superusuario debe recibir lista de permisos de la BD.');
    }

    /**
     * Prueba que un usuario regular inicia sesión exitosamente.
     */
    public function testInicioSesionExitosoUsuarioRegular(): void
    {
        $datos = [
            'cedula' => '99990002',
            'clave'  => $this->passwordOriginal
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(1, $resultado['resultado']);
        $this->assertEquals('99990002', $resultado['datos']['cedulaUsuario']);
    }

    /**
     * Prueba que una contraseña incorrecta incrementa los intentos fallidos directamente en la tabla usuarios de la BD.
     */
    public function testContraseñaIncorrectaIncrementaIntentosEnBD(): void
    {
        $datos = [
            'cedula' => '99990002',
            'clave'  => 'ContrasenaIncorrecta123$'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertStringContainsString('no son correctas', $resultado['mensaje']);

        // Verificar persistencia real en MySQL
        $stmt = $this->pdo->prepare("SELECT intentos_fallidos FROM usuarios WHERE cedulaUsuario = '99990002'");
        $stmt->execute();
        $intentos = (int)$stmt->fetchColumn();

        $this->assertEquals(1, $intentos, 'Los intentos fallidos deben incrementarse en la base de datos.');
    }

    /**
     * Prueba que al acumular 6 o más intentos fallidos en BD el usuario pasa automáticamente a bloqueo = 2.
     */
    public function testBloqueoUsuarioTrasSuperarLimiteIntentos(): void
    {
        $datos = [
            'cedula' => '99990004', // Tiene 5 intentos previos en la BD
            'clave'  => 'ClaveErroneaParaBloquear$'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('bloqueado', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);

        // Verificar que en la BD el estatus de bloqueo fue actualizado a 2
        $stmt = $this->pdo->prepare("SELECT bloqueo, intentos_fallidos FROM usuarios WHERE cedulaUsuario = '99990004'");
        $stmt->execute();
        $fila = $stmt->fetch();

        $this->assertEquals(2, (int)$fila['bloqueo'], 'El usuario debe estar bloqueado en la base de datos (bloqueo = 2).');
        $this->assertEquals(6, (int)$fila['intentos_fallidos']);
    }

    /**
     * Prueba que un usuario con bloqueo = 2 en la BD recibe acceso denegado incluso con la clave correcta.
     */
    public function testAccesoDenegadoUsuarioBloqueado(): void
    {
        $datos = [
            'cedula' => '99990003', // Usuario con bloqueo = 2
            'clave'  => $this->passwordOriginal
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('denegado', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertStringContainsString('bloqueado', $resultado['mensaje']);
    }

    /**
     * Prueba el intento de inicio de sesión con una cédula inexistente en la BD.
     */
    public function testUsuarioNoExiste(): void
    {
        $datos = [
            'cedula' => '99999999',
            'clave'  => 'CualquierClave123$'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertStringContainsString('no son correctas', $resultado['mensaje']);
    }

    /**
     * Prueba validación de campos vacíos.
     */
    public function testCamposVacios(): void
    {
        $resultado = $this->modelo->ProcesarDatos(['cedula' => '', 'clave' => '']);

        $this->assertEquals('inicio', $resultado['accion']);
        $this->assertEquals(0, $resultado['resultado']);
        $this->assertStringContainsString('obligatorias', $resultado['mensaje']);
    }
}
