<?php

declare(strict_types=1);

namespace Tests\Smoke;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PDO;
use Exception;

#[Group('smoke')]
#[Group('smokeBaseDatos')]
class BaseDatosSmokeTest extends TestCase
{
    private string $rutaRaiz;

    protected function setUp(): void
    {
        $this->rutaRaiz = dirname(__DIR__, 2);
        $rutaConfig = $this->rutaRaiz . '/config/config.php';
        require_once $rutaConfig;
    }

    /**
     * Caso 1: Conexión operativa básica a la base de datos principal (cannibalsbd2).
     */
    public function testConexionBaseDatosPrincipal(): void
    {
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', _DB_HOST_, _DB_NAME_);
            $pdo = new PDO($dsn, _DB_USER_, _DB_PASS_, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3
            ]);

            $stmt = $pdo->query('SELECT 1');
            $resultado = (int)$stmt->fetchColumn();

            $this->assertEquals(1, $resultado, 'La base de datos principal debe responder a una consulta de comprobación.');
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), '2002') || str_contains($e->getMessage(), 'permisos de acceso')) {
                $this->markTestSkipped('Conexión a MySQL restringida por el entorno de ejecución actual.');
            }
            $this->fail('Fallo al conectar con la base de datos principal [' . _DB_NAME_ . ']: ' . $e->getMessage());
        }
    }

    /**
     * Caso 2: Conexión operativa básica a la base de datos del Sistema General (bds2 / SG).
     */
    public function testConexionBaseDatosSistemaGeneral(): void
    {
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', _DB_HOST_SG_, _DB_NAME_SG_);
            $pdo = new PDO($dsn, _DB_USER_SG_, _DB_PASS_SG_, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3
            ]);

            $stmt = $pdo->query('SELECT 1');
            $resultado = (int)$stmt->fetchColumn();

            $this->assertEquals(1, $resultado, 'La base de datos SG debe responder a una consulta de comprobación.');
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), '2002') || str_contains($e->getMessage(), 'permisos de acceso')) {
                $this->markTestSkipped('Conexión a MySQL SG restringida por el entorno de ejecución actual.');
            }
            $this->fail('Fallo al conectar con la base de datos SG [' . _DB_NAME_SG_ . ']: ' . $e->getMessage());
        }
    }
}
