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
class RegistrarCategoriaCatalogoIntegrationTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    /**
     * ESC-INT-CC-01 / CP-INT-CC-01: Registro exitoso de una nueva categoría de catálogo.
     * Verifica persistencia en tabla `categoria_catalogo` y formateo automático de nombre a Title Case.
     */
    public function testRegistroExitoso(): void
    {
        $datos = [
            'accion'      => 'incluir',
            'nombre'      => 'protecciones corporales',
            'descripcion' => 'Equipo de seguridad',
            'tipo_talla'  => 'Categorico'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('incluir', $resultado['accion']);
        $this->assertEquals('Categoría registrada exitosamente.', $resultado['mensaje']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        $stmt = $this->pdo->query("SELECT * FROM categoria_catalogo WHERE tipo_talla = 'Categorico'");
        $cat = $stmt->fetch();

        $this->assertNotEmpty($cat, 'La categoría debió registrarse en la base de datos.');
        $this->assertEquals('Protecciones Corporales', $cat['nombre'], 'El nombre debió formatearse a Title Case.');
        $this->assertEquals('Equipo de seguridad', $cat['descripcion']);
        $this->assertEquals('Categorico', $cat['tipo_talla']);
    }

    /**
     * ESC-INT-CC-01 / CP-INT-CC-02: Control de duplicidad: Rechazo si el nombre ya existe en la base de datos.
     */
    public function testErrorNombreDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO categoria_catalogo (nombre, tipo_talla, descripcion) 
                          VALUES ('Patines', 'Numerico', 'Calzado deportivo')");

        $datos = [
            'accion'      => 'incluir',
            'nombre'      => 'Patines',
            'descripcion' => 'Otra descripción',
            'tipo_talla'  => 'Numerico'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una categoría registrada con este nombre.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM categoria_catalogo");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'No debió insertarse una categoría duplicada.');
    }

    /**
     * ESC-INT-CC-01 / CP-INT-CC-03: Control de duplicidad insensible a mayúsculas y espacios en blanco periféricos.
     */
    public function testErrorNombreDuplicadoCaseInsensitiveYEspacios(): void
    {
        $this->pdo->exec("INSERT INTO categoria_catalogo (nombre, tipo_talla, descripcion) 
                          VALUES ('Cascos', 'Categorico', 'Protección craneal')");

        $datos = [
            'accion'      => 'incluir',
            'nombre'      => '   cascos   ',
            'descripcion' => 'Nueva entrada',
            'tipo_talla'  => 'Categorico'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una categoría registrada con este nombre.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM categoria_catalogo");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * ESC-INT-CC-01 / CP-INT-CC-04: Asignación de tipo de talla por defecto ('Numerico') cuando no se especifica.
     */
    public function testTipoTallaPorDefectoNumerico(): void
    {
        $datos = [
            'accion'      => 'incluir',
            'nombre'      => 'Discos De Juego',
            'descripcion' => 'Pucks oficiales'
            // tipo_talla omitido
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('incluir', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT tipo_talla FROM categoria_catalogo WHERE nombre = 'Discos De Juego'");
        $this->assertEquals('Numerico', $stmt->fetchColumn(), 'El tipo de talla por defecto debe ser Numerico.');
    }
}

