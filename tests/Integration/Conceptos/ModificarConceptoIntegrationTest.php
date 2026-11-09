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
class ModificarConceptoIntegrationTest extends TestCase
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
     * Modificación exitosa de un concepto existente.
     */
    public function testModificacionExitosa(): void
    {
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) 
                          VALUES (1, 'Mensualidad', 30.00, 'M', 5, 1)");

        $datos = [
            'accion'     => 'modificar',
            'id'         => '1',
            'nombre'     => 'Mensualidad Preferencial',
            'monto'      => '35.50',
            'frecuencia' => 'M',
            'dias'       => '10'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD
        $stmt = $this->pdo->query("SELECT * FROM conceptos WHERE codigo_concepto = 1");
        $concepto = $stmt->fetch();

        $this->assertEquals('Mensualidad Preferencial', $concepto['nombre']);
        $this->assertEquals(35.50, (float)$concepto['monto']);
        $this->assertEquals(10, (int)$concepto['dias_gracia']);
    }

    /**
     * Auto-exclusión: conservar sus propios datos sin error de duplicado o multa existente.
     */
    public function testModificacionConservarPropiosDatos(): void
    {
        // Concepto con frecuencia T (multa)
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) 
                          VALUES (1, 'Multa General', 5.00, 'T', 0, 1)");

        $datos = [
            'accion'     => 'modificar',
            'id'         => '1',
            'nombre'     => 'Multa General', // Mismo nombre
            'monto'      => '6.00',          // Modifica monto
            'frecuencia' => 'T',             // Misma frecuencia T (debe auto-excluirse)
            'dias'       => '0'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT monto FROM conceptos WHERE codigo_concepto = 1");
        $this->assertEquals(6.00, (float)$stmt->fetchColumn());
    }

    /**
     * Rechazo al intentar modificar hacia el nombre de otro concepto existente.
     */
    public function testErrorModificarNombreAjeno(): void
    {
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) VALUES 
                          (1, 'Mensualidad', 30.00, 'M', 5, 1),
                          (2, 'Inscripcion', 25.00, 'A', 0, 1)");

        // Concepto 2 intenta cambiar su nombre por 'Mensualidad'
        $datos = [
            'accion'     => 'modificar',
            'id'         => '2',
            'nombre'     => 'Mensualidad', // Conflicto
            'monto'      => '25.00',
            'frecuencia' => 'A',
            'dias'       => '0'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);

        $stmt = $this->pdo->query("SELECT nombre FROM conceptos WHERE codigo_concepto = 2");
        $this->assertEquals('Inscripcion', $stmt->fetchColumn());
    }

    /**
     * Rechazo si otro concepto intenta cambiar su frecuencia a 'T' habiendo ya una multa activa.
     */
    public function testErrorModificarMultaDuplicada(): void
    {
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) VALUES 
                          (1, 'Multa Activa', 5.00, 'T', 0, 1),
                          (2, 'Inscripcion', 25.00, 'A', 0, 1)");

        // Concepto 2 intenta cambiar su frecuencia a 'T'
        $datos = [
            'accion'     => 'modificar',
            'id'         => '2',
            'nombre'     => 'Inscripcion Modificada',
            'monto'      => '25.00',
            'frecuencia' => 'T', // Conflicto: ya existe la multa 1
            'dias'       => '0'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('MULTA_EXISTENTE', $resultado['codigo']);
    }

    /**
     * Rechazo al intentar modificar un ID inexistente en BD (INVALID_ID).
     */
    public function testErrorModificarIdInexistente(): void
    {
        $datos = [
            'accion'     => 'modificar',
            'id'         => '99999',
            'nombre'     => 'Concepto Fantasma',
            'monto'      => '20.00',
            'frecuencia' => 'M',
            'dias'       => '5'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }
}

