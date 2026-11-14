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
#[Group('RF-04')]
#[Group('ESC-04')]
class EliminarParticipacionesTest extends TestCase
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

    // Caso 23: Eliminación exitosa de participación y cascada atómica sobre snapshot
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-23')]
    public function testEliminarParticipacionExitosa(): void
    {
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtPrevio = $this->createMock(PDOStatement::class);
        $stmtPrevio->method('execute')->willReturn(true);
        $stmtPrevio->method('fetch')->willReturn([
            'torneo' => 'Copa Barquisimeto',
            'equipo' => 'Cannibals Sub-20'
        ]);

        $stmtDelSnap = $this->createMock(PDOStatement::class);
        $stmtDelSnap->method('execute')->willReturn(true);

        $stmtDelPart = $this->createMock(PDOStatement::class);
        $stmtDelPart->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtExistePart,
            $stmtPrevio,
            $stmtDelSnap,
            $stmtDelPart
        ) {
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'WHERE p.codigo_participacion = :id')) {
                return $stmtPrevio;
            }
            if (str_contains($sql, 'DELETE FROM detalles_participacion')) {
                return $stmtDelSnap;
            }
            return $stmtDelPart;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'               => 'eliminar',
            'codigo_participacion' => 15
        ]);

        $this->assertEquals('exito', $resultado['accion']);
        $previo = json_decode($resultado['previo'], true);
        $this->assertEquals('Copa Barquisimeto', $previo['torneo']);
        $this->assertEquals('Cannibals Sub-20', $previo['equipo']);
    }

    // Caso 24: Rechazo de eliminación cuando la participación no existe
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-24')]
    public function testEliminarParticipacionInexistenteRechazada(): void
    {
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(0); // No existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturn($stmtExistePart);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'               => 'eliminar',
            'codigo_participacion' => 999
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 25: Captura de fallo en base de datos con reversión de transacción
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-25')]
    public function testEliminarFalloBaseDeDatosRollback(): void
    {
        $stmtExistePart = $this->createMock(PDOStatement::class);
        $stmtExistePart->method('execute')->willReturn(true);
        $stmtExistePart->method('fetchColumn')->willReturn(1);

        $stmtPrevio = $this->createMock(PDOStatement::class);
        $stmtPrevio->method('execute')->willReturn(true);
        $stmtPrevio->method('fetch')->willReturn([
            'torneo' => 'Torneo Test',
            'equipo' => 'Equipo Test'
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExistePart, $stmtPrevio) {
            if (str_contains($sql, 'FROM `participaciones`')) {
                return $stmtExistePart;
            }
            if (str_contains($sql, 'WHERE p.codigo_participacion = :id')) {
                return $stmtPrevio;
            }
            throw new PDOException('Error simulado al eliminar snapshot de participación');
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'               => 'eliminar',
            'codigo_participacion' => 15
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error simulado al eliminar snapshot de participación', $resultado['codigo']);
    }
}

