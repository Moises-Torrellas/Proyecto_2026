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
#[Group('consultarListaAtletas')]
class ConsultarListaAtletasTest extends TestCase
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

    // Caso 1: Consulta exitosa de lista de atletas activos con documento propio
    public function testConsultarAtletasExitoso(): void
    {
        $atletasSimulados = [
            [
                'codigo_atleta'       => 1,
                'p_nombre'            => 'Carlos',
                'p_apellidos'         => 'Perez',
                's_nombre'            => 'Alberto',
                's_apellidos'         => 'Gomez',
                'categoria'           => 'Juvenil',
                'documento_identidad' => '12345678'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($atletasSimulados);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE i.estatus = 1'))
            ->willReturn($stmtMock);

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ConsultarAtletas();

        $this->assertEquals('consultarA', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Carlos', $resultado['datos'][0]['p_nombre']);
        $this->assertEquals('12345678', $resultado['datos'][0]['documento_identidad']);
    }

    // Caso 2: Atleta menor sin cédula propia que utiliza cédula del representante con prefijo R-
    public function testConsultarAtletasConDocumentoRepresentante(): void
    {
        $atletasSimulados = [
            [
                'codigo_atleta'       => 2,
                'p_nombre'            => 'Luis',
                'p_apellidos'         => 'Rojas',
                's_nombre'            => '',
                's_apellidos'         => '',
                'categoria'           => 'Infantil',
                'documento_identidad' => 'R-87654321'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($atletasSimulados);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ConsultarAtletas();

        $this->assertEquals('consultarA', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertStringStartsWith('R-', $resultado['datos'][0]['documento_identidad']);
    }

    // Caso 3: Consulta cuando no hay atletas registrados o activos
    public function testConsultarAtletasSinRegistros(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ConsultarAtletas();

        $this->assertEquals('consultarA', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 4: Excepción o fallo en la base de datos
    public function testConsultarAtletasErrorBaseDeDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de base de datos'));

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ConsultarAtletas();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de base de datos', $resultado['mensaje']);
    }
}
