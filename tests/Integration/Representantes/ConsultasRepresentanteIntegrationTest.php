<?php

declare(strict_types=1);

namespace tests\Integration\Representantes;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRepresentantes;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("representantes")]
class ConsultasRepresentanteIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloRepresentantes $modelo;

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

        $this->modelo = new ModeloRepresentantes();

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
        $this->pdo->exec("TRUNCATE TABLE representantes;");
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

    private function sembrarRepresentantes(): void
    {
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) VALUES 
                          (1, '14555666', 'V', 'Roberto Carlos', 'Mendoza Silva', '0414-1112233', 'Av Lara', 'roberto@mail.com', '@roberto'),
                          (2, '18999888', 'V', 'Gabriela Sofia', 'Alvarado Rojas', '0424-5556677', 'Av Los Leones', 'gabriela@mail.com', '@gaby'),
                          (3, '82333444', 'E', 'John Mark', 'Smith Miller', '0412-8889900', 'Cabudare', 'john@mail.com', '@john')");
    }

    /**
     * Búsqueda de representante por ID.
     */
    public function testBuscarRepresentantePorId(): void
    {
        $this->sembrarRepresentantes();

        $resultado = $this->modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $rep = $resultado['datos'][0];
        $this->assertEquals(1, (int)$rep['codigo_representante']);
        $this->assertEquals('14555666', $rep['cedula']);
        $this->assertEquals('V', $rep['tipo_doc']);
        $this->assertEquals('Roberto Carlos', $rep['nombre']);
        $this->assertEquals('Mendoza Silva', $rep['apellido']);
        $this->assertEquals('0414-1112233', $rep['telefono']);
        $this->assertEquals('Av Lara', $rep['direccion']);
        $this->assertEquals('roberto@mail.com', $rep['correo']);
        $this->assertEquals('@roberto', $rep['instagram']);
    }

    /**
     * Consulta con filtro por nombre, cédula o teléfono.
     */
    public function testConsultarConFiltro(): void
    {
        $this->sembrarRepresentantes();

        // 1. Filtrar por nombre 'Gabriela'
        $resNombre = $this->modelo->Consultar(['filtro' => 'Gabriela']);
        $this->assertEquals('consultar', $resNombre['accion']);
        $this->assertCount(1, $resNombre['datos']);
        $this->assertEquals('Gabriela Sofia', $resNombre['datos'][0]['nombre']);

        // 2. Filtrar por cédula '14555666'
        $resCedula = $this->modelo->Consultar(['filtro' => '14555666']);
        $this->assertCount(1, $resCedula['datos']);
        $this->assertEquals('Roberto Carlos', $resCedula['datos'][0]['nombre']);

        // 3. Filtrar por teléfono '0412-8889900'
        $resTlf = $this->modelo->Consultar(['filtro' => '0412-8889900']);
        $this->assertCount(1, $resTlf['datos']);
        $this->assertEquals('John Mark', $resTlf['datos'][0]['nombre']);

        // 4. Sin filtro devuelve los 3 registros
        $resTodos = $this->modelo->Consultar([]);
        $this->assertCount(3, $resTodos['datos']);
    }

    /**
     * Consulta utilizando filtros específicos de nacionalidad y cédula.
     */
    public function testConsultarPorNacionalidadYCedula(): void
    {
        $this->sembrarRepresentantes();

        // Acción 'generar' para reporte filtrado por nacionalidad E y prefijo de cédula '82'
        $datos = [
            'accion'       => 'generar',
            'nacionalidad' => 'E',
            'cedula'       => '82'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('John Mark', $resultado['datos'][0]['nombre']);
        $this->assertEquals('E', $resultado['datos'][0]['tipo_doc']);
    }

    /**
     * Verificación de existencia de representante.
     */
    public function testVerificarRepresentanteExistente(): void
    {
        $this->sembrarRepresentantes();

        $this->assertTrue($this->modelo->verificarRepresentantes(1));
        $this->assertTrue($this->modelo->verificarRepresentantes(2));
        $this->assertFalse($this->modelo->verificarRepresentantes(99999));
    }
}
