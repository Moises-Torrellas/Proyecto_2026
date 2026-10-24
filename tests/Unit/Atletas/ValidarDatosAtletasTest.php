<?php

namespace tests\Unit\Atletas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloAtletas;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitAtletas')]
#[Group('validarAtletas')]
class ValidarDatosAtletasTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    /**
     * Crea una instancia de ModeloAtletas inyectando un PDO simulado.
     */
    private function crearModeloConPdoMock(PDO $pdoMock)
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);
    }

    /**
     * Invoca el método privado ValidarExpresiones mediante Reflection.
     */
    private function invocarValidarExpresiones(ModeloAtletas $modelo, array $datos): void
    {
        $reflector = new ReflectionClass(ModeloAtletas::class);
        $metodo = $reflector->getMethod('ValidarExpresiones');
        $metodo->invoke($modelo, $datos);
    }

    /**
     * Obtiene el valor de un atributo privada del modelo mediante Reflection.
     */
    private function obtenerAtributo(ModeloAtletas $modelo, string $nombreAtributo): mixed
    {
        $reflector = new ReflectionClass(ModeloAtletas::class);
        $propiedad = $reflector->getProperty($nombreAtributo);
        return $propiedad->getValue($modelo);
    }

    /**
     * Genera un conjunto base de datos válidos para pruebas.
     */
    private function obtenerDatosAtletaValidos(array $sobrescribir = []): array
    {
        $anioActual = (int)date('Y');
        $fechaNac = ($anioActual - 15) . '-05-20'; // Atleta de 15 años (menor de edad)

        $datos = [
            'accion'            => 'incluir',
            'id'                => '1',
            'doc_identidad'     => '12345678',
            'nombre'            => 'Carlos Alberto',
            'apellido'          => 'Perez Gomez',
            'telefono'          => '0414-1234567',
            'direccion'         => 'Av. Venezuela con Calle 10',
            'representante'     => '1',
            'posicion'          => '1',
            'genero'            => 'M',
            'categoria'         => '1',
            'fecha_nac'         => $fechaNac,
            'estatus'           => '1',
            'edad'              => '15',
            'foto_actual'       => 'default.png',
            'dorsal'            => '10',
            'peso'              => '62.5',
            'estatura'          => '170',
            'lugar_nacimiento'  => 'Barquisimeto',
            'correo'            => 'atleta@correo.com',
            'municipio'         => 'Iribarren',
            'instagram'         => '@atleta_2026',
            'talla_pantalon'    => 'M',
            'talla_franela'     => 'M',
            'talla_calzado'     => '41',
            'tipo_sangre'       => 'O+',
            'es_alergico'       => '0',
            'alergias_detalle'  => 'Ninguna'
        ];

        return array_merge($datos, $sobrescribir);
    }

    /* =========================================================================
     * SECCIÓN 1: PRUEBAS PARA EL MÉTODO ProcesarDatos
     * ========================================================================= */

    // Caso 1: Datos vacíos
    public function testProcesarDatosVacioLanzaExcepcion(): void
    {
        $modelo = new ModeloAtletas();
        $datosVacios = [];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar el registro del atleta.');

        $modelo->ProcesarDatos($datosVacios);
    }

    // Caso 2: Acción inválida o no soportada
    public function testProcesarDatosAccionInvalidaLanzaExcepcion(): void
    {
        $modelo = new ModeloAtletas();
        $datosInvalidos = [
            'accion' => 'accion_no_valida',
            'nombre' => 'Carlos'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada para el atleta no es válida.');

        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 3: Asignación y normalización de propiedades en ProcesarDatos
    public function testProcesarDatosAsignacionYNormalizacionPropiedades(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPdoMock($pdoMock);
        $modelo = new ModeloAtletas();

        $datos = [
            'accion'            => 'generar',
            'id'                => '25',
            'nombre'            => '  carlos alberto  ',
            'apellido'          => '  perez martinez  ',
            'municipio'         => '  palavecino  ',
            'lugar_nacimiento'  => '  barquisimeto  ',
            'direccion'         => '  urb las mercedes calle 4  ',
            'correo'            => 'atleta@correo.com',
            'telefono'          => '0414-7654321',
            'doc_identidad'     => '12345678',
            'dorsal'            => '',
            'peso'              => '',
            'estatura'          => '',
            'foto'              => ['atleta_2026-10-22_123456.jpg'],
            'es_alergico'       => '1',
            'alergias_detalle'  => '  polen y polvo  '
        ];

        $modelo->ProcesarDatos($datos);

        $this->assertEquals('Carlos Alberto', $this->obtenerAtributo($modelo, 'nombre'));
        $this->assertEquals('Perez Martinez', $this->obtenerAtributo($modelo, 'apellido'));
        $this->assertEquals('Palavecino', $this->obtenerAtributo($modelo, 'municipio'));
        $this->assertEquals('Barquisimeto', $this->obtenerAtributo($modelo, 'lugar_nacimiento'));
        $this->assertEquals('Urb Las Mercedes Calle 4', $this->obtenerAtributo($modelo, 'direccion'));
        $this->assertEquals('atleta@correo.com', $this->obtenerAtributo($modelo, 'correo'));
        $this->assertEquals('0414-7654321', $this->obtenerAtributo($modelo, 'telefono'));
        $this->assertEquals('12345678', $this->obtenerAtributo($modelo, 'doc_identidad'));
        $this->assertEquals(0, $this->obtenerAtributo($modelo, 'dorsal'));
        $this->assertEquals(0.0, $this->obtenerAtributo($modelo, 'peso_kg'));
        $this->assertEquals(0, $this->obtenerAtributo($modelo, 'estatura_cm'));
        $this->assertEquals('Retiro Voluntario', $this->obtenerAtributo($modelo, 'motivo_retiro'));
        $this->assertEquals('atleta_2026-10-22_123456.jpg', $this->obtenerAtributo($modelo, 'foto'));
        $this->assertEquals(1, $this->obtenerAtributo($modelo, 'es_alergico'));
        $this->assertEquals('polen y polvo', $this->obtenerAtributo($modelo, 'alergias_detalle'));
    }

    // Caso 4: Foto por defecto cuando no se suministra
    public function testProcesarDatosFotoPorDefectoCuandoNoSeEnvia(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPdoMock($pdoMock);
        $modelo = new ModeloAtletas();
        $datos = [
            'accion' => 'generar',
            'nombre' => 'Luis'
        ];

        $modelo->ProcesarDatos($datos);

        $this->assertEquals('default.png', $this->obtenerAtributo($modelo, 'foto'));
    }

    // Caso 5: Foto como string simple (no array)
    public function testProcesarDatosFotoComoString(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPdoMock($pdoMock);
        $modelo = new ModeloAtletas();
        $datos = [
            'accion' => 'generar',
            'foto'   => 'atleta_2026-10-22_9999.png'
        ];

        $modelo->ProcesarDatos($datos);

        $this->assertEquals('atleta_2026-10-22_9999.png', $this->obtenerAtributo($modelo, 'foto'));
    }

    // Caso 6: ProcesarDatos enruta la acción 'generar' hacia Consultar()
    public function testProcesarDatosEjecutaAccionGenerar(): void
    {
        $datosFilaSimulada = [
            ['id_atleta' => 1, 'nombres' => 'Carlos', 'apellidos' => 'Perez']
        ];

        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($datosFilaSimulada);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPdoMock($pdoMock);
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos(['accion' => 'generar']);

        $this->assertIsArray($resultado);
        $this->assertEquals([
            'accion' => 'consultar',
            'datos'  => $datosFilaSimulada
        ], $resultado);
    }

    // Caso 7: ProcesarDatos enruta la acción 'buscar' hacia Buscar()
    public function testProcesarDatosEjecutaAccionBuscar(): void
    {
        $datosFilaSimulada = [
            ['id_atleta' => 1, 'nombres' => 'Carlos', 'apellidos' => 'Perez']
        ];

        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($datosFilaSimulada);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPdoMock($pdoMock);
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos(['accion' => 'buscar', 'id' => '1']);

        $this->assertIsArray($resultado);
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEquals($datosFilaSimulada, $resultado['datos']);
    }

    /* =========================================================================
     * SECCIÓN 2: PRUEBAS PARA EL MÉTODO ValidarExpresiones (Validaciones de Campos)
     * ========================================================================= */

    // Id inválido
    public function testValidarIdInvalido(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        $this->invocarValidarExpresiones($modelo, ['id' => 'abc']);
    }

    // Id válido
    public function testValidarIdValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['id' => '12345']);
        $this->assertTrue(true);
    }

    // Fecha nacimiento con formato inválido
    public function testValidarFechaNacInvalidaFormato(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Formato de fecha inválido. Use AAAA-MM-DD.');

        $this->invocarValidarExpresiones($modelo, ['fecha_nac' => '15/05/2010']);
    }

    // Fecha nacimiento con formato válido
    public function testValidarFechaNacValida(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['fecha_nac' => '2010-05-15']);
        $this->assertTrue(true);
    }

    // Nombre inválido (longitud menor a 3 caracteres en acción estándar)
    public function testValidarNombreInvalidoMenorATresCaracteres(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombres inválido.');

        $this->invocarValidarExpresiones($modelo, ['nombre' => 'Jo']);
    }

    // Nombre inválido (contiene números o caracteres especiales)
    public function testValidarNombreInvalidoConNumeros(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombres inválido.');

        $this->invocarValidarExpresiones($modelo, ['nombre' => 'Juan123']);
    }

    // Nombre válido en acción 'generar' (permite desde 1 carácter para filtros)
    public function testValidarNombreValidoAccionGenerar(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['accion' => 'generar', 'nombre' => 'J']);
        $this->assertTrue(true);
    }

    // Nombre inválido en acción 'generar' con caracteres no permitidos
    public function testValidarNombreInvalidoAccionGenerar(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombres inválido.');

        $this->invocarValidarExpresiones($modelo, ['accion' => 'generar', 'nombre' => 'J1']);
    }

    // Apellido inválido (menor a 3 caracteres en acción estándar)
    public function testValidarApellidoInvalidoMenorATresCaracteres(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Apellidos inválido.');

        $this->invocarValidarExpresiones($modelo, ['apellido' => 'Pe']);
    }

    // Apellido inválido (contiene números)
    public function testValidarApellidoInvalidoConNumeros(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Apellidos inválido.');

        $this->invocarValidarExpresiones($modelo, ['apellido' => 'Perez9']);
    }

    // Apellido válido en acción 'generar' (permite 1 carácter)
    public function testValidarApellidoValidoAccionGenerar(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['accion' => 'generar', 'apellido' => 'P']);
        $this->assertTrue(true);
    }

    // Categoría inválida (no numérica)
    public function testValidarCategoriaInvalida(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Categoria o categoria inválida.');

        $this->invocarValidarExpresiones($modelo, ['categoria' => 'sub15']);
    }

    // Categoría válida
    public function testValidarCategoriaValida(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['categoria' => '5']);
        $this->assertTrue(true);
    }

    // Posición inválida (no numérica)
    public function testValidarPosicionInvalida(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Posición o posicion inválida.');

        $this->invocarValidarExpresiones($modelo, ['posicion' => 'delantero']);
    }

    // Posición válida
    public function testValidarPosicionValida(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['posicion' => '2']);
        $this->assertTrue(true);
    }

    // Género inválido (diferente de H o M)
    public function testValidarGeneroInvalido(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Genero o genero inválido.');

        $this->invocarValidarExpresiones($modelo, ['genero' => 'X']);
    }

    // Género válido (H y M)
    public function testValidarGeneroValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['genero' => 'H']);
        $this->invocarValidarExpresiones($modelo, ['genero' => 'M']);
        $this->assertTrue(true);
    }

    // Representante inválido (no numérico)
    public function testValidarRepresentanteInvalido(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Representante inválido.');

        $this->invocarValidarExpresiones($modelo, ['representante' => 'abc']);
    }

    // Representante válido (dígitos numéricos permitidos)
    public function testValidarRepresentanteValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['representante' => '12']);
        $this->assertTrue(true);
    }

    // Documento de identidad inválido general (menos de 7 dígitos)
    public function testValidarDocIdentidadInvalidoMenorSieteDigitos(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cedula inválida. Debe contener de 7 a 8 dígitos.');

        $this->invocarValidarExpresiones($modelo, ['doc_identidad' => '123456']);
    }

    // Documento de identidad inválido general (más de 8 dígitos)
    public function testValidarDocIdentidadInvalidoMayorOchoDigitos(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cedula inválida. Debe contener de 7 a 8 dígitos.');

        $this->invocarValidarExpresiones($modelo, ['doc_identidad' => '123456789']);
    }

    // Documento de identidad inválido general (contiene letras)
    public function testValidarDocIdentidadInvalidoConLetras(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cedula inválida. Debe contener de 7 a 8 dígitos.');

        $this->invocarValidarExpresiones($modelo, ['doc_identidad' => 'V1234567']);
    }

    // Documento de identidad válido general (7 y 8 dígitos)
    public function testValidarDocIdentidadValidoSieteYOchoDigitos(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['doc_identidad' => '1234567']);
        $this->invocarValidarExpresiones($modelo, ['doc_identidad' => '12345678']);
        $this->assertTrue(true);
    }

    // Documento de identidad inválido en acción 'generar' (más de 8 dígitos o no numérico)
    public function testValidarDocIdentidadInvalidoAccionGenerar(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('documento de identidad inválido.');

        $this->invocarValidarExpresiones($modelo, [
            'accion'        => 'generar',
            'doc_identidad' => '123456789'
        ]);
    }

    // Documento de identidad válido en acción 'generar' (acepta de 1 a 8 dígitos)
    public function testValidarDocIdentidadValidoAccionGenerar(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, [
            'accion'        => 'generar',
            'doc_identidad' => '123'
        ]);
        $this->assertTrue(true);
    }

    // Teléfono inválido (sin guión)
    public function testValidarTelefonoInvalidoSinGuion(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Telefono invalido.');

        $this->invocarValidarExpresiones($modelo, ['telefono' => '04141234567']);
    }

    // Teléfono inválido (longitud errónea)
    public function testValidarTelefonoInvalidoLongitud(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Telefono invalido.');

        $this->invocarValidarExpresiones($modelo, ['telefono' => '0414-123456']);
    }

    // Teléfono válido (formato 0000-0000000)
    public function testValidarTelefonoValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['telefono' => '0414-1234567']);
        $this->assertTrue(true);
    }

    // Dirección inválida (menos de 5 caracteres)
    public function testValidarDireccionInvalidaCorta(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Direccion inválida.');

        $this->invocarValidarExpresiones($modelo, ['direccion' => 'Casa']);
    }

    // Dirección válida
    public function testValidarDireccionValida(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['direccion' => 'Av. Los Leones con Av. Lara']);
        $this->assertTrue(true);
    }

    // Foto actual inválida (no es default.png ni cumple el patrón regex)
    public function testValidarFotoActualInvalida(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('El nombre de la foto tiene un formato inválido o una extensión no permitida.');

        $this->invocarValidarExpresiones($modelo, ['foto_actual' => 'foto_perfil.png']);
    }

    // Foto actual válida ('default.png')
    public function testValidarFotoActualValidaDefault(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['foto_actual' => 'default.png']);
        $this->assertTrue(true);
    }

    // Foto actual válida con patrón reglamentario
    public function testValidarFotoActualValidaPatron(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['foto_actual' => 'atleta_2026-10-22_123456789.png']);
        $this->assertTrue(true);
    }

    // Edad inválida (más de 2 dígitos o no numérica)
    public function testValidarEdadInvalida(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Edad inválida.');

        $this->invocarValidarExpresiones($modelo, ['edad' => '100']);
    }

    // Edad válida
    public function testValidarEdadValida(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['edad' => '15']);
        $this->assertTrue(true);
    }

    // Estatus inválido (diferente de 1 o 2)
    public function testValidarEstatusInvalido(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('estatus inválido.');

        $this->invocarValidarExpresiones($modelo, ['estatus' => '3']);
    }

    // Estatus válido (1 y 2)
    public function testValidarEstatusValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['estatus' => '1']);
        $this->invocarValidarExpresiones($modelo, ['estatus' => '2']);
        $this->assertTrue(true);
    }

    // Dorsal inválido (más de 3 dígitos o letras)
    public function testValidarDorsalInvalido(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Dorsal inválido.');

        $this->invocarValidarExpresiones($modelo, ['dorsal' => '1000']);
    }

    // Dorsal válido (de 1 a 3 dígitos)
    public function testValidarDorsalValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['dorsal' => '99']);
        $this->assertTrue(true);
    }

    // Peso inválido (con más de 2 decimales)
    public function testValidarPesoInvalido(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Peso inválido.');

        $this->invocarValidarExpresiones($modelo, ['peso' => '75.555']);
    }

    // Peso válido (entero o con hasta 2 decimales)
    public function testValidarPesoValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['peso' => '68']);
        $this->invocarValidarExpresiones($modelo, ['peso' => '68.50']);
        $this->assertTrue(true);
    }

    // Estatura inválida (menos de 2 o más de 3 dígitos)
    public function testValidarEstaturaInvalida(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Estatura inválida.');

        $this->invocarValidarExpresiones($modelo, ['estatura' => '5']);
    }

    // Estatura válida
    public function testValidarEstaturaValida(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['estatura' => '175']);
        $this->assertTrue(true);
    }

    // Lugar de nacimiento inválido (menos de 3 o más de 100 caracteres)
    public function testValidarLugarNacimientoInvalido(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Lugar de nacimiento inválido.');

        $this->invocarValidarExpresiones($modelo, ['lugar_nacimiento' => 'Ab']);
    }

    // Lugar de nacimiento válido
    public function testValidarLugarNacimientoValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['lugar_nacimiento' => 'Barquisimeto']);
        $this->assertTrue(true);
    }

    // Correo inválido
    public function testValidarCorreoInvalido(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Correo inválido.');

        $this->invocarValidarExpresiones($modelo, ['correo' => 'correo_invalido.com']);
    }

    // Correo válido
    public function testValidarCorreoValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['correo' => 'atleta.contacto@gmail.com']);
        $this->assertTrue(true);
    }

    // Instagram inválido
    public function testValidarInstagramInvalido(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Instagram inválido.');

        $this->invocarValidarExpresiones($modelo, ['instagram' => '@usuario con espacios']);
    }

    // Instagram válido
    public function testValidarInstagramValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['instagram' => '@atleta_oficial']);
        $this->assertTrue(true);
    }

    // Municipio inválido (menos de 3 letras o con números)
    public function testValidarMunicipioInvalido(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Municipio inválido.');

        $this->invocarValidarExpresiones($modelo, ['municipio' => 'Municipio 1']);
    }

    // Municipio válido
    public function testValidarMunicipioValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['municipio' => 'Palavecino']);
        $this->assertTrue(true);
    }

    // Talla de pantalón inválida
    public function testValidarTallaPantalonInvalida(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Talla de pantalón inválida.');

        $this->invocarValidarExpresiones($modelo, ['talla_pantalon' => '34']);
    }

    // Talla de pantalón válida
    public function testValidarTallaPantalonValida(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['talla_pantalon' => 'M']);
        $this->assertTrue(true);
    }

    // Talla de franela inválida
    public function testValidarTallaFranelaInvalida(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Talla de franela inválida.');

        $this->invocarValidarExpresiones($modelo, ['talla_franela' => '14']);
    }

    // Talla de franela válida
    public function testValidarTallaFranelaValida(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['talla_franela' => 'XL']);
        $this->assertTrue(true);
    }

    // Talla de calzado inválida (menor a 10 o mayor a 50)
    public function testValidarTallaCalzadoInvalida(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Talla de calzado inválida. Solo números del 10 al 50.');

        $this->invocarValidarExpresiones($modelo, ['talla_calzado' => '9']);
    }

    // Talla de calzado válida
    public function testValidarTallaCalzadoValida(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['talla_calzado' => '42']);
        $this->assertTrue(true);
    }

    // Tipo de sangre inválido
    public function testValidarTipoSangreInvalido(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tipo de sangre inválido.');

        $this->invocarValidarExpresiones($modelo, ['tipo_sangre' => 'C+']);
    }

    // Tipo de sangre válido
    public function testValidarTipoSangreValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['tipo_sangre' => 'AB+']);
        $this->assertTrue(true);
    }

    // Dato es_alergico inválido (diferente de '0' o '1')
    public function testValidarEsAlergicoInvalido(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Dato es_alergico inválido.');

        $this->invocarValidarExpresiones($modelo, ['es_alergico' => '2']);
    }

    // Dato es_alergico válido
    public function testValidarEsAlergicoValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['es_alergico' => '1']);
        $this->invocarValidarExpresiones($modelo, ['es_alergico' => '0']);
        $this->assertTrue(true);
    }

    // Detalle de alergias demasiado largo (más de 255 caracteres)
    public function testValidarAlergiasDetalleDemasiadoLargo(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Detalle de alergias demasiado largo.');

        $this->invocarValidarExpresiones($modelo, ['alergias_detalle' => str_repeat('a', 256)]);
    }

    // Detalle de alergias válido
    public function testValidarAlergiasDetalleValido(): void
    {
        $modelo = new ModeloAtletas();
        $this->invocarValidarExpresiones($modelo, ['alergias_detalle' => 'Alergia al polen y penicilina']);
        $this->assertTrue(true);
    }

    /* =========================================================================
     * SECCIÓN 3: REGLAS DE NEGOCIO EN ValidarExpresiones (Edad, Cédula, Representante)
     * ========================================================================= */

    // Atleta menor de edad sin representante en acción 'incluir'
    public function testMenorDeEdadSinRepresentanteEnAccionIncluir(): void
    {
        $modelo = new ModeloAtletas();
        $anioActual = (int)date('Y');
        $fechaMenor = ($anioActual - 12) . '-01-01'; // 12 años

        $datos = [
            'accion'        => 'incluir',
            'fecha_nac'     => $fechaMenor,
            'representante' => ''
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('El atleta es menor de edad necesita asociar un representante.');

        $this->invocarValidarExpresiones($modelo, $datos);
    }

    // Atleta menor de edad con representante '0' en acción 'modificar'
    public function testMenorDeEdadConRepresentanteCeroEnAccionModificar(): void
    {
        $modelo = new ModeloAtletas();
        $anioActual = (int)date('Y');
        $fechaMenor = ($anioActual - 14) . '-01-01'; // 14 años

        $datos = [
            'accion'        => 'modificar',
            'fecha_nac'     => $fechaMenor,
            'representante' => '0'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('El atleta es menor de edad necesita asociar un representante.');

        $this->invocarValidarExpresiones($modelo, $datos);
    }

    // Atleta menor de edad con representante '0' en acción 'reinscribir'
    public function testMenorDeEdadConRepresentanteCeroEnAccionReinscribir(): void
    {
        $modelo = new ModeloAtletas();
        $anioActual = (int)date('Y');
        $fechaMenor = ($anioActual - 14) . '-01-01'; // 14 años

        $datos = [
            'accion'        => 'reinscribir',
            'fecha_nac'     => $fechaMenor,
            'representante' => '0'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('El atleta es menor de edad necesita asociar un representante.');

        $this->invocarValidarExpresiones($modelo, $datos);
    }

    // Atleta mayor de 9 años sin cédula en acción 'incluir'
    public function testMayorDeNueveAniosSinDocIdentidadEnAccionIncluir(): void
    {
        $modelo = new ModeloAtletas();
        $anioActual = (int)date('Y');
        $fechaMayorNueve = ($anioActual - 11) . '-01-01'; // 11 años

        $datos = [
            'accion'        => 'incluir',
            'fecha_nac'     => $fechaMayorNueve,
            'representante' => '1',
            'doc_identidad' => ''
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Necesita ingresar el documento de identidad del atleta.');

        $this->invocarValidarExpresiones($modelo, $datos);
    }

    // Atleta menor de 9 años sin cédula está permitido
    public function testMenorDeNueveAniosSinDocIdentidadPermitido(): void
    {
        $modelo = new ModeloAtletas();
        $anioActual = (int)date('Y');
        $fechaMenorNueve = ($anioActual - 6) . '-01-01'; // 6 años

        $datos = [
            'accion'        => 'incluir',
            'fecha_nac'     => $fechaMenorNueve,
            'representante' => '1',
            'doc_identidad' => ''
        ];

        $this->invocarValidarExpresiones($modelo, $datos);
        $this->assertTrue(true);
    }

    // Atleta mayor de edad (>= 18 años) no requiere representante
    public function testMayorDeEdadNoRequiereRepresentante(): void
    {
        $modelo = new ModeloAtletas();
        $anioActual = (int)date('Y');
        $fechaAdulto = ($anioActual - 20) . '-01-01'; // 20 años

        $datos = [
            'accion'        => 'incluir',
            'fecha_nac'     => $fechaAdulto,
            'doc_identidad' => '12345678',
            'representante' => ''
        ];

        $this->invocarValidarExpresiones($modelo, $datos);
        $this->assertTrue(true);
    }

    // Conjunto completo de datos válidos pasa la validación sin excepciones
    public function testDatosCompletosValidosPasanValidacion(): void
    {
        $modelo = new ModeloAtletas();
        $datos = $this->obtenerDatosAtletaValidos();

        $this->invocarValidarExpresiones($modelo, $datos);
        $this->assertTrue(true);
    }
}
