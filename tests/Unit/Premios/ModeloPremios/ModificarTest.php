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

class ModificarTest extends TestCase
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

    #[Group('cu_premios_modificar')]
    #[Group('escenario_exito')]
    public function test_deberia_modificarPremioExitosamente_cuando_datosSonValidos(): void
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
            'nombre'        => 'Trofeo Actualizado',
            'tipo'          => 'G'
        ]);

        // Assert
        $this->assertIsArray($resultado);
        $this->assertSame('exito', $resultado['accion']);
    }

    #[Group('cu_premios_modificar')]
    #[Group('escenario_limite')]
    public function test_deberia_permitirModificarTipo_cuando_noEstaAsociadoEnPalmares(): void
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
            'nombre'        => 'Trofeo Modificado',
            'tipo'          => 'I'
        ]);

        // Assert
        $this->assertSame('exito', $resultado['accion']);
    }

    #[Group('cu_premios_modificar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarErrorPorNombreDuplicado_cuando_seColisionaConOtroPremio(): void
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
            'nombre'        => 'Nombre En Conflicto',
            'tipo'          => 'G'
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
    }

    #[Group('cu_premios_modificar')]
    #[Group('escenario_error')]
    public function test_deberia_retornarErrorTypeAssociated_cuando_seCambiaTipoConAsociacionExistente(): void
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
    public function test_deberia_revertirTransaccionYRetornarError_cuando_fallaSentenciaSQLDeActualizacion(): void
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
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Error de base de datos en actualizacion'));

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
        $this->assertSame('Error de base de datos en actualizacion', $resultado['codigo']);
    }
}

