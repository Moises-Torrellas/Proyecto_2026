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
#[Group('reinscribirAtletas')]
class ReinscribirAtletasTest extends TestCase
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
     * Genera datos válidos para procesar la acción 'reinscribir'.
     */
    private function obtenerDatosAtletaValidos(array $sobrescribir = []): array
    {
        $anioActual = (int)date('Y');
        $fechaNac = ($anioActual - 15) . '-05-20'; // 15 años (menor)

        $datos = [
            'accion'            => 'reinscribir',
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
     * SECCIÓN 1: VALIDACIÓN DE ENTIDADES RELACIONADAS
     * ========================================================================= */

    // Caso 1: Categoría no existe
    public function testCategoriaNoExiste(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['categoria' => '99']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 2: Posición no existe
    public function testPosicionNoExiste(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturnOnConsecutiveCalls(1, 0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['posicion' => '99']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '0', $resultado['codigo']);
    }

    // Caso 3: Representante no existe
    public function testRepresentanteNoExiste(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturnOnConsecutiveCalls(1, 1, 0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['representante' => '99']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '1', $resultado['codigo']);
    }

    // Caso 4: Atleta mayor de edad sin representante omite verificación de representante
    public function testMayorDeEdadSinRepresentante(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'inscripciones WHERE dorsal = :dorsal')) {
                $stmt->method('fetchColumn')->willReturn(0); // Dorsal disponible
            } else {
                $stmt->method('fetchColumn')->willReturn(1);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $anioActual = (int)date('Y');
        $datos = $this->obtenerDatosAtletaValidos([
            'fecha_nac'     => ($anioActual - 21) . '-01-01',
            'edad'          => '21',
            'representante' => ''
        ]);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
    }

    /* =========================================================================
     * SECCIÓN 2: VALIDACIONES DE UNICIDAD (DORSAL Y CORREO)
     * ========================================================================= */

    // Caso 5: Dorsal duplicado en la misma categoría en otro atleta
    public function testDorsalDuplicadoEnMismaCategoria(): void
    {
        $pdoMock = $this->createMock(PDO::class);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'inscripciones WHERE dorsal = :dorsal')) {
                $stmt->method('fetchColumn')->willReturn(1); // Dorsal ocupado
            } else {
                $stmt->method('fetchColumn')->willReturn(0);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['dorsal' => '10', 'categoria' => '1']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_DORSAL, $resultado['codigo']);
    }

    // Caso 6: Correo duplicado en otro atleta
    public function testCorreoDuplicadoEnOtroAtleta(): void
    {
        $pdoMock = $this->createMock(PDO::class);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'inscripciones WHERE dorsal = :dorsal')) {
                $stmt->method('fetchColumn')->willReturn(0); // Dorsal disponible
            } elseif (str_contains($sql, 'contacto_atleta') && str_contains($sql, 'correo') && str_contains($sql, 'codigo_atleta = :id')) {
                $stmt->method('fetchColumn')->willReturn(0); // No es propio
            } elseif (str_contains($sql, 'contacto_atleta') && str_contains($sql, 'correo')) {
                $stmt->method('fetchColumn')->willReturn(1); // Existe en otro atleta
            } else {
                $stmt->method('fetchColumn')->willReturn(0);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['correo' => 'otro@correo.com']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_EMAIL, $resultado['codigo']);
    }

    // Caso 7: Correo propio no genera error de duplicado
    public function testCorreoPropioNoGeneraErrorDuplicado(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'inscripciones WHERE dorsal = :dorsal')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } elseif (str_contains($sql, 'contacto_atleta') && str_contains($sql, 'correo') && str_contains($sql, 'codigo_atleta = :id')) {
                $stmt->method('fetchColumn')->willReturn(1); // Es propio
            } else {
                $stmt->method('fetchColumn')->willReturn(1);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['id' => '1', 'correo' => 'atleta@correo.com']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
    }

    /* =========================================================================
     * SECCIÓN 3: REINSCRIPCIÓN EXITOSA Y MANEJO DE ERRORES
     * ========================================================================= */

    // Caso 8: Reinscripción exitosa
    public function testReinscribirExitoso(): void
    {
        $consultasEjecutadas = [];
        $parametrosInscripcion = null;

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$consultasEjecutadas, &$parametrosInscripcion) {
            $consultasEjecutadas[] = $sql;
            $stmt = $this->createMock(PDOStatement::class);

            if (str_contains($sql, 'INSERT INTO inscripciones')) {
                $stmt->method('execute')->willReturnCallback(function ($params) use (&$parametrosInscripcion) {
                    $parametrosInscripcion = $params;
                    return true;
                });
            } else {
                $stmt->method('execute')->willReturn(true);
            }

            if (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'inscripciones WHERE dorsal = :dorsal')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } elseif (str_contains($sql, 'contacto_atleta') && str_contains($sql, 'correo')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } else {
                $stmt->method('fetchColumn')->willReturn(1);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['id' => '5']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        $consultasTexto = implode(' ', $consultasEjecutadas);
        $this->assertStringContainsString('INSERT INTO inscripciones', $consultasTexto);
        $this->assertStringContainsString('UPDATE datos_medicos SET tipo_sangre', $consultasTexto);

        $this->assertNotNull($parametrosInscripcion);
        $this->assertEquals('5', $parametrosInscripcion[':ca']);
        $this->assertEquals('1', $parametrosInscripcion[':cc']);
        $this->assertEquals('1', $parametrosInscripcion[':cp']);
    }

    // Caso 9: Error en base de datos ejecuta rollback
    public function testErrorEnBaseDeDatosRealizaRollback(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            if (str_contains($sql, 'INSERT INTO inscripciones')) {
                throw new Exception('Error de base de datos durante reinscripción');
            }

            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'inscripciones WHERE dorsal = :dorsal')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } else {
                $stmt->method('fetchColumn')->willReturn(0);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['id' => '5']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de base de datos durante reinscripción', $resultado['codigo']);
    }
}
