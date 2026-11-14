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

    private function crearModeloConPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);
    }

    private function invocarValidarExpresiones(ModeloAtletas $modelo, array $datos): void
    {
        $reflector = new ReflectionClass(ModeloAtletas::class);
        $metodo = $reflector->getMethod('ValidarExpresiones');
        $metodo->invoke($modelo, $datos);
    }

    private function obtenerAtributo(ModeloAtletas $modelo, string $nombreAtributo): mixed
    {
        $reflector = new ReflectionClass(ModeloAtletas::class);
        $propiedad = $reflector->getProperty($nombreAtributo);
        return $propiedad->getValue($modelo);
    }

    private function obtenerDatosAtletaValidos(array $sobrescribir = []): array
    {
        $anioActual = (int)date('Y');
        $fechaNac = ($anioActual - 15) . '-05-20';

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

    public function testProcesarDatosVacioLanzaExcepcion(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar el registro del atleta.');

        $modelo->ProcesarDatos([]);
    }

    public function testProcesarDatosAccionInvalidaLanzaExcepcion(): void
    {
        $modelo = new ModeloAtletas();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada para el atleta no es válida.');

        $modelo->ProcesarDatos(['accion' => 'accion_no_valida', 'nombre' => 'Carlos']);
    }

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

    public function testProcesarDatosManejoFoto(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPdoMock($pdoMock);

        // Sin foto -> default.png
        $modelo1 = new ModeloAtletas();
        $modelo1->ProcesarDatos(['accion' => 'generar', 'nombre' => 'Luis']);
        $this->assertEquals('default.png', $this->obtenerAtributo($modelo1, 'foto'));

        // Con foto como string
        $modelo2 = new ModeloAtletas();
        $modelo2->ProcesarDatos(['accion' => 'generar', 'foto' => 'atleta_2026-10-22_9999.png']);
        $this->assertEquals('atleta_2026-10-22_9999.png', $this->obtenerAtributo($modelo2, 'foto'));
    }

    public function testProcesarDatosAccionesDirectas(): void
    {
        $datosFilaSimulada = [['id_atleta' => 1, 'nombres' => 'Carlos', 'apellidos' => 'Perez']];

        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($datosFilaSimulada);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPdoMock($pdoMock);

        // Acción generar
        $modelo1 = new ModeloAtletas();
        $resGenerar = $modelo1->ProcesarDatos(['accion' => 'generar']);
        $this->assertEquals(['accion' => 'consultar', 'datos' => $datosFilaSimulada], $resGenerar);

        // Acción buscar
        $modelo2 = new ModeloAtletas();
        $resBuscar = $modelo2->ProcesarDatos(['accion' => 'buscar', 'id' => '1']);
        $this->assertEquals('buscar', $resBuscar['accion']);
        $this->assertEquals($datosFilaSimulada, $resBuscar['datos']);
    }

    /* =========================================================================
     * SECCIÓN 2: VALIDACIONES DE CAMPOS (Evaluación Consolidada de Reglas)
     * ========================================================================= */

    public function testValidarCamposInvalidosLanzaExcepcion(): void
    {
        $modelo = new ModeloAtletas();
        $casos = self::obtenerCasosInvalidos();

        foreach ($casos as $nombre => [$datos, $mensajeEsperado]) {
            try {
                $this->invocarValidarExpresiones($modelo, $datos);
                $this->fail("Se esperaba excepción en caso [$nombre] y no se lanzó.");
            } catch (Exception $e) {
                $this->assertEquals($mensajeEsperado, $e->getMessage(), "Mensaje incorrecto en caso [$nombre]");
            }
        }
    }

    public function testValidarCamposValidosNoLanzanExcepcion(): void
    {
        $modelo = new ModeloAtletas();
        $casos = self::obtenerCasosValidos();

        foreach ($casos as $nombre => [$datos]) {
            $this->invocarValidarExpresiones($modelo, $datos);
            $this->addToAssertionCount(1);
        }
    }

    private static function obtenerCasosInvalidos(): array
    {
        return [
            'id no numerico'                      => [['id' => 'abc'], 'Id inválido.'],
            'fecha nac formato invalido'          => [['fecha_nac' => '15/05/2010'], 'Formato de fecha inválido. Use AAAA-MM-DD.'],
            'nombre menor a 3 caracteres'         => [['nombre' => 'Jo'], 'Nombres inválido.'],
            'nombre con numeros'                  => [['nombre' => 'Juan123'], 'Nombres inválido.'],
            'nombre invalido en accion generar'   => [['accion' => 'generar', 'nombre' => 'J1'], 'Nombres inválido.'],
            'apellido menor a 3 caracteres'       => [['apellido' => 'Pe'], 'Apellidos inválido.'],
            'apellido con numeros'                => [['apellido' => 'Perez9'], 'Apellidos inválido.'],
            'categoria no numerica'               => [['categoria' => 'sub15'], 'Categoria o categoria inválida.'],
            'posicion no numerica'                => [['posicion' => 'delantero'], 'Posición o posicion inválida.'],
            'genero invalido'                     => [['genero' => 'X'], 'Genero o genero inválido.'],
            'representante no numerico'           => [['representante' => 'abc'], 'Representante inválido.'],
            'doc_identidad menor a 7 digitos'     => [['doc_identidad' => '123456'], 'Cedula inválida. Debe contener de 7 a 8 dígitos.'],
            'doc_identidad mayor a 8 digitos'     => [['doc_identidad' => '123456789'], 'Cedula inválida. Debe contener de 7 a 8 dígitos.'],
            'doc_identidad con letras'            => [['doc_identidad' => 'V1234567'], 'Cedula inválida. Debe contener de 7 a 8 dígitos.'],
            'doc_identidad invalido en generar'   => [['accion' => 'generar', 'doc_identidad' => '123456789'], 'documento de identidad inválido.'],
            'telefono sin guion'                  => [['telefono' => '04141234567'], 'Telefono invalido.'],
            'telefono longitud erronea'           => [['telefono' => '0414-123456'], 'Telefono invalido.'],
            'direccion menor a 5 caracteres'      => [['direccion' => 'Casa'], 'Direccion inválida.'],
            'foto actual formato invalido'        => [['foto_actual' => 'foto_perfil.png'], 'El nombre de la foto tiene un formato inválido o una extensión no permitida.'],
            'edad mayor a 2 digitos'              => [['edad' => '100'], 'Edad inválida.'],
            'estatus fuera de rango (1-2)'        => [['estatus' => '3'], 'estatus inválido.'],
            'dorsal mayor a 3 digitos'            => [['dorsal' => '1000'], 'Dorsal inválido.'],
            'peso con mas de 2 decimales'         => [['peso' => '75.555'], 'Peso inválido.'],
            'estatura fuera de rango'             => [['estatura' => '5'], 'Estatura inválida.'],
            'lugar nacimiento menor a 3 car.'     => [['lugar_nacimiento' => 'Ab'], 'Lugar de nacimiento inválido.'],
            'correo sin formato valido'           => [['correo' => 'correo_invalido.com'], 'Correo inválido.'],
            'instagram con espacios'              => [['instagram' => '@usuario con espacios'], 'Instagram inválido.'],
            'municipio con numeros'               => [['municipio' => 'Municipio 1'], 'Municipio inválido.'],
            'talla pantalon invalida'             => [['talla_pantalon' => '34'], 'Talla de pantalón inválida.'],
            'talla franela invalida'              => [['talla_franela' => '14'], 'Talla de franela inválida.'],
            'talla calzado menor a 10'            => [['talla_calzado' => '9'], 'Talla de calzado inválida. Solo números del 10 al 50.'],
            'tipo sangre no reconocido'           => [['tipo_sangre' => 'C+'], 'Tipo de sangre inválido.'],
            'es_alergico fuera de 0 y 1'          => [['es_alergico' => '2'], 'Dato es_alergico inválido.'],
            'alergias detalle mayor a 255 car.'   => [['alergias_detalle' => str_repeat('a', 256)], 'Detalle de alergias demasiado largo.']
        ];
    }

    private static function obtenerCasosValidos(): array
    {
        return [
            'id valido'                           => [['id' => '12345']],
            'fecha nac valida'                    => [['fecha_nac' => '2010-05-15']],
            'nombre valido en accion generar'     => [['accion' => 'generar', 'nombre' => 'J']],
            'apellido valido en accion generar'   => [['accion' => 'generar', 'apellido' => 'P']],
            'categoria valida'                    => [['categoria' => '5']],
            'posicion valida'                     => [['posicion' => '2']],
            'genero H'                            => [['genero' => 'H']],
            'genero M'                            => [['genero' => 'M']],
            'representante valido'                => [['representante' => '12']],
            'doc_identidad 7 digitos'             => [['doc_identidad' => '1234567']],
            'doc_identidad 8 digitos'             => [['doc_identidad' => '12345678']],
            'doc_identidad en generar'            => [['accion' => 'generar', 'doc_identidad' => '123']],
            'telefono con formato'                => [['telefono' => '0414-1234567']],
            'direccion valida'                    => [['direccion' => 'Av. Los Leones con Av. Lara']],
            'foto default'                        => [['foto_actual' => 'default.png']],
            'foto con patron oficial'             => [['foto_actual' => 'atleta_2026-10-22_123456789.png']],
            'edad valida'                         => [['edad' => '15']],
            'estatus 1 activo'                    => [['estatus' => '1']],
            'estatus 2 inactivo'                  => [['estatus' => '2']],
            'dorsal valido'                       => [['dorsal' => '99']],
            'peso entero'                         => [['peso' => '68']],
            'peso decimal'                        => [['peso' => '68.50']],
            'estatura valida'                     => [['estatura' => '175']],
            'lugar nacimiento valido'             => [['lugar_nacimiento' => 'Barquisimeto']],
            'correo electronico valido'           => [['correo' => 'atleta.contacto@gmail.com']],
            'instagram valido'                    => [['instagram' => '@atleta_oficial']],
            'municipio valido'                    => [['municipio' => 'Palavecino']],
            'talla pantalon valida'               => [['talla_pantalon' => 'M']],
            'talla franela valida'                => [['talla_franela' => 'XL']],
            'talla calzado valida'                => [['talla_calzado' => '42']],
            'tipo sangre valido'                  => [['tipo_sangre' => 'AB+']],
            'es_alergico 1'                       => [['es_alergico' => '1']],
            'es_alergico 0'                       => [['es_alergico' => '0']],
            'alergias detalle valido'             => [['alergias_detalle' => 'Alergia al polen y penicilina']]
        ];
    }

    /* =========================================================================
     * SECCIÓN 3: REGLAS DE NEGOCIO EN ValidarExpresiones (Edad, Cédula, Representante)
     * ========================================================================= */

    public function testMenorDeEdadSinRepresentanteLanzaExcepcion(): void
    {
        $modelo = new ModeloAtletas();
        $anioActual = (int)date('Y');
        $fechaMenor = ($anioActual - 12) . '-01-01';

        $casos = [
            ['accion' => 'incluir',     'representante' => ''],
            ['accion' => 'modificar',   'representante' => '0'],
            ['accion' => 'reinscribir', 'representante' => '0']
        ];

        foreach ($casos as $caso) {
            $datos = [
                'accion'        => $caso['accion'],
                'fecha_nac'     => $fechaMenor,
                'representante' => $caso['representante']
            ];

            try {
                $this->invocarValidarExpresiones($modelo, $datos);
                $this->fail("Se esperaba excepción para acción {$caso['accion']}");
            } catch (Exception $e) {
                $this->assertEquals('El atleta es menor de edad necesita asociar un representante.', $e->getMessage());
            }
        }
    }

    public function testReglaDocIdentidadSegunEdadEnAccionIncluir(): void
    {
        $modelo = new ModeloAtletas();
        $anioActual = (int)date('Y');

        // Mayor de 9 años sin cédula -> Excepción
        $fechaMayorNueve = ($anioActual - 11) . '-01-01';
        try {
            $this->invocarValidarExpresiones($modelo, [
                'accion'        => 'incluir',
                'fecha_nac'     => $fechaMayorNueve,
                'representante' => '1',
                'doc_identidad' => ''
            ]);
            $this->fail('Se esperaba excepción por falta de documento de identidad en mayor de 9 años');
        } catch (Exception $e) {
            $this->assertEquals('Necesita ingresar el documento de identidad del atleta.', $e->getMessage());
        }

        // Menor de 9 años sin cédula -> Permitido
        $fechaMenorNueve = ($anioActual - 6) . '-01-01';
        $this->invocarValidarExpresiones($modelo, [
            'accion'        => 'incluir',
            'fecha_nac'     => $fechaMenorNueve,
            'representante' => '1',
            'doc_identidad' => ''
        ]);
        $this->addToAssertionCount(1);
    }

    public function testMayorDeEdadNoRequiereRepresentante(): void
    {
        $modelo = new ModeloAtletas();
        $anioActual = (int)date('Y');
        $fechaAdulto = ($anioActual - 20) . '-01-01';

        $datos = [
            'accion'        => 'incluir',
            'fecha_nac'     => $fechaAdulto,
            'doc_identidad' => '12345678',
            'representante' => ''
        ];

        $this->invocarValidarExpresiones($modelo, $datos);
        $this->addToAssertionCount(1);
    }

    public function testDatosCompletosValidosPasanValidacion(): void
    {
        $modelo = new ModeloAtletas();
        $datos = $this->obtenerDatosAtletaValidos();

        $this->invocarValidarExpresiones($modelo, $datos);
        $this->addToAssertionCount(1);
    }
}
