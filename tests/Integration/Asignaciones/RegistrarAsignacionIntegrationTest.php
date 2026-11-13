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
class RegistrarAsignacionIntegrationTest extends TestCase
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
        $this->pdo->exec("TRUNCATE TABLE identidad_atleta;");
        $this->pdo->exec("TRUNCATE TABLE atleta_representante;");
        $this->pdo->exec("TRUNCATE TABLE representantes;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        // Posiciones
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura) VALUES 
            (1, 'Defensa', 'DEF'),
            (2, 'Delantero', 'DEL'),
            (3, 'Portero', 'POR')");

        // Categorías de atleta
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES 
            (7, 'U-17', 15, 17)");

        // Atletas
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2010-05-15', 'foto2.jpg')");

        // Inscripciones activas de atletas (Atleta 1 es Portero pos 3)
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus) VALUES 
            (1, 1, 7, 3, 10, 70, 170, '2026-01-01', 1),
            (2, 2, 7, 2, 9, 65, 168, '2026-01-01', 1)");

        // Categoría de catálogo
        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES 
            (1, 'Protecciones', 'Categorico'),
            (2, 'Balones', 'Sin talla')");

        // Catálogo:
        // Item 5: Requiere posición 3 (Portero)
        // Item 6: Sin restricción de posición (codigo_posicion = NULL)
        // Item 7: Requiere posición 2 (Delantero)
        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, codigo_posicion, talla) VALUES 
            (5, 'Pads Portero', 1, 1, 3, 'SENIOR'),
            (6, 'Balon Oficial', 2, 2, NULL, 'UNICA'),
            (7, 'Pechera Delantero', 1, 1, 2, 'M')");

        // Estado físico
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
            (1, 'Excelente', 1),
            (2, 'Bueno', 2)");

        // Artículos en inventario:
        // Art 10: Disponible (estatus 1), Cat 5 (Portero)
        // Art 20: Disponible (estatus 1), Cat 6 (General)
        // Art 30: Disponible (estatus 1), Cat 6 (General)
        // Art 40: Disponible (estatus 1), Cat 7 (Delantero)
        // Art 50: Ocupado/Asignado (estatus 2), Cat 6 (General)
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_estado, id_catalogo, codigo_club, estatus) VALUES 
            (10, 1, 5, 'CL-0010', 1),
            (20, 1, 6, 'CL-0020', 1),
            (30, 2, 6, 'CL-0030', 1),
            (40, 1, 7, 'CL-0040', 1),
            (50, 1, 6, 'CL-0050', 2)");
    }

    public function testRegistrarAsignacionIndividualExitosaEnBaseDeDatos(): void
    {
        $fechaHoy = date('Y-m-d');
        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '10',
            'fecha_asignacion' => $fechaHoy
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Asignación procesada.', $resultado['mensaje']);
        $this->assertEquals(1, $resultado['total']);

        // Verificar inserción en asignaciones
        $stmt = $this->pdo->query("SELECT * FROM asignaciones WHERE codigo_atleta = 1 AND codigo_articulo = 10");
        $asig = $stmt->fetch();
        $this->assertNotEmpty($asig, 'La asignación individual debe persistirse en BD.');
        $this->assertEquals(1, $asig['estatus'], 'El estatus de la asignación debe ser 1 (activa).');
        $this->assertEquals($fechaHoy, $asig['fecha_asignacion']);

        // Verificar cambio de estatus en articulos_inventario a 2 (ocupado)
        $stmtArt = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 10");
        $this->assertEquals(2, $stmtArt->fetchColumn(), 'El artículo debe pasar a estatus 2 (ocupado).');
    }

    public function testRegistrarAsignacionSinRestriccionDePosicionExitosa(): void
    {
        $fechaHoy = date('Y-m-d');
        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '20',
            'fecha_asignacion' => $fechaHoy
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmtArt = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 20");
        $this->assertEquals(2, $stmtArt->fetchColumn());
    }

    public function testRegistrarAsignacionMultiplePorLoteExitosa(): void
    {
        $fechaHoy = date('Y-m-d');
        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '1',
            'articulos'        => ['20', '30'],
            'fecha_asignacion' => $fechaHoy
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals(2, $resultado['total']);

        $stmtAsig = $this->pdo->query("SELECT * FROM asignaciones WHERE codigo_atleta = 1 ORDER BY codigo_articulo ASC");
        $asigs = $stmtAsig->fetchAll();
        $this->assertCount(2, $asigs);

        $stmtArts = $this->pdo->query("SELECT codigo_articulo, estatus FROM articulos_inventario WHERE codigo_articulo IN (20, 30)");
        $arts = $stmtArts->fetchAll();
        foreach ($arts as $art) {
            $this->assertEquals(2, $art['estatus'], "El artículo {$art['codigo_articulo']} debe tener estatus 2.");
        }
    }

    public function testRegistrarAsignacionRechazaArticuloOcupado(): void
    {
        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '50',
            'fecha_asignacion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_EQUIPO_OCUPADO', $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM asignaciones WHERE codigo_articulo = 50");
        $this->assertEquals(0, $stmt->fetchColumn(), 'No debe registrarse asignación si el equipo está ocupado.');
    }

    public function testRegistrarAsignacionRechazaAtletaInexistente(): void
    {
        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '999',
            'codigo_articulo'  => '20',
            'fecha_asignacion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_ATLETA_NO_EXISTE', $resultado['codigo']);

        // El artículo 20 debe permanecer disponible
        $stmtArt = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 20");
        $this->assertEquals(1, $stmtArt->fetchColumn());
    }

    public function testRegistrarAsignacionRechazaArticuloInexistente(): void
    {
        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '9999',
            'fecha_asignacion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_EQUIPO_NO_EXISTE', $resultado['codigo']);
    }

    public function testRegistrarAsignacionRechazaPosicionIncompatible(): void
    {
        // Atleta 1 es Portero (posicion 3), pero el artículo 40 (Pechera) requiere posición 2 (Delantero)
        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '40',
            'fecha_asignacion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_POSICION_NO_COINCIDE', $resultado['codigo']);

        // El artículo 40 debe seguir disponible
        $stmtArt = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 40");
        $this->assertEquals(1, $stmtArt->fetchColumn());
    }

    public function testRegistrarAsignacionPorLoteRevierteTransaccionSiUnoFalla(): void
    {
        // Lote con Art 20 (disponible) y Art 50 (ocupado)
        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '1',
            'articulos'        => ['20', '50'],
            'fecha_asignacion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_EQUIPO_OCUPADO', $resultado['codigo']);

        // Atomicidad: Art 20 no debe haberse asignado ni cambiado de estatus
        $stmtAsig = $this->pdo->query("SELECT COUNT(*) FROM asignaciones WHERE codigo_articulo = 20");
        $this->assertEquals(0, $stmtAsig->fetchColumn(), 'Rollback falló: se insertó asignación parcial.');

        $stmtArt20 = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 20");
        $this->assertEquals(1, $stmtArt20->fetchColumn(), 'Rollback falló: el artículo 20 cambió a ocupado.');
    }

    public function testRegistrarAsignacionRechazaFechaAnteriorAHoy(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La fecha de la nueva asignación no puede ser menor al día actual.');

        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '20',
            'fecha_asignacion' => '2024-05-10'
        ];

        $this->modelo->ProcesarDatos($datos);
    }
}
