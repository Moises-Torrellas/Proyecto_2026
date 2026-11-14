<?php

declare(strict_types=1);

namespace tests\Unit\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use PDO;
use PDOStatement;
use PDOException;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    $configPath = dirname(__DIR__, 3) . '/config/config.php';
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

#[Group('unit')]
#[Group('unitPremios')]
#[Group('cu_premios_eliminar')]
class EliminarPremiosTest extends TestCase
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
        $this->assertEquals('exito', $resultado['accion']);
    }

    #[Group('escenario_limite')]
    public function test_deberia_manejarEliminacionConLimiteNumerico_cuando_idEsMaximo(): void
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
            'codigo_premio' => PHP_INT_MAX
        ]);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    #[Group('escenario_error')]
    public function test_deberia_rechazarEliminacion_cuando_premioNoExiste(): void
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
            'codigo_premio' => 9999
        ]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    #[Group('escenario_error')]
    public function test_deberia_rechazarEliminacion_cuando_premioEstaAsociadoEnPalmares(): void
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
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo']);
    }

    #[Group('escenario_error')]
    public function test_deberia_revertirTransaccionYRetornarError_cuando_fallaSentenciaSQLDeBorrado(): void
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
        $stmtDelete->method('execute')->willThrowException(new PDOException('Error de clave foranea al eliminar'));

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
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de clave foranea al eliminar', $resultado['codigo']);
    }
}
