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
#[Group('RF-05')]
#[Group('ESC-05')]
class ConsultarEquiposTest extends TestCase
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

    // Caso 27: Consulta general de equipos sin filtros
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-27')]
    public function testConsultarEquiposSinFiltros(): void
    {
        $datosEquipos = [
            ['id_equipos' => 1, 'nombre' => 'Cannibals Oro', 'cantidad_atletas' => 5, 'total_premios' => 2],
            ['id_equipos' => 2, 'nombre' => 'Cannibals Plata', 'cantidad_atletas' => 4, 'total_premios' => 1]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([]);
        $stmt->method('fetchAll')->willReturn($datosEquipos);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('FROM equipos e', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Consultar([]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
    }

    // Caso 28: Consulta de equipos con filtro dinámico por texto
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-28')]
    public function testConsultarEquiposConFiltroTexto(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with($this->callback(function ($params) {
            return isset($params[':f1']) && $params[':f1'] === '%Cannibals%';
        }));
        $stmt->method('fetchAll')->willReturn([
            ['id_equipos' => 1, 'nombre' => 'Cannibals Oro', 'cantidad_atletas' => 5, 'total_premios' => 2]
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('AND e.nombre LIKE :f1', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Consultar(['filtro' => 'Cannibals']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 29: Captura de error de base de datos en Consultar
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-29')]
    public function testConsultarEquiposErrorBaseDeDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error de conexión simulado'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Consultar([]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error de conexión simulado', $resultado['mensaje']);
    }

    // Caso 30: Invocación directa de ConsultarEquipos exitosa
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-30')]
    public function testConsultarEquiposDirectoExitoso(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([
            ['id_equipos' => 3, 'nombre' => 'Cannibals Bronce', 'cantidad_atletas' => 6, 'total_premios' => 0]
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('query')->willReturn($stmt);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ConsultarEquipos();

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Cannibals Bronce', $resultado['datos'][0]['nombre']);
    }

    // Caso 31: Captura de excepción en ConsultarEquipos
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-31')]
    public function testConsultarEquiposDirectoErrorBaseDeDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('query')->willThrowException(new PDOException('Fallo al consultar equipos'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ConsultarEquipos();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Fallo al consultar equipos', $resultado['mensaje']);
    }

    // Caso 32: Consulta de atletas elegibles para asignación sin filtros (modal)
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-32')]
    public function testConsultarAtletasParaAsignacionSinFiltros(): void
    {
        $rawAtletas = [
            [
                'id_atleta'        => 10,
                'doc_identidad'    => 'V-20111222',
                'nombres'          => 'Carlos',
                'apellidos'        => 'Rodriguez',
                'nombre_categoria' => 'Juvenil',
                'nombre_posicion'  => 'Delantero'
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with([]);
        $stmt->method('fetchAll')->willReturn($rawAtletas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('FROM vista_atletas_asignacion', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ConsultarAtletasParaAsignacion([]);

        $this->assertEquals('consultarAtletasModal', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $atleta = $resultado['datos'][0];
        $this->assertEquals(10, $atleta['id']);
        $this->assertEquals('V-20111222', $atleta['doc_i']);
        $this->assertEquals('Carlos Rodriguez', $atleta['nombre']);
        $this->assertEquals('Juvenil', $atleta['categoria']);
        $this->assertEquals('Delantero', $atleta['posicion']);
    }

    // Caso 33: Consulta de atletas elegibles con filtro de búsqueda por documento o nombre
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-33')]
    public function testConsultarAtletasParaAsignacionConFiltroTexto(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->with($this->callback(function ($params) {
            return isset($params[':f1']) && $params[':f1'] === '%Perez%'
                && isset($params[':f2']) && $params[':f2'] === '%Perez%'
                && isset($params[':f3']) && $params[':f3'] === '%Perez%';
        }));
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('doc_identidad LIKE :f1 OR nombres LIKE :f2 OR apellidos LIKE :f3', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ConsultarAtletasParaAsignacion(['filtro' => 'Perez']);

        $this->assertEquals('consultarAtletasModal', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
    }

    // Caso 34: Captura de excepción en ConsultarAtletasParaAsignacion
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-34')]
    public function testConsultarAtletasParaAsignacionErrorBaseDeDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error al cargar modal de atletas'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ConsultarAtletasParaAsignacion([]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error al cargar modal de atletas', $resultado['mensaje']);
    }

    // Caso 35: Consulta de atletas asignados a un equipo específico
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-35')]
    public function testConsultarAtletasAsignadosEquipoExitoso(): void
    {
        $rawAtletas = [
            [
                'id_atleta'        => 15,
                'doc_identidad'    => 'V-25333444',
                'nombres'          => 'Andres',
                'apellidos'        => 'Gimenez',
                'nombre_categoria' => 'Mayores',
                'nombre_posicion'  => 'Portero'
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($rawAtletas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('FROM vista_atletas_equipo WHERE id_equipo = :idEquipo', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ConsultarAtletasAsignadosEquipo(8);

        $this->assertEquals('consultarAtletasAsignadosEquipo', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Andres Gimenez', $resultado['datos'][0]['nombre']);
    }

    // Caso 36: Captura de excepción en ConsultarAtletasAsignadosEquipo
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-36')]
    public function testConsultarAtletasAsignadosEquipoErrorBaseDeDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Fallo al obtener atletas del equipo'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ConsultarAtletasAsignadosEquipo(8);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 37: Búsqueda individual directa por ID con parámetro
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-37')]
    public function testBuscarEquipoPorIdDirecto(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([
            ['id_equipos' => 3, 'nombre' => 'Cannibals Sub-16']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmt) {
            $this->assertStringContainsString('WHERE codigo_equipo = :id', $sql);
            return $stmt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Buscar(3);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEquals(3, $resultado['datos'][0]['id_equipos']);
    }

    // Caso 38: Búsqueda individual enrutada mediante ProcesarDatos
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-38')]
    public function testBuscarEquipoEnrutadoPorProcesarDatos(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([
            ['id_equipos' => 7, 'nombre' => 'Cannibals Sub-18']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion' => 'buscar',
            'id'     => 7
        ]);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEquals(7, $resultado['datos'][0]['id_equipos']);
    }

    // Caso 39: Captura de fallo en base de datos durante Buscar
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-39')]
    public function testBuscarEquipoErrorBaseDeDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error al buscar equipo'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->Buscar(1);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error al buscar equipo', $resultado['mensaje']);
    }
}

