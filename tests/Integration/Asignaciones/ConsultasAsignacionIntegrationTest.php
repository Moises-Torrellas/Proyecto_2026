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
class ConsultasAsignacionIntegrationTest extends TestCase
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
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("TRUNCATE TABLE identidad_atleta;");
        $this->pdo->exec("TRUNCATE TABLE atleta_representante;");
        $this->pdo->exec("TRUNCATE TABLE representantes;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        // Atletas
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2010-05-15', 'foto2.jpg')");

        // Documentos de identidad (usados por vista_asignaciones_general)
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES 
            (1, 'V', '30123456'),
            (2, 'V', '29654321')");

        // Categoría y catálogo
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES 
            (1, 'Indumentaria', 'Categorico')");

        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, codigo_posicion, talla) VALUES 
            (1, 'Casco Cannibals', 2, 1, NULL, 'L'),
            (2, 'Balon Oficial', 3, 1, NULL, 'UNICA')");

        // Estado físico
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
            (1, 'Excelente', 1)");

        // Artículos de inventario
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_estado, id_catalogo, codigo_club, estatus) VALUES 
            (20, 1, 1, 'CL-0020', 2),
            (21, 1, 2, 'CL-0021', 2),
            (22, 1, 1, 'CL-0022', 2)");

        // Asignaciones:
        // Asignación 1: Atleta 1, Art 20, fecha 2026-03-01
        // Asignación 2: Atleta 1, Art 21, fecha 2026-05-15
        // Asignación 3: Atleta 2, Art 22, fecha 2026-05-20
        $this->pdo->exec("INSERT INTO asignaciones (id_asignacion, codigo_atleta, codigo_articulo, fecha_asignacion, estatus) VALUES 
            (1, 1, 20, '2026-03-01', 1),
            (2, 1, 21, '2026-05-15', 1),
            (3, 2, 22, '2026-05-20', 1)");
    }

    public function testConsultarAsignacionesGeneralAgrupadoPorAtleta(): void
    {
        $datos = ['accion' => 'consultar'];
        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertCount(2, $resultado['datos'], 'Deben existir 2 atletas con asignaciones.');

        // Encontrar al atleta Diego Reinoso
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
        $this->assertCount(2, $diego['asignaciones'], 'Diego Reinoso debe tener 2 artículos asignados.');
    }

    public function testConsultarAsignacionesConFiltroTexto(): void
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

    public function testConsultarAsignacionesConFiltroPorAtletaEspecifico(): void
    {
        $datos = [
            'accion'        => 'consultar',
            'codigo_atleta' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['codigo_atleta']);
        $this->assertCount(2, $resultado['datos'][0]['asignaciones']);
    }

    public function testConsultarAsignacionesConRangoDeFechas(): void
    {
        // Rango de mayo 2026: solo debe incluir la asignación 2 (Diego) y la asignación 3 (Carlos)
        $datos = [
            'accion'           => 'consultar',
            'fecha_asignacion' => '2026-05-01',
            'fecha_f'          => '2026-05-31'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);

        $totalAsignaciones = 0;
        foreach ($resultado['datos'] as $atleta) {
            $totalAsignaciones += count($atleta['asignaciones']);
        }
        $this->assertEquals(2, $totalAsignaciones, 'En mayo 2026 solo se realizaron 2 asignaciones.');
    }

    public function testBuscarAsignacionPorIdExistente(): void
    {
        // Vía ProcesarDatos con accion buscar
        $resultado = $this->modelo->ProcesarDatos(['accion' => 'buscar', 'id_asignacion' => '1']);
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['id_asignacion']);
        $this->assertEquals(20, $resultado['datos'][0]['codigo_articulo']);

        // Vía método directo Buscar(1)
        $resultadoDirecto = $this->modelo->Buscar(1);
        $this->assertEquals('buscar', $resultadoDirecto['accion']);
        $this->assertEquals(20, $resultadoDirecto['datos'][0]['codigo_articulo']);
    }

    public function testConsultarAsignacionesDevolucionRetornaEstructuraPlana(): void
    {
        $resultado = $this->modelo->ConsultarAsignacionesDevolucion();

        $this->assertIsArray($resultado);
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(3, $resultado['datos']);

        $primerFila = $resultado['datos'][0];
        $this->assertArrayHasKey('id_asignacion', $primerFila);
        $this->assertArrayHasKey('estatus_asignacion', $primerFila);
        $this->assertArrayHasKey('atleta', $primerFila);
        $this->assertArrayHasKey('articulo', $primerFila);
    }

    public function testProcesarDatosRechazaAccionInvalida(): void
    {
        $datos = ['accion' => 'accion_inexistente'];
        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_ACCION', $resultado['codigo']);
    }

    public function testProcesarDatosRechazaDatosVacios(): void
    {
        $resultado = $this->modelo->ProcesarDatos([]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_VACIO', $resultado['codigo']);
    }
}
