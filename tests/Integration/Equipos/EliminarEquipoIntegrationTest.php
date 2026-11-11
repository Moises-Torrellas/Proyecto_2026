<?php

namespace tests\Integration\Equipos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEquipos;
use App\modelo\Conexion;
use PDO;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("equipos")]
class EliminarEquipoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloEquipos $modelo;

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

        $this->modelo = new ModeloEquipos();

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
        $this->pdo->exec("TRUNCATE TABLE participaciones;");
        $this->pdo->exec("TRUNCATE TABLE torneos;");
        $this->pdo->exec("TRUNCATE TABLE detalles_equipos;");
        $this->pdo->exec("TRUNCATE TABLE equipos;");
        $this->pdo->exec("TRUNCATE TABLE inscripciones;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2009-05-10', 'foto1.jpg')");

        // Equipos
        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES 
            (1, 'Cannibals Eliminable'),
            (2, 'Cannibals En Torneo')");

        // Detalles del equipo 1
        $this->pdo->exec("INSERT INTO detalles_equipos (codigo_equipo, codigo_atleta) VALUES (1, 1)");

        // Torneo y participación para equipo 2
        $fInicio = date('Y-m-d', strtotime('+5 days'));
        $fFin    = date('Y-m-d', strtotime('+15 days'));
        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (10, 'TORNEO CLAUSURA', '{$fInicio}', '{$fFin}', 'Cancha 1', 1)");

        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES 
            (1, 10, 2)");
    }

    public function testEliminarEquipoSinDependenciasExitoso(): void
    {
        $datos = [
            'accion' => 'eliminar',
            'id'     => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('eliminar', $resultado['accion']);
        $this->assertEquals('Equipo eliminado correctamente.', $resultado['mensaje']);

        // Verificar eliminación en tabla equipos
        $stmtEq = $this->pdo->query("SELECT COUNT(*) FROM equipos WHERE codigo_equipo = 1");
        $this->assertEquals(0, $stmtEq->fetchColumn());

        // Verificar eliminación en cascada de detalles_equipos
        $stmtDet = $this->pdo->query("SELECT COUNT(*) FROM detalles_equipos WHERE codigo_equipo = 1");
        $this->assertEquals(0, $stmtDet->fetchColumn(), 'Los detalles del equipo deben eliminarse.');
    }

    public function testEliminarEquipoRechazaIdInexistente(): void
    {
        $datos = [
            'accion' => 'eliminar',
            'id'     => 9999
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['mensaje']);
    }

    public function testEliminarEquipoConParticipacionesEnTorneosRechaza(): void
    {
        // Equipo 2 está registrado en participaciones
        $datos = [
            'accion' => 'eliminar',
            'id'     => 2
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['mensaje']);

        // Verificar que el equipo 2 sigue existiendo
        $stmtEq = $this->pdo->query("SELECT COUNT(*) FROM equipos WHERE codigo_equipo = 2");
        $this->assertEquals(1, $stmtEq->fetchColumn());
    }

    public function testEliminarEquipoIdInvalidoDisparaExcepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        $datos = [
            'accion' => 'eliminar',
            'id'     => 'id_no_valido'
        ];

        $this->modelo->ProcesarDatos($datos);
    }
}

