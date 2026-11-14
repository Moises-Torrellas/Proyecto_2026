<?php

declare(strict_types=1);

namespace tests\Integration\Catalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCatalogo;
use App\modelo\Conexion;
use Exception;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("catalogo")]
class RegistrarCatalogoIntegrationTest extends TestCase
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
    }

    /**
     * ESC-INT-CAT-01 / CP-INT-CAT-01: Registro exitoso de un producto en catálogo.
     * Verifica persistencia en tabla `catalogo`, formateo de nombre a Title Case y talla a mayúsculas.
     */
    public function testRegistroCatalogoExitoso(): void
    {
        $datos = [
            'accion'          => 'incluir',
            'nombre'          => 'casco de protección',
            'stock_minimo'    => '3',
            'id_categoria'    => '1',
            'talla'           => 'm',
            'codigo_posicion' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        $stmt = $this->pdo->query("SELECT * FROM catalogo WHERE id_categoria = 1");
        $cat = $stmt->fetch();

        $this->assertNotEmpty($cat, 'El producto debió insertarse en la tabla catalogo.');
        $this->assertEquals('Casco De Protección', $cat['nombre'], 'El nombre debió formatearse a Title Case.');
        $this->assertEquals('M', $cat['talla'], 'La talla debió formatearse a mayúsculas.');
        $this->assertEquals(3, (int)$cat['stock_minimo']);
        $this->assertEquals(1, (int)$cat['codigo_posicion']);
    }

    /**
     * ESC-INT-CAT-01 / CP-INT-CAT-02: Registro exitoso con posición opcional nula o vacía.
     */
    public function testRegistroSinPosicionOpcional(): void
    {
        $datos = [
            'accion'          => 'incluir',
            'nombre'          => 'Stick Easton',
            'stock_minimo'    => '5',
            'id_categoria'    => '2',
            'talla'           => 'SENIOR',
            'codigo_posicion' => '' // Sin posición requerida
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT * FROM catalogo WHERE nombre = 'Stick Easton'");
        $cat = $stmt->fetch();

        $this->assertNotEmpty($cat);
        $this->assertNull($cat['codigo_posicion'], 'El campo codigo_posicion debe almacenarse como NULL.');
    }

    /**
     * ESC-INT-CAT-01 / CP-INT-CAT-03: Rechazo de inclusión cuando la categoría referenciada no existe.
     */
    public function testRechazoCategoriaInexistente(): void
    {
        $datos = [
            'accion'          => 'incluir',
            'nombre'          => 'Guantes Pro',
            'stock_minimo'    => '2',
            'id_categoria'    => '9999', // No existe
            'talla'           => 'L'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('La categoría seleccionada no existe.', $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM catalogo WHERE nombre = 'Guantes Pro'");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'No debió insertarse el registro en catalogo.');
    }

    /**
     * ESC-INT-CAT-01 / CP-INT-CAT-04: Validación de excepciones por formato inválido en los datos de entrada.
     */
    public function testValidacionFormatoExpresiones(): void
    {
        // 1. Nombre inválido con caracteres especiales no permitidos
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        $this->modelo->ProcesarDatos([
            'accion'       => 'incluir',
            'nombre'       => 'Pads $$$$$',
            'id_categoria' => '1',
            'stock_minimo' => '2',
            'talla'        => 'M'
        ]);
    }
}

