<?php

namespace tests\Integration\Devoluciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloDevoluciones;
use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloArticulosInventario;
use App\modelo\Conexion;
use PDO;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("devoluciones")]
class ModificarDevolucionIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloDevoluciones $modelo;

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

        $this->modelo = new ModeloDevoluciones();
        $this->modelo->setAsignaciones(new ModeloAsignaciones());
        $this->modelo->setEquipamientos(new ModeloArticulosInventario());

        $this->limpiarTablas();
        $this->sembrarDatosBase();
    }

    protected function tearDown(): void
    {
        if ($this->pdo) {
            $this->limpiarTablas();
            $reflector = new ReflectionClass(Conexion::class);
            $propiedad = $reflector->getProperty('instancia');
            $propiedad->setValue(null, null);
            $this->pdo = null;
        }
        parent::tearDown();
    }

    private function limpiarTablas(): void
    {
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE devoluciones;");
        $this->pdo->exec("TRUNCATE TABLE asignaciones;");
        $this->pdo->exec("TRUNCATE TABLE articulos_inventario;");
        $this->pdo->exec("TRUNCATE TABLE catalogo;");
        $this->pdo->exec("TRUNCATE TABLE categoria_catalogo;");
        $this->pdo->exec("TRUNCATE TABLE estado_fisico;");
        $this->pdo->exec("TRUNCATE TABLE inscripciones;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("TRUNCATE TABLE posiciones;");
        $this->pdo->exec("TRUNCATE TABLE categorias;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg')");

        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES (1, 'Protecciones', 'Categorico')");
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, codigo_posicion, talla) VALUES 
            (5, 'Casco Cannibals', 1, 1, NULL, 'L')");

        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
            (1, 'Excelente', 1),
            (2, 'Regular', 2),
            (3, 'Dañado', 3)");

        // Artículo 10 (disponible tras ser devuelto, estatus 1)
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_estado, id_catalogo, codigo_club, estatus) VALUES 
            (10, 1, 5, 'CL-0010', 1)");

        // Asignación 1 (estatus 2 = devuelta)
        $this->pdo->exec("INSERT INTO asignaciones (id_asignacion, codigo_atleta, codigo_articulo, fecha_asignacion, estatus) VALUES 
            (1, 1, 10, '2026-01-01', 2)");

        // Devolución 1
        $this->pdo->exec("INSERT INTO devoluciones (id_devolucion, id_asignacion, id_estado, fecha_devolucion, observacion) VALUES 
            (1, 1, 1, '2026-02-01', 'Recepción inicial en buen estado')");
    }

    public function testModificarFechaYObservacionExitosa(): void
    {
        $datos = [
            'accion'           => 'modificar',
            'id_devolucion'    => 1,
            'id_estado'        => 1,
            'fecha_devolucion' => '2026-02-15',
            'observacion'      => 'Observación corregida tras peritaje'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Modificación exitosa.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT fecha_devolucion, observacion, id_estado FROM devoluciones WHERE id_devolucion = 1");
        $dev = $stmt->fetch();
        $this->assertEquals('2026-02-15', $dev['fecha_devolucion']);
        $this->assertEquals('Observación corregida tras peritaje', $dev['observacion']);
        $this->assertEquals(1, $dev['id_estado']);
    }

    public function testModificarCambioDeEstadoFisicoActualizaCondicionEnInventario(): void
    {
        // Se reclasifica el implemento de Excelente (1) a Regular (2)
        $datos = [
            'accion'           => 'modificar',
            'id_devolucion'    => 1,
            'id_estado'        => 2,
            'fecha_devolucion' => '2026-02-01',
            'observacion'      => 'Reclasificado a regular'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificar que la devolución se actualizó a id_estado 2
        $stmtDev = $this->pdo->query("SELECT id_estado FROM devoluciones WHERE id_devolucion = 1");
        $this->assertEquals(2, $stmtDev->fetchColumn());

        // Verificar que la tabla articulos_inventario también se actualizó a id_estado 2
        $stmtArt = $this->pdo->query("SELECT id_estado FROM articulos_inventario WHERE codigo_articulo = 10");
        $this->assertEquals(2, $stmtArt->fetchColumn(), 'El artículo debe sincronizar su id_estado a 2.');
    }

    public function testModificarDevolucionRechazaIdInexistente(): void
    {
        $datos = [
            'accion'           => 'modificar',
            'id_devolucion'    => 9999,
            'id_estado'        => 1,
            'fecha_devolucion' => '2026-02-01',
            'observacion'      => 'Prueba no existe'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('no existe', $resultado['mensaje']);
    }
}

