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
class ModificarEquipoIntegrationTest extends TestCase
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
        $this->pdo->exec("TRUNCATE TABLE categorias;");
        $this->pdo->exec("TRUNCATE TABLE posiciones;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        // Ranking de categorías según edad_max DESC:
        // Cat 1: Senior (max 35) -> idx 0
        // Cat 2: U-17 (max 17)   -> idx 1
        // Cat 3: U-15 (max 14)   -> idx 2
        // Cat 4: U-12 (max 12)   -> idx 3
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES 
            (1, 'Senior', 18, 35),
            (2, 'U-17', 15, 17),
            (3, 'U-15', 13, 14),
            (4, 'U-12', 10, 12)");

        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura) VALUES (1, 'Defensa', 'DEF')");

        // Atletas
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES 
            (1, 'Diego', 'Reinoso', 'H', '2009-05-10', 'foto1.jpg'),
            (2, 'Carlos', 'Mendoza', 'H', '2009-08-15', 'foto2.jpg'),
            (3, 'Juan', 'Perez', 'H', '2012-03-20', 'foto3.jpg'),
            (4, 'Pedro', 'Gomez', 'H', '2014-06-01', 'foto4.jpg')");

        // Inscripciones activas
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus) VALUES 
            (1, 1, 2, 1, 10, 65, 170, '2026-01-01', 1),
            (2, 2, 2, 1, 11, 66, 171, '2026-01-01', 1),
            (3, 3, 3, 1, 12, 55, 160, '2026-01-01', 1),
            (4, 4, 4, 1, 13, 45, 150, '2026-01-01', 1)");

        // Equipos
        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES 
            (1, 'Cannibals Alfa'),
            (2, 'Cannibals Beta')");

        // Detalles del equipo 1 con atleta 1
        $this->pdo->exec("INSERT INTO detalles_equipos (codigo_equipo, codigo_atleta) VALUES (1, 1)");
    }

    public function testModificarEquipoNombreYAtletasExitoso(): void
    {
        // Modificar equipo 1: nuevo nombre y reemplazar atletas por [2, 3] (ambos de categorías adyacentes 2 y 3)
        $datos = [
            'accion'  => 'modificar',
            'id'      => 1,
            'nombre'  => 'cannibals alfa pro',
            'atletas' => [2, 3]
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Equipo modificado correctamente.', $resultado['mensaje']);

        // Verificar actualización de nombre
        $stmtEq = $this->pdo->query("SELECT nombre FROM equipos WHERE codigo_equipo = 1");
        $this->assertEquals('Cannibals Alfa Pro', $stmtEq->fetchColumn());

        // Verificar reemplazo de atletas en detalles_equipos
        $stmtDet = $this->pdo->query("SELECT codigo_atleta FROM detalles_equipos WHERE codigo_equipo = 1 ORDER BY codigo_atleta ASC");
        $atletasActuales = $stmtDet->fetchAll(PDO::FETCH_COLUMN, 0);
        $this->assertEquals([2, 3], $atletasActuales);
    }

    public function testModificarEquipoConservandoPropioNombre(): void
    {
        $datos = [
            'accion'  => 'modificar',
            'id'      => 1,
            'nombre'  => 'Cannibals Alfa',
            'atletas' => [1, 2]
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('modificar', $resultado['accion']);

        $stmtDet = $this->pdo->query("SELECT COUNT(*) FROM detalles_equipos WHERE codigo_equipo = 1");
        $this->assertEquals(2, $stmtDet->fetchColumn());
    }

    public function testModificarEquipoRechazaNombreDuplicadoDeOtroEquipo(): void
    {
        // Intentar cambiar el nombre del equipo 1 por "Cannibals Beta" (que le pertenece al equipo 2)
        $datos = [
            'accion'  => 'modificar',
            'id'      => 1,
            'nombre'  => 'cannibals beta',
            'atletas' => [1]
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['mensaje']);

        $stmtEq = $this->pdo->query("SELECT nombre FROM equipos WHERE codigo_equipo = 1");
        $this->assertEquals('Cannibals Alfa', $stmtEq->fetchColumn(), 'El nombre original debe conservarse.');
    }

    public function testModificarEquipoRechazaViolacionReglasCategoria(): void
    {
        // Intentar reasignar al equipo 1 los atletas 1 (Cat 2) y 4 (Cat 4) que no son adyacentes
        $datos = [
            'accion'  => 'modificar',
            'id'      => 1,
            'nombre'  => 'Cannibals Alfa',
            'atletas' => [1, 4]
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('categoría inferior inmediata', $resultado['mensaje']);

        // Verificar que la plantilla original del equipo 1 no fue alterada (rollback)
        $stmtDet = $this->pdo->query("SELECT codigo_atleta FROM detalles_equipos WHERE codigo_equipo = 1");
        $this->assertEquals([1], $stmtDet->fetchAll(PDO::FETCH_COLUMN, 0));
    }

    public function testModificarEquipoRechazaIdInvalido(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        $datos = [
            'accion'  => 'modificar',
            'id'      => 'no_numerico',
            'nombre'  => 'Cannibals Alfa',
            'atletas' => []
        ];

        $this->modelo->ProcesarDatos($datos);
    }
}

