<?php

namespace tests\Integration\Asignaciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloArticulosInventario;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group("integ")]
class RegistrarAsignacionTest extends TestCase
{
    // SOLUCIÓN ERROR 2: Se añade "?" para hacer que la propiedad acepte valores nulos (nullable)
    private ?PDO $pdo = null;
    private ModeloAsignaciones $modelo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=cannibalsbd2;charset=utf8', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $this->pdo);

        $this->modelo = new ModeloAsignaciones();
        $this->modelo->setArticulos(new ModeloArticulosInventario());
        
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE asignaciones;");
        $this->pdo->exec("TRUNCATE TABLE articulos_inventario;");
        $this->pdo->exec("TRUNCATE TABLE atletas;");
        $this->pdo->exec("TRUNCATE TABLE catalogo;");
        $this->pdo->exec("TRUNCATE TABLE inscripciones;");
        $this->pdo->exec("TRUNCATE TABLE posiciones;");
        $this->pdo->exec("TRUNCATE TABLE categorias;");
        $this->pdo->exec("TRUNCATE TABLE categoria_catalogo;");
        $this->pdo->exec("TRUNCATE TABLE estado_fisico;");
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
        parent::tearDown();
    }

    public function testIncluirAsignacionExitosaEnBaseDeDatosReal(): void
    {
        $this->pdo->exec("INSERT IGNORE INTO posiciones (codigo_posicion, nombre, abreviatura) VALUES (3, 'Portero', 'PR')");
        
        $this->pdo->exec("INSERT IGNORE INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES (7, 'U-17', 15, 17)");
        
        $this->pdo->exec("INSERT IGNORE INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto.jpg')");
        
        $this->pdo->exec("INSERT IGNORE INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus) VALUES (1, 1, 7, 3, 10, 70, 170, '2026-01-01', 1)");

        // SOLUCIÓN ERROR 1: 3 columnas definidas y 3 valores entregados (usando tipo_talla que ahora sí existe)
        $this->pdo->exec("INSERT IGNORE INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES (6, 'Protecciones', 'Categorico')");
        
        // SOLUCIÓN ERROR 1: 6 columnas definidas y 6 valores entregados (usando codigo_posicion que ahora sí existe)
        $this->pdo->exec("INSERT IGNORE INTO catalogo (id_catalogo, nombre, stock_minimo, Id_categoria, codigo_posicion, talla) VALUES (5, 'Pads', 1, 6, 3, 'SENIOR')");
        
        $this->pdo->exec("INSERT IGNORE INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (1, 'Excelente', 1)");
        
        $this->pdo->exec("INSERT IGNORE INTO articulos_inventario (codigo_articulo, id_estado, id_catalogo, codigo_club, estatus) VALUES (10, 1, 5, 'CL-0010', 1)");

        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '10',
            'fecha_asignacion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        
        $stmtAsignacion = $this->pdo->query("SELECT * FROM asignaciones WHERE codigo_atleta = 1 AND codigo_articulo = 10");
        $asignacionDB = $stmtAsignacion->fetch();
        $this->assertNotEmpty($asignacionDB, 'La asignación no se insertó en la base de datos.');
        $this->assertEquals(1, $asignacionDB['estatus']); 

        $stmtArticulo = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 10");
        $articuloDB = $stmtArticulo->fetch();
        $this->assertEquals(2, $articuloDB['estatus'], 'El estatus del artículo no cambió a ocupado.');
    }

    public function testIncluirAsignacionFallaSiArticuloEstaOcupado(): void
    {
        $this->pdo->exec("INSERT IGNORE INTO posiciones (codigo_posicion, nombre, abreviatura) VALUES (3, 'Portero', 'PR')");
        
        $this->pdo->exec("INSERT IGNORE INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES (7, 'U-17', 15, 17)");
        
        $this->pdo->exec("INSERT IGNORE INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto.jpg')");
        
        $this->pdo->exec("INSERT IGNORE INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus) VALUES (1, 1, 7, 3, 10, 70, 170, '2026-01-01', 1)");

        $this->pdo->exec("INSERT IGNORE INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES (6, 'Protecciones', 'Categorico')");
        
        $this->pdo->exec("INSERT IGNORE INTO catalogo (id_catalogo, nombre, stock_minimo, Id_categoria, codigo_posicion, talla) VALUES (5, 'Pads', 1, 6, 3, 'SENIOR')");
        
        $this->pdo->exec("INSERT IGNORE INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (1, 'Excelente', 1)");
        
        $this->pdo->exec("INSERT IGNORE INTO articulos_inventario (codigo_articulo, id_estado, id_catalogo, codigo_club, estatus) VALUES (10, 1, 5, 'CL-0010', 2)");

        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '10',
            'fecha_asignacion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_EQUIPO_OCUPADO', $resultado['codigo']);
    }
}