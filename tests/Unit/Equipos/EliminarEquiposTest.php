<?php

namespace tests\Unit\Equipos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEquipos;
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
#[Group('unitEquipos')]
#[Group('RF-04')]
#[Group('ESC-04')]
class EliminarEquiposTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloEquipos
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloEquipos();
    }

    // Caso 23: Eliminación exitosa de un equipo sin participaciones en torneos
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-23')]
    public function testEliminarEquipoSinParticipacionesExitoso(): void
    {
        $stmtExisteEquipo = $this->createMock(PDOStatement::class);
        $stmtExisteEquipo->method('execute')->willReturn(true);
        $stmtExisteEquipo->method('fetchColumn')->willReturn(1); // El equipo existe

        $stmtTieneParticipaciones = $this->createMock(PDOStatement::class);
        $stmtTieneParticipaciones->method('execute')->willReturn(true);
        $stmtTieneParticipaciones->method('fetchColumn')->willReturn(0); // Sin participaciones

        $stmtDelDetalles = $this->createMock(PDOStatement::class);
        $stmtDelDetalles->method('execute')->willReturn(true);

        $stmtDelEquipo = $this->createMock(PDOStatement::class);
        $stmtDelEquipo->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtExisteEquipo,
            $stmtTieneParticipaciones,
            $stmtDelDetalles,
            $stmtDelEquipo
        ) {
            if (str_contains($sql, 'FROM `equipos`')) {
                return $stmtExisteEquipo;
            }
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtTieneParticipaciones;
            }
            if (str_contains($sql, 'DELETE FROM detalles_equipos')) {
                return $stmtDelDetalles;
            }
            return $stmtDelEquipo;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => 10
        ]);

        $this->assertEquals('eliminar', $resultado['accion']);
        $this->assertEquals('Equipo eliminado correctamente.', $resultado['mensaje']);
    }

    // Caso 24: Rechazo de eliminación cuando el equipo no existe en la base de datos
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-24')]
    public function testEliminarEquipoInexistenteRechazado(): void
    {
        $stmtExisteEquipo = $this->createMock(PDOStatement::class);
        $stmtExisteEquipo->method('execute')->willReturn(true);
        $stmtExisteEquipo->method('fetchColumn')->willReturn(0); // No existe el equipo

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExisteEquipo);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => 999
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['mensaje']);
    }

    // Caso 25: Rechazo de eliminación por integridad referencial (participaciones en torneos)
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-25')]
    public function testEliminarEquipoConParticipacionesAsociadasRechazado(): void
    {
        $stmtExisteEquipo = $this->createMock(PDOStatement::class);
        $stmtExisteEquipo->method('execute')->willReturn(true);
        $stmtExisteEquipo->method('fetchColumn')->willReturn(1); // El equipo existe

        $stmtTieneParticipaciones = $this->createMock(PDOStatement::class);
        $stmtTieneParticipaciones->method('execute')->willReturn(true);
        $stmtTieneParticipaciones->method('fetchColumn')->willReturn(1); // SÍ tiene participaciones

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExisteEquipo, $stmtTieneParticipaciones) {
            if (str_contains($sql, 'FROM `equipos`')) {
                return $stmtExisteEquipo;
            }
            return $stmtTieneParticipaciones;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => 10
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['mensaje']);
    }

    // Caso 26: Captura de excepción en base de datos durante el DELETE con reversión
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-26')]
    public function testEliminarEquipoFalloBaseDeDatosRollback(): void
    {
        $stmtExisteEquipo = $this->createMock(PDOStatement::class);
        $stmtExisteEquipo->method('execute')->willReturn(true);
        $stmtExisteEquipo->method('fetchColumn')->willReturn(1);

        $stmtTieneParticipaciones = $this->createMock(PDOStatement::class);
        $stmtTieneParticipaciones->method('execute')->willReturn(true);
        $stmtTieneParticipaciones->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExisteEquipo, $stmtTieneParticipaciones) {
            if (str_contains($sql, 'FROM `equipos`')) {
                return $stmtExisteEquipo;
            }
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtTieneParticipaciones;
            }
            throw new PDOException('Error simulado al eliminar detalles del equipo');
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => 10
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error simulado al eliminar detalles del equipo', $resultado['mensaje']);
    }
}

