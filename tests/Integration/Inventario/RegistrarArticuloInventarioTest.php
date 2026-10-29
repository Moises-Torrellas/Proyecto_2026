<?php

namespace tests\Integration\Inventario;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloArticulosInventario;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group("integ")]
class RegistrarArticuloInventarioTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloArticulosInventario $modelo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=cannibalsbd2;charset=utf8', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $this->pdo);

        $this->modelo = new ModeloArticulosInventario();
        
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE articulos_inventario;");
        $this->pdo->exec("TRUNCATE TABLE catalogo;");
        $this->pdo->exec("TRUNCATE TABLE categoria_catalogo;");
        $this->pdo->exec("TRUNCATE TABLE estado_fisico;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        parent::tearDown();
    }

    public function testIncluirMultiplesArticulosConEstadosFisicosIndependientes(): void
    {
        $this->pdo->exec("INSERT IGNORE INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES (1, 'Protecciones', 'Categorico')");
        $this->pdo->exec("INSERT IGNORE INTO catalogo (id_catalogo, nombre, stock_minimo, Id_categoria, talla) VALUES (1, 'Casco', 1, 1, 'M'), (2, 'Pads', 1, 1, 'L')");
        $this->pdo->exec("INSERT IGNORE INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (1, 'Excelente', 1), (2, 'Regular', 2), (3, 'Dañado', 3)");

        $articulos = [
            ['id_catalogo' => 1, 'id_estado' => 1], // Casco M - Excelente
            ['id_catalogo' => 1, 'id_estado' => 2], // Casco M - Regular
            ['id_catalogo' => 2, 'id_estado' => 3]  // Pads L - Dañado
        ];

        $datos = [
            'accion'    => 'incluir',
            'articulos' => $articulos
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertCount(3, $resultado['codigos']);

        // Verificar en la BD
        $stmt = $this->pdo->query("SELECT * FROM articulos_inventario ORDER BY codigo_articulo ASC");
        $filas = $stmt->fetchAll();

        $this->assertCount(3, $filas);
        $this->assertEquals(1, $filas[0]['id_catalogo']);
        $this->assertEquals(1, $filas[0]['id_estado']);
        $this->assertEquals('CL-0001', $filas[0]['codigo_club']);

        $this->assertEquals(1, $filas[1]['id_catalogo']);
        $this->assertEquals(2, $filas[1]['id_estado']);
        $this->assertEquals('CL-0002', $filas[1]['codigo_club']);

        $this->assertEquals(2, $filas[2]['id_catalogo']);
        $this->assertEquals(3, $filas[2]['id_estado']);
        $this->assertEquals('CL-0003', $filas[2]['codigo_club']);
    }

    public function testModificarSoloEstadoFisico(): void
    {
        $this->pdo->exec("INSERT IGNORE INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES (1, 'Protecciones', 'Categorico')");
        $this->pdo->exec("INSERT IGNORE INTO catalogo (id_catalogo, nombre, stock_minimo, Id_categoria, talla) VALUES (1, 'Casco', 1, 1, 'M')");
        $this->pdo->exec("INSERT IGNORE INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (1, 'Excelente', 1), (2, 'Regular', 2)");
        $this->pdo->exec("INSERT INTO articulos_inventario (codigo_articulo, id_catalogo, id_estado, codigo_club, estatus) VALUES (10, 1, 1, 'CL-0010', 1)");

        $datos = [
            'accion'          => 'modificar',
            'codigo_articulo' => 10,
            'id_catalogo'     => 1,
            'id_estado'       => 2 // Cambiar a Regular
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT id_estado, id_catalogo FROM articulos_inventario WHERE codigo_articulo = 10");
        $fila = $stmt->fetch();

        $this->assertEquals(2, $fila['id_estado']);
        $this->assertEquals(1, $fila['id_catalogo']);
    }
}

