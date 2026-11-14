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
class RegistrarPosicionIntegrationTest extends TestCase
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
     * Inserción exitosa de una nueva posición.
     */
    public function testRegistroExitoso(): void
    {
        $datos = [
            'accion'      => 'incluir',
            'nombre'      => 'Alero Fuerte',
            'abreviatura' => 'AF',
            'descripcion' => 'Jugador ofensivo y reboteador'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('incluir', $resultado['accion']);
        $this->assertEquals('Posicion registrada exitosamente.', $resultado['mensaje']);

        // Verificamos en BD
        $stmt = $this->pdo->query("SELECT * FROM posiciones WHERE abreviatura = 'AF'");
        $pos = $stmt->fetch();

        $this->assertNotEmpty($pos, 'La posición debió registrarse en la tabla posiciones.');
        $this->assertEquals('Alero Fuerte', $pos['nombre']);
        $this->assertEquals('AF', $pos['abreviatura']);
        $this->assertEquals('Jugador Ofensivo Y Reboteador', $pos['descripcion']);
    }

    /**
     * Rechazo si el nombre de la posición ya existe.
     */
    public function testErrorNombreDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO posiciones (nombre, abreviatura, descripcion) VALUES ('Base', 'B', 'Armador')");

        $datos = [
            'accion'      => 'incluir',
            'nombre'      => 'Base', // Mismo nombre
            'abreviatura' => 'BA',
            'descripcion' => 'Organizador principal'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una posición registrada con este nombre.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM posiciones");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * Rechazo si la abreviatura de la posición ya existe.
     */
    public function testErrorAbreviaturaDuplicada(): void
    {
        $this->pdo->exec("INSERT INTO posiciones (nombre, abreviatura, descripcion) VALUES ('Base', 'BA', 'Armador')");

        $datos = [
            'accion'      => 'incluir',
            'nombre'      => 'Escolta',
            'abreviatura' => 'BA', // Misma abreviatura
            'descripcion' => 'Tirador perimetral'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una posición registrada con esta abreviatura.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM posiciones");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }
}
