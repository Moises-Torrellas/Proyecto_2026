<?php

declare(strict_types=1);

namespace tests\Unit\Palmares;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPalmares;
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
    define('DUPLICATE', '004');
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
#[Group('unitPalmares')]
#[Group('RF-04')]
#[Group('ESC-06')]
class EliminarPalmaresTest extends TestCase
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

    // ==========================================
    // ESCENARIO 06: Eliminación de Registros de Palmarés
    // ==========================================

    // Caso 27: Eliminación exitosa de un registro de palmarés individual
    #[Group('RF-04')]
    #[Group('ESC-06')]
    #[Group('CP-27')]
    #[Group('escenario_exito')]
    public function test_deberia_eliminarPalmaresIndividualExitosamente_cuando_idExiste(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtDel = $this->createMock(PDOStatement::class);
        $stmtDel->expects($this->once())->method('bindValue')->with(':id', 15, PDO::PARAM_INT)->willReturn(true);
        $stmtDel->expects($this->once())->method('execute')->willReturn(true);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtDel) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExiste;
            }
            return $stmtDel;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'id'            => 15,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 28: Rechazo de eliminación individual cuando el ID no existe en base de datos
    #[Group('RF-04')]
    #[Group('ESC-06')]
    #[Group('CP-28')]
    #[Group('escenario_error')]
    public function test_deberia_rechazarEliminacionIndividual_cuando_idNoExiste(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->with($this->stringContains('SELECT COUNT(*)'))->willReturn($stmtExiste);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'id'            => 999,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 29: Eliminación exitosa de un registro de palmarés grupal
    #[Group('RF-04')]
    #[Group('ESC-06')]
    #[Group('CP-29')]
    #[Group('escenario_exito')]
    public function test_deberia_eliminarPalmaresGrupalExitosamente_cuando_idExiste(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtDel = $this->createMock(PDOStatement::class);
        $stmtDel->expects($this->once())->method('bindValue')->with(':id', 30, PDO::PARAM_INT)->willReturn(true);
        $stmtDel->expects($this->once())->method('execute')->willReturn(true);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtDel) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExiste;
            }
            return $stmtDel;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'id'            => 30,
            'tipo_palmares' => 'grupal'
        ]);

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 30: Rechazo de eliminación grupal cuando el ID no existe en base de datos
    #[Group('RF-04')]
    #[Group('ESC-06')]
    #[Group('CP-30')]
    #[Group('escenario_error')]
    public function test_deberia_rechazarEliminacionGrupal_cuando_idNoExiste(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->with($this->stringContains('SELECT COUNT(*)'))->willReturn($stmtExiste);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'id'            => 999,
            'tipo_palmares' => 'grupal'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 31: Error de base de datos durante la eliminación con reversión transaccional
    #[Group('RF-04')]
    #[Group('ESC-06')]
    #[Group('CP-31')]
    #[Group('escenario_error')]
    public function test_deberia_revertirTransaccionYRetornarError_cuando_fallaEliminacionSQL(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtDel = $this->createMock(PDOStatement::class);
        $stmtDel->method('bindValue')->willReturn(true);
        $stmtDel->method('execute')->willThrowException(new PDOException('Fallo de clave foránea al eliminar'));

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtDel) {
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtExiste;
            }
            return $stmtDel;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'id'            => 15,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Fallo de clave foránea al eliminar', $resultado['codigo']);
    }
}
