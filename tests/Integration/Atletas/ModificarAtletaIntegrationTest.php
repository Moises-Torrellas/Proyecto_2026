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
class ModificarAtletaIntegrationTest extends TestCase
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

    private function sembrarDatosIniciales(): void
    {
        // Posiciones
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) VALUES 
                          (1, 'Base', 'B', 'Armador'),
                          (2, 'Escolta', 'E', 'Tirador')");

        // Categorías
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES 
                          (1, 'U-15', 13, 15),
                          (2, 'U-17', 15, 17),
                          (3, 'Senior', 18, 30)");

        // Representantes
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, telefono, direccion, nombre, apellido, tipo_doc, correo, instagram) VALUES 
                          (1, '12944555', '0416-5533382', 'Calle 60', 'Carlos', 'Perez', 'V', 'carlos@gmail.com', ''),
                          (2, '14888999', '0424-7788990', 'Carrera 19', 'Maria', 'Rodriguez', 'V', 'maria@gmail.com', '')");

        // Atleta 1 base
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, s_nombre, p_apellidos, s_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (1, 'Diego', 'Alejandro', 'Reinoso', 'Chirinos', 'H', '2011-05-15', 'Barquisimeto', 'default.png')");
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (1, 'V', '30111222')");
        $this->pdo->exec("INSERT INTO contacto_atleta (codigo_atleta, direccion, telefono, correo, municipio, instagram) 
                          VALUES (1, 'Av Las Industrias', '0414-1111111', 'diego@cannibals.com', 'Iribarren', '@diego_orig')");
        $this->pdo->exec("INSERT INTO datos_medicos (codigo_atleta, tipo_sangre, es_alergico, alergias_detalle) 
                          VALUES (1, 'O+', 0, '')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus, talla_pantalon, talla_franela, talla_calzado) 
                          VALUES (1, 1, 1, 1, 10, 55.00, 160, '2026-01-10', 1, '14', '16', '37')");
        $this->pdo->exec("INSERT INTO atleta_representante (codigo_atleta, codigo_representante) VALUES (1, 1)");
    }

    /**
     * Escenario 1: Modificación integral exitosa.
     */
    public function testModificacionIntegral(): void
    {
        $this->sembrarDatosIniciales();

        $datosMod = [
            'accion'           => 'modificar',
            'id'               => '1',
            'nombre'           => 'Diego Fernando',
            'apellido'         => 'Reinoso Mendoza',
            'doc_identidad'    => '30111222',
            'genero'           => 'H',
            'fecha_nac'        => '2011-05-15',
            'telefono'         => '0414-9998888',
            'direccion'        => 'Nueva Direccion Carrera 21',
            'lugar_nacimiento' => 'Barquisimeto',
            'municipio'        => 'Palavecino',
            'correo'           => 'diego_nuevo@cannibals.com',
            'instagram'        => '@diego_basket23',
            'categoria'        => '2', // Cambio a U-17
            'posicion'         => '2', // Cambio a Escolta
            'representante'    => '1',
            'dorsal'           => '23', // Cambio de dorsal
            'peso'             => '63.00',
            'estatura'         => '172',
            'talla_pantalon'   => 'S',
            'talla_franela'    => 'M',
            'talla_calzado'    => '40',
            'tipo_sangre'      => 'A+',
            'es_alergico'      => '1',
            'alergias_detalle' => 'Penicilina'
        ];

        $resultado = $this->modelo->ProcesarDatos($datosMod);

        $this->assertEquals('exito', $resultado['accion']);

        // 1. Verificación en tabla 'atletas'
        $stmtAtleta = $this->pdo->query("SELECT * FROM atletas WHERE codigo_atleta = 1");
        $atleta = $stmtAtleta->fetch();
        $this->assertEquals('Diego', $atleta['p_nombre']);
        $this->assertEquals('Fernando', $atleta['s_nombre']);
        $this->assertEquals('Reinoso', $atleta['p_apellidos']);
        $this->assertEquals('Mendoza', $atleta['s_apellidos']);

        // 2. Verificación en tabla 'contacto_atleta'
        $stmtContacto = $this->pdo->query("SELECT * FROM contacto_atleta WHERE codigo_atleta = 1");
        $contacto = $stmtContacto->fetch();
        $this->assertEquals('0414-9998888', $contacto['telefono']);
        $this->assertEquals('Nueva Direccion Carrera 21', $contacto['direccion']);
        $this->assertEquals('diego_nuevo@cannibals.com', $contacto['correo']);
        $this->assertEquals('@diego_basket23', $contacto['instagram']);

        // 3. Verificación en tabla 'inscripciones'
        $stmtInsc = $this->pdo->query("SELECT * FROM inscripciones WHERE codigo_atleta = 1");
        $insc = $stmtInsc->fetch();
        $this->assertEquals(2, $insc['codigo_categoria'], 'La categoría debió cambiar a 2 (U-17).');
        $this->assertEquals(2, $insc['codigo_posicion'], 'La posición debió cambiar a 2 (Escolta).');
        $this->assertEquals(23, $insc['dorsal'], 'El dorsal debió cambiar a 23.');
        $this->assertEquals(63.00, (float)$insc['peso_kg']);
        $this->assertEquals(172, (float)$insc['estatura_cm']);
        $this->assertEquals('M', $insc['talla_franela']);

        // 4. Verificación en tabla 'datos_medicos'
        $stmtMed = $this->pdo->query("SELECT * FROM datos_medicos WHERE codigo_atleta = 1");
        $med = $stmtMed->fetch();
        $this->assertEquals('A+', $med['tipo_sangre']);
        $this->assertEquals(1, $med['es_alergico']);
        $this->assertEquals('Penicilina', $med['alergias_detalle']);
    }

    /**
     * Escenario 2: Modificación conservando los propios datos.
     */
    public function testModificacionConservarPropiosDatos(): void
    {
        $this->sembrarDatosIniciales();

        $datosMod = [
            'accion'           => 'modificar',
            'id'               => '1',
            'nombre'           => 'Diego Alejandro',
            'apellido'         => 'Reinoso Chirinos',
            'doc_identidad'    => '30111222', // Misma cédula
            'telefono'         => '0414-1111111', // Mismo teléfono
            'correo'           => 'diego@cannibals.com', // Mismo correo
            'instagram'        => '@diego_orig', // Mismo instagram
            'genero'           => 'H',
            'fecha_nac'        => '2011-05-15',
            'lugar_nacimiento' => 'Barquisimeto',
            'categoria'        => '1',
            'posicion'         => '1',
            'representante'    => '1',
            'dorsal'           => '10', // Mismo dorsal
            'peso'             => '57.00', // Modifica solo el peso
            'estatura'         => '162',
            'talla_pantalon'   => 'S',
            'talla_franela'    => 'S',
            'talla_calzado'    => '38'
        ];

        $resultado = $this->modelo->ProcesarDatos($datosMod);

        $this->assertEquals('exito', $resultado['accion']);

        $stmtInsc = $this->pdo->query("SELECT peso_kg FROM inscripciones WHERE codigo_atleta = 1");
        $this->assertEquals(57.00, (float)$stmtInsc->fetchColumn());
    }

    /**
     * Escenario 3: Rechazo al modificar cédula hacia la de otro atleta.
     */
    public function testErrorModificarCedulaAjena(): void
    {
        $this->sembrarDatosIniciales();

        // Creamos un segundo atleta con cédula 30555666
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (2, 'Gabriel', 'Mendoza', 'H', '2011-08-20', 'Lara', 'default.png')");
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (2, 'V', '30555666')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, estatus) 
                          VALUES (2, 2, 1, 1, 15, 1)");

        // El atleta 2 intenta cambiar su cédula a '30111222' (que pertenece al atleta 1)
        $datosMod = [
            'accion'           => 'modificar',
            'id'               => '2',
            'nombre'           => 'Gabriel',
            'apellido'         => 'Mendoza',
            'doc_identidad'    => '30111222', // Colisión con atleta 1
            'genero'           => 'H',
            'fecha_nac'        => '2011-08-20',
            'lugar_nacimiento' => 'Lara',
            'categoria'        => '1',
            'posicion'         => '1',
            'representante'    => '1',
            'dorsal'           => '15'
        ];

        $resultado = $this->modelo->ProcesarDatos($datosMod);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_CEDULA, $resultado['codigo']);

        // Verificamos que la cédula del atleta 2 no fue modificada
        $stmtDoc = $this->pdo->query("SELECT numero_doc FROM identidad_atleta WHERE codigo_atleta = 2");
        $this->assertEquals('30555666', $stmtDoc->fetchColumn());
    }

    /**
     * Escenario 4: Rechazo al modificar teléfono hacia el de otro atleta.
     */
    public function testErrorModificarTelefonoAjeno(): void
    {
        $this->sembrarDatosIniciales();

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (2, 'Gabriel', 'Mendoza', 'H', '2011-08-20', 'Lara', 'default.png')");
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (2, 'V', '30555666')");
        $this->pdo->exec("INSERT INTO contacto_atleta (codigo_atleta, telefono) VALUES (2, '0414-2222222')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, estatus) 
                          VALUES (2, 2, 1, 1, 15, 1)");

        // Atleta 2 intenta cambiar su teléfono al '0414-1111111' (perteneciente al atleta 1)
        $datosMod = [
            'accion'           => 'modificar',
            'id'               => '2',
            'nombre'           => 'Gabriel',
            'apellido'         => 'Mendoza',
            'doc_identidad'    => '30555666',
            'telefono'         => '0414-1111111',
            'genero'           => 'H',
            'fecha_nac'        => '2011-08-20',
            'lugar_nacimiento' => 'Lara',
            'categoria'        => '1',
            'posicion'         => '1',
            'representante'    => '1',
            'dorsal'           => '15'
        ];

        $resultado = $this->modelo->ProcesarDatos($datosMod);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_PHONE, $resultado['codigo']);
    }

    /**
     * Escenario 5: Cambio y desvinculación de representante.
     */
    public function testModificarRepresentante(): void
    {
        $this->sembrarDatosIniciales();

        // 1. Cambiar al representante 2
        $datosMod1 = [
            'accion'           => 'modificar',
            'id'               => '1',
            'nombre'           => 'Diego Alejandro',
            'apellido'         => 'Reinoso Chirinos',
            'doc_identidad'    => '30111222',
            'genero'           => 'H',
            'fecha_nac'        => '2011-05-15',
            'lugar_nacimiento' => 'Barquisimeto',
            'categoria'        => '1',
            'posicion'         => '1',
            'dorsal'           => '10',
            'representante'    => '2'
        ];

        $res1 = $this->modelo->ProcesarDatos($datosMod1);
        $this->assertEquals('exito', $res1['accion']);

        $stmtRep = $this->pdo->query("SELECT codigo_representante FROM atleta_representante WHERE codigo_atleta = 1");
        $this->assertEquals(2, (int)$stmtRep->fetchColumn());

        // 2. Desvincular representante enviando '0':
        // Según reglas del negocio, el atleta debe ser mayor de edad (>= 18 años)
        $anioAdulto = (int)date('Y') - 21;
        $this->pdo->exec("UPDATE atletas SET fecha_nac = '{$anioAdulto}-05-15' WHERE codigo_atleta = 1");

        $datosMod2 = [
            'accion'           => 'modificar',
            'id'               => '1',
            'nombre'           => 'Diego Alejandro',
            'apellido'         => 'Reinoso Chirinos',
            'doc_identidad'    => '30111222',
            'genero'           => 'H',
            'fecha_nac'        => "{$anioAdulto}-05-15",
            'lugar_nacimiento' => 'Barquisimeto',
            'categoria'        => '3', // Senior
            'posicion'         => '1',
            'dorsal'           => '10',
            'representante'    => '0' // Desvinculación permitida por ser mayor de edad
        ];

        $res2 = $this->modelo->ProcesarDatos($datosMod2);
        $this->assertEquals('exito', $res2['accion']);

        $stmtConteoRep = $this->pdo->query("SELECT COUNT(*) FROM atleta_representante WHERE codigo_atleta = 1");
        $this->assertEquals(0, (int)$stmtConteoRep->fetchColumn(), 'El registro relacional de representante debió ser eliminado.');
    }
}
