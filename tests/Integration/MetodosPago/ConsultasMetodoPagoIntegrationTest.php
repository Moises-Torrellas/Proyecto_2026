<?php

declare(strict_types=1);

namespace tests\Integration\MetodosPago;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloMetodosPago;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("metodos_pago")]
class ConsultasMetodoPagoIntegrationTest extends TestCase
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

    private function sembrarMetodosPago(): void
    {
        $this->pdo->exec("INSERT INTO metodos_pago (codigo_metodo, nombre, nec_referencia, estatus) VALUES 
                          (1, 'Transferencia', 1, 1),
                          (2, 'Pago Movil', 1, 1),
                          (3, 'Efectivo Divisas', 0, 2)"); // Método 3 inactivo/bloqueado
    }

    /**
     * Búsqueda de método de pago por ID.
     */
    public function testBuscarMetodoPorId(): void
    {
        $this->sembrarMetodosPago();

        $resultado = $this->modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $metodo = $resultado['datos'][0];
        $this->assertEquals(1, (int)$metodo['codigo_metodo']);
        $this->assertEquals('Transferencia', $metodo['nombre']);
        $this->assertEquals(1, (int)$metodo['nec_referencia']);
        $this->assertEquals(1, (int)$metodo['estatus']);
    }

    /**
     * Consulta con filtro de búsqueda por nombre.
     */
    public function testConsultarConFiltro(): void
    {
        $this->sembrarMetodosPago();

        // 1. Filtrar por 'Transferencia'
        $resTransf = $this->modelo->Consultar(['filtro' => 'Transferencia']);
        $this->assertEquals('consultar', $resTransf['accion']);
        $this->assertCount(1, $resTransf['datos']);
        $this->assertEquals('Transferencia', $resTransf['datos'][0]['nombre']);

        // 2. Filtrar por 'Movil'
        $resMovil = $this->modelo->Consultar(['filtro' => 'Movil']);
        $this->assertCount(1, $resMovil['datos']);
        $this->assertEquals('Pago Movil', $resMovil['datos'][0]['nombre']);

        // 3. Consulta sin filtro devuelve todos (activos e inactivos)
        $resTodos = $this->modelo->Consultar([]);
        $this->assertCount(3, $resTodos['datos']);
    }

    /**
     * Consulta exclusiva de métodos activos (ConsultarMetodos).
     */
    public function testConsultarMetodosActivos(): void
    {
        $this->sembrarMetodosPago();

        $resultado = $this->modelo->ConsultarMetodos();

        $this->assertEquals('buscar', $resultado['accion']);
        // Solo deben retornar 2 métodos activos (Transferencia y Pago Movil), ignorando Efectivo (estatus = 2)
        $this->assertCount(2, $resultado['datos']);

        foreach ($resultado['datos'] as $metodo) {
            $this->assertEquals(1, (int)$metodo['estatus'], 'Solo deben retornarse métodos con estatus 1.');
        }
    }
}

