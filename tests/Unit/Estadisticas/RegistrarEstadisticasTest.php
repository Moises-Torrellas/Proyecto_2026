<?php

namespace tests\Unit\Estadisticas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadisticas;
use App\modelo\ModeloParticipaciones;
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
#[Group('unitEstadisticas')]
#[Group('RF-02')]
#[Group('ESC-02')]
class RegistrarEstadisticasTest extends TestCase
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

    private function obtenerDatosRegistroValidos(array $sobrescribir = []): array
    {
        $datos = [
            'accion'         => 'incluir',
            'participacion'  => 2,
            'atleta'         => 10,
            'goles'          => 4,
            'asistencias'    => 2,
            'penalizaciones' => 1,
            'goles_c'        => 0,
            'partido'        => 3,
            'average'        => 1.33
        ];

        return array_merge($datos, $sobrescribir);
    }

    // ==========================================
    // ESCENARIO 02: Registro / Inclusión de Estadísticas Deportivas
    // ==========================================

    // Caso 06: Inclusión exitosa de estadísticas deportivas con commit transaccional y asignación de ID
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-06')]
    public function testIncluirEstadisticaExitosa(): void
    {
        // Arrange
        // 1. Verificar existencia de participación
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        // 2. Verificar existencia de atleta
        $stmtExisteAtl = $this->createMock(PDOStatement::class);
        $stmtExisteAtl->method('execute')->willReturn(true);
        $stmtExisteAtl->method('fetchColumn')->willReturn(1);

        // 3. Verificar estadística duplicada
        $stmtDup = $this->createMock(PDOStatement::class);
        $stmtDup->method('bindValue')->willReturn(true);
        $stmtDup->method('execute')->willReturn(true);
        $stmtDup->method('fetchColumn')->willReturn(0); // No duplicada

        // 4. Update de detalles_participacion
        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindValue')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        // 5. Select para obtener codigo_dtll_prtc asignado
        $stmtId = $this->createMock(PDOStatement::class);
        $stmtId->method('execute')->willReturn(true);
        $stmtId->method('fetchColumn')->willReturn(105);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePart, $stmtExisteAtl, $stmtDup, $stmtUpdate, $stmtId
        ) {
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtExisteAtl;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'estatus_estadistica = 1')) {
                return $stmtDup;
            }
            if (str_contains($sql, 'UPDATE detalles_participacion')) {
                return $stmtUpdate;
            }
            if (str_contains($sql, 'SELECT codigo_dtll_prtc FROM detalles_participacion')) {
                return $stmtId;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $mockParticipaciones = $this->createMock(ModeloParticipaciones::class);
        $mockParticipaciones->expects($this->once())
            ->method('validarParticipacionIndividual')
            ->with(2, 10)
            ->willReturn(true);

        $modelo = new ModeloEstadisticas();
        $modelo->setParticipaciones($mockParticipaciones);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosRegistroValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals(105, $modelo->getId());
    }

    // Caso 07: Rechazo de inclusión cuando la participación indicada no existe en el sistema (INVALID_ID)
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-07')]
    public function testIncluirEstadisticaParticipacionInexistente(): void
    {
        // Arrange
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(0); // No existe participación

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->with($this->stringContains('FROM `participaciones`'))->willReturn($stmtExistePart);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosRegistroValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 08: Rechazo de inclusión cuando el atleta indicado no existe en el sistema (INVALID_ID . '1')
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-08')]
    public function testIncluirEstadisticaAtletaInexistente(): void
    {
        // Arrange
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1); // Participación sí existe

        $stmtExisteAtl = $this->createMock(PDOStatement::class);
        $stmtExisteAtl->method('execute')->willReturn(true);
        $stmtExisteAtl->method('fetchColumn')->willReturn(0); // Atleta no existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistePart, $stmtExisteAtl) {
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtExisteAtl;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosRegistroValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '1', $resultado['codigo']);
    }

    // Caso 09: Rechazo de inclusión si el atleta no perteneció a la participación seleccionada (EMPTY_SELECTION)
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-09')]
    public function testIncluirEstadisticaAtletaNoPertenecienteAParticipacion(): void
    {
        // Arrange
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtExisteAtl = $this->createMock(PDOStatement::class);
        $stmtExisteAtl->method('execute')->willReturn(true);
        $stmtExisteAtl->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistePart, $stmtExisteAtl) {
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            return $stmtExisteAtl;
        });

        $this->inyectarPdoMock($pdoMock);

        $mockParticipaciones = $this->createMock(ModeloParticipaciones::class);
        $mockParticipaciones->expects($this->once())
            ->method('validarParticipacionIndividual')
            ->with(2, 10)
            ->willReturn(false); // No perteneció

        $modelo = new ModeloEstadisticas();
        $modelo->setParticipaciones($mockParticipaciones);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosRegistroValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(EMPTY_SELECTION, $resultado['codigo']);
    }

    // Caso 10: Rechazo de inclusión cuando ya existen estadísticas para ese atleta en la participación (DUPLICATE)
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-10')]
    public function testIncluirEstadisticaDuplicada(): void
    {
        // Arrange
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtExisteAtl = $this->createMock(PDOStatement::class);
        $stmtExisteAtl->method('execute')->willReturn(true);
        $stmtExisteAtl->method('fetchColumn')->willReturn(1);

        $stmtDup = $this->createMock(PDOStatement::class);
        $stmtDup->method('bindValue')->willReturn(true);
        $stmtDup->method('execute')->willReturn(true);
        $stmtDup->method('fetchColumn')->willReturn(1); // Ya existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePart, $stmtExisteAtl, $stmtDup
        ) {
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtExisteAtl;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'estatus_estadistica = 1')) {
                return $stmtDup;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $mockParticipaciones = $this->createMock(ModeloParticipaciones::class);
        $mockParticipaciones->method('validarParticipacionIndividual')->willReturn(true);

        $modelo = new ModeloEstadisticas();
        $modelo->setParticipaciones($mockParticipaciones);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosRegistroValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE, $resultado['codigo']);
    }

    // Caso 11: Error de base de datos durante el UPDATE con rollback transaccional
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-11')]
    public function testIncluirEstadisticaErrorBaseDatos(): void
    {
        // Arrange
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtExisteAtl = $this->createMock(PDOStatement::class);
        $stmtExisteAtl->method('execute')->willReturn(true);
        $stmtExisteAtl->method('fetchColumn')->willReturn(1);

        $stmtDup = $this->createMock(PDOStatement::class);
        $stmtDup->method('bindValue')->willReturn(true);
        $stmtDup->method('execute')->willReturn(true);
        $stmtDup->method('fetchColumn')->willReturn(0);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindValue')->willReturn(true);
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Fallo crítico al actualizar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePart, $stmtExisteAtl, $stmtDup, $stmtUpdate
        ) {
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtExisteAtl;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'estatus_estadistica = 1')) {
                return $stmtDup;
            }
            if (str_contains($sql, 'UPDATE detalles_participacion')) {
                return $stmtUpdate;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $mockParticipaciones = $this->createMock(ModeloParticipaciones::class);
        $mockParticipaciones->method('validarParticipacionIndividual')->willReturn(true);

        $modelo = new ModeloEstadisticas();
        $modelo->setParticipaciones($mockParticipaciones);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosRegistroValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Fallo crítico al actualizar', $resultado['codigo']);
    }
}
