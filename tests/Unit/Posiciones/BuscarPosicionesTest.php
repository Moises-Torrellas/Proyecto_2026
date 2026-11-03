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
#[Group('buscarPosiciones')]
class BuscarPosicionesTest extends TestCase
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

    // Caso 1: Búsqueda exitosa pasando el id como argumento
    public function testBuscarPorArgumentoExitoso(): void
    {
        $filas = [$this->obtenerFilaPosicion()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->Buscar(1);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(1, $resultado['datos'][0]['codigo_posicion']);
    }

    // Caso 2: Búsqueda exitosa a través de ProcesarDatos
    public function testBuscarPorProcesarDatos(): void
    {
        $filas = [$this->obtenerFilaPosicion()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'buscar',
            'id'     => '1'
        ]);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 3: Búsqueda de una posición inexistente
    public function testBuscarNoEncontrado(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindParam')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->Buscar(999);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 4: Error en base de datos al buscar
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error al buscar posición'));
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPosiciones();
        $resultado = $modelo->Buscar(1);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al buscar posición', $resultado['mensaje']);
    }
}

