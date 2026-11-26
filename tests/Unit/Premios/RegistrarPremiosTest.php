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
#[Group('RF-02')]
#[Group('ESC-02')]
class RegistrarPremiosTest extends TestCase
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

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $base = [
            'accion' => 'incluir',
            'nombre' => 'Copa Oro Hockey',
            'tipo'   => 'G'
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
    // ESCENARIO 02: Registro / Inclusión de Galardones
    // ==========================================

    // Caso 04: Incluir premio exitosamente con confirmación transaccional cuando los datos son válidos
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-04')]
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
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 05: Aplicar trim y formato Título (Title Case) cuando el nombre tiene espacios laterales
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-05')]
    #[Group('escenario_limite')]
    public function test_deberia_aplicarTrimYFormatoTitulo_cuando_nombreTieneEspaciosLaterales(): void
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
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos([
            'nombre' => '   trofeo mejor goleador   ',
            'tipo'   => ' I '
        ]));

        // Assert
        $reflector = new ReflectionClass($modelo);
        $propNombre = $reflector->getProperty('nombre');
        $propTipo = $reflector->getProperty('tipo');

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Trofeo Mejor Goleador', $propNombre->getValue($modelo));
        $this->assertEquals('I', $propTipo->getValue($modelo));
    }

    // Caso 06: Rechazar inclusión y revertir transacción cuando el nombre de premio ya existe (DUPLICATE_NAME)
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-06')]
    #[Group('escenario_error')]
    public function test_deberia_rechazarInclusion_cuando_nombreDePremioYaExiste(): void
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
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);
    }

    // Caso 07: Revertir transacción con rollback y retornar código de error cuando falla la inserción SQL
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-07')]
    #[Group('escenario_error')]
    public function test_deberia_revertirTransaccionYRetornarError_cuando_fallaInsercionSQL(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('bindParam')->willReturn(true);
        $stmtInsert->method('execute')->willThrowException(new PDOException('Error de comunicacion con motor SQL'));

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
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de comunicacion con motor SQL', $resultado['codigo']);
    }
}
