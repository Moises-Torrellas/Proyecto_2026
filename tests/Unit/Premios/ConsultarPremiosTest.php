<?php

namespace tests\Unit\Premios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitPremios')]
#[Group('RF-05')]
#[Group('ESC-05')]
class ConsultarPremiosTest extends TestCase
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

    private function obtenerFilaPremio(array $sobrescribir = []): array
    {
        $base = [
            'codigo_premio' => 1,
            'nombre'        => 'Campeón',
            'tipo'          => 'G'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 20: Consulta general sin filtros
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-20')]
    public function testConsultarGeneral(): void
    {
        // Arrange
        $filas = [
            $this->obtenerFilaPremio(['codigo_premio' => 1, 'nombre' => 'Campeón', 'tipo' => 'G']),
            $this->obtenerFilaPremio(['codigo_premio' => 2, 'nombre' => 'Mejor Portero', 'tipo' => 'I'])
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('ORDER BY codigo_premio ASC'))->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('Campeón', $resultado['datos'][0]['nombre']);
        $this->assertEquals('Mejor Portero', $resultado['datos'][1]['nombre']);
    }

    // Caso 21: Consulta aplicando filtro general por término de búsqueda (nombre o tipo)
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-21')]
    public function testConsultarConFiltroGeneral(): void
    {
        // Arrange
        $filas = [$this->obtenerFilaPremio(['nombre' => 'Mvp', 'tipo' => 'I'])];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->with($this->callback(function (array $params) {
            return isset($params[':f1']) && $params[':f1'] === '%Mvp%'
                && isset($params[':f2']) && $params[':f2'] === '%Mvp%';
        }))->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('(nombre LIKE :f1 OR tipo LIKE :f2)'))->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Consultar(['filtro' => 'Mvp']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Mvp', $resultado['datos'][0]['nombre']);
    }

    // Caso 22: Consulta con filtro específico por nombre
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-22')]
    public function testConsultarConFiltroNombre(): void
    {
        // Arrange
        $filas = [$this->obtenerFilaPremio(['nombre' => 'Goleador', 'tipo' => 'I'])];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->with($this->callback(function (array $params) {
            return isset($params[':nombre']) && str_starts_with($params[':nombre'], 'Goleador');
        }))->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('nombre LIKE :nombre'))->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act - Mediante ProcesarDatos con acción generar (reportes)
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'generar',
            'nombre' => 'Goleador'
        ]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Goleador', $resultado['datos'][0]['nombre']);
    }

    // Caso 23: Consulta con filtro específico por tipo ('I' o 'G')
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-23')]
    public function testConsultarConFiltroTipo(): void
    {
        // Arrange
        $filas = [
            $this->obtenerFilaPremio(['codigo_premio' => 1, 'nombre' => 'Campeón', 'tipo' => 'G']),
            $this->obtenerFilaPremio(['codigo_premio' => 2, 'nombre' => 'Subcampeón', 'tipo' => 'G'])
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->with($this->callback(function (array $params) {
            return isset($params[':tipo']) && $params[':tipo'] === 'G';
        }))->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('tipo = :tipo'))->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'generar',
            'tipo'   => 'G'
        ]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('G', $resultado['datos'][0]['tipo']);
    }

    // Caso 24: Consulta combinada por nombre y tipo
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-24')]
    public function testConsultarCombinadoNombreYTipo(): void
    {
        // Arrange
        $filas = [$this->obtenerFilaPremio(['nombre' => 'Campeón Invicto', 'tipo' => 'G'])];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->with($this->callback(function (array $params) {
            return isset($params[':nombre']) && isset($params[':tipo']) && $params[':tipo'] === 'G';
        }))->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtMock) {
            if (str_contains($sql, 'nombre LIKE :nombre') && str_contains($sql, 'tipo = :tipo')) {
                return $stmtMock;
            }
            return $this->createMock(PDOStatement::class);
        });
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'generar',
            'nombre' => 'Campeón Invicto',
            'tipo'   => 'G'
        ]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 25: Consulta invocada mediante ProcesarDatos con acción generar
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-25')]
    public function testConsultarPorGenerar(): void
    {
        // Arrange
        $filas = [$this->obtenerFilaPremio()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->ProcesarDatos(['accion' => 'generar']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 26: Consulta sin coincidencias devuelve arreglo vacío
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-26')]
    public function testConsultarSinCoincidencias(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPremios();

        // Act
        $resultado = $modelo->Consultar(['filtro' => 'Inexistente']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }
}
