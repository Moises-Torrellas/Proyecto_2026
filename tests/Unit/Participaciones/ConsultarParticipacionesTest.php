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
#[Group('RF-05')]
#[Group('ESC-05')]
class ConsultarParticipacionesTest extends TestCase
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

    // Caso 26: Consulta general de participaciones sin filtros
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-26')]
    public function testConsultarParticipacionesSinFiltros(): void
    {
        $datosEsperados = [
            [
                'codigo_participacion' => 1,
                'codigo_torneo'        => 5,
                'torneo_nombre'        => 'Copa Oro 2026',
                'torneo_estatus'       => 1,
                'fecha_inicio'         => '2026-06-01',
                'codigo_equipo'        => 10,
                'equipo_nombre'        => 'Cannibals Mayores',
                'cantidad_atletas'     => 12
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([]);
        $stmt->method('fetchAll')->willReturn($datosEsperados);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('FROM participaciones p', $sql);
            $this->assertStringContainsString('INNER JOIN torneos t', $sql);
            $this->assertStringContainsString('INNER JOIN equipos e', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Consultar([]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 27: Consulta con filtro dinámico por texto de torneo o equipo
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-27')]
    public function testConsultarParticipacionesConFiltroTextoTorneoOEquipo(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with($this->callback(function ($params) {
            return isset($params[':f1']) && $params[':f1'] === '%Elite%'
                && isset($params[':f2']) && $params[':f2'] === '%Elite%';
        }));
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('AND (t.nombre LIKE :f1 OR e.nombre LIKE :f2', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Consultar(['filtro' => 'Elite']);

        $this->assertEquals('consultar', $resultado['accion']);
    }

    // Caso 28: Consulta con filtro de texto que incluye estatus 'por disputarse'
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-28')]
    public function testConsultarParticipacionesConFiltroTextoEstatusPorDisputarse(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('OR t.estatus = 1', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Consultar(['filtro' => 'por disputarse']);

        $this->assertEquals('consultar', $resultado['accion']);
    }

    // Caso 29: Consulta con filtro de texto que incluye estatus 'en curso'
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-29')]
    public function testConsultarParticipacionesConFiltroTextoEstatusEnCurso(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('OR t.estatus = 2', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Consultar(['filtro' => 'en curso']);

        $this->assertEquals('consultar', $resultado['accion']);
    }

    // Caso 30: Consulta con filtro de texto que incluye estatus 'finalizado'
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-30')]
    public function testConsultarParticipacionesConFiltroTextoEstatusFinalizado(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('OR t.estatus = 3', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Consultar(['filtro' => 'finalizado']);

        $this->assertEquals('consultar', $resultado['accion']);
    }

    // Caso 31: Consulta con filtro de estatus de torneo exacto
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-31')]
    public function testConsultarParticipacionesConFiltroEstatusTorneoExacto(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with($this->callback(function ($params) {
            return isset($params[':estatus']) && $params[':estatus'] === 1;
        }));
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('AND t.estatus = :estatus', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Consultar(['estatus_torneo' => 1]);

        $this->assertEquals('consultar', $resultado['accion']);
    }

    // Caso 32: Consulta con filtros de torneo y equipo específicos
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-32')]
    public function testConsultarParticipacionesConFiltroTorneoYEquipoEspecificos(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with($this->callback(function ($params) {
            return isset($params[':codigo_torneo_filtro']) && $params[':codigo_torneo_filtro'] === 5
                && isset($params[':codigo_equipo_filtro']) && $params[':codigo_equipo_filtro'] === 10;
        }));
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('AND t.codigo_torneo = :codigo_torneo_filtro', $sql);
            $this->assertStringContainsString('AND e.codigo_equipo = :codigo_equipo_filtro', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Consultar([
            'codigo_torneo' => 5,
            'codigo_equipo' => 10
        ]);

        $this->assertEquals('consultar', $resultado['accion']);
    }

    // Caso 33: Captura de fallo en base de datos en Consultar
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-33')]
    public function testConsultarParticipacionesErrorBaseDeDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error al consultar participaciones'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Consultar([]);

        $this->assertEquals('error', $resultado['accion']);
    }

    // Caso 34: Búsqueda individual de participación existente
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-34')]
    public function testBuscarParticipacionExistenteExitosa(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([
            ['codigo_participacion' => 7, 'codigo_torneo' => 2, 'codigo_equipo' => 4]
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('WHERE p.codigo_participacion = :codigo_participacion', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'               => 'buscar',
            'codigo_participacion' => 7
        ]);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEquals(7, $resultado['datos'][0]['codigo_participacion']);
    }

    // Caso 35: Búsqueda individual cuando el registro no existe
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-35')]
    public function testBuscarParticipacionNoEncontradaRetornaError(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]); // No encontrado

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'               => 'buscar',
            'codigo_participacion' => 999
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Registro no encontrado', $resultado['mensaje']);
    }

    // Caso 36: Captura de fallo en base de datos durante Buscar
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-36')]
    public function testBuscarParticipacionErrorBaseDeDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Fallo al ejecutar SELECT en Buscar'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion'               => 'buscar',
            'codigo_participacion' => 1
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al buscar el registro', $resultado['mensaje']);
    }

    // Caso 37: Generación de reporte enrutada mediante ProcesarDatos
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-37')]
    public function testGenerarReporteEnrutadoPorProcesarDatos(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([
            ['codigo_participacion' => 1, 'torneo_nombre' => 'Copa Barquisimeto']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion' => 'generar'
        ]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }
}

