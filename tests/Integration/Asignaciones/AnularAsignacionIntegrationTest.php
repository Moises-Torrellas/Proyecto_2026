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
class AnularAsignacionIntegrationTest extends TestCase
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
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto1.jpg')");

        $this->pdo->exec("INSERT INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES 
            (1, 'Material de Entrenamiento', 'Sin talla')");

        $this->pdo->exec("INSERT INTO catalogo (id_catalogo, nombre, stock_minimo, id_categoria, codigo_posicion, talla) VALUES 
            (1, 'Cono de Entrenamiento', 5, 1, NULL, 'UNICA')");

        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
            (1, 'Excelente', 1)");

        // Art 20 en estatus 2 (ocupado por asignación 1)
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_estado, id_catalogo, codigo_club, estatus) VALUES 
            (20, 1, 1, 'CL-0020', 2)");

        // Asignación 1 activa
        $this->pdo->exec("INSERT INTO asignaciones (id_asignacion, codigo_atleta, codigo_articulo, fecha_asignacion, estatus) VALUES 
            (1, 1, 20, '2026-02-10', 1)");
    }

    public function testAnularAsignacionExitosaYLiberaArticuloEnInventario(): void
    {
        $datos = [
            'accion'        => 'anular',
            'id_asignacion' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Asignación anulada.', $resultado['mensaje']);

        // Verificar que la asignación quedó en estatus 3 (anulada)
        $stmt = $this->pdo->query("SELECT estatus FROM asignaciones WHERE id_asignacion = 1");
        $this->assertEquals(3, $stmt->fetchColumn(), 'El estatus de la asignación debe ser 3 (anulada).');

        // Verificar que el artículo en articulos_inventario quedó liberado (estatus 1)
        $stmtArt = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 20");
        $this->assertEquals(1, $stmtArt->fetchColumn(), 'El artículo debe haber sido liberado a estatus 1 (disponible).');
    }

    public function testAnularAsignacionRechazaIdInexistente(): void
    {
        $datos = [
            'accion'        => 'anular',
            'id_asignacion' => '9999'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_NO_EXISTE', $resultado['codigo']);
    }

    public function testAnularAsignacionIdInvalidoDisparaExcepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ID de asignación inválido.');

        $datos = [
            'accion'        => 'anular',
            'id_asignacion' => 'invalido'
        ];

        $this->modelo->ProcesarDatos($datos);
    }

    public function testCambiarEstatusAsignacionMetodoDirectoPersisteEnBD(): void
    {
        // Cambiar estatus de asignación 1 a 2 (ej: devuelta)
        $exito = $this->modelo->CambiarEstatusAsignacion(1, 2);

        $this->assertTrue($exito);

        $stmt = $this->pdo->query("SELECT estatus FROM asignaciones WHERE id_asignacion = 1");
        $this->assertEquals(2, $stmt->fetchColumn());
    }
}
