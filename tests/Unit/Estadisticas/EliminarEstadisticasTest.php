<?php

declare(strict_types=1);

namespace tests\Unit\Estadisticas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadisticas;
use App\modelo\Conexion;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    $configPath = dirname(__DIR__, 3) . '/config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
}

if (!defined('DUPLICATE')) {
    define('DUPLICATE', '010');
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
if (!defined('EMPTY_SELECTION')) {
    define('EMPTY_SELECTION', '009');
}
if (!defined('DB_CONNECTION')) {
    define('DB_CONNECTION', '500');
}

#[Group('unit')]
#[Group('unitEstadisticas')]
#[Group('RF-04')]
#[Group('ESC-04')]
class EliminarEstadisticasTest extends TestCase
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

    // ==========================================
    // ESCENARIO 04: Eliminación / Desactivación de Estadísticas Deportivas
    // ==========================================

    // Caso 20: Eliminación lógica exitosa reseteando valores a 0 y estatus a 0 con commit
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-20')]
    #[Group('escenario_exito')]
    public function test_deberia_eliminarEstadisticaExitosamente_cuando_idExiste(): void
    {
        // Arrange
        // 1. Verificar existencia de ID
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        // 2. Ejecutar UPDATE de eliminación lógica
        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindValue')->with(':id', 25, PDO::PARAM_INT)->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtUpdate) {
            if (str_contains($sql, 'FROM `detalles_participacion`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'UPDATE detalles_participacion SET') && str_contains($sql, 'estatus_estadistica = 0')) {
                return $stmtUpdate;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => 25
        ]);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 21: Rechazo de eliminación cuando el registro de estadísticas no existe (INVALID_ID)
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-21')]
    #[Group('escenario_error')]
    public function test_deberia_rechazarEliminacion_cuando_idNoExiste(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0); // No existe el registro

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->with($this->stringContains('FROM `detalles_participacion`'))->willReturn($stmtExiste);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => 999
        ]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 22: Error de base de datos durante el UPDATE con rollback transaccional
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-22')]
    #[Group('escenario_error')]
    public function test_deberia_revertirTransaccionYRetornarError_cuando_fallaSentenciaSQLDeEliminacion(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindValue')->willReturn(true);
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Fallo al ejecutar eliminación en BD'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtUpdate) {
            if (str_contains($sql, 'FROM `detalles_participacion`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'UPDATE detalles_participacion SET')) {
                return $stmtUpdate;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => 40
        ]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Fallo al ejecutar eliminación en BD', $resultado['codigo']);
    }
}
