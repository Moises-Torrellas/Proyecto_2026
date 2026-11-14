<?php

declare(strict_types=1);

namespace tests\Integration\MetodosPago;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloMetodosPago;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("metodos_pago")]
class BloquearMetodoPagoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloMetodosPago $modelo;

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

        $this->modelo = new ModeloMetodosPago();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE metodos_pago;");
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
     * Bloqueo exitoso cambiando estatus de activo (1) a inactivo (2).
     */
    public function testBloquearMetodoExitoso(): void
    {
        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) 
                          VALUES (1, 'Pago Movil', 1, 1)");

        $datos = [
            'accion'  => 'bloquear',
            'id'      => '1',
            'bloqueo' => 1 // Indica que el estatus actual es 1 (debe pasar a 2)
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD que el estatus cambió a 2
        $stmt = $this->pdo->query("SELECT estatus FROM metodos_pago WHERE codigo_metodo = 1");
        $this->assertEquals(2, (int)$stmt->fetchColumn(), 'El método debió pasar a estatus 2 (bloqueado).');
    }

    /**
     * Desbloqueo exitoso cambiando estatus de inactivo (2) a activo (1).
     */
    public function testDesbloquearMetodoExitoso(): void
    {
        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) 
                          VALUES (1, 'Pago Movil', 1, 2)");

        $datos = [
            'accion'  => 'bloquear',
            'id'      => '1',
            'bloqueo' => 2 // Indica que el estatus actual es 2 (debe pasar a 1)
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT estatus FROM metodos_pago WHERE codigo_metodo = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'El método debió reactivarse con estatus 1.');
    }

    /**
     * Rechazo al intentar bloquear un ID inexistente (INVALID_ID).
     */
    public function testErrorBloquearIdInexistente(): void
    {
        $datos = [
            'accion'  => 'bloquear',
            'id'      => '99999',
            'bloqueo' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }
}

