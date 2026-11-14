<?php

declare(strict_types=1);

namespace tests\Integration\ArticulosInventario;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloArticulosInventario;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("articulosinventario")]
class EliminarArticulosInventarioIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloArticulosInventario $modelo;

    protected function setUp(): void
    {
        $dsn = 'mysql:host=' . (_DB_HOST_ ?: '127.0.0.1') . ';dbname=' . (_DB_NAME_ ?: 'cannibalsbd2') . ';charset=utf8';
        $this->pdo = new PDO($dsn, _DB_USER_ ?: 'root', _DB_PASS_ ?: '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $this->pdo);

        $this->modelo = new ModeloArticulosInventario();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE devoluciones;");
        $this->pdo->exec("TRUNCATE TABLE asignaciones;");
        $this->pdo->exec("TRUNCATE TABLE articulos_inventario;");
        $this->pdo->exec("TRUNCATE TABLE catalogo;");
        $this->pdo->exec("TRUNCATE TABLE categoria_catalogo;");
        $this->pdo->exec("TRUNCATE TABLE estado_fisico;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        $this->sembrarDatosBase();
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function sembrarDatosBase(): void
    {
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES (1, 'Protecciones', 'Categorico')");
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, talla) VALUES 
                          (1, 'Casco Bauer', 2, 1, 'M'),
                          (2, 'Pads CCM', 1, 1, 'L')");
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
                          (1, 'Excelente', 1),
                          (2, 'Regular', 2)");
    }

    /**
     * ESC-INT-AI-03 / CP-INT-AI-10: Retiro seguro exitoso de un artículo disponible (estatus 1 a 3).
     * Invoca el procedimiento almacenado transaccional `RetirarArticuloSeguro` y actualiza a estatus = 3 (Retirado).
     */
    public function testEliminarArticuloDisponibleExitoso(): void
    {
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_catalogo, id_estado, codigo_club, estatus) 
                          VALUES (10, 1, 1, 'CL-0010', 1)");

        $datos = [
            'accion'          => 'eliminar',
            'codigo_articulo' => 10
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('El artículo ha sido retirado del inventario.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 10");
        $this->assertEquals(3, (int)$stmt->fetchColumn(), 'El artículo debió pasar a estatus 3 (Retirado).');
    }

    /**
     * ESC-INT-AI-03 / CP-INT-AI-11: Bloqueo de retiro seguro cuando el artículo está asignado/en uso (estatus = 2).
     */
    public function testRechazoEliminarArticuloEnUso(): void
    {
        // Artículo en uso (estatus = 2)
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_catalogo, id_estado, codigo_club, estatus) 
                          VALUES (12, 1, 1, 'CL-0012', 2)");

        $datos = [
            'accion'          => 'eliminar',
            'codigo_articulo' => 12
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El artículo no puede ser retirado porque está en uso o ya fue dado de baja.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 12");
        $this->assertEquals(2, (int)$stmt->fetchColumn(), 'El estatus del artículo debió permanecer en 2.');
    }

    /**
     * ESC-INT-AI-03 / CP-INT-AI-12: Bloqueo de retiro cuando el artículo ya se encuentra retirado (estatus = 3).
     */
    public function testRechazoEliminarArticuloYaRetirado(): void
    {
        // Artículo ya retirado (estatus = 3)
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_catalogo, id_estado, codigo_club, estatus) 
                          VALUES (14, 1, 1, 'CL-0014', 3)");

        $datos = [
            'accion'          => 'eliminar',
            'codigo_articulo' => 14
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El artículo no puede ser retirado porque está en uso o ya fue dado de baja.', $resultado['mensaje']);
    }

    /**
     * ESC-INT-AI-03 / CP-INT-AI-13: Manejo defensivo ante intento de eliminar un artículo con ID inexistente.
     */
    public function testErrorEliminarIdInexistente(): void
    {
        $datos = [
            'accion'          => 'eliminar',
            'codigo_articulo' => 99999
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DB_CONNECTION, $resultado['codigo']);
    }

    /**
     * ESC-INT-AI-03 / CP-INT-AI-14: Reincorporación exitosa de un artículo en condición de retirado (estatus 3 a 1).
     */
    public function testReincorporarArticuloRetiradoExitoso(): void
    {
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_catalogo, id_estado, codigo_club, estatus) 
                          VALUES (18, 2, 2, 'CL-0018', 3)");

        $datos = [
            'accion'          => 'reincorporar',
            'codigo_articulo' => 18
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Artículo reincorporado como Disponible.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 18");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'El artículo debió volver a estatus 1 (Disponible).');
    }

    /**
     * ESC-INT-AI-03 / CP-INT-AI-15: Rechazo de eliminación o reincorporación cuando el parámetro codigo_articulo está ausente.
     */
    public function testRechazoOperacionesConIdVacio(): void
    {
        $resEliminar = $this->modelo->ProcesarDatos(['accion' => 'eliminar']);
        $this->assertEquals('error', $resEliminar['accion']);
        $this->assertEquals(EMPTY_SELECTION, $resEliminar['codigo']);

        $resReincorporar = $this->modelo->ProcesarDatos(['accion' => 'reincorporar']);
        $this->assertEquals('error', $resReincorporar['accion']);
        $this->assertEquals(EMPTY_SELECTION, $resReincorporar['codigo']);
    }
}

