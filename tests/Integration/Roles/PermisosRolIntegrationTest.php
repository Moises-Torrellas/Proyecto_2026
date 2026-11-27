<?php

declare(strict_types=1);

namespace tests\Integration\Roles;

use App\modelo\Conexion;
use App\modelo\ModeloRoles;
use PDO;
use ReflectionClass;
use App\modelo\ModeloPermisos;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('integ')]
#[Group('roles')]
#[Group('rolesPermisos')]
class PermisosRolIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloRoles $modelo;

    private int $modulo;
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

    private function crearRol(string $nombre, int $nivel = 3, string $descripcion = 'Descripcion De Prueba'): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO roles (nombre_rol, descripcion, nivel_rol, estatus) VALUES (:nombre, :descripcion, :nivel, 1)");
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':nivel' => $nivel]);
        return (int)$this->pdo->lastInsertId();
    }

    private function asignarPermisosRol(int $idRol, array $idsPermisos): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO permisos_rol (id_permiso, id_rol) VALUES (:permiso, :rol)");
        foreach ($idsPermisos as $idPermiso) {
            $stmt->execute([':permiso' => $idPermiso, ':rol' => $idRol]);
        }
    }

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

    private function prepararRol(array $permisosIniciales = []): void
    {
        $this->idRol = $this->crearRol('Integ Roles Permisos');
        $this->asignarPermisosRol($this->idRol, $permisosIniciales);
    }

    private function guardar(array $permisos): array
    {
        return $this->modelo->procesarDatos([
            'accion'   => 'guardar_permisos',
            'id'       => (string)$this->idRol,
            'permisos' => $permisos,
        ]);
    }

    private function cargar(): array
    {
        $resultado = $this->modelo->CargarPermisos($this->idRol);
        $this->assertSame('CargarPermisos', $resultado['accion']);
        return array_column($resultado['datos'], null, 'id_permiso');
    }

    public function testCargarPermisosDelRol(): void
    {
        $this->prepararRol([$this->permisos[0]]);

        $matriz = $this->cargar();

        $this->assertSame(1, (int)$matriz[$this->permisos[0]]['asignado']);
        $this->assertSame(0, (int)$matriz[$this->permisos[1]]['asignado']);
        $this->assertSame('Integ Roles Permisos', $matriz[$this->permisos[0]]['nombre_rol']);
        $this->assertSame('Integ Roles Modulo', $matriz[$this->permisos[0]]['nombre_modulo']);
    }

    public function testCargarPermisosExcluyeModulosInternos(): void
    {
        $this->prepararRol();

        $modulos = array_unique(array_map('intval', array_column($this->cargar(), 'id_modulo')));

        $this->assertEmpty(array_intersect($modulos, [1, 2, 3, 4, 5, 8]));
    }

    public function testGuardarReemplazaPermisos(): void
    {
        $this->prepararRol([$this->permisos[0]]);

        $resultado = $this->guardar([$this->permisos[1] => 1, $this->permisos[2] => 1]);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame([$this->permisos[1], $this->permisos[2]], $this->permisosDelRol($this->idRol));

        $matriz = $this->cargar();
        $this->assertSame(0, (int)$matriz[$this->permisos[0]]['asignado']);
        $this->assertSame(1, (int)$matriz[$this->permisos[1]]['asignado']);
    }

    public function testGuardarIgnoraPermisosNoMarcados(): void
    {
        $this->prepararRol();

        $this->guardar([$this->permisos[0] => 1, $this->permisos[1] => 0]);

        $this->assertSame([$this->permisos[0]], $this->permisosDelRol($this->idRol));
    }

    public function testGuardarNoAfectaOtrosRoles(): void
    {
        $otroRol = $this->crearRol('Integ Roles Otro');
        $this->asignarPermisosRol($otroRol, [$this->permisos[0]]);
        $this->prepararRol([$this->permisos[0]]);

        $this->guardar([]);

        $this->assertSame([$this->permisos[0]], $this->permisosDelRol($otroRol));
    }

    public function testGuardarSinPermisos(): void
    {
        $this->prepararRol([$this->permisos[0], $this->permisos[1]]);

        $resultado = $this->guardar([]);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame([], $this->permisosDelRol($this->idRol));
    }

    public function testGuardarConPermisoInexistenteRevierte(): void
    {
        $this->prepararRol([$this->permisos[0]]);

        $resultado = $this->guardar([$this->permisos[1] => 1, 99999999 => 1]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame([$this->permisos[0]], $this->permisosDelRol($this->idRol), 'La transacción debe revertirse.');
    }

    public function testPermisosGuardadosSeReflejanEnUsuariosDelRol(): void
    {
        $this->prepararRol();
        $idUsuario = $this->crearUsuario($this->idRol);

        $this->guardar([$this->permisos[2] => 1]);

        $matriz = array_column((new ModeloPermisos())->permisosUsuarios($idUsuario)['datos'], null, 'id_permiso');
        $this->assertSame(0, (int)$matriz[$this->permisos[0]]['asignado']);
        $this->assertSame(1, (int)$matriz[$this->permisos[2]]['asignado']);
    }
}
