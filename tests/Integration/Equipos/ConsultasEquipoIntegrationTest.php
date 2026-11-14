<?php

namespace tests\Integration\Equipos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEquipos;
use App\modelo\Conexion;
use PDO;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("equipos")]
class ConsultasEquipoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloEquipos $modelo;

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

        $this->modelo = new ModeloEquipos();

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
        $this->pdo->exec("TRUNCATE TABLE participaciones;");
        $this->pdo->exec("TRUNCATE TABLE detalles_equipos;");
        $this->pdo->exec("TRUNCATE TABLE equipos;");
        $this->pdo->exec("TRUNCATE TABLE inscripciones;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("TRUNCATE TABLE identidad_atleta;");
        $this->pdo->exec("TRUNCATE TABLE categorias;");
        $this->pdo->exec("TRUNCATE TABLE posiciones;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES (1, 'Juvenil', 15, 17)");
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura) VALUES (1, 'Portero', 'POR')");

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2009-05-10', 'foto1.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2009-08-15', 'foto2.jpg')");

        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES 
            (1, 'V', '30123456'),
            (2, 'V', '29654321')");

        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus) VALUES 
            (1, 1, 1, 1, 10, 65, 170, '2026-01-01', 1),
            (2, 2, 1, 1, 11, 66, 171, '2026-01-01', 1)");

        // Equipos
        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES 
            (1, 'Cannibals Alfa'),
            (2, 'Cannibals Beta')");

        // Detalles del equipo 1 con atletas 1 y 2
        $this->pdo->exec("INSERT INTO detalles_equipos (codigo_equipo, codigo_atleta) VALUES 
            (1, 1),
            (1, 2)");
    }

    public function testConsultarGeneralConCalculoAtletasYPremios(): void
    {
        $resultado = $this->modelo->Consultar([]);

        $this->assertIsArray($resultado);
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        // El equipo 1 debe tener cantidad_atletas = 2
        $alfa = null;
        foreach ($resultado['datos'] as $eq) {
            if ($eq['id_equipos'] == 1) {
                $alfa = $eq;
                break;
            }
        }

        $this->assertNotNull($alfa);
        $this->assertEquals('Cannibals Alfa', $alfa['nombre']);
        $this->assertEquals(2, $alfa['cantidad_atletas']);
        $this->assertArrayHasKey('total_premios', $alfa);
    }

    public function testConsultarConFiltroTextoNombre(): void
    {
        $resultado = $this->modelo->Consultar(['filtro' => 'Beta']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Cannibals Beta', $resultado['datos'][0]['nombre']);
    }

    public function testConsultarEquiposMetodoDirecto(): void
    {
        $resultado = $this->modelo->ConsultarEquipos();

        $this->assertIsArray($resultado);
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
    }

    public function testBuscarEquipoPorId(): void
    {
        // Vía Buscar(1)
        $resDirecto = $this->modelo->Buscar(1);
        $this->assertEquals('buscar', $resDirecto['accion']);
        $this->assertNotEmpty($resDirecto['datos']);
        $this->assertEquals('Cannibals Alfa', $resDirecto['datos'][0]['nombre']);

        // Vía ProcesarDatos acción buscar
        $resProcesar = $this->modelo->ProcesarDatos(['accion' => 'buscar', 'id' => 1]);
        $this->assertEquals('buscar', $resProcesar['accion']);
        $this->assertEquals('Cannibals Alfa', $resProcesar['datos'][0]['nombre']);
    }

    public function testConsultarAtletasParaAsignacion(): void
    {
        $resultado = $this->modelo->ConsultarAtletasParaAsignacion([]);

        $this->assertIsArray($resultado);
        $this->assertEquals('consultarAtletasModal', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        $primerAtleta = $resultado['datos'][0];
        $this->assertArrayHasKey('id', $primerAtleta);
        $this->assertArrayHasKey('doc_i', $primerAtleta);
        $this->assertArrayHasKey('nombre', $primerAtleta);
        $this->assertArrayHasKey('categoria', $primerAtleta);
        $this->assertArrayHasKey('posicion', $primerAtleta);
    }

    public function testConsultarAtletasAsignadosEquipo(): void
    {
        $resultado = $this->modelo->ConsultarAtletasAsignadosEquipo(1);

        $this->assertIsArray($resultado);
        $this->assertEquals('consultarAtletasAsignadosEquipo', $resultado['accion']);
        $this->assertCount(2, $resultado['datos'], 'El equipo 1 debe tener 2 atletas asignados.');
    }

    public function testProcesarDatosRechazaDatosVacios(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar el registro del equipo.');

        $this->modelo->ProcesarDatos([]);
    }

    public function testProcesarDatosRechazaAccionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada para el equipo no es válida.');

        $this->modelo->ProcesarDatos(['accion' => 'accion_desconocida']);
    }
}

