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

class IncluirTest extends TestCase
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
    public function test_deberia_incluirPremioExitosamente_cuando_datosSonCompletosYValidos(): void
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
            'nombre' => 'Trofeo Goleador',
            'tipo'   => 'I'
        ]);

        // Assert
        $this->assertIsArray($resultado);
        $this->assertSame('exito', $resultado['accion']);
    }

    #[Group('cu_premios_incluir')]
    #[Group('escenario_limite')]
    public function test_deberia_normalizarNombreYTipoConEspacios_cuando_seIncluyePremio(): void
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
            'nombre' => '   mejor portero de liga   ',
            'tipo'   => '  I  '
        ]);

        // Assert
        $reflector = new ReflectionClass($modelo);
        $propNombre = $reflector->getProperty('nombre');
        $propTipo = $reflector->getProperty('tipo');

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame('Mejor Portero De Liga', $propNombre->getValue($modelo));
        $this->assertSame('I', $propTipo->getValue($modelo));
    }

    #[Group('cu_premios_incluir')]
    #[Group('escenario_error')]
    public function test_deberia_retornarErrorDuplicado_cuando_nombreYaExisteRegistrado(): void
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
            'nombre' => 'Trofeo Duplicado',
            'tipo'   => 'G'
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
    }

    #[Group('cu_premios_incluir')]
    #[Group('escenario_error')]
    public function test_deberia_revertirTransaccionYRetornarError_cuando_fallaSentenciaSQLDeInsercion(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willThrowException(new PDOException('Fallo de integridad SQL'));

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
            'nombre' => 'Trofeo Error',
            'tipo'   => 'G'
        ]);

        // Assert
        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Fallo de integridad SQL', $resultado['codigo']);
    }
}

