<?php

declare(strict_types=1);

namespace tests\Unit\Premios\ModeloPremios;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use PDO;
use PDOStatement;
use PDOException;
use Exception;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    $configPath = dirname(__DIR__, 4) . '/config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
}

if (!defined('DUPLICATE_NAME')) {
    define('DUPLICATE_NAME', '004');
}
if (!defined('INVALID_ID')) {
    define('INVALID_ID', '003');
}
if (!defined('ASSOCIATES')) {
    define('ASSOCIATES', '006');
}
if (!defined('VALIDATION')) {
    define('VALIDATION', '007');
}
if (!defined('DB_CONNECTION')) {
    define('DB_CONNECTION', '500');
}

class ProcesarDatosTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);
    }

    private function configurarPdoConTransaccion(): PDO
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);

        return $pdoMock;
    }

    #[Group('cu_premios_incluir')]
    #[Group('escenario_exito')]
    public function test_deberia_incluirPremioExitosamente_cuando_datosSonValidos(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCheck, $stmtInsert) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            return $stmtInsert;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'incluir',
            'nombre' => 'Trofeo Oro Campeon',
            'tipo'   => 'G'
        ]);

        // Assert
        $this->assertIsArray($resultado);
        $this->assertSame('exito', $resultado['accion']);
    }

    #[Group('cu_premios_modificar')]
    #[Group('escenario_exito')]
    public function test_deberia_modificarPremioExitosamente_cuando_datosSonValidosYNoHayColision(): void
    {
        // Arrange
        $stmtCheckPropio = $this->createMock(PDOStatement::class);
        $stmtCheckPropio->method('execute')->willReturn(true);
        $stmtCheckPropio->method('fetchColumn')->willReturn(1);

        $stmtTipo = $this->createMock(PDOStatement::class);
        $stmtTipo->method('execute')->willReturn(true);
        $stmtTipo->method('fetchColumn')->willReturn('G');

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCheckPropio, $stmtTipo, $stmtUpdate) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheckPropio;
            }
            if (str_contains($sql, 'SELECT tipo FROM premios')) {
                return $stmtTipo;
            }
            return $stmtUpdate;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'codigo_premio' => 1,
            'nombre'        => 'Trofeo Plata Subcampeon',
            'tipo'          => 'G'
        ]);

        // Assert
        $this->assertIsArray($resultado);
        $this->assertSame('exito', $resultado['accion']);
    }

    #[Group('cu_premios_eliminar')]
    #[Group('escenario_exito')]
    public function test_deberia_eliminarPremioExitosamente_cuando_existeYNoTieneAsociaciones(): void
    {
        // Arrange
        $stmtCheckExiste = $this->createMock(PDOStatement::class);
        $stmtCheckExiste->method('execute')->willReturn(true);
        $stmtCheckExiste->method('fetchColumn')->willReturn(1);

        $stmtCheckAsociacion = $this->createMock(PDOStatement::class);
        $stmtCheckAsociacion->method('execute')->willReturn(true);
        $stmtCheckAsociacion->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('bindParam')->willReturn(true);
        $stmtDelete->method('execute')->willReturn(true);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCheckExiste, $stmtCheckAsociacion, $stmtDelete) {
            if (str_contains($sql, 'FROM `premios`')) {
                return $stmtCheckExiste;
            }
            if (str_contains($sql, 'palmares_grupal') || str_contains($sql, 'palmares_individual')) {
                return $stmtCheckAsociacion;
            }
            return $stmtDelete;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_premio' => 1
        ]);

        // Assert
        $this->assertIsArray($resultado);
        $this->assertSame('exito', $resultado['accion']);
    }

    #[Group('cu_premios_buscar')]
    #[Group('escenario_exito')]
    public function test_deberia_despacharABuscarExitosamente_cuando_accionEsBuscar(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_premio' => 1, 'nombre' => 'Copa Oro', 'tipo' => 'G']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'buscar',
            'codigo_premio' => 1
        ]);

        // Assert
        $this->assertSame('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertSame('Copa Oro', $resultado['datos'][0]['nombre']);
    }

    #[Group('cu_premios_generar')]
    #[Group('escenario_exito')]
    public function test_deberia_despacharAConsultarExitosamente_cuando_accionEsGenerar(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_premio' => 1, 'nombre' => 'Copa Oro', 'tipo' => 'G']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'generar',
            'nombre' => 'Copa Oro'
        ]);

        // Assert
        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    #[Group('cu_premios_incluir')]
    #[Group('escenario_limite')]
    public function test_deberia_aplicarTrimYConversionATitulo_cuando_nombreTieneEspaciosYMinusculas(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCheck, $stmtInsert) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            return $stmtInsert;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'incluir',
            'nombre' => '   copa de hockey sobre hielo   ',
            'tipo'   => ' G '
        ]);

        // Assert
        $reflector = new ReflectionClass($modelo);
        $propNombre = $reflector->getProperty('nombre');
        $propTipo = $reflector->getProperty('tipo');

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame('Copa De Hockey Sobre Hielo', $propNombre->getValue($modelo));
        $this->assertSame('G', $propTipo->getValue($modelo));
    }

    #[Group('cu_premios_modificar')]
    #[Group('escenario_limite')]
    public function test_deberia_permitirModificarTipo_cuando_noEstaAsociadoAPalmares(): void
    {
        // Arrange
        $stmtCheckPropio = $this->createMock(PDOStatement::class);
        $stmtCheckPropio->method('execute')->willReturn(true);
        $stmtCheckPropio->method('fetchColumn')->willReturn(1);

        $stmtTipo = $this->createMock(PDOStatement::class);
        $stmtTipo->method('execute')->willReturn(true);
        $stmtTipo->method('fetchColumn')->willReturn('G');

        $stmtPalmares = $this->createMock(PDOStatement::class);
        $stmtPalmares->method('execute')->willReturn(true);
        $stmtPalmares->method('fetchColumn')->willReturn(0);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCheckPropio, $stmtTipo, $stmtPalmares, $stmtUpdate) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'premios')) {
                return $stmtCheckPropio;
            }
            if (str_contains($sql, 'SELECT tipo FROM premios')) {
                return $stmtTipo;
            }
            if (str_contains($sql, 'palmares_grupal') || str_contains($sql, 'palmares_individual')) {
                return $stmtPalmares;
            }
            return $stmtUpdate;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'codigo_premio' => 1,
            'nombre'        => 'Trofeo Individual',
            'tipo'          => 'I'
        ]);

        // Assert
        $this->assertSame('exito', $resultado['accion']);
    }

    #[Group('cu_premios_buscar')]
    #[Group('escenario_limite')]
    public function test_deberia_manejarCamposOpcionalesNulos_cuando_seProcesanAccionesSinTipoONombre(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos(['accion' => 'buscar']);

        // Assert
        $this->assertSame('buscar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    #[Group('cu_premios_procesar')]
    #[Group('escenario_error')]
    public function test_deberia_lanzarExcepcion_cuando_elArregloDeDatosEstaVacio(): void
    {
        // Arrange
        $modelo = new ModeloPremios();

        // Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        // Act
        $modelo->ProcesarDatos([]);
    }

    #[Group('cu_premios_procesar')]
    #[Group('escenario_error')]
    public function test_deberia_lanzarExcepcion_cuando_laAccionNoEsValida(): void
    {
        // Arrange
        $modelo = new ModeloPremios();

        // Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada no es válida para Premios.');

        // Act
        $modelo->ProcesarDatos(['accion' => 'accion_invalida']);
    }

    #[Group('cu_premios_procesar')]
    #[Group('escenario_error')]
    public function test_deberia_lanzarExcepcion_cuando_noSeEspecificaAccion(): void
    {
        // Arrange
        $modelo = new ModeloPremios();

        // Assert Expectation
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada no es válida para Premios.');

        // Act
        $modelo->ProcesarDatos(['nombre' => 'Premio Sin Accion']);
    }

    #[Group('cu_premios_incluir')]
    #[Group('escenario_error')]
    public function test_deberia_retornarErrorPorNombreDuplicado_cuando_seIncluyePremioExistente(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturn($stmtCheck);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'incluir',
            'nombre' => 'Copa Duplicada',
            'tipo'   => 'G'
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
    }

    #[Group('cu_premios_incluir')]
    #[Group('escenario_error')]
    public function test_deberia_revertirTransaccionYRetornarError_cuando_fallaInsercion(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willThrowException(new PDOException('Error SQL en insercion'));

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCheck, $stmtInsert) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheck;
            }
            return $stmtInsert;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'incluir',
            'nombre' => 'Nuevo Trofeo',
            'tipo'   => 'G'
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error SQL en insercion', $resultado['codigo']);
    }

    #[Group('cu_premios_modificar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarErrorPorNombreDuplicado_cuando_seModificaConNombreDeOtroPremio(): void
    {
        // Arrange
        $stmtCheckPropio = $this->createMock(PDOStatement::class);
        $stmtCheckPropio->method('execute')->willReturn(true);
        $stmtCheckPropio->method('fetchColumn')->willReturn(0);

        $stmtCheckDuplicado = $this->createMock(PDOStatement::class);
        $stmtCheckDuplicado->method('execute')->willReturn(true);
        $stmtCheckDuplicado->method('fetchColumn')->willReturn(1);

        $llamadaCount = 0;
        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (&$llamadaCount, $stmtCheckPropio, $stmtCheckDuplicado) {
            $llamadaCount++;
            if ($llamadaCount === 1) {
                return $stmtCheckPropio;
            }
            return $stmtCheckDuplicado;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'codigo_premio' => 2,
            'nombre'        => 'Nombre De Otro Premio',
            'tipo'          => 'G'
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
    }

    #[Group('cu_premios_modificar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarErrorTypeAssociated_cuando_seCambiaTipoYEstaAsociadoAPalmaresGrupal(): void
    {
        // Arrange
        $stmtCheckPropio = $this->createMock(PDOStatement::class);
        $stmtCheckPropio->method('execute')->willReturn(true);
        $stmtCheckPropio->method('fetchColumn')->willReturn(1);

        $stmtTipo = $this->createMock(PDOStatement::class);
        $stmtTipo->method('execute')->willReturn(true);
        $stmtTipo->method('fetchColumn')->willReturn('G');

        $stmtPalmaresGrupal = $this->createMock(PDOStatement::class);
        $stmtPalmaresGrupal->method('execute')->willReturn(true);
        $stmtPalmaresGrupal->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCheckPropio, $stmtTipo, $stmtPalmaresGrupal) {
            if (str_contains($sql, 'SELECT tipo FROM premios')) {
                return $stmtTipo;
            }
            if (str_contains($sql, 'palmares_grupal')) {
                return $stmtPalmaresGrupal;
            }
            return $stmtCheckPropio;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'codigo_premio' => 1,
            'nombre'        => 'Trofeo',
            'tipo'          => 'I'
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('TYPE_ASSOCIATED', $resultado['codigo']);
    }

    #[Group('cu_premios_modificar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarErrorTypeAssociated_cuando_seCambiaTipoYEstaAsociadoAPalmaresIndividual(): void
    {
        // Arrange
        $stmtCheckPropio = $this->createMock(PDOStatement::class);
        $stmtCheckPropio->method('execute')->willReturn(true);
        $stmtCheckPropio->method('fetchColumn')->willReturn(1);

        $stmtTipo = $this->createMock(PDOStatement::class);
        $stmtTipo->method('execute')->willReturn(true);
        $stmtTipo->method('fetchColumn')->willReturn('G');

        $stmtPalmaresGrupal = $this->createMock(PDOStatement::class);
        $stmtPalmaresGrupal->method('execute')->willReturn(true);
        $stmtPalmaresGrupal->method('fetchColumn')->willReturn(0);

        $stmtPalmaresIndividual = $this->createMock(PDOStatement::class);
        $stmtPalmaresIndividual->method('execute')->willReturn(true);
        $stmtPalmaresIndividual->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCheckPropio, $stmtTipo, $stmtPalmaresGrupal, $stmtPalmaresIndividual) {
            if (str_contains($sql, 'SELECT tipo FROM premios')) {
                return $stmtTipo;
            }
            if (str_contains($sql, 'palmares_grupal')) {
                return $stmtPalmaresGrupal;
            }
            if (str_contains($sql, 'palmares_individual')) {
                return $stmtPalmaresIndividual;
            }
            return $stmtCheckPropio;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'codigo_premio' => 1,
            'nombre'        => 'Trofeo',
            'tipo'          => 'I'
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('TYPE_ASSOCIATED', $resultado['codigo']);
    }

    #[Group('cu_premios_modificar')]
    #[Group('escenario_error')]
    public function test_deberia_revertirTransaccionYRetornarError_cuando_fallaActualizacionEnBD(): void
    {
        // Arrange
        $stmtCheckPropio = $this->createMock(PDOStatement::class);
        $stmtCheckPropio->method('execute')->willReturn(true);
        $stmtCheckPropio->method('fetchColumn')->willReturn(1);

        $stmtTipo = $this->createMock(PDOStatement::class);
        $stmtTipo->method('execute')->willReturn(true);
        $stmtTipo->method('fetchColumn')->willReturn('G');

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Error al actualizar registro'));

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCheckPropio, $stmtTipo, $stmtUpdate) {
            if (str_contains($sql, 'COUNT(*)')) {
                return $stmtCheckPropio;
            }
            if (str_contains($sql, 'SELECT tipo FROM premios')) {
                return $stmtTipo;
            }
            return $stmtUpdate;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'codigo_premio' => 1,
            'nombre'        => 'Trofeo',
            'tipo'          => 'G'
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al actualizar registro', $resultado['codigo']);
    }

    #[Group('cu_premios_eliminar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarErrorInvalidId_cuando_seIntentaEliminarPremioInexistente(): void
    {
        // Arrange
        $stmtCheckExiste = $this->createMock(PDOStatement::class);
        $stmtCheckExiste->method('execute')->willReturn(true);
        $stmtCheckExiste->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturn($stmtCheckExiste);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_premio' => 999
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
    }

    #[Group('cu_premios_eliminar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarErrorAssociates_cuando_seEliminaPremioAsociadoAPalmaresGrupal(): void
    {
        // Arrange
        $stmtCheckExiste = $this->createMock(PDOStatement::class);
        $stmtCheckExiste->method('execute')->willReturn(true);
        $stmtCheckExiste->method('fetchColumn')->willReturn(1);

        $stmtCheckPalmaresGrupal = $this->createMock(PDOStatement::class);
        $stmtCheckPalmaresGrupal->method('execute')->willReturn(true);
        $stmtCheckPalmaresGrupal->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCheckExiste, $stmtCheckPalmaresGrupal) {
            if (str_contains($sql, 'palmares_grupal')) {
                return $stmtCheckPalmaresGrupal;
            }
            return $stmtCheckExiste;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_premio' => 1
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(ASSOCIATES, $resultado['codigo']);
    }

    #[Group('cu_premios_eliminar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarErrorAssociates_cuando_seEliminaPremioAsociadoAPalmaresIndividual(): void
    {
        // Arrange
        $stmtCheckExiste = $this->createMock(PDOStatement::class);
        $stmtCheckExiste->method('execute')->willReturn(true);
        $stmtCheckExiste->method('fetchColumn')->willReturn(1);

        $stmtCheckPalmaresGrupal = $this->createMock(PDOStatement::class);
        $stmtCheckPalmaresGrupal->method('execute')->willReturn(true);
        $stmtCheckPalmaresGrupal->method('fetchColumn')->willReturn(0);

        $stmtCheckPalmaresIndividual = $this->createMock(PDOStatement::class);
        $stmtCheckPalmaresIndividual->method('execute')->willReturn(true);
        $stmtCheckPalmaresIndividual->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCheckExiste, $stmtCheckPalmaresGrupal, $stmtCheckPalmaresIndividual) {
            if (str_contains($sql, 'palmares_grupal')) {
                return $stmtCheckPalmaresGrupal;
            }
            if (str_contains($sql, 'palmares_individual')) {
                return $stmtCheckPalmaresIndividual;
            }
            return $stmtCheckExiste;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_premio' => 1
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(ASSOCIATES, $resultado['codigo']);
    }

    #[Group('cu_premios_eliminar')]
    #[Group('escenario_error')]
    public function test_deberia_revertirTransaccionYRetornarError_cuando_fallaSentenciaDelete(): void
    {
        // Arrange
        $stmtCheckExiste = $this->createMock(PDOStatement::class);
        $stmtCheckExiste->method('execute')->willReturn(true);
        $stmtCheckExiste->method('fetchColumn')->willReturn(1);

        $stmtCheckAsociacion = $this->createMock(PDOStatement::class);
        $stmtCheckAsociacion->method('execute')->willReturn(true);
        $stmtCheckAsociacion->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('bindParam')->willReturn(true);
        $stmtDelete->method('execute')->willThrowException(new PDOException('Error al borrar fila'));

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtCheckExiste, $stmtCheckAsociacion, $stmtDelete) {
            if (str_contains($sql, 'FROM `premios`')) {
                return $stmtCheckExiste;
            }
            if (str_contains($sql, 'palmares_grupal') || str_contains($sql, 'palmares_individual')) {
                return $stmtCheckAsociacion;
            }
            return $stmtDelete;
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_premio' => 1
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al borrar fila', $resultado['codigo']);
    }
}
