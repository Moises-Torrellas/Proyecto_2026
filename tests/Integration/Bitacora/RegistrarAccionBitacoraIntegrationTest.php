<?php

declare(strict_types=1);

namespace tests\Integration\Bitacora;

use App\interface\InterBitacora;
use App\modelo\Conexion;
use App\modelo\ModeloBitacora;
use PDO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

/**
 * RF-BIT-01 (integración): Registrar acciones en la tabla bitacora de la BD de seguridad
 * real a través del procedimiento pa_incluir_bitacora. Una falla al registrar no debe
 * interrumpir la operación que el usuario estaba realizando.
 *
 * Escenarios:
 *  E1. Registro de una acción con todos sus datos.
 *  E2. Registro de una acción sin datos previos ni nuevos.
 *  E3. Datos que superan el tamaño de las columnas.
 *  E4. Usuario o módulo inexistente.
 *  E5. Registro desde los controladores con registrarBitacora() (app/controlador/Base.php).
 */
#[Group('integ')]
#[Group('bitacora')]
#[Group('bitacoraRegistrar')]
class RegistrarAccionBitacoraIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloBitacora $modelo;

    private int $idModulo;
    private int $idUsuario;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];

        $host = _DB_HOST_SG_ ?: '127.0.0.1';
        $nombre = _DB_NAME_SG_ ?: 'bds2';
        $usuario = _DB_USER_SG_ ?: 'root';

        $this->pdo = new PDO("mysql:host={$host};dbname={$nombre};charset=utf8mb4", $usuario, _DB_PASS_SG_, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        $this->inyectarConexion($this->pdo);

        $this->limpiarDatosPrueba();
        $this->crearDatosBase();

        $this->modelo = new ModeloBitacora();
    }

    protected function tearDown(): void
    {
        $this->limpiarDatosPrueba();
        $this->pdo = null;
        $this->inyectarConexion(null);
        $_SESSION = [];
        unset($_SERVER['HTTP_USER_AGENT']);
        parent::tearDown();
    }

    private function inyectarConexion(?PDO $pdo): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdo);
        $reflector->getProperty('instancia')->setValue(null, $pdo);
    }

    /**
     * Las pruebas trabajan contra la base de datos de seguridad real (_DB_NAME_SG_) sin vaciar
     * tablas. Solo se borra lo que crean: el módulo "Integ Bitacora ...", el rol
     * "Integ Bitacora Rol", los usuarios con cédula "9999xxxx" y sus registros de bitácora.
     */
    private function limpiarDatosPrueba(): void
    {
        if (!$this->pdo) {
            return;
        }

        $usuarios = "SELECT idUsuario FROM (SELECT idUsuario FROM usuarios WHERE cedulaUsuario LIKE '9999%') AS t";
        $modulos = "SELECT id_modulo FROM (SELECT id_modulo FROM modulos WHERE nombre_modulo LIKE 'Integ Bitacora%') AS t";

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("DELETE FROM bitacora WHERE idUsuario IN ($usuarios) OR id_modulo IN ($modulos) OR acciones LIKE 'Integ Bitacora%';");
        $this->pdo->exec("DELETE FROM usuarios WHERE cedulaUsuario LIKE '9999%';");
        $this->pdo->exec("DELETE FROM roles WHERE nombre_rol LIKE 'Integ Bitacora%';");
        $this->pdo->exec("DELETE FROM modulos WHERE nombre_modulo LIKE 'Integ Bitacora%';");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function crearDatosBase(): void
    {
        $this->pdo->exec("INSERT INTO modulos (nombre_modulo, descripcion, icono, estatus) VALUES ('Integ Bitacora Modulo', 'Modulo de prueba', 'circle-minus', 1)");
        $this->idModulo = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO roles (nombre_rol, descripcion, nivel_rol, estatus) VALUES ('Integ Bitacora Rol', 'Rol de prueba', 3, 1)");
        $idRol = (int)$this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("INSERT INTO usuarios
            (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos)
            VALUES ('99990701', 'Bitacora', 'Prueba', 'default.png', '0414-9990701', :pass, 'bitacora.prueba@integ.test', :rol, 1, 1, 0)");
        $stmt->execute([':pass' => password_hash('Clave.Prueba123', PASSWORD_BCRYPT), ':rol' => $idRol]);
        $this->idUsuario = (int)$this->pdo->lastInsertId();

        // El disparador de usuarios deja su propio registro; se borra para partir de una bitácora vacía
        $this->pdo->exec("DELETE FROM bitacora WHERE idUsuario = {$this->idUsuario}");
    }

    /** @return array[] registros de bitácora del usuario de prueba, del más antiguo al más reciente */
    private function registrosDelUsuario(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM bitacora WHERE idUsuario = :id ORDER BY id_bitacora ASC");
        $stmt->execute([':id' => $this->idUsuario]);
        return $stmt->fetchAll();
    }

    // Caso 1 (E1): El modelo cumple el contrato de bitácora que usan los demás módulos
    public function testImplementaInterfaz(): void
    {
        $this->assertInstanceOf(InterBitacora::class, $this->modelo);
    }

    // Caso 2 (E1): La acción queda guardada con todos sus datos y la fecha actual
    public function testRegistroConTodosLosDatos(): void
    {
        $this->modelo->RegistrarAccion($this->idModulo, 'Integ Bitacora modifico un dato', $this->idUsuario, '{"nombre":"Antes"}', '{"nombre":"Despues"}', 'Mozilla/5.0');

        $registros = $this->registrosDelUsuario();
        $this->assertCount(1, $registros);
        $this->assertSame($this->idModulo, (int)$registros[0]['id_modulo']);
        $this->assertSame('Integ Bitacora modifico un dato', $registros[0]['acciones']);
        $this->assertSame('{"nombre":"Antes"}', $registros[0]['datos_previos']);
        $this->assertSame('{"nombre":"Despues"}', $registros[0]['datos_nuevos']);
        $this->assertSame('Mozilla/5.0', $registros[0]['entorno']);

        $ahora = $this->pdo->query("SELECT NOW()")->fetchColumn();
        $this->assertLessThanOrEqual(5, abs(strtotime($ahora) - strtotime($registros[0]['fecha_hora'])));
    }

    // Caso 3 (E1): El registro se ve en la consulta con el nombre del usuario y del módulo
    public function testRegistroApareceEnConsulta(): void
    {
        $this->modelo->RegistrarAccion($this->idModulo, 'Integ Bitacora consulta visible', $this->idUsuario, '', '', 'Firefox');

        $datos = $this->modelo->Consultar(['filtro' => 'Integ Bitacora consulta visible'])['datos'];

        $this->assertCount(1, $datos);
        $this->assertSame('Bitacora', $datos[0]['nombreUsuario']);
        $this->assertSame('99990701', $datos[0]['cedulaUsuario']);
        $this->assertSame('Integ Bitacora Modulo', $datos[0]['nombre_modulo']);
    }

    // Caso 4 (E2): Con datos vacíos (como hace registrarBitacora) el registro se guarda
    public function testRegistroConDatosVacios(): void
    {
        $this->modelo->RegistrarAccion($this->idModulo, 'Integ Bitacora sin datos', $this->idUsuario, '', '', '');

        $registros = $this->registrosDelUsuario();
        $this->assertCount(1, $registros);
        $this->assertSame('', $registros[0]['datos_previos']);
        $this->assertSame('', $registros[0]['datos_nuevos']);
    }

    // Caso 5 (E2): Sin pasar datos previos ni nuevos (como hace Inicio al bloquear un usuario)
    // la acción también debe quedar registrada
    public function testRegistroSinPasarDatosOpcionales(): void
    {
        $this->modelo->RegistrarAccion($this->idModulo, 'Integ Bitacora usuario bloqueado', $this->idUsuario);

        $registros = $this->registrosDelUsuario();
        $this->assertCount(1, $registros, 'La acción no quedó registrada en la bitácora.');
        $this->assertSame('Integ Bitacora usuario bloqueado', $registros[0]['acciones']);
        $this->assertSame('No Aplica', $registros[0]['datos_previos']);
        $this->assertSame('No Aplica', $registros[0]['datos_nuevos']);
    }

    // Caso 6 (E3): Un entorno de más de 50 caracteres se guarda recortado
    public function testEntornoLargoSeRecorta(): void
    {
        $entorno = str_repeat('N', 80);

        $this->modelo->RegistrarAccion($this->idModulo, 'Integ Bitacora entorno largo', $this->idUsuario, '', '', $entorno);

        $registros = $this->registrosDelUsuario();
        $this->assertCount(1, $registros);
        $this->assertSame(str_repeat('N', 50), $registros[0]['entorno']);
    }

    // Caso 7 (E4): Un usuario inexistente no genera registro ni interrumpe la operación
    public function testUsuarioInexistenteNoInterrumpe(): void
    {
        $this->modelo->RegistrarAccion($this->idModulo, 'Integ Bitacora usuario fantasma', 99999999, '', '', '');

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM bitacora WHERE acciones = 'Integ Bitacora usuario fantasma'");
        $this->assertSame(0, (int)$stmt->fetchColumn());
    }

    // Caso 8 (E4): Un módulo inexistente no genera registro ni interrumpe la operación
    public function testModuloInexistenteNoInterrumpe(): void
    {
        $this->modelo->RegistrarAccion(99999999, 'Integ Bitacora modulo fantasma', $this->idUsuario, '', '', '');

        $this->assertSame([], $this->registrosDelUsuario());
    }

    // Caso 9 (E5): Con sesión iniciada se registra a nombre del usuario y con el navegador recortado a 50
    public function testControladorRegistraConSesion(): void
    {
        $_SESSION['id'] = $this->idUsuario;
        $_SERVER['HTTP_USER_AGENT'] = str_repeat('Mozilla ', 10);

        registrarBitacora($this->modelo, $this->idModulo, 'Integ Bitacora desde controlador', '{"a":1}', '{"a":2}');

        $registros = $this->registrosDelUsuario();
        $this->assertCount(1, $registros);
        $this->assertSame('Integ Bitacora desde controlador', $registros[0]['acciones']);
        $this->assertSame('{"a":1}', $registros[0]['datos_previos']);
        $this->assertSame('{"a":2}', $registros[0]['datos_nuevos']);
        $this->assertSame(substr(str_repeat('Mozilla ', 10), 0, 50), $registros[0]['entorno']);
    }

    // Caso 10 (E5): Sin navegador identificado el entorno queda como "Desconocido"
    public function testControladorSinNavegador(): void
    {
        $_SESSION['id'] = $this->idUsuario;
        unset($_SERVER['HTTP_USER_AGENT']);

        registrarBitacora($this->modelo, $this->idModulo, 'Integ Bitacora sin navegador');

        $registros = $this->registrosDelUsuario();
        $this->assertCount(1, $registros);
        $this->assertSame('Desconocido', $registros[0]['entorno']);
    }

    // Caso 11 (E5): Sin sesión iniciada no se registra nada
    public function testControladorSinSesionNoRegistra(): void
    {
        registrarBitacora($this->modelo, $this->idModulo, 'Integ Bitacora sin sesion');

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM bitacora WHERE acciones = 'Integ Bitacora sin sesion'");
        $this->assertSame(0, (int)$stmt->fetchColumn());
    }
}
