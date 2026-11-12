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
class EliminarCategoriaCatalogoIntegrationTest extends TestCase
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
     * ESC-INT-CC-03 / CP-INT-CC-09: Eliminación exitosa de una categoría de catálogo sin productos asociados.
     */
    public function testEliminarExitoso(): void
    {
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla, descripcion) 
                          VALUES (1, 'Accesorios', 'Categorico', 'Cintas y botellas')");

        $datos = [
            'accion'       => 'eliminar',
            'id_categoria' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('eliminar', $resultado['accion']);
        $this->assertEquals('Categoría eliminada exitosamente.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM categoria_catalogo WHERE id_categoria = 1");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'La categoría debió ser eliminada de la base de datos.');
    }

    /**
     * ESC-INT-CC-03 / CP-INT-CC-10: Manejo defensivo ante intento de eliminar una categoría inexistente.
     */
    public function testErrorEliminarIdInexistente(): void
    {
        $datos = [
            'accion'       => 'eliminar',
            'id_categoria' => '99999'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('La categoría no existe.', $resultado['mensaje']);
    }

    /**
     * ESC-INT-CC-03 / CP-INT-CC-11: Integridad referencial: Bloqueo de eliminación si la categoría tiene productos en `catalogo`.
     */
    public function testErrorEliminarConCatalogoAsociado(): void
    {
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla, descripcion) 
                          VALUES (1, 'Protecciones', 'Categorico', 'Cascos y pads')");
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, talla) 
                          VALUES (10, 'Casco Bauer', 2, 1, 'M')");

        $datos = [
            'accion'       => 'eliminar',
            'id_categoria' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('No se puede eliminar: la categoría tiene artículos del catálogo asociados.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM categoria_catalogo WHERE id_categoria = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'La categoría no debió eliminarse debido a la restricción referencial.');
    }
}

