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
class AnularDevolucionIntegrationTest extends TestCase
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
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2010-05-15', 'foto2.jpg')");

        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES (1, 'Protecciones', 'Categorico')");
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, codigo_posicion, talla) VALUES 
            (5, 'Casco Cannibals', 1, 1, NULL, 'L')");

        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
            (1, 'Excelente', 1),
            (2, 'Regular', 2),
            (3, 'Dañado', 3)");

        // Artículo 10 devuelto (estatus 1 = disponible)
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_estado, id_catalogo, codigo_club, estatus) VALUES 
            (10, 1, 5, 'CL-0010', 1)");

        // Asignación 1 (estatus 2 = devuelta)
        $this->pdo->exec("INSERT INTO asignaciones (id_asignacion, codigo_atleta, codigo_articulo, fecha_asignacion, estatus) VALUES 
            (1, 1, 10, '2026-01-01', 2)");

        // Devolución 1
        $this->pdo->exec("INSERT INTO devoluciones (id_devolucion, id_asignacion, id_estado, fecha_devolucion, observacion) VALUES 
            (1, 1, 1, '2026-02-01', 'Devolución previa')");
    }

    public function testAnularDevolucionRestauraAsignacionYMarcaArticuloComoOcupado(): void
    {
        $datos = [
            'accion'           => 'anular',
            'id_devolucion'    => 1,
            'motivo_anulacion' => 'Error de registro administrativo'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Anulación procesada, equipo reasignado.', $resultado['mensaje']);

        // 1. Verificar eliminación física del registro en devoluciones
        $stmtDev = $this->pdo->query("SELECT COUNT(*) FROM devoluciones WHERE id_devolucion = 1");
        $this->assertEquals(0, $stmtDev->fetchColumn(), 'El registro de devolución debió ser eliminado.');

        // 2. Verificar restauración de asignación a estatus 1 (activo)
        $stmtAsig = $this->pdo->query("SELECT estatus FROM asignaciones WHERE id_asignacion = 1");
        $this->assertEquals(1, $stmtAsig->fetchColumn(), 'La asignación debió volver a estatus activo (1).');

        // 3. Verificar restauración de artículo a estatus 2 (ocupado)
        $stmtArt = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 10");
        $this->assertEquals(2, $stmtArt->fetchColumn(), 'El artículo debió volver a estatus ocupado (2).');
    }

    public function testAnularDevolucionRechazaSiArticuloFueReasignadoAOtroAtleta(): void
    {
        // Simulamos que el artículo 10 fue reasignado posteriormente al atleta 2 en una nueva asignación activa
        $this->pdo->exec("INSERT INTO asignaciones (id_asignacion, codigo_atleta, codigo_articulo, fecha_asignacion, estatus) VALUES 
            (2, 2, 10, '2026-02-05', 1)");

        $datos = [
            'accion'           => 'anular',
            'id_devolucion'    => 1,
            'motivo_anulacion' => 'Intento de anulación con artículo ocupado'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('ya ha sido reasignado a otro atleta y está en uso', $resultado['mensaje']);

        // Comprobar que la devolución 1 NO fue eliminada
        $stmtDev = $this->pdo->query("SELECT COUNT(*) FROM devoluciones WHERE id_devolucion = 1");
        $this->assertEquals(1, $stmtDev->fetchColumn(), 'La devolución no debe borrarse si el artículo está en uso.');
    }

    public function testAnularDevolucionRechazaIdInexistente(): void
    {
        $datos = [
            'accion'           => 'anular',
            'id_devolucion'    => 9999,
            'motivo_anulacion' => 'No existe'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('ya no existe en el sistema', $resultado['mensaje']);
    }
}

