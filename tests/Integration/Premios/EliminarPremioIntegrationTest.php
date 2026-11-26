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
class EliminarPremioIntegrationTest extends TestCase
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
     * ESC-INT-PREM-03 / CP-INT-PREM-12: Eliminación exitosa de un premio sin dependencias.
     * Verifica que el registro sea eliminado físicamente de la base de datos.
     */
    public function testEliminacionPremioExitosa(): void
    {
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES (1, 'Premio Por Eliminar', 'I')");

        $datos = [
            'accion'        => 'eliminar',
            'codigo_premio' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM premios WHERE codigo_premio = 1");
        $this->assertEquals(0, (int)$stmt->fetchColumn(), 'El premio debió haber sido eliminado de la base de datos.');
    }

    /**
     * ESC-INT-PREM-03 / CP-INT-PREM-13: Rechazo de eliminación cuando el ID no existe en la base de datos.
     */
    public function testErrorEliminarPremioIdInexistente(): void
    {
        $datos = [
            'accion'        => 'eliminar',
            'codigo_premio' => 99999
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    /**
     * ESC-INT-PREM-03 / CP-INT-PREM-14: Bloqueo de eliminación cuando el premio está vinculado a palmarés grupal.
     */
    public function testErrorEliminarPremioAsociadoPalmaresGrupal(): void
    {
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES (1, 'Campeón de Liga', 'G')");

        // Sembrar registro vinculado en palmares_grupal
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("INSERT INTO palmares_grupal (codigo_grupal, codigo_participacion, codigo_premio) VALUES (1, 10, 1)");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        $datos = [
            'accion'        => 'eliminar',
            'codigo_premio' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo']);

        // El registro debe permanecer en la base de datos
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM premios WHERE codigo_premio = 1");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'El premio con asociaciones no debe ser eliminado de la base de datos.');
    }

    /**
     * ESC-INT-PREM-03 / CP-INT-PREM-15: Bloqueo de eliminación cuando el premio está vinculado a palmarés individual.
     */
    public function testErrorEliminarPremioAsociadoPalmaresIndividual(): void
    {
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES (2, 'Mejor Encestador', 'I')");

        // Sembrar registro vinculado en palmares_individual
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("INSERT INTO palmares_individual (codigo_individual, codigo_premio, codigo_dtll_prtc) VALUES (1, 2, 20)");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

        $datos = [
            'accion'        => 'eliminar',
            'codigo_premio' => 2
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo']);

        // El registro debe permanecer en la base de datos
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM premios WHERE codigo_premio = 2");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'El premio con asociaciones individuales no debe ser eliminado.');
    }
}
