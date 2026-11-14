<?php

declare(strict_types=1);

namespace tests\Integration\Atletas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloAtletas;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("atletas")]
class ConsultasAtletaIntegrationTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloAtletas $modelo;

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

        $this->modelo = new ModeloAtletas();

        // Limpieza de tablas respetando la integridad referencial
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
        $this->pdo->exec("TRUNCATE TABLE categorias;");
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

    private function sembrarCatalogosYAtleta(): void
    {
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) VALUES 
                          (1, 'Base', 'B', 'Armador'),
                          (2, 'Escolta', 'E', 'Tirador')");

        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES 
                          (1, 'U-15', 13, 15),
                          (2, 'U-17', 15, 17)");

        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, telefono, direccion, nombre, apellido, tipo_doc, correo, instagram) VALUES 
                          (1, '12944555', '0416-5533382', 'Calle 60', 'Carlos', 'Perez', 'V', 'carlos@gmail.com', '')");

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, s_nombre, p_apellidos, s_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (1, 'Diego', 'Alejandro', 'Reinoso', 'Chirinos', 'H', '2011-05-15', 'Barquisimeto', 'default.png')");
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (1, 'V', '30111222')");
        $this->pdo->exec("INSERT INTO contacto_atleta (codigo_atleta, direccion, telefono, correo, municipio, instagram) 
                          VALUES (1, 'Av Las Industrias', '0414-1111111', 'diego@cannibals.com', 'Iribarren', '@diego')");
        $this->pdo->exec("INSERT INTO datos_medicos (codigo_atleta, tipo_sangre, es_alergico, alergias_detalle) 
                          VALUES (1, 'O+', 1, 'Asma leve')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus, talla_pantalon, talla_franela, talla_calzado) 
                          VALUES (1, 1, 1, 1, 10, 55.00, 160, '2025-01-15', 2, '14', '16', '37')");
        $this->pdo->exec("INSERT INTO atleta_representante (codigo_atleta, codigo_representante) VALUES (1, 1)");
    }

    /**
     * Escenario 1: Historial de inscripciones y retiros combinados vía UNION ALL.
     */
    public function testHistorialInscripcionYRetiros(): void
    {
        $this->sembrarCatalogosYAtleta();

        // 1. Registramos el retiro de la primera inscripción
        $this->pdo->exec("INSERT INTO retiros (codigo_inscripcion, fecha_retiro, motivo) 
                          VALUES (1, '2025-06-20', 'Mudanza temporal de ciudad')");

        // 2. Registramos una segunda inscripción (reingreso) en categoría 2 (U-17)
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus, talla_pantalon, talla_franela, talla_calzado) 
                          VALUES (2, 1, 2, 2, 14, 60.00, 168, '2026-01-10', 1, 'S', 'M', '39')");

        $resultado = $this->modelo->ConsultarHistorialInscripcion(1);

        $this->assertEquals('historial', $resultado['accion']);
        $historial = $resultado['datos'];

        // Deben existir 3 eventos en total: 2 inscripciones y 1 retiro
        $this->assertCount(3, $historial, 'El historial debe contener 3 registros combinados.');

        // Por ORDER BY fecha DESC, el evento más reciente debe ser la inscripción del 2026-01-10
        $this->assertEquals('2026-01-10', $historial[0]['fecha']);
        $this->assertEquals('Inscripción', $historial[0]['tipo']);
        $this->assertStringContainsString('Categoría: U-17', $historial[0]['detalles']);

        // El segundo evento debe ser el retiro del 2025-06-20
        $this->assertEquals('2025-06-20', $historial[1]['fecha']);
        $this->assertEquals('Retiro', $historial[1]['tipo']);
        $this->assertStringContainsString('Mudanza temporal de ciudad', $historial[1]['detalles']);

        // El tercer evento debe ser la inscripción inicial del 2025-01-15
        $this->assertEquals('2025-01-15', $historial[2]['fecha']);
        $this->assertEquals('Inscripción', $historial[2]['tipo']);
        $this->assertStringContainsString('Categoría: U-15', $historial[2]['detalles']);
    }

    /**
     * Escenario 2: Búsqueda con filtro por nombre o cédula.
     */
    public function testConsultarConFiltro(): void
    {
        $this->sembrarCatalogosYAtleta();

        // Creamos un segundo atleta activo
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, s_nombre, p_apellidos, s_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (2, 'Gabriel', 'Alejandro', 'Mendoza', 'Ramos', 'H', '2010-09-12', 'Cabudare', 'default.png')");
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (2, 'V', '30999888')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, estatus) 
                          VALUES (2, 2, 1, 1, 15, 1)");

        // 1. Filtrar por 'Gabriel'
        $resGabriel = $this->modelo->Consultar(['filtro' => 'Gabriel']);
        $this->assertEquals('consultar', $resGabriel['accion']);
        $this->assertCount(1, $resGabriel['datos']);
        $this->assertEquals('Gabriel Alejandro', $resGabriel['datos'][0]['nombres']);
        $this->assertEquals('Mendoza Ramos', $resGabriel['datos'][0]['apellidos']);

        // 2. Filtrar por cédula '30111222'
        $resCedula = $this->modelo->Consultar(['filtro' => '30111222']);
        $this->assertCount(1, $resCedula['datos']);
        $this->assertEquals('Diego Alejandro', $resCedula['datos'][0]['nombres']);

        // 3. Consulta sin filtro devuelve ambos registros
        $resTodos = $this->modelo->Consultar([]);
        $this->assertCount(2, $resTodos['datos']);
    }

    /**
     * Escenario 3: Búsqueda individual de un atleta por ID.
     */
    public function testBuscarAtletaPorId(): void
    {
        $this->sembrarCatalogosYAtleta();

        $resultado = $this->modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertNotEmpty($resultado['datos']);

        $atleta = $resultado['datos'][0];
        $this->assertEquals(1, (int)$atleta['id_atleta']);
        $this->assertEquals('Diego Alejandro', $atleta['nombres']);
        $this->assertEquals('Reinoso Chirinos', $atleta['apellidos']);
        $this->assertEquals('30111222', $atleta['doc_identidad']);
        $this->assertEquals('0414-1111111', $atleta['telefono']);
        $this->assertEquals('diego@cannibals.com', $atleta['correo']);
        $this->assertEquals('Barquisimeto', $atleta['lugar_nacimiento']);
        $this->assertEquals('O+', $atleta['tipo_sangre']);
        $this->assertEquals(1, (int)$atleta['es_alergico']);
        $this->assertEquals('Asma leve', $atleta['alergias_detalle']);
        $this->assertEquals('14', $atleta['talla_pantalon']);
        $this->assertEquals('16', $atleta['talla_franela']);
        $this->assertEquals('37', $atleta['talla_calzado']);
        $this->assertEquals(1, (int)$atleta['id_categoria']);
        $this->assertEquals(1, (int)$atleta['id_posicion']);
        $this->assertEquals(1, (int)$atleta['id_representante']);
    }
}

