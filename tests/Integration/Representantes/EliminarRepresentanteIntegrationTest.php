<?php

declare(strict_types=1);

namespace tests\Integration\Representantes;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRepresentantes;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("representantes")]
class EliminarRepresentanteIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloRepresentantes $modelo;

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

        $this->modelo = new ModeloRepresentantes();

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
        $this->pdo->exec("TRUNCATE TABLE representantes;");
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
     * Eliminación exitosa de un representante sin atletas asociados.
     */
    public function testEliminarExitoso(): void
    {
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) 
                          VALUES (1, '14555666', 'V', 'Roberto', 'Mendoza', '0414-1112233', 'Av Lara', 'roberto@mail.com', '@roberto')");

        $datos = [
            'accion' => 'eliminar',
            'id'     => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos que ya no existe en la tabla
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM representantes WHERE codigo_representante = 1");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'El representante debió ser eliminado de la base de datos.');
    }

    /**
     * Bloqueo de eliminación si el representante tiene atletas asociados (ASSOCIATES).
     */
    public function testErrorEliminarConAtletasAsociados(): void
    {
        // 1. Insertamos representante
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) 
                          VALUES (1, '14555666', 'V', 'Roberto', 'Mendoza', '0414-1112233', 'Av Lara', 'roberto@mail.com', '@roberto')");

        // 2. Insertamos atleta y lo vinculamos al representante
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (1, 'Diego', 'Mendoza', 'H', '2011-05-15', 'Barquisimeto', 'default.png')");
        $this->pdo->exec("INSERT INTO atleta_representante (codigo_atleta, codigo_representante) VALUES (1, 1)");

        // 3. Intentamos eliminar el representante
        $datos = [
            'accion' => 'eliminar',
            'id'     => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo'], 'Debe rechazar la eliminación por atletas asociados (ASSOCIATES).');

        // Verificamos que el representante sigue existiendo en BD
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM representantes WHERE codigo_representante = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'El representante no debió eliminarse.');
    }

    /**
     * Rechazo si el ID del representante no existe (INVALID_ID).
     */
    public function testErrorEliminarIdInexistente(): void
    {
        $datos = [
            'accion' => 'eliminar',
            'id'     => '99999'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }
}

