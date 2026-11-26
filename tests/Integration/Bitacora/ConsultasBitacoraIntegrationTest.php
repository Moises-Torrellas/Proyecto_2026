<?php

declare(strict_types=1);

namespace tests\Integration\Bitacora;

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
 * RF-BIT-03 / RF-BIT-04 (integración): Consultar la bitácora de la BD de seguridad real a
 * través de vista_consulta_bitacora, del registro más reciente al más antiguo, con
 * búsqueda por texto, filtros por módulo, usuario y rango de fechas, paginación y modo
 * reporte; y cargar los catálogos de usuarios y módulos para los filtros.
 *
 * Escenarios:
 *  E1. Consulta sin filtros (orden y datos de la vista).
 *  E2. Búsqueda por texto (usuario, cédula, acción o módulo).
 *  E3. Filtro por módulo y por usuario.
 *  E4. Filtro por rango de fechas.
 *  E5. Paginación y reporte sin límite.
 *  E6. Catálogos de usuarios y módulos.
 */
#[Group('integ')]
#[Group('bitacora')]
#[Group('bitacoraConsultar')]
class ConsultasBitacoraIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloBitacora $modelo;

    private int $moduloA;
    private int $moduloB;
    private int $usuarioA;
    private int $usuarioB;

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
     * tablas. Solo se borra lo que crean: los módulos "Integ Bitacora ...", el rol
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

    /**
     * Dos módulos y dos usuarios de prueba, sin registros de bitácora.
     */
    private function crearDatosBase(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO modulos (nombre_modulo, descripcion, icono, estatus) VALUES (:nombre, 'Modulo de prueba', 'circle-minus', 1)");
        $stmt->execute([':nombre' => 'Integ Bitacora Alfa']);
        $this->moduloA = (int)$this->pdo->lastInsertId();
        $stmt->execute([':nombre' => 'Integ Bitacora Beta']);
        $this->moduloB = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO roles (nombre_rol, descripcion, nivel_rol, estatus) VALUES ('Integ Bitacora Rol', 'Rol de prueba', 3, 1)");
        $idRol = (int)$this->pdo->lastInsertId();

        $this->usuarioA = $this->crearUsuario('99990711', 'Zzinteg Ana', $idRol);
        $this->usuarioB = $this->crearUsuario('99990712', 'Zzinteg Beto', $idRol);

        $this->pdo->exec("DELETE FROM bitacora WHERE idUsuario IN ({$this->usuarioA}, {$this->usuarioB})");
    }

    private function crearUsuario(string $cedula, string $nombre, int $idRol): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO usuarios
            (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos)
            VALUES (:cedula, :nombre, 'Prueba', 'default.png', :telefono, :pass, :correo, :rol, 1, 1, 0)");
        $stmt->execute([
            ':cedula'   => $cedula,
            ':nombre'   => $nombre,
            ':telefono' => '0414-' . substr($cedula, 1),
            ':pass'     => password_hash('Clave.Prueba123', PASSWORD_BCRYPT),
            ':correo'   => "u{$cedula}@integ.test",
            ':rol'      => $idRol,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Inserta un registro de bitácora directamente en la BD (para poder fijar la fecha) y devuelve su id.
     */
    private function crearRegistro(int $modulo, int $usuario, string $accion, string $fechaHora = 'now'): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO bitacora (id_modulo, acciones, datos_previos, datos_nuevos, entorno, fecha_hora, idUsuario)
            VALUES (:modulo, :accion, '', '', 'PHPUnit', :fecha, :usuario)");
        $stmt->execute([
            ':modulo'  => $modulo,
            ':accion'  => $accion,
            ':fecha'   => date('Y-m-d H:i:s', strtotime($fechaHora)),
            ':usuario' => $usuario,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** @return string[] acciones devueltas que pertenecen a las pruebas */
    private function acciones(array $resultado): array
    {
        $this->assertSame('consultar', $resultado['accion']);
        return array_values(array_filter(
            array_column($resultado['datos'], 'acciones'),
            fn($a) => str_starts_with($a, 'Integ Bitacora')
        ));
    }

    // Caso 1 (E1): Los registros salen del más reciente al más antiguo con los datos de la vista
    public function testConsultaOrdenadaConDatosDeLaVista(): void
    {
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora primero');
        $this->crearRegistro($this->moduloB, $this->usuarioB, 'Integ Bitacora segundo');

        $resultado = $this->modelo->Consultar(['filtro' => 'Integ Bitacora']);

        $this->assertSame(['Integ Bitacora segundo', 'Integ Bitacora primero'], $this->acciones($resultado));

        $fila = $resultado['datos'][0];
        $this->assertSame('Zzinteg Beto', $fila['nombreUsuario']);
        $this->assertSame('99990712', $fila['cedulaUsuario']);
        $this->assertSame('Integ Bitacora Beta', $fila['nombre_modulo']);
        $this->assertSame(date('Y-m-d'), $fila['fecha']);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $fila['hora']);
    }

    // Caso 2 (E1): Sin parámetros se devuelven como máximo 100 registros
    public function testConsultaSinFiltrosLimitaA100(): void
    {
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora ultimo');

        $resultado = $this->modelo->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertLessThanOrEqual(100, count($resultado['datos']));
        $this->assertSame('Integ Bitacora ultimo', $resultado['datos'][0]['acciones'], 'El registro más reciente va primero.');
    }

    // Caso 3 (E2): La búsqueda encuentra por acción, nombre de usuario, cédula y módulo
    public function testBusquedaPorTexto(): void
    {
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora registro de ana');
        $this->crearRegistro($this->moduloB, $this->usuarioB, 'Integ Bitacora registro de beto');

        $this->assertSame(['Integ Bitacora registro de ana'], $this->acciones($this->modelo->Consultar(['filtro' => 'registro de ana'])));
        $this->assertSame(['Integ Bitacora registro de beto'], $this->acciones($this->modelo->Consultar(['filtro' => 'Zzinteg Beto'])));
        $this->assertSame(['Integ Bitacora registro de ana'], $this->acciones($this->modelo->Consultar(['filtro' => '99990711'])));
        $this->assertSame(['Integ Bitacora registro de beto'], $this->acciones($this->modelo->Consultar(['filtro' => 'Integ Bitacora Beta'])));
    }

    // Caso 4 (E2): Una búsqueda sin coincidencias devuelve una lista vacía
    public function testBusquedaSinCoincidencias(): void
    {
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora algo');

        $resultado = $this->modelo->Consultar(['filtro' => 'Integ Bitacora texto que no existe']);

        $this->assertSame(['accion' => 'consultar', 'datos' => []], $resultado);
    }

    // Caso 5 (E2): Un texto con comillas o comodines no rompe la consulta
    public function testBusquedaConCaracteresEspeciales(): void
    {
        $resultado = $this->modelo->Consultar(['filtro' => "' OR 1=1 -- %_"]);

        $this->assertSame(['accion' => 'consultar', 'datos' => []], $resultado);
    }

    // Caso 6 (E3): Filtrar por módulo devuelve solo los registros de ese módulo
    public function testFiltroPorModulo(): void
    {
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora en alfa');
        $this->crearRegistro($this->moduloB, $this->usuarioA, 'Integ Bitacora en beta');

        $resultado = $this->modelo->Consultar(['id_modulo' => $this->moduloB, 'accion' => 'reporte']);

        $this->assertSame(['Integ Bitacora en beta'], $this->acciones($resultado));
        $this->assertSame(['Integ Bitacora Beta'], array_unique(array_column($resultado['datos'], 'nombre_modulo')));
    }

    // Caso 7 (E3): Filtrar por usuario devuelve solo los registros de ese usuario
    public function testFiltroPorUsuario(): void
    {
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora de ana');
        $this->crearRegistro($this->moduloA, $this->usuarioB, 'Integ Bitacora de beto');

        $resultado = $this->modelo->Consultar(['idUsuario' => $this->usuarioA, 'accion' => 'reporte']);

        $this->assertSame(['Integ Bitacora de ana'], $this->acciones($resultado));
        $this->assertSame(['99990711'], array_unique(array_column($resultado['datos'], 'cedulaUsuario')));
    }

    // Caso 8 (E3): Los filtros por módulo, usuario y texto se combinan
    public function testFiltrosCombinados(): void
    {
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora ana alfa');
        $this->crearRegistro($this->moduloB, $this->usuarioA, 'Integ Bitacora ana beta');
        $this->crearRegistro($this->moduloB, $this->usuarioB, 'Integ Bitacora beto beta');

        $resultado = $this->modelo->Consultar([
            'filtro'    => 'Integ Bitacora',
            'id_modulo' => $this->moduloB,
            'idUsuario' => $this->usuarioA,
        ]);

        $this->assertSame(['Integ Bitacora ana beta'], $this->acciones($resultado));
    }

    // Caso 9 (E4): El rango de fechas incluye ambos extremos y excluye lo que queda fuera
    public function testFiltroPorRangoDeFechas(): void
    {
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora hace diez dias', '-10 days');
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora hace cinco dias', '-5 days');
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora hace tres dias', '-3 days');
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora hoy');

        $resultado = $this->modelo->Consultar([
            'idUsuario'    => $this->usuarioA,
            'fecha_inicio' => date('Y-m-d', strtotime('-5 days')),
            'fecha_fin'    => date('Y-m-d', strtotime('-3 days')),
        ]);

        $this->assertSame(['Integ Bitacora hace tres dias', 'Integ Bitacora hace cinco dias'], $this->acciones($resultado));
    }

    // Caso 10 (E4): Con una sola fecha no se aplica el filtro de fechas
    public function testUnaSolaFechaNoFiltra(): void
    {
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora antiguo', '-30 days');
        $this->crearRegistro($this->moduloA, $this->usuarioA, 'Integ Bitacora reciente');

        $resultado = $this->modelo->Consultar([
            'idUsuario'    => $this->usuarioA,
            'fecha_inicio' => date('Y-m-d'),
        ]);

        $this->assertSame(['Integ Bitacora reciente', 'Integ Bitacora antiguo'], $this->acciones($resultado));
    }

    // Caso 11 (E5): La paginación respeta límite y desplazamiento
    public function testPaginacion(): void
    {
        foreach ([1, 2, 3, 4, 5] as $n) {
            $this->crearRegistro($this->moduloA, $this->usuarioA, "Integ Bitacora pagina {$n}");
        }

        $pagina1 = $this->modelo->Consultar(['idUsuario' => $this->usuarioA, 'limit' => 2, 'offset' => 0]);
        $pagina2 = $this->modelo->Consultar(['idUsuario' => $this->usuarioA, 'limit' => 2, 'offset' => 2]);
        $pagina3 = $this->modelo->Consultar(['idUsuario' => $this->usuarioA, 'limit' => 2, 'offset' => 4]);

        $this->assertSame(['Integ Bitacora pagina 5', 'Integ Bitacora pagina 4'], $this->acciones($pagina1));
        $this->assertSame(['Integ Bitacora pagina 3', 'Integ Bitacora pagina 2'], $this->acciones($pagina2));
        $this->assertSame(['Integ Bitacora pagina 1'], $this->acciones($pagina3));
    }

    // Caso 12 (E5): En modo reporte se devuelven todos los registros sin importar el límite
    public function testReporteSinLimite(): void
    {
        foreach ([1, 2, 3, 4, 5] as $n) {
            $this->crearRegistro($this->moduloA, $this->usuarioA, "Integ Bitacora reporte {$n}");
        }

        $resultado = $this->modelo->Consultar(['idUsuario' => $this->usuarioA, 'accion' => 'reporte', 'limit' => 2]);

        $this->assertCount(5, $this->acciones($resultado));
    }

    // Caso 13 (E5): Registrar un usuario deja automáticamente su registro en la bitácora (disparador)
    public function testRegistroDeUsuarioApareceEnBitacora(): void
    {
        $idRol = (int)$this->pdo->query("SELECT id_rol FROM roles WHERE nombre_rol = 'Integ Bitacora Rol'")->fetchColumn();
        $this->crearUsuario('99990713', 'Zzinteg Carla', $idRol);

        $resultado = $this->modelo->Consultar(['filtro' => '99990713', 'accion' => 'reporte']);

        $this->assertNotEmpty($resultado['datos'], 'El disparador de usuarios debe dejar un registro en la bitácora.');
    }

    // Caso 14 (E6): El catálogo de usuarios incluye a los usuarios de prueba, ordenado y sin contraseñas
    public function testCatalogoUsuarios(): void
    {
        $resultado = $this->modelo->consultarUsuarios();

        $this->assertSame('consultar_usuarios', $resultado['accion']);

        $nombres = array_column($resultado['datos'], 'nombreUsuario');
        $ordenados = $nombres;
        usort($ordenados, fn($a, $b) => strcasecmp($a, $b));
        $this->assertSame(array_map('mb_strtolower', $ordenados), array_map('mb_strtolower', $nombres));

        $porId = array_column($resultado['datos'], null, 'idUsuario');
        $this->assertSame('99990711', $porId[$this->usuarioA]['cedulaUsuario']);
        $this->assertSame(['idUsuario', 'cedulaUsuario', 'nombreUsuario', 'apellidoUsuario'], array_keys($porId[$this->usuarioA]));
    }

    // Caso 15 (E6): El catálogo de módulos incluye a los módulos de prueba ordenados por nombre
    public function testCatalogoModulos(): void
    {
        $resultado = $this->modelo->consultarModulos();

        $this->assertSame('consultar_modulos', $resultado['accion']);

        $porId = array_column($resultado['datos'], 'nombre_modulo', 'id_modulo');
        $this->assertSame('Integ Bitacora Alfa', $porId[$this->moduloA]);
        $this->assertSame('Integ Bitacora Beta', $porId[$this->moduloB]);

        $nombres = array_column($resultado['datos'], 'nombre_modulo');
        $posA = array_search('Integ Bitacora Alfa', $nombres, true);
        $posB = array_search('Integ Bitacora Beta', $nombres, true);
        $this->assertSame($posA + 1, $posB, 'Los módulos se listan en orden alfabético.');
    }
}
