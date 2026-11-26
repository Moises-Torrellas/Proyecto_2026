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
#[Group('RF-03')]
#[Group('ESC-03')]
class ModificarPremiosTest extends TestCase
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

    private function obtenerDatosModificarValidos(array $sobrescribir = []): array
    {
        $base = [
            'accion'        => 'modificar',
            'codigo_premio' => 1,
            'nombre'        => 'Copa Oro Hockey Modificada',
            'tipo'          => 'G'
        ];

        return array_merge($base, $sobrescribir);
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

    // ==========================================
    // ESCENARIO 03: Modificación y Actualización de Galardones
    // ==========================================

    // Caso 08: Modificar premio exitosamente con confirmación transaccional cuando los datos son válidos
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-08')]
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
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 09: Permitir modificar el tipo de premio cuando no tiene asociaciones en palmarés
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-09')]
    #[Group('escenario_limite')]
    public function test_deberia_permitirModificarTipo_cuando_premioNoTieneAsociacionesEnPalmares(): void
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
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos(['tipo' => 'I']));

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 10: Rechazar modificación por nombre duplicado perteneciente a otro premio (DUPLICATE_NAME)
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-10')]
    #[Group('escenario_error')]
    public function test_deberia_rechazarModificacion_cuando_nombreColisionaConOtroPremio(): void
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
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos(['codigo_premio' => 2]));

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);
    }

    // Caso 11: Rechazar cambio de tipo cuando el premio está asociado a palmarés (TYPE_ASSOCIATED)
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-11')]
    #[Group('escenario_error')]
    public function test_deberia_rechazarCambioDeTipo_cuando_estaAsociadoAPalmares(): void
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
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos(['tipo' => 'I']));

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('TYPE_ASSOCIATED', $resultado['codigo']);
    }

    // Caso 12: Revertir transacción con rollback y retornar error cuando falla la actualización SQL
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-12')]
    #[Group('escenario_error')]
    public function test_deberia_revertirTransaccionYRetornarError_cuando_fallaActualizacionSQL(): void
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
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Error de comunicacion SQL en actualizacion'));

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
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de comunicacion SQL en actualizacion', $resultado['codigo']);
    }
}
