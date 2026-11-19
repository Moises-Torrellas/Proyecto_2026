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
#[Group('RF-03')]
#[Group('ESC-03')]
class ModificarEstadisticasTest extends TestCase
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
        $datos = [
            'accion'         => 'modificar',
            'id'             => 50,
            'participacion'  => 3,
            'atleta'         => 12,
            'goles'          => 6,
            'asistencias'    => 4,
            'penalizaciones' => 2,
            'goles_c'        => 1,
            'partido'        => 5,
            'average'        => 1.20
        ];

        return array_merge($datos, $sobrescribir);
    }

    // ==========================================
    // ESCENARIO 03: Modificación / Actualización de Estadísticas Deportivas
    // ==========================================

    // Caso 12: Modificación exitosa de estadísticas deportivas con commit transaccional
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-12')]
    public function testModificarEstadisticaExitosa(): void
    {
        // Arrange
        // 1. Verificar existencia de ID (detalles_participacion)
        $stmtExisteId = $this->createMock(PDOStatement::class);
        $stmtExisteId->method('execute')->willReturn(true);
        $stmtExisteId->method('fetchColumn')->willReturn(1);

        // 2. Verificar existencia de participación
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        // 3. Verificar existencia de atleta
        $stmtExisteAtl = $this->createMock(PDOStatement::class);
        $stmtExisteAtl->method('execute')->willReturn(true);
        $stmtExisteAtl->method('fetchColumn')->willReturn(1);

        // 4. Verificar duplicada propia
        $stmtDupPropia = $this->createMock(PDOStatement::class);
        $stmtDupPropia->method('bindValue')->willReturn(true);
        $stmtDupPropia->method('execute')->willReturn(true);
        $stmtDupPropia->method('fetchColumn')->willReturn(0);

        // 5. Update de detalles_participacion
        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindValue')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExisteId, $stmtExistePart, $stmtExisteAtl, $stmtDupPropia, $stmtUpdate
        ) {
            if (str_contains($sql, 'FROM `detalles_participacion`')) {
                return $stmtExisteId;
            }
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtExisteAtl;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'codigo_dtll_prtc !=')) {
                return $stmtDupPropia;
            }
            if (str_contains($sql, 'UPDATE detalles_participacion')) {
                return $stmtUpdate;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $mockParticipaciones = $this->createMock(ModeloParticipaciones::class);
        $mockParticipaciones->expects($this->once())
            ->method('validarParticipacionIndividual')
            ->with(3, 12)
            ->willReturn(true);

        $modelo = new ModeloEstadisticas();
        $modelo->setParticipaciones($mockParticipaciones);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 13: Rechazo de modificación cuando el ID de la estadística no existe (INVALID_ID . '2')
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-13')]
    public function testModificarEstadisticaIdInexistente(): void
    {
        // Arrange
        $stmtExisteId = $this->createMock(PDOStatement::class);
        $stmtExisteId->method('execute')->willReturn(true);
        $stmtExisteId->method('fetchColumn')->willReturn(0); // No existe el registro

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->with($this->stringContains('FROM `detalles_participacion`'))->willReturn($stmtExisteId);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '2', $resultado['codigo']);
    }

    // Caso 14: Rechazo de modificación cuando la participación no existe (INVALID_ID)
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-14')]
    public function testModificarEstadisticaParticipacionInexistente(): void
    {
        // Arrange
        $stmtExisteId = $this->createMock(PDOStatement::class);
        $stmtExisteId->method('execute')->willReturn(true);
        $stmtExisteId->method('fetchColumn')->willReturn(1); // ID existe

        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(0); // Participación no existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExisteId, $stmtExistePart) {
            if (str_contains($sql, 'FROM `detalles_participacion`')) {
                return $stmtExisteId;
            }
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 15: Rechazo de modificación cuando el atleta no existe (INVALID_ID . '1')
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-15')]
    public function testModificarEstadisticaAtletaInexistente(): void
    {
        // Arrange
        $stmtExisteId = $this->createMock(PDOStatement::class);
        $stmtExisteId->method('execute')->willReturn(true);
        $stmtExisteId->method('fetchColumn')->willReturn(1);

        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtExisteAtl = $this->createMock(PDOStatement::class);
        $stmtExisteAtl->method('execute')->willReturn(true);
        $stmtExisteAtl->method('fetchColumn')->willReturn(0); // Atleta no existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExisteId, $stmtExistePart, $stmtExisteAtl
        ) {
            if (str_contains($sql, 'FROM `detalles_participacion`')) {
                return $stmtExisteId;
            }
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
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '1', $resultado['codigo']);
    }

    // Caso 16: Rechazo de modificación si el atleta no perteneció a la participación (EMPTY_SELECTION)
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-16')]
    public function testModificarEstadisticaAtletaNoPertenecienteAParticipacion(): void
    {
        // Arrange
        $stmtExisteId = $this->createMock(PDOStatement::class);
        $stmtExisteId->method('execute')->willReturn(true);
        $stmtExisteId->method('fetchColumn')->willReturn(1);

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

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExisteId, $stmtExistePart, $stmtExisteAtl
        ) {
            if (str_contains($sql, 'FROM `detalles_participacion`')) {
                return $stmtExisteId;
            }
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            return $stmtExisteAtl;
        });

        $this->inyectarPdoMock($pdoMock);

        $mockParticipaciones = $this->createMock(ModeloParticipaciones::class);
        $mockParticipaciones->expects($this->once())
            ->method('validarParticipacionIndividual')
            ->with(3, 12)
            ->willReturn(false); // No perteneció

        $modelo = new ModeloEstadisticas();
        $modelo->setParticipaciones($mockParticipaciones);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(EMPTY_SELECTION, $resultado['codigo']);
    }

    // Caso 17: Rechazo de modificación por duplicidad contra otro registro del mismo atleta y torneo (DUPLICATE)
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-17')]
    public function testModificarEstadisticaDuplicadaOtroRegistro(): void
    {
        // Arrange
        $stmtExisteId = $this->createMock(PDOStatement::class);
        $stmtExisteId->method('execute')->willReturn(true);
        $stmtExisteId->method('fetchColumn')->willReturn(1);

        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtExisteAtl = $this->createMock(PDOStatement::class);
        $stmtExisteAtl->method('execute')->willReturn(true);
        $stmtExisteAtl->method('fetchColumn')->willReturn(1);

        $stmtDupPropia = $this->createMock(PDOStatement::class);
        $stmtDupPropia->method('bindValue')->willReturn(true);
        $stmtDupPropia->method('execute')->willReturn(true);
        $stmtDupPropia->method('fetchColumn')->willReturn(1); // Otro registro ya tiene esa combinación

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExisteId, $stmtExistePart, $stmtExisteAtl, $stmtDupPropia
        ) {
            if (str_contains($sql, 'FROM `detalles_participacion`')) {
                return $stmtExisteId;
            }
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtExisteAtl;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'codigo_dtll_prtc !=')) {
                return $stmtDupPropia;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $mockParticipaciones = $this->createMock(ModeloParticipaciones::class);
        $mockParticipaciones->method('validarParticipacionIndividual')->willReturn(true);

        $modelo = new ModeloEstadisticas();
        $modelo->setParticipaciones($mockParticipaciones);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE, $resultado['codigo']);
    }

    // Caso 18: Modificación exitosa conservando los mismos datos sin falso positivo de duplicidad propia
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-18')]
    public function testModificarEstadisticaSinFalsoPositivoDuplicadaPropia(): void
    {
        // Arrange
        $stmtExisteId = $this->createMock(PDOStatement::class);
        $stmtExisteId->method('execute')->willReturn(true);
        $stmtExisteId->method('fetchColumn')->willReturn(1);

        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtExisteAtl = $this->createMock(PDOStatement::class);
        $stmtExisteAtl->method('execute')->willReturn(true);
        $stmtExisteAtl->method('fetchColumn')->willReturn(1);

        $stmtDupPropia = $this->createMock(PDOStatement::class);
        $stmtDupPropia->method('bindValue')->willReturn(true);
        $stmtDupPropia->method('execute')->willReturn(true);
        $stmtDupPropia->method('fetchColumn')->willReturn(0); // Ningún OTRO registro coincide

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindValue')->willReturn(true);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExisteId, $stmtExistePart, $stmtExisteAtl, $stmtDupPropia, $stmtUpdate
        ) {
            if (str_contains($sql, 'FROM `detalles_participacion`')) {
                return $stmtExisteId;
            }
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtExisteAtl;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'codigo_dtll_prtc !=')) {
                return $stmtDupPropia;
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
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos([
            'goles' => 10,
            'average' => 2.00
        ]));

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 19: Error de base de datos durante el UPDATE con rollback transaccional
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-19')]
    public function testModificarEstadisticaErrorBaseDatos(): void
    {
        // Arrange
        $stmtExisteId = $this->createMock(PDOStatement::class);
        $stmtExisteId->method('execute')->willReturn(true);
        $stmtExisteId->method('fetchColumn')->willReturn(1);

        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtExisteAtl = $this->createMock(PDOStatement::class);
        $stmtExisteAtl->method('execute')->willReturn(true);
        $stmtExisteAtl->method('fetchColumn')->willReturn(1);

        $stmtDupPropia = $this->createMock(PDOStatement::class);
        $stmtDupPropia->method('bindValue')->willReturn(true);
        $stmtDupPropia->method('execute')->willReturn(true);
        $stmtDupPropia->method('fetchColumn')->willReturn(0);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('bindValue')->willReturn(true);
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Error de deadlock en modificación'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExisteId, $stmtExistePart, $stmtExisteAtl, $stmtDupPropia, $stmtUpdate
        ) {
            if (str_contains($sql, 'FROM `detalles_participacion`')) {
                return $stmtExisteId;
            }
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtExisteAtl;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'codigo_dtll_prtc !=')) {
                return $stmtDupPropia;
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
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de deadlock en modificación', $resultado['codigo']);
    }
}
