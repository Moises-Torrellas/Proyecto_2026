<?php

declare(strict_types=1);

namespace tests\Integration\Recuperacion;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRecuperacion;
use App\modelo\ModeloInicio;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("recuperacion")]
class RecuperacionIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloRecuperacion $modelo;
    private string $passwordInicial = 'ClaveAntigua123$';

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];

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

        // Modelo con simulador de envío de correo para integración sin dependencia externa de SMTP
        $this->modelo = $this->crearModeloConSimuladorCorreo();

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

    private function crearModeloConSimuladorCorreo(): ModeloRecuperacion
    {
        return new class extends ModeloRecuperacion {
            protected function enviarCorreo($mensaje): array
            {
                $_SESSION['codigo_verificacion'] = '654321';
                $_SESSION['codigo_timestamp'] = time();
                $_SESSION['codigo_intentos'] = 0;

                return ['accion' => 'comprobar', 'mensaje' => $mensaje];
            }
        };
    }

    private function cargarDatosPrueba(): void
    {
        $hash = password_hash($this->passwordInicial, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare("INSERT INTO usuarios 
            (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos) 
            VALUES (:cedula, 'Pedro', 'Recuperador', 'default.png', '0414-9998877', :hash, 'pedro_integ@test.com', 6, 1, 1, 0)");
        $stmt->execute([':cedula' => '99990005', ':hash' => $hash]);
    }

    /**
     * Prueba que consultar una cédula existente en la BD inicia el proceso de recuperación y carga datos en $_SESSION.
     */
    public function testComprobarCedulaExistenteEnBD(): void
    {
        $datos = [
            'accion' => 'comprobar',
            'cedula' => '99990005'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('comprobar', $resultado['accion']);
        $this->assertEquals('pedro_integ@test.com', $_SESSION['correo']);
        $this->assertEquals('Pedro Recuperador', $_SESSION['destinatario']);
        $this->assertEquals('99990005', $_SESSION['cedula_r']);
        $this->assertFalse($_SESSION['verificacion']);
        $this->assertEquals('654321', $_SESSION['codigo_verificacion']);
    }

    /**
     * Prueba que consultar una cédula no registrada en la base de datos retorna error.
     */
    public function testComprobarCedulaInexistenteEnBD(): void
    {
        $datos = [
            'accion' => 'comprobar',
            'cedula' => '99999999'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('cédula está registrada', $resultado['mensaje']);
        $this->assertArrayNotHasKey('correo', $_SESSION);
    }

    /**
     * Prueba que validar el código correcto emitido otorga permiso de cambio (verificacion = true).
     */
    public function testComprobarCodigoExitoso(): void
    {
        $_SESSION['codigo_verificacion'] = '654321';
        $_SESSION['codigo_timestamp'] = time();
        $_SESSION['codigo_intentos'] = 0;

        $datos = [
            'accion' => 'comprobarCodigo',
            'codigo' => '654321'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('comprobarCodigo', $resultado['accion']);
        $this->assertTrue($_SESSION['verificacion']);
    }

    /**
     * Prueba que un código erróneo no autoriza la verificación.
     */
    public function testComprobarCodigoInvalido(): void
    {
        $_SESSION['codigo_verificacion'] = '654321';
        $_SESSION['codigo_timestamp'] = time();
        $_SESSION['codigo_intentos'] = 0;

        $datos = [
            'accion' => 'comprobarCodigo',
            'codigo' => '111111'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('no es correcto', $resultado['mensaje']);
        $this->assertArrayNotHasKey('verificacion', $_SESSION);
    }

    /**
     * Prueba que un código expirado (> 30 minutos) es rechazado.
     */
    public function testComprobarCodigoExpirado(): void
    {
        $_SESSION['codigo_verificacion'] = '654321';
        $_SESSION['codigo_timestamp'] = time() - 1900; // 31 minutos atrás
        $_SESSION['codigo_intentos'] = 0;

        $datos = [
            'accion' => 'comprobarCodigo',
            'codigo' => '654321'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('ha expirado', $resultado['mensaje']);
    }

    /**
     * Prueba que cambiar contraseña realiza el UPDATE real en la tabla usuarios de la BD.
     */
    public function testCambiarContraseñaActualizaRegistroEnBaseDeDatos(): void
    {
        $_SESSION['verificacion'] = true;
        $_SESSION['cedula_r'] = '99990005';

        $nuevaClave = 'NuevaClaveSuperSegura2026$';

        $datos = [
            'accion'     => 'cambiar',
            'contraseña' => $nuevaClave
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('cambiar', $resultado['accion']);
        $this->assertStringContainsString('correctamente', $resultado['mensaje']);

        // Consultar directamente en MySQL para verificar la actualización física
        $stmt = $this->pdo->prepare("SELECT pass_hash FROM usuarios WHERE cedulaUsuario = '99990005'");
        $stmt->execute();
        $nuevoHash = $stmt->fetchColumn();

        $this->assertNotEmpty($nuevoHash);
        $this->assertTrue(password_verify($nuevaClave, $nuevoHash), 'El hash en la BD debe coincidir con la nueva contraseña.');
    }

    /**
     * Prueba el flujo completo de extremo a extremo:
     * 1. Comprobar cédula en BD.
     * 2. Validar código de verificación.
     * 3. Cambiar contraseña en BD.
     * 4. Iniciar sesión con ModeloInicio usando la nueva contraseña.
     */
    public function testFlujoCompletoDeRecuperacionEInicioDeSesion(): void
    {
        $cedula = '99990005';
        $claveNueva = 'ClaveRecuperada123$';

        // 1. Comprobar Cédula
        $resComprobar = $this->modelo->ProcesarDatos(['accion' => 'comprobar', 'cedula' => $cedula]);
        $this->assertEquals('comprobar', $resComprobar['accion']);

        // 2. Comprobar Código
        $codigoGenerado = $_SESSION['codigo_verificacion'];
        $resCodigo = $this->modelo->ProcesarDatos(['accion' => 'comprobarCodigo', 'codigo' => $codigoGenerado]);
        $this->assertEquals('comprobarCodigo', $resCodigo['accion']);
        $this->assertTrue($_SESSION['verificacion']);

        // 3. Cambiar Contraseña
        $resCambiar = $this->modelo->ProcesarDatos(['accion' => 'cambiar', 'contraseña' => $claveNueva]);
        $this->assertEquals('cambiar', $resCambiar['accion']);

        // 4. Iniciar Sesión con ModeloInicio usando las nuevas credenciales
        $modeloInicio = new ModeloInicio();
        $resInicio = $modeloInicio->ProcesarDatos(['cedula' => $cedula, 'clave' => $claveNueva]);

        $this->assertEquals('inicio', $resInicio['accion']);
        $this->assertEquals(1, $resInicio['resultado'], 'El inicio de sesión debe ser exitoso con la nueva clave.');
        $this->assertEquals($cedula, $resInicio['datos']['cedulaUsuario']);
    }
}
