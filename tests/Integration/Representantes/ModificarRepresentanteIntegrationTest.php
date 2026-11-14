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
class ModificarRepresentanteIntegrationTest extends TestCase
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
     * Modificación exitosa de los datos de un representante.
     */
    public function testModificacionExitosa(): void
    {
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) 
                          VALUES (1, '14555666', 'V', 'Roberto', 'Mendoza', '0414-1112233', 'Antigua Direccion', 'antiguo@correo.com', '@antiguo')");

        $datos = [
            'accion'       => 'modificar',
            'id'           => '1',
            'cedula'       => '14555666',
            'nacionalidad' => 'V',
            'nombre'       => 'Roberto Carlos',
            'apellido'     => 'Mendoza Silva',
            'telefono'     => '0414-9998877',
            'direccion'    => 'Nueva Direccion Av Venezuela',
            'correo'       => 'nuevo@correo.com',
            'instagram'    => '@nuevo_insta'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificamos en BD
        $stmt = $this->pdo->query("SELECT * FROM representantes WHERE codigo_representante = 1");
        $rep = $stmt->fetch();

        $this->assertEquals('Roberto Carlos', $rep['nombre']);
        $this->assertEquals('Mendoza Silva', $rep['apellido']);
        $this->assertEquals('0414-9998877', $rep['telefono']);
        $this->assertEquals('Nueva Direccion Av Venezuela', $rep['direccion']);
        $this->assertEquals('nuevo@correo.com', $rep['correo']);
        $this->assertEquals('@nuevo_insta', $rep['instagram']);
    }

    /**
     * Auto-exclusión: conservar sus propios datos sin disparar duplicado.
     */
    public function testModificacionConservarPropiosDatos(): void
    {
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) 
                          VALUES (1, '14555666', 'V', 'Roberto', 'Mendoza', '0414-1112233', 'Av Lara', 'roberto@correo.com', '@roberto')");

        // Conserva cedula, telefono, correo e instagram; solo cambia direccion
        $datos = [
            'accion'       => 'modificar',
            'id'           => '1',
            'cedula'       => '14555666',
            'nacionalidad' => 'V',
            'nombre'       => 'Roberto',
            'apellido'     => 'Mendoza',
            'telefono'     => '0414-1112233',
            'direccion'    => 'Av Lara Con Calle 15 Nueva Casa',
            'correo'       => 'roberto@correo.com',
            'instagram'    => '@roberto'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        $stmt = $this->pdo->query("SELECT direccion FROM representantes WHERE codigo_representante = 1");
        $this->assertEquals('Av Lara Con Calle 15 Nueva Casa', $stmt->fetchColumn());
    }

    /**
     * Rechazo al intentar modificar hacia la cédula de otro representante.
     */
    public function testErrorModificarCedulaAjena(): void
    {
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) VALUES 
                          (1, '14111111', 'V', 'Carlos', 'Perez', '0414-1111111', 'Dir 1', 'carlos@mail.com', '@carlos'),
                          (2, '15222222', 'V', 'Maria', 'Gomez', '0414-2222222', 'Dir 2', 'maria@mail.com', '@maria')");

        // Representante 2 intenta usar la cédula del representante 1
        $datos = [
            'accion'       => 'modificar',
            'id'           => '2',
            'cedula'       => '14111111', // Cédula ya ocupada por representante 1
            'nacionalidad' => 'V',
            'nombre'       => 'Maria',
            'apellido'     => 'Gomez',
            'telefono'     => '0414-2222222',
            'direccion'    => 'Dir 2',
            'correo'       => 'maria@mail.com',
            'instagram'    => '@maria'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_CEDULA, $resultado['codigo']);

        // Verificamos que la cédula en BD no fue modificada
        $stmt = $this->pdo->query("SELECT cedula FROM representantes WHERE codigo_representante = 2");
        $this->assertEquals('15222222', $stmt->fetchColumn());
    }

    /**
     * Rechazo al intentar modificar hacia el teléfono de otro representante.
     */
    public function testErrorModificarTelefonoAjeno(): void
    {
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) VALUES 
                          (1, '14111111', 'V', 'Carlos', 'Perez', '0414-1111111', 'Dir 1', 'carlos@mail.com', '@carlos'),
                          (2, '15222222', 'V', 'Maria', 'Gomez', '0414-2222222', 'Dir 2', 'maria@mail.com', '@maria')");

        // Representante 2 intenta usar el teléfono del representante 1
        $datos = [
            'accion'       => 'modificar',
            'id'           => '2',
            'cedula'       => '15222222',
            'nacionalidad' => 'V',
            'nombre'       => 'Maria',
            'apellido'     => 'Gomez',
            'telefono'     => '0414-1111111', // Teléfono en conflicto
            'direccion'    => 'Dir 2',
            'correo'       => 'maria@mail.com',
            'instagram'    => '@maria'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_PHONE, $resultado['codigo']);
    }

    /**
     * Rechazo al intentar modificar hacia el correo de otro representante.
     */
    public function testErrorModificarCorreoAjeno(): void
    {
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) VALUES 
                          (1, '14111111', 'V', 'Carlos', 'Perez', '0414-1111111', 'Dir 1', 'carlos@mail.com', '@carlos'),
                          (2, '15222222', 'V', 'Maria', 'Gomez', '0414-2222222', 'Dir 2', 'maria@mail.com', '@maria')");

        $datos = [
            'accion'       => 'modificar',
            'id'           => '2',
            'cedula'       => '15222222',
            'nacionalidad' => 'V',
            'nombre'       => 'Maria',
            'apellido'     => 'Gomez',
            'telefono'     => '0414-2222222',
            'direccion'    => 'Dir 2',
            'correo'       => 'carlos@mail.com', // Correo en conflicto
            'instagram'    => '@maria'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_EMAIL, $resultado['codigo']);
    }

    /**
     * Rechazo al intentar modificar hacia el Instagram de otro representante.
     */
    public function testErrorModificarInstagramAjeno(): void
    {
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) VALUES 
                          (1, '14111111', 'V', 'Carlos', 'Perez', '0414-1111111', 'Dir 1', 'carlos@mail.com', '@carlos_oficial'),
                          (2, '15222222', 'V', 'Maria', 'Gomez', '0414-2222222', 'Dir 2', 'maria@mail.com', '@maria')");

        $datos = [
            'accion'       => 'modificar',
            'id'           => '2',
            'cedula'       => '15222222',
            'nacionalidad' => 'V',
            'nombre'       => 'Maria',
            'apellido'     => 'Gomez',
            'telefono'     => '0414-2222222',
            'direccion'    => 'Dir 2',
            'correo'       => 'maria@mail.com',
            'instagram'    => '@carlos_oficial' // Instagram en conflicto
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_INSTAGRAM, $resultado['codigo']);
    }

    /**
     * Rechazo al modificar cédula hacia la de un atleta menor de edad.
     */
    public function testErrorModificarAtletaMenor(): void
    {
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, tipo_doc, nombre, apellido, telefono, direccion, correo, instagram) 
                          VALUES (1, '14111111', 'V', 'Carlos', 'Perez', '0414-1111111', 'Dir 1', 'carlos@mail.com', '@carlos')");

        // Creamos un atleta menor de 18 años
        $anioMenor = (int)date('Y') - 12;
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (1, 'Samuel', 'Torres', 'H', '{$anioMenor}-04-10', 'Barquisimeto', 'default.png')");
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (1, 'V', '32444555')");

        // Representante intenta cambiar su cédula por la del menor
        $datos = [
            'accion'       => 'modificar',
            'id'           => '1',
            'cedula'       => '32444555',
            'nacionalidad' => 'V',
            'nombre'       => 'Carlos',
            'apellido'     => 'Perez',
            'telefono'     => '0414-1111111',
            'direccion'    => 'Dir 1',
            'correo'       => 'carlos@mail.com',
            'instagram'    => '@carlos'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('UNDER_AGE_ATHLETE', $resultado['codigo']);
    }
}

