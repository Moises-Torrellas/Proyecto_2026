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
#[Group('modificarAtletas')]
class ModificarAtletasTest extends TestCase
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
     * Genera datos válidos para procesar la acción 'modificar'.
     */
    private function obtenerDatosAtletaValidos(array $sobrescribir = []): array
    {
        $anioActual = (int)date('Y');
        $fechaNac = ($anioActual - 15) . '-05-20'; // Menor de edad (15 años)

        $datos = [
            'accion'            => 'modificar',
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

    // Caso 1: Validación de entidades relacionadas inexistentes (categoría, posición, representante)
    public function testEntidadesRelacionadasInexistentes(): void
    {
        $casos = [
            'categoria'     => [['categoria' => '99'], INVALID_ID],
            'posicion'      => [['posicion' => '99'], INVALID_ID . '0'],
            'representante' => [['representante' => '99'], INVALID_ID . '1']
        ];

        foreach ($casos as $tipo => [$sobrescribir, $codigoEsperado]) {
            $stmtMock = $this->createMock(PDOStatement::class);
            $stmtMock->method('execute')->willReturn(true);
            if ($tipo === 'categoria') {
                $stmtMock->method('fetchColumn')->willReturn(0);
            } elseif ($tipo === 'posicion') {
                $stmtMock->method('fetchColumn')->willReturnOnConsecutiveCalls(1, 0);
            } else {
                $stmtMock->method('fetchColumn')->willReturnOnConsecutiveCalls(1, 1, 0);
            }

            $pdoMock = $this->createMock(PDO::class);
            $pdoMock->method('prepare')->willReturn($stmtMock);
            $this->crearModeloConPDOMock($pdoMock);

            $datos = $this->obtenerDatosAtletaValidos($sobrescribir);
            $modelo = new ModeloAtletas();
            $resultado = $modelo->ProcesarDatos($datos);

            $this->assertEquals('error', $resultado['accion']);
            $this->assertEquals($codigoEsperado, $resultado['codigo'], "Fallo en verificación de $tipo");
        }
    }

    /* =========================================================================
     * SECCIÓN 2: VALIDACIONES DE UNICIDAD Y DUPLICADOS
     * ========================================================================= */

    // Caso 2: Cédula duplicada en otro atleta
    public function testCedulaDuplicadaEnOtroAtleta(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'identidad_atleta') && str_contains($sql, 'codigo_atleta = :id')) {
                // Verificar existencia propia: NO es del atleta actual
                $stmt->method('fetchColumn')->willReturn(0);
            } elseif (str_contains($sql, 'identidad_atleta')) {
                // Verificar existencia global: SÍ existe en otro atleta
                $stmt->method('fetchColumn')->willReturn(1);
            } else {
                $stmt->method('fetchColumn')->willReturn(0);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['id' => '1', 'doc_identidad' => '12345678']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_CEDULA, $resultado['codigo']);
    }

    // Caso 5: Cédula propia no genera error de duplicado
    public function testCedulaPropiaNoGeneraErrorDuplicado(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'identidad_atleta') && str_contains($sql, 'codigo_atleta = :id')) {
                // Es propia
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'inscripciones WHERE dorsal = :dorsal')) {
                // Dorsal disponible
                $stmt->method('fetchColumn')->willReturn(0);
            } else {
                // Otros counts devuelven 1 (registros existen)
                $stmt->method('fetchColumn')->willReturn(1);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['id' => '1', 'doc_identidad' => '12345678']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 6: Campos únicos duplicados en otro atleta (Teléfono, Correo, Instagram, Dorsal)
    public function testCamposUnicosDuplicadosEnOtroAtleta(): void
    {
        $casos = [
            ['campo' => 'telefono',  'datos' => ['id' => '1', 'telefono' => '0414-1234567'], 'codigo' => DUPLICATE_PHONE],
            ['campo' => 'correo',    'datos' => ['id' => '1', 'correo' => 'atleta@correo.com'], 'codigo' => DUPLICATE_EMAIL],
            ['campo' => 'instagram', 'datos' => ['id' => '1', 'instagram' => '@atleta_2026'], 'codigo' => DUPLICATE_INSTAGRAM],
            ['campo' => 'dorsal',    'datos' => ['id' => '1', 'dorsal' => '10', 'categoria' => '1'], 'codigo' => DUPLICATE_DORSAL]
        ];

        foreach ($casos as $caso) {
            $pdoMock = $this->createMock(PDO::class);
            $pdoMock->method('beginTransaction')->willReturn(true);
            $pdoMock->method('inTransaction')->willReturn(true);
            $pdoMock->method('rollBack')->willReturn(true);

            $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($caso) {
                $stmt = $this->createMock(PDOStatement::class);
                $stmt->method('execute')->willReturn(true);

                if (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                    $stmt->method('fetchColumn')->willReturn(1);
                } elseif (str_contains($sql, 'codigo_atleta = :id')) {
                    $stmt->method('fetchColumn')->willReturn(0);
                } elseif ($caso['campo'] === 'dorsal' && str_contains($sql, 'inscripciones WHERE dorsal = :dorsal')) {
                    $stmt->method('fetchColumn')->willReturn(1);
                } elseif ($caso['campo'] !== 'dorsal' && str_contains($sql, 'contacto_atleta') && str_contains($sql, $caso['campo'])) {
                    $stmt->method('fetchColumn')->willReturn(1);
                } else {
                    $stmt->method('fetchColumn')->willReturn(0);
                }
                return $stmt;
            });

            $this->crearModeloConPDOMock($pdoMock);

            $datos = $this->obtenerDatosAtletaValidos($caso['datos']);

            $modelo = new ModeloAtletas();
            $resultado = $modelo->ProcesarDatos($datos);

            $this->assertEquals('error', $resultado['accion']);
            $this->assertEquals($caso['codigo'], $resultado['codigo'], "Fallo en duplicado de {$caso['campo']}");
        }
    }

    /* =========================================================================
     * SECCIÓN 3: MODIFICACIONES EXITOSAS (UPDATE Y INSERT)
     * ========================================================================= */

    // Caso 10: Modificación exitosa actualizando registros existentes
    public function testModificarExitosoActualizandoRegistrosExistentes(): void
    {
        $consultasEjecutadas = [];

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$consultasEjecutadas) {
            $consultasEjecutadas[] = $sql;
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'identidad_atleta') && str_contains($sql, 'codigo_atleta = :id')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'contacto_atleta') && str_contains($sql, 'codigo_atleta = :id')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'atleta_representante WHERE codigo_atleta = :id')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'datos_medicos WHERE codigo_atleta = :id')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'inscripciones WHERE dorsal = :dorsal')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } else {
                $stmt->method('fetchColumn')->willReturn(1);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['id' => '1']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);

        // Verificar que se ejecutaron sentencias UPDATE para los registros existentes
        $consultasTexto = implode(' ', $consultasEjecutadas);
        $this->assertStringContainsString('UPDATE atletas SET', $consultasTexto);
        $this->assertStringContainsString('UPDATE contacto_atleta SET', $consultasTexto);
        $this->assertStringContainsString('UPDATE identidad_atleta SET', $consultasTexto);
        $this->assertStringContainsString('UPDATE atleta_representante SET', $consultasTexto);
        $this->assertStringContainsString('UPDATE inscripciones SET', $consultasTexto);
        $this->assertStringContainsString('UPDATE datos_medicos SET', $consultasTexto);
    }

    // Caso 11: Modificación exitosa insertando registros cuando no existían previamente
    public function testModificarExitosoInsertandoNuevosRegistros(): void
    {
        $consultasEjecutadas = [];

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$consultasEjecutadas) {
            $consultasEjecutadas[] = $sql;
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones') || str_contains($sql, 'representantes')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'inscripciones WHERE dorsal = :dorsal')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } elseif (str_contains($sql, 'SELECT COUNT(*)')) {
                // No existen registros previos en las tablas relacionadas
                $stmt->method('fetchColumn')->willReturn(0);
            } else {
                $stmt->method('fetchColumn')->willReturn(0);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['id' => '2']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);

        // Verificar que se ejecutaron sentencias INSERT para los registros faltantes
        $consultasTexto = implode(' ', $consultasEjecutadas);
        $this->assertStringContainsString('INSERT INTO contacto_atleta', $consultasTexto);
        $this->assertStringContainsString('INSERT INTO identidad_atleta', $consultasTexto);
        $this->assertStringContainsString('INSERT INTO atleta_representante', $consultasTexto);
        $this->assertStringContainsString('INSERT INTO datos_medicos', $consultasTexto);
    }

    // Caso 12: Atleta mayor de edad sin representante elimina asociación previa
    public function testModificarMayorDeEdadSinRepresentanteEliminaAsociacion(): void
    {
        $consultasEjecutadas = [];

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$consultasEjecutadas) {
            $consultasEjecutadas[] = $sql;
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'categorias') || str_contains($sql, 'posiciones')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'inscripciones WHERE dorsal = :dorsal')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } else {
                $stmt->method('fetchColumn')->willReturn(1);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $anioActual = (int)date('Y');
        $datos = $this->obtenerDatosAtletaValidos([
            'id'            => '1',
            'fecha_nac'     => ($anioActual - 20) . '-01-01',
            'edad'          => '20',
            'representante' => ''
        ]);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('exito', $resultado['accion']);
        $consultasTexto = implode(' ', $consultasEjecutadas);
        $this->assertStringContainsString('DELETE FROM atleta_representante WHERE codigo_atleta = :id', $consultasTexto);
    }

    // Caso 13: Error o excepción en base de datos ejecuta rollback
    public function testExcepcionEnBaseDeDatosRealizaRollback(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            if (str_contains($sql, 'UPDATE atletas SET')) {
                throw new Exception('Error de base de datos simulado');
            }

            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);
            if (str_contains($sql, 'inscripciones WHERE dorsal = :dorsal')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } else {
                $stmt->method('fetchColumn')->willReturn(1);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $datos = $this->obtenerDatosAtletaValidos(['id' => '1']);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos($datos);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de base de datos simulado', $resultado['codigo']);
    }
}
