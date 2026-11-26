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

/**
 * RF-USU-08 (integración): Edición del perfil propio por secciones (personal, contacto,
 * seguridad). Cada sección actualiza solo sus columnas en la BD y refresca la sesión.
 *
 * Escenarios:
 *  E1. Edición de datos personales (con y sin foto nueva).
 *  E2. Edición de datos de contacto.
 *  E3. Cambio de contraseña.
 *  E4. Datos duplicados de otro usuario activo.
 *  E5. Tipo de edición inválido o falta de contraseña.
 */
#[Group('integ')]
#[Group('usuarios')]
#[Group('usuariosEditarPerfil')]
class EditarPerfilUsuarioIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloUsuarios $modelo;

    /** Roles creados para la prueba: ['nivel1' => id, 'nivel2' => id, 'nivel3' => id, 'nivel3b' => id] */
    private array $roles = [];

    private string $claveInicial = 'ClaveInicial.123';

    private int $idUsuario;

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

        $this->idUsuario = $this->crearUsuario([
            'cedula'   => '99990040',
            'nombre'   => 'Perfil',
            'apellido' => 'Original',
            'foto'     => 'foto_original.png',
            'telefono' => '0414-9990040',
            'correo'   => 'perfil@integ.test',
        ]);
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

    /**
     * Las pruebas trabajan contra la base de datos de seguridad real (_DB_NAME_SG_) sin vaciar tablas:
     * todo lo que crean las pruebas se identifica con la cédula "9999xxxx" y con roles cuyo
     * nombre empieza por "Integ Usuarios", y se elimina antes y después de cada prueba.
     * Así los usuarios propios del sistema (Admin, Soporte, ...) nunca se modifican.
     */
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

    /**
     * Inserta un usuario directamente en la BD (sin pasar por el modelo) y devuelve su id.
     */
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

    private function editar(string $tipo, array $datos): array
    {
        return $this->modelo->procesarDatos(array_merge([
            'accion'       => 'editar_perfil',
            'tipo_edicion' => $tipo,
            'id'           => $this->idUsuario,
        ], $datos));
    }

    // Caso 1 (E1): Datos personales sin foto: se actualizan y la foto se conserva
    public function testEditarDatosPersonalesSinFoto(): void
    {
        $resultado = $this->editar('personal', ['cedula' => '99990041', 'nombre' => 'nuevo', 'apellido' => 'perfil']);

        $this->assertSame('exito', $resultado['accion']);
        $usuario = $this->obtenerUsuario($this->idUsuario);
        $this->assertSame('99990041', $usuario['cedulaUsuario']);
        $this->assertSame('Nuevo', $usuario['nombreUsuario']);
        $this->assertSame('Perfil', $usuario['apellidoUsuario']);
        $this->assertSame('foto_original.png', $usuario['foto']);
        $this->assertSame('perfil@integ.test', $usuario['correo'], 'No debe tocar los datos de contacto.');

        $this->assertSame('99990041', $_SESSION['cedula']);
        $this->assertSame('Nuevo', $_SESSION['nombre']);
        $this->assertArrayNotHasKey('foto', $_SESSION);
    }

    // Caso 2 (E1): Datos personales con foto nueva
    public function testEditarDatosPersonalesConFoto(): void
    {
        $resultado = $this->editar('personal', [
            'cedula' => '99990040', 'nombre' => 'Perfil', 'apellido' => 'Original', 'foto' => 'foto_nueva.png',
        ]);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame('foto_nueva.png', $this->obtenerUsuario($this->idUsuario)['foto']);
        $this->assertSame('foto_nueva.png', $_SESSION['foto']);
    }

    // Caso 3 (E2): Datos de contacto
    public function testEditarDatosContacto(): void
    {
        $resultado = $this->editar('contacto', ['telefono' => '0414-9990049', 'correo' => 'Contacto.Nuevo@Integ.test']);

        $this->assertSame('exito', $resultado['accion']);
        $usuario = $this->obtenerUsuario($this->idUsuario);
        $this->assertSame('0414-9990049', $usuario['telefonoUsuario']);
        $this->assertSame('contacto.nuevo@integ.test', $usuario['correo']);
        $this->assertSame('Perfil', $usuario['nombreUsuario'], 'No debe tocar los datos personales.');
        $this->assertSame('contacto.nuevo@integ.test', $_SESSION['correo']);
    }

    // Caso 4 (E3): Cambio de contraseña
    public function testCambiarContraseña(): void
    {
        $resultado = $this->editar('seguridad', ['contraseña' => 'Perfil.Nueva789']);

        $this->assertSame('exito', $resultado['accion']);
        $hash = $this->obtenerUsuario($this->idUsuario)['pass_hash'];
        $this->assertTrue(password_verify('Perfil.Nueva789', $hash));
        $this->assertArrayNotHasKey('contraseña', $_SESSION);
    }

    // Caso 5 (E4): Cédula de otro usuario activo
    public function testRechazaCedulaDeOtroUsuario(): void
    {
        $this->crearUsuario(['cedula' => '99990048', 'correo' => 'otro@integ.test', 'telefono' => '0414-9990048']);

        $resultado = $this->editar('personal', ['cedula' => '99990048', 'nombre' => 'Cambio', 'apellido' => 'Fallido']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_CEDULA, $resultado['codigo']);
        $this->assertSame('Perfil', $this->obtenerUsuario($this->idUsuario)['nombreUsuario']);
    }

    // Caso 6 (E4): Correo de otro usuario activo
    public function testRechazaCorreoDeOtroUsuario(): void
    {
        $this->crearUsuario(['cedula' => '99990048', 'correo' => 'otro@integ.test', 'telefono' => '0414-9990048']);

        $resultado = $this->editar('contacto', ['telefono' => '0414-9990049', 'correo' => 'otro@integ.test']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL, $resultado['codigo']);
        $this->assertSame('0414-9990040', $this->obtenerUsuario($this->idUsuario)['telefonoUsuario']);
    }

    // Caso 7 (E4): Teléfono de otro usuario activo
    public function testRechazaTelefonoDeOtroUsuario(): void
    {
        $this->crearUsuario(['cedula' => '99990048', 'correo' => 'otro@integ.test', 'telefono' => '0414-9990048']);

        $resultado = $this->editar('contacto', ['telefono' => '0414-9990048', 'correo' => 'perfil@integ.test']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE, $resultado['codigo']);
    }

    // Caso 8 (E5): Seguridad sin contraseña nueva
    public function testSeguridadSinContraseña(): void
    {
        $hashAnterior = $this->obtenerUsuario($this->idUsuario)['pass_hash'];

        $resultado = $this->editar('seguridad', ['contraseña' => '']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame($hashAnterior, $this->obtenerUsuario($this->idUsuario)['pass_hash']);
    }

    // Caso 9 (E5): Tipo de edición no válido
    public function testTipoEdicionInvalido(): void
    {
        $resultado = $this->editar('otro', ['nombre' => 'X']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Perfil', $this->obtenerUsuario($this->idUsuario)['nombreUsuario']);
    }
}
