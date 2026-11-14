<?php

declare(strict_types=1);

namespace tests\Integration\Posiciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPosiciones;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("posiciones")]
class ConsultasPosicionIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloPosiciones $modelo;

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

        $this->modelo = new ModeloPosiciones();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE retiros;");
        $this->pdo->exec("TRUNCATE TABLE detalles_participacion;");
        $this->pdo->exec("TRUNCATE TABLE detalles_equipos;");
        $this->pdo->exec("TRUNCATE TABLE asignaciones;");
        $this->pdo->exec("TRUNCATE TABLE cargos;");
        $this->pdo->exec("TRUNCATE TABLE datos_medicos;");
        $this->pdo->exec("TRUNCATE TABLE contacto_atleta;");
        $this->pdo->exec("TRUNCATE TABLE identidad_atleta;");
        $this->pdo->exec("TRUNCATE TABLE atleta_representante;");
        $this->pdo->exec("TRUNCATE TABLE inscripciones;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("TRUNCATE TABLE posiciones;");
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

    private function sembrarPosiciones(): void
    {
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) VALUES 
                          (1, 'Base', 'BA', 'Organizador de juego'),
                          (2, 'Escolta', 'ES', 'Tirador perimetral'),
                          (3, 'Alero', 'AL', 'Jugador versatil')");
    }

    /**
     * Búsqueda de posición por ID.
     */
    public function testBuscarPosicionPorId(): void
    {
        $this->sembrarPosiciones();

        $resultado = $this->modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $pos = $resultado['datos'][0];
        $this->assertEquals(1, (int)$pos['codigo_posicion']);
        $this->assertEquals('Base', $pos['nombre']);
        $this->assertEquals('BA', $pos['abreviatura']);
        $this->assertEquals('Organizador de juego', $pos['descripcion']);
    }

    /**
     * Consulta con filtro de búsqueda por nombre o abreviatura.
     */
    public function testConsultarConFiltro(): void
    {
        $this->sembrarPosiciones();

        // 1. Filtrar por nombre 'Escolta'
        $resNombre = $this->modelo->Consultar(['filtro' => 'Escolta']);
        $this->assertEquals('consultar', $resNombre['accion']);
        $this->assertCount(1, $resNombre['datos']);
        $this->assertEquals('Escolta', $resNombre['datos'][0]['nombre']);

        // 2. Filtrar por abreviatura 'BA'
        $resAbrev = $this->modelo->Consultar(['filtro' => 'BA']);
        $this->assertCount(1, $resAbrev['datos']);
        $this->assertEquals('Base', $resAbrev['datos'][0]['nombre']);

        // 3. Consulta sin filtro devuelve todas las posiciones
        $resTodas = $this->modelo->Consultar([]);
        $this->assertCount(3, $resTodas['datos']);
    }

    /**
     * Consulta utilizando filtros específicos asignados al modelo.
     */
    public function testConsultarPorNombreYAbreviatura(): void
    {
        $this->sembrarPosiciones();

        $datos = [
            'accion'      => 'generar',
            'nombre'      => 'Alero',
            'abreviatura' => 'AL'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Alero', $resultado['datos'][0]['nombre']);
        $this->assertEquals('AL', $resultado['datos'][0]['abreviatura']);
    }

    /**
     * Validación de existencia de una posición vía verificarPosiciones.
     */
    public function testVerificarPosicionExistente(): void
    {
        $this->sembrarPosiciones();

        $this->assertTrue($this->modelo->verificarPosiciones(1));
        $this->assertTrue($this->modelo->verificarPosiciones(2));
        $this->assertFalse($this->modelo->verificarPosiciones(99999));
    }
}
