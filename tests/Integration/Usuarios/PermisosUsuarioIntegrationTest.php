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
#[Group('usuariosPermisos')]
class PermisosUsuarioIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloUsuarios $modelo;

    private array $roles = [];

    private string $claveInicial = 'ClaveInicial.123';

    private int $idUsuario;
    private array $permisos;

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

    private function obtenerPermisosVisibles(int $cantidad): array
    {
        $stmt = $this->pdo->query("SELECT id_permiso FROM permisos WHERE id_modulo NOT IN (1, 2, 3, 4, 5, 8) ORDER BY id_permiso ASC LIMIT {$cantidad}");
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        if (count($ids) < $cantidad) {
            $this->markTestSkipped("La BD de pruebas necesita al menos {$cantidad} permisos registrados.");
        }
        return $ids;
    }

    private function asignarPermisosRol(int $idRol, array $idsPermisos): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO permisos_rol (id_permiso, id_rol) VALUES (:permiso, :rol)");
        foreach ($idsPermisos as $idPermiso) {
            $stmt->execute([':permiso' => $idPermiso, ':rol' => $idRol]);
        }
    }

    private function crearExcepcion(int $idUsuario, int $idPermiso, int $tipo): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO excepciones (id_permiso, id_usuario, tipo) VALUES (:permiso, :usuario, :tipo)");
        $stmt->execute([':permiso' => $idPermiso, ':usuario' => $idUsuario, ':tipo' => $tipo]);
    }

    private function obtenerExcepciones(int $idUsuario): array
    {
        $stmt = $this->pdo->prepare("SELECT id_permiso, tipo FROM excepciones WHERE id_usuario = :id ORDER BY id_permiso");
        $stmt->execute([':id' => $idUsuario]);
        $excepciones = [];
        foreach ($stmt->fetchAll() as $fila) {
            $excepciones[(int)$fila['id_permiso']] = (int)$fila['tipo'];
        }
        return $excepciones;
    }

    private function preparar(): void
    {
        $this->permisos = $this->obtenerPermisosVisibles(3);
        $this->asignarPermisosRol($this->roles['nivel3'], [$this->permisos[0], $this->permisos[1]]);
        $this->idUsuario = $this->crearUsuario();
    }

    private function guardar(array $seleccion): array
    {
        $permisos = [];
        foreach ($seleccion as $idPermiso) {
            $permisos[$idPermiso] = 1;
        }
        return $this->modelo->procesarDatos([
            'accion'   => 'guardar_permisos_usuario',
            'id'       => $this->idUsuario,
            'permisos' => $permisos,
        ]);
    }

    private function cargar(): array
    {
        $resultado = $this->modelo->procesarDatos(['accion' => 'CargarPermisosUsuario', 'id' => $this->idUsuario]);
        $this->assertSame('CargarPermisosUsuario', $resultado['accion']);
        return array_column($resultado['datos'], null, 'id_permiso');
    }

    public function testCargarPermisosHeredadosDelRol(): void
    {
        $this->preparar();

        $permisos = $this->cargar();

        $this->assertSame(1, (int)$permisos[$this->permisos[0]]['asignado']);
        $this->assertSame(1, (int)$permisos[$this->permisos[1]]['asignado']);
        $this->assertSame(0, (int)$permisos[$this->permisos[2]]['asignado']);
        $this->assertSame(2, (int)$permisos[$this->permisos[0]]['excepcion'], '2 = sin excepción.');
        $this->assertSame($this->roles['nivel3'], (int)$permisos[$this->permisos[0]]['id_rol']);
    }

    public function testCargarPermisosAplicaExcepciones(): void
    {
        $this->preparar();
        $this->crearExcepcion($this->idUsuario, $this->permisos[0], 0);
        $this->crearExcepcion($this->idUsuario, $this->permisos[2], 1);

        $permisos = $this->cargar();

        $this->assertSame(0, (int)$permisos[$this->permisos[0]]['asignado']);
        $this->assertSame(0, (int)$permisos[$this->permisos[0]]['excepcion']);
        $this->assertSame(1, (int)$permisos[$this->permisos[1]]['asignado']);
        $this->assertSame(1, (int)$permisos[$this->permisos[2]]['asignado']);
        $this->assertSame(1, (int)$permisos[$this->permisos[2]]['excepcion']);
    }

    public function testCargarPermisosExcluyeModulosInternos(): void
    {
        $this->preparar();

        $modulos = array_unique(array_map('intval', array_column($this->cargar(), 'id_modulo')));

        $this->assertEmpty(array_intersect($modulos, [1, 2, 3, 4, 5, 8]));
    }

    public function testGuardarSeleccionDistintaCreaExcepciones(): void
    {
        $this->preparar();

        $resultado = $this->guardar([$this->permisos[1], $this->permisos[2]]);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame(
            [$this->permisos[0] => 0, $this->permisos[2] => 1],
            $this->obtenerExcepciones($this->idUsuario)
        );

        $permisos = $this->cargar();
        $this->assertSame(0, (int)$permisos[$this->permisos[0]]['asignado']);
        $this->assertSame(1, (int)$permisos[$this->permisos[1]]['asignado']);
        $this->assertSame(1, (int)$permisos[$this->permisos[2]]['asignado']);
    }

    public function testGuardarSeleccionIgualAlRolNoCreaExcepciones(): void
    {
        $this->preparar();

        $resultado = $this->guardar([$this->permisos[0], $this->permisos[1]]);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame([], $this->obtenerExcepciones($this->idUsuario));
    }

    public function testGuardarReemplazaExcepcionesAnteriores(): void
    {
        $this->preparar();
        $this->crearExcepcion($this->idUsuario, $this->permisos[2], 1);

        $this->guardar([$this->permisos[0]]);

        $this->assertSame([$this->permisos[1] => 0], $this->obtenerExcepciones($this->idUsuario));
    }
}
