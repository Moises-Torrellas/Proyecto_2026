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
class RegistrarEstadisticasIntegrationTest extends TestCase
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

        // 5. Detalles de Participación iniciales (estatus_estadistica = 0, sin estadísticas registradas aún)
        // Atleta 1 en Participación 1
        // Atleta 2 en Participación 1
        // Atleta 2 en Participación 2
        // Nota: Atleta 3 no tiene detalle de participación registrado
        $this->pdo->exec("INSERT INTO detalles_participacion 
            (codigo_dtll_prtc, codigo_participacion, codigo_atleta, goles, asistencias, penalizaciones, goles_contra, partidos_jugados, average, estatus_estadistica) VALUES 
            (1, 1, 1, 0, 0, 0, 0, 0, 0.00, 0),
            (2, 1, 2, 0, 0, 0, 0, 0, 0.00, 0),
            (3, 2, 2, 0, 0, 0, 0, 0, 0.00, 0)");
    }

    /**
     * ESC-INT-EST-01 / CP-INT-EST-01: Registro exitoso de estadísticas deportivas con persistencia real en BD.
     * Verifica la actualización de detalles_participacion, activación del estatus_estadistica = 1 y asignación del ID.
     */
    public function testRegistroEstadisticaExitoso(): void
    {
        $datos = [
            'accion'         => 'incluir',
            'participacion'  => 1,
            'atleta'         => 1,
            'goles'          => 5,
            'asistencias'    => 3,
            'penalizaciones' => 1,
            'goles_c'        => 0,
            'partido'        => 4,
            'average'        => 1.25
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);

        // Verificación directa en base de datos
        $stmt = $this->pdo->prepare("SELECT * FROM detalles_participacion WHERE codigo_participacion = :p AND codigo_atleta = :a");
        $stmt->execute([':p' => 1, ':a' => 1]);
        $estadistica = $stmt->fetch();

        $this->assertNotEmpty($estadistica, 'El registro de estadística debe existir en detalles_participacion.');
        $this->assertEquals(1, (int)$estadistica['estatus_estadistica'], 'El estatus_estadistica debe activarse en 1.');
        $this->assertEquals(5, (int)$estadistica['goles']);
        $this->assertEquals(3, (int)$estadistica['asistencias']);
        $this->assertEquals(1, (int)$estadistica['penalizaciones']);
        $this->assertEquals(0, (int)$estadistica['goles_contra']);
        $this->assertEquals(4, (int)$estadistica['partidos_jugados']);
        $this->assertEquals('1.25', (string)$estadistica['average']);
        $this->assertEquals(1, (int)$this->modelo->getId());
    }

    /**
     * ESC-INT-EST-01 / CP-INT-EST-02: Registro exitoso utilizando parámetro transicional 'torneo' como participación.
     * Verifica que el modelo sea compatible si el cliente envía 'torneo' en vez de 'participacion'.
     */
    public function testRegistroEstadisticaSoporteTransicionalTorneo(): void
    {
        $datos = [
            'accion'         => 'incluir',
            'torneo'         => 1, // Enviado como alias de participación
            'atleta'         => 2,
            'goles'          => 2,
            'asistencias'    => 1,
            'penalizaciones' => 0,
            'goles_c'        => 0,
            'partido'        => 2,
            'average'        => 1.00
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->prepare("SELECT * FROM detalles_participacion WHERE codigo_participacion = :p AND codigo_atleta = :a");
        $stmt->execute([':p' => 1, ':a' => 2]);
        $estadistica = $stmt->fetch();

        $this->assertNotEmpty($estadistica);
        $this->assertEquals(1, (int)$estadistica['estatus_estadistica']);
        $this->assertEquals(2, (int)$estadistica['goles']);
        $this->assertEquals(2, (int)$this->modelo->getId());
    }

    /**
     * ESC-INT-EST-01 / CP-INT-EST-03: Rechazo de inserción cuando ya existe una estadística activa para el atleta en esa participación.
     * Verifica que retorne el código DUPLICATE y preserve los datos previos sin sobreescribirlos.
     */
    public function testErrorRegistroEstadisticaDuplicada(): void
    {
        // Sembrado previo: activación de estadística
        $this->pdo->exec("UPDATE detalles_participacion SET 
            estatus_estadistica = 1, goles = 7, asistencias = 2, penalizaciones = 0, goles_contra = 0, partidos_jugados = 3, average = 2.33 
            WHERE codigo_dtll_prtc = 1");

        $datos = [
            'accion'         => 'incluir',
            'participacion'  => 1,
            'atleta'         => 1,
            'goles'          => 10,
            'asistencias'    => 5,
            'penalizaciones' => 2,
            'goles_c'        => 0,
            'partido'        => 4,
            'average'        => 2.50
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE, $resultado['codigo']);

        // Verificamos que los datos en BD no fueron sobreescritos
        $stmt = $this->pdo->query("SELECT goles FROM detalles_participacion WHERE codigo_dtll_prtc = 1");
        $this->assertEquals(7, (int)$stmt->fetchColumn(), 'Los goles deben conservarse con el valor original previo al intento de duplicación.');
    }

    /**
     * ESC-INT-EST-01 / CP-INT-EST-04: Rechazo de registro cuando la participación no existe en la BD (INVALID_ID).
     */
    public function testErrorRegistroParticipacionInexistente(): void
    {
        $datos = [
            'accion'         => 'incluir',
            'participacion'  => 99999, // ID inexistente
            'atleta'         => 1,
            'goles'          => 1,
            'asistencias'    => 0,
            'penalizaciones' => 0,
            'goles_c'        => 0,
            'partido'        => 1,
            'average'        => 1.00
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    /**
     * ESC-INT-EST-01 / CP-INT-EST-05: Rechazo de registro cuando el atleta no existe en la BD (INVALID_ID . '1').
     */
    public function testErrorRegistroAtletaInexistente(): void
    {
        $datos = [
            'accion'         => 'incluir',
            'participacion'  => 1,
            'atleta'         => 99999, // Atleta inexistente
            'goles'          => 1,
            'asistencias'    => 0,
            'penalizaciones' => 0,
            'goles_c'        => 0,
            'partido'        => 1,
            'average'        => 1.00
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '1', $resultado['codigo']);
    }

    /**
     * ESC-INT-EST-01 / CP-INT-EST-06: Rechazo de registro cuando el atleta no está asociado a la participación (EMPTY_SELECTION).
     * Verifica que falle la validación de pertenencia si no existe la tupla en detalles_participacion.
     */
    public function testErrorRegistroAtletaNoPerteneceAParticipacion(): void
    {
        // El Atleta 3 existe en la tabla atletas, pero NO está asignado a Participación 1
        $datos = [
            'accion'         => 'incluir',
            'participacion'  => 1,
            'atleta'         => 3,
            'goles'          => 3,
            'asistencias'    => 1,
            'penalizaciones' => 0,
            'goles_c'        => 0,
            'partido'        => 2,
            'average'        => 1.50
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(EMPTY_SELECTION, $resultado['codigo']);
    }

    /**
     * ESC-INT-EST-01 / CP-INT-EST-07: Excepción al intentar procesar un arreglo de datos vacío.
     */
    public function testErrorDatosVaciosLanzaExcepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $this->modelo->ProcesarDatos([]);
    }

    /**
     * ESC-INT-EST-01 / CP-INT-EST-08: Excepción al solicitar una acción no soportada por el modelo.
     */
    public function testErrorAccionInvalidaLanzaExcepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada para estadísticas no es válida.');

        $this->modelo->ProcesarDatos([
            'accion' => 'accion_desconocida',
            'atleta' => 1
        ]);
    }
}
