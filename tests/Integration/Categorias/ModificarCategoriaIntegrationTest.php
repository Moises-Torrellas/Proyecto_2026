<?php

declare(strict_types=1);

namespace tests\Integration\Categorias;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCategorias;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("categorias")]
class ModificarCategoriaIntegrationTest extends TestCase
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
     * Modificación exitosa de una categoría existente.
     */
    public function testModificacionExitosa(): void
    {
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) 
                          VALUES (1, 'U-15', 13, 15)");

        $datos = [
            'accion'   => 'modificar',
            'id'       => '1',
            'nombre'   => 'SUB-15 PRO',
            'edad_min' => '13',
            'edad_max' => '15'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD
        $stmt = $this->pdo->query("SELECT * FROM categorias WHERE codigo_categoria = 1");
        $cat = $stmt->fetch();

        $this->assertEquals('SUB-15 PRO', $cat['nombre']);
        $this->assertEquals(13, (int)$cat['edad_min']);
        $this->assertEquals(15, (int)$cat['edad_max']);
    }

    /**
     * Auto-exclusión: conservar sus propios datos sin error de duplicado o solapamiento.
     */
    public function testModificacionConservarPropiosDatos(): void
    {
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) 
                          VALUES (1, 'U-15', 13, 15)");

        // Conserva el mismo nombre y el mismo rango de edad
        $datos = [
            'accion'   => 'modificar',
            'id'       => '1',
            'nombre'   => 'U-15',
            'edad_min' => '13',
            'edad_max' => '15'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
    }

    /**
     * Rechazo al intentar modificar hacia el nombre de otra categoría.
     */
    public function testErrorModificarNombreAjeno(): void
    {
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES 
                          (1, 'U-15', 13, 15),
                          (2, 'U-17', 16, 17)");

        // Categoría 2 intenta tomar el nombre de categoría 1
        $datos = [
            'accion'   => 'modificar',
            'id'       => '2',
            'nombre'   => 'U-15', // Conflicto
            'edad_min' => '16',
            'edad_max' => '17'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe otra categoría registrada con este nombre.', $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT nombre FROM categorias WHERE codigo_categoria = 2");
        $this->assertEquals('U-17', $stmt->fetchColumn());
    }

    /**
     * Rechazo al intentar modificar hacia un rango de edad solapado con otra categoría.
     */
    public function testErrorModificarSolapamientoEdad(): void
    {
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES 
                          (1, 'U-15', 13, 15),
                          (2, 'Senior', 20, 30)");

        // Categoría 2 intenta cambiar su rango a 14-22 (solapa con U-15)
        $datos = [
            'accion'   => 'modificar',
            'id'       => '2',
            'nombre'   => 'Senior',
            'edad_min' => '14',
            'edad_max' => '22'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe otra categoría que cubre este rango de edad.', $resultado['codigo']);
    }
}

