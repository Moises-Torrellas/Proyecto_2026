<?php

namespace tests\Integration\Atletas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloAtletas;
use App\modelo\Conexion;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group("integ")]
class RestriccionDorsalPorCategoriaTest extends TestCase
{
    private ?PDO $pdo = null;
    private ModeloAtletas $modelo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=cannibalsbd2;charset=utf8', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $this->pdo);

        $this->modelo = new ModeloAtletas();

        // Limpieza de tablas respetando el esquema e integridad referencial de cannibalsbd2
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
        parent::tearDown();
    }

    /**
     * Caso Manual 7: Restricción de dorsal único por categoría (Registro)
     * Verifica que al intentar registrar a un segundo atleta con el dorsal 10 en U-17,
     * el sistema lo rechace retornando DUPLICATE_DORSAL.
     */
    public function testRechazarInclusionDeAtletaConDorsalDuplicadoEnMismaCategoria(): void
    {
        // 1. Configuración previa: Posición (Portero), Categoría U-17 (id 7) y Representante
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) VALUES (3, 'Portero', 'PR', '')");
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES (7, 'U-17', 15, 17)");
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, telefono, direccion, nombre, apellido, tipo_doc, correo, instagram) 
                          VALUES (1, '12944555', '0416-5533382', 'Calle 60 Entre Carrera 14A Y 14B', 'Carlos', 'Perez', 'V', 'carlos@gmail.com', '')");

        // 2. Paso 1 en la interfaz: Atleta A ya inscrito en U-17 que usa el dorsal número 10
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, s_nombre, p_apellidos, s_apellidos, genero, fecha_nac, foto, lugar_nacimiento) 
                          VALUES (1, 'Diego', 'Alexandro', 'Reinoso', 'Chirinos', 'H', '2010-05-15', 'default.png', 'Barquisimeto')");
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (1, 'V', '30111222')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus, talla_pantalon, talla_franela, talla_calzado) 
                          VALUES (1, 1, 7, 3, 10, 70, 170, '2026-01-01', 1, 'M', 'M', '40')");

        // 3. Pasos 2 y 3: Intentar registrar a otro atleta asignándole la misma categoría U-17 (7) y el mismo dorsal 10
        $datos = [
            'accion'        => 'incluir',
            'doc_identidad' => '30222333',
            'nombre'        => 'Luis David',
            'apellido'      => 'Torres Mora',
            'fecha_nac'     => '2010-08-20',
            'genero'        => 'H',
            'categoria'     => '7',
            'posicion'      => '3',
            'representante' => '1',
            'dorsal'        => '10',
            'telefono'      => '0414-2223344',
            'peso'          => '65',
            'estatura'      => '168',
            'talla_pantalon'=> 'M',
            'talla_franela' => 'M',
            'talla_calzado' => '39'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        // 4. Verificación cruzada: El modelo debe rechazar la acción con DUPLICATE_DORSAL
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_DORSAL, $resultado['codigo'], 'El sistema debe rechazar la acción por dorsal duplicado (DUPLICATE_DORSAL).');

        // Confirmar en BD que la nómina no se alteró y se mantiene un único dorsal 10 activo
        $stmtInscripciones = $this->pdo->query("SELECT COUNT(*) FROM inscripciones WHERE codigo_categoria = 7 AND dorsal = 10 AND estatus = 1");
        $this->assertEquals(1, (int)$stmtInscripciones->fetchColumn(), 'Solo debe haber un atleta con dorsal 10 activo en U-17.');
    }

    /**
     * Caso Manual 7: Restricción de dorsal único por categoría (Modificación)
     * Verifica que al editar un atleta existente en U-17 e intentar asignarle
     * el dorsal 10 ya ocupado por otro compañero, se rechace la transacción.
     */
    public function testRechazarModificacionDeAtletaConDorsalDuplicadoEnMismaCategoria(): void
    {
        // 1. Configuración previa: Posición, Categoría U-17 y Representante
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) VALUES (3, 'Portero', 'PR', '')");
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES (7, 'U-17', 15, 17)");
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, telefono, direccion, nombre, apellido, tipo_doc, correo, instagram) 
                          VALUES (1, '12944555', '0416-5533382', 'Calle 60 Entre Carrera 14A Y 14B', 'Carlos', 'Perez', 'V', 'carlos@gmail.com', '')");

        // Atleta A con dorsal 10 en categoría U-17
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, s_nombre, p_apellidos, s_apellidos, genero, fecha_nac, foto, lugar_nacimiento) 
                          VALUES (1, 'Diego', 'Alexandro', 'Reinoso', 'Chirinos', 'H', '2010-05-15', 'default.png', 'Barquisimeto')");
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (1, 'V', '30111222')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus, talla_pantalon, talla_franela, talla_calzado) 
                          VALUES (1, 1, 7, 3, 10, 70, 170, '2026-01-01', 1, 'M', 'M', '40')");

        // Atleta B con dorsal 7 en categoría U-17
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, s_nombre, p_apellidos, s_apellidos, genero, fecha_nac, foto, lugar_nacimiento) 
                          VALUES (2, 'Andres', 'Felipe', 'Morales', 'Ruiz', 'H', '2010-09-10', 'default.png', 'Barquisimeto')");
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (2, 'V', '30444555')");
        $this->pdo->exec("INSERT INTO contacto_atleta (codigo_atleta, direccion, telefono, correo, municipio, instagram) 
                          VALUES (2, 'Carrera 19', '0414-5556677', 'andres@gmail.com', 'Iribarren', '')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus, talla_pantalon, talla_franela, talla_calzado) 
                          VALUES (2, 2, 7, 3, 7, 65, 168, '2026-01-01', 1, 'M', 'M', '39')");

        // Intentar modificar Atleta B para arrebatarle el dorsal 10 al Atleta A
        $datosModificar = [
            'accion'        => 'modificar',
            'id'            => '2',
            'doc_identidad' => '30444555',
            'nombre'        => 'Andres Felipe',
            'apellido'      => 'Morales Ruiz',
            'fecha_nac'     => '2010-09-10',
            'genero'        => 'H',
            'categoria'     => '7',
            'posicion'      => '3',
            'representante' => '1',
            'dorsal'        => '10', // Conflicto
            'telefono'      => '0414-5556677',
            'peso'          => '65',
            'estatura'      => '168',
            'talla_pantalon'=> 'M',
            'talla_franela' => 'M',
            'talla_calzado' => '39'
        ];

        $resultado = $this->modelo->ProcesarDatos($datosModificar);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_DORSAL, $resultado['codigo'], 'Debe rechazar la edición con DUPLICATE_DORSAL si el número ya está ocupado.');
    }

    /**
     * Caso de Control: Permite el mismo dorsal si pertenecen a categorías diferentes.
     */
    public function testPermitirMismoDorsalEnCategoriasDiferentes(): void
    {
        // 1. Configuración previa: U-17 (id 7) y U-15 (id 6)
        $this->pdo->exec("INSERT INTO posiciones (codigo_posicion, nombre, abreviatura, descripcion) VALUES (3, 'Portero', 'PR', '')");
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES (7, 'U-17', 15, 17)");
        $this->pdo->exec("INSERT INTO categorias (codigo_categoria, nombre, edad_min, edad_max) VALUES (6, 'U-14', 13, 14)");
        $this->pdo->exec("INSERT INTO representantes (codigo_representante, cedula, telefono, direccion, nombre, apellido, tipo_doc, correo, instagram) 
                          VALUES (1, '12944555', '0416-5533382', 'Calle 60 Entre Carrera 14A Y 14B', 'Carlos', 'Perez', 'V', 'carlos@gmail.com', '')");

        // Atleta A con dorsal 10 en U-17
        $this->pdo->exec("INSERT INTO atletas (codigo_atleta, p_nombre, s_nombre, p_apellidos, s_apellidos, genero, fecha_nac, foto, lugar_nacimiento) 
                          VALUES (1, 'Diego', 'Alexandro', 'Reinoso', 'Chirinos', 'H', '2010-05-15', 'default.png', 'Barquisimeto')");
        $this->pdo->exec("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (1, 'V', '30111222')");
        $this->pdo->exec("INSERT INTO inscripciones (codigo_inscripcion, codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus, talla_pantalon, talla_franela, talla_calzado) 
                          VALUES (1, 1, 7, 3, 10, 70, 170, '2026-01-01', 1, 'M', 'M', '40')");

        // Atleta C se registra con dorsal 10 pero en U-14 (id 6)
        $datos = [
            'accion'        => 'incluir',
            'doc_identidad' => '30333444',
            'nombre'        => 'Pedro Jose',
            'apellido'      => 'Ramirez Gil',
            'fecha_nac'     => '2012-04-12',
            'genero'        => 'H',
            'categoria'     => '6',
            'posicion'      => '3',
            'representante' => '1',
            'dorsal'        => '10',
            'telefono'      => '0414-3334455',
            'peso'          => '55',
            'estatura'      => '158',
            'talla_pantalon'=> 'S',
            'talla_franela' => 'S',
            'talla_calzado' => '37'
        ];

        $resultado = $this->modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion'], 'El mismo dorsal debe permitirse si la categoría no es la misma.');
    }
}