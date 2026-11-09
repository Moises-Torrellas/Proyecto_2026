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
class RetiroYReinscripcionAtletaIntegrationTest extends TestCase
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
        $this->pdo->exec("TRUNCATE TABLE conceptos;");
        $this->pdo->exec("TRUNCATE TABLE monedas;");
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

    private function sembrarAtletaActivo(): void
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
                          VALUES (1, 'O+', 0, '')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus, talla_pantalon, talla_franela, talla_calzado) 
                          VALUES (1, 1, 1, 1, 10, 55.00, 160, '2026-01-10', 1, '14', '16', '37')");
        $this->pdo->exec("INSERT INTO atleta_representante (codigo_atleta, codigo_representante) VALUES (1, 1)");
    }

    /**
     * Escenario 1: Retiro voluntario exitoso.
     */
    public function testRetiroExitoso(): void
    {
        $this->sembrarAtletaActivo();

        $datosRetiro = [
            'accion'        => 'eliminar',
            'id'            => '1',
            'motivo_retiro' => 'Mudanza familiar a otra region'
        ];

        $resultado = $this->modelo->ProcesarDatos($datosRetiro);

        $this->assertEquals('exito', $resultado['accion']);

        // 1. Verificamos que el estatus de la inscripción cambió a 2 (Retirado)
        $stmtInsc = $this->pdo->query("SELECT estatus FROM inscripciones WHERE codigo_atleta = 1 AND codigo_inscripcion = 1");
        $this->assertEquals(2, (int)$stmtInsc->fetchColumn());

        // 2. Verificamos que se insertó el registro de retiro en la tabla `retiros`
        $stmtRetiro = $this->pdo->query("SELECT * FROM retiros WHERE codigo_inscripcion = 1");
        $retiro = $stmtRetiro->fetch();
        $this->assertNotEmpty($retiro, 'No se registró la fila en la tabla retiros.');
        $this->assertEquals('Mudanza familiar a otra region', $retiro['motivo']);
        $this->assertEquals(date('Y-m-d'), $retiro['fecha_retiro']);
    }

    /**
     * Escenario 2: Bloqueo de retiro con cargos pendientes.
     */
    public function testErrorRetiroConCargos(): void
    {
        $this->sembrarAtletaActivo();

        // Creamos conceptos y monedas base
        $this->pdo->exec("INSERT INTO monedas (codigo_moneda, nombre, abreviatura, simbolo, base, estatus) VALUES (2, 'Dolar', 'USD', '$', 1, 1)");
        $this->pdo->exec("INSERT INTO conceptos (codigo_concepto, nombre, monto, frecuencia, dias_gracia, estatus) VALUES (1, 'Mensualidad', 30.00, 'M', 0, 1)");

        // Insertamos un cargo pendiente (estatus = 1) para el atleta 1
        $this->pdo->exec("INSERT INTO cargos (codigo_cargo, codigo_concepto, codigo_atleta, monto_total, fecha_emision, estatus, codigo_moneda) 
                          VALUES (1, 1, 1, 30.00, CURDATE(), 1, 2)");

        $datosRetiro = [
            'accion'        => 'eliminar',
            'id'            => '1',
            'motivo_retiro' => 'Retiro voluntario'
        ];

        $resultado = $this->modelo->ProcesarDatos($datosRetiro);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo'], 'El sistema debe rechazar el retiro por cargos pendientes (ASSOCIATES).');

        // La inscripción debe seguir en estatus 1 (Activo)
        $stmtInsc = $this->pdo->query("SELECT estatus FROM inscripciones WHERE codigo_atleta = 1");
        $this->assertEquals(1, (int)$stmtInsc->fetchColumn());

        // No debe haber filas en la tabla retiros
        $stmtRet = $this->pdo->query("SELECT COUNT(*) FROM retiros WHERE codigo_inscripcion = 1");
        $this->assertEquals(0, (int)$stmtRet->fetchColumn());
    }

    /**
     * Escenario 3: Bloqueo de retiro con materiales pendientes.
     */
    public function testErrorRetiroConMateriales(): void
    {
        $this->sembrarAtletaActivo();

        // Insertamos una asignación activa (estatus = 1)
        $this->pdo->exec("INSERT INTO asignaciones (id_asignacion, codigo_atleta, codigo_articulo, fecha_asignacion, estatus) 
                          VALUES (1, 1, 10, CURDATE(), 1)");

        $datosRetiro = [
            'accion'        => 'eliminar',
            'id'            => '1',
            'motivo_retiro' => 'Retiro voluntario'
        ];

        $resultado = $this->modelo->ProcesarDatos($datosRetiro);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES . '1', $resultado['codigo'], 'El sistema debe rechazar el retiro por materiales pendientes (ASSOCIATES . 1).');

        // La inscripción debe seguir en estatus 1
        $stmtInsc = $this->pdo->query("SELECT estatus FROM inscripciones WHERE codigo_atleta = 1");
        $this->assertEquals(1, (int)$stmtInsc->fetchColumn());
    }

    /**
     * Escenario 4: Reinscripción exitosa.
     */
    public function testReinscripcionExitosa(): void
    {
        $this->sembrarAtletaActivo();

        // 1. Procedemos al retiro inicial
        $this->pdo->exec("UPDATE inscripciones SET estatus = 2 WHERE codigo_inscripcion = 1");
        $this->pdo->exec("INSERT INTO retiros (codigo_inscripcion, fecha_retiro, motivo) VALUES (1, '2026-03-01', 'Pausa médica')");

        // 2. Ejecutamos la reinscripción en la categoría 2 (U-17) con nueva posición y dorsal
        $datosReinsc = [
            'accion'           => 'reinscribir',
            'id'               => '1',
            'categoria'        => '2',
            'posicion'         => '2',
            'representante'    => '1',
            'dorsal'           => '14',
            'peso'             => '62.00',
            'estatura'         => '168',
            'talla_pantalon'   => 'S',
            'talla_franela'    => 'M',
            'talla_calzado'    => '39',
            'tipo_sangre'      => 'O+',
            'es_alergico'      => '0',
            'alergias_detalle' => '',
            'telefono'         => '0414-1111111',
            'correo'           => 'diego@cannibals.com',
            'direccion'        => 'Av Las Industrias'
        ];

        $resultado = $this->modelo->ProcesarDatos($datosReinsc);

        $this->assertEquals('exito', $resultado['accion']);

        // 3. Verificamos que existan exactamente 2 inscripciones para el atleta
        $stmtTotal = $this->pdo->query("SELECT COUNT(*) FROM inscripciones WHERE codigo_atleta = 1");
        $this->assertEquals(2, (int)$stmtTotal->fetchColumn(), 'Debe haber 2 filas en inscripciones para registrar el historial.');

        // 4. Verificamos que la primera inscripción mantenga estatus 2 (Retirado)
        $stmtInsc1 = $this->pdo->query("SELECT estatus, dorsal FROM inscripciones WHERE codigo_inscripcion = 1");
        $insc1 = $stmtInsc1->fetch();
        $this->assertEquals(2, (int)$insc1['estatus']);
        $this->assertEquals(10, (int)$insc1['dorsal']);

        // 5. Verificamos que la nueva inscripción tenga estatus 1 (Activo) con los nuevos datos
        $stmtInsc2 = $this->pdo->query("SELECT * FROM inscripciones WHERE codigo_atleta = 1 AND estatus = 1");
        $insc2 = $stmtInsc2->fetch();
        $this->assertNotEmpty($insc2, 'La nueva inscripción no está activa.');
        $this->assertEquals(2, (int)$insc2['codigo_categoria']);
        $this->assertEquals(2, (int)$insc2['codigo_posicion']);
        $this->assertEquals(14, (int)$insc2['dorsal']);
        $this->assertEquals(62.00, (float)$insc2['peso_kg']);
        $this->assertEquals(date('Y-m-d'), $insc2['fecha_inscripcion']);
    }

    /**
     * Escenario 5: Rechazo por dorsal duplicado en nueva categoría.
     */
    public function testErrorReinscripcionDorsalDuplicado(): void
    {
        $this->sembrarAtletaActivo();

        // 1. Atleta 1 retirado
        $this->pdo->exec("UPDATE inscripciones SET estatus = 2 WHERE codigo_inscripcion = 1");
        $this->pdo->exec("INSERT INTO retiros (codigo_inscripcion, fecha_retiro, motivo) VALUES (1, '2026-03-01', 'Retiro temporal')");

        // 2. Atleta 2 activo en Categoría 2 (U-17) con dorsal 14
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, p_apellidos, genero, fecha_nac, lugar_nacimiento, foto) 
                          VALUES (2, 'Samuel', 'Perez', 'H', '2010-02-10', 'Lara', 'default.png')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, estatus) 
                          VALUES (2, 2, 2, 1, 14, 1)");

        // 3. Atleta 1 intenta reinscribirse en Categoría 2 con el mismo dorsal 14
        $datosReinsc = [
            'accion'           => 'reinscribir',
            'id'               => '1',
            'categoria'        => '2',
            'posicion'         => '1',
            'dorsal'           => '14', // Colisión con atleta 2 en Categoría 2
            'peso'             => '60.00',
            'estatura'         => '165'
        ];

        $resultado = $this->modelo->ProcesarDatos($datosReinsc);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_DORSAL, $resultado['codigo']);

        // Verificamos que no se haya insertado una nueva inscripción para atleta 1
        $stmtConteo = $this->pdo->query("SELECT COUNT(*) FROM inscripciones WHERE codigo_atleta = 1");
        $this->assertEquals(1, (int)$stmtConteo->fetchColumn(), 'No debió crearse una nueva inscripción.');
    }
}

