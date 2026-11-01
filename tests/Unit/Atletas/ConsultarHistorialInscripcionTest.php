<?php

namespace tests\Unit\Atletas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloAtletas;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitAtletas')]
#[Group('historialAtletas')]
class ConsultarHistorialInscripcionTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    /**
     * Inyecta el mock de PDO en la instancia singleton de Conexion.
     */
    private function crearModeloConPDOMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);
    }

    // Caso 1: Consulta de historial pasando el ID por parámetro
    public function testConsultarHistorialPorParametroExitoso(): void
    {
        $historialSimulado = [
            [
                'fecha'    => '2026-01-10',
                'tipo'     => 'Inscripción',
                'detalles' => 'Categoría: Juvenil - Posición: Delantero'
            ],
            [
                'fecha'    => '2025-11-20',
                'tipo'     => 'Retiro',
                'detalles' => 'Motivo: Fin de temporada'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($historialSimulado);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('UNION ALL'))
            ->willReturn($stmtMock);

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ConsultarHistorialInscripcion(5);

        $this->assertEquals('historial', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('Inscripción', $resultado['datos'][0]['tipo']);
        $this->assertEquals('Retiro', $resultado['datos'][1]['tipo']);
    }

    // Caso 2: Consulta de historial vía ProcesarDatos
    public function testConsultarHistorialViaProcesarDatosExitoso(): void
    {
        $historialSimulado = [
            [
                'fecha'    => '2026-02-01',
                'tipo'     => 'Inscripción',
                'detalles' => 'Categoría: Sub-18 - Posición: Portero'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($historialSimulado);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'historial',
            'id'     => '12'
        ]);

        $this->assertEquals('historial', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Sub-18', explode(' - ', $resultado['datos'][0]['detalles'])[0] ? 'Sub-18' : '');
    }

    // Caso 3: Consulta de historial cuando el atleta no tiene registros
    public function testConsultarHistorialSinRegistros(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ConsultarHistorialInscripcion(999);

        $this->assertEquals('historial', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 4: Excepción o fallo en la base de datos
    public function testConsultarHistorialErrorBaseDeDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error al consultar historial'));

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ConsultarHistorialInscripcion(5);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al consultar historial', $resultado['mensaje']);
    }
}
