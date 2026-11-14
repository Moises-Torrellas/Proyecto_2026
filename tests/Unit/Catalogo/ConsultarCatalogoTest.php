<?php

namespace tests\Unit\Catalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCatalogo;
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
#[Group('unitCatalogo')]
#[Group('RF-05')]
#[Group('ESC-05')]
class ConsultarCatalogoTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloCatalogo
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloCatalogo();
    }

    // Caso 22: Consulta general sin filtros
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-22')]
    public function testConsultarGeneralExitoso(): void
    {
        // Arrange
        $registrosEsperados = [
            [
                'id_catalogo'      => 1,
                'nombre'           => 'Casco Bauer',
                'categoria_nombre' => 'Cascos',
                'posicion_nombre'  => 'Portero',
                'stock_actual'     => 5
            ],
            [
                'id_catalogo'      => 2,
                'nombre'           => 'Stick CCM',
                'categoria_nombre' => 'Stick',
                'posicion_nombre'  => 'Delantero',
                'stock_actual'     => 10
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($registrosEsperados);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('ORDER BY c.nombre ASC'))->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('Casco Bauer', $resultado['datos'][0]['nombre']);
        $this->assertEquals(5, $resultado['datos'][0]['stock_actual']);
    }

    // Caso 23: Consulta con filtro dinámico de texto
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-23')]
    public function testConsultarConFiltroTexto(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                return isset($params[':f1']) && $params[':f1'] === '%Bauer%'
                    && isset($params[':f2']) && $params[':f2'] === '%Bauer%'
                    && isset($params[':f3']) && $params[':f3'] === '%Bauer%';
            }))
            ->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('c.nombre LIKE :f1 OR'))->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar(['filtro' => 'Bauer']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
    }

    // Caso 24: Consulta con filtros de atributos id_categoria y talla
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-24')]
    public function testConsultarConFiltrosDeAtributos(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                return isset($params[':id_categoria']) && $params[':id_categoria'] === '2'
                    && isset($params[':talla']) && $params[':talla'] === 'XL';
            }))
            ->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function ($sql) {
            return str_contains($sql, 'AND c.id_categoria = :id_categoria')
                && str_contains($sql, 'AND c.talla = :talla');
        }))->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Asignamos atributos privados mediante Reflection
        $reflector = new ReflectionClass(ModeloCatalogo::class);
        $reflector->getProperty('id_categoria')->setValue($modelo, '2');
        $reflector->getProperty('talla')->setValue($modelo, 'XL');

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
    }

    // Caso 25: Error de base de datos durante la consulta general
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-25')]
    public function testConsultarErrorBaseDeDatos(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error de conexión al listar catálogo'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals('error', $resultado['accion']);
    }

    // Caso 26: Búsqueda específica pasando ID por parámetro directo a Buscar($id)
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-26')]
    public function testBuscarPorIdParametroDirecto(): void
    {
        // Arrange
        $registro = [
            ['id_catalogo' => 5, 'nombre' => 'Casco Bauer', 'id_categoria' => 2, 'stock_minimo' => 3]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($registro);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('SELECT * FROM catalogo WHERE id_catalogo = :id_catalogo'))->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Buscar(5);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(5, $resultado['datos'][0]['id_catalogo']);
        $this->assertEquals('Casco Bauer', $resultado['datos'][0]['nombre']);
    }

    // Caso 27: Búsqueda específica enrutada a través de ProcesarDatos con acción 'buscar'
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-27')]
    public function testBuscarViaProcesarDatos(): void
    {
        // Arrange
        $registro = [
            ['id_catalogo' => 10, 'nombre' => 'Stick CCM', 'id_categoria' => 3]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($registro);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'      => 'buscar',
            'id_catalogo' => '10'
        ]);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(10, $resultado['datos'][0]['id_catalogo']);
    }

    // Caso 28: Generación de reporte enrutada mediante ProcesarDatos con acción 'generar'
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-28')]
    public function testGenerarReporteViaProcesarDatos(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'       => 'generar',
            'id_categoria' => '2'
        ]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
    }

    // Caso 29: Error de base de datos durante la búsqueda específica
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-29')]
    public function testBuscarErrorBaseDeDatos(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Fallo en búsqueda de catálogo'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Buscar(5);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Fallo en búsqueda de catálogo', $resultado['mensaje']);
    }
}

