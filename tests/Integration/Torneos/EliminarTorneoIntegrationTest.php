<?php

namespace tests\Integration\Torneos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloTorneos;
use App\modelo\Conexion;
use PDO;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("torneos")]
class EliminarTorneoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloTorneos $modelo;

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

        $this->modelo = new ModeloTorneos();

        $this->limpiarTablas();
        $this->sembrarDatosBase();
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
        $this->pdo->exec("TRUNCATE TABLE participaciones;");
        $this->pdo->exec("TRUNCATE TABLE torneos;");
        $this->pdo->exec("TRUNCATE TABLE equipos;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function sembrarDatosBase(): void
    {
        $fInicio = date('Y-m-d', strtotime('+5 days'));
        $fFin    = date('Y-m-d', strtotime('+15 days'));

        $this->pdo->exec("INSERT INTO torneos (codigo_torneo, nombre, fecha_inicio, fecha_fin, ubicacion, estatus) VALUES 
            (1, 'TORNEO ELIMINABLE', '{$fInicio}', '{$fFin}', 'Cancha 1', 1),
            (2, 'TORNEO CON EQUIPOS', '{$fInicio}', '{$fFin}', 'Cancha 2', 1)");

        $this->pdo->exec("INSERT INTO equipos (codigo_equipo, nombre) VALUES (10, 'Cannibals HC')");

        // Vincular torneo 2 a equipo 10 en participaciones
        $this->pdo->exec("INSERT INTO participaciones (codigo_participacion, codigo_torneo, codigo_equipo) VALUES 
            (1, 2, 10)");
    }

    public function testEliminarTorneoSinDependenciasExitoso(): void
    {
        $datos = [
            'accion'        => 'eliminar',
            'codigo_torneo' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);

        // Verificar que el torneo 1 ya no existe en la base de datos
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM torneos WHERE codigo_torneo = 1");
        $this->assertEquals(0, $stmt->fetchColumn(), 'El torneo debió ser eliminado físicamente de la base de datos.');
    }

    public function testEliminarTorneoRechazaIdInexistente(): void
    {
        $datos = [
            'accion'        => 'eliminar',
            'codigo_torneo' => 9999
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El torneo no existe.', $resultado['codigo']);
    }

    public function testEliminarTorneoConParticipacionesAsociadasRechazaPorFK(): void
    {
        // El torneo 2 está vinculado en participaciones
        $datos = [
            'accion'        => 'eliminar',
            'codigo_torneo' => 2
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertNotEmpty($resultado['codigo'], 'Debe retornar el mensaje de error de integridad foránea.');

        // Verificar que el torneo 2 no fue eliminado
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM torneos WHERE codigo_torneo = 2");
        $this->assertEquals(1, $stmt->fetchColumn(), 'El torneo con participaciones no debe ser eliminado de la base de datos.');
    }

    public function testEliminarTorneoCodigoInvalidoDisparaExcepcion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Código de torneo inválido.');

        $datos = [
            'accion'        => 'eliminar',
            'codigo_torneo' => 'invalido'
        ];

        $this->modelo->ProcesarDatos($datos);
    }
}

