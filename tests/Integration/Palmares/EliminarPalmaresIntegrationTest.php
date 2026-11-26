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
class EliminarPalmaresIntegrationTest extends TestCase
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
        // 1. Torneo finalizado
        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (1, 'Copa Oro 2026', '2026-05-10', '2026-05-20', 'Gimnasio Techado', 3)");

        // 2. Equipos
        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES 
            (1, 'Cannibals Alfa'),
            (2, 'Titanes del Este')");

        // 3. Atletas
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2005-03-10', 'diego.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2006-08-20', 'carlos.jpg')");

        // 4. Participaciones
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES 
            (1, 1, 1),
            (2, 1, 2)");

        // 5. Detalles de Participación
        $this->pdo->exec("INSERT INTO detalles_participacion 
            (codigo_dtll_prtc, codigo_participacion, codigo_atleta, goles, asistencias, penalizaciones, goles_contra, partidos_jugados, average) VALUES 
            (1, 1, 1, 5, 2, 0, 0, 3, 1.67),
            (2, 2, 2, 1, 0, 1, 0, 3, 0.33)");

        // 6. Premios
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES 
            (1, 'Jugador Más Valioso', 'I'),
            (2, 'Mejor Portero', 'I'),
            (3, 'Campeón Copa Oro', 'G'),
            (4, 'Subcampeón Copa Oro', 'G')");

        // 7. Registros de palmarés existentes
        // Palmares Individual: 1 y 2
        $this->pdo->exec("INSERT INTO palmares_individual (codigo_individual, codigo_premio, codigo_dtll_prtc) VALUES 
            (1, 1, 1),
            (2, 2, 2)");

        // Palmares Grupal: 1 y 2
        $this->pdo->exec("INSERT INTO palmares_grupal (codigo_grupal, codigo_premio, codigo_participacion) VALUES 
            (1, 3, 1),
            (2, 4, 2)");
    }

    /**
     * ESC-INT-PALM-03 / CP-INT-PALM-25: Eliminación exitosa de un palmarés individual en base de datos.
     * Verifica que el registro sea eliminado físicamente de la tabla palmares_individual.
     */
    public function testEliminarPalmaresIndividualExitoso(): void
    {
        $datos = [
            'accion'        => 'eliminar',
            'id'            => 1,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos que ya no exista el registro 1 en BD
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM palmares_individual WHERE codigo_individual = 1");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'El registro individual 1 debió ser eliminado de la BD.');
    }

    /**
     * ESC-INT-PALM-03 / CP-INT-PALM-26: Eliminación exitosa de un palmarés grupal en base de datos.
     * Verifica que el registro sea eliminado físicamente de la tabla palmares_grupal.
     */
    public function testEliminarPalmaresGrupalExitoso(): void
    {
        $datos = [
            'accion'        => 'eliminar',
            'id'            => 1,
            'tipo_palmares' => 'grupal'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos que ya no exista el registro 1 en BD
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM palmares_grupal WHERE codigo_grupal = 1");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'El registro grupal 1 debió ser eliminado de la BD.');
    }

    /**
     * ESC-INT-PALM-03 / CP-INT-PALM-27: Aislamiento en la eliminación individual.
     * Verifica que al eliminar el registro individual 1, el registro individual 2 y los registros grupales no sufran alteración.
     */
    public function testAislamientoEliminacionIndividual(): void
    {
        $datos = [
            'accion'        => 'eliminar',
            'id'            => 1,
            'tipo_palmares' => 'individual'
        ];

        $this->modelo->ProcesarDatos($datos);

        // Registro individual 2 debe seguir existiendo intacto
        $stmtInd = $this->pdo->query("SELECT * FROM palmares_individual WHERE codigo_individual = 2");
        $regInd = $stmtInd->fetch();
        $this->assertNotEmpty($regInd);
        $this->assertEquals(2, (int)$regInd['codigo_premio']);

        // Registros grupales 1 y 2 deben seguir intactos
        $stmtGrp = $this->pdo->query("SELECT COUNT(*) FROM palmares_grupal");
        $this->assertEquals(2, (int)$stmtGrp->fetchColumn(), 'Los palmarés grupales no deben ser afectados.');
    }

    /**
     * ESC-INT-PALM-03 / CP-INT-PALM-28: Aislamiento en la eliminación grupal.
     * Verifica que al eliminar el registro grupal 1, el registro grupal 2 y los registros individuales no sufran alteración.
     */
    public function testAislamientoEliminacionGrupal(): void
    {
        $datos = [
            'accion'        => 'eliminar',
            'id'            => 1,
            'tipo_palmares' => 'grupal'
        ];

        $this->modelo->ProcesarDatos($datos);

        // Registro grupal 2 debe seguir existiendo intacto
        $stmtGrp = $this->pdo->query("SELECT * FROM palmares_grupal WHERE codigo_grupal = 2");
        $regGrp = $stmtGrp->fetch();
        $this->assertNotEmpty($regGrp);
        $this->assertEquals(4, (int)$regGrp['codigo_premio']);

        // Registros individuales 1 y 2 deben seguir intactos
        $stmtInd = $this->pdo->query("SELECT COUNT(*) FROM palmares_individual");
        $this->assertEquals(2, (int)$stmtInd->fetchColumn(), 'Los palmarés individuales no deben ser afectados.');
    }

    /**
     * ESC-INT-PALM-03 / CP-INT-PALM-29: Rechazo de eliminación cuando el ID individual no existe en la base de datos.
     * Verifica que retorne el código INVALID_ID y la base de datos permanezca intacta.
     */
    public function testEliminarIdIndividualInexistente(): void
    {
        $datos = [
            'accion'        => 'eliminar',
            'id'            => 99999,
            'tipo_palmares' => 'individual'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);

        // El conteo original de 2 registros se mantiene
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM palmares_individual");
        $this->assertEquals(2, (int)$stmt->fetchColumn());
    }

    /**
     * ESC-INT-PALM-03 / CP-INT-PALM-30: Rechazo de eliminación cuando el ID grupal no existe en la base de datos.
     * Verifica que retorne el código INVALID_ID y la base de datos permanezca intacta.
     */
    public function testEliminarIdGrupalInexistente(): void
    {
        $datos = [
            'accion'        => 'eliminar',
            'id'            => 99999,
            'tipo_palmares' => 'grupal'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);

        // El conteo original de 2 registros se mantiene
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM palmares_grupal");
        $this->assertEquals(2, (int)$stmt->fetchColumn());
    }
}
