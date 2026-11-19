<?php

declare(strict_types=1);

namespace tests\Integration\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use Exception;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("premios")]
class RegistrarPremioIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloPremios $modelo;

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

        $this->modelo = new ModeloPremios();

        $this->limpiarTablas();
    }

    protected function tearDown(): void
    {
        if ($this->pdo) {
            $this->limpiarTablas();
            $reflector = new ReflectionClass(Conexion::class);
            $propiedad = $reflector->getProperty('instancia');
            $propiedad->setValue(null, null);
            $this->pdo = null;
        }
        parent::tearDown();
    }

    private function limpiarTablas(): void
    {
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE palmares_individual;");
        $this->pdo->exec("TRUNCATE TABLE palmares_grupal;");
        $this->pdo->exec("TRUNCATE TABLE premios;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    /**
     * ESC-INT-PREM-01 / CP-INT-PREM-01: Inclusión exitosa de un premio individual en base de datos.
     * Verifica la persistencia en la tabla `premios`, el formateo a Title Case y asignación del ID.
     */
    public function testRegistroPremioIndividualExitoso(): void
    {
        $datos = [
            'accion' => 'incluir',
            'nombre' => 'jugador más valioso',
            'tipo'   => 'I'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificación directa en base de datos
        $stmt = $this->pdo->prepare("SELECT * FROM premios WHERE nombre = :nombre");
        $stmt->execute([':nombre' => 'Jugador Más Valioso']);
        $premio = $stmt->fetch();

        $this->assertNotEmpty($premio, 'El premio individual debió insertarse en la tabla premios.');
        $this->assertEquals('Jugador Más Valioso', $premio['nombre'], 'El nombre debe almacenarse formateado en Title Case.');
        $this->assertEquals('I', $premio['tipo'], 'El tipo debe coincidir con individual (I).');
        $this->assertGreaterThan(0, (int)$premio['codigo_premio']);
    }

    /**
     * ESC-INT-PREM-01 / CP-INT-PREM-02: Inclusión exitosa de un premio grupal en base de datos.
     * Verifica persistencia del tipo 'G' y correcta inserción en la base de datos.
     */
    public function testRegistroPremioGrupalExitoso(): void
    {
        $datos = [
            'accion' => 'incluir',
            'nombre' => 'campeón copa oro',
            'tipo'   => 'G'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->prepare("SELECT * FROM premios WHERE nombre = :nombre");
        $stmt->execute([':nombre' => 'Campeón Copa Oro']);
        $premio = $stmt->fetch();

        $this->assertNotEmpty($premio, 'El premio grupal debió insertarse en la base de datos.');
        $this->assertEquals('Campeón Copa Oro', $premio['nombre']);
        $this->assertEquals('G', $premio['tipo'], 'El tipo debe coincidir con grupal (G).');
    }

    /**
     * ESC-INT-PREM-01 / CP-INT-PREM-03: Rechazo de inserción cuando ya existe un premio con el mismo nombre.
     * Verifica que retorne el código DUPLICATE_NAME y se ejecute rollback sin duplicar en la BD.
     */
    public function testErrorRegistroNombreDuplicado(): void
    {
        // 1. Sembrado previo
        $this->pdo->exec("INSERT INTO premios (nombre, tipo) VALUES ('Mejor Portero', 'I')");

        $datos = [
            'accion' => 'incluir',
            'nombre' => 'mejor portero', // Mismo nombre en minúsculas (el modelo normaliza)
            'tipo'   => 'I'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);

        // Verificar que no se insertó un segundo registro
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM premios WHERE nombre = 'Mejor Portero'");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'No deben existir registros duplicados en la base de datos.');
    }

    /**
     * ESC-INT-PREM-01 / CP-INT-PREM-04: Excepción al intentar procesar un arreglo de datos vacío.
     */
    public function testErrorDatosVaciosLanzaExcepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $this->modelo->ProcesarDatos([]);
    }

    /**
     * ESC-INT-PREM-01 / CP-INT-PREM-05: Excepción al intentar procesar una acción no permitida.
     */
    public function testErrorAccionInvalidaLanzaExcepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada no es válida para Premios.');

        $this->modelo->ProcesarDatos([
            'accion' => 'accion_no_soportada',
            'nombre' => 'Premio Test',
            'tipo'   => 'I'
        ]);
    }
}
