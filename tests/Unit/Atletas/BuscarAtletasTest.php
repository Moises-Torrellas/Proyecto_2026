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
#[Group('buscarAtletas')]
class BuscarAtletasTest extends TestCase
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

    // Caso 1: Búsqueda exitosa pasando el ID como argumento
    public function testBuscarPorParametroExitoso(): void
    {
        $atletaSimulado = [
            'id_atleta'        => '5',
            'doc_identidad'    => '20123456',
            'nombres'          => 'Carlos Alberto',
            'apellidos'        => 'Perez Gomez',
            'correo'           => 'carlos@correo.com',
            'municipio'        => 'Iribarren',
            'instagram'        => '@carlos_p',
            'talla_pantalon'   => 'M',
            'talla_franela'    => 'M',
            'talla_calzado'    => '42',
            'tipo_sangre'      => 'O+',
            'es_alergico'      => 0,
            'alergias_detalle' => 'Ninguna'
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([$atletaSimulado]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('FROM vista_atletas v'))
            ->willReturn($stmtMock);

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->Buscar(5);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Carlos Alberto', $resultado['datos'][0]['nombres']);
    }

    // Caso 2: Búsqueda exitosa ejecutada mediante ProcesarDatos
    public function testBuscarViaProcesarDatosExitoso(): void
    {
        $atletaSimulado = [
            'id_atleta'     => '10',
            'doc_identidad' => '25987654',
            'nombres'       => 'Ana Maria',
            'apellidos'     => 'Torres Ramos'
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([$atletaSimulado]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'buscar',
            'id'     => '10'
        ]);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Ana Maria', $resultado['datos'][0]['nombres']);
    }

    // Caso 3: Búsqueda de un atleta inexistente retorna lista vacía
    public function testBuscarAtletaNoEncontradoRetornaVacio(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->Buscar(999);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 4: Excepción en la consulta de base de datos
    public function testBuscarErrorBaseDeDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de conexión con la base de datos'));

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->Buscar(5);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de conexión con la base de datos', $resultado['mensaje']);
    }
}
