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
class ConsultasDevolucionIntegrationTest extends TestCase
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
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("TRUNCATE TABLE identidad_atleta;");
        $this->pdo->exec("TRUNCATE TABLE atleta_representante;");
        $this->pdo->exec("TRUNCATE TABLE representantes;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2010-05-15', 'foto2.jpg')");

        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES 
            (1, 'V', '30123456'),
            (2, 'V', '29654321')");

        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES (1, 'Protecciones', 'Categorico')");
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, codigo_posicion, talla) VALUES 
            (1, 'Casco Cannibals', 1, 1, NULL, 'L'),
            (2, 'Guantes Pro', 2, 1, NULL, 'M')");

        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
            (1, 'Excelente', 1),
            (2, 'Regular', 2),
            (3, 'Dañado', 3)");

        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_estado, id_catalogo, codigo_club, estatus) VALUES 
            (10, 1, 1, 'CL-0010', 1),
            (20, 2, 2, 'CL-0020', 1),
            (30, 1, 1, 'CL-0030', 1)");

        // Asignaciones previas
        $this->pdo->exec("INSERT INTO asignaciones (id_asignacion, codigo_atleta, codigo_articulo, fecha_asignacion, estatus) VALUES 
            (1, 1, 10, '2026-02-01', 2),
            (2, 1, 20, '2026-03-01', 2),
            (3, 2, 30, '2026-05-10', 2)");

        // Devoluciones registradas:
        // Dev 1: Diego, Art 10, Excelente, 2026-03-15
        // Dev 2: Diego, Art 20, Regular, 2026-05-20
        // Dev 3: Carlos, Art 30, Excelente, 2026-05-25
        $this->pdo->exec("INSERT INTO devoluciones (id_devolucion, id_asignacion, id_estado, fecha_devolucion, observacion) VALUES 
            (1, 1, 1, '2026-03-15', 'Devuelto impecable'),
            (2, 2, 2, '2026-05-20', 'Desgaste normal de uso'),
            (3, 3, 1, '2026-05-25', 'Sin novedades')");
    }

    public function testConsultarDevolucionesGeneralAgrupadoPorAtleta(): void
    {
        $datos = ['accion' => 'consultar'];
        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertCount(2, $resultado['datos'], 'Deben listarse 2 atletas con devoluciones.');

        $diego = null;
        foreach ($resultado['datos'] as $atleta) {
            if ($atleta['codigo_atleta'] == 1) {
                $diego = $atleta;
                break;
            }
        }

        $this->assertNotNull($diego);
        $this->assertEquals('Diego Reinoso', $diego['nombre_completo']);
        $this->assertEquals('30123456', $diego['doc_identidad']);
        $this->assertCount(2, $diego['devoluciones'], 'Diego debe tener 2 devoluciones asociadas.');
    }

    public function testConsultarDevolucionesConFiltroTexto(): void
    {
        $datos = [
            'accion' => 'consultar',
            'filtro' => 'Carlos'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Carlos Mendoza', $resultado['datos'][0]['nombre_completo']);
    }

    public function testConsultarDevolucionesConFiltroPorAtletaEspecifico(): void
    {
        $datos = [
            'accion'        => 'consultar',
            'codigo_atleta' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['codigo_atleta']);
        $this->assertCount(2, $resultado['datos'][0]['devoluciones']);
    }

    public function testConsultarDevolucionesConFiltroPorEstadoFisico(): void
    {
        // Filtrar por id_estado 2 (Regular): solo la devolución 2 de Diego
        $datos = [
            'accion'    => 'consultar',
            'id_estado' => 2
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertCount(1, $resultado['datos'][0]['devoluciones']);
        $this->assertEquals(2, $resultado['datos'][0]['devoluciones'][0]['id_estado']);
        $this->assertEquals('Regular', $resultado['datos'][0]['devoluciones'][0]['estado_fisico']);
    }

    public function testConsultarDevolucionesConRangoDeFechas(): void
    {
        // Rango de mayo: Devolución 2 (Diego) y Devolución 3 (Carlos)
        $datos = [
            'accion'      => 'consultar',
            'fecha_desde' => '2026-05-01',
            'fecha_hasta' => '2026-05-31'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $totalDevs = 0;
        foreach ($resultado['datos'] as $atleta) {
            $totalDevs += count($atleta['devoluciones']);
        }
        $this->assertEquals(2, $totalDevs, 'En mayo deben existir exactamente 2 devoluciones.');
    }

    public function testBuscarDevolucionPorIdConVistaAsignaciones(): void
    {
        $resultado = $this->modelo->Buscar(1);

        $this->assertIsArray($resultado);
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $dev = $resultado['datos'][0];
        $this->assertEquals('2026-03-15', $dev['fecha_devolucion']);
        $this->assertEquals('Devuelto impecable', $dev['observacion']);
        $this->assertEquals('Diego Reinoso', $dev['atleta']);
        $this->assertEquals('30123456', $dev['doc_identidad']);
        $this->assertEquals('Casco Cannibals', $dev['articulo']);
        $this->assertEquals('Excelente', $dev['estado_fisico']);
    }

    public function testProcesarDatosRechazaAccionInvalida(): void
    {
        $datos = ['accion' => 'accion_no_reconocida'];
        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Acción no válida', $resultado['codigo']);
    }
}

