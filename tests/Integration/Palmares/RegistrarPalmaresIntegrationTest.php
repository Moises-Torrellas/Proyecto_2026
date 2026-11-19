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
class RegistrarPalmaresIntegrationTest extends TestCase
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
        // 1. Torneos (estatus 3 = Finalizado, requerido por triggers de palmarés; estatus 1 = En curso)
        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (1, 'Copa Oro 2026', '2026-05-10', '2026-05-20', 'Gimnasio Techado', 3),
            (2, 'Liga Clausura 2026', '2026-11-01', '2026-11-15', 'Polideportivo Central', 3),
            (3, 'Torneo Apertura En Curso', '2026-12-01', '2026-12-20', 'Estadio Olímpico', 1)");

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
        // Torneo 1 (Finalizado): Equipo 1 (part. 1) y Equipo 2 (part. 2). Equipo 3 no participa.
        // Torneo 3 (En curso): Equipo 1 (part. 3).
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES 
            (1, 1, 1),
            (2, 1, 2),
            (3, 3, 1)");

        // 5. Detalles de Participación
        // Atleta 1 en Part. 1 (Torneo 1). Atleta 2 en Part. 2 (Torneo 1). Atleta 1 en Part. 3 (Torneo 3 En Curso). Atleta 3 sin participación.
        $this->pdo->exec("INSERT INTO detalles_participacion 
            (codigo_dtll_prtc, codigo_participacion, codigo_atleta, goles, asistencias, penalizaciones, goles_contra, partidos_jugados, average) VALUES 
            (1, 1, 1, 5, 2, 0, 0, 3, 1.67),
            (2, 2, 2, 1, 0, 1, 0, 3, 0.33),
            (3, 3, 1, 2, 1, 0, 0, 1, 2.00)");

        // 6. Premios
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES 
            (1, 'Jugador Más Valioso', 'I'),
            (2, 'Mejor Portero', 'I'),
            (3, 'Campeón Copa Oro', 'G'),
            (4, 'Subcampeón Copa Oro', 'G')");
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-01: Inclusión exitosa de un palmarés individual en base de datos.
     * Verifica la resolución de codigo_dtll_prtc, persistencia en palmares_individual y retorno de éxito.
     */
    public function testRegistroPalmaresIndividualExitoso(): void
    {
        $datos = [
            'accion'        => 'incluir',
            'torneo'        => 1,
            'atleta'        => 1,
            'premio'        => 1,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);

        // Verificación directa en base de datos
        $stmt = $this->pdo->prepare("SELECT * FROM palmares_individual WHERE codigo_premio = :premio AND codigo_dtll_prtc = :detalle");
        $stmt->execute([':premio' => 1, ':detalle' => 1]);
        $registro = $stmt->fetch();

        $this->assertNotEmpty($registro, 'El registro de palmarés individual debió insertarse en la BD.');
        $this->assertEquals(1, (int)$registro['codigo_premio']);
        $this->assertEquals(1, (int)$registro['codigo_dtll_prtc']);
        $this->assertGreaterThan(0, (int)$registro['codigo_individual']);
        $this->assertEquals((int)$registro['codigo_individual'], (int)$this->modelo->getId());
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-02: Inclusión exitosa de un palmarés grupal en base de datos.
     * Verifica la ejecución del procedimiento almacenado ProcesarPalmaresGrupal y persistencia en palmares_grupal.
     */
    public function testRegistroPalmaresGrupalExitoso(): void
    {
        $datos = [
            'accion'        => 'incluir',
            'torneo'        => 1,
            'equipo'        => 1,
            'premio'        => 3,
            'tipo_palmares' => 'grupal'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);

        // Verificación directa en base de datos
        $stmt = $this->pdo->prepare("SELECT * FROM palmares_grupal WHERE codigo_premio = :premio AND codigo_participacion = :participacion");
        $stmt->execute([':premio' => 3, ':participacion' => 1]);
        $registro = $stmt->fetch();

        $this->assertNotEmpty($registro, 'El registro de palmarés grupal debió insertarse a través del SP.');
        $this->assertEquals(3, (int)$registro['codigo_premio']);
        $this->assertEquals(1, (int)$registro['codigo_participacion']);
        $this->assertGreaterThan(0, (int)$registro['codigo_grupal']);
        $this->assertEquals((int)$registro['codigo_grupal'], (int)$this->modelo->getId());
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-03: Rechazo por trigger de BD cuando el torneo no ha finalizado (Individual).
     * Verifica que el trigger trg_antes_insertar_palmares_individual bloquee inserción en torneo en curso.
     */
    public function testErrorRegistroIndividualTorneoNoFinalizadoBloqueadoPorTrigger(): void
    {
        $datos = [
            'accion'        => 'incluir',
            'torneo'        => 3, // Torneo 3 tiene estatus = 1 (no finalizado)
            'atleta'        => 1,
            'premio'        => 1,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('No se puede registrar palmar', $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM palmares_individual WHERE codigo_dtll_prtc = 3");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'El trigger debió abortar la inserción.');
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-04: Rechazo por trigger de BD cuando el torneo no ha finalizado (Grupal).
     * Verifica que el trigger trg_antes_insertar_palmares_grupal provoque el rollback dentro del procedimiento almacenado
     * impidiendo que se persista el registro en palmares_grupal.
     */
    public function testErrorRegistroGrupalTorneoNoFinalizadoBloqueadoPorTrigger(): void
    {
        $datos = [
            'accion'        => 'incluir',
            'torneo'        => 3, // Torneo 3 tiene estatus = 1 (no finalizado)
            'equipo'        => 1,
            'premio'        => 3,
            'tipo_palmares' => 'grupal'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        // El SP ProcesarPalmaresGrupal captura la SQLEXCEPTION del trigger y ejecuta ROLLBACK
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM palmares_grupal WHERE codigo_participacion = 3");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'El trigger y el SP debieron cancelar la inserción en la base de datos.');
        $this->assertEmpty($this->modelo->getId(), 'No debió generarse un ID de palmarés grupal.');
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-05: Rechazo al registrar palmarés individual si el atleta no participó en el torneo.
     * Verifica que retorne el mensaje de error correspondiente y se mantenga la integridad sin filas insertadas.
     */
    public function testErrorRegistroAtletaSinParticipacion(): void
    {
        // Atleta 3 no tiene participación en el Torneo 1
        $datos = [
            'accion'        => 'incluir',
            'torneo'        => 1,
            'atleta'        => 3,
            'premio'        => 1,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El atleta no cuenta con una participación registrada en este torneo.', $resultado['codigo']);

        // Verificar que no se insertó ningún registro
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM palmares_individual");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'No deben existir registros en palmares_individual.');
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-06: Rechazo al registrar palmarés grupal si el equipo no participó en el torneo.
     * Verifica que retorne el mensaje de error correspondiente y se ejecute rollback.
     */
    public function testErrorRegistroEquipoSinParticipacion(): void
    {
        // Equipo 3 no participa en el Torneo 1
        $datos = [
            'accion'        => 'incluir',
            'torneo'        => 1,
            'equipo'        => 3,
            'premio'        => 3,
            'tipo_palmares' => 'grupal'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El equipo no cuenta con una participación registrada en este torneo.', $resultado['codigo']);

        // Verificar que no se insertó nada
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM palmares_grupal");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'No deben existir registros en palmares_grupal.');
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-07: Rechazo al registrar palmarés individual duplicado en la misma participación.
     * Verifica que retorne el código DUPLICATE y no duplique registros en la base de datos.
     */
    public function testErrorRegistroIndividualDuplicado(): void
    {
        // Inserción previa (Torneo 1 está finalizado con estatus = 3)
        $this->pdo->exec("INSERT INTO palmares_individual (codigo_premio, codigo_dtll_prtc) VALUES (1, 1)");

        $datos = [
            'accion'        => 'incluir',
            'torneo'        => 1,
            'atleta'        => 1,
            'premio'        => 1,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE, $resultado['codigo']);

        // Verificar que solo existe 1 registro en BD
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM palmares_individual WHERE codigo_premio = 1 AND codigo_dtll_prtc = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'No debe duplicarse el palmarés individual.');
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-08: Rechazo al registrar palmarés grupal duplicado en la misma participación.
     * Verifica que retorne el código DUPLICATE y no duplique registros en la base de datos.
     */
    public function testErrorRegistroGrupalDuplicado(): void
    {
        // Inserción previa (Torneo 1 está finalizado con estatus = 3)
        $this->pdo->exec("INSERT INTO palmares_grupal (codigo_premio, codigo_participacion) VALUES (3, 1)");

        $datos = [
            'accion'        => 'incluir',
            'torneo'        => 1,
            'equipo'        => 1,
            'premio'        => 3,
            'tipo_palmares' => 'grupal'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE, $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM palmares_grupal WHERE codigo_premio = 3 AND codigo_participacion = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'No debe duplicarse el palmarés grupal.');
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-09: Rechazo al registrar palmarés con premio inexistente en la tabla premios.
     * Verifica que retorne el código INVALID_ID.
     */
    public function testErrorRegistroPremioInexistente(): void
    {
        $datos = [
            'accion'        => 'incluir',
            'torneo'        => 1,
            'atleta'        => 1,
            'premio'        => 99999, // ID inexistente
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM palmares_individual");
        $this->assertEquals(0, (int)$stmt->fetchColumn());
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-10: Rechazo por incongruencia de tipo de premio (ej. premio grupal en palmarés individual).
     * Verifica la integración con ModeloPremios::validarTipoPremio y retorno de VALIDATION.
     */
    public function testErrorRegistroTipoPremioIncongruente(): void
    {
        // Premio 3 es tipo 'G', pero se envía como individual
        $datos = [
            'accion'        => 'incluir',
            'torneo'        => 1,
            'atleta'        => 1,
            'premio'        => 3,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(VALIDATION, $resultado['codigo']);

        // Premio 1 es tipo 'I', pero se envía como grupal
        $datosGrupal = [
            'accion'        => 'incluir',
            'torneo'        => 1,
            'equipo'        => 1,
            'premio'        => 1,
            'tipo_palmares' => 'grupal'
        ];

        $resultadoGrp = $this->modelo->ProcesarDatos($datosGrupal);

        $this->assertEquals('error', $resultadoGrp['accion']);
        $this->assertEquals(VALIDATION, $resultadoGrp['codigo']);
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-11: Falla al no inyectar ModeloPremios antes de incluir.
     */
    public function testErrorModelosRequeridosNoConfigurados(): void
    {
        $modeloSinDependencia = new ModeloPalmares();

        $datos = [
            'accion'        => 'incluir',
            'torneo'        => 1,
            'atleta'        => 1,
            'premio'        => 1,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $modeloSinDependencia->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Modelos requeridos no configurados.', $resultado['codigo']);
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-12: Excepción al intentar procesar un arreglo de datos vacío.
     */
    public function testErrorDatosVaciosLanzaExcepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $this->modelo->ProcesarDatos([]);
    }

    /**
     * ESC-INT-PALM-01 / CP-INT-PALM-13: Excepción al intentar procesar una acción no permitida.
     */
    public function testErrorAccionInvalidaLanzaExcepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada para el palmarés no es válida.');

        $this->modelo->ProcesarDatos([
            'accion'        => 'accion_desconocida',
            'torneo'        => 1,
            'atleta'        => 1,
            'premio'        => 1,
            'tipo_palmares' => 'individual'
        ]);
    }
}
