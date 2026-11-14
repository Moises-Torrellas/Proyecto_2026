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
class RegistrarArticulosInventarioIntegrationTest extends TestCase
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
     * ESC-INT-AI-01 / CP-INT-AI-01: Inclusión exitosa de un artículo individual.
     * Verifica la autogeneración del código club correlativo (CL-0001), estatus inicial 1 (Disponible) y persistencia en BD.
     */
    public function testRegistroIndividualExitoso(): void
    {
        $datos = [
            'accion'      => 'incluir',
            'id_catalogo' => 1,
            'id_estado'   => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Artículo registrado con el código CL-0001.', $resultado['mensaje']);
        $this->assertEquals(['CL-0001'], $resultado['codigos']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        // Verificamos en la base de datos
        $stmt = $this->pdo->query("SELECT * FROM articulos_inventario WHERE codigo_club = 'CL-0001'");
        $articulo = $stmt->fetch();

        $this->assertNotEmpty($articulo, 'El artículo debió insertarse en la tabla articulos_inventario.');
        $this->assertEquals(1, (int)$articulo['id_catalogo']);
        $this->assertEquals(1, (int)$articulo['id_estado']);
        $this->assertEquals(1, (int)$articulo['estatus'], 'El estatus inicial debe ser 1 (Disponible).');
    }

    /**
     * ESC-INT-AI-01 / CP-INT-AI-02: Inclusión por lote de múltiples artículos con códigos consecutivos.
     */
    public function testRegistroPorLoteExitoso(): void
    {
        $lote = [
            ['id_catalogo' => 1, 'id_estado' => 1],
            ['id_catalogo' => 1, 'id_estado' => 1],
            ['id_catalogo' => 2, 'id_estado' => 2],
        ];

        $datos = [
            'accion'    => 'incluir',
            'articulos' => $lote
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertCount(3, $resultado['codigos']);
        $this->assertEquals(['CL-0001', 'CL-0002', 'CL-0003'], $resultado['codigos']);
        $this->assertEquals('Se registraron 3 artículos (CL-0001 al CL-0003).', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM articulos_inventario WHERE estatus = 1");
        $this->assertEquals(3, (int)$stmt->fetchColumn(), 'Deben haberse registrado 3 artículos disponibles.');
    }

    /**
     * ESC-INT-AI-01 / CP-INT-AI-03: Inclusión con estados físicos independientes sobre el mismo producto de catálogo.
     */
    public function testRegistroConEstadosFisicosDiferenciados(): void
    {
        $articulos = [
            ['id_catalogo' => 1, 'id_estado' => 1], // Casco Excelente
            ['id_catalogo' => 1, 'id_estado' => 2], // Casco Regular
            ['id_catalogo' => 1, 'id_estado' => 3], // Casco Dañado
        ];

        $resultado = $this->modelo->ProcesarDatos([
            'accion'    => 'incluir',
            'articulos' => $articulos
        ]);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT id_estado, codigo_club FROM articulos_inventario ORDER BY codigo_articulo ASC");
        $filas = $stmt->fetchAll();

        $this->assertCount(3, $filas);
        $this->assertEquals(1, (int)$filas[0]['id_estado']);
        $this->assertEquals('CL-0001', $filas[0]['codigo_club']);

        $this->assertEquals(2, (int)$filas[1]['id_estado']);
        $this->assertEquals('CL-0002', $filas[1]['codigo_club']);

        $this->assertEquals(3, (int)$filas[2]['id_estado']);
        $this->assertEquals('CL-0003', $filas[2]['codigo_club']);
    }

    /**
     * ESC-INT-AI-01 / CP-INT-AI-04: Continuidad de correlativo cuando ya existen artículos previos en la base de datos.
     */
    public function testIncrementoCorrelativoConExistenciasPrevias(): void
    {
        // Insertamos un registro previo con CL-0045
        $this->pdo->exec("INSERT INTO articulos_inventario (id_catalogo, id_estado, codigo_club, estatus) 
                          VALUES (1, 1, 'CL-0045', 1)");

        $datos = [
            'accion'      => 'incluir',
            'id_catalogo' => 2,
            'id_estado'   => 2
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals(['CL-0046'], $resultado['codigos']);

        $stmt = $this->pdo->query("SELECT codigo_club FROM articulos_inventario WHERE id_catalogo = 2");
        $this->assertEquals('CL-0046', $stmt->fetchColumn(), 'El correlativo debió continuar en CL-0046.');
    }

    /**
     * ESC-INT-AI-01 / CP-INT-AI-05: Validación y rechazo al enviar arreglo de artículos vacío.
     */
    public function testRechazoRegistroArregloVacio(): void
    {
        $datos = [
            'accion'    => 'incluir',
            'articulos' => []
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_VACIO', $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM articulos_inventario");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'No debió insertarse ningún artículo.');
    }
}

