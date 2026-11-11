<?php

declare(strict_types=1);

namespace tests\Integration\Catalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCatalogo;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("catalogo")]
class ModificarCatalogoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloCatalogo $modelo;

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

        $this->modelo = new ModeloCatalogo();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE devoluciones;");
        $this->pdo->exec("TRUNCATE TABLE asignaciones;");
        $this->pdo->exec("TRUNCATE TABLE articulos_inventario;");
        $this->pdo->exec("TRUNCATE TABLE catalogo;");
        $this->pdo->exec("TRUNCATE TABLE categoria_catalogo;");
        $this->pdo->exec("TRUNCATE TABLE posiciones;");
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
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES 
                          (1, 'Protecciones', 'Categorico'),
                          (2, 'Palos', 'Numerico')");

        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) VALUES 
                          (1, 'Portero', 'POR', 'Defensa de arco'),
                          (2, 'Defensa', 'DEF', 'Línea de contención')");
    }

    /**
     * ESC-INT-CAT-02 / CP-INT-CAT-05: Modificación exitosa de los datos de un producto en catálogo.
     */
    public function testModificacionExitosa(): void
    {
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, talla, codigo_posicion) 
                          VALUES (1, 'Casco Bauer', 2, 1, 'M', 1)");

        $datos = [
            'accion'          => 'modificar',
            'id_catalogo'     => '1',
            'nombre'          => 'Casco Bauer Pro',
            'stock_minimo'    => '4',
            'id_categoria'    => '1',
            'talla'           => 'l',
            'codigo_posicion' => '2'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        $stmt = $this->pdo->query("SELECT * FROM catalogo WHERE id_catalogo = 1");
        $cat = $stmt->fetch();

        $this->assertEquals('Casco Bauer Pro', $cat['nombre']);
        $this->assertEquals(4, (int)$cat['stock_minimo']);
        $this->assertEquals('L', $cat['talla']);
        $this->assertEquals(2, (int)$cat['codigo_posicion']);
    }

    /**
     * ESC-INT-CAT-02 / CP-INT-CAT-06: Modificación preservando la categoría y actualizando únicamente talla y stock.
     */
    public function testModificacionPreservandoCategoriaYActualizandoTalla(): void
    {
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, talla, codigo_posicion) 
                          VALUES (5, 'Pads CCM', 3, 1, 'M', 1)");

        $datos = [
            'accion'          => 'modificar',
            'id_catalogo'     => '5',
            'nombre'          => 'Pads CCM',
            'stock_minimo'    => '6',
            'id_categoria'    => '1',
            'talla'           => 'XL',
            'codigo_posicion' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT talla, stock_minimo FROM catalogo WHERE id_catalogo = 5");
        $fila = $stmt->fetch();

        $this->assertEquals('XL', $fila['talla']);
        $this->assertEquals(6, (int)$fila['stock_minimo']);
    }

    /**
     * ESC-INT-CAT-02 / CP-INT-CAT-07: Modificación con desasignación de posición (estableciendo posición a nulo).
     */
    public function testModificacionConPosicionNula(): void
    {
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, talla, codigo_posicion) 
                          VALUES (8, 'Stick Hockey', 2, 2, 'Senior', 1)");

        $datos = [
            'accion'          => 'modificar',
            'id_catalogo'     => '8',
            'nombre'          => 'Stick Hockey',
            'stock_minimo'    => '2',
            'id_categoria'    => '2',
            'talla'           => 'Senior',
            'codigo_posicion' => '' // Vacío -> debe convertirse en null
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT codigo_posicion FROM catalogo WHERE id_catalogo = 8");
        $this->assertNull($stmt->fetchColumn(), 'codigo_posicion debió actualizarse a NULL.');
    }

    /**
     * ESC-INT-CAT-02 / CP-INT-CAT-08: Rechazo de modificación ante un ID de catálogo no existente en BD.
     */
    public function testRechazoModificarIdInexistente(): void
    {
        $datos = [
            'accion'          => 'modificar',
            'id_catalogo'     => '99999',
            'nombre'          => 'Casco Inexistente',
            'stock_minimo'    => '2',
            'id_categoria'    => '1',
            'talla'           => 'M'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(defined('INVALID_ID') ? INVALID_ID : 'ID inválido o inexistente.', $resultado['codigo']);
    }
}

