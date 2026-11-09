<?php

declare(strict_types=1);

namespace tests\Integration\Conceptos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloConceptos;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('DUPLICATE_NAME')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("conceptos")]
class RegistrarConceptoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloConceptos $modelo;

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

        $this->modelo = new ModeloConceptos();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE vueltos;");
        $this->pdo->exec("TRUNCATE TABLE detalles_pagos;");
        $this->pdo->exec("TRUNCATE TABLE pagos;");
        $this->pdo->exec("TRUNCATE TABLE cargos;");
        $this->pdo->exec("TRUNCATE TABLE conceptos;");
        $this->pdo->exec("TRUNCATE TABLE monedas;");
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
     * Inserción exitosa de un concepto de cargo regular.
     */
    public function testRegistroExitoso(): void
    {
        $datos = [
            'accion'     => 'incluir',
            'nombre'     => 'mensualidad general',
            'monto'      => '35.00',
            'frecuencia' => 'M',
            'dias'       => '5'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD que se guardó en formato Title Case y con estatus 1 por defecto
        $stmt = $this->pdo->query("SELECT * FROM conceptos WHERE nombre = 'Mensualidad General'");
        $concepto = $stmt->fetch();

        $this->assertNotEmpty($concepto, 'El concepto debió insertarse en la tabla conceptos.');
        $this->assertEquals('Mensualidad General', $concepto['nombre']);
        $this->assertEquals(35.00, (float)$concepto['monto']);
        $this->assertEquals('M', $concepto['frecuencia']);
        $this->assertEquals(5, (int)$concepto['dias_gracia']);
        $this->assertEquals(1, (int)$concepto['estatus']);
    }

    /**
     * Inserción exitosa de un concepto de tipo multa (frecuencia T).
     */
    public function testRegistroMultaExitoso(): void
    {
        $datos = [
            'accion'     => 'incluir',
            'nombre'     => 'Recargo Por Mora',
            'monto'      => '5.00',
            'frecuencia' => 'T',
            'dias'       => '0'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT * FROM conceptos WHERE frecuencia = 'T'");
        $multa = $stmt->fetch();

        $this->assertNotEmpty($multa);
        $this->assertEquals('Recargo Por Mora', $multa['nombre']);
        $this->assertEquals('T', $multa['frecuencia']);
    }

    /**
     * Rechazo por nombre duplicado.
     */
    public function testErrorNombreDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO conceptos (nombre, monto, frecuencia, dias_gracia, estatus) 
                          VALUES ('Inscripcion Anual', 25.00, 'A', 0, 1)");

        $datos = [
            'accion'     => 'incluir',
            'nombre'     => 'inscripcion anual', // Mismo nombre
            'monto'      => '30.00',
            'frecuencia' => 'A',
            'dias'       => '0'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM conceptos");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * Rechazo si ya existe un concepto activo de tipo multa (MULTA_EXISTENTE).
     */
    public function testErrorMultaExistente(): void
    {
        // 1. Ya existe una multa activa en el sistema
        $this->pdo->exec("INSERT INTO conceptos (nombre, monto, frecuencia, dias_gracia, estatus) 
                          VALUES ('Multa Por Retraso', 5.00, 'T', 0, 1)");

        // 2. Intentamos registrar otra multa activa
        $datos = [
            'accion'     => 'incluir',
            'nombre'     => 'Segunda Multa',
            'monto'      => '10.00',
            'frecuencia' => 'T',
            'dias'       => '0'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('MULTA_EXISTENTE', $resultado['codigo'], 'Debe rechazar la creación de múltiples multas activas.');
    }
}

