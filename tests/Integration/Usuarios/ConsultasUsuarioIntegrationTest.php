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
#[Group('usuariosConsultas')]
class ConsultasUsuarioIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloUsuarios $modelo;

    private array $roles = [];

    private string $claveInicial = 'ClaveInicial.123';

    private array $ids = [];

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

    private function cargarUsuarios(): void
    {
        $this->ids['ana'] = $this->crearUsuario([
            'cedula' => '99990031', 'nombre' => 'Ana', 'apellido' => 'Torres',
            'telefono' => '0414-9990031', 'correo' => 'ana.torres@integ.test', 'rol' => $this->roles['nivel3'],
        ]);
        $this->ids['luis'] = $this->crearUsuario([
            'cedula' => '99990032', 'nombre' => 'Luis', 'apellido' => 'Torres',
            'telefono' => '0414-9990032', 'correo' => 'luis.torres@integ.test', 'rol' => $this->roles['nivel3b'],
        ]);
        $this->ids['eliminado'] = $this->crearUsuario([
            'cedula' => '99990033', 'nombre' => 'Eliminado', 'apellido' => 'Torres',
            'telefono' => '0414-9990033', 'correo' => 'eliminado.torres@integ.test', 'estatus' => 0,
        ]);
    }

    private function cedulasDePrueba(array $resultado): array
    {
        $cedulas = array_column($resultado['datos'], 'cedulaUsuario');
        $cedulas = array_values(array_filter($cedulas, fn($c) => str_starts_with($c, '9999')));
        sort($cedulas);
        return $cedulas;
    }

    private function reporte(array $filtros): array
    {
        return $this->modelo->procesarDatos(array_merge(['accion' => 'reporte'], $filtros));
    }

    public function testListadoGeneralSoloActivos(): void
    {
        $this->cargarUsuarios();

        $resultado = $this->modelo->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame(['99990031', '99990032'], $this->cedulasDePrueba($resultado));

        $ana = array_values(array_filter($resultado['datos'], fn($u) => $u['cedulaUsuario'] === '99990031'))[0];
        $this->assertSame('Integ Usuarios nivel3', $ana['nombre_rol']);
        $this->assertArrayNotHasKey('pass_hash', $ana, 'La vista no debe exponer la contraseña.');
    }

    public function testListadoOrdenadoPorId(): void
    {
        $this->cargarUsuarios();

        $ids = array_map('intval', array_column($this->modelo->Consultar()['datos'], 'idUsuario'));
        $ordenados = $ids;
        sort($ordenados);

        $this->assertSame($ordenados, $ids);
    }

    public function testBusquedaGeneralPorApellido(): void
    {
        $this->cargarUsuarios();

        $resultado = $this->modelo->Consultar(['filtro' => 'Torres']);

        $this->assertSame(['99990031', '99990032'], $this->cedulasDePrueba($resultado));
    }

    public function testBusquedaGeneralPorCorreo(): void
    {
        $this->cargarUsuarios();

        $resultado = $this->modelo->Consultar(['filtro' => 'luis.torres@']);

        $this->assertSame(['99990032'], $this->cedulasDePrueba($resultado));
    }

    public function testBusquedaGeneralPorRol(): void
    {
        $this->cargarUsuarios();

        $resultado = $this->modelo->Consultar(['filtro' => 'Integ Usuarios nivel3b']);

        $this->assertSame(['99990032'], $this->cedulasDePrueba($resultado));
    }

    public function testBusquedaSinCoincidencias(): void
    {
        $this->cargarUsuarios();

        $resultado = $this->modelo->Consultar(['filtro' => 'zzz-no-existe-zzz']);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    public function testReportePorCedula(): void
    {
        $this->cargarUsuarios();

        $resultado = $this->reporte(['cedula' => '9999003']);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame(['99990031', '99990032'], $this->cedulasDePrueba($resultado));
    }

    public function testReportePorNombreYApellido(): void
    {
        $this->cargarUsuarios();

        $resultado = $this->reporte(['nombre' => 'ana', 'apellido' => 'torres']);

        $this->assertSame(['99990031'], $this->cedulasDePrueba($resultado));
    }

    public function testReportePorRol(): void
    {
        $this->cargarUsuarios();

        $resultado = $this->reporte(['roles_id' => (string)$this->roles['nivel3b']]);

        $this->assertSame(['99990032'], array_column($resultado['datos'], 'cedulaUsuario'));
    }

    public function testReporteRolTodosNoFiltra(): void
    {
        $this->cargarUsuarios();

        $resultado = $this->reporte(['cedula' => '9999003', 'roles_id' => '0']);

        $this->assertSame(['99990031', '99990032'], $this->cedulasDePrueba($resultado));
    }

    public function testBuscarPorId(): void
    {
        $this->cargarUsuarios();

        $resultado = $this->modelo->procesarDatos(['accion' => 'buscar', 'id' => $this->ids['ana']]);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertSame('99990031', $resultado['datos'][0]['cedulaUsuario']);
        $this->assertSame(3, (int)$resultado['datos'][0]['nivel_rol']);
    }

    public function testBuscarUsuarioEliminado(): void
    {
        $this->cargarUsuarios();

        $resultado = $this->modelo->procesarDatos(['accion' => 'buscar', 'id' => $this->ids['eliminado']]);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    public function testRegistrarUltimoIngreso(): void
    {
        $id = $this->crearUsuario();
        $this->assertNull($this->obtenerUsuario($id)['ultimo_ingreso']);

        $resultado = $this->modelo->registrarUltimoIngreso($id);

        $this->assertSame('exito', $resultado['accion']);
        $ultimoIngreso = $this->obtenerUsuario($id)['ultimo_ingreso'];
        $this->assertNotNull($ultimoIngreso);
        $ahoraBD = $this->pdo->query("SELECT NOW()")->fetchColumn();
        $this->assertLessThanOrEqual(5, abs(strtotime($ahoraBD) - strtotime($ultimoIngreso)));
    }
}
