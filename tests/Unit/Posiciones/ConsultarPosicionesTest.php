<?php

namespace tests\Unit\Posiciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPosiciones;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitPosiciones')]
#[Group('consultarPosiciones')]
class ConsultarPosicionesTest extends TestCase
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

    private function obtenerFilaPosicion(array $sobrescribir = []): array
    {
        $base = [
            'codigo_posicion' => 1,
            'nombre'          => 'Primera Base',
            'abreviatura'     => 'PB',
            'descripcion'     => 'Jugador defensivo de la primera base'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Consulta general sin filtros
    public function testConsultarGeneral(): void
    {
        $filas = [$this->obtenerFilaPosicion()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Primera Base', $resultado['datos'][0]['nombre']);
    }

    // Caso 2: Consulta aplicando filtro por texto
    public function testConsultarConFiltro(): void
    {
        $filas = [$this->obtenerFilaPosicion(['nombre' => 'Receptor', 'abreviatura' => 'REC'])];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->with($this->callback(function ($params) {
            return isset($params[':f1']) && $params[':f1'] === '%Receptor%';
        }))->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->Consultar(['filtro' => 'Receptor']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 3: Consulta invocada mediante ProcesarDatos con acción generar
    public function testConsultarPorGenerar(): void
    {
        $filas = [$this->obtenerFilaPosicion()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos([
            'accion'      => 'generar',
            'nombre'      => 'Primera Base',
            'abreviatura' => 'PB'
        ]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 4: Consulta que no encuentra ningún resultado
    public function testConsultarVacio(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->Consultar(['filtro' => 'Inexistente']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 5: Error de base de datos durante la consulta
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de base de datos'));
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->Consultar();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al listar las posiciones.', $resultado['mensaje']);
    }
}

