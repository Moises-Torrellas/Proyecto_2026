<?php

declare(strict_types=1);

namespace tests\Integration\Conceptos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloConceptos;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("conceptos")]
class ConsultasConceptoIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloConceptos $modelo;

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

        $this->modelo = new ModeloConceptos();

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE conceptos;");
        $this->pdo->exec("TRUNCATE TABLE monedas;");
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

    private function sembrarConceptos(): void
    {
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) 
                          VALUES (1, 'Dólar', 'USD', '$', 1, 1)");

        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) VALUES 
                          (1, 'Mensualidad', 30.00, 'M', 5, 1),
                          (2, 'Inscripción Anual', 25.00, 'A', 0, 2),
                          (3, 'Multa Por Mora', 5.00, 'T', 0, 1)");
    }

    /**
     * Búsqueda de concepto por ID.
     */
    public function testBuscarConceptoPorId(): void
    {
        $this->sembrarConceptos();

        $resultado = $this->modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $concepto = $resultado['datos'][0];
        $this->assertEquals(1, (int)$concepto['codigo_concepto']);
        $this->assertEquals('Mensualidad', $concepto['nombre']);
        $this->assertEquals(30.00, (float)$concepto['monto']);
        $this->assertEquals('M', $concepto['frecuencia']);
        $this->assertEquals(5, (int)$concepto['dias_gracia']);
        $this->assertEquals(1, (int)$concepto['estatus']);
    }

    /**
     * Consulta con filtro de búsqueda por nombre o monto y resolución de símbolo base.
     */
    public function testConsultarConFiltro(): void
    {
        $this->sembrarConceptos();

        // 1. Filtrar por nombre 'Mensualidad'
        $resNombre = $this->modelo->Consultar(['filtro' => 'Mensualidad']);
        $this->assertEquals('consultar', $resNombre['accion']);
        $this->assertCount(1, $resNombre['datos']);
        $this->assertEquals('Mensualidad', $resNombre['datos'][0]['nombre']);
        $this->assertEquals('$', $resNombre['datos'][0]['simbolo_base']);

        // 2. Filtrar por monto '25'
        $resMonto = $this->modelo->Consultar(['filtro' => '25']);
        $this->assertCount(1, $resMonto['datos']);
        $this->assertEquals('Inscripción Anual', $resMonto['datos'][0]['nombre']);

        // 3. Consulta sin filtro devuelve todos los conceptos
        $resTodos = $this->modelo->Consultar([]);
        $this->assertCount(3, $resTodos['datos']);
    }

    /**
     * Consulta de conceptos activos para facturación, excluyendo multas (ConsultarConcepto).
     */
    public function testConsultarConceptosActivosSinMulta(): void
    {
        $this->sembrarConceptos();

        $resultado = $this->modelo->ConsultarConcepto();

        $this->assertEquals('consultarCo', $resultado['accion']);

        // Solo debe retornar 1 concepto ('Mensualidad', ya que Inscripción está inactiva y Multa tiene frecuencia 'T')
        $this->assertCount(1, $resultado['datos'], 'Debe retornar únicamente conceptos activos con frecuencia distinta a T.');
        $this->assertEquals('Mensualidad', $resultado['datos'][0]['nombre']);
        $this->assertEquals('M', $resultado['datos'][0]['frecuencia']);
        $this->assertEquals(1, (int)$resultado['datos'][0]['estatus']);
    }
}
