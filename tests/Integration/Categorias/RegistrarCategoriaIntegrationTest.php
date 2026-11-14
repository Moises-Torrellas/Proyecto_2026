<?php

declare(strict_types=1);

namespace tests\Integration\Categorias;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCategorias;
use App\modelo\Conexion;
use Exception;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("categorias")]
class RegistrarCategoriaIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloCategorias $modelo;

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

        $this->modelo = new ModeloCategorias();

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
        $this->pdo->exec("TRUNCATE TABLE categorias;");
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
     * Inserción exitosa de una nueva categoría.
     */
    public function testRegistroExitoso(): void
    {
        $datos = [
            'accion'   => 'incluir',
            'nombre'   => 'U-15',
            'edad_min' => '13',
            'edad_max' => '15'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD
        $stmt = $this->pdo->query("SELECT * FROM categorias WHERE nombre = 'U-15'");
        $cat = $stmt->fetch();

        $this->assertNotEmpty($cat, 'La categoría debió insertarse en la tabla categorias.');
        $this->assertEquals('U-15', $cat['nombre']);
        $this->assertEquals(13, (int)$cat['edad_min']);
        $this->assertEquals(15, (int)$cat['edad_max']);
    }

    /**
     * Rechazo si el nombre de la categoría ya existe.
     */
    public function testErrorNombreDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO categorias (nombre, edad_min, edad_max) VALUES ('U-15', 13, 15)");

        $datos = [
            'accion'   => 'incluir',
            'nombre'   => 'u-15', // Misma categoría (el modelo convierte a mayúsculas)
            'edad_min' => '16',
            'edad_max' => '17'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una categoría registrada con este nombre.', $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM categorias");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * Rechazo si el rango de edad se solapa con una categoría existente.
     */
    public function testErrorSolapamientoEdad(): void
    {
        // Categoría existente de 13 a 15 años
        $this->pdo->exec("INSERT INTO categorias (nombre, edad_min, edad_max) VALUES ('U-15', 13, 15)");

        // Intentamos registrar categoría que solapa (14 a 16 años)
        $datos = [
            'accion'   => 'incluir',
            'nombre'   => 'Juvenil',
            'edad_min' => '14',
            'edad_max' => '16'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe una categoría que cubre este rango de edad.', $resultado['codigo']);
    }

    /**
     * Rechazo si la edad mínima es mayor que la edad máxima.
     */
    public function testErrorEdadMinimaMayorQueMaxima(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La edad mínima no puede ser mayor que la edad máxima.');

        $this->modelo->ProcesarDatos([
            'accion'   => 'incluir',
            'nombre'   => 'U-12',
            'edad_min' => '12',
            'edad_max' => '10'
        ]);
    }
}

