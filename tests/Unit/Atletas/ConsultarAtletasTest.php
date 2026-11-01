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
#[Group('consultarAtletas')]
class ConsultarAtletasTest extends TestCase
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

    // Caso 1: Consulta general sin filtros
    public function testConsultarSinFiltrosExitoso(): void
    {
        $atletasSimulados = [
            ['id_atleta' => '1', 'nombres' => 'Carlos', 'apellidos' => 'Perez', 'estatus' => 1],
            ['id_atleta' => '2', 'nombres' => 'Maria', 'apellidos' => 'Gomez', 'estatus' => 2]
        ];

        $sqlEjecutado = null;
        $paramsEjecutados = null;

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturnCallback(function ($params) use (&$paramsEjecutados) {
            $paramsEjecutados = $params;
            return true;
        });
        $stmtMock->method('fetchAll')->willReturn($atletasSimulados);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$sqlEjecutado, $stmtMock) {
            $sqlEjecutado = $sql;
            return $stmtMock;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertStringContainsString('SELECT * FROM vista_atletas WHERE 1=1', $sqlEjecutado);
        $this->assertStringContainsString('ORDER BY CASE WHEN estatus = 1 THEN 0 ELSE 1 END ASC', $sqlEjecutado);
        $this->assertEmpty($paramsEjecutados);
    }

    // Caso 2: Consulta con filtro dinámico general (keyup)
    public function testConsultarConFiltroGeneral(): void
    {
        $sqlEjecutado = null;
        $paramsEjecutados = null;

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturnCallback(function ($params) use (&$paramsEjecutados) {
            $paramsEjecutados = $params;
            return true;
        });
        $stmtMock->method('fetchAll')->willReturn([
            ['id_atleta' => '1', 'nombres' => 'Carlos', 'apellidos' => 'Perez']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$sqlEjecutado, $stmtMock) {
            $sqlEjecutado = $sql;
            return $stmtMock;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->Consultar(['filtro' => 'Carlos']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);

        $this->assertStringContainsString(':f1 OR', $sqlEjecutado);
        $this->assertStringContainsString(':f8', $sqlEjecutado);

        $this->assertArrayHasKey(':f1', $paramsEjecutados);
        $this->assertEquals('%Carlos%', $paramsEjecutados[':f1']);
        $this->assertEquals('%Carlos%', $paramsEjecutados[':f8']);
    }

    // Caso 3: Consulta con filtros específicos vía ProcesarDatos ('generar')
    public function testConsultarConFiltrosEspecificosViaProcesarDatos(): void
    {
        $sqlEjecutado = null;
        $paramsEjecutados = null;

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturnCallback(function ($params) use (&$paramsEjecutados) {
            $paramsEjecutados = $params;
            return true;
        });
        $stmtMock->method('fetchAll')->willReturn([
            ['id_atleta' => '1', 'doc_identidad' => '12345678', 'nombres' => 'Carlos']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$sqlEjecutado, $stmtMock) {
            $sqlEjecutado = $sql;
            return $stmtMock;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'generar',
            'doc_identidad' => '12345678',
            'nombre'        => 'Carlos',
            'apellido'      => 'Perez',
            'categoria'     => '1',
            'posicion'      => '2',
            'genero'        => 'M',
            'estatus'       => '1',
            'edad'          => '15'
        ]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);

        $this->assertStringContainsString('AND doc_identidad LIKE :doc_i', $sqlEjecutado);
        $this->assertStringContainsString('AND nombres LIKE :nombre', $sqlEjecutado);
        $this->assertStringContainsString('AND apellidos LIKE :apellido', $sqlEjecutado);
        $this->assertStringContainsString('AND id_categoria = :id_cat', $sqlEjecutado);
        $this->assertStringContainsString('AND id_posicion = :id_p', $sqlEjecutado);
        $this->assertStringContainsString('AND genero = :genero', $sqlEjecutado);
        $this->assertStringContainsString('AND estatus = :estatus', $sqlEjecutado);
        $this->assertStringContainsString('AND (YEAR(CURDATE()) - YEAR(fecha_nac)) = :edad', $sqlEjecutado);

        $this->assertEquals('12345678%', $paramsEjecutados[':doc_i']);
        $this->assertEquals('Carlos%', $paramsEjecutados[':nombre']);
        $this->assertEquals('Perez%', $paramsEjecutados[':apellido']);
        $this->assertEquals('1', $paramsEjecutados[':id_cat']);
        $this->assertEquals('2', $paramsEjecutados[':id_p']);
        $this->assertEquals('M', $paramsEjecutados[':genero']);
        $this->assertEquals('1', $paramsEjecutados[':estatus']);
        $this->assertEquals(15, $paramsEjecutados[':edad']);
    }

    // Caso 4: Consulta que no encuentra atletas retorna array vacío
    public function testConsultarSinRegistrosRetornaArrayVacio(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->Consultar(['filtro' => 'Inexistente']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 5: Excepción en la base de datos
    public function testConsultarErrorBaseDeDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error al conectar con la vista'));

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->Consultar();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al conectar con la vista', $resultado['mensaje']);
    }
}
