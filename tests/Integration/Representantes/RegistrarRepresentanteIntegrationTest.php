<?php

declare(strict_types=1);

namespace tests\Integration\Representantes;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRepresentantes;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('DUPLICATE_CEDULA')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group("integ")]
#[Group("representantes")]
class RegistrarRepresentanteIntegrationTest extends TestCase
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

    /**
     * Inserción completa con todos los campos.
     */
    public function testRegistroCompleto(): void
    {
        $datos = [
            'accion'       => 'incluir',
            'cedula'       => '14555666',
            'nacionalidad' => 'V',
            'nombre'       => 'Roberto Carlos',
            'apellido'     => 'Mendoza Silva',
            'telefono'     => '0414-5556677',
            'direccion'    => 'Carrera 19 Entre Calles 20 Y 21',
            'correo'       => 'roberto.mendoza@gmail.com',
            'instagram'    => '@robertom'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD
        $stmt = $this->pdo->prepare("SELECT * FROM representantes WHERE cedula = :cedula");
        $stmt->execute([':cedula' => '14555666']);
        $rep = $stmt->fetch();

        $this->assertNotEmpty($rep, 'El representante debió insertarse en la tabla representantes.');
        $this->assertEquals('14555666', $rep['cedula']);
        $this->assertEquals('V', $rep['tipo_doc']);
        $this->assertEquals('Roberto Carlos', $rep['nombre']);
        $this->assertEquals('Mendoza Silva', $rep['apellido']);
        $this->assertEquals('0414-5556677', $rep['telefono']);
        $this->assertEquals('Carrera 19 Entre Calles 20 Y 21', $rep['direccion']);
        $this->assertEquals('roberto.mendoza@gmail.com', $rep['correo']);
        $this->assertEquals('@robertom', $rep['instagram']);
    }

    /**
     * Inserción exitosa con campos opcionales vacíos (correo e instagram).
     */
    public function testRegistroCamposOpcionalesVacios(): void
    {
        $datos = [
            'accion'       => 'incluir',
            'cedula'       => '16888999',
            'nacionalidad' => 'V',
            'nombre'       => 'Elena Beatriz',
            'apellido'     => 'Torres Perez',
            'telefono'     => '0424-7778899',
            'direccion'    => 'Urb El Parral Manzana B',
            'correo'       => '',
            'instagram'    => ''
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->prepare("SELECT * FROM representantes WHERE cedula = :cedula");
        $stmt->execute([':cedula' => '16888999']);
        $rep = $stmt->fetch();

        $this->assertNotEmpty($rep);
        $this->assertEquals('', $rep['correo']);
        $this->assertEquals('', $rep['instagram']);
    }

    /**
     * Rechazo por cédula duplicada.
     */
    public function testErrorCedulaDuplicada(): void
    {
        // Insertamos representante inicial
        $this->pdo->exec("INSERT INTO representantes (cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) 
                          VALUES ('15111222', 'V', 'Carlos', 'Gomez', '0414-1112233', 'Calle 10', 'carlos@mail.com', '@carlos')");

        // Intento con misma cédula
        $datos = [
            'accion'       => 'incluir',
            'cedula'       => '15111222',
            'nacionalidad' => 'V',
            'nombre'       => 'Pedro Luis',
            'apellido'     => 'Morales Ruiz',
            'telefono'     => '0416-9998877',
            'direccion'    => 'Calle 40',
            'correo'       => 'pedro@mail.com',
            'instagram'    => '@pedro'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_CEDULA, $resultado['codigo']);

        // No debe haberse insertado el segundo
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM representantes");
        $this->assertEquals(1, (int)$stmt->fetchColumn());
    }

    /**
     * Rechazo por teléfono duplicado.
     */
    public function testErrorTelefonoDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO representantes (cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) 
                          VALUES ('15111222', 'V', 'Carlos', 'Gomez', '0414-1112233', 'Calle 10', 'carlos@mail.com', '@carlos')");

        $datos = [
            'accion'       => 'incluir',
            'cedula'       => '18333444',
            'nacionalidad' => 'V',
            'nombre'       => 'Pedro Luis',
            'apellido'     => 'Morales Ruiz',
            'telefono'     => '0414-1112233', // Mismo teléfono
            'direccion'    => 'Calle 40',
            'correo'       => 'pedro@mail.com',
            'instagram'    => '@pedro'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_PHONE, $resultado['codigo']);
    }

    /**
     * Rechazo por correo duplicado.
     */
    public function testErrorCorreoDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO representantes (cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) 
                          VALUES ('15111222', 'V', 'Carlos', 'Gomez', '0414-1112233', 'Calle 10', 'contacto@familia.com', '@carlos')");

        $datos = [
            'accion'       => 'incluir',
            'cedula'       => '18333444',
            'nacionalidad' => 'V',
            'nombre'       => 'Pedro Luis',
            'apellido'     => 'Morales Ruiz',
            'telefono'     => '0416-5554433',
            'direccion'    => 'Calle 40',
            'correo'       => 'contacto@familia.com', // Mismo correo
            'instagram'    => '@pedro'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_EMAIL, $resultado['codigo']);
    }

    /**
     * Rechazo por Instagram duplicado.
     */
    public function testErrorInstagramDuplicado(): void
    {
        $this->pdo->exec("INSERT INTO representantes (cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) 
                          VALUES ('15111222', 'V', 'Carlos', 'Gomez', '0414-1112233', 'Calle 10', 'carlos@mail.com', '@representante_fam')");

        $datos = [
            'accion'       => 'incluir',
            'cedula'       => '18333444',
            'nacionalidad' => 'V',
            'nombre'       => 'Pedro Luis',
            'apellido'     => 'Morales Ruiz',
            'telefono'     => '0416-5554433',
            'direccion'    => 'Calle 40',
            'correo'       => 'pedro@mail.com',
            'instagram'    => '@representante_fam' // Mismo instagram
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_INSTAGRAM, $resultado['codigo']);
    }

    /**
     * Rechazo si la cédula pertenece a un atleta menor de edad.
     */
    public function testErrorAtletaMenorDeEdad(): void
    {
        // 1. Registramos en BD un atleta que tiene 14 años
        $anioNacMenor = (int)date('Y') - 14;
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                        VALUES (1, 'Junior', 'Perez', 'H', '{$anioNacMenor}-06-15', 'Barquisimeto', 'default.png')");
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (1, 'V', '31222333')");

        // 2. Intentamos registrar a un representante con la misma cédula del atleta menor
        $datos = [
            'accion'       => 'incluir',
            'cedula'       => '31222333',
            'nacionalidad' => 'V',
            'nombre'       => 'Junior Alexander',
            'apellido'     => 'Perez Ramos',
            'telefono'     => '0414-7776655',
            'direccion'    => 'Av Libertador',
            'correo'       => 'junior@mail.com',
            'instagram'    => '@junior'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('UNDER_AGE_ATHLETE', $resultado['codigo']);

        // Verificamos que no se haya insertado en representantes
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM representantes WHERE cedula = '31222333'");
        $this->assertEquals(0, (int)$stmt->fetchColumn());
    }
}

