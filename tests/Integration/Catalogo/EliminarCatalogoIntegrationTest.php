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
class EliminarCatalogoIntegrationTest extends TestCase
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
                          (1, 'Protecciones', 'Categorico')");
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
                          (1, 'Excelente', 1)");
    }

    /**
     * ESC-INT-CAT-03 / CP-INT-CAT-09: Eliminación exitosa de catálogo sin artículos físicos asociados.
     * Invoca el procedimiento almacenado `EliminarCatalogoSeguro` y verifica la eliminación física en BD.
     */
    public function testEliminarCatalogoSinArticulosExitoso(): void
    {
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, talla) 
                          VALUES (1, 'Casco Bauer', 2, 1, 'M')");

        $datos = [
            'accion'      => 'eliminar',
            'id_catalogo' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM catalogo WHERE id_catalogo = 1");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'El registro debió ser eliminado de la tabla catalogo.');
    }

    /**
     * ESC-INT-CAT-03 / CP-INT-CAT-10: Bloqueo de eliminación si el catálogo tiene artículos físicos asociados en articulos_inventario.
     */
    public function testRechazoEliminarCatalogoConArticulosInventario(): void
    {
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, talla) 
                          VALUES (2, 'Pads CCM', 1, 1, 'L')");
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_catalogo, id_estado, codigo_club, estatus) 
                          VALUES (1, 2, 1, 'CL-0001', 1)");

        $datos = [
            'accion'      => 'eliminar',
            'id_catalogo' => '2'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(
            'No se puede eliminar el catálogo porque tiene artículos en el inventario físico asociados.',
            $resultado['codigo']
        );

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM catalogo WHERE id_catalogo = 2");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'El registro de catálogo debe permanecer en BD.');
    }

    /**
     * ESC-INT-CAT-03 / CP-INT-CAT-11: Manejo defensivo ante eliminación de un ID inexistente en BD.
     */
    public function testRechazoEliminarIdInexistente(): void
    {
        $datos = [
            'accion'      => 'eliminar',
            'id_catalogo' => '99999'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(defined('INVALID_ID') ? INVALID_ID : 'ID inválido o inexistente.', $resultado['codigo']);
    }
}

