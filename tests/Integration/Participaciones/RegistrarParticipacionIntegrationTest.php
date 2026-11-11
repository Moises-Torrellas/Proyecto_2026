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
class RegistrarParticipacionIntegrationTest extends TestCase
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
            (1, 'COPA ORO 2026', '{$fInicio}', '{$fFin}', 'Polideportivo Central', 1)");

        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES 
            (1, 'Cannibals Alfa'),
            (2, 'Cannibals Beta')");

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2009-05-10', 'foto1.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2009-08-15', 'foto2.jpg')");

        // El equipo 1 tiene a los atletas 1 y 2
        $this->pdo->exec("INSERT INTO detalles_equipos (codigo_equipo, codigo_atleta) VALUES 
            (1, 1),
            (1, 2)");
    }

    public function testRegistrarParticipacionExitosaConSnapshotDeAtletas(): void
    {
        $datos = [
            'accion'        => 'incluir',
            'codigo_torneo' => 1,
            'codigo_equipo' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertArrayHasKey('nuevo', $resultado);

        // Verificar inserción en participaciones
        $stmt = $this->pdo->query("SELECT * FROM participaciones WHERE codigo_torneo = 1 AND codigo_equipo = 1");
        $participacion = $stmt->fetch();
        $this->assertNotEmpty($participacion);
        $idPart = (int)$participacion['codigo_participacion'];

        // Verificar que se generó el snapshot en detalles_participacion con los atletas del equipo
        $stmtSnap = $this->pdo->prepare("SELECT codigo_atleta, goles, asistencias FROM detalles_participacion WHERE codigo_participacion = ? ORDER BY codigo_atleta ASC");
        $stmtSnap->execute([$idPart]);
        $atletasSnapshot = $stmtSnap->fetchAll();

        $this->assertCount(2, $atletasSnapshot, 'Debe crearse una fila por cada atleta en detalles_participacion.');
        $this->assertEquals(1, $atletasSnapshot[0]['codigo_atleta']);
        $this->assertEquals(0, $atletasSnapshot[0]['goles']);
        $this->assertEquals(2, $atletasSnapshot[1]['codigo_atleta']);
    }

    public function testRegistrarParticipacionSinAtletasInicialesExitoso(): void
    {
        // Equipo 2 no tiene atletas en detalles_equipos
        $datos = [
            'accion'        => 'incluir',
            'codigo_torneo' => 1,
            'codigo_equipo' => 2
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT codigo_participacion FROM participaciones WHERE codigo_torneo = 1 AND codigo_equipo = 2");
        $idPart = $stmt->fetchColumn();
        $this->assertNotEmpty($idPart);

        // Snapshot en detalles_participacion debe estar vacío (0 atletas)
        $stmtSnap = $this->pdo->prepare("SELECT COUNT(*) FROM detalles_participacion WHERE codigo_participacion = ?");
        $stmtSnap->execute([$idPart]);
        $this->assertEquals(0, $stmtSnap->fetchColumn());
    }

    public function testRegistrarParticipacionRechazaDuplicado(): void
    {
        // Registrar primero al equipo 1 en el torneo 1
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES (1, 1, 1)");

        // Intentar registrar de nuevo al equipo 1 en el torneo 1
        $datosDuplicado = [
            'accion'        => 'incluir',
            'codigo_torneo' => 1,
            'codigo_equipo' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datosDuplicado);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE, $resultado['codigo']);

        $stmtCount = $this->pdo->query("SELECT COUNT(*) FROM participaciones WHERE codigo_torneo = 1 AND codigo_equipo = 1");
        $this->assertEquals(1, $stmtCount->fetchColumn(), 'No deben existir participaciones duplicadas del mismo equipo en el mismo torneo.');
    }

    public function testRegistrarParticipacionRechazaTorneoInexistente(): void
    {
        $datos = [
            'accion'        => 'incluir',
            'codigo_torneo' => 9999,
            'codigo_equipo' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    public function testRegistrarParticipacionRechazaEquipoInexistente(): void
    {
        $datos = [
            'accion'        => 'incluir',
            'codigo_torneo' => 1,
            'codigo_equipo' => 9999
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '0', $resultado['codigo']);
    }

    public function testValidarParticipacionGrupalMetodoAuxiliar(): void
    {
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES (10, 1, 1)");

        $this->assertTrue($this->modelo->validarParticipacionGrupal(1, 1));
        $this->assertFalse($this->modelo->validarParticipacionGrupal(1, 2));
    }
}
