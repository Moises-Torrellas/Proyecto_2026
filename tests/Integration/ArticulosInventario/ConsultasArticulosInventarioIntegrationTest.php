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
class ConsultasArticulosInventarioIntegrationTest extends TestCase
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
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES 
                          (1, 'Protecciones', 'Categorico'),
                          (2, 'Palos', 'Numerico')");

        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, talla) VALUES 
                          (1, 'Casco Bauer', 2, 1, 'M'),
                          (2, 'Pads CCM', 1, 1, 'L'),
                          (3, 'Stick Easton', 3, 2, 'Senior')");

        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
                          (1, 'Excelente', 1),
                          (2, 'Regular', 2),
                          (3, 'Dañado', 3)");

        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_catalogo, id_estado, codigo_club, estatus) VALUES 
                          (1, 1, 1, 'CL-0001', 1),
                          (2, 1, 2, 'CL-0002', 1),
                          (3, 2, 1, 'CL-0003', 1),
                          (4, 3, 3, 'CL-0004', 3)"); // Artículo 4 dado de baja
    }

    /**
     * ESC-INT-AI-04 / CP-INT-AI-16: Consulta general agrupada por catálogo sin filtros.
     * Verifica agrupación por id_catalogo, detalle de piezas individuales, categoría y función EsAptoParaUso().
     */
    public function testConsultarAgrupadoSinFiltros(): void
    {
        $datos = ['accion' => 'consultar'];
        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        // Debe haber agrupaciones para los 3 catálogos con artículos
        $catalogos = $resultado['datos'];
        $this->assertCount(3, $catalogos);

        // Primer catálogo agrupado (Casco Bauer) tiene 2 piezas
        $cascos = $catalogos[0];
        $this->assertEquals(1, $cascos['id_catalogo']);
        $this->assertStringContainsString('Casco Bauer', $cascos['articulo']);
        $this->assertCount(2, $cascos['piezas']);

        // Verificar propiedades de las piezas ordenadas por codigo_articulo DESC
        $pieza1 = $cascos['piezas'][0];
        $this->assertEquals('CL-0002', $pieza1['codigo_club']);
        $this->assertEquals('Regular', $pieza1['estado_fisico']);
        $this->assertArrayHasKey('apto_para_uso', $pieza1);

        $pieza2 = $cascos['piezas'][1];
        $this->assertEquals('CL-0001', $pieza2['codigo_club']);
        $this->assertEquals('Excelente', $pieza2['estado_fisico']);
    }

    /**
     * ESC-INT-AI-04 / CP-INT-AI-17: Consulta con filtro de texto parcial en nombre de artículo.
     */
    public function testConsultarConFiltroTexto(): void
    {
        $datos = [
            'accion' => 'consultar',
            'filtro' => 'Pads'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertStringContainsString('Pads CCM', $resultado['datos'][0]['articulo']);
    }

    /**
     * ESC-INT-AI-04 / CP-INT-AI-18: Consulta con filtro específico por catálogo (id_catalogo).
     */
    public function testConsultarConFiltroCatalogo(): void
    {
        $datos = [
            'accion'      => 'consultar',
            'id_catalogo' => 3 // Stick Easton
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(3, $resultado['datos'][0]['id_catalogo']);
        $this->assertEquals('Stick Easton (Talla: Senior)', $resultado['datos'][0]['articulo']);
    }

    /**
     * ESC-INT-AI-04 / CP-INT-AI-19: Consulta con filtro específico por estado físico (id_estado).
     */
    public function testConsultarConFiltroEstadoFisico(): void
    {
        $datos = [
            'accion'    => 'consultar',
            'id_estado' => 2 // Regular
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['id_catalogo']);
        $this->assertCount(1, $resultado['datos'][0]['piezas']);
        $this->assertEquals('Regular', $resultado['datos'][0]['piezas'][0]['estado_fisico']);
    }

    /**
     * ESC-INT-AI-04 / CP-INT-AI-20: Búsqueda puntual por clave primaria mediante Buscar($id) y ProcesarDatos().
     */
    public function testBuscarArticuloPorId(): void
    {
        $resultadoDirecto = $this->modelo->Buscar(1);
        $this->assertEquals('buscar', $resultadoDirecto['accion']);
        $this->assertNotEmpty($resultadoDirecto['datos']);
        $this->assertEquals('CL-0001', $resultadoDirecto['datos'][0]['codigo_club']);

        $resultadoDispatcher = $this->modelo->ProcesarDatos([
            'accion'          => 'buscar',
            'codigo_articulo' => 3
        ]);
        $this->assertEquals('buscar', $resultadoDispatcher['accion']);
        $this->assertEquals('CL-0003', $resultadoDispatcher['datos'][0]['codigo_club']);
    }

    /**
     * ESC-INT-AI-04 / CP-INT-AI-21: Carga de combos para poblar selects de catálogos y estados físicos.
     */
    public function testCargarCombos(): void
    {
        $datos = ['accion' => 'cargar_combos'];
        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('cargar_combos', $resultado['accion']);
        $this->assertArrayHasKey('catalogos', $resultado);
        $this->assertArrayHasKey('estados', $resultado);

        $this->assertCount(3, $resultado['catalogos']);
        $this->assertCount(3, $resultado['estados']);
    }

    /**
     * ESC-INT-AI-04 / CP-INT-AI-22: Consulta de artículos libres / disponibles para asignación (estatus = 1).
     */
    public function testConsultarArticulosLibres(): void
    {
        $resultado = $this->modelo->ConsultarArticulosLibres();

        $this->assertEquals('exito', $resultado['accion']);
        $articulosLibres = $resultado['datos'];

        // Solo deben listarse los 3 artículos con estatus = 1 (el artículo 4 tiene estatus 3)
        $this->assertCount(3, $articulosLibres);

        foreach ($articulosLibres as $item) {
            $this->assertNotEquals(4, (int)$item['codigo_articulo'], 'El artículo con estatus 3 no debe aparecer.');
        }
    }
}
