<?php

declare(strict_types=1);

namespace tests\Integration\Palmares;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPalmares;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("palmares")]
class ConsultasPalmaresIntegrationTest extends TestCase
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
        // 1. Torneos finalizados (estatus = 3)
        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (1, 'Copa Oro 2026', '2026-05-10', '2026-05-20', 'Gimnasio Techado', 3),
            (2, 'Liga Clausura 2026', '2026-11-01', '2026-11-15', 'Polideportivo Central', 3)");

        // 2. Equipos
        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES 
            (1, 'Cannibals Alfa'),
            (2, 'Titanes del Este')");

        // 3. Atletas
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2005-03-10', 'diego.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2006-08-20', 'carlos.jpg')");

        // 4. Identidad / Representantes
        // Atleta 1 tiene cédula propia en identidad_atleta
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES 
            (1, 'V', 'V-28123456')");

        // Atleta 2 es menor y su documento proviene de su representante
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, telefono, direccion, nombre, apellido, tipo_doc) VALUES 
            (1, '15987654', '04141234567', 'Av. Principal', 'Pedro', 'Mendoza', 'V')");
        $this->pdo->exec("INSERT INTO atleta_representante (codigo_at_re, codigo_atleta, codigo_representante) VALUES 
            (1, 2, 1)");

        // 5. Participaciones
        // Torneo 1: Equipo 1 (part. 1) y Equipo 2 (part. 2)
        // Torneo 2: Equipo 1 (part. 3)
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES 
            (1, 1, 1),
            (2, 1, 2),
            (3, 2, 1)");

        // 6. Detalles de Participación
        // Atleta 1 en Part. 1 (Torneo 1)
        // Atleta 2 en Part. 2 (Torneo 1)
        // Atleta 1 en Part. 3 (Torneo 2)
        $this->pdo->exec("INSERT INTO detalles_participacion 
            (codigo_dtll_prtc, codigo_participacion, codigo_atleta, goles, asistencias, penalizaciones, goles_contra, partidos_jugados, average) VALUES 
            (1, 1, 1, 5, 2, 0, 0, 3, 1.67),
            (2, 2, 2, 1, 0, 1, 0, 3, 0.33),
            (3, 3, 1, 3, 1, 0, 0, 2, 1.50)");

        // 7. Premios
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES 
            (1, 'Jugador Más Valioso', 'I'),
            (2, 'Mejor Portero', 'I'),
            (3, 'Campeón Copa Oro', 'G'),
            (4, 'Campeón Liga Clausura', 'G')");

        // 8. Registros de Palmarés Individuales
        // Atleta 1 ganó Premio 1 en Torneo 1 (det. 1)
        // Atleta 1 ganó Premio 2 en Torneo 2 (det. 3) -> 2 premios para Atleta 1 (prueba de agrupación)
        // Atleta 2 ganó Premio 2 en Torneo 1 (det. 2) -> 1 premio para Atleta 2
        $this->pdo->exec("INSERT INTO palmares_individual (codigo_individual, codigo_premio, codigo_dtll_prtc) VALUES 
            (1, 1, 1),
            (2, 2, 3),
            (3, 2, 2)");

        // 9. Registros de Palmarés Grupales
        // Equipo 1 ganó Premio 3 en Torneo 1 (part. 1)
        // Equipo 1 ganó Premio 4 en Torneo 2 (part. 3) -> 2 premios para Equipo 1 (prueba de agrupación)
        $this->pdo->exec("INSERT INTO palmares_grupal (codigo_grupal, codigo_premio, codigo_participacion) VALUES 
            (1, 3, 1),
            (2, 4, 3)");
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-31: Consulta general de palmarés individual y verificación de agrupación.
     * Verifica que a través de la vista vista_palmares_individual se agrupen los premios por atleta.
     */
    public function testConsultarIndividualGeneralAgrupado(): void
    {
        $resultado = $this->modelo->ConsultarIndividual();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertCount(2, $resultado['datos'], 'Deben agruparse 2 atletas distintos.');

        // Atleta 1 (Diego Reinoso) debe tener total_premios = 2
        $atletaDiego = null;
        foreach ($resultado['datos'] as $item) {
            if ($item['id_atleta'] == 1) {
                $atletaDiego = $item;
                break;
            }
        }

        $this->assertNotNull($atletaDiego);
        $this->assertEquals('Diego', $atletaDiego['atleta_nombres']);
        $this->assertEquals('Reinoso', $atletaDiego['atleta_apellidos']);
        $this->assertEquals(2, $atletaDiego['total_premios']);
        $this->assertCount(2, $atletaDiego['premios']);

        // Verificamos los nombres de los premios agrupados
        $nombresPremios = array_column($atletaDiego['premios'], 'nombre_premio');
        $this->assertContains('Jugador Más Valioso', $nombresPremios);
        $this->assertContains('Mejor Portero', $nombresPremios);
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-32: Consulta de palmarés individual con filtro por nombre de atleta.
     */
    public function testConsultarIndividualFiltroPorNombreAtleta(): void
    {
        $resultado = $this->modelo->ConsultarIndividual(['filtro' => 'Diego']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Diego', $resultado['datos'][0]['atleta_nombres']);
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-33: Consulta de palmarés individual con filtro por nombre de premio.
     */
    public function testConsultarIndividualFiltroPorNombrePremio(): void
    {
        $resultado = $this->modelo->ConsultarIndividual(['filtro' => 'Valioso']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['id_atleta']);
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-34: Consulta de palmarés individual con filtro por nombre de torneo.
     */
    public function testConsultarIndividualFiltroPorNombreTorneo(): void
    {
        $resultado = $this->modelo->ConsultarIndividual(['filtro' => 'Clausura']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['id_atleta']);
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-35: Consulta general de palmarés grupal y verificación de agrupación.
     * Verifica que a través de la vista vista_palmares_grupal se agrupen los premios ganados por el equipo.
     */
    public function testConsultarGrupalGeneralAgrupado(): void
    {
        $resultado = $this->modelo->ConsultarGrupal();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertCount(1, $resultado['datos'], 'Equipo 1 tiene 2 premios y debe agruparse en 1 fila de equipo.');

        $equipo1 = $resultado['datos'][0];
        $this->assertEquals(1, $equipo1['id_equipo']);
        $this->assertEquals('Cannibals Alfa', $equipo1['nombre_equipo']);
        $this->assertEquals(2, $equipo1['total_premios']);
        $this->assertCount(2, $equipo1['premios']);

        $nombresPremios = array_column($equipo1['premios'], 'nombre_premio');
        $this->assertContains('Campeón Copa Oro', $nombresPremios);
        $this->assertContains('Campeón Liga Clausura', $nombresPremios);
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-36: Consulta de palmarés grupal con filtro por nombre de equipo.
     */
    public function testConsultarGrupalFiltroPorNombreEquipo(): void
    {
        $resultado = $this->modelo->ConsultarGrupal(['filtro' => 'Cannibals']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Cannibals Alfa', $resultado['datos'][0]['nombre_equipo']);

        // Filtro que no coincide con ningún equipo con premios
        $resultadoVacio = $this->modelo->ConsultarGrupal(['filtro' => 'Inexistente']);
        $this->assertEmpty($resultadoVacio['datos']);
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-37: Consulta de palmarés grupal con filtro por nombre de torneo.
     */
    public function testConsultarGrupalFiltroPorNombreTorneo(): void
    {
        $resultado = $this->modelo->ConsultarGrupal(['filtro' => 'Oro']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-38: Búsqueda individual por ID con cédula propia del atleta (identidad_atleta).
     */
    public function testBuscarIndividualConCedulaPropia(): void
    {
        $resultado = $this->modelo->BuscarIndividual(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);

        $datos = $resultado['datos'][0];
        $this->assertEquals(1, (int)$datos['codigo_individual']);
        $this->assertEquals(1, (int)$datos['id_premio']);
        $this->assertEquals(1, (int)$datos['id_atleta']);
        $this->assertEquals(1, (int)$datos['id_torneo']);
        $this->assertEquals('Diego', $datos['nombres']);
        $this->assertEquals('Reinoso', $datos['apellidos']);
        $this->assertEquals('V-28123456', $datos['cedula']);
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-39: Búsqueda individual por ID con cédula del representante (prefijo R-).
     */
    public function testBuscarIndividualConCedulaRepresentante(): void
    {
        $resultado = $this->modelo->BuscarIndividual(3);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);

        $datos = $resultado['datos'][0];
        $this->assertEquals(3, (int)$datos['codigo_individual']);
        $this->assertEquals(2, (int)$datos['id_atleta']);
        $this->assertEquals('Carlos', $datos['nombres']);
        $this->assertEquals('R-15987654', $datos['cedula'], 'Debe anteponer R- a la cédula del representante cuando no tiene cédula propia.');
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-40: Búsqueda individual por ID inexistente.
     */
    public function testBuscarIndividualInexistente(): void
    {
        $resultado = $this->modelo->BuscarIndividual(99999);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-41: Búsqueda grupal por ID.
     */
    public function testBuscarGrupalExitoso(): void
    {
        $resultado = $this->modelo->BuscarGrupal(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);

        $datos = $resultado['datos'][0];
        $this->assertEquals(1, (int)$datos['codigo_grupal']);
        $this->assertEquals(3, (int)$datos['id_premio']);
        $this->assertEquals(1, (int)$datos['id_equipo']);
        $this->assertEquals(1, (int)$datos['id_torneo']);
        $this->assertEquals('Cannibals Alfa', $datos['nombre_equipo']);
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-42: Búsqueda grupal por ID inexistente.
     */
    public function testBuscarGrupalInexistente(): void
    {
        $resultado = $this->modelo->BuscarGrupal(99999);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-43: Consulta de atletas con participación en torneo vía ProcesarDatos.
     */
    public function testObtenerAtletasParticipacionEnTorneo(): void
    {
        $datos = [
            'accion' => 'atletas_participacion',
            'torneo' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('atletas_participacion', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertCount(2, $resultado['datos'], 'En Torneo 1 participan Atleta 1 y Atleta 2.');

        $idsAtletas = array_column($resultado['datos'], 'id_atleta');
        $this->assertContains(1, $idsAtletas);
        $this->assertContains(2, $idsAtletas);
    }

    /**
     * ESC-INT-PALM-04 / CP-INT-PALM-44: Consulta de equipos con participación en torneo vía ProcesarDatos.
     */
    public function testObtenerEquiposParticipacionEnTorneo(): void
    {
        $datos = [
            'accion' => 'equipos_participacion',
            'torneo' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('equipos_participacion', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertCount(2, $resultado['datos'], 'En Torneo 1 participan Equipo 1 y Equipo 2.');

        $nombresEquipos = array_column($resultado['datos'], 'nombre');
        $this->assertContains('Cannibals Alfa', $nombresEquipos);
        $this->assertContains('Titanes del Este', $nombresEquipos);
    }
}
