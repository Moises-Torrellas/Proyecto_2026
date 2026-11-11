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
class RegistrarDevolucionIntegrationTest extends TestCase
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
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura) VALUES (3, 'Portero', 'PR')");
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES (7, 'U-17', 15, 17)");
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus) VALUES 
            (1, 1, 7, 3, 10, 70, 170, '2026-01-01', 1)");
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES (1, 'Protecciones', 'Categorico')");
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, codigo_posicion, talla) VALUES 
            (5, 'Pads Portero', 1, 1, 3, 'SENIOR')");

        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
            (1, 'Excelente', 1),
            (2, 'Regular', 2),
            (3, 'Dañado', 3)");

        // Artículo 10 ocupado (estatus 2)
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_estado, id_catalogo, codigo_club, estatus) VALUES 
            (10, 1, 5, 'CL-0010', 2),
            (20, 1, 5, 'CL-0020', 2)");

        // Asignación 1 activa (estatus 1)
        $this->pdo->exec("INSERT INTO asignaciones (id_asignacion, codigo_atleta, codigo_articulo, fecha_asignacion, estatus) VALUES 
            (1, 1, 10, '2026-01-01', 1),
            (2, 1, 20, '2026-01-01', 1)");
    }

    public function testIncluirDevolucionExitosaYLiberarArticuloEnBD(): void
    {
        $datos = [
            'accion'           => 'incluir',
            'id_asignacion'    => 1,
            'id_estado'        => 1,
            'observacion'      => 'Devuelto en óptimas condiciones',
            'fecha_devolucion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Devolución procesada y equipo liberado.', $resultado['mensaje']);
        $this->assertArrayHasKey('id_devolucion', $resultado);

        // 1. Verificamos que la devolución se insertó en devoluciones
        $stmtDev = $this->pdo->query("SELECT * FROM devoluciones WHERE id_asignacion = 1");
        $devolucionDB = $stmtDev->fetch();
        $this->assertNotEmpty($devolucionDB);
        $this->assertEquals(1, $devolucionDB['id_estado']);
        $this->assertEquals('Devuelto en óptimas condiciones', $devolucionDB['observacion']);

        // 2. Verificamos que el SP ProcesarDevolucionSegura actualizó la asignación a estatus 2 (devuelta)
        $stmtAsig = $this->pdo->query("SELECT estatus FROM asignaciones WHERE id_asignacion = 1");
        $this->assertEquals(2, $stmtAsig->fetchColumn(), 'El estatus de la asignación debe ser 2 (devuelta).');

        // 3. Verificamos que el trigger trg_despues_insertar_devolucion liberó el artículo a estatus 1 (disponible)
        $stmtArt = $this->pdo->query("SELECT estatus, id_estado FROM articulos_inventario WHERE codigo_articulo = 10");
        $artDB = $stmtArt->fetch();
        $this->assertEquals(1, $artDB['estatus'], 'El artículo debe haber sido liberado a estatus 1 (disponible).');
        $this->assertEquals(1, $artDB['id_estado']);
    }

    public function testIncluirDevolucionConCondicionDanadaActivaTriggerBloqueo(): void
    {
        // Se devuelve con estado físico 3 (Dañado, nivel_estado = 3)
        $datos = [
            'accion'           => 'incluir',
            'id_asignacion'    => 2,
            'id_estado'        => 3,
            'observacion'      => 'Pads rotos durante la práctica',
            'fecha_devolucion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // El trigger trg_bloquear_articulos_danados al detectar nivel_estado = 3,
        // fuerza estatus = 3 (Retirado / Dañado) en vez de dejarlo en 1 (Disponible)
        $stmtArt = $this->pdo->query("SELECT estatus, id_estado FROM articulos_inventario WHERE codigo_articulo = 20");
        $artDB = $stmtArt->fetch();
        $this->assertEquals(3, $artDB['estatus'], 'El trigger trg_bloquear_articulos_danados debió forzar estatus = 3.');
        $this->assertEquals(3, $artDB['id_estado']);
    }

    public function testIncluirDevolucionRechazaAsignacionInexistente(): void
    {
        $datos = [
            'accion'           => 'incluir',
            'id_asignacion'    => 9999,
            'id_estado'        => 1,
            'observacion'      => 'Intento inválido',
            'fecha_devolucion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('no existe, no es válida o ya fue devuelta', $resultado['mensaje']);
    }

    public function testIncluirDevolucionRechazaAsignacionYaDevueltaOInactiva(): void
    {
        // Primero devolvemos la asignación 1 exitosamente
        $this->modelo->ProcesarDatos([
            'accion'           => 'incluir',
            'id_asignacion'    => 1,
            'id_estado'        => 1,
            'observacion'      => 'Primera devolución',
            'fecha_devolucion' => date('Y-m-d')
        ]);

        // Intentar devolver nuevamente la misma asignación 1
        $datosRepetidos = [
            'accion'           => 'incluir',
            'id_asignacion'    => 1,
            'id_estado'        => 1,
            'observacion'      => 'Segunda devolución ilegal',
            'fecha_devolucion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datosRepetidos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('no existe, no es válida o ya fue devuelta', $resultado['mensaje']);
    }

    public function testIncluirDevolucionConObservacionesVaciasOpcionales(): void
    {
        $datos = [
            'accion'           => 'incluir',
            'id_asignacion'    => 1,
            'id_estado'        => 2,
            'observacion'      => '',
            'fecha_devolucion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT observacion, id_estado FROM devoluciones WHERE id_asignacion = 1");
        $fila = $stmt->fetch();
        $this->assertEquals('', $fila['observacion']);
        $this->assertEquals(2, $fila['id_estado']);
    }
}

