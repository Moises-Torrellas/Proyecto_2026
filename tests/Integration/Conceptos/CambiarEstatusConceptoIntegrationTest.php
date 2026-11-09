<?php

declare(strict_types=1);

namespace tests\Integration\Conceptos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloConceptos;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("conceptos")]
class CambiarEstatusConceptoIntegrationTest extends TestCase
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
        $this->pdo->exec("TRUNCATE TABLE conceptos;");
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
     * Desactivación de concepto cambiando estatus de activo (1) a inactivo (2).
     */
    public function testDesactivarConceptoExitoso(): void
    {
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) 
                          VALUES (1, 'Mensualidad', 30.00, 'M', 5, 1)");

        $datos = [
            'accion'  => 'estatus',
            'id'      => '1',
            'estatus' => 1 // Estatus actual es 1, debe cambiar a 2
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD que pasó a estatus 2
        $stmt = $this->pdo->query("SELECT estatus FROM conceptos WHERE codigo_concepto = 1");
        $this->assertEquals(2, (int)$stmt->fetchColumn(), 'El concepto debió desactivarse con estatus 2.');
    }

    /**
     * Activación de concepto cambiando estatus de inactivo (2) a activo (1).
     */
    public function testActivarConceptoExitoso(): void
    {
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) 
                          VALUES (1, 'Mensualidad', 30.00, 'M', 5, 2)");

        $datos = [
            'accion'  => 'estatus',
            'id'      => '1',
            'estatus' => 2 // Estatus actual es 2, debe cambiar a 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT estatus FROM conceptos WHERE codigo_concepto = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'El concepto debió reactivarse con estatus 1.');
    }

    /**
     * Rechazo al intentar cambiar el estatus de un ID inexistente (INVALID_ID).
     */
    public function testErrorCambiarEstatusIdInexistente(): void
    {
        $datos = [
            'accion'  => 'estatus',
            'id'      => '99999',
            'estatus' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }
}

