<?php

declare(strict_types=1);

namespace tests\Integration\Palmares;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPalmares;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use Exception;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("palmares")]
class ModificarPalmaresIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloPalmares $modelo;
    private ModeloPremios $modeloPremios;

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

        $this->modelo = new ModeloPalmares();
        $this->modeloPremios = new ModeloPremios();
        $this->modelo->setModeloPremios($this->modeloPremios);

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
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("TRUNCATE TABLE equipos;");
        $this->pdo->exec("TRUNCATE TABLE torneos;");
        $this->pdo->exec("TRUNCATE TABLE premios;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        // 1. Torneos finalizados (estatus 3)
        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (1, 'Copa Oro 2026', '2026-05-10', '2026-05-20', 'Gimnasio Techado', 3),
            (2, 'Liga Clausura 2026', '2026-11-01', '2026-11-15', 'Polideportivo Central', 3)");

        // 2. Equipos
        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES 
            (1, 'Cannibals Alfa'),
            (2, 'Titanes del Este'),
            (3, 'Huracanes FC')");

        // 3. Atletas
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2005-03-10', 'diego.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2006-08-20', 'carlos.jpg'),
            (3, 'Luis', 'Navarro', 'H', '2007-01-15', 'luis.jpg')");

        // 4. Participaciones
        // Torneo 1: Equipo 1 (part. 1) y Equipo 2 (part. 2).
        // Torneo 2: Equipo 1 (part. 3).
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES 
            (1, 1, 1),
            (2, 1, 2),
            (3, 2, 1)");

        // 5. Detalles de Participación
        // Part 1 (Torneo 1, Equipo 1): Atleta 1 (det. 1).
        // Part 2 (Torneo 1, Equipo 2): Atleta 2 (det. 2).
        // Part 3 (Torneo 2, Equipo 1): Atleta 1 (det. 3).
        $this->pdo->exec("INSERT INTO detalles_participacion 
            (codigo_dtll_prtc, codigo_participacion, codigo_atleta, goles, asistencias, penalizaciones, goles_contra, partidos_jugados, average) VALUES 
            (1, 1, 1, 5, 2, 0, 0, 3, 1.67),
            (2, 2, 2, 1, 0, 1, 0, 3, 0.33),
            (3, 3, 1, 3, 1, 0, 0, 2, 1.50)");

        // 6. Premios
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES 
            (1, 'Jugador Más Valioso', 'I'),
            (2, 'Mejor Portero', 'I'),
            (3, 'Campeón Copa Oro', 'G'),
            (4, 'Subcampeón Copa Oro', 'G')");

        // 7. Sembrado de palmarés existentes iniciales
        // Palmarés individual ID 1: Atleta 1, Premio 1 en Torneo 1 (det. 1)
        $this->pdo->exec("INSERT INTO palmares_individual (codigo_individual, codigo_premio, codigo_dtll_prtc) VALUES (1, 1, 1)");
        // Palmarés grupal ID 1: Equipo 1, Premio 3 en Torneo 1 (part. 1)
        $this->pdo->exec("INSERT INTO palmares_grupal (codigo_grupal, codigo_premio, codigo_participacion) VALUES (1, 3, 1)");
    }

    /**
     * ESC-INT-PALM-02 / CP-INT-PALM-14: Modificación exitosa del premio asignado a un palmarés individual.
     * Verifica que se actualice el campo codigo_premio en la tabla palmares_individual.
     */
    public function testModificarPremioPalmaresIndividualExitoso(): void
    {
        $datos = [
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 1,
            'atleta'        => 1,
            'premio'        => 2, // Cambiar a Premio 2: Mejor Portero ('I')
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificación directa en base de datos
        $stmt = $this->pdo->query("SELECT * FROM palmares_individual WHERE codigo_individual = 1");
        $registro = $stmt->fetch();

        $this->assertEquals(2, (int)$registro['codigo_premio']);
        $this->assertEquals(1, (int)$registro['codigo_dtll_prtc']);
    }

    /**
     * ESC-INT-PALM-02 / CP-INT-PALM-15: Modificación exitosa del atleta en un palmarés individual.
     * Cambia de Atleta 1 a Atleta 2 (quien también participó en el Torneo 1 mediante det. 2).
     */
    public function testModificarAtletaPalmaresIndividualExitoso(): void
    {
        $datos = [
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 1,
            'atleta'        => 2, // Atleta 2 tiene det. 2 en Torneo 1
            'premio'        => 1,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT * FROM palmares_individual WHERE codigo_individual = 1");
        $registro = $stmt->fetch();

        $this->assertEquals(1, (int)$registro['codigo_premio']);
        $this->assertEquals(2, (int)$registro['codigo_dtll_prtc'], 'El codigo_dtll_prtc debe apuntar a la participación del atleta 2.');
    }

    /**
     * ESC-INT-PALM-02 / CP-INT-PALM-16: Modificación exitosa del torneo en un palmarés individual.
     * Cambia del Torneo 1 al Torneo 2 (donde Atleta 1 tiene det. 3).
     */
    public function testModificarTorneoPalmaresIndividualExitoso(): void
    {
        $datos = [
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 2, // Torneo 2 (donde Atleta 1 tiene det. 3)
            'atleta'        => 1,
            'premio'        => 1,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT * FROM palmares_individual WHERE codigo_individual = 1");
        $registro = $stmt->fetch();

        $this->assertEquals(3, (int)$registro['codigo_dtll_prtc'], 'El codigo_dtll_prtc debe actualizarse al detalle del Torneo 2.');
    }

    /**
     * ESC-INT-PALM-02 / CP-INT-PALM-17: Modificación exitosa del premio asignado a un palmarés grupal.
     * Cambia el premio grupal de Premio 3 ('Campeón') a Premio 4 ('Subcampeón').
     */
    public function testModificarPremioPalmaresGrupalExitoso(): void
    {
        $datos = [
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 1,
            'equipo'        => 1,
            'premio'        => 4, // Cambiar a Premio 4 ('Subcampeón')
            'tipo_palmares' => 'grupal'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT * FROM palmares_grupal WHERE codigo_grupal = 1");
        $registro = $stmt->fetch();

        $this->assertEquals(4, (int)$registro['codigo_premio']);
        $this->assertEquals(1, (int)$registro['codigo_participacion']);
    }

    /**
     * ESC-INT-PALM-02 / CP-INT-PALM-18: Modificación exitosa del equipo en un palmarés grupal.
     * Cambia del Equipo 1 al Equipo 2 (quien también participó en el Torneo 1 con part. 2).
     */
    public function testModificarEquipoPalmaresGrupalExitoso(): void
    {
        $datos = [
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 1,
            'equipo'        => 2, // Equipo 2 participó en Torneo 1 con part. 2
            'premio'        => 3,
            'tipo_palmares' => 'grupal'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT * FROM palmares_grupal WHERE codigo_grupal = 1");
        $registro = $stmt->fetch();

        $this->assertEquals(3, (int)$registro['codigo_premio']);
        $this->assertEquals(2, (int)$registro['codigo_participacion'], 'La participación debe actualizarse al Equipo 2.');
    }

    /**
     * ESC-INT-PALM-02 / CP-INT-PALM-19: Rechazo de modificación en palmarés individual cuando el atleta no participó en el torneo.
     * Verifica que retorne el mensaje de error y no modifique los datos en BD (rollback).
     */
    public function testErrorModificarAtletaSinParticipacion(): void
    {
        // Atleta 3 no participó en el Torneo 1
        $datos = [
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 1,
            'atleta'        => 3,
            'premio'        => 1,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El atleta no cuenta con una participación registrada en este torneo.', $resultado['codigo']);

        // Verificamos que el registro permanezca intacto en base de datos
        $stmt = $this->pdo->query("SELECT * FROM palmares_individual WHERE codigo_individual = 1");
        $registro = $stmt->fetch();
        $this->assertEquals(1, (int)$registro['codigo_premio']);
        $this->assertEquals(1, (int)$registro['codigo_dtll_prtc']);
    }

    /**
     * ESC-INT-PALM-02 / CP-INT-PALM-20: Rechazo de modificación en palmarés grupal cuando el equipo no participó en el torneo.
     * Verifica que retorne el mensaje de error y conserve los valores anteriores.
     */
    public function testErrorModificarEquipoSinParticipacion(): void
    {
        // Equipo 3 no participó en el Torneo 1
        $datos = [
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 1,
            'equipo'        => 3,
            'premio'        => 3,
            'tipo_palmares' => 'grupal'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El equipo no cuenta con una participación registrada en este torneo.', $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT * FROM palmares_grupal WHERE codigo_grupal = 1");
        $registro = $stmt->fetch();
        $this->assertEquals(3, (int)$registro['codigo_premio']);
        $this->assertEquals(1, (int)$registro['codigo_participacion']);
    }

    /**
     * ESC-INT-PALM-02 / CP-INT-PALM-21: Rechazo de modificación cuando el ID de premio no existe en la base de datos.
     * Verifica que retorne el código INVALID_ID.
     */
    public function testErrorModificarPremioInexistente(): void
    {
        $datos = [
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 1,
            'atleta'        => 1,
            'premio'        => 88888, // Inexistente
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);

        // Conserva el valor original
        $stmt = $this->pdo->query("SELECT codigo_premio FROM palmares_individual WHERE codigo_individual = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * ESC-INT-PALM-02 / CP-INT-PALM-22: Rechazo de modificación por incompatibilidad de tipo (premio grupal en registro individual).
     * Verifica que retorne el código VALIDATION.
     */
    public function testErrorModificarTipoPremioIncompatibleIndividual(): void
    {
        // Premio 3 es grupal 'G'
        $datos = [
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 1,
            'atleta'        => 1,
            'premio'        => 3,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION, $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT codigo_premio FROM palmares_individual WHERE codigo_individual = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * ESC-INT-PALM-02 / CP-INT-PALM-23: Rechazo de modificación por incompatibilidad de tipo (premio individual en registro grupal).
     * Verifica que retorne el código VALIDATION.
     */
    public function testErrorModificarTipoPremioIncompatibleGrupal(): void
    {
        // Premio 1 es individual 'I'
        $datos = [
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 1,
            'equipo'        => 1,
            'premio'        => 1,
            'tipo_palmares' => 'grupal'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION, $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT codigo_premio FROM palmares_grupal WHERE codigo_grupal = 1");
        $this->assertEquals(3, (int)$stmt->fetchColumn());
    }

    /**
     * ESC-INT-PALM-02 / CP-INT-PALM-24: Error al intentar modificar sin configurar ModeloPremios.
     */
    public function testErrorModificarModelosRequeridosNoConfigurados(): void
    {
        $modeloSinDependencia = new ModeloPalmares();

        $datos = [
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 1,
            'atleta'        => 1,
            'premio'        => 2,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $modeloSinDependencia->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Modelos requeridos no configurados.', $resultado['codigo']);
    }
}
