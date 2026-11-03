<?php

namespace tests\Unit\Representantes;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRepresentantes;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitRepresentantes')]
#[Group('eliminarRepresentantes')]
class EliminarRepresentantesTest extends TestCase
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

    // Caso 1: Eliminación exitosa de un representante
    public function testEliminarExitoso(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtAsociado = $this->createMock(PDOStatement::class);
        $stmtAsociado->method('execute')->willReturn(true);
        $stmtAsociado->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtAsociado, $stmtDelete) {
            if (str_contains($sql, 'FROM `representantes`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'FROM `atleta_representante`')) {
                return $stmtAsociado;
            }
            return $stmtDelete;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '10'
        ]);

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 2: El representante a eliminar no existe
    public function testRepresentanteNoExiste(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExiste);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '99'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 3: El representante tiene atletas asociados
    public function testRepresentanteConAtletas(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtAsociado = $this->createMock(PDOStatement::class);
        $stmtAsociado->method('execute')->willReturn(true);
        $stmtAsociado->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtAsociado) {
            if (str_contains($sql, 'FROM `representantes`')) {
                return $stmtExiste;
            }
            return $stmtAsociado;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '10'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo']);
    }

    // Caso 4: Error en base de datos al ejecutar DELETE
    public function testErrorBaseDatos(): void
    {
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtAsociado = $this->createMock(PDOStatement::class);
        $stmtAsociado->method('execute')->willReturn(true);
        $stmtAsociado->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('execute')->willThrowException(new Exception('Error al eliminar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExiste, $stmtAsociado, $stmtDelete) {
            if (str_contains($sql, 'FROM `representantes`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'FROM `atleta_representante`')) {
                return $stmtAsociado;
            }
            return $stmtDelete;
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '10'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al eliminar', $resultado['codigo']);
    }
}
