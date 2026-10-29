<?php

namespace tests\Integration\Devoluciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloDevoluciones;
use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloArticulosInventario;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group("integ")]
class RegistrarDevolucionTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloDevoluciones $modelo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=cannibalsbd2;charset=utf8', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $this->pdo);

        $this->modelo = new ModeloDevoluciones();
        $this->modelo->setAsignaciones(new ModeloAsignaciones());
        $this->modelo->setEquipamientos(new ModeloArticulosInventario());

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $this->pdo->exec("TRUNCATE TABLE devoluciones;");
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
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function cargarDatosBaseParaPrueba(): void
    {
        $this->pdo->exec("INSERT IGNORE INTO posiciones (codigo_posicion, nombre, abreviatura) VALUES (3, 'Portero', 'PR')");
        $this->pdo->exec("INSERT IGNORE INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES (7, 'U-17', 15, 17)");
        $this->pdo->exec("INSERT IGNORE INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, foto) VALUES (1, 'Diego', 'Reinoso', 'H', '2011-12-07', 'foto.jpg')");
        $this->pdo->exec("INSERT IGNORE INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus) VALUES (1, 1, 7, 3, 10, 70, 170, '2026-01-01', 1)");
        $this->pdo->exec("INSERT IGNORE INTO categoria_catalogo (id_categoria, nombre, tipo_talla) VALUES (6, 'Protecciones', 'Categorico')");
        $this->pdo->exec("INSERT IGNORE INTO catalogo (id_catalogo, nombre, stock_minimo, Id_categoria, codigo_posicion, talla) VALUES (5, 'Pads', 1, 6, 3, 'SENIOR')");
        
        $this->pdo->exec("INSERT IGNORE INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (1, 'Excelente', 1)");
        $this->pdo->exec("INSERT IGNORE INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (2, 'Regular', 2)");
        $this->pdo->exec("INSERT IGNORE INTO estado_fisico (id_estado, nombre, nivel_estado) VALUES (3, 'Dañado', 3)");

        // Artículo asignado previamente (estatus 2 = en uso / prestado)
        $this->pdo->exec("INSERT IGNORE INTO articulos_inventario (codigo_articulo, id_estado, id_catalogo, codigo_club, estatus) VALUES (10, 1, 5, 'CL-0010', 2)");
        
        // Asignación activa (estatus 1 = activo)
        $this->pdo->exec("INSERT IGNORE INTO asignaciones (id_asignacion, codigo_atleta, codigo_articulo, fecha_asignacion, estatus) VALUES (1, 1, 10, '2026-01-01', 1)");
    }

    public function testIncluirDevolucionExitosaYLiberarArticuloEnBaseDeDatosReal(): void
    {
        $this->cargarDatosBaseParaPrueba();

        $datos = [
            'accion'           => 'incluir',
            'id_asignacion'    => 1,
            'id_estado'        => 1,
            'observacion'      => 'Devuelto en óptimas condiciones',
            'fecha_devolucion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertArrayHasKey('id_devolucion', $resultado);

        // 1. Verificamos que la devolución se insertó en la base de datos
        $stmtDev = $this->pdo->query("SELECT * FROM devoluciones WHERE id_asignacion = 1");
        $devolucionDB = $stmtDev->fetch();
        $this->assertNotEmpty($devolucionDB, 'La devolución no se insertó en la base de datos.');
        $this->assertEquals(1, $devolucionDB['id_estado']);
        $this->assertEquals('Devuelto en óptimas condiciones', $devolucionDB['observacion']);

        // 2. Verificamos que el SP ProcesarDevolucionSegura actualizó el estatus de la asignación a 2 (devuelta/inactiva)
        $stmtAsig = $this->pdo->query("SELECT estatus FROM asignaciones WHERE id_asignacion = 1");
        $asignacionDB = $stmtAsig->fetch();
        $this->assertEquals(2, $asignacionDB['estatus'], 'El estatus de la asignación no cambió a devuelto/inactivo (2).');

        // 3. Verificamos que el trigger trg_despues_insertar_devolucion liberó el artículo a estatus 1 (disponible)
        $stmtArt = $this->pdo->query("SELECT estatus, id_estado FROM articulos_inventario WHERE codigo_articulo = 10");
        $articuloDB = $stmtArt->fetch();
        $this->assertEquals(1, $articuloDB['estatus'], 'El estatus del artículo en inventario no cambió a 1 (disponible).');
        $this->assertEquals(1, $articuloDB['id_estado']);
    }

    public function testIncluirDevolucionFallaSiAsignacionNoExisteOYaFueDevuelta(): void
    {
        $this->cargarDatosBaseParaPrueba();

        $datosInvalido = [
            'accion'           => 'incluir',
            'id_asignacion'    => 999, // Asignación inexistente
            'id_estado'        => 1,
            'observacion'      => 'Prueba fallo asignación inexistente',
            'fecha_devolucion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datosInvalido);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('no existe, no es válida o ya fue devuelta', $resultado['mensaje']);
    }

    public function testIncluirDevolucionConArticuloDanadoActivaTriggerDeBloqueo(): void
    {
        $this->cargarDatosBaseParaPrueba();

        // Se devuelve con estado físico 3 (Dañado, nivel_estado = 3)
$datosDanado = [
            'accion'           => 'incluir',
            'id_asignacion'    => 1,
            'id_estado'        => 3,
            'observacion'      => 'Pads rotos en entrenamiento',
            'fecha_devolucion' => date('Y-m-d')
        ];

        $resultado = $this->modelo->ProcesarDatos($datosDanado);

        $this->assertEquals('exito', $resultado['accion']);

        // El trigger trg_bloquear_articulos_danados se dispara antes de la actualización
        // al detectar nivel_estado = 3, forzando estatus = 3 (Dañado/Retirado) en vez de 1 (Disponible)
        $stmtArt = $this->pdo->query("SELECT estatus, id_estado FROM articulos_inventario WHERE codigo_articulo = 10");
        $articuloDB = $stmtArt->fetch();

        $this->assertEquals(3, $articuloDB['estatus'], 'El trigger trg_bloquear_articulos_danados debió establecer estatus = 3.');
        $this->assertEquals(3, $articuloDB['id_estado']);
    }

    public function testAnularDevolucionRestauraAsignacionYMarcaArticuloComoOcupado(): void
    {
        $this->cargarDatosBaseParaPrueba();

        // 1. Procesar la devolución primero
        $datosIncluir = [
            'accion'           => 'incluir',
            'id_asignacion'    => 1,
            'id_estado'        => 1,
            'observacion'      => 'Devolución inicial',
            'fecha_devolucion' => date('Y-m-d')
        ];
        $resDevolucion = $this->modelo->ProcesarDatos($datosIncluir);
        $this->assertEquals('exito', $resDevolucion['accion']);
        $idDevolucion = $resDevolucion['id_devolucion'];

        // 2. Anular la devolución
        $datosAnular = [
            'accion'           => 'anular',
            'id_devolucion'    => $idDevolucion,
            'motivo_anulacion' => 'Error administrativo de recepción'
        ];
        $resAnulacion = $this->modelo->ProcesarDatos($datosAnular);

        $this->assertEquals('exito', $resAnulacion['accion']);

        // 3. Comprobamos que el registro de devolución fue eliminado físicamente
        $stmtCheckDev = $this->pdo->prepare("SELECT COUNT(*) FROM devoluciones WHERE id_devolucion = ?");
        $stmtCheckDev->execute([$idDevolucion]);
        $this->assertEquals(0, $stmtCheckDev->fetchColumn(), 'El registro de devolución no fue removido.');

        // 4. Comprobamos que la asignación volvió a estatus 1 (activo / prestado)
        $stmtAsig = $this->pdo->query("SELECT estatus FROM asignaciones WHERE id_asignacion = 1");
        $this->assertEquals(1, $stmtAsig->fetchColumn(), 'La asignación no se restauró a estatus activo (1).');

        // 5. Comprobamos que el artículo volvió a estatus 2 (ocupado)
        $stmtArt = $this->pdo->query("SELECT estatus FROM articulos_inventario WHERE codigo_articulo = 10");
        $this->assertEquals(2, $stmtArt->fetchColumn(), 'El artículo no regresó a estatus ocupado (2).');
    }
}
