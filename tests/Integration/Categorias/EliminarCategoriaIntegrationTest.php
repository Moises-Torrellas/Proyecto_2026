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
class EliminarCategoriaIntegrationTest extends TestCase
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
     * Eliminación exitosa de una categoría sin atletas asociados.
     */
    public function testEliminarExitoso(): void
    {
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) 
                          VALUES (1, 'U-15', 13, 15)");

        $datos = [
            'accion' => 'eliminar',
            'id'     => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('eliminar', $resultado['accion']);
        $this->assertEquals('Categoría eliminada exitosamente.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM categorias WHERE codigo_categoria = 1");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'La categoría debió ser eliminada de la base de datos.');
    }

    /**
     * Bloqueo de eliminación si la categoría tiene atletas asociados en inscripciones.
     */
    public function testErrorEliminarConAtletasAsociados(): void
    {
        // 1. Insertamos categoría
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES (1, 'U-15', 13, 15)");

        // 2. Insertamos atleta e inscripción vinculada a la categoría 1
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (1, 'Diego', 'Reinoso', 'H', '2011-05-15', 'Barquisimeto', 'default.png')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, estatus) 
                          VALUES (1, 1, 1, 1, 10, 1)");

        // 3. Intentamos eliminar la categoría
        $datos = [
            'accion' => 'eliminar',
            'id'     => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('No se puede eliminar: la categoría tiene atletas asociados.', $resultado['codigo']);

        // Verificamos que la categoría sigue existiendo en BD
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM categorias WHERE codigo_categoria = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * Rechazo al intentar eliminar una categoría con ID inexistente.
     */
    public function testErrorEliminarIdInexistente(): void
    {
        $datos = [
            'accion' => 'eliminar',
            'id'     => '99999'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('La categoría no existe.', $resultado['codigo']);
    }
}

