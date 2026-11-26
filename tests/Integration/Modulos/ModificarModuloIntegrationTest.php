<?php

declare(strict_types=1);

namespace tests\Integration\Modulos;

use App\modelo\Conexion;
use App\modelo\ModeloBitacora;
use App\modelo\ModeloModulos;
use Exception;
use PDO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

/**
 * RF-MOD-03 (integración): Modificar el nombre y la descripción de un módulo activo en la
 * BD de seguridad real. El nombre debe seguir siendo único entre los módulos activos y
 * los textos se guardan en formato título.
 *
 * Escenarios:
 *  E1. Modificación válida de nombre y descripción.
 *  E2. Se conserva el mismo nombre del módulo.
 *  E3. Módulo inexistente o inactivo.
 *  E4. Nombre que ya usa otro módulo.
 *  E5. Datos con formato inválido.
 */
#[Group('integ')]
#[Group('modulos')]
#[Group('modulosModificar')]
class ModificarModuloIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloModulos $modelo;

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

        $this->modelo = new ModeloModulos();
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
     * tablas. Los módulos del sistema nunca se modifican: las pruebas crean sus propios
     * módulos (nombre "Integ Modulos ..."), un rol "Integ Modulos Rol" y un usuario con
     * cédula "9999xxxx", y los borran junto con sus registros de bitácora.
     */
    private function limpiarDatosPrueba(): void
    {
        if (!$this->pdo) {
            return;
        }

        $usuarios = "SELECT idUsuario FROM (SELECT idUsuario FROM usuarios WHERE cedulaUsuario LIKE '9999%') AS t";
        $modulos = "SELECT id_modulo FROM (SELECT id_modulo FROM modulos WHERE nombre_modulo LIKE 'Integ Modulos%') AS t";

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("DELETE FROM bitacora WHERE idUsuario IN ($usuarios) OR id_modulo IN ($modulos);");
        $this->pdo->exec("DELETE FROM usuarios WHERE cedulaUsuario LIKE '9999%';");
        $this->pdo->exec("DELETE FROM roles WHERE nombre_rol LIKE 'Integ Modulos%';");
        $this->pdo->exec("DELETE FROM modulos WHERE nombre_modulo LIKE 'Integ Modulos%';");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    /**
     * Inserta un módulo directamente en la BD (sin pasar por el modelo) y devuelve su id.
     */
    private function crearModulo(string $nombre, int $estatus = 1, string $descripcion = 'Modulo De Prueba'): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO modulos (nombre_modulo, descripcion, icono, estatus) VALUES (:nombre, :descripcion, 'circle-minus', :estatus)");
        $stmt->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':estatus' => $estatus]);
        return (int)$this->pdo->lastInsertId();
    }

    private function obtenerModulo(int $id): array|false
    {
        $stmt = $this->pdo->prepare("SELECT * FROM modulos WHERE id_modulo = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function modificar(array $datos): array
    {
        return $this->modelo->ProcesarDatos(array_merge(['accion' => 'modificar'], $datos));
    }

    // Caso 1 (E1): El nombre y la descripción se guardan en formato título sin espacios sobrantes
    public function testModificacionExitosa(): void
    {
        $id = $this->crearModulo('Integ Modulos Alfa');

        $resultado = $this->modificar(['id' => (string)$id, 'nombre' => '  integ modulos gamma ', 'descripcion' => 'gestiona las PRUEBAS']);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $modulo = $this->obtenerModulo($id);
        $this->assertSame('Integ Modulos Gamma', $modulo['nombre_modulo']);
        $this->assertSame('Gestiona Las Pruebas', $modulo['descripcion']);
    }

    // Caso 2 (E1): La modificación no cambia el icono ni el estatus del módulo
    public function testModificacionConservaIconoYEstatus(): void
    {
        $id = $this->crearModulo('Integ Modulos Alfa');
        $this->pdo->exec("UPDATE modulos SET icono = 'users' WHERE id_modulo = {$id}");

        $this->modificar(['id' => (string)$id, 'nombre' => 'Integ Modulos Gamma', 'descripcion' => 'Otra descripcion']);

        $modulo = $this->obtenerModulo($id);
        $this->assertSame('users', $modulo['icono']);
        $this->assertSame(1, (int)$modulo['estatus']);
    }

    // Caso 3 (E1): El nuevo nombre se ve en el listado del módulo y en la bitácora
    public function testNuevoNombreSeReflejaEnConsultas(): void
    {
        $id = $this->crearModulo('Integ Modulos Alfa');

        $this->pdo->exec("INSERT INTO roles (nombre_rol, descripcion, nivel_rol, estatus) VALUES ('Integ Modulos Rol', 'Rol de prueba', 3, 1)");
        $idRol = (int)$this->pdo->lastInsertId();
        $this->pdo->prepare("INSERT INTO usuarios
            (cedulaUsuario, nombreUsuario, apellidoUsuario, foto, telefonoUsuario, pass_hash, correo, id_rol, estatus, bloqueo, intentos_fallidos)
            VALUES ('99990801', 'Modulos', 'Prueba', 'default.png', '0414-9990801', 'x', 'modulos.prueba@integ.test', :rol, 1, 1, 0)")
            ->execute([':rol' => $idRol]);
        $idUsuario = (int)$this->pdo->lastInsertId();
        $this->pdo->exec("INSERT INTO bitacora (id_modulo, acciones, datos_previos, datos_nuevos, entorno, idUsuario)
            VALUES ({$id}, 'Accion de prueba', '', '', 'PHPUnit', {$idUsuario})");

        $this->modificar(['id' => (string)$id, 'nombre' => 'Integ Modulos Gamma', 'descripcion' => 'Modulo De Prueba']);

        $listado = $this->modelo->Consultar(['filtro' => 'Integ Modulos'])['datos'];
        $this->assertSame(['Integ Modulos Gamma'], array_column($listado, 'nombre_modulo'));

        $bitacora = (new ModeloBitacora())->Consultar(['idUsuario' => $idUsuario])['datos'];
        $this->assertSame('Integ Modulos Gamma', $bitacora[0]['nombre_modulo']);
    }

    // Caso 4 (E2): Conservar el propio nombre y cambiar solo la descripción es válido
    public function testConservarPropioNombre(): void
    {
        $id = $this->crearModulo('Integ Modulos Alfa');

        $resultado = $this->modificar(['id' => (string)$id, 'nombre' => 'integ modulos alfa', 'descripcion' => 'Nueva descripcion']);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame('Nueva Descripcion', $this->obtenerModulo($id)['descripcion']);
    }

    // Caso 5 (E3): Un id que no existe devuelve INVALID_ID
    public function testModuloInexistente(): void
    {
        $resultado = $this->modificar(['id' => '99999999', 'nombre' => 'Integ Modulos Gamma', 'descripcion' => 'Descripcion']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM modulos WHERE nombre_modulo = 'Integ Modulos Gamma'")->fetchColumn());
    }

    // Caso 6 (E3): Un módulo inactivo no se puede modificar
    public function testModuloInactivo(): void
    {
        $id = $this->crearModulo('Integ Modulos Alfa', 0);

        $resultado = $this->modificar(['id' => (string)$id, 'nombre' => 'Integ Modulos Gamma', 'descripcion' => 'Descripcion']);

        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertSame('Integ Modulos Alfa', $this->obtenerModulo($id)['nombre_modulo']);
    }

    // Caso 7 (E3): Sin id no se modifica ningún módulo
    public function testSinId(): void
    {
        $resultado = $this->modificar(['nombre' => 'Integ Modulos Gamma', 'descripcion' => 'Descripcion']);

        $this->assertSame(INVALID_ID, $resultado['codigo']);
    }

    // Caso 8 (E4): El nombre de otro módulo activo se rechaza y no se cambia nada
    public function testNombreDeOtroModulo(): void
    {
        $this->crearModulo('Integ Modulos Alfa');
        $id = $this->crearModulo('Integ Modulos Beta', 1, 'Descripcion Original');

        $resultado = $this->modificar(['id' => (string)$id, 'nombre' => 'INTEG MODULOS ALFA', 'descripcion' => 'Cambiada']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);

        $modulo = $this->obtenerModulo($id);
        $this->assertSame('Integ Modulos Beta', $modulo['nombre_modulo']);
        $this->assertSame('Descripcion Original', $modulo['descripcion']);
    }

    // Caso 9 (E4): El nombre de un módulo del sistema también se rechaza
    public function testNombreDeModuloDelSistema(): void
    {
        $id = $this->crearModulo('Integ Modulos Alfa');

        $resultado = $this->modificar(['id' => (string)$id, 'nombre' => 'Usuarios', 'descripcion' => 'Descripcion']);

        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
        $this->assertSame('Integ Modulos Alfa', $this->obtenerModulo($id)['nombre_modulo']);
    }

    // Caso 10 (E4): El nombre de un módulo inactivo sí puede reutilizarse
    public function testNombreDeModuloInactivo(): void
    {
        $this->crearModulo('Integ Modulos Alfa', 0);
        $id = $this->crearModulo('Integ Modulos Beta');

        $resultado = $this->modificar(['id' => (string)$id, 'nombre' => 'Integ Modulos Alfa', 'descripcion' => 'Descripcion']);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame('Integ Modulos Alfa', $this->obtenerModulo($id)['nombre_modulo']);
    }

    // Caso 11 (E5): Un nombre con números se rechaza antes de llegar a la BD
    public function testNombreInvalido(): void
    {
        $id = $this->crearModulo('Integ Modulos Alfa');

        try {
            $this->modificar(['id' => (string)$id, 'nombre' => 'Integ Modulos 2', 'descripcion' => 'Descripcion']);
            $this->fail('Se esperaba una excepción por nombre inválido.');
        } catch (Exception $e) {
            $this->assertSame('Nombre inválido.', $e->getMessage());
        }

        $this->assertSame('Integ Modulos Alfa', $this->obtenerModulo($id)['nombre_modulo']);
    }

    // Caso 12 (E5): Una descripción con caracteres especiales se rechaza
    public function testDescripcionInvalida(): void
    {
        $id = $this->crearModulo('Integ Modulos Alfa');

        try {
            $this->modificar(['id' => (string)$id, 'nombre' => 'Integ Modulos Alfa', 'descripcion' => "<script>alert('x')</script>"]);
            $this->fail('Se esperaba una excepción por descripción inválida.');
        } catch (Exception $e) {
            $this->assertSame('Descripción inválida.', $e->getMessage());
        }

        $this->assertSame('Modulo De Prueba', $this->obtenerModulo($id)['descripcion']);
    }

    // Caso 13 (E5): Un id con intento de inyección SQL se rechaza
    public function testIdConInyeccion(): void
    {
        $id = $this->crearModulo('Integ Modulos Alfa');

        try {
            $this->modificar(['id' => "{$id} OR 1=1", 'nombre' => 'Integ Modulos Gamma', 'descripcion' => 'Descripcion']);
            $this->fail('Se esperaba una excepción por id inválido.');
        } catch (Exception $e) {
            $this->assertSame('Id inválido.', $e->getMessage());
        }

        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM modulos WHERE nombre_modulo = 'Integ Modulos Gamma'")->fetchColumn());
    }
}
