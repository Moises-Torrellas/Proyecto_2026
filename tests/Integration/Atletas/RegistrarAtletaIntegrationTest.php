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
class RegistrarAtletaIntegrationTest extends TestCase
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

    private function cargarCatalogosBase(): void
    {
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) VALUES (1, 'Base', 'B', 'Organizador de juego')");
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES 
                          (1, 'U-15', 13, 15),
                          (2, 'Iniciación', 6, 9),
                          (3, 'Senior', 18, 30)");
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, telefono, direccion, nombre, apellido, tipo_doc, correo, instagram) 
                          VALUES (1, '12944555', '0416-5533382', 'Calle 60 Entre Carrera 14A Y 14B', 'Carlos', 'Perez', 'V', 'carlos@gmail.com', '@carlosp')");
    }

    /**
     * Escenario 1: Registro completo atómico.
     */
    public function testRegistroCompleto(): void
    {
        $this->cargarCatalogosBase();

        $datos = [
            'accion'           => 'incluir',
            'nombre'           => 'Diego Alejandro',
            'apellido'         => 'Reinoso Chirinos',
            'doc_identidad'    => '30111222',
            'genero'           => 'H',
            'fecha_nac'        => '2011-05-15',
            'telefono'         => '0414-5556677',
            'direccion'        => 'Av Las Industrias Carrera 3',
            'lugar_nacimiento' => 'Barquisimeto',
            'municipio'        => 'Iribarren',
            'correo'           => 'diego.reinoso@gmail.com',
            'instagram'        => '@diego_basket',
            'categoria'        => '1',
            'posicion'         => '1',
            'representante'    => '1',
            'dorsal'           => '23',
            'peso'             => '58.50',
            'estatura'         => '165',
            'talla_pantalon'   => 'S',
            'talla_franela'    => 'S',
            'talla_calzado'    => '38',
            'tipo_sangre'      => 'O+',
            'es_alergico'      => '1',
            'alergias_detalle' => 'Alérgico al polvo y polen'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // 1. Verificación en tabla 'atletas'
        $stmtAtleta = $this->pdo->query("SELECT * FROM atletas WHERE p_nombre = 'Diego' AND p_apellidos = 'Reinoso'");
        $atleta = $stmtAtleta->fetch();
        $this->assertNotEmpty($atleta, 'El atleta no fue registrado en la tabla atletas.');
        $idAtleta = (int) $atleta['codigo_atleta'];
        $this->assertEquals('Alejandro', $atleta['s_nombre']);
        $this->assertEquals('Chirinos', $atleta['s_apellidos']);
        $this->assertEquals('Barquisimeto', $atleta['lugar_nacimiento']);
        $this->assertEquals('H', $atleta['genero']);

        // 2. Verificación en tabla 'identidad_atleta'
        $stmtIdent = $this->pdo->query("SELECT * FROM identidad_atleta WHERE codigo_atleta = {$idAtleta}");
        $ident = $stmtIdent->fetch();
        $this->assertNotEmpty($ident, 'No se registró la identidad del atleta.');
        $this->assertEquals('30111222', $ident['numero_doc']);

        // 3. Verificación en tabla 'contacto_atleta'
        $stmtContacto = $this->pdo->query("SELECT * FROM contacto_atleta WHERE codigo_atleta = {$idAtleta}");
        $contacto = $stmtContacto->fetch();
        $this->assertNotEmpty($contacto, 'No se registró el contacto del atleta.');
        $this->assertEquals('0414-5556677', $contacto['telefono']);
        $this->assertEquals('diego.reinoso@gmail.com', $contacto['correo']);
        $this->assertEquals('@diego_basket', $contacto['instagram']);

        // 4. Verificación en tabla 'inscripciones'
        $stmtInsc = $this->pdo->query("SELECT * FROM inscripciones WHERE codigo_atleta = {$idAtleta}");
        $insc = $stmtInsc->fetch();
        $this->assertNotEmpty($insc, 'No se registró la inscripción del atleta.');
        $this->assertEquals(1, $insc['codigo_categoria']);
        $this->assertEquals(1, $insc['codigo_posicion']);
        $this->assertEquals(23, $insc['dorsal']);
        $this->assertEquals(1, $insc['estatus'], 'El estatus de la inscripción inicial debe ser 1 (Activo).');

        // 5. Verificación en tabla 'datos_medicos'
        $stmtMed = $this->pdo->query("SELECT * FROM datos_medicos WHERE codigo_atleta = {$idAtleta}");
        $med = $stmtMed->fetch();
        $this->assertNotEmpty($med, 'No se registraron los datos médicos del atleta.');
        $this->assertEquals('O+', $med['tipo_sangre']);
        $this->assertEquals(1, $med['es_alergico']);

        // 6. Verificación en tabla 'atleta_representante'
        $stmtRep = $this->pdo->query("SELECT * FROM atleta_representante WHERE codigo_atleta = {$idAtleta}");
        $rep = $stmtRep->fetch();
        $this->assertNotEmpty($rep, 'No se vinculó el atleta con el representante.');
        $this->assertEquals(1, $rep['codigo_representante']);
    }

    /**
     * Escenario 2a: Registro de atleta menor de 9 años sin cédula.
     */
    public function testRegistroMenorSinCedula(): void
    {
        $this->cargarCatalogosBase();

        $anioNac = (int)date('Y') - 8; // 8 años de edad

        $datos = [
            'accion'           => 'incluir',
            'nombre'           => 'Mateo Jose',
            'apellido'         => 'Gomez Silva',
            'doc_identidad'    => '',
            'genero'           => 'H',
            'fecha_nac'        => "{$anioNac}-03-10",
            'telefono'         => '',
            'direccion'        => 'Urb La Rosaleda',
            'lugar_nacimiento' => 'Cabudare',
            'categoria'        => '2', // Iniciación
            'posicion'         => '1',
            'representante'    => '1',
            'dorsal'           => '7',
            'peso'             => '30.00',
            'estatura'         => '125',
            'talla_pantalon'   => 'XS',
            'talla_franela'    => 'XS',
            'talla_calzado'    => '30',
            'tipo_sangre'      => 'A+',
            'es_alergico'      => '0'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmtAtleta = $this->pdo->query("SELECT codigo_atleta FROM atletas WHERE p_nombre = 'Mateo' AND p_apellidos = 'Gomez'");
        $idAtleta = (int) $stmtAtleta->fetchColumn();
        $this->assertGreaterThan(0, $idAtleta);

        // Sin identidad registrada en BD
        $stmtIdent = $this->pdo->query("SELECT COUNT(*) FROM identidad_atleta WHERE codigo_atleta = {$idAtleta}");
        $this->assertEquals(0, (int) $stmtIdent->fetchColumn());

        // Representante sí vinculado por ser menor de edad
        $stmtRep = $this->pdo->query("SELECT COUNT(*) FROM atleta_representante WHERE codigo_atleta = {$idAtleta}");
        $this->assertEquals(1, (int) $stmtRep->fetchColumn());
    }

    /**
     * Escenario 2b: Registro de atleta mayor de edad sin representante.
     */
    public function testRegistroMayorSinRepresentante(): void
    {
        $this->cargarCatalogosBase();

        $anioNac = (int)date('Y') - 20; // 20 años de edad

        $datos = [
            'accion'           => 'incluir',
            'nombre'           => 'Daniel Eduardo',
            'apellido'         => 'Alvarado Perez',
            'doc_identidad'    => '28999888',
            'genero'           => 'H',
            'fecha_nac'        => "{$anioNac}-05-20",
            'telefono'         => '0412-8889900',
            'direccion'        => 'Carrera 23 con Calle 10',
            'lugar_nacimiento' => 'Barquisimeto',
            'categoria'        => '3', // Senior
            'posicion'         => '1',
            'representante'    => '0', // Sin representante por ser mayor de edad
            'dorsal'           => '99',
            'peso'             => '75.00',
            'estatura'         => '180',
            'talla_pantalon'   => 'M',
            'talla_franela'    => 'L',
            'talla_calzado'    => '42',
            'tipo_sangre'      => 'O+',
            'es_alergico'      => '0'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmtAtleta = $this->pdo->query("SELECT codigo_atleta FROM atletas WHERE p_nombre = 'Daniel' AND p_apellidos = 'Alvarado'");
        $idAtleta = (int) $stmtAtleta->fetchColumn();
        $this->assertGreaterThan(0, $idAtleta);

        // Sin representante vinculado
        $stmtRep = $this->pdo->query("SELECT COUNT(*) FROM atleta_representante WHERE codigo_atleta = {$idAtleta}");
        $this->assertEquals(0, (int) $stmtRep->fetchColumn());

        // Con identidad registrada
        $stmtIdent = $this->pdo->query("SELECT COUNT(*) FROM identidad_atleta WHERE codigo_atleta = {$idAtleta}");
        $this->assertEquals(1, (int) $stmtIdent->fetchColumn());
    }

    /**
     * Escenario 3: Rechazo ante documento de identidad duplicado.
     */
    public function testErrorCedulaDuplicada(): void
    {
        $this->cargarCatalogosBase();

        // 1. Insertamos un primer atleta con cédula 29999888
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (1, 'Andres', 'Silva', 'H', '2011-01-01', 'Lara', 'default.png')");
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (1, 'V', '29999888')");

        // 2. Intentamos registrar a otro atleta con la misma cédula
        $datos = [
            'accion'           => 'incluir',
            'nombre'           => 'Jose Manuel',
            'apellido'         => 'Rojas Gil',
            'doc_identidad'    => '29999888',
            'genero'           => 'H',
            'fecha_nac'        => '2011-02-02',
            'lugar_nacimiento' => 'Barquisimeto',
            'categoria'        => '1',
            'posicion'         => '1',
            'representante'    => '1',
            'dorsal'           => '12'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_CEDULA, $resultado['codigo']);

        // Verificamos que el segundo atleta no se haya creado en BD
        $stmtConteo = $this->pdo->query("SELECT COUNT(*) FROM atletas");
        $this->assertEquals(1, (int) $stmtConteo->fetchColumn(), 'No deben quedar registros huérfanos tras el error.');
    }

    /**
     * Escenario 4: Rechazo ante teléfono duplicado.
     */
    public function testErrorTelefonoDuplicado(): void
    {
        $this->cargarCatalogosBase();

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (1, 'Andres', 'Silva', 'H', '2011-01-01', 'Lara', 'default.png')");
        $this->pdo->exec("INSERT INTO contacto_atleta (codigo_atleta, telefono) VALUES (1, '0412-9988776')");

        $datos = [
            'accion'           => 'incluir',
            'nombre'           => 'Jose Manuel',
            'apellido'         => 'Rojas Gil',
            'doc_identidad'    => '30333444',
            'telefono'         => '0412-9988776',
            'genero'           => 'H',
            'fecha_nac'        => '2011-02-02',
            'lugar_nacimiento' => 'Barquisimeto',
            'categoria'        => '1',
            'posicion'         => '1',
            'representante'    => '1',
            'dorsal'           => '12'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_PHONE, $resultado['codigo']);
    }

    /**
     * Escenario 5: Rechazo ante correo duplicado.
     */
    public function testErrorCorreoDuplicado(): void
    {
        $this->cargarCatalogosBase();

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (1, 'Andres', 'Silva', 'H', '2011-01-01', 'Lara', 'default.png')");
        $this->pdo->exec("INSERT INTO contacto_atleta (codigo_atleta, correo) VALUES (1, 'atleta_club@cannibals.com')");

        $datos = [
            'accion'           => 'incluir',
            'nombre'           => 'Camilo Ernesto',
            'apellido'         => 'Blanco Peña',
            'doc_identidad'    => '30333444',
            'correo'           => 'atleta_club@cannibals.com',
            'genero'           => 'H',
            'fecha_nac'        => '2011-03-03',
            'lugar_nacimiento' => 'Barquisimeto',
            'categoria'        => '1',
            'posicion'         => '1',
            'representante'    => '1',
            'dorsal'           => '14'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_EMAIL, $resultado['codigo']);
    }

    /**
     * Escenario 6: Rechazo ante Instagram duplicado.
     */
    public function testErrorInstagramDuplicado(): void
    {
        $this->cargarCatalogosBase();

        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (1, 'Andres', 'Silva', 'H', '2011-01-01', 'Lara', 'default.png')");
        $this->pdo->exec("INSERT INTO contacto_atleta (codigo_atleta, instagram) VALUES (1, '@atletacannibal')");

        $datos = [
            'accion'           => 'incluir',
            'nombre'           => 'Camilo Ernesto',
            'apellido'         => 'Blanco Peña',
            'doc_identidad'    => '30333444',
            'instagram'        => '@atletacannibal',
            'genero'           => 'H',
            'fecha_nac'        => '2011-03-03',
            'lugar_nacimiento' => 'Barquisimeto',
            'categoria'        => '1',
            'posicion'         => '1',
            'representante'    => '1',
            'dorsal'           => '14'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_INSTAGRAM, $resultado['codigo']);
    }

    /**
     * Escenario 7: Rechazo ante claves foráneas inexistentes.
     */
    public function testErrorClavesForaneas(): void
    {
        $this->cargarCatalogosBase();

        // A. Categoría inexistente
        $datosCat = [
            'accion'           => 'incluir',
            'nombre'           => 'Pedro',
            'apellido'         => 'Picapiedra',
            'doc_identidad'    => '30333444',
            'lugar_nacimiento' => 'Barquisimeto',
            'genero'           => 'H',
            'fecha_nac'        => '2011-01-01',
            'categoria'        => '99999',
            'posicion'         => '1',
            'representante'    => '1'
        ];
        $resCat = $this->modelo->ProcesarDatos($datosCat);
        $this->assertEquals('error', $resCat['accion']);
        $this->assertEquals(INVALID_ID, $resCat['codigo']);

        // B. Posición inexistente
        $datosPos = [
            'accion'           => 'incluir',
            'nombre'           => 'Pedro',
            'apellido'         => 'Picapiedra',
            'doc_identidad'    => '30333444',
            'lugar_nacimiento' => 'Barquisimeto',
            'genero'           => 'H',
            'fecha_nac'        => '2011-01-01',
            'categoria'        => '1',
            'posicion'         => '99999',
            'representante'    => '1'
        ];
        $resPos = $this->modelo->ProcesarDatos($datosPos);
        $this->assertEquals('error', $resPos['accion']);
        $this->assertEquals(INVALID_ID . '0', $resPos['codigo']);

        // C. Representante inexistente
        $datosRep = [
            'accion'           => 'incluir',
            'nombre'           => 'Pedro',
            'apellido'         => 'Picapiedra',
            'doc_identidad'    => '30333444',
            'lugar_nacimiento' => 'Barquisimeto',
            'genero'           => 'H',
            'fecha_nac'        => '2011-01-01',
            'categoria'        => '1',
            'posicion'         => '1',
            'representante'    => '99999'
        ];
        $resRep = $this->modelo->ProcesarDatos($datosRep);
        $this->assertEquals('error', $resRep['accion']);
        $this->assertEquals(INVALID_ID . '1', $resRep['codigo']);
    }
}
