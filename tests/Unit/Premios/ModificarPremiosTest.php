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

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloPremios
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloPremios();
    }

    private function obtenerDatosModificarValidos(array $sobrescribir = []): array
    {
        $datos = [
            'accion'        => 'modificar',
            'codigo_premio' => 1,
            'nombre'        => 'Campeón Absoluto',
            'tipo'          => 'G'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 08: Modificación exitosa actualizando el nombre sin conflicto y manteniendo el tipo
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-08')]
    public function testModificarPremioConNuevoNombreExitoso(): void
    {
        // Arrange
        $stmtExistePropia = $this->createMock(PDOStatement::class);
        $stmtExistePropia->method('bindValue')->willReturn(true);
        $stmtExistePropia->method('execute')->willReturn(true);
        $stmtExistePropia->method('fetchColumn')->willReturn(0); // El nombre cambió

        $stmtExisteGlobal = $this->createMock(PDOStatement::class);
        $stmtExisteGlobal->method('bindValue')->willReturn(true);
        $stmtExisteGlobal->method('execute')->willReturn(true);
        $stmtExisteGlobal->method('fetchColumn')->willReturn(0); // Ningún otro premio tiene ese nombre

        $stmtTipo = $this->createMock(PDOStatement::class);
        $stmtTipo->method('execute')->willReturn(true);
        $stmtTipo->method('fetchColumn')->willReturn('G'); // Mismo tipo, no cambia

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePropia, $stmtExisteGlobal, $stmtTipo, $stmtUpdate
        ) {
            if (str_contains($sql, 'codigo_premio = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistePropia;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'premios')) {
                return $stmtExisteGlobal;
            }
            if (str_contains($sql, 'SELECT tipo FROM premios')) {
                return $stmtTipo;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 09: Modificación exitosa conservando su propio nombre
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-09')]
    public function testModificarPremioConservandoMismoNombreExitoso(): void
    {
        // Arrange
        $stmtExistePropia = $this->createMock(PDOStatement::class);
        $stmtExistePropia->method('bindValue')->willReturn(true);
        $stmtExistePropia->method('execute')->willReturn(true);
        $stmtExistePropia->method('fetchColumn')->willReturn(1); // Es el mismo nombre propio

        $stmtTipo = $this->createMock(PDOStatement::class);
        $stmtTipo->method('execute')->willReturn(true);
        $stmtTipo->method('fetchColumn')->willReturn('G');

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePropia, $stmtTipo, $stmtUpdate
        ) {
            if (str_contains($sql, 'codigo_premio = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistePropia;
            }
            if (str_contains($sql, 'SELECT tipo FROM premios')) {
                return $stmtTipo;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 10: Rechazo de modificación por nombre duplicado perteneciente a otro premio (DUPLICATE_NAME)
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-10')]
    public function testModificarPremioNombreDuplicadoRechazado(): void
    {
        // Arrange
        $stmtExistePropia = $this->createMock(PDOStatement::class);
        $stmtExistePropia->method('bindValue')->willReturn(true);
        $stmtExistePropia->method('execute')->willReturn(true);
        $stmtExistePropia->method('fetchColumn')->willReturn(0); // El nombre es distinto al actual

        $stmtExisteGlobal = $this->createMock(PDOStatement::class);
        $stmtExisteGlobal->method('bindValue')->willReturn(true);
        $stmtExisteGlobal->method('execute')->willReturn(true);
        $stmtExisteGlobal->method('fetchColumn')->willReturn(1); // Ya existe en otro registro

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePropia, $stmtExisteGlobal
        ) {
            if (str_contains($sql, 'codigo_premio = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistePropia;
            }
            return $stmtExisteGlobal;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE_NAME, $resultado['codigo']);
    }

    // Caso 11: Rechazo de cambio de tipo cuando el premio está asignado a palmarés grupal (TYPE_ASSOCIATED)
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-11')]
    public function testModificarTipoPremioAsociadoPalmaresGrupalRechazado(): void
    {
        // Arrange
        $stmtExistePropia = $this->createMock(PDOStatement::class);
        $stmtExistePropia->method('bindValue')->willReturn(true);
        $stmtExistePropia->method('execute')->willReturn(true);
        $stmtExistePropia->method('fetchColumn')->willReturn(1);

        $stmtTipo = $this->createMock(PDOStatement::class);
        $stmtTipo->method('execute')->willReturn(true);
        $stmtTipo->method('fetchColumn')->willReturn('G'); // Tipo actual es Grupal

        $stmtPalmaresGrupal = $this->createMock(PDOStatement::class);
        $stmtPalmaresGrupal->method('bindValue')->willReturn(true);
        $stmtPalmaresGrupal->method('execute')->willReturn(true);
        $stmtPalmaresGrupal->method('fetchColumn')->willReturn(1); // Asociado en palmarés grupal

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePropia, $stmtTipo, $stmtPalmaresGrupal
        ) {
            if (str_contains($sql, 'codigo_premio = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistePropia;
            }
            if (str_contains($sql, 'SELECT tipo FROM premios')) {
                return $stmtTipo;
            }
            if (str_contains($sql, 'FROM `palmares_grupal`')) {
                return $stmtPalmaresGrupal;
            }
            return $this->createMock(PDOStatement::class);
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Se intenta cambiar de 'G' a 'I'
        $datos = $this->obtenerDatosModificarValidos(['tipo' => 'I']);

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('TYPE_ASSOCIATED', $resultado['codigo']);
    }

    // Caso 12: Rechazo de cambio de tipo cuando el premio está asignado a palmarés individual (TYPE_ASSOCIATED)
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-12')]
    public function testModificarTipoPremioAsociadoPalmaresIndividualRechazado(): void
    {
        // Arrange
        $stmtExistePropia = $this->createMock(PDOStatement::class);
        $stmtExistePropia->method('bindValue')->willReturn(true);
        $stmtExistePropia->method('execute')->willReturn(true);
        $stmtExistePropia->method('fetchColumn')->willReturn(1);

        $stmtTipo = $this->createMock(PDOStatement::class);
        $stmtTipo->method('execute')->willReturn(true);
        $stmtTipo->method('fetchColumn')->willReturn('I'); // Tipo actual es Individual

        $stmtPalmaresGrupal = $this->createMock(PDOStatement::class);
        $stmtPalmaresGrupal->method('bindValue')->willReturn(true);
        $stmtPalmaresGrupal->method('execute')->willReturn(true);
        $stmtPalmaresGrupal->method('fetchColumn')->willReturn(0);

        $stmtPalmaresIndiv = $this->createMock(PDOStatement::class);
        $stmtPalmaresIndiv->method('bindValue')->willReturn(true);
        $stmtPalmaresIndiv->method('execute')->willReturn(true);
        $stmtPalmaresIndiv->method('fetchColumn')->willReturn(1); // Asociado en palmarés individual

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePropia, $stmtTipo, $stmtPalmaresGrupal, $stmtPalmaresIndiv
        ) {
            if (str_contains($sql, 'codigo_premio = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistePropia;
            }
            if (str_contains($sql, 'SELECT tipo FROM premios')) {
                return $stmtTipo;
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

        // Se intenta cambiar de 'I' a 'G'
        $datos = $this->obtenerDatosModificarValidos(['tipo' => 'G']);

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('TYPE_ASSOCIATED', $resultado['codigo']);
    }

    // Caso 13: Modificación exitosa cambiando tipo cuando no está asociado a ningún palmarés
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-13')]
    public function testModificarTipoPremioSinAsociacionesExitoso(): void
    {
        // Arrange
        $stmtExistePropia = $this->createMock(PDOStatement::class);
        $stmtExistePropia->method('bindValue')->willReturn(true);
        $stmtExistePropia->method('execute')->willReturn(true);
        $stmtExistePropia->method('fetchColumn')->willReturn(1);

        $stmtTipo = $this->createMock(PDOStatement::class);
        $stmtTipo->method('execute')->willReturn(true);
        $stmtTipo->method('fetchColumn')->willReturn('I'); // Era Individual

        $stmtPalmaresGrupal = $this->createMock(PDOStatement::class);
        $stmtPalmaresGrupal->method('bindValue')->willReturn(true);
        $stmtPalmaresGrupal->method('execute')->willReturn(true);
        $stmtPalmaresGrupal->method('fetchColumn')->willReturn(0);

        $stmtPalmaresIndiv = $this->createMock(PDOStatement::class);
        $stmtPalmaresIndiv->method('bindValue')->willReturn(true);
        $stmtPalmaresIndiv->method('execute')->willReturn(true);
        $stmtPalmaresIndiv->method('fetchColumn')->willReturn(0);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePropia, $stmtTipo, $stmtPalmaresGrupal, $stmtPalmaresIndiv, $stmtUpdate
        ) {
            if (str_contains($sql, 'codigo_premio = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistePropia;
            }
            if (str_contains($sql, 'SELECT tipo FROM premios')) {
                return $stmtTipo;
            }
            if (str_contains($sql, 'FROM `palmares_grupal`')) {
                return $stmtPalmaresGrupal;
            }
            if (str_contains($sql, 'FROM `palmares_individual`')) {
                return $stmtPalmaresIndiv;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Cambiando a 'G'
        $datos = $this->obtenerDatosModificarValidos(['tipo' => 'G']);

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 14: Error de base de datos durante UPDATE con rollback
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-14')]
    public function testModificarPremioErrorBaseDeDatosConRollback(): void
    {
        // Arrange
        $stmtExistePropia = $this->createMock(PDOStatement::class);
        $stmtExistePropia->method('bindValue')->willReturn(true);
        $stmtExistePropia->method('execute')->willReturn(true);
        $stmtExistePropia->method('fetchColumn')->willReturn(1);

        $stmtTipo = $this->createMock(PDOStatement::class);
        $stmtTipo->method('execute')->willReturn(true);
        $stmtTipo->method('fetchColumn')->willReturn('G');

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindParam')->willReturn(true);
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Error al actualizar registro'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePropia, $stmtTipo, $stmtUpdate
        ) {
            if (str_contains($sql, 'codigo_premio = :id') && str_contains($sql, 'nombre = :valor')) {
                return $stmtExistePropia;
            }
            if (str_contains($sql, 'SELECT tipo FROM premios')) {
                return $stmtTipo;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error al actualizar registro', $resultado['codigo']);
    }
}
