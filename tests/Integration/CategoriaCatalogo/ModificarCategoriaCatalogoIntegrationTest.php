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
class ModificarCategoriaCatalogoIntegrationTest extends TestCase
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
     * ESC-INT-CC-02 / CP-INT-CC-05: Modificación exitosa con nuevo nombre y atributos actualizados.
     */
    public function testModificacionExitosa(): void
    {
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla, descripcion) 
                          VALUES (1, 'Palos', 'Numerico', 'Palos de hockey')");

        $datos = [
            'accion'       => 'modificar',
            'id_categoria' => '1',
            'nombre'       => 'Sticks Profesionales',
            'descripcion'  => 'Palos de fibra de carbono',
            'tipo_talla'   => 'Categorico'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Categoría modificada exitosamente.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT * FROM categoria_catalogo WHERE id_categoria = 1");
        $cat = $stmt->fetch();

        $this->assertEquals('Sticks Profesionales', $cat['nombre']);
        $this->assertEquals('Palos de fibra de carbono', $cat['descripcion']);
        $this->assertEquals('Categorico', $cat['tipo_talla']);
    }

    /**
     * ESC-INT-CC-02 / CP-INT-CC-06: Modificación conservando su propio nombre (auto-exclusión de duplicidad).
     */
    public function testModificacionConservarPropiosDatos(): void
    {
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla, descripcion) 
                          VALUES (2, 'Cascos', 'Categorico', 'Protección')");

        $datos = [
            'accion'       => 'modificar',
            'id_categoria' => '2',
            'nombre'       => 'Cascos', // Mismo nombre
            'descripcion'  => 'Protección certificada',
            'tipo_talla'   => 'Categorico'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Categoría modificada exitosamente.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT descripcion FROM categoria_catalogo WHERE id_categoria = 2");
        $this->assertEquals('Protección certificada', $stmt->fetchColumn());
    }

    /**
     * ESC-INT-CC-02 / CP-INT-CC-07: Rechazo si el nuevo nombre ya pertenece a otra categoría registrada en BD.
     */
    public function testErrorModificarNombreAjenoDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla, descripcion) VALUES 
                          (1, 'Patines', 'Numerico', 'Línea de patines'),
                          (2, 'Guantes', 'Categorico', 'Línea de guantes')");

        $datos = [
            'accion'       => 'modificar',
            'id_categoria' => '2',
            'nombre'       => 'Patines', // Nombre de la categoría 1
            'descripcion'  => 'Intento de colisión',
            'tipo_talla'   => 'Numerico'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe otra categoría registrada con este nombre.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT nombre FROM categoria_catalogo WHERE id_categoria = 2");
        $this->assertEquals('Guantes', $stmt->fetchColumn(), 'El registro 2 debe conservar su nombre original.');
    }

    /**
     * ESC-INT-CC-02 / CP-INT-CC-08: Normalización y formateo Title Case al modificar el nombre.
     */
    public function testModificacionFormateoTitleCase(): void
    {
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla, descripcion) 
                          VALUES (3, 'Textil', 'Letras', 'Ropa deportiva')");

        $datos = [
            'accion'       => 'modificar',
            'id_categoria' => '3',
            'nombre'       => 'indumentaria oficial de juego',
            'descripcion'  => 'Uniformes',
            'tipo_talla'   => 'Letras'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('modificar', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT nombre FROM categoria_catalogo WHERE id_categoria = 3");
        $this->assertEquals('Indumentaria Oficial De Juego', $stmt->fetchColumn());
    }
}

