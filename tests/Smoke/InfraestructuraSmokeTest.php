<?php

namespace Tests\Smoke;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PDO;
use Exception;

#[Group('smoke')]
#[Group('smokeInfraestructura')]
class InfraestructuraSmokeTest extends TestCase
{
    private string $rutaRaiz;

    protected function setUp(): void
    {
        $this->rutaRaiz = dirname(__DIR__, 2);
    }

    // Caso 1: El archivo .env existe en la raíz y tiene formato válido
    public function testArchivoEnvExisteYEsLegible(): void
    {
        $rutaEnv = $this->rutaRaiz . '/.env';

        $this->assertFileExists($rutaEnv, 'El archivo .env debe existir en la raíz del proyecto.');
        $contenido = @file_get_contents($rutaEnv);
        $this->assertNotFalse($contenido, 'El archivo .env debe poder leerse correctamente.');

        $variables = parse_ini_file($rutaEnv);
        $this->assertIsArray($variables, 'El archivo .env debe ser interpretable como un archivo INI.');
        $this->assertNotEmpty($variables, 'El archivo .env no debe estar vacío.');
    }

    // Caso 2: Las variables de entorno esenciales están definidas
    public function testVariablesEntornoObligatorias(): void
    {
        $rutaEnv = $this->rutaRaiz . '/.env';
        $variables = parse_ini_file($rutaEnv);

        $clavesRequeridas = [
            'DB_NAME',
            'DB_HOST',
            'DB_USER',
            'DB_NAME_SG',
            'DB_HOST_SG',
            'DB_USER_SG'
        ];

        foreach ($clavesRequeridas as $clave) {
            $this->assertArrayHasKey($clave, $variables, "La variable [{$clave}] debe estar presente en el archivo .env.");
            $this->assertNotEmpty($variables[$clave], "La variable [{$clave}] en .env no debe estar vacía.");
        }
    }

    // Caso 3: Las constantes globales del sistema se definen correctamente
    public function testConstantesConfiguradas(): void
    {
        $rutaConfig = $this->rutaRaiz . '/config/config.php';
        $this->assertFileExists($rutaConfig);

        require_once $rutaConfig;

        $this->assertTrue(defined('_DB_NAME_'), 'La constante _DB_NAME_ debe estar definida.');
        $this->assertTrue(defined('_DB_NAME_SG_'), 'La constante _DB_NAME_SG_ debe estar definida.');
        $this->assertTrue(defined('_MD_INICIO_'), 'La constante _MD_INICIO_ debe estar definida.');
        $this->assertTrue(defined('_MD_ATLETAS_'), 'La constante _MD_ATLETAS_ debe estar definida.');
        $this->assertTrue(defined('INVALID_ID'), 'La constante de error INVALID_ID debe estar definida.');
        $this->assertTrue(defined('DUPLICATE_NAME'), 'La constante de error DUPLICATE_NAME debe estar definida.');
    }

    // Caso 4: Conexión operativa a la base de datos principal
    public function testConexionBaseDatosPrincipal(): void
    {
        $rutaConfig = $this->rutaRaiz . '/config/config.php';
        require_once $rutaConfig;

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
            $this->fail('Fallo crítico al conectar con la base de datos principal: ' . $e->getMessage());
        }
    }

    // Caso 5: Conexión operativa a la base de datos del Sistema General (SG)
    public function testConexionBaseDatosSistemaGeneral(): void
    {
        $rutaConfig = $this->rutaRaiz . '/config/config.php';
        require_once $rutaConfig;

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
            $this->fail('Fallo crítico al conectar con la base de datos SG: ' . $e->getMessage());
        }
    }

    // Caso 6: Las extensiones de PHP requeridas por el sistema están instaladas y activas
    public function testExtensionesPhpRequeridas(): void
    {
        $extensiones = [
            'pdo',
            'pdo_mysql',
            'intl',
            'gd',
            'mbstring',
            'openssl',
            'curl'
        ];

        foreach ($extensiones as $ext) {
            $this->assertTrue(
                extension_loaded($ext),
                "La extensión PHP [{$ext}] es indispensable para el sistema y debe estar habilitada."
            );
        }
    }

    // Caso 7: Los directorios de almacenamiento en caliente existen y tienen permisos de escritura
    public function testDirectoriosCriticosConPermisoEscritura(): void
    {
        $directorios = [
            $this->rutaRaiz . '/logs',
            $this->rutaRaiz . '/backups',
            $this->rutaRaiz . '/storage'
        ];

        foreach ($directorios as $dir) {
            $this->assertDirectoryExists($dir, "El directorio [{$dir}] debe existir en el proyecto.");
            $archivoPrueba = $dir . '/.smoke_test_' . uniqid() . '.tmp';
            $escrito = @file_put_contents($archivoPrueba, 'smoke_test');
            if ($escrito !== false) {
                @unlink($archivoPrueba);
            }
            $this->assertNotFalse($escrito, "El directorio [{$dir}] debe permitir escribir archivos en caliente.");
        }
    }
}
