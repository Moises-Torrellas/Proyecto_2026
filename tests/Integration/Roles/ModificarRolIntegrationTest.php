<?php

declare(strict_types=1);

namespace tests\Integration\Roles;

use App\modelo\Conexion;
use App\modelo\ModeloRoles;
use PDO;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

/**
 * RF-ROL-04 (integración): Modificar el nombre y la descripción de un rol en la BD de
 * seguridad real. El nombre no puede pertenecer a otro rol.
 *
 * Escenarios:
 *  E1. Modificación con nombre y descripción.
 *  E2. Modificación solo del nombre (se conserva la descripción).
 *  E3. El rol conserva su propio nombre.
 *  E4. Rol inexistente.
 *  E5. Nombre ya usado por otro rol.
 */
#[Group('integ')]
#[Group('roles')]
#[Group('rolesModificar')]
class ModificarRolIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloRoles $modelo;

    private int $modulo;
    /** @var int[] Tres permisos de prueba del módulo de prueba */
    private array $permisos = [];

    private int $idRol;

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
        $this->crearPermisosPrueba();

        $this->modelo = new ModeloRoles();
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
     * Las pruebas trabajan contra la base de datos de seguridad real (_DB_NAME_SG_) sin vaciar tablas.
     * Todo lo que crean las pruebas se identifica y se elimina antes y después de cada prueba:
     * - roles cuyo nombre empieza por "Integ Roles",
     * - el módulo "Integ Roles Modulo" y sus permisos (clave "integ_rol_..."),
     * - usuarios con cédula "9999xxxx".
     * Los roles, permisos y usuarios propios del sistema nunca se modifican.
     */
    private function limpiarDatosPrueba(): void
    {
        if (!$this->pdo) {
            return;
        }

        $permisos = "SELECT id_permiso FROM (SELECT id_permiso FROM permisos WHERE clave LIKE 'integ\\_rol\\_%') AS t";
        $usuarios = "SELECT idUsuario FROM (SELECT idUsuario FROM usuarios WHERE cedulaUsuario LIKE '9999%') AS t";
        $roles = "SELECT id_rol FROM (SELECT id_rol FROM roles WHERE nombre_rol LIKE 'Integ Roles%') AS t";

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("DELETE FROM excepciones WHERE id_permiso IN ($permisos) OR id_usuario IN ($usuarios);");
        $this->pdo->exec("DELETE FROM permisos_rol WHERE id_permiso IN ($permisos) OR id_rol IN ($roles);");
        $this->pdo->exec("DELETE FROM bitacora WHERE idUsuario IN ($usuarios) OR acciones LIKE 'Registro al usuario: 9999%';");
        $this->pdo->exec("DELETE FROM usuarios WHERE cedulaUsuario LIKE '9999%';");
        $this->pdo->exec("DELETE FROM roles WHERE nombre_rol LIKE 'Integ Roles%';");
        $this->pdo->exec("DELETE FROM permisos WHERE id_permiso IN ($permisos);");
        $this->pdo->exec("DELETE FROM modulos WHERE nombre_modulo LIKE 'Integ Roles%';");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function crearPermisosPrueba(): void
    {
        $this->pdo->exec("INSERT INTO modulos (nombre_modulo, descripcion, icono, estatus) VALUES ('Integ Roles Modulo', 'Modulo de prueba', 'circle-minus', 1)");
        $this->modulo = (int)$this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("INSERT INTO permisos (id_modulo, nombre, clave, descripcion, estatus) VALUES (:modulo, :nombre, :clave, 'Permiso de prueba', 1)");
        foreach (['uno', 'dos', 'tres'] as $sufijo) {
            $stmt->execute([':modulo' => $this->modulo, ':nombre' => "Integ Rol Permiso {$sufijo}", ':clave' => "integ_rol_{$sufijo}"]);
            $this->permisos[] = (int)$this->pdo->lastInsertId();
        }
    }

    /**
     * Inserta un rol directamente en la BD (sin pasar por el modelo) y devuelve su id.
     */
    private function crearRol(string $nombre, int $nivel = 3, string $descripcion = 'Descripcion De Prueba'): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO roles (nombre_rol, descripcion, nivel_rol, estatus) VALUES (:nombre, :descripcion, :nivel, 1)");
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':nivel' => $nivel]);
        return (int)$this->pdo->lastInsertId();
    }

    private function obtenerRol(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE id_rol = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function contarRoles(string $nombre): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM roles WHERE nombre_rol = :nombre");
        $stmt->execute([':nombre' => $nombre]);
        return (int)$stmt->fetchColumn();
    }

    private function asignarPermisosRol(int $idRol, array $idsPermisos): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO permisos_rol (id_permiso, id_rol) VALUES (:permiso, :rol)");
        foreach ($idsPermisos as $idPermiso) {
            $stmt->execute([':permiso' => $idPermiso, ':rol' => $idRol]);
        }
    }

    /** @return int[] ids de permisos asignados al rol, ordenados */
    private function permisosDelRol(int $idRol): array
    {
        $stmt = $this->pdo->prepare("SELECT id_permiso FROM permisos_rol WHERE id_rol = :rol ORDER BY id_permiso");
        $stmt->execute([':rol' => $idRol]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function crearUsuario(int $idRol, int $estatus = 1, string $cedula = '99990601'): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO usuarios
            (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos)
            VALUES (:cedula, 'Usuario', 'Roles', 'default.png', :telefono, :pass, :correo, :rol, :estatus, 1, 0)");
        $stmt->execute([
            ':cedula'   => $cedula,
            ':telefono' => '0414-' . substr($cedula, -7),
            ':pass'     => password_hash('Clave.Prueba123', PASSWORD_BCRYPT),
            ':correo'   => "rol.{$cedula}@integ.test",
            ':rol'      => $idRol,
            ':estatus'  => $estatus,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    private function prepararRol(): void
    {
        $this->idRol = $this->crearRol('Integ Roles Original', 3, 'Descripcion Original');
    }

    private function modificar(array $datos): array
    {
        return $this->modelo->procesarDatos(array_merge([
            'accion' => 'modificar',
            'id'     => (string)$this->idRol,
        ], $datos));
    }

    // Caso 1 (E1): Se actualizan nombre y descripción
    public function testModificacionExitosa(): void
    {
        $this->prepararRol();

        $resultado = $this->modificar(['nombre' => 'integ roles cambiado', 'descripcion' => 'nueva descripcion']);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $rol = $this->obtenerRol($this->idRol);
        $this->assertSame('Integ Roles Cambiado', $rol['nombre_rol']);
        $this->assertSame('Nueva Descripcion', $rol['descripcion']);
        $this->assertSame(3, (int)$rol['nivel_rol'], 'El nivel del rol no cambia.');
    }

    // Caso 2 (E1): Modificar el rol no toca sus permisos ni sus usuarios
    public function testModificarNoAfectaPermisosNiUsuarios(): void
    {
        $this->prepararRol();
        $this->asignarPermisosRol($this->idRol, [$this->permisos[0], $this->permisos[1]]);
        $idUsuario = $this->crearUsuario($this->idRol);

        $this->modificar(['nombre' => 'Integ Roles Cambiado']);

        $this->assertSame([$this->permisos[0], $this->permisos[1]], $this->permisosDelRol($this->idRol));
        $stmt = $this->pdo->prepare("SELECT id_rol FROM usuarios WHERE idUsuario = :id");
        $stmt->execute([':id' => $idUsuario]);
        $this->assertSame($this->idRol, (int)$stmt->fetchColumn());
    }

    // Caso 3 (E2): Sin descripción se conserva la que tenía
    public function testModificacionSoloNombre(): void
    {
        $this->prepararRol();

        $resultado = $this->modificar(['nombre' => 'Integ Roles Cambiado', 'descripcion' => '']);

        $this->assertSame('exito', $resultado['accion']);
        $rol = $this->obtenerRol($this->idRol);
        $this->assertSame('Integ Roles Cambiado', $rol['nombre_rol']);
        $this->assertSame('Descripcion Original', $rol['descripcion']);
    }

    // Caso 4 (E3): Guardar con su mismo nombre no se considera duplicado
    public function testConservaSuPropioNombre(): void
    {
        $this->prepararRol();

        $resultado = $this->modificar(['nombre' => 'Integ Roles Original', 'descripcion' => 'otra descripcion']);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame('Otra Descripcion', $this->obtenerRol($this->idRol)['descripcion']);
    }

    // Caso 5 (E4): Un rol que no existe
    public function testRechazaRolInexistente(): void
    {
        $this->idRol = 99999999;

        $resultado = $this->modificar(['nombre' => 'Integ Roles Cambiado']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertSame(0, $this->contarRoles('Integ Roles Cambiado'));
    }

    // Caso 6 (E5): Nombre que ya usa otro rol
    public function testRechazaNombreDeOtroRol(): void
    {
        $this->prepararRol();
        $this->crearRol('Integ Roles Ocupado');

        $resultado = $this->modificar(['nombre' => 'Integ Roles Ocupado', 'descripcion' => 'cambio']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
        $rol = $this->obtenerRol($this->idRol);
        $this->assertSame('Integ Roles Original', $rol['nombre_rol'], 'La transacción debe revertirse.');
        $this->assertSame('Descripcion Original', $rol['descripcion']);
    }
}
