<?php

declare(strict_types=1);

namespace tests\Integration\CategoriaCatalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCategoriaCatalogo;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("categoriacatalogo")]
class ConsultasCategoriaCatalogoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloCategoriaCatalogo $modelo;

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

        $this->modelo = new ModeloCategoriaCatalogo();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE devoluciones;");
        $this->pdo->exec("TRUNCATE TABLE asignaciones;");
        $this->pdo->exec("TRUNCATE TABLE articulos_inventario;");
        $this->pdo->exec("TRUNCATE TABLE catalogo;");
        $this->pdo->exec("TRUNCATE TABLE categoria_catalogo;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        $this->sembrarCategorias();
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function sembrarCategorias(): void
    {
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla, descripcion) VALUES 
                          (1, 'Protecciones', 'Categorico', 'Cascos, pads y coderas'),
                          (2, 'Palos', 'Numerico', 'Sticks de hockey'),
                          (3, 'Patines', 'Numerico', 'Calzado con ruedas'),
                          (4, 'Textil', 'Letras', 'Uniformes y franelas')");
    }

    /**
     * ESC-INT-CC-04 / CP-INT-CC-12: Búsqueda de categoría por ID puntual mediante Buscar($id).
     */
    public function testBuscarPorId(): void
    {
        $resultado = $this->modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $cat = $resultado['datos'][0];
        $this->assertEquals(1, (int)$cat['id_categoria']);
        $this->assertEquals('Protecciones', $cat['nombre']);
        $this->assertEquals('Categorico', $cat['tipo_talla']);
    }

    /**
     * ESC-INT-CC-04 / CP-INT-CC-13: Búsqueda individual a través del despachador ProcesarDatos().
     */
    public function testBuscarViaProcesarDatos(): void
    {
        $datos = [
            'accion'       => 'buscar',
            'id_categoria' => '3'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $cat = $resultado['datos'][0];
        $this->assertEquals(3, (int)$cat['id_categoria']);
        $this->assertEquals('Patines', $cat['nombre']);
    }

    /**
     * ESC-INT-CC-04 / CP-INT-CC-14: Consulta general sin filtros de todas las categorías ordenadas por id_categoria ASC.
     */
    public function testConsultarTodosSinFiltro(): void
    {
        $resultado = $this->modelo->Consultar([]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(4, $resultado['datos']);

        $this->assertEquals('Protecciones', $resultado['datos'][0]['nombre']);
        $this->assertEquals('Textil', $resultado['datos'][3]['nombre']);
    }

    /**
     * ESC-INT-CC-04 / CP-INT-CC-15: Consulta con filtro de texto parcial sobre el nombre de la categoría.
     */
    public function testConsultarConFiltroTexto(): void
    {
        $resultado = $this->modelo->Consultar(['filtro' => 'Palo']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Palos', $resultado['datos'][0]['nombre']);
    }

    /**
     * ESC-INT-CC-04 / CP-INT-CC-16: Consulta con filtro sin coincidencias devolviendo datos vacíos.
     */
    public function testConsultarConFiltroSinCoincidencias(): void
    {
        $resultado = $this->modelo->Consultar(['filtro' => 'CategoriaFantasma99']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos'], 'No debe retornar datos ante términos no registrados.');
    }
}

