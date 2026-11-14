<?php

declare(strict_types=1);

namespace tests\Integration\EstadoFisico;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadoFisico;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("estadofisico")]
class EliminarEstadoFisicoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloEstadoFisico $modelo;

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

        $this->modelo = new ModeloEstadoFisico();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE devoluciones;");
        $this->pdo->exec("TRUNCATE TABLE asignaciones;");
        $this->pdo->exec("TRUNCATE TABLE articulos_inventario;");
        $this->pdo->exec("TRUNCATE TABLE catalogo;");
        $this->pdo->exec("TRUNCATE TABLE categoria_catalogo;");
        $this->pdo->exec("TRUNCATE TABLE estado_fisico;");
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
     * ESC-INT-EF-03 / CP-INT-EF-09: Eliminación exitosa de un estado físico sin registros asociados.
     */
    public function testEliminarExitoso(): void
    {
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (1, 'Excelente', 1)");

        $datos = [
            'accion'    => 'eliminar',
            'id_estado' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('eliminar', $resultado['accion']);
        $this->assertEquals('Estado físico eliminado exitosamente.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM estado_fisico WHERE id_estado = 1");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'El estado físico debió ser eliminado de la base de datos.');
    }

    /**
     * ESC-INT-EF-03 / CP-INT-EF-10: Rechazo de eliminación si el ID no existe en la base de datos.
     */
    public function testErrorEliminarIdInexistente(): void
    {
        $datos = [
            'accion'    => 'eliminar',
            'id_estado' => '9999'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El estado físico no existe.', $resultado['mensaje']);
    }

    /**
     * ESC-INT-EF-03 / CP-INT-EF-11: Integridad Referencial: Bloqueo de eliminación si existen artículos de inventario asociados.
     */
    public function testErrorEliminarConArticulosAsociados(): void
    {
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (1, 'Excelente', 1)");
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES (1, 'Protecciones', 'Categorico')");
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, Id_categoria, talla) VALUES (1, 'Casco', 1, 1, 'M')");
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_estado, id_catalogo, codigo_club, estatus) 
                          VALUES (1, 1, 1, 'CAS-001', 1)");

        $datos = [
            'accion'    => 'eliminar',
            'id_estado' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('No se puede eliminar: existen artículos de inventario asociados a este estado.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM estado_fisico WHERE id_estado = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'El estado físico no debió ser eliminado.');
    }

    /**
     * ESC-INT-EF-03 / CP-INT-EF-12: Integridad Referencial: Bloqueo de eliminación si existen devoluciones asociadas.
     */
    public function testErrorEliminarConDevolucionesAsociadas(): void
    {
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (2, 'Dañado', 3)");
        $this->pdo->exec("INSERT INTO devoluciones (id_devolucion, id_asignacion, id_estado, fecha_devolucion, observacion) 
                          VALUES (1, 1, 2, '2026-06-01', 'Devuelto con fisura')");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        $datos = [
            'accion'    => 'eliminar',
            'id_estado' => '2'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('No se puede eliminar: existen devoluciones asociadas a este estado.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM estado_fisico WHERE id_estado = 2");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'El estado físico no debió ser eliminado.');
    }
}

