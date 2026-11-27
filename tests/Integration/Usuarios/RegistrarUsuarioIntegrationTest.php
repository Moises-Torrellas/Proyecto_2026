<?php

declare(strict_types=1);

namespace tests\Integration\Usuarios;

use App\modelo\Conexion;
use App\modelo\ModeloUsuarios;
use PDO;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('integ')]
#[Group('usuarios')]
#[Group('usuariosRegistrar')]
class RegistrarUsuarioIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloUsuarios $modelo;

    private array $roles = [];

    private string $claveInicial = 'ClaveInicial.123';

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
        $this->crearRolesPrueba();

        $this->modelo = new ModeloUsuarios();
    }

    protected function tearDown(): void
    {
        $this->limpiarDatosPrueba();
        $this->pdo = null;
        $this->inyectarConexion(null);
        $_SESSION = [];
        parent::tearDown();
    }

    private function inyectarConexion(?PDO $pdo): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, $pdo);
        $reflector->getProperty('instancia')->setValue(null, $pdo);
    }

    private function limpiarDatosPrueba(): void
    {
        if (!$this->pdo) {
            return;
        }

        $idsUsuarios = "SELECT idUsuario FROM (SELECT idUsuario FROM usuarios WHERE cedulaUsuario LIKE '9999%') AS t";
        $idsRoles = "SELECT id_rol FROM (SELECT id_rol FROM roles WHERE nombre_rol LIKE 'Integ Usuarios%') AS t";

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("DELETE FROM excepciones WHERE id_usuario IN ($idsUsuarios);");
        $this->pdo->exec("DELETE FROM notificaciones WHERE id_usuario IN ($idsUsuarios);");
        $this->pdo->exec("DELETE FROM bitacora WHERE idUsuario IN ($idsUsuarios) OR acciones LIKE 'Registro al usuario: 9999%';");
        $this->pdo->exec("DELETE FROM usuarios WHERE cedulaUsuario LIKE '9999%';");
        $this->pdo->exec("DELETE FROM permisos_rol WHERE id_rol IN ($idsRoles);");
        $this->pdo->exec("DELETE FROM roles WHERE nombre_rol LIKE 'Integ Usuarios%';");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function crearRolesPrueba(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO roles (nombre_rol, descripcion, nivel_rol, estatus) VALUES (:nombre, 'Rol de prueba', :nivel, 1)");

        foreach (['nivel1' => 1, 'nivel2' => 2, 'nivel3' => 3, 'nivel3b' => 3] as $clave => $nivel) {
            $stmt->execute([':nombre' => "Integ Usuarios {$clave}", ':nivel' => $nivel]);
            $this->roles[$clave] = (int)$this->pdo->lastInsertId();
        }
    }

    private function crearUsuario(array $datos = []): int
    {
        $base = [
            'cedula'   => '99990001',
            'nombre'   => 'Usuario',
            'apellido' => 'Prueba',
            'foto'     => 'default.png',
            'telefono' => '0414-9990001',
            'correo'   => 'usuario.prueba@integ.test',
            'rol'      => $this->roles['nivel3'],
            'estatus'  => 1,
            'bloqueo'  => 1,
        ];
        $d = array_merge($base, $datos);

        $stmt = $this->pdo->prepare("INSERT INTO usuarios
            (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos)
            VALUES (:cedula, :nombre, :apellido, :foto, :telefono, :pass, :correo, :rol, :estatus, :bloqueo, 0)");
        $stmt->execute([
            ':cedula'   => $d['cedula'],
            ':nombre'   => $d['nombre'],
            ':apellido' => $d['apellido'],
            ':foto'     => $d['foto'],
            ':telefono' => $d['telefono'],
            ':pass'     => password_hash($this->claveInicial, PASSWORD_BCRYPT),
            ':correo'   => $d['correo'],
            ':rol'      => $d['rol'],
            ':estatus'  => $d['estatus'],
            ':bloqueo'  => $d['bloqueo'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    private function obtenerUsuario(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE idUsuario = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function obtenerUsuarioPorCedula(string $cedula): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE cedulaUsuario = :cedula");
        $stmt->execute([':cedula' => $cedula]);
        return $stmt->fetch();
    }

    private function contarUsuariosPorCedula(string $cedula): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE cedulaUsuario = :cedula");
        $stmt->execute([':cedula' => $cedula]);
        return (int)$stmt->fetchColumn();
    }

    private function datosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'accion'     => 'incluir',
            'cedula'     => '99990010',
            'nombre'     => '  maría josé ',
            'apellido'   => 'pérez lópez',
            'telefono'   => '0414-9990010',
            'correo'     => '  Maria.Perez@Integ.TEST ',
            'contraseña' => 'Clave.Segura123',
            'roles_id'   => $this->roles['nivel3'],
            'foto'       => 'user_99990010.png',
        ], $sobrescribir);
    }

    public function testRegistroExitosoGuardaUsuarioEnBD(): void
    {
        $resultado = $this->modelo->procesarDatos($this->datosValidos());

        $this->assertSame(['accion' => 'exito'], $resultado);

        $usuario = $this->obtenerUsuarioPorCedula('99990010');
        $this->assertNotFalse($usuario, 'El usuario debe existir en la BD.');
        $this->assertSame('María José', $usuario['nombreUsuario']);
        $this->assertSame('Pérez López', $usuario['apellidoUsuario']);
        $this->assertSame('maria.perez@integ.test', $usuario['correo']);
        $this->assertSame('0414-9990010', $usuario['telefonoUsuario']);
        $this->assertSame('user_99990010.png', $usuario['foto']);
        $this->assertSame($this->roles['nivel3'], (int)$usuario['id_rol']);
        $this->assertSame(1, (int)$usuario['estatus']);
        $this->assertSame(1, (int)$usuario['bloqueo'], 'Un usuario nuevo nace desbloqueado.');
        $this->assertSame(0, (int)$usuario['intentos_fallidos']);
        $this->assertTrue(password_verify('Clave.Segura123', $usuario['pass_hash']));
    }

    public function testRegistroQuedaEnBitacoraConUsuarioDeSesion(): void
    {
        $idOperador = $this->crearUsuario(['cedula' => '99990099', 'correo' => 'operador@integ.test', 'telefono' => '0414-9990099']);
        $_SESSION['idUsuario'] = $idOperador;

        $resultado = $this->modelo->procesarDatos($this->datosValidos());
        $this->assertSame('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT * FROM bitacora WHERE acciones LIKE 'Registro al usuario: 99990010%'");
        $bitacora = $stmt->fetchAll();

        $this->assertCount(1, $bitacora, 'El disparador debe dejar un único registro en la bitácora.');
        $this->assertSame($idOperador, (int)$bitacora[0]['idUsuario']);
        $this->assertSame(1, (int)$bitacora[0]['id_modulo']);
        $this->assertStringContainsString('Integ Usuarios nivel3', $bitacora[0]['datos_nuevos']);
    }

    public function testUsuarioRegistradoApareceEnConsulta(): void
    {
        $this->modelo->procesarDatos($this->datosValidos());

        $resultado = $this->modelo->Consultar(['filtro' => '99990010']);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertSame('Integ Usuarios nivel3', $resultado['datos'][0]['nombre_rol']);
    }

    public function testReactivaUsuarioEliminado(): void
    {
        $idOriginal = $this->crearUsuario([
            'cedula'   => '99990010',
            'nombre'   => 'Antiguo',
            'correo'   => 'antiguo@integ.test',
            'telefono' => '0414-9990000',
            'estatus'  => 0,
        ]);

        $resultado = $this->modelo->procesarDatos($this->datosValidos());

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame(1, $this->contarUsuariosPorCedula('99990010'), 'No debe duplicarse la cédula.');

        $usuario = $this->obtenerUsuario($idOriginal);
        $this->assertSame(1, (int)$usuario['estatus']);
        $this->assertSame('María José', $usuario['nombreUsuario']);
        $this->assertSame('maria.perez@integ.test', $usuario['correo']);
        $this->assertSame('0414-9990010', $usuario['telefonoUsuario']);
        $this->assertTrue(password_verify('Clave.Segura123', $usuario['pass_hash']));
    }

    public function testRegistroSinContraseñaNoInserta(): void
    {
        $resultado = $this->modelo->procesarDatos($this->datosValidos(['contraseña' => '']));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(VALIDATION, $resultado['codigo']);
        $this->assertSame(0, $this->contarUsuariosPorCedula('99990010'));
    }

    public function testRegistroConRolInexistenteNoInserta(): void
    {
        $resultado = $this->modelo->procesarDatos($this->datosValidos(['roles_id' => 999999]));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertSame(0, $this->contarUsuariosPorCedula('99990010'));
    }

    public function testRechazaCedulaDeUsuarioActivo(): void
    {
        $idExistente = $this->crearUsuario(['cedula' => '99990010', 'nombre' => 'Existente']);

        $resultado = $this->modelo->procesarDatos($this->datosValidos([
            'correo'   => 'otro@integ.test',
            'telefono' => '0414-9990011',
        ]));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_CEDULA, $resultado['codigo']);
        $this->assertSame('Existente', $this->obtenerUsuario($idExistente)['nombreUsuario'], 'El usuario existente no debe cambiar.');
    }

    public function testRechazaCorreoDeUsuarioActivo(): void
    {
        $this->crearUsuario(['cedula' => '99990001', 'correo' => 'maria.perez@integ.test']);

        $resultado = $this->modelo->procesarDatos($this->datosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL, $resultado['codigo']);
        $this->assertSame(0, $this->contarUsuariosPorCedula('99990010'));
    }

    public function testRechazaTelefonoDeUsuarioActivo(): void
    {
        $this->crearUsuario(['cedula' => '99990001', 'telefono' => '0414-9990010']);

        $resultado = $this->modelo->procesarDatos($this->datosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE, $resultado['codigo']);
        $this->assertSame(0, $this->contarUsuariosPorCedula('99990010'));
    }

    public function testRechazaCorreoDeUsuarioEliminado(): void
    {
        $this->crearUsuario(['cedula' => '99990001', 'correo' => 'maria.perez@integ.test', 'estatus' => 0]);

        $resultado = $this->modelo->procesarDatos($this->datosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL . '0', $resultado['codigo']);
        $this->assertSame(0, $this->contarUsuariosPorCedula('99990010'));
    }

    public function testRechazaTelefonoDeUsuarioEliminado(): void
    {
        $this->crearUsuario(['cedula' => '99990001', 'telefono' => '0414-9990010', 'estatus' => 0]);

        $resultado = $this->modelo->procesarDatos($this->datosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE . '0', $resultado['codigo']);
        $this->assertSame(0, $this->contarUsuariosPorCedula('99990010'));
    }

    public function testReactivacionRechazaCorreoDeOtroUsuarioEliminado(): void
    {
        $idReactivar = $this->crearUsuario(['cedula' => '99990010', 'correo' => 'propio@integ.test', 'telefono' => '0414-9990000', 'estatus' => 0]);
        $this->crearUsuario(['cedula' => '99990001', 'correo' => 'maria.perez@integ.test', 'telefono' => '0414-9990001', 'estatus' => 0]);

        $resultado = $this->modelo->procesarDatos($this->datosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL . '0', $resultado['codigo']);
        $this->assertSame(0, (int)$this->obtenerUsuario($idReactivar)['estatus'], 'No debe reactivarse.');
    }

    public function testErrorDelProcedimientoNoReportaExito(): void
    {
        $this->pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");

        $resultado = $this->modelo->procesarDatos($this->datosValidos(['foto' => str_repeat('a', 300) . '.png']));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(0, $this->contarUsuariosPorCedula('99990010'));
    }
}
