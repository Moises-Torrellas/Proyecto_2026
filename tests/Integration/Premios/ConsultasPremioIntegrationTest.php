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
class ConsultasPremioIntegrationTest extends TestCase
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

    private function sembrarPremiosBase(): void
    {
        $this->pdo->exec("INSERT INTO premios (codigo_premio, nombre, tipo) VALUES 
                          (1, 'Campeón Clausura', 'G'),
                          (2, 'Subcampeón Clausura', 'G'),
                          (3, 'Jugador Más Valioso', 'I'),
                          (4, 'Mejor Bloqueador', 'I')");
    }

    /**
     * ESC-INT-PREM-04 / CP-INT-PREM-16: Búsqueda directa de premio por ID.
     */
    public function testBuscarPremioPorIdDirecto(): void
    {
        $this->sembrarPremiosBase();

        $resultado = $this->modelo->Buscar(3);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);

        $premio = $resultado['datos'][0];
        $this->assertEquals(3, (int)$premio['codigo_premio']);
        $this->assertEquals('Jugador Más Valioso', $premio['nombre']);
        $this->assertEquals('I', $premio['tipo']);
    }

    /**
     * ESC-INT-PREM-04 / CP-INT-PREM-17: Búsqueda de premio por ID despachada a través de ProcesarDatos.
     */
    public function testBuscarPremioPorProcesarDatos(): void
    {
        $this->sembrarPremiosBase();

        $datos = [
            'accion'        => 'buscar',
            'codigo_premio' => 1
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(1, (int)$resultado['datos'][0]['codigo_premio']);
        $this->assertEquals('Campeón Clausura', $resultado['datos'][0]['nombre']);
        $this->assertEquals('G', $resultado['datos'][0]['tipo']);
    }

    /**
     * ESC-INT-PREM-04 / CP-INT-PREM-18: Consulta general de premios sin filtros.
     * Verifica que retorne todos los premios ordenados por codigo_premio ASC.
     */
    public function testConsultarGeneralPremios(): void
    {
        $this->sembrarPremiosBase();

        $resultado = $this->modelo->Consultar([]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(4, $resultado['datos']);

        // Verificamos el orden ascendente
        $this->assertEquals(1, (int)$resultado['datos'][0]['codigo_premio']);
        $this->assertEquals(2, (int)$resultado['datos'][1]['codigo_premio']);
        $this->assertEquals(3, (int)$resultado['datos'][2]['codigo_premio']);
        $this->assertEquals(4, (int)$resultado['datos'][3]['codigo_premio']);
    }

    /**
     * ESC-INT-PREM-04 / CP-INT-PREM-19: Consulta con filtro general de búsqueda (LIKE nombre o tipo).
     */
    public function testConsultarConFiltroGeneral(): void
    {
        $this->sembrarPremiosBase();

        // 1. Filtro por término 'Clausura' (debe coincidir con Campeón y Subcampeón)
        $resClausura = $this->modelo->Consultar(['filtro' => 'Clausura']);
        $this->assertEquals('consultar', $resClausura['accion']);
        $this->assertCount(2, $resClausura['datos']);

        // 2. Filtro por término 'Valioso'
        $resValioso = $this->modelo->Consultar(['filtro' => 'Valioso']);
        $this->assertCount(1, $resValioso['datos']);
        $this->assertEquals('Jugador Más Valioso', $resValioso['datos'][0]['nombre']);
    }

    /**
     * ESC-INT-PREM-04 / CP-INT-PREM-20: Consulta filtrando por tipo de premio ('I' o 'G') vía ProcesarDatos.
     */
    public function testConsultarPorTipoPremio(): void
    {
        $this->sembrarPremiosBase();

        // Filtrar premios individuales ('I')
        $datosIndiv = [
            'accion' => 'generar',
            'tipo'   => 'I'
        ];

        $resIndiv = $this->modelo->ProcesarDatos($datosIndiv);
        $this->assertEquals('consultar', $resIndiv['accion']);
        $this->assertCount(2, $resIndiv['datos']);
        foreach ($resIndiv['datos'] as $p) {
            $this->assertEquals('I', $p['tipo']);
        }

        // Filtrar premios grupales ('G')
        $datosGrup = [
            'accion' => 'generar',
            'tipo'   => 'G'
        ];

        $resGrup = $this->modelo->ProcesarDatos($datosGrup);
        $this->assertEquals('consultar', $resGrup['accion']);
        $this->assertCount(2, $resGrup['datos']);
        foreach ($resGrup['datos'] as $p) {
            $this->assertEquals('G', $p['tipo']);
        }
    }

    /**
     * ESC-INT-PREM-04 / CP-INT-PREM-21: Consulta combinada con filtros de prefijo de nombre y tipo.
     */
    public function testConsultarCombinadoNombreYTipo(): void
    {
        $this->sembrarPremiosBase();

        $datos = [
            'accion' => 'generar',
            'nombre' => 'Mejor',
            'tipo'   => 'I'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Mejor Bloqueador', $resultado['datos'][0]['nombre']);
        $this->assertEquals('I', $resultado['datos'][0]['tipo']);
    }

    /**
     * ESC-INT-PREM-04 / CP-INT-PREM-22: Validación exitosa de tipo de premio existente.
     */
    public function testValidarTipoPremioExitoso(): void
    {
        $this->sembrarPremiosBase();

        // No debe lanzar excepción
        $this->modelo->validarTipoPremio(1, 'G');
        $this->modelo->validarTipoPremio(3, 'I');
        $this->assertTrue(true, 'La validación del tipo de premio concluyó exitosamente.');
    }

    /**
     * ESC-INT-PREM-04 / CP-INT-PREM-23: Validación de tipo de premio lanza excepción INVALID_ID para ID inexistente.
     */
    public function testValidarTipoPremioIdInexistenteLanzaExcepcion(): void
    {
        $this->sembrarPremiosBase();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(INVALID_ID);

        $this->modelo->validarTipoPremio(9999, 'I');
    }

    /**
     * ESC-INT-PREM-04 / CP-INT-PREM-24: Validación de tipo de premio lanza excepción VALIDATION por incongruencia de tipo.
     */
    public function testValidarTipoPremioIncongruenteLanzaExcepcion(): void
    {
        $this->sembrarPremiosBase();

        // El premio 1 es 'G' (Grupal), se espera 'I'
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(VALIDATION);

        $this->modelo->validarTipoPremio(1, 'I');
    }

    /**
     * ESC-INT-PREM-04 / CP-INT-PREM-25: Invocación de procedimiento almacenado AsignarPremioAtleta.
     */
    public function testAsignarPremioAtletaProcedimiento(): void
    {
        $resultado = $this->modelo->AsignarPremioAtleta(1, 'Premio Trayectoria', 100.00);

        $this->assertIsArray($resultado);
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Premio registrado exitosamente para el atleta.', $resultado['mensaje']);
    }
}
