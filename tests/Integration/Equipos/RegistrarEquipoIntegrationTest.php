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
class RegistrarEquipoIntegrationTest extends TestCase
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
            (4, 'Pedro', 'Gomez', 'H', '2014-06-01', 'foto4.jpg'),
            (5, 'Luis', 'Ramirez', 'H', '1995-10-12', 'foto5.jpg'),
            (6, 'Mario', 'Inactivo', 'H', '2010-01-01', 'foto6.jpg')");

        // Inscripciones activas
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus) VALUES 
            (1, 1, 2, 1, 10, 65, 170, '2026-01-01', 1),
            (2, 2, 2, 1, 11, 66, 171, '2026-01-01', 1),
            (3, 3, 3, 1, 12, 55, 160, '2026-01-01', 1),
            (4, 4, 4, 1, 13, 45, 150, '2026-01-01', 1),
            (5, 5, 1, 1, 14, 80, 180, '2026-01-01', 1)");
    }

    public function testRegistrarEquipoSinAtletasExitoso(): void
    {
        $datos = [
            'accion' => 'incluir',
            'nombre' => 'cannibals sub 18',
            'atletas' => []
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('incluir', $resultado['accion']);
        $this->assertEquals('Equipo registrado correctamente.', $resultado['mensaje']);

        $stmt = $this->pdo->prepare("SELECT * FROM equipos WHERE nombre = :nombre");
        $stmt->execute([':nombre' => 'Cannibals Sub 18']);
        $equipoDB = $stmt->fetch();

        $this->assertNotEmpty($equipoDB, 'El equipo debe persistirse en BD.');
        $this->assertEquals('Cannibals Sub 18', $equipoDB['nombre'], 'El nombre debe almacenarse en Title Case.');
    }

    public function testRegistrarEquipoConAtletasMismaCategoriaExitoso(): void
    {
        // Atletas 1 y 2 pertenecen a la categoría 2 (U-17)
        $datos = [
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Juvenil A',
            'atletas' => [1, 2]
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('incluir', $resultado['accion']);

        // Verificar que el equipo y sus atletas se insertaron
        $stmtEq = $this->pdo->query("SELECT codigo_equipo FROM equipos WHERE nombre = 'Cannibals Juvenil A'");
        $idEquipo = $stmtEq->fetchColumn();
        $this->assertNotEmpty($idEquipo);

        $stmtDet = $this->pdo->prepare("SELECT codigo_atleta FROM detalles_equipos WHERE codigo_equipo = ? ORDER BY codigo_atleta ASC");
        $stmtDet->execute([$idEquipo]);
        $atletasDB = $stmtDet->fetchAll(PDO::FETCH_COLUMN, 0);

        $this->assertEquals([1, 2], $atletasDB);
    }

    public function testRegistrarEquipoConAtletasCategoriasAdyacentesExitoso(): void
    {
        // Atleta 1 (Cat 2 U-17) y Atleta 3 (Cat 3 U-15): categorías inmediatamente contiguas (ranking 1 y 2)
        $datos = [
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Mixto Contiguo',
            'atletas' => [1, 3]
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('incluir', $resultado['accion']);

        $stmtCount = $this->pdo->query("SELECT COUNT(*) FROM detalles_equipos");
        $this->assertEquals(2, $stmtCount->fetchColumn());
    }

    public function testRegistrarEquipoRechazaNombreDuplicado(): void
    {
        // Sembrar equipo inicial
        $this->pdo->exec("INSERT INTO equipos (nombre) VALUES ('Cannibals Elite')");

        $datos = [
            'accion' => 'incluir',
            'nombre' => 'cannibals elite',
            'atletas' => []
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM equipos WHERE nombre = 'Cannibals Elite'");
        $this->assertEquals(1, $stmt->fetchColumn(), 'No deben existir equipos duplicados.');
    }

    public function testRegistrarEquipoRechazaMasDeDosCategoriasDistintas(): void
    {
        // Atleta 1 (Cat 2), Atleta 3 (Cat 3), Atleta 5 (Cat 1): 3 categorías distintas
        $datos = [
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Multicategoria',
            'atletas' => [1, 3, 5]
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Un equipo no puede tener atletas de más de 2 categorías distintas.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM equipos WHERE nombre = 'Cannibals Multicategoria'");
        $this->assertEquals(0, $stmt->fetchColumn(), 'No debe persistirse el equipo si falla la regla de categorías.');
    }

    public function testRegistrarEquipoRechazaCategoriasNoAdyacentes(): void
    {
        // Atleta 5 (Cat 1 Senior, idx 0) y Atleta 4 (Cat 4 U-12, idx 3): diferencia de ranking = 3 > 1
        $datos = [
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Desproporcionado',
            'atletas' => [5, 4]
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El equipo solo puede conformarse por atletas de la misma categoría o de una categoría inferior inmediata.', $resultado['mensaje']);
    }

    public function testRegistrarEquipoRechazaAtletasSinInscripcionActiva(): void
    {
        // Atleta 6 no tiene inscripciones activas
        $datos = [
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals Sin Registro',
            'atletas' => [6]
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('No se pudo determinar la categoría de los atletas', $resultado['mensaje']);
    }

    public function testRegistrarEquipoRechazaNombreInvalido(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido. Solo se permiten letras, números, espacios y guiones.');

        $datos = [
            'accion'  => 'incluir',
            'nombre'  => 'Cannibals @@@ $$$',
            'atletas' => []
        ];

        $this->modelo->ProcesarDatos($datos);
    }
}

