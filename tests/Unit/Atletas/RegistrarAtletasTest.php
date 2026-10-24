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
#[Group('registrarAtletas')]
class RegistrarAtletasTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    /**
     * Inyecta el mock de PDO en la instancia singleton de Conexion.
     */
    private function crearModeloConPDOMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);
    }

    /**
     * Genera datos válidos para procesar la acción 'incluir'.
     */
    private function obtenerDatosAtletaValidos(array $sobrescribir = []): array
    {
        $anioActual = (int)date('Y');
        $fechaNac = ($anioActual - 15) . '-05-20'; // Menor de edad (15 años)

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
     * SECCIÓN 1: VALIDACIÓN DE ENTIDADES RELACIONADAS (EXISTENCIA EN BD)
     * ========================================================================= */

    // Caso 1: Categoría no existe en base de datos
    public function testCategoriaNoExiste(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['categoria' => '99']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 2: Posición no existe en base de datos
    public function testPosicionNoExiste(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturnOnConsecutiveCalls(1, 0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['posicion' => '99']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '0', $resultado['codigo']);
    }

    // Caso 3: Representante asignado no existe en base de datos
    public function testRepresentanteNoExiste(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturnOnConsecutiveCalls(1, 1, 0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['representante' => '99']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '1', $resultado['codigo']);
    }

    // Caso 4: Atleta mayor de edad sin representante omite verificación de representante
    public function testMayorSinRepresentante(): void
    {
        // Arrange
        $parametrosSP = null;
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$parametrosSP) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('closeCursor')->willReturn(true);
            if (str_contains($sql, 'RegistrarAtletaCompleto')) {
                $stmt->method('execute')->willReturnCallback(function ($params) use (&$parametrosSP) {
                    $parametrosSP = $params;
                    return true;
                });
            } else {
                $stmt->method('execute')->willReturn(true);
                $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones');
                $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            }
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => 1]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $anioActual = (int)date('Y');
        $datos = $this->obtenerDatosAtletaValidos([
            'fecha_nac'     => ($anioActual - 22) . '-01-15',
            'edad'          => '22',
            'representante' => ''
        ]);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertNull($parametrosSP[':rep']);
    }

    /* =========================================================================
     * SECCIÓN 2: VALIDACIONES DE UNICIDAD (DUPLICADOS EN BD)
     * ========================================================================= */

    // Caso 5: Documento de identidad ya existe en la base de datos
    public function testCedulaDuplicada(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['doc_identidad' => '12345678']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_CEDULA, $resultado['codigo']);
    }

    // Caso 6: Atleta menor de 9 años sin documento de identidad omite verificación de cédula
    public function testMenorSinCedula(): void
    {
        // Arrange
        $parametrosSP = null;
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$parametrosSP) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('closeCursor')->willReturn(true);
            if (str_contains($sql, 'RegistrarAtletaCompleto')) {
                $stmt->method('execute')->willReturnCallback(function ($params) use (&$parametrosSP) {
                    $parametrosSP = $params;
                    return true;
                });
            } elseif (str_contains($sql, 'identidad_atleta')) {
                $stmt->method('execute')->willReturn(true);
                $stmt->method('fetchColumn')->willReturn(1);
            } else {
                $stmt->method('execute')->willReturn(true);
                $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
                $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            }
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => 1]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $anioActual = (int)date('Y');
        $datos = $this->obtenerDatosAtletaValidos([
            'fecha_nac'     => ($anioActual - 8) . '-06-10',
            'edad'          => '8',
            'doc_identidad' => ''
        ]);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertSame('', $parametrosSP[':doc']);
    }

    // Caso 7: Teléfono ya existe en la base de datos
    public function testTelefonoDuplicado(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);
            if (str_contains($sql, 'identidad_atleta')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } else {
                $stmt->method('fetchColumn')->willReturn(1);
            }
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['telefono' => '0414-1234567']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_PHONE, $resultado['codigo']);
    }

    // Caso 8: Atleta sin teléfono omite verificación de teléfono
    public function testSinTelefono(): void
    {
        // Arrange
        $parametrosSP = null;
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$parametrosSP) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('closeCursor')->willReturn(true);
            if (str_contains($sql, 'RegistrarAtletaCompleto')) {
                $stmt->method('execute')->willReturnCallback(function ($params) use (&$parametrosSP) {
                    $parametrosSP = $params;
                    return true;
                });
            } elseif (str_contains($sql, 'contacto_atleta') && str_contains($sql, 'telefono')) {
                $stmt->method('execute')->willReturn(true);
                $stmt->method('fetchColumn')->willReturn(1);
            } else {
                $stmt->method('execute')->willReturn(true);
                $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
                $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            }
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => 1]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['telefono' => '']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertSame('', $parametrosSP[':tel']);
    }

    // Caso 9: Correo electrónico ya existe en la base de datos
    public function testCorreoDuplicado(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);
            if (str_contains($sql, 'correo')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } else {
                $stmt->method('fetchColumn')->willReturn(0);
            }
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['correo' => 'atleta@correo.com']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_EMAIL, $resultado['codigo']);
    }

    // Caso 10: Atleta sin correo omite verificación de correo
    public function testSinCorreo(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('closeCursor')->willReturn(true);
            $stmt->method('execute')->willReturn(true);
            if (str_contains($sql, 'contacto_atleta') && str_contains($sql, 'correo')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } else {
                $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
                $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            }
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => 1]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['correo' => '']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 11: Instagram ya existe en la base de datos
    public function testInstagramDuplicado(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);
            if (str_contains($sql, 'instagram')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } else {
                $stmt->method('fetchColumn')->willReturn(0);
            }
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['instagram' => '@atleta_2026']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_INSTAGRAM, $resultado['codigo']);
    }

    // Caso 12: Atleta sin Instagram omite verificación de Instagram
    public function testSinInstagram(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('closeCursor')->willReturn(true);
            $stmt->method('execute')->willReturn(true);
            if (str_contains($sql, 'contacto_atleta') && str_contains($sql, 'instagram')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } else {
                $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
                $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            }
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => 1]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['instagram' => '']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 13: Dorsal ya ocupado en la misma categoría
    public function testDorsalDuplicado(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);
            if (str_contains($sql, 'inscripciones')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } else {
                $stmt->method('fetchColumn')->willReturn(0);
            }
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['dorsal' => '10', 'categoria' => '1']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_DORSAL, $resultado['codigo']);
    }

    // Caso 14: Atleta sin dorsal permite registro sin verificar dorsal
    public function testSinDorsal(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('closeCursor')->willReturn(true);
            $stmt->method('execute')->willReturn(true);
            if (str_contains($sql, 'inscripciones')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } else {
                $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
                $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            }
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => 1]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['dorsal' => '0']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    /* =========================================================================
     * SECCIÓN 3: CONTROL DE RESPUESTAS DEL PROCEDIMIENTO ALMACENADO (@resultado)
     * ========================================================================= */

    // Caso 15: El procedimiento almacenado retorna -1 (Cédula duplicada en BD)
    public function testSpCedulaDuplicada(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);
            $stmt->method('closeCursor')->willReturn(true);
            $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
            $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => -1]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos();

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_CEDULA, $resultado['codigo']);
    }

    // Caso 16: El procedimiento almacenado retorna -2 (Teléfono duplicado en BD)
    public function testSpTelefonoDuplicado(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);
            $stmt->method('closeCursor')->willReturn(true);
            $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
            $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => -2]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos();

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_PHONE, $resultado['codigo']);
    }

    // Caso 17: El procedimiento almacenado retorna 0 (Error interno de BD)
    public function testSpErrorBd(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);
            $stmt->method('closeCursor')->willReturn(true);
            $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
            $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => 0]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos();

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ocurrió un error en la base de datos al registrar el atleta.', $resultado['codigo']);
    }

    // Caso 18: Fallo o excepción SQL al ejecutar el procedimiento almacenado
    public function testSpExcepcion(): void
    {
        // Arrange
        $mensajeExcepcion = 'SQLSTATE[HY000]: General error: 2006 MySQL server has gone away';
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($mensajeExcepcion) {
            $stmt = $this->createMock(PDOStatement::class);
            if (str_contains($sql, 'RegistrarAtletaCompleto')) {
                $stmt->method('execute')->willThrowException(new Exception($mensajeExcepcion));
            } else {
                $stmt->method('execute')->willReturn(true);
                $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
                $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            }
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos();

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals($mensajeExcepcion, $resultado['codigo']);
    }

    /* =========================================================================
     * SECCIÓN 4: REGISTRO EXITOSO Y TRANSFORMACIÓN DE PARÁMETROS
     * ========================================================================= */

    // Caso 19: Registro exitoso de atleta menor de edad con datos completos
    public function testRegistroExitoso(): void
    {
        // Arrange
        $parametrosSP = null;
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$parametrosSP) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('closeCursor')->willReturn(true);
            if (str_contains($sql, 'RegistrarAtletaCompleto')) {
                $stmt->method('execute')->willReturnCallback(function ($params) use (&$parametrosSP) {
                    $parametrosSP = $params;
                    return true;
                });
            } else {
                $stmt->method('execute')->willReturn(true);
                $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
                $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            }
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => 1]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos();

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertNotNull($parametrosSP);
        $this->assertEquals('12345678', $parametrosSP[':doc']);
        $this->assertEquals('Carlos', $parametrosSP[':pn']);
        $this->assertEquals('Alberto', $parametrosSP[':sn']);
        $this->assertEquals('Perez', $parametrosSP[':pa']);
        $this->assertEquals('Gomez', $parametrosSP[':sa']);
        $this->assertEquals('M', $parametrosSP[':gen']);
        $this->assertEquals('1', $parametrosSP[':rep']);
        $this->assertEquals('1', $parametrosSP[':cat']);
        $this->assertEquals('1', $parametrosSP[':pos']);
        $this->assertEquals('10', $parametrosSP[':dor']);
        $this->assertEquals('62.5', $parametrosSP[':peso']);
        $this->assertEquals('170', $parametrosSP[':est']);
    }

    // Caso 20: Registro exitoso de atleta con nombres y apellidos simples (sin segundo nombre/apellido)
    public function testNombresSimples(): void
    {
        // Arrange
        $parametrosSP = null;
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$parametrosSP) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('closeCursor')->willReturn(true);
            if (str_contains($sql, 'RegistrarAtletaCompleto')) {
                $stmt->method('execute')->willReturnCallback(function ($params) use (&$parametrosSP) {
                    $parametrosSP = $params;
                    return true;
                });
            } else {
                $stmt->method('execute')->willReturn(true);
                $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
                $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            }
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => 1]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos([
            'nombre'   => 'Carlos',
            'apellido' => 'Perez'
        ]);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Carlos', $parametrosSP[':pn']);
        $this->assertSame('', $parametrosSP[':sn']);
        $this->assertEquals('Perez', $parametrosSP[':pa']);
        $this->assertSame('', $parametrosSP[':sa']);
    }

    // Caso 21: Registro exitoso cuando foto se proporciona como arreglo
    public function testFotoArreglo(): void
    {
        // Arrange
        $parametrosSP = null;
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$parametrosSP) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('closeCursor')->willReturn(true);
            if (str_contains($sql, 'RegistrarAtletaCompleto')) {
                $stmt->method('execute')->willReturnCallback(function ($params) use (&$parametrosSP) {
                    $parametrosSP = $params;
                    return true;
                });
            } else {
                $stmt->method('execute')->willReturn(true);
                $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
                $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            }
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => 1]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos([
            'foto' => ['atleta_subida.png', 'otra_foto.png']
        ]);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('atleta_subida.png', $parametrosSP[':foto']);
    }

    // Caso 22: Verificación de estructura y contenido de datos_nuevos en respuesta exitosa
    public function testEstructuraDatosNuevos(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('closeCursor')->willReturn(true);
            $stmt->method('execute')->willReturn(true);
            $esCatalogo = str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes');
            $stmt->method('fetchColumn')->willReturn($esCatalogo ? 1 : 0);
            return $stmt;
        });
        $pdoMock->method('query')->willReturnCallback(function () {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('fetch')->willReturn(['resultado' => 1]);
            return $stmt;
        });
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos();

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        $datosNuevos = json_decode($resultado['datos_nuevos'], true);
        $this->assertIsArray($datosNuevos);
        $this->assertArrayHasKey('doc_identidad', $datosNuevos);
        $this->assertArrayHasKey('nombre', $datosNuevos);
        $this->assertArrayHasKey('apellido', $datosNuevos);
        $this->assertArrayHasKey('genero', $datosNuevos);
        $this->assertArrayHasKey('fecha_nac', $datosNuevos);
        $this->assertArrayHasKey('telefono', $datosNuevos);
        $this->assertArrayHasKey('direccion', $datosNuevos);
        $this->assertArrayHasKey('representante', $datosNuevos);
        $this->assertArrayHasKey('categoria', $datosNuevos);
        $this->assertArrayHasKey('posicion', $datosNuevos);
        $this->assertArrayHasKey('dorsal', $datosNuevos);
        $this->assertArrayHasKey('peso_kg', $datosNuevos);
        $this->assertArrayHasKey('estatura_cm', $datosNuevos);
        $this->assertArrayHasKey('foto', $datosNuevos);

        $this->assertEquals('Carlos Alberto', $datosNuevos['nombre']);
        $this->assertEquals('Perez Gomez', $datosNuevos['apellido']);
    }

    // Caso 23: Respuesta de error incluye datos_nuevos en formato JSON
    public function testDatosNuevosEnError(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['categoria' => '99']);

        // Act
        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        $datosNuevos = json_decode($resultado['datos_nuevos'], true);
        $this->assertIsArray($datosNuevos);
        $this->assertEquals('99', $datosNuevos['categoria']);
    }
}
