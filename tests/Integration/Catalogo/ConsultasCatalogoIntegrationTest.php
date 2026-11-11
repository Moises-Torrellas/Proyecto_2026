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
class ConsultasCatalogoIntegrationTest extends TestCase
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
                          (1, 'Portero', 'POR', 'Defensa de arco')");

        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
                          (1, 'Excelente', 1),
                          (2, 'Regular', 2)");

        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, talla, codigo_posicion) VALUES 
                          (1, 'Casco Bauer', 2, 1, 'M', 1),
                          (2, 'Pads CCM', 1, 1, 'L', 1),
                          (3, 'Stick Easton', 3, 2, 'Senior', NULL)");

        // Artículos físicos: Casco Bauer tiene 2 artículos (1 excelente, 1 regular)
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_catalogo, id_estado, codigo_club, estatus) VALUES 
                          (1, 1, 1, 'CL-0001', 1),
                          (2, 1, 2, 'CL-0002', 1)");
    }

    /**
     * ESC-INT-CAT-04 / CP-INT-CAT-12: Consulta general con resolución de subconsultas y función SQL StockDisponibleCatalogo.
     */
    public function testConsultarGeneralConSubconsultasYStock(): void
    {
        $resultado = $this->modelo->Consultar([]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(3, $resultado['datos']);

        // Verificamos el Casco Bauer
        $casco = $resultado['datos'][0];
        $this->assertEquals('Casco Bauer', $casco['nombre']);
        $this->assertEquals('Protecciones', $casco['categoria_nombre'], 'La subconsulta debe resolver el nombre de la categoría.');
        $this->assertEquals('Portero', $casco['posicion_nombre'], 'La subconsulta debe resolver el nombre de la posición.');
        $this->assertEquals(1, (int)$casco['stock_actual'], 'StockDisponibleCatalogo debe contabilizar 1 artículo libre y excelente.');
    }

    /**
     * ESC-INT-CAT-04 / CP-INT-CAT-13: Consulta con filtro de texto sobre el nombre del producto.
     */
    public function testConsultarConFiltroTextoNombre(): void
    {
        $resultado = $this->modelo->Consultar(['filtro' => 'Pads']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Pads CCM', $resultado['datos'][0]['nombre']);
    }

    /**
     * ESC-INT-CAT-04 / CP-INT-CAT-14: Consulta con filtro de texto coincidente con el nombre de la categoría.
     */
    public function testConsultarConFiltroTextoCategoria(): void
    {
        // 'Palos' es el nombre de la categoría 2
        $resultado = $this->modelo->Consultar(['filtro' => 'Palos']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Stick Easton', $resultado['datos'][0]['nombre']);
    }

    /**
     * ESC-INT-CAT-04 / CP-INT-CAT-15: Consulta con filtro exacto de talla.
     */
    public function testConsultarConFiltroTalla(): void
    {
        $resultado = $this->modelo->Consultar(['filtro' => 'Senior']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Stick Easton', $resultado['datos'][0]['nombre']);
        $this->assertEquals('Senior', $resultado['datos'][0]['talla']);
    }

    /**
     * ESC-INT-CAT-04 / CP-INT-CAT-16: Búsqueda puntual directa por clave primaria mediante Buscar($id).
     */
    public function testBuscarCatalogoPorId(): void
    {
        $resultado = $this->modelo->Buscar(2);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $pads = $resultado['datos'][0];
        $this->assertEquals(2, (int)$pads['id_catalogo']);
        $this->assertEquals('Pads CCM', $pads['nombre']);
        $this->assertEquals('L', $pads['talla']);
    }

    /**
     * ESC-INT-CAT-04 / CP-INT-CAT-17: Búsqueda puntual mediante el despachador ProcesarDatos().
     */
    public function testBuscarCatalogoViaProcesarDatos(): void
    {
        $datos = [
            'accion'      => 'buscar',
            'id_catalogo' => '3'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);
        $this->assertEquals('Stick Easton', $resultado['datos'][0]['nombre']);
    }

    /**
     * ESC-INT-CAT-04 / CP-INT-CAT-18: Despacho de consulta para reportes vía acción 'generar'.
     */
    public function testGenerarReporteViaProcesarDatos(): void
    {
        $datos = ['accion' => 'generar'];
        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(3, $resultado['datos']);
    }
}

