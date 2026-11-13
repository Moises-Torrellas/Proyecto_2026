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
class ModificarParticipacionIntegrationTest extends TestCase
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
            (1, 'Cannibals Alfa'),
            (2, 'Cannibals Beta')");

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2009-05-10', 'foto1.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2009-08-15', 'foto2.jpg'),
            (3, 'Juan', 'Perez', 'H', '2010-03-20', 'foto3.jpg')");

        // Equipo 1 tiene atletas 1 y 2
        $this->pdo->exec("INSERT INTO detalles_equipos (codigo_equipo, codigo_atleta) VALUES 
            (1, 1),
            (1, 2)");

        // Equipo 2 tiene al atleta 3
        $this->pdo->exec("INSERT INTO detalles_equipos (codigo_equipo, codigo_atleta) VALUES 
            (2, 3)");

        // Participación 1: Torneo 1, Equipo 1
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES (1, 1, 1)");

        // Snapshot inicial de participación 1
        $this->pdo->exec("INSERT INTO detalles_participacion (codigo_participacion, codigo_atleta, goles, asistencias) VALUES 
            (1, 1, 0, 0),
            (1, 2, 0, 0)");

        // Participación 2: Torneo 1, Equipo 2
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES (2, 1, 2)");
        $this->pdo->exec("INSERT INTO detalles_participacion (codigo_participacion, codigo_atleta, goles, asistencias) VALUES (2, 3, 0, 0)");
    }

    public function testModificarParticipacionCambioDeEquipoActualizaSnapshot(): void
    {
        // Se crea un tercer equipo con nuevo atleta para asignarlo a la participación 1
        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES (3, 'Cannibals Gamma')");
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (4, 'Pedro', 'Gomez', 'H', '2009-11-10', 'foto4.jpg')");
        $this->pdo->exec("INSERT INTO detalles_equipos (codigo_equipo, codigo_atleta) VALUES (3, 4)");

        $datos = [
            'accion'               => 'modificar',
            'codigo_participacion' => 1,
            'codigo_torneo'        => 1,
            'codigo_equipo'        => 3
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);

        // Verificar que la participación ahora tiene el equipo 3
        $stmt = $this->pdo->query("SELECT codigo_equipo FROM participaciones WHERE codigo_participacion = 1");
        $this->assertEquals(3, $stmt->fetchColumn());

        // Verificar que el snapshot de atletas se actualizó (se borraron 1 y 2, y ahora está 4)
        $stmtSnap = $this->pdo->query("SELECT codigo_atleta FROM detalles_participacion WHERE codigo_participacion = 1");
        $atletasSnapshot = $stmtSnap->fetchAll(PDO::FETCH_COLUMN, 0);
        $this->assertEquals([4], $atletasSnapshot, 'El snapshot de atletas debió actualizarse a los atletas del equipo 3.');
    }

    public function testModificarParticipacionMismoEquipoYTorneoExitoso(): void
    {
        $datos = [
            'accion'               => 'modificar',
            'codigo_participacion' => 1,
            'codigo_torneo'        => 1,
            'codigo_equipo'        => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT codigo_equipo FROM participaciones WHERE codigo_participacion = 1");
        $this->assertEquals(1, $stmt->fetchColumn());
    }

    public function testModificarParticipacionRechazaDuplicadoEnMismoTorneo(): void
    {
        // Intentar cambiar la participación 2 para que tenga el equipo 1 (que ya está en participación 1 en el torneo 1)
        $datos = [
            'accion'               => 'modificar',
            'codigo_participacion' => 2,
            'codigo_torneo'        => 1,
            'codigo_equipo'        => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE, $resultado['codigo']);

        // Verificar que la participación 2 conservó su equipo 2
        $stmt = $this->pdo->query("SELECT codigo_equipo FROM participaciones WHERE codigo_participacion = 2");
        $this->assertEquals(2, $stmt->fetchColumn());
    }

    public function testModificarParticipacionRechazaIdInexistente(): void
    {
        $datos = [
            'accion'               => 'modificar',
            'codigo_participacion' => 9999,
            'codigo_torneo'        => 1,
            'codigo_equipo'        => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '2', $resultado['codigo']);
    }

    public function testValidarParticipacionDuplicadaPropiaMetodoAuxiliar(): void
    {
        // En torneo 1: part 1 tiene eq 1, part 2 tiene eq 2
        // Verificar si eq 1 está duplicado para part 2 (sí, porque lo tiene part 1)
        $this->assertTrue($this->modelo->validarParticipacionDuplicadaPropia(1, 1, 2));

        // Verificar si eq 1 está duplicado para part 1 (no, es su propio registro)
        $this->assertFalse($this->modelo->validarParticipacionDuplicadaPropia(1, 1, 1));
    }
}
