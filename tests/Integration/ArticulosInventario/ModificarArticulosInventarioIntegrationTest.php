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
class ModificarArticulosInventarioIntegrationTest extends TestCase
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
                          (2, 'Regular', 2),
                          (3, 'Dañado', 3)");
    }

    /**
     * ESC-INT-AI-02 / CP-INT-AI-06: Modificación exitosa del estado físico y catálogo de un artículo.
     * Verifica la persistencia en BD y que el código club permanezca inalterado.
     */
    public function testModificarEstadoFisicoYCatalogoExitoso(): void
    {
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_catalogo, id_estado, codigo_club, estatus) 
                          VALUES (10, 1, 1, 'CL-0010', 1)");

        $datos = [
            'accion'          => 'modificar',
            'codigo_articulo' => 10,
            'id_catalogo'     => 2, // Cambia de Casco (1) a Pads (2)
            'id_estado'       => 2  // Cambia de Excelente (1) a Regular (2)
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Artículo actualizado correctamente.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT * FROM articulos_inventario WHERE codigo_articulo = 10");
        $articulo = $stmt->fetch();

        $this->assertEquals(2, (int)$articulo['id_catalogo']);
        $this->assertEquals(2, (int)$articulo['id_estado']);
        $this->assertEquals('CL-0010', $articulo['codigo_club'], 'El código club asignado no debe cambiar.');
        $this->assertEquals(1, (int)$articulo['estatus']);
    }

    /**
     * ESC-INT-AI-02 / CP-INT-AI-07: Modificación únicamente del estado físico preservando el catálogo.
     */
    public function testModificarSoloEstadoFisico(): void
    {
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_catalogo, id_estado, codigo_club, estatus) 
                          VALUES (15, 1, 1, 'CL-0015', 1)");

        $datos = [
            'accion'          => 'modificar',
            'codigo_articulo' => 15,
            'id_catalogo'     => 1, // Mantiene catálogo
            'id_estado'       => 3  // Cambia a Dañado
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT id_estado, id_catalogo FROM articulos_inventario WHERE codigo_articulo = 15");
        $fila = $stmt->fetch();

        $this->assertEquals(3, (int)$fila['id_estado']);
        $this->assertEquals(1, (int)$fila['id_catalogo']);
    }

    /**
     * ESC-INT-AI-02 / CP-INT-AI-08: Validación del payload JSON de datos_nuevos retornado tras la modificación.
     */
    public function testVerificarPayloadDatosNuevosEnModificacion(): void
    {
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_catalogo, id_estado, codigo_club, estatus) 
                          VALUES (20, 2, 2, 'CL-0020', 1)");

        $datos = [
            'accion'          => 'modificar',
            'codigo_articulo' => 20,
            'id_catalogo'     => 2,
            'id_estado'       => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertArrayHasKey('datos_nuevos', $resultado);
        $decoded = json_decode($resultado['datos_nuevos'], true);

        $this->assertEquals(20, $decoded['codigo_articulo']);
        $this->assertEquals(2, $decoded['id_catalogo']);
        $this->assertEquals(1, $decoded['id_estado']);
    }

    /**
     * ESC-INT-AI-02 / CP-INT-AI-09: Actualización directa de estatus mediante método CambiarEstatus().
     */
    public function testCambiarEstatusDirecto(): void
    {
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_catalogo, id_estado, codigo_club, estatus) 
                          VALUES (25, 1, 1, 'CL-0025', 1)");

        // Cambiamos de estatus 1 (Disponible) a estatus 2 (Asignado/En uso)
        $actualizado = $this->modelo->CambiarEstatus(25, 2);

        $this->assertTrue($actualizado);

        $stmt = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 25");
        $this->assertEquals(2, (int)$stmt->fetchColumn());
    }
}

