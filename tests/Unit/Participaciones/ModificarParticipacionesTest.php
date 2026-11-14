<?php

namespace tests\Unit\Participaciones;

use PHPUnit\Framework\TestCase;
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
#[Group('unitParticipaciones')]
#[Group('RF-03')]
#[Group('ESC-03')]
class ModificarParticipacionesTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloParticipaciones
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloParticipaciones();
    }

    // Caso 17: Modificación exitosa de participación y regeneración de snapshot
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-17')]
    public function testModificarParticipacionExitosa(): void
    {
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtPrevio = $this->createMock(PDOStatement::class);
        $stmtPrevio->method('execute')->willReturn(true);
        $stmtPrevio->method('fetch')->willReturn([
            'torneo' => 'Torneo Apertura',
            'equipo' => 'Cannibals B'
        ]);

        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(1);

        $stmtExisteEquipo = $this->createMock(PDOStatement::class);
        $stmtExisteEquipo->method('execute')->willReturn(true);
        $stmtExisteEquipo->method('fetchColumn')->willReturn(1);

        $stmtCheckDuplicadoPropio = $this->createMock(PDOStatement::class);
        $stmtCheckDuplicadoPropio->method('execute')->willReturn(true);
        $stmtCheckDuplicadoPropio->method('fetchColumn')->willReturn(0); // No colisiona

        $stmtNuevoInfo = $this->createMock(PDOStatement::class);
        $stmtNuevoInfo->method('execute')->willReturn(true);
        $stmtNuevoInfo->method('fetch')->willReturn([
            'torneo' => 'Torneo Clausura',
            'equipo' => 'Cannibals A'
        ]);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $stmtDelSnap = $this->createMock(PDOStatement::class);
        $stmtDelSnap->method('execute')->willReturn(true);

        $stmtInsSnap = $this->createMock(PDOStatement::class);
        $stmtInsSnap->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtExistePart,
            $stmtPrevio,
            $stmtExisteTorneo,
            $stmtExisteEquipo,
            $stmtCheckDuplicadoPropio,
            $stmtNuevoInfo,
            $stmtUpdate,
            $stmtDelSnap,
            $stmtInsSnap
        ) {
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'WHERE p.codigo_participacion = :id')) {
                return $stmtPrevio;
            }
            if (str_contains($sql, 'FROM `torneos`')) {
                return $stmtExisteTorneo;
            }
            if (str_contains($sql, 'FROM `equipos`')) {
                return $stmtExisteEquipo;
            }
            if (str_contains($sql, 'codigo_participacion != :codigo_participacion')) {
                return $stmtCheckDuplicadoPropio;
            }
            if (str_contains($sql, 'WHERE t.codigo_torneo = :torneo AND e.codigo_equipo = :equipo')) {
                return $stmtNuevoInfo;
            }
            if (str_contains($sql, 'UPDATE participaciones')) {
                return $stmtUpdate;
            }
            if (str_contains($sql, 'DELETE FROM detalles_participacion')) {
                return $stmtDelSnap;
            }
            return $stmtInsSnap;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'               => 'modificar',
            'codigo_participacion' => 10,
            'codigo_torneo'        => 2,
            'codigo_equipo'        => 6
        ]);

        $this->assertEquals('exito', $resultado['accion']);
        $previo = json_decode($resultado['previo'], true);
        $nuevo = json_decode($resultado['nuevo'], true);
        $this->assertEquals('Torneo Apertura', $previo['torneo']);
        $this->assertEquals('Torneo Clausura', $nuevo['torneo']);
    }

    // Caso 18: Rechazo de modificación cuando la participación no existe
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-18')]
    public function testModificarParticipacionInexistenteRechazada(): void
    {
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(0); // No existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExistePart);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'               => 'modificar',
            'codigo_participacion' => 999,
            'codigo_torneo'        => 2,
            'codigo_equipo'        => 6
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '2', $resultado['codigo']);
    }

    // Caso 19: Rechazo de modificación cuando el nuevo torneo no existe
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-19')]
    public function testModificarTorneoInexistenteRechazado(): void
    {
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtPrevio = $this->createMock(PDOStatement::class);
        $stmtPrevio->method('execute')->willReturn(true);
        $stmtPrevio->method('fetch')->willReturn(['torneo' => 'T1', 'equipo' => 'E1']);

        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(0); // Torneo no existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExistePart, $stmtPrevio, $stmtExisteTorneo) {
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'WHERE p.codigo_participacion = :id')) {
                return $stmtPrevio;
            }
            return $stmtExisteTorneo;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'               => 'modificar',
            'codigo_participacion' => 10,
            'codigo_torneo'        => 999,
            'codigo_equipo'        => 6
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 20: Rechazo de modificación cuando el nuevo equipo no existe
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-20')]
    public function testModificarEquipoInexistenteRechazado(): void
    {
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtPrevio = $this->createMock(PDOStatement::class);
        $stmtPrevio->method('execute')->willReturn(true);
        $stmtPrevio->method('fetch')->willReturn(['torneo' => 'T1', 'equipo' => 'E1']);

        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(1);

        $stmtExisteEquipo = $this->createMock(PDOStatement::class);
        $stmtExisteEquipo->method('execute')->willReturn(true);
        $stmtExisteEquipo->method('fetchColumn')->willReturn(0); // Equipo no existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtExistePart,
            $stmtPrevio,
            $stmtExisteTorneo,
            $stmtExisteEquipo
        ) {
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'WHERE p.codigo_participacion = :id')) {
                return $stmtPrevio;
            }
            if (str_contains($sql, 'FROM `torneos`')) {
                return $stmtExisteTorneo;
            }
            return $stmtExisteEquipo;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'               => 'modificar',
            'codigo_participacion' => 10,
            'codigo_torneo'        => 2,
            'codigo_equipo'        => 888
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '0', $resultado['codigo']);
    }

    // Caso 21: Rechazo de modificación cuando el par torneo/equipo ya está registrado en otra participación
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-21')]
    public function testModificarParticipacionDuplicadaEnOtroRegistroRechazada(): void
    {
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtPrevio = $this->createMock(PDOStatement::class);
        $stmtPrevio->method('execute')->willReturn(true);
        $stmtPrevio->method('fetch')->willReturn(['torneo' => 'T1', 'equipo' => 'E1']);

        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(1);

        $stmtExisteEquipo = $this->createMock(PDOStatement::class);
        $stmtExisteEquipo->method('execute')->willReturn(true);
        $stmtExisteEquipo->method('fetchColumn')->willReturn(1);

        $stmtCheckDuplicadoPropio = $this->createMock(PDOStatement::class);
        $stmtCheckDuplicadoPropio->method('execute')->willReturn(true);
        $stmtCheckDuplicadoPropio->method('fetchColumn')->willReturn(1); // Duplicado en otro registro

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtExistePart,
            $stmtPrevio,
            $stmtExisteTorneo,
            $stmtExisteEquipo,
            $stmtCheckDuplicadoPropio
        ) {
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'WHERE p.codigo_participacion = :id')) {
                return $stmtPrevio;
            }
            if (str_contains($sql, 'FROM `torneos`')) {
                return $stmtExisteTorneo;
            }
            if (str_contains($sql, 'FROM `equipos`')) {
                return $stmtExisteEquipo;
            }
            return $stmtCheckDuplicadoPropio;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'               => 'modificar',
            'codigo_participacion' => 10,
            'codigo_torneo'        => 2,
            'codigo_equipo'        => 6
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE, $resultado['codigo']);
    }

    // Caso 22: Captura de excepción en base de datos durante UPDATE con reversión
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-22')]
    public function testModificarFalloBaseDeDatosRollback(): void
    {
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtPrevio = $this->createMock(PDOStatement::class);
        $stmtPrevio->method('execute')->willReturn(true);
        $stmtPrevio->method('fetch')->willReturn(['torneo' => 'T1', 'equipo' => 'E1']);

        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(1);

        $stmtExisteEquipo = $this->createMock(PDOStatement::class);
        $stmtExisteEquipo->method('execute')->willReturn(true);
        $stmtExisteEquipo->method('fetchColumn')->willReturn(1);

        $stmtCheckDuplicadoPropio = $this->createMock(PDOStatement::class);
        $stmtCheckDuplicadoPropio->method('execute')->willReturn(true);
        $stmtCheckDuplicadoPropio->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtExistePart,
            $stmtPrevio,
            $stmtExisteTorneo,
            $stmtExisteEquipo,
            $stmtCheckDuplicadoPropio
        ) {
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'WHERE p.codigo_participacion = :id')) {
                return $stmtPrevio;
            }
            if (str_contains($sql, 'FROM `torneos`')) {
                return $stmtExisteTorneo;
            }
            if (str_contains($sql, 'FROM `equipos`')) {
                return $stmtExisteEquipo;
            }
            if (str_contains($sql, 'codigo_participacion != :codigo_participacion')) {
                return $stmtCheckDuplicadoPropio;
            }
            throw new PDOException('Error simulado al actualizar participación');
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'               => 'modificar',
            'codigo_participacion' => 10,
            'codigo_torneo'        => 2,
            'codigo_equipo'        => 6
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error simulado al actualizar participación', $resultado['codigo']);
    }
}

