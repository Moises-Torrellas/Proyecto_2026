<?php

declare(strict_types=1);

namespace tests\Integration\Categorias;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCategorias;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("categorias")]
class ConsultasCategoriaIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloCategorias $modelo;

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

        $this->modelo = new ModeloCategorias();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE retiros;");
        $this->pdo->exec("TRUNCATE TABLE detalles_participacion;");
        $this->pdo->exec("TRUNCATE TABLE detalles_equipos;");
        $this->pdo->exec("TRUNCATE TABLE asignaciones;");
        $this->pdo->exec("TRUNCATE TABLE cargos;");
        $this->pdo->exec("TRUNCATE TABLE datos_medicos;");
        $this->pdo->exec("TRUNCATE TABLE contacto_atleta;");
        $this->pdo->exec("TRUNCATE TABLE identidad_atleta;");
        $this->pdo->exec("TRUNCATE TABLE atleta_representante;");
        $this->pdo->exec("TRUNCATE TABLE inscripciones;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("TRUNCATE TABLE categorias;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
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
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES 
                          (1, 'U-11', 10, 11),
                          (2, 'U-15', 13, 15),
                          (3, 'Senior', 20, 30)");
    }

    /**
     * Búsqueda de categoría por ID.
     */
    public function testBuscarCategoriaPorId(): void
    {
        $this->sembrarCategorias();

        $resultado = $this->modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $cat = $resultado['datos'][0];
        $this->assertEquals(1, (int)$cat['codigo_categoria']);
        $this->assertEquals('U-11', $cat['nombre']);
        $this->assertEquals(10, (int)$cat['edad_min']);
        $this->assertEquals(11, (int)$cat['edad_max']);
    }

    /**
     * Consulta con filtro de búsqueda por nombre.
     */
    public function testConsultarConFiltro(): void
    {
        $this->sembrarCategorias();

        // 1. Filtrar por nombre 'U-15'
        $resNombre = $this->modelo->Consultar(['filtro' => 'U-15']);
        $this->assertEquals('consultar', $resNombre['accion']);
        $this->assertCount(1, $resNombre['datos']);
        $this->assertEquals('U-15', $resNombre['datos'][0]['nombre']);

        // 2. Filtrar por 'Senior'
        $resSenior = $this->modelo->Consultar(['filtro' => 'Senior']);
        $this->assertCount(1, $resSenior['datos']);
        $this->assertEquals('Senior', $resSenior['datos'][0]['nombre']);

        // 3. Consulta sin filtro devuelve todas las categorías
        $resTodas = $this->modelo->Consultar([]);
        $this->assertCount(3, $resTodas['datos']);
    }

    /**
     * Consulta utilizando filtros específicos de rango de edad.
     */
    public function testConsultarPorRangoEdad(): void
    {
        $this->sembrarCategorias();

        // Buscamos categorías con edad_min >= 12 y edad_max <= 16 (coincide U-15: 13-15)
        $datos = [
            'accion'   => 'generar',
            'edad_min' => '12',
            'edad_max' => '16'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('U-15', $resultado['datos'][0]['nombre']);
    }

    /**
     * Validación de existencia de una categoría vía verificarCategoria.
     */
    public function testVerificarCategoriaExistente(): void
    {
        $this->sembrarCategorias();

        $this->assertTrue($this->modelo->verificarCategoria(1));
        $this->assertTrue($this->modelo->verificarCategoria(2));
        $this->assertFalse($this->modelo->verificarCategoria(99999));
    }
}

