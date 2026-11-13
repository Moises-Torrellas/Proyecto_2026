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
class ModificarEstadoFisicoIntegrationTest extends TestCase
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
     * ESC-INT-EF-02 / CP-INT-EF-05: Modificación exitosa conservando su propio nombre.
     * Verifica que no genera falso positivo de duplicidad contra sí mismo.
     */
    public function testModificacionConservarPropiosDatos(): void
    {
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (1, 'Excelente', 1)");

        $datos = [
            'accion'       => 'modificar',
            'id_estado'    => '1',
            'nombre'       => 'Excelente',
            'nivel_estado' => '2' // Cambio solo nivel
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Estado físico modificado exitosamente.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT * FROM estado_fisico WHERE id_estado = 1");
        $estado = $stmt->fetch();

        $this->assertEquals('Excelente', $estado['nombre']);
        $this->assertEquals(2, (int)$estado['nivel_estado']);
    }

    /**
     * ESC-INT-EF-02 / CP-INT-EF-06: Modificación exitosa cambiando a un nuevo nombre y nivel no duplicado.
     */
    public function testModificacionExitosaNuevoNombre(): void
    {
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (1, 'Bueno', 2)");

        $datos = [
            'accion'       => 'modificar',
            'id_estado'    => '1',
            'nombre'       => 'Óptimo',
            'nivel_estado' => '1'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Estado físico modificado exitosamente.', $resultado['mensaje']);

        $stmt = $this->pdo->query("SELECT * FROM estado_fisico WHERE id_estado = 1");
        $estado = $stmt->fetch();

        $this->assertEquals('Óptimo', $estado['nombre']);
        $this->assertEquals(1, (int)$estado['nivel_estado']);
    }

    /**
     * ESC-INT-EF-02 / CP-INT-EF-07: Rechazo si el nuevo nombre ya pertenece a otro estado físico registrado.
     */
    public function testErrorModificarNombreAjenoDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES 
                          (1, 'Excelente', 1),
                          (2, 'Regular', 2)");

        $datos = [
            'accion'       => 'modificar',
            'id_estado'    => '2',
            'nombre'       => 'Excelente', // Intenta apropiarse del nombre del registro 1
            'nivel_estado' => '2'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe otro estado físico registrado con este nombre.', $resultado['mensaje']);

        // Verificamos que el registro 2 conserve su valor original en la base de datos
        $stmt = $this->pdo->query("SELECT nombre FROM estado_fisico WHERE id_estado = 2");
        $this->assertEquals('Regular', $stmt->fetchColumn());
    }

    /**
     * ESC-INT-EF-02 / CP-INT-EF-08: Normalización y actualización correcta de mayúsculas/minúsculas.
     */
    public function testModificacionNormalizaFormatoNombre(): void
    {
        $this->pdo->exec("INSERT INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (1, 'Antiguo', 3)");

        $datos = [
            'accion'       => 'modificar',
            'id_estado'    => '1',
            'nombre'       => 'desgaste por uso severo',
            'nivel_estado' => '4'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('modificar', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT nombre, nivel_estado FROM estado_fisico WHERE id_estado = 1");
        $fila = $stmt->fetch();

        $this->assertEquals('Desgaste Por Uso Severo', $fila['nombre']);
        $this->assertEquals(4, (int)$fila['nivel_estado']);
    }
}

