<?php

declare(strict_types=1);

namespace tests\Integration\Estadisticas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadisticas;
use App\modelo\ModeloParticipaciones;
use App\modelo\Conexion;
use Exception;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("estadisticas")]
class ModificarEstadisticasIntegrationTest extends TestCase
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
        // 1. Torneos
        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (1, 'Copa Oro 2026', '2026-05-10', '2026-05-20', 'Gimnasio Techado', 3),
            (2, 'Liga Clausura 2026', '2026-11-01', '2026-11-15', 'Polideportivo Central', 1)");

        // 2. Equipos
        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES 
            (1, 'Cannibals Alfa'),
            (2, 'Titanes del Este')");

        // 3. Atletas
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2005-03-10', 'diego.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2006-08-20', 'carlos.jpg'),
            (3, 'Luis', 'Navarro', 'H', '2007-01-15', 'luis.jpg')");

        // 4. Participaciones
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES 
            (1, 1, 1),
            (2, 1, 2)");

        // 5. Detalles de Participación con estadísticas iniciales activas
        // Detalle 1: Part 1, Atleta 1 (activa con datos)
        // Detalle 2: Part 1, Atleta 2 (activa con datos)
        // Detalle 3: Part 2, Atleta 2 (inactiva)
        $this->pdo->exec("INSERT INTO detalles_participacion 
            (codigo_dtll_prtc, codigo_participacion, codigo_atleta, goles, asistencias, penalizaciones, goles_contra, partidos_jugados, average, estatus_estadistica) VALUES 
            (1, 1, 1, 4, 2, 1, 0, 3, 1.33, 1),
            (2, 1, 2, 1, 0, 0, 0, 3, 0.33, 1),
            (3, 2, 2, 0, 0, 0, 0, 0, 0.00, 0)");
    }

    /**
     * ESC-INT-EST-02 / CP-INT-EST-09: Modificación exitosa de estadísticas deportivas existentes en base de datos.
     * Verifica la actualización efectiva de las métricas en la tabla detalles_participacion y el commit transaccional.
     */
    public function testModificacionEstadisticaExitosa(): void
    {
        $datos = [
            'accion'         => 'modificar',
            'id'             => 1,
            'participacion'  => 1,
            'atleta'         => 1,
            'goles'          => 8,
            'asistencias'    => 5,
            'penalizaciones' => 2,
            'goles_c'        => 1,
            'partido'        => 5,
            'average'        => 1.60
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);

        // Verificación en base de datos
        $stmt = $this->pdo->prepare("SELECT * FROM detalles_participacion WHERE codigo_dtll_prtc = 1");
        $stmt->execute();
        $estadistica = $stmt->fetch();

        $this->assertEquals(1, (int)$estadistica['estatus_estadistica']);
        $this->assertEquals(8, (int)$estadistica['goles']);
        $this->assertEquals(5, (int)$estadistica['asistencias']);
        $this->assertEquals(2, (int)$estadistica['penalizaciones']);
        $this->assertEquals(1, (int)$estadistica['goles_contra']);
        $this->assertEquals(5, (int)$estadistica['partidos_jugados']);
        $this->assertEquals('1.60', (string)$estadistica['average']);
    }

    /**
     * ESC-INT-EST-02 / CP-INT-EST-10: Modificación conservando su propia asignación de participación y atleta.
     * Verifica que el método validarEstadisticaDuplicadaPropia excluya el ID actual y no genere falsos positivos.
     */
    public function testModificacionConservandoMismoAtletaYParticipacion(): void
    {
        $datos = [
            'accion'         => 'modificar',
            'id'             => 1,
            'participacion'  => 1,
            'atleta'         => 1,
            'goles'          => 12,
            'asistencias'    => 7,
            'penalizaciones' => 0,
            'goles_c'        => 0,
            'partido'        => 6,
            'average'        => 2.00
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->prepare("SELECT goles, average FROM detalles_participacion WHERE codigo_dtll_prtc = 1");
        $stmt->execute();
        $fila = $stmt->fetch();

        $this->assertEquals(12, (int)$fila['goles']);
        $this->assertEquals('2.00', (string)$fila['average']);
    }

    /**
     * ESC-INT-EST-02 / CP-INT-EST-11: Rechazo de modificación cuando el ID de detalle participación no existe en la BD (INVALID_ID . '2').
     */
    public function testErrorModificarIdEstadisticaInexistente(): void
    {
        $datos = [
            'accion'         => 'modificar',
            'id'             => 99999, // ID inexistente
            'participacion'  => 1,
            'atleta'         => 1,
            'goles'          => 3,
            'asistencias'    => 1,
            'penalizaciones' => 0,
            'goles_c'        => 0,
            'partido'        => 2,
            'average'        => 1.50
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '2', $resultado['codigo']);
    }

    /**
     * ESC-INT-EST-02 / CP-INT-EST-12: Rechazo de modificación cuando la participación no existe en la BD (INVALID_ID).
     */
    public function testErrorModificarParticipacionInexistente(): void
    {
        $datos = [
            'accion'         => 'modificar',
            'id'             => 1,
            'participacion'  => 99999, // Participación inexistente
            'atleta'         => 1,
            'goles'          => 3,
            'asistencias'    => 1,
            'penalizaciones' => 0,
            'goles_c'        => 0,
            'partido'        => 2,
            'average'        => 1.50
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    /**
     * ESC-INT-EST-02 / CP-INT-EST-13: Rechazo de modificación cuando el atleta no existe en la BD (INVALID_ID . '1').
     */
    public function testErrorModificarAtletaInexistente(): void
    {
        $datos = [
            'accion'         => 'modificar',
            'id'             => 1,
            'participacion'  => 1,
            'atleta'         => 99999, // Atleta inexistente
            'goles'          => 3,
            'asistencias'    => 1,
            'penalizaciones' => 0,
            'goles_c'        => 0,
            'partido'        => 2,
            'average'        => 1.50
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '1', $resultado['codigo']);
    }

    /**
     * ESC-INT-EST-02 / CP-INT-EST-14: Rechazo de modificación cuando el atleta no está asignado a la participación (EMPTY_SELECTION).
     */
    public function testErrorModificarAtletaNoPerteneceAParticipacion(): void
    {
        // Atleta 3 no pertenece a Participación 1
        $datos = [
            'accion'         => 'modificar',
            'id'             => 1,
            'participacion'  => 1,
            'atleta'         => 3,
            'goles'          => 4,
            'asistencias'    => 2,
            'penalizaciones' => 0,
            'goles_c'        => 0,
            'partido'        => 3,
            'average'        => 1.33
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(EMPTY_SELECTION, $resultado['codigo']);
    }

    /**
     * ESC-INT-EST-02 / CP-INT-EST-15: Rechazo de modificación cuando colisiona con otro registro existente en detalles_participacion (DUPLICATE).
     */
    public function testErrorModificarColisionConOtraEstadisticaExistente(): void
    {
        // Se intenta modificar el Detalle 1 (Part 1, Atleta 1) para que tenga los valores (Part 1, Atleta 2),
        // los cuales ya corresponden al Detalle 2.
        $datos = [
            'accion'         => 'modificar',
            'id'             => 1,
            'participacion'  => 1,
            'atleta'         => 2,
            'goles'          => 5,
            'asistencias'    => 2,
            'penalizaciones' => 0,
            'goles_c'        => 0,
            'partido'        => 3,
            'average'        => 1.67
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE, $resultado['codigo']);

        // Verificamos que el Detalle 1 no fue alterado
        $stmt = $this->pdo->query("SELECT codigo_atleta, goles FROM detalles_participacion WHERE codigo_dtll_prtc = 1");
        $fila = $stmt->fetch();
        $this->assertEquals(1, (int)$fila['codigo_atleta']);
        $this->assertEquals(4, (int)$fila['goles']);
    }

    /**
     * ESC-INT-EST-02 / CP-INT-EST-16: Modificación exitosa con soporte transicional de clave 'torneo' en lugar de 'participacion'.
     */
    public function testModificacionSoporteTransicionalTorneo(): void
    {
        $datos = [
            'accion'         => 'modificar',
            'id'             => 1,
            'torneo'         => 1, // En lugar de participacion
            'atleta'         => 1,
            'goles'          => 6,
            'asistencias'    => 4,
            'penalizaciones' => 1,
            'goles_c'        => 0,
            'partido'        => 4,
            'average'        => 1.50
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT goles, average FROM detalles_participacion WHERE codigo_dtll_prtc = 1");
        $fila = $stmt->fetch();
        $this->assertEquals(6, (int)$fila['goles']);
    }
}
