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
class ConsultasEstadisticasIntegrationTest extends TestCase
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
        $this->pdo->exec("TRUNCATE TABLE posiciones;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("TRUNCATE TABLE equipos;");
        $this->pdo->exec("TRUNCATE TABLE torneos;");
        $this->pdo->exec("TRUNCATE TABLE premios;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        // 1. Torneos
        // Torneo 1: Finalizado (estatus = 3)
        // Torneo 2: Finalizado (estatus = 3)
        // Torneo 3: En Curso (estatus = 1)
        // Torneo 4: Finalizado pero sin participaciones (estatus = 3)
        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (1, 'Copa Oro 2026', '2026-05-10', '2026-05-20', 'Gimnasio Techado', 3),
            (2, 'Liga Clausura 2026', '2026-11-01', '2026-11-15', 'Polideportivo Central', 3),
            (3, 'Torneo Apertura 2027', '2027-02-01', '2027-02-28', 'Estadio Olímpico', 1),
            (4, 'Torneo Amistoso 2026', '2026-08-01', '2026-08-10', 'Cancha Anexa', 3)");

        // 2. Equipos
        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES 
            (1, 'Cannibals Alfa'),
            (2, 'Titanes del Este')");

        // 3. Atletas
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2005-03-10', 'diego.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2006-08-20', 'carlos.jpg'),
            (3, 'Luis', 'Navarro', 'H', '2007-01-15', 'luis.jpg')");

        // 4. Identidad y Representantes
        // Atleta 1 tiene documento propio
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES 
            (1, 'V', 'V-28123456')");

        // Atleta 2 es menor de edad y su documento proviene de su representante
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, telefono, direccion, nombre, apellido, tipo_doc) VALUES 
            (1, '15987654', '04141234567', 'Av. Libertador', 'Pedro', 'Mendoza', 'V')");
        $this->pdo->exec("INSERT INTO atleta_representante (codigo_at_re, codigo_atleta, codigo_representante) VALUES 
            (1, 2, 1)");

        // 5. Categorías, Posiciones e Inscripciones
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) VALUES 
            (1, 'Delantero', 'DEL', 'Ataque')");
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES 
            (1, 'Juvenil', 15, 18)");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus) VALUES 
            (1, 1, 1, 1, 10, 65, 175, '2026-01-15', 1),
            (2, 2, 1, 1, 7, 60, 170, '2026-01-15', 1)");

        // 6. Participaciones
        // Torneo 1 (Finalizado): Part 1 (Equipo 1) y Part 2 (Equipo 2)
        // Torneo 2 (Finalizado): Part 3 (Equipo 1)
        // Torneo 3 (En Curso): Part 4 (Equipo 1)
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES 
            (1, 1, 1),
            (2, 1, 2),
            (3, 2, 1),
            (4, 3, 1)");

        // 7. Detalles de Participación
        // Detalle 1: Part 1 (Torneo 1), Atleta 1 -> Activa
        // Detalle 2: Part 1 (Torneo 1), Atleta 2 -> Activa
        // Detalle 3: Part 3 (Torneo 2), Atleta 1 -> Activa (Atleta 1 ha jugado en 2 torneos)
        // Detalle 4: Part 2 (Torneo 1), Atleta 3 -> Inactiva (estatus_estadistica = 0)
        $this->pdo->exec("INSERT INTO detalles_participacion 
            (codigo_dtll_prtc, codigo_participacion, codigo_atleta, goles, asistencias, penalizaciones, goles_contra, partidos_jugados, average, estatus_estadistica) VALUES 
            (1, 1, 1, 5, 3, 1, 0, 4, 1.25, 1),
            (2, 1, 2, 2, 1, 0, 0, 3, 0.67, 1),
            (3, 3, 1, 4, 2, 0, 0, 2, 2.00, 1),
            (4, 2, 3, 0, 0, 0, 0, 0, 0.00, 0)");
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-21: Consulta general de estadísticas activas y función ObtenerTorneosAtleta.
     * Verifica que solo retorne registros con estatus_estadistica = 1, ordenados por codigo_atleta ASC, t.fecha_inicio DESC.
     */
    public function testConsultarGeneralEstadisticasActivas(): void
    {
        $resultado = $this->modelo->Consultar([]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertCount(3, $resultado['datos'], 'Deben listarse exactamente los 3 registros activos.');

        // Registro 1: Atleta 1 en Torneo 2 (más reciente en fecha_inicio 2026-11-01)
        $fila0 = $resultado['datos'][0];
        $this->assertEquals(1, (int)$fila0['id_atleta']);
        $this->assertEquals('Liga Clausura 2026', $fila0['torneo_nombre']);
        $this->assertEquals(2, (int)$fila0['total_torneos_jugados'], 'Atleta 1 ha participado en 2 torneos distintos.');

        // Registro 2: Atleta 1 en Torneo 1 (fecha_inicio 2026-05-10)
        $fila1 = $resultado['datos'][1];
        $this->assertEquals(1, (int)$fila1['id_atleta']);
        $this->assertEquals('Copa Oro 2026', $fila1['torneo_nombre']);

        // Registro 3: Atleta 2 en Torneo 1
        $fila2 = $resultado['datos'][2];
        $this->assertEquals(2, (int)$fila2['id_atleta']);
        $this->assertEquals('Carlos', $fila2['nombres']);
        $this->assertEquals('Mendoza', $fila2['apellidos']);
        $this->assertEquals(1, (int)$fila2['total_torneos_jugados']);
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-22: Consulta con filtro de texto general por nombre del atleta.
     */
    public function testConsultarConFiltroNombreAtleta(): void
    {
        $resultado = $this->modelo->Consultar(['filtro' => 'Carlos']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Carlos', $resultado['datos'][0]['nombres']);
        $this->assertEquals('Mendoza', $resultado['datos'][0]['apellidos']);
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-23: Consulta con filtro de texto general por apellido del atleta.
     */
    public function testConsultarConFiltroApellidoAtleta(): void
    {
        $resultado = $this->modelo->Consultar(['filtro' => 'Reinoso']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        foreach ($resultado['datos'] as $item) {
            $this->assertEquals('Reinoso', $item['apellidos']);
        }
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-24: Consulta con filtro de texto general por nombre del torneo.
     */
    public function testConsultarConFiltroNombreTorneo(): void
    {
        $resultado = $this->modelo->Consultar(['filtro' => 'Clausura']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Liga Clausura 2026', $resultado['datos'][0]['torneo_nombre']);
        $this->assertEquals(4, (int)$resultado['datos'][0]['goles']);
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-25: Consulta con filtro específico por ID de participación.
     */
    public function testConsultarConFiltroParticipacion(): void
    {
        $resultado = $this->modelo->Consultar(['participacion' => 1]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos'], 'Participación 1 tiene 2 atletas con estadísticas activas.');
        $idsAtletas = array_map('intval', array_column($resultado['datos'], 'id_atleta'));
        $this->assertContains(1, $idsAtletas);
        $this->assertContains(2, $idsAtletas);
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-26: Consulta con filtro específico por ID de atleta.
     */
    public function testConsultarConFiltroAtleta(): void
    {
        $resultado = $this->modelo->Consultar(['atleta' => 2]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(2, (int)$resultado['datos'][0]['id_atleta']);
        $this->assertEquals(2, (int)$resultado['datos'][0]['goles']);
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-27: Búsqueda individual directa por ID con documento propio de identidad (identidad_atleta).
     */
    public function testBuscarEstadisticaPorIdConCedulaDirecta(): void
    {
        $resultado = $this->modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);

        $detalle = $resultado['datos'][0];
        $this->assertEquals(1, (int)$detalle['id_estadisticas']);
        $this->assertEquals('V-28123456', $detalle['cedula'], 'Debe retornar la cédula de la tabla identidad_atleta.');
        $this->assertEquals('Diego', $detalle['nombres']);
        $this->assertEquals('Reinoso', $detalle['apellidos']);
        $this->assertEquals(5, (int)$detalle['goles']);
        $this->assertEquals(3, (int)$detalle['asistencias']);
        $this->assertEquals('1.25', (string)$detalle['average']);
        $this->assertEquals(2, (int)$detalle['total_torneos_jugados']);
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-28: Búsqueda individual de estadística con cédula proveniente de representante (CONCAT 'R-').
     */
    public function testBuscarEstadisticaPorIdConCedulaRepresentante(): void
    {
        $resultado = $this->modelo->Buscar(2);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);

        $detalle = $resultado['datos'][0];
        $this->assertEquals(2, (int)$detalle['id_estadisticas']);
        $this->assertEquals('R-15987654', $detalle['cedula'], 'Debe formatearse con el prefijo R- y la cédula del representante.');
        $this->assertEquals('Carlos', $detalle['nombres']);
        $this->assertEquals('Mendoza', $detalle['apellidos']);
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-29: Búsqueda individual de estadística despachada a través de ProcesarDatos.
     */
    public function testBuscarEstadisticaDespachadaPorProcesarDatos(): void
    {
        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'buscar',
            'id'     => 3
        ]);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(3, (int)$resultado['datos'][0]['id_estadisticas']);
        $this->assertEquals(4, (int)$resultado['datos'][0]['goles']);
        $this->assertEquals('Liga Clausura 2026', $resultado['datos'][0]['torneo_nombre']);
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-30: Búsqueda de estadística con ID inexistente maneja excepción y retorna error.
     */
    public function testBuscarEstadisticaIdInexistenteRetornaError(): void
    {
        $resultado = $this->modelo->Buscar(99999);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Registro de estadísticas no encontrado.', $resultado['mensaje']);
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-31: Consulta de torneos finalizados con participaciones registradas (ConsultarTorneosConParticipacion).
     * Verifica que solo devuelva torneos con estatus = 3 y que posean al menos una participación (excluyendo torneos en curso o sin equipos).
     */
    public function testConsultarTorneosConParticipacion(): void
    {
        $resultado = $this->modelo->ConsultarTorneosConParticipacion();

        $this->assertEquals('torneos', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);

        // Deben aparecer Torneo 1 (Copa Oro 2026) y Torneo 2 (Liga Clausura 2026).
        // Torneo 3 está en curso (estatus = 1) y Torneo 4 no tiene participaciones.
        $this->assertCount(2, $resultado['datos']);

        // Verificamos orden descendente por fecha_inicio
        $this->assertEquals(2, (int)$resultado['datos'][0]['codigo_torneo'], 'Torneo 2 tiene fecha_inicio más reciente (2026-11-01).');
        $this->assertEquals(1, (int)$resultado['datos'][1]['codigo_torneo'], 'Torneo 1 tiene fecha_inicio anterior (2026-05-10).');
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-32: Consulta de atletas asociados a un torneo mediante sus participaciones (ConsultarAtletasPorTorneo).
     * Verifica que recupere la información del atleta, su categoría e identificación correspondiente.
     */
    public function testConsultarAtletasPorTorneo(): void
    {
        $resultado = $this->modelo->ConsultarAtletasPorTorneo(1);

        $this->assertEquals('atletas_participacion', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertCount(3, $resultado['datos'], 'En Torneo 1 participan Atleta 1, Atleta 2 y Atleta 3.');

        // Ordenados por p_nombre ASC: Carlos (2), Diego (1), Luis (3)
        $nombres = array_column($resultado['datos'], 'p_nombre');
        $this->assertEquals(['Carlos', 'Diego', 'Luis'], $nombres);

        // Verificamos resolución de documento de identidad y categoría para Diego y Carlos
        $atletaCarlos = $resultado['datos'][0];
        $this->assertEquals('Carlos', $atletaCarlos['p_nombre']);
        $this->assertEquals('Juvenil', $atletaCarlos['categoria']);
        $this->assertEquals('R-15987654', $atletaCarlos['documento_identidad']);

        $atletaDiego = $resultado['datos'][1];
        $this->assertEquals('Diego', $atletaDiego['p_nombre']);
        $this->assertEquals('Juvenil', $atletaDiego['categoria']);
        $this->assertEquals('V-28123456', $atletaDiego['documento_identidad']);
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-33: Validación de estadísticas duplicadas (validarEstadisticaDuplicada).
     */
    public function testValidarEstadisticaDuplicada(): void
    {
        // 1. Participación 1, Atleta 1 tiene estatus_estadistica = 1 -> Debe retornar true
        $this->assertTrue($this->modelo->validarEstadisticaDuplicada(1, 1));

        // 2. Participación 2, Atleta 3 tiene estatus_estadistica = 0 (inactiva) -> Debe retornar false
        $this->assertFalse($this->modelo->validarEstadisticaDuplicada(2, 3));

        // 3. Participación 1, Atleta 3 no existe en esa participación -> Debe retornar false
        $this->assertFalse($this->modelo->validarEstadisticaDuplicada(1, 3));
    }

    /**
     * ESC-INT-EST-04 / CP-INT-EST-34: Validación de duplicado propio (validarEstadisticaDuplicadaPropia).
     */
    public function testValidarEstadisticaDuplicadaPropia(): void
    {
        // 1. Mismo registro (Detalle 1: Part 1, Atleta 1 con id = 1) -> Debe retornar false (no colisiona con otro)
        $this->assertFalse($this->modelo->validarEstadisticaDuplicadaPropia(1, 1, 1));

        // 2. Comprobar si (Part 1, Atleta 1) existe en OTRO registro diferente a id = 2 -> Debe retornar true
        $this->assertTrue($this->modelo->validarEstadisticaDuplicadaPropia(1, 1, 2));

        // 3. Comprobar tupla inexistente -> Debe retornar false
        $this->assertFalse($this->modelo->validarEstadisticaDuplicadaPropia(2, 1, 1));
    }
}
