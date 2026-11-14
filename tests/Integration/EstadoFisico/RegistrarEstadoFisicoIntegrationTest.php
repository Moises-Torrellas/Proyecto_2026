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
class RegistrarEstadoFisicoIntegrationTest extends TestCase
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

    /**
     * ESC-INT-EF-01 / CP-INT-EF-01: Inserción exitosa de un nuevo estado físico.
     * Verifica persistencia real en la base de datos y formateo en Title Case.
     */
    public function testRegistroExitoso(): void
    {
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'excelente condición',
            'nivel_estado' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('incluir', $resultado['accion']);
        $this->assertEquals('Estado físico registrado exitosamente.', $resultado['mensaje']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        // Verificamos en BD
        $stmt = $this->pdo->query("SELECT * FROM estado_fisico WHERE nivel_estado = 1");
        $estado = $stmt->fetch();

        $this->assertNotEmpty($estado, 'El estado físico debió registrarse en la tabla estado_fisico.');
        $this->assertEquals('Excelente Condición', $estado['nombre'], 'El nombre debió ser formateado a Title Case.');
        $this->assertEquals(1, (int)$estado['nivel_estado']);
    }

    /**
     * ESC-INT-EF-01 / CP-INT-EF-02: Rechazo por nombre de estado físico duplicado.
     */
    public function testErrorNombreDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO estado_fisico (nombre, nivel_estado) VALUES ('Excelente', 1)");

        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Excelente',
            'nivel_estado' => '2'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe un estado físico registrado con este nombre.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM estado_fisico");
        $this->assertEquals(1, (int)$stmt->fetchColumn(), 'No debió crearse un registro duplicado.');
    }

    /**
     * ESC-INT-EF-01 / CP-INT-EF-03: Rechazo por nombre duplicado considerando espacios y diferencias de caja.
     */
    public function testErrorNombreDuplicadoCaseInsensitiveYEspacios(): void
    {
        $this->pdo->exec("INSERT INTO estado_fisico (nombre, nivel_estado) VALUES ('Regular', 2)");

        $datos = [
            'accion'       => 'incluir',
            'nombre'       => '   regular   ',
            'nivel_estado' => '3'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe un estado físico registrado con este nombre.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM estado_fisico");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * ESC-INT-EF-01 / CP-INT-EF-04: Inserción múltiple de estados físicos con distintos niveles.
     */
    public function testRegistroMultiplesNiveles(): void
    {
        $estadosAInsertar = [
            ['nombre' => 'Excelente', 'nivel_estado' => 1],
            ['nombre' => 'Bueno',     'nivel_estado' => 2],
            ['nombre' => 'Regular',   'nivel_estado' => 3],
            ['nombre' => 'Dañado',    'nivel_estado' => 4],
        ];

        foreach ($estadosAInsertar as $item) {
            $resultado = $this->modelo->ProcesarDatos([
                'accion'       => 'incluir',
                'nombre'       => $item['nombre'],
                'nivel_estado' => $item['nivel_estado']
            ]);
            $this->assertEquals('incluir', $resultado['accion']);
        }

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM estado_fisico");
        $this->assertEquals(4, (int)$stmt->fetchColumn(), 'Deben haberse registrado 4 estados físicos en BD.');
    }
}

