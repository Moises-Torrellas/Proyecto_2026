<?php

namespace tests\Integration\Participaciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloParticipaciones;
use App\modelo\Conexion;
use PDO;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("participaciones")]
class EliminarParticipacionIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloParticipaciones $modelo;

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

        $this->modelo = new ModeloParticipaciones();

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
        $this->pdo->exec("TRUNCATE TABLE palmares_individual;");
        $this->pdo->exec("TRUNCATE TABLE palmares_grupal;");
        $this->pdo->exec("TRUNCATE TABLE detalles_participacion;");
        $this->pdo->exec("TRUNCATE TABLE participaciones;");
        $this->pdo->exec("TRUNCATE TABLE detalles_equipos;");
        $this->pdo->exec("TRUNCATE TABLE equipos;");
        $this->pdo->exec("TRUNCATE TABLE torneos;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        $fInicio = date('Y-m-d', strtotime('+10 days'));
        $fFin    = date('Y-m-d', strtotime('+20 days'));

        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (1, 'COPA NACIONAL 2026', '{$fInicio}', '{$fFin}', 'Polideportivo Central', 1)");

        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES 
            (1, 'Cannibals Alfa')");

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2009-05-10', 'foto1.jpg')");

        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES (1, 1, 1)");

        $this->pdo->exec("INSERT INTO detalles_participacion (codigo_participacion, codigo_atleta, goles, asistencias) VALUES (1, 1, 2, 1)");
    }

    public function testEliminarParticipacionExitosaConLimpiezaEnCascada(): void
    {
        $datos = [
            'accion'               => 'eliminar',
            'codigo_participacion' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);

        // Verificar eliminación de la fila en participaciones
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM participaciones WHERE codigo_participacion = 1");
        $this->assertEquals(0, $stmt->fetchColumn());

        // Verificar limpieza en detalles_participacion
        $stmtSnap = $this->pdo->query("SELECT COUNT(*) FROM detalles_participacion WHERE codigo_participacion = 1");
        $this->assertEquals(0, $stmtSnap->fetchColumn(), 'Los detalles de la participación deben ser eliminados.');
    }

    public function testEliminarParticipacionRechazaIdInexistente(): void
    {
        $datos = [
            'accion'               => 'eliminar',
            'codigo_participacion' => 9999
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    public function testValidarParticipacionIndividualMetodoAuxiliar(): void
    {
        // En participación 1 está el atleta 1
        $this->assertTrue($this->modelo->validarParticipacionIndividual(1, 1));
        $this->assertFalse($this->modelo->validarParticipacionIndividual(1, 99));
    }
}
