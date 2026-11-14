<?php

declare(strict_types=1);

namespace tests\Integration\EstadoFisico;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadoFisico;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("estadofisico")]
class ConsultasEstadoFisicoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloEstadoFisico $modelo;

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

        $this->modelo = new ModeloEstadoFisico();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE devoluciones;");
        $this->pdo->exec("TRUNCATE TABLE asignaciones;");
        $this->pdo->exec("TRUNCATE TABLE articulos_inventario;");
        $this->pdo->exec("TRUNCATE TABLE catalogo;");
        $this->pdo->exec("TRUNCATE TABLE categoria_catalogo;");
        $this->pdo->exec("TRUNCATE TABLE estado_fisico;");
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

    private function sembrarEstadosFisicos(): void
    {
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
                          (1, 'Excelente', 1),
                          (2, 'Bueno', 2),
                          (3, 'Regular', 3),
                          (4, 'Dañado', 4)");
    }

    /**
     * ESC-INT-EF-04 / CP-INT-EF-13: Búsqueda de estado físico por ID puntual usando Buscar($id).
     */
    public function testBuscarEstadoFisicoPorId(): void
    {
        $this->sembrarEstadosFisicos();

        $resultado = $this->modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $estado = $resultado['datos'][0];
        $this->assertEquals(1, (int)$estado['id_estado']);
        $this->assertEquals('Excelente', $estado['nombre']);
        $this->assertEquals(1, (int)$estado['nivel_estado']);
    }

    /**
     * ESC-INT-EF-04 / CP-INT-EF-14: Búsqueda individual a través del método ProcesarDatos().
     */
    public function testBuscarEstadoFisicoMedianteProcesarDatos(): void
    {
        $this->sembrarEstadosFisicos();

        $datos = [
            'accion'    => 'buscar',
            'id_estado' => '2'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $estado = $resultado['datos'][0];
        $this->assertEquals(2, (int)$estado['id_estado']);
        $this->assertEquals('Bueno', $estado['nombre']);
        $this->assertEquals(2, (int)$estado['nivel_estado']);
    }

    /**
     * ESC-INT-EF-04 / CP-INT-EF-15: Consulta general de todos los estados físicos sin filtros.
     */
    public function testConsultarTodosSinFiltro(): void
    {
        $this->sembrarEstadosFisicos();

        $resultado = $this->modelo->Consultar([]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(4, $resultado['datos'], 'Deben listarse los 4 estados físicos existentes.');
        
        // Comprobar orden ascendente por id_estado
        $this->assertEquals('Excelente', $resultado['datos'][0]['nombre']);
        $this->assertEquals('Dañado', $resultado['datos'][3]['nombre']);
    }

    /**
     * ESC-INT-EF-04 / CP-INT-EF-16: Consulta con filtro de texto parcial (búsqueda por coincidencia en nombre).
     */
    public function testConsultarConFiltroTexto(): void
    {
        $this->sembrarEstadosFisicos();

        // Filtrar por término parcial "Reg"
        $resultado = $this->modelo->Consultar(['filtro' => 'Reg']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Regular', $resultado['datos'][0]['nombre']);
    }

    /**
     * ESC-INT-EF-04 / CP-INT-EF-17: Consulta con filtro específico por nivel de estado.
     */
    public function testConsultarConFiltroNivelEstado(): void
    {
        $this->sembrarEstadosFisicos();

        // Filtrar por nivel_estado = 4
        $resultado = $this->modelo->Consultar(['nivel_estado' => '4']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Dañado', $resultado['datos'][0]['nombre']);
        $this->assertEquals(4, (int)$resultado['datos'][0]['nivel_estado']);
    }

    /**
     * ESC-INT-EF-04 / CP-INT-EF-18: Consulta con filtro sin coincidencias.
     */
    public function testConsultarConFiltroSinCoincidencias(): void
    {
        $this->sembrarEstadosFisicos();

        $resultado = $this->modelo->Consultar(['filtro' => 'TextoNoExistenteEnBD']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos'], 'No debe retornar datos si el filtro no coincide.');
    }
}

