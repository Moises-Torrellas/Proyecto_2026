<?php

namespace tests\Unit\Torneos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloTorneos;
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
#[Group('unitTorneos')]
#[Group('RF-04')]
#[Group('ESC-04')]
class EliminarTorneosTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloTorneos
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloTorneos();
    }

    // Caso 21: Eliminación exitosa de un torneo sin equipos o atletas asociados
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-21')]
    public function testEliminarTorneoExitoso(): void
    {
        // Arrange
        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(1); // El torneo sí existe

        $stmtTieneEquipos = $this->createMock(PDOStatement::class);
        $stmtTieneEquipos->method('execute')->willReturn(true);
        $stmtTieneEquipos->method('fetchColumn')->willReturn(0); // No tiene equipos asociados

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExisteTorneo, $stmtTieneEquipos, $stmtDelete) {
            if (str_contains($sql, 'FROM `torneos`')) {
                return $stmtExisteTorneo;
            }
            if (str_contains($sql, 'FROM `equipos`')) {
                return $stmtTieneEquipos;
            }
            return $stmtDelete;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_torneo' => 10
        ]);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 22: Rechazo de eliminación cuando el torneo no existe en la base de datos
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-22')]
    public function testEliminarTorneoInexistenteRechazado(): void
    {
        // Arrange
        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(0); // El torneo no existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtExisteTorneo);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_torneo' => 999
        ]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El torneo no existe.', $resultado['codigo']);
    }

    // Caso 23: Rechazo de eliminación por integridad referencial (equipos o atletas asociados)
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-23')]
    public function testEliminarTorneoConEquiposAsociadosRechazado(): void
    {
        // Arrange
        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(1); // El torneo sí existe

        $stmtTieneEquipos = $this->createMock(PDOStatement::class);
        $stmtTieneEquipos->method('execute')->willReturn(true);
        $stmtTieneEquipos->method('fetchColumn')->willReturn(1); // SÍ tiene equipos asociados

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExisteTorneo, $stmtTieneEquipos) {
            if (str_contains($sql, 'FROM `torneos`')) {
                return $stmtExisteTorneo;
            }
            return $stmtTieneEquipos;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_torneo' => 10
        ]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('No se puede eliminar: el torneo tiene equipos o atletas asociados.', $resultado['codigo']);
    }

    // Caso 24: Captura de excepción en base de datos durante el DELETE
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-24')]
    public function testEliminarTorneoFalloBaseDeDatos(): void
    {
        // Arrange
        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(1);

        $stmtTieneEquipos = $this->createMock(PDOStatement::class);
        $stmtTieneEquipos->method('execute')->willReturn(true);
        $stmtTieneEquipos->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExisteTorneo, $stmtTieneEquipos) {
            if (str_contains($sql, 'FROM `torneos`')) {
                return $stmtExisteTorneo;
            }
            if (str_contains($sql, 'FROM `equipos`')) {
                return $stmtTieneEquipos;
            }
            throw new PDOException('Error de conexión simulado al eliminar torneo');
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'codigo_torneo' => 10
        ]);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error de conexión simulado', $resultado['codigo']);
    }
}

