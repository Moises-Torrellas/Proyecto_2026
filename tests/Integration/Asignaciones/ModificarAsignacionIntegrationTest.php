<?php

namespace tests\Integration\Asignaciones;

use PHPUnit\Framework\TestCase;
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
#[Group("asignaciones")]
class ModificarAsignacionIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloAsignaciones $modelo;

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

        $this->modelo = new ModeloAsignaciones();
        $this->modelo->setArticulos(new ModeloArticulosInventario());

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
        // Atletas
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2010-05-15', 'foto2.jpg')");

        // Categoría de catálogo
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES 
            (1, 'Material de Entrenamiento', 'Sin talla')");

        // Catálogo (artículos sin restricción de posición para pruebas generales de asignación)
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, codigo_posicion, talla) VALUES 
            (1, 'Cono de Entrenamiento', 5, 1, NULL, 'UNICA'),
            (2, 'Balon de Juego', 2, 1, NULL, 'UNICA')");

        // Estado físico
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
            (1, 'Excelente', 1),
            (2, 'Bueno', 2)");

        // Artículos en inventario:
        // Art 20: Ocupado por la asignación 1 (estatus 2)
        // Art 30: Disponible (estatus 1)
        // Art 50: Ocupado en otra asignación (estatus 2)
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_estado, id_catalogo, codigo_club, estatus) VALUES 
            (20, 1, 1, 'CL-0020', 2),
            (30, 2, 1, 'CL-0030', 1),
            (50, 1, 2, 'CL-0050', 2)");

        // Asignación 1 activa asignada al atleta 1 con artículo 20
        $this->pdo->exec("INSERT INTO asignaciones (id_asignacion, codigo_atleta, codigo_articulo, fecha_asignacion, estatus) VALUES 
            (1, 1, 20, '2026-02-10', 1)");

        // Asignación 2 inactiva (anulada = estatus 3)
        $this->pdo->exec("INSERT INTO asignaciones (id_asignacion, codigo_atleta, codigo_articulo, fecha_asignacion, estatus) VALUES 
            (2, 2, 50, '2026-02-01', 3)");
    }

    public function testModificarFechaAsignacionExitosa(): void
    {
        $datos = [
            'accion'           => 'modificar',
            'id_asignacion'    => '1',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '20',
            'fecha_asignacion' => '2026-05-15'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Modificación exitosa.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT fecha_asignacion, codigo_articulo FROM asignaciones WHERE id_asignacion = 1");
        $asig = $stmt->fetch();
        $this->assertEquals('2026-05-15', $asig['fecha_asignacion']);
        $this->assertEquals(20, $asig['codigo_articulo']);
    }

    public function testModificarAsignacionCambioDeArticuloExitosoConLiberacionYReasignacion(): void
    {
        // Asignación 1 tenía Art 20. Se cambia a Art 30 (que está libre).
        $datos = [
            'accion'           => 'modificar',
            'id_asignacion'    => '1',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '30',
            'fecha_asignacion' => '2026-05-20'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificar asignación actualizada
        $stmt = $this->pdo->query("SELECT codigo_articulo FROM asignaciones WHERE id_asignacion = 1");
        $this->assertEquals(30, $stmt->fetchColumn());

        // Verificar que el viejo artículo (20) se liberó (estatus 1)
        $stmtViejo = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 20");
        $this->assertEquals(1, $stmtViejo->fetchColumn(), 'El artículo anterior debe volver a estatus 1 (disponible).');

        // Verificar que el nuevo artículo (30) se ocupó (estatus 2)
        $stmtNuevo = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 30");
        $this->assertEquals(2, $stmtNuevo->fetchColumn(), 'El nuevo artículo debe pasar a estatus 2 (ocupado).');
    }

    public function testModificarAsignacionRechazaNuevoArticuloNoDisponible(): void
    {
        // Se intenta cambiar al Art 50 (que ya está ocupado con estatus 2)
        $datos = [
            'accion'           => 'modificar',
            'id_asignacion'    => '1',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '50',
            'fecha_asignacion' => '2026-05-20'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_EQUIPO_NO_DISPONIBLE', $resultado['codigo']);

        // La asignación debe mantener el artículo 20
        $stmt = $this->pdo->query("SELECT codigo_articulo FROM asignaciones WHERE id_asignacion = 1");
        $this->assertEquals(20, $stmt->fetchColumn());
    }

    public function testModificarAsignacionRechazaArticuloInexistente(): void
    {
        $datos = [
            'accion'           => 'modificar',
            'id_asignacion'    => '1',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '9999',
            'fecha_asignacion' => '2026-05-20'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_EQUIPO_NO_EXISTE', $resultado['codigo']);
    }

    public function testModificarAsignacionRechazaAsignacionInexistente(): void
    {
        $datos = [
            'accion'           => 'modificar',
            'id_asignacion'    => '9999',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '30',
            'fecha_asignacion' => '2026-05-20'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_NO_EXISTE', $resultado['codigo']);
    }

    public function testModificarAsignacionRechazaSiEstatusNoEsActivo(): void
    {
        // La asignación 2 tiene estatus 3 (anulada)
        $datos = [
            'accion'           => 'modificar',
            'id_asignacion'    => '2',
            'codigo_atleta'    => '2',
            'codigo_articulo'  => '30',
            'fecha_asignacion' => '2026-05-20'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_ESTATUS', $resultado['codigo']);
    }

    public function testModificarAsignacionIdInvalidoDisparaExcepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ID de asignación inválido.');

        $datos = [
            'accion'           => 'modificar',
            'id_asignacion'    => 'id_invalido',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '20',
            'fecha_asignacion' => '2026-05-20'
        ];

        $this->modelo->ProcesarDatos($datos);
    }
}
