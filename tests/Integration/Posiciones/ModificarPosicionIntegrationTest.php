<?php

declare(strict_types=1);

namespace tests\Integration\Posiciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPosiciones;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("posiciones")]
class ModificarPosicionIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloPosiciones $modelo;

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

        $this->modelo = new ModeloPosiciones();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE retiros;");
        $this->pdo->exec("TRUNCATE TABLE detalles_participacion;");
        $this->pdo->exec("TRUNCATE TABLE detalles_equipos;");
        $this->pdo->exec("TRUNCATE TABLE asignaciones;");
        $this->pdo->exec("TRUNCATE TABLE cargos;");
        $this->pdo->exec("TRUNCATE TABLE datos_medicos;");
        $this->pdo->exec("TRUNCATE TABLE contacto_atleta;");
        $this->pdo->exec("TRUNCATE TABLE identidad_atleta;");
        $this->pdo->exec("TRUNCATE TABLE atleta_representante;");
        $this->pdo->exec("TRUNCATE TABLE inscripciones;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("TRUNCATE TABLE posiciones;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    /**
     * Modificación exitosa de una posición existente.
     */
    public function testModificacionExitosa(): void
    {
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) 
                          VALUES (1, 'Base', 'B', 'Organizador de juego')");

        $datos = [
            'accion'      => 'modificar',
            'id'          => '1',
            'nombre'      => 'Base Armador',
            'abreviatura' => 'BA',
            'descripcion' => 'Organizador principal y estratega'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Posicion modificada exitosamente.', $resultado['mensaje']);

        // Verificamos en BD
        $stmt = $this->pdo->query("SELECT * FROM posiciones WHERE codigo_posicion = 1");
        $pos = $stmt->fetch();

        $this->assertEquals('Base Armador', $pos['nombre']);
        $this->assertEquals('BA', $pos['abreviatura']);
        $this->assertEquals('Organizador Principal Y Estratega', $pos['descripcion']);
    }

    /**
     * Auto-exclusión: conservar sus propios datos sin error de duplicado.
     */
    public function testModificacionConservarPropiosDatos(): void
    {
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) 
                          VALUES (1, 'Base', 'BA', 'Organizador de juego')");

        $datos = [
            'accion'      => 'modificar',
            'id'          => '1',
            'nombre'      => 'Base', // Mismo nombre
            'abreviatura' => 'BA',   // Misma abreviatura
            'descripcion' => 'Nueva descripcion actualizada'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('modificar', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT descripcion FROM posiciones WHERE codigo_posicion = 1");
        $this->assertEquals('Nueva Descripcion Actualizada', $stmt->fetchColumn());
    }

    /**
     * Rechazo al intentar modificar hacia el nombre de otra posición.
     */
    public function testErrorModificarNombreAjeno(): void
    {
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) VALUES 
                          (1, 'Base', 'BA', 'Organizador'),
                          (2, 'Escolta', 'ES', 'Tirador')");

        // Posición 2 intenta usar el nombre de la posición 1
        $datos = [
            'accion'      => 'modificar',
            'id'          => '2',
            'nombre'      => 'Base', // Conflicto con posición 1
            'abreviatura' => 'ES',
            'descripcion' => 'Tirador'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una posición registrada con este nombre.', $resultado['mensaje']);

        // Verificamos que el nombre en BD no se modificó
        $stmt = $this->pdo->query("SELECT nombre FROM posiciones WHERE codigo_posicion = 2");
        $this->assertEquals('Escolta', $stmt->fetchColumn());
    }

    /**
     * Rechazo al intentar modificar hacia la abreviatura de otra posición.
     */
    public function testErrorModificarAbreviaturaAjena(): void
    {
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) VALUES 
                          (1, 'Base', 'BA', 'Organizador'),
                          (2, 'Escolta', 'ES', 'Tirador')");

        // Posición 2 intenta usar la abreviatura de la posición 1
        $datos = [
            'accion'      => 'modificar',
            'id'          => '2',
            'nombre'      => 'Escolta',
            'abreviatura' => 'BA', // Conflicto con posición 1
            'descripcion' => 'Tirador'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una posición registrada con esta abreviatura.', $resultado['mensaje']);
    }
}
