<?php

namespace tests\Unit\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
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
#[Group('unitPremios')]
#[Group('RF-04')]
#[Group('ESC-04')]
class EliminarPremiosTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloPremios
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloPremios();
    }

    // Caso 15: Eliminación exitosa de un premio no vinculado a ningún palmarés
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-15')]
    public function testEliminarPremioExitoso(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1); // El premio existe

        $stmtPalmaresGrupal = $this->createMock(PDOStatement::class);
        $stmtPalmaresGrupal->method('bindValue')->willReturn(true);
        $stmtPalmaresGrupal->method('execute')->willReturn(true);
        $stmtPalmaresGrupal->method('fetchColumn')->willReturn(0); // Sin palmarés grupal

        $stmtPalmaresIndiv = $this->createMock(PDOStatement::class);
        $stmtPalmaresIndiv->method('bindValue')->willReturn(true);
        $stmtPalmaresIndiv->method('execute')->willReturn(true);
        $stmtPalmaresIndiv->method('fetchColumn')->willReturn(0); // Sin palmarés individual

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('bindParam')->willReturn(true);
        $stmtDelete->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExiste, $stmtPalmaresGrupal, $stmtPalmaresIndiv, $stmtDelete
        ) {
            if (str_contains($sql, 'FROM `premios`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'FROM `palmares_grupal`')) {
                return $stmtPalmaresGrupal;
            }
            if (str_contains($sql, 'FROM `palmares_individual`')) {
                return $stmtPalmaresIndiv;
            }
            return $stmtDelete;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_premio' => 5
        ]);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 16: Rechazo de eliminación cuando el código de premio no existe (INVALID_ID)
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-16')]
    public function testEliminarPremioInexistenteRechazado(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0); // No existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->with($this->stringContains('FROM `premios`'))->willReturn($stmtExiste);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_premio' => 999
        ]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 17: Rechazo de eliminación cuando el premio está vinculado a palmarés grupal (ASSOCIATES)
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-17')]
    public function testEliminarPremioAsociadoPalmaresGrupalRechazado(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1); // Existe

        $stmtPalmaresGrupal = $this->createMock(PDOStatement::class);
        $stmtPalmaresGrupal->method('bindValue')->willReturn(true);
        $stmtPalmaresGrupal->method('execute')->willReturn(true);
        $stmtPalmaresGrupal->method('fetchColumn')->willReturn(1); // Vinculado a palmarés grupal

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExiste, $stmtPalmaresGrupal
        ) {
            if (str_contains($sql, 'FROM `premios`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'FROM `palmares_grupal`')) {
                return $stmtPalmaresGrupal;
            }
            return $this->createMock(PDOStatement::class);
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_premio' => 1
        ]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo']);
    }

    // Caso 18: Rechazo de eliminación cuando el premio está vinculado a palmarés individual (ASSOCIATES)
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-18')]
    public function testEliminarPremioAsociadoPalmaresIndividualRechazado(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1); // Existe

        $stmtPalmaresGrupal = $this->createMock(PDOStatement::class);
        $stmtPalmaresGrupal->method('bindValue')->willReturn(true);
        $stmtPalmaresGrupal->method('execute')->willReturn(true);
        $stmtPalmaresGrupal->method('fetchColumn')->willReturn(0);

        $stmtPalmaresIndiv = $this->createMock(PDOStatement::class);
        $stmtPalmaresIndiv->method('bindValue')->willReturn(true);
        $stmtPalmaresIndiv->method('execute')->willReturn(true);
        $stmtPalmaresIndiv->method('fetchColumn')->willReturn(1); // Vinculado a palmarés individual

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExiste, $stmtPalmaresGrupal, $stmtPalmaresIndiv
        ) {
            if (str_contains($sql, 'FROM `premios`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'FROM `palmares_grupal`')) {
                return $stmtPalmaresGrupal;
            }
            if (str_contains($sql, 'FROM `palmares_individual`')) {
                return $stmtPalmaresIndiv;
            }
            return $this->createMock(PDOStatement::class);
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_premio' => 3
        ]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo']);
    }

    // Caso 19: Error de base de datos durante DELETE con rollback transaccional
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-19')]
    public function testEliminarPremioErrorBaseDeDatosConRollback(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('bindValue')->willReturn(true);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtPalmaresGrupal = $this->createMock(PDOStatement::class);
        $stmtPalmaresGrupal->method('bindValue')->willReturn(true);
        $stmtPalmaresGrupal->method('execute')->willReturn(true);
        $stmtPalmaresGrupal->method('fetchColumn')->willReturn(0);

        $stmtPalmaresIndiv = $this->createMock(PDOStatement::class);
        $stmtPalmaresIndiv->method('bindValue')->willReturn(true);
        $stmtPalmaresIndiv->method('execute')->willReturn(true);
        $stmtPalmaresIndiv->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('bindParam')->willReturn(true);
        $stmtDelete->method('execute')->willThrowException(new PDOException('Error de integridad al eliminar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExiste, $stmtPalmaresGrupal, $stmtPalmaresIndiv, $stmtDelete
        ) {
            if (str_contains($sql, 'FROM `premios`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'FROM `palmares_grupal`')) {
                return $stmtPalmaresGrupal;
            }
            if (str_contains($sql, 'FROM `palmares_individual`')) {
                return $stmtPalmaresIndiv;
            }
            return $stmtDelete;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_premio' => 2
        ]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error de integridad al eliminar', $resultado['codigo']);
    }
}
