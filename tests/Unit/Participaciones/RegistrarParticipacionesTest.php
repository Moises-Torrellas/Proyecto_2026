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
#[Group('RF-02')]
#[Group('ESC-02')]
class RegistrarParticipacionesTest extends TestCase
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

    // Caso 12: Inclusión exitosa de participación y generación de snapshot de atletas
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-12')]
    public function testIncluirParticipacionExitosa(): void
    {
        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(1);

        $stmtExisteEquipo = $this->createMock(PDOStatement::class);
        $stmtExisteEquipo->method('execute')->willReturn(true);
        $stmtExisteEquipo->method('fetchColumn')->willReturn(1);

        $stmtCheckGrupal = $this->createMock(PDOStatement::class);
        $stmtCheckGrupal->method('execute')->willReturn(true);
        $stmtCheckGrupal->method('fetchColumn')->willReturn(0); // No duplicado

        $stmtInfo = $this->createMock(PDOStatement::class);
        $stmtInfo->method('execute')->willReturn(true);
        $stmtInfo->method('fetch')->willReturn([
            'torneo' => 'Copa Oro 2026',
            'equipo' => 'Cannibals Elite'
        ]);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $stmtSnapshot = $this->createMock(PDOStatement::class);
        $stmtSnapshot->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('lastInsertId')->willReturn('101');
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtExisteTorneo,
            $stmtExisteEquipo,
            $stmtCheckGrupal,
            $stmtInfo,
            $stmtInsert,
            $stmtSnapshot
        ) {
            if (str_contains($sql, 'FROM `torneos`')) {
                return $stmtExisteTorneo;
            }
            if (str_contains($sql, 'FROM `equipos`')) {
                return $stmtExisteEquipo;
            }
            if (str_contains($sql, 'FROM participaciones WHERE codigo_torneo')) {
                return $stmtCheckGrupal;
            }
            if (str_contains($sql, 'JOIN equipos e WHERE t.codigo_torneo')) {
                return $stmtInfo;
            }
            if (str_contains($sql, 'INSERT INTO participaciones')) {
                return $stmtInsert;
            }
            return $stmtSnapshot;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'codigo_torneo' => 1,
            'codigo_equipo' => 5
        ]);

        $this->assertEquals('exito', $resultado['accion']);
        $nuevo = json_decode($resultado['nuevo'], true);
        $this->assertEquals('Copa Oro 2026', $nuevo['torneo']);
        $this->assertEquals('Cannibals Elite', $nuevo['equipo']);
    }

    // Caso 13: Rechazo de inclusión cuando el torneo no existe en la base de datos
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-13')]
    public function testIncluirTorneoInexistenteRechazado(): void
    {
        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(0); // Torneo inexistente

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExisteTorneo);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'codigo_torneo' => 999,
            'codigo_equipo' => 5
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 14: Rechazo de inclusión cuando el equipo no existe en la base de datos
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-14')]
    public function testIncluirEquipoInexistenteRechazado(): void
    {
        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(1);

        $stmtExisteEquipo = $this->createMock(PDOStatement::class);
        $stmtExisteEquipo->method('execute')->willReturn(true);
        $stmtExisteEquipo->method('fetchColumn')->willReturn(0); // Equipo inexistente

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExisteTorneo, $stmtExisteEquipo) {
            if (str_contains($sql, 'FROM `torneos`')) {
                return $stmtExisteTorneo;
            }
            return $stmtExisteEquipo;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'codigo_torneo' => 1,
            'codigo_equipo' => 888
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID . '0', $resultado['codigo']);
    }

    // Caso 15: Rechazo de inclusión cuando el equipo ya participa en dicho torneo (duplicado)
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-15')]
    public function testIncluirParticipacionDuplicadaRechazada(): void
    {
        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(1);

        $stmtExisteEquipo = $this->createMock(PDOStatement::class);
        $stmtExisteEquipo->method('execute')->willReturn(true);
        $stmtExisteEquipo->method('fetchColumn')->willReturn(1);

        $stmtCheckGrupal = $this->createMock(PDOStatement::class);
        $stmtCheckGrupal->method('execute')->willReturn(true);
        $stmtCheckGrupal->method('fetchColumn')->willReturn(1); // Ya existe participación

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtExisteTorneo,
            $stmtExisteEquipo,
            $stmtCheckGrupal
        ) {
            if (str_contains($sql, 'FROM `torneos`')) {
                return $stmtExisteTorneo;
            }
            if (str_contains($sql, 'FROM `equipos`')) {
                return $stmtExisteEquipo;
            }
            return $stmtCheckGrupal;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'codigo_torneo' => 1,
            'codigo_equipo' => 5
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE, $resultado['codigo']);
    }

    // Caso 16: Captura de excepción en base de datos con reversión de transacción
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-16')]
    public function testIncluirFalloBaseDeDatosRollback(): void
    {
        $stmtExisteTorneo = $this->createMock(PDOStatement::class);
        $stmtExisteTorneo->method('execute')->willReturn(true);
        $stmtExisteTorneo->method('fetchColumn')->willReturn(1);

        $stmtExisteEquipo = $this->createMock(PDOStatement::class);
        $stmtExisteEquipo->method('execute')->willReturn(true);
        $stmtExisteEquipo->method('fetchColumn')->willReturn(1);

        $stmtCheckGrupal = $this->createMock(PDOStatement::class);
        $stmtCheckGrupal->method('execute')->willReturn(true);
        $stmtCheckGrupal->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtExisteTorneo,
            $stmtExisteEquipo,
            $stmtCheckGrupal
        ) {
            if (str_contains($sql, 'FROM `torneos`')) {
                return $stmtExisteTorneo;
            }
            if (str_contains($sql, 'FROM `equipos`')) {
                return $stmtExisteEquipo;
            }
            if (str_contains($sql, 'FROM participaciones WHERE codigo_torneo')) {
                return $stmtCheckGrupal;
            }
            throw new PDOException('Error simulado al insertar participación');
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'codigo_torneo' => 1,
            'codigo_equipo' => 5
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error simulado al insertar participación', $resultado['codigo']);
    }
}

