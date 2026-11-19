<?php

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
    require_once dirname(__DIR__, 3) . '/config/config.php';
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

    // Caso 25: Eliminación exitosa de un registro de palmarés individual
    #[Group('RF-04')]
    #[Group('ESC-06')]
    #[Group('CP-25')]
    public function testEliminarPalmaresIndividualExitoso(): void
    {
        $stmtDel = $this->createMock(PDOStatement::class);
        $stmtDel->expects($this->once())->method('bindValue')->with(':id', 15, PDO::PARAM_INT)->willReturn(true);
        $stmtDel->expects($this->once())->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM palmares_individual WHERE codigo_individual = :id'))
            ->willReturn($stmtDel);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'id'            => 15,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 26: Eliminación exitosa de un registro de palmarés grupal
    #[Group('RF-04')]
    #[Group('ESC-06')]
    #[Group('CP-26')]
    public function testEliminarPalmaresGrupalExitoso(): void
    {
        $stmtDel = $this->createMock(PDOStatement::class);
        $stmtDel->expects($this->once())->method('bindValue')->with(':id', 30, PDO::PARAM_INT)->willReturn(true);
        $stmtDel->expects($this->once())->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('DELETE FROM palmares_grupal WHERE codigo_grupal = :id'))
            ->willReturn($stmtDel);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'id'            => 30,
            'tipo_palmares' => 'grupal'
        ]);

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 27: Error de base de datos durante la eliminación con reversión transaccional
    #[Group('RF-04')]
    #[Group('ESC-06')]
    #[Group('CP-27')]
    public function testEliminarPalmaresFalloBDConRollback(): void
    {
        $stmtDel = $this->createMock(PDOStatement::class);
        $stmtDel->method('bindValue')->willReturn(true);
        $stmtDel->method('execute')->willThrowException(new PDOException('Fallo de clave foránea al eliminar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturn($stmtDel);

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
