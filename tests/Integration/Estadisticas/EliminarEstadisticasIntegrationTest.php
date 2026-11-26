<?php

declare(strict_types=1);

namespace tests\Integration\Estadisticas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadisticas;
use App\modelo\ModeloParticipaciones;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("estadisticas")]
class EliminarEstadisticasIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloEstadisticas $modelo;
    private ModeloParticipaciones $modeloParticipaciones;

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

        $this->modelo = new ModeloEstadisticas();
        $this->modeloParticipaciones = new ModeloParticipaciones();
        $this->modelo->setParticipaciones($this->modeloParticipaciones);

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
        $this->pdo->exec("TRUNCATE TABLE identidad_atleta;");
        $this->pdo->exec("TRUNCATE TABLE atleta_representante;");
        $this->pdo->exec("TRUNCATE TABLE representantes;");
        $this->pdo->exec("TRUNCATE TABLE inscripciones;");
        $this->pdo->exec("TRUNCATE TABLE categorias;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("TRUNCATE TABLE equipos;");
        $this->pdo->exec("TRUNCATE TABLE torneos;");
        $this->pdo->exec("TRUNCATE TABLE premios;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        // 1. Torneo
        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (1, 'Copa Oro 2026', '2026-05-10', '2026-05-20', 'Gimnasio Techado', 3)");

        // 2. Equipos
        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES 
            (1, 'Cannibals Alfa')");

        // 3. Atletas
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2005-03-10', 'diego.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2006-08-20', 'carlos.jpg')");

        // 4. Participación
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES 
            (1, 1, 1)");

        // 5. Detalles de Participación con estadísticas activas
        $this->pdo->exec("INSERT INTO detalles_participacion 
            (codigo_dtll_prtc, codigo_participacion, codigo_atleta, goles, asistencias, penalizaciones, goles_contra, partidos_jugados, average, estatus_estadistica) VALUES 
            (1, 1, 1, 9, 4, 1, 0, 5, 1.80, 1),
            (2, 1, 2, 3, 1, 0, 0, 4, 0.75, 1)");
    }

    /**
     * ESC-INT-EST-03 / CP-INT-EST-17: Eliminación lógica exitosa de estadística deportiva en base de datos.
     * Verifica que estatus_estadistica se actualice a 0 y todas las métricas deportivas se reinicien a 0.
     */
    public function testEliminacionLogicaEstadisticaExitosa(): void
    {
        $datos = [
            'accion' => 'eliminar',
            'id'     => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);

        // Verificación directa en base de datos
        $stmt = $this->pdo->prepare("SELECT * FROM detalles_participacion WHERE codigo_dtll_prtc = 1");
        $stmt->execute();
        $estadistica = $stmt->fetch();

        $this->assertEquals(0, (int)$estadistica['estatus_estadistica'], 'El estatus debe ser 0 tras eliminación lógica.');
        $this->assertEquals(0, (int)$estadistica['goles'], 'Los goles deben reiniciarse a 0.');
        $this->assertEquals(0, (int)$estadistica['asistencias'], 'Las asistencias deben reiniciarse a 0.');
        $this->assertEquals(0, (int)$estadistica['penalizaciones'], 'Las penalizaciones deben reiniciarse a 0.');
        $this->assertEquals(0, (int)$estadistica['goles_contra'], 'Los goles en contra deben reiniciarse a 0.');
        $this->assertEquals(0, (int)$estadistica['partidos_jugados'], 'Los partidos jugados deben reiniciarse a 0.');
        $this->assertEquals('0.00', (string)$estadistica['average'], 'El average debe reiniciarse a 0.');
    }

    /**
     * ESC-INT-EST-03 / CP-INT-EST-18: Rechazo de eliminación cuando el ID de estadística no existe en la BD (INVALID_ID).
     */
    public function testErrorEliminarIdEstadisticaInexistente(): void
    {
        $datos = [
            'accion' => 'eliminar',
            'id'     => 99999 // ID inexistente
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);

        // Verificar que los registros existentes no sufrieron cambios
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM detalles_participacion WHERE estatus_estadistica = 1");
        $this->assertEquals(2, (int)$stmt->fetchColumn(), 'Los registros activos deben permanecer inalterados.');
    }

    /**
     * ESC-INT-EST-03 / CP-INT-EST-19: Verificación de exclusión en la consulta general tras la eliminación lógica.
     * Verifica que un registro con estatus_estadistica = 0 sea excluido del listado activo retornado por Consultar().
     */
    public function testRegistroEliminadoLogicamenteExcluidoDeConsultar(): void
    {
        // 1. Antes de eliminar, consultar() retorna 2 registros activos
        $antes = $this->modelo->Consultar([]);
        $this->assertCount(2, $antes['datos']);

        // 2. Eliminamos el registro 1
        $this->modelo->ProcesarDatos(['accion' => 'eliminar', 'id' => 1]);

        // 3. Después de eliminar, consultar() debe retornar solo 1 registro (el ID 2)
        $despues = $this->modelo->Consultar([]);
        $this->assertCount(1, $despues['datos']);
        $this->assertEquals(2, (int)$despues['datos'][0]['id_estadisticas']);
    }

    /**
     * ESC-INT-EST-03 / CP-INT-EST-20: Re-inclusión posterior exitosa de una estadística eliminada lógicamente.
     * Verifica que una tupla con estatus_estadistica = 0 pueda ser reactivada mediante 'incluir' sin incurrir en error DUPLICATE.
     */
    public function testReactivacionPosteriorDeEstadisticaEliminada(): void
    {
        // 1. Eliminación lógica del registro 1
        $this->modelo->ProcesarDatos(['accion' => 'eliminar', 'id' => 1]);

        // 2. Intento de re-inclusión para la misma participación y atleta
        $datosReinclusion = [
            'accion'         => 'incluir',
            'participacion'  => 1,
            'atleta'         => 1,
            'goles'          => 4,
            'asistencias'    => 2,
            'penalizaciones' => 0,
            'goles_c'        => 0,
            'partido'        => 2,
            'average'        => 2.00
        ];

        $resultado = $this->modelo->ProcesarDatos($datosReinclusion);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos que volvió a activarse en BD con las nuevas estadísticas
        $stmt = $this->pdo->query("SELECT * FROM detalles_participacion WHERE codigo_dtll_prtc = 1");
        $fila = $stmt->fetch();

        $this->assertEquals(1, (int)$fila['estatus_estadistica']);
        $this->assertEquals(4, (int)$fila['goles']);
        $this->assertEquals(2, (int)$fila['asistencias']);
        $this->assertEquals('2.00', (string)$fila['average']);
    }
}
