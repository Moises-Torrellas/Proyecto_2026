<?php

namespace tests\Unit\ArticulosInventario;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloArticulosInventario;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitArticulosInventario')]
#[Group('RF-05')]
#[Group('ESC-05')]
class ConsultarArticulosInventarioTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloArticulosInventario
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloArticulosInventario();
    }

    // Caso 22: Consulta general agrupada por catálogo sin filtros
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-22')]
    public function testConsultarAgrupadoSinFiltrosExitoso(): void
    {
        // Arrange
        $filasBD = [
            [
                'codigo_articulo' => 1,
                'id_catalogo'     => 10,
                'articulo'        => 'Casco Bauer',
                'talla'           => 'L',
                'categoria'       => 'Cascos',
                'estado'          => 'Excelente',
                'nivel_estado'    => 1,
                'apto_para_uso'   => 1,
                'id_estado'       => 1,
                'codigo_club'     => 'CL-0001',
                'estatus'         => 1
            ],
            [
                'codigo_articulo' => 2,
                'id_catalogo'     => 10,
                'articulo'        => 'Casco Bauer',
                'talla'           => 'L',
                'categoria'       => 'Cascos',
                'estado'          => 'Bueno',
                'nivel_estado'    => 2,
                'apto_para_uso'   => 1,
                'id_estado'       => 2,
                'codigo_club'     => 'CL-0002',
                'estatus'         => 1
            ],
            [
                'codigo_articulo' => 3,
                'id_catalogo'     => 20,
                'articulo'        => 'Stick CCM',
                'talla'           => '',
                'categoria'       => 'Stick',
                'estado'          => 'Excelente',
                'nivel_estado'    => 1,
                'apto_para_uso'   => 1,
                'id_estado'       => 1,
                'codigo_club'     => 'CL-0003',
                'estatus'         => 1
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($filasBD);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('ORDER BY c.nombre ASC, e.codigo_articulo DESC'))->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos(['accion' => 'consultar']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']); // 2 catálogos agrupados

        // Verificar catálogo 10
        $this->assertEquals(10, $resultado['datos'][0]['id_catalogo']);
        $this->assertEquals('Casco Bauer (Talla: L)', $resultado['datos'][0]['articulo']);
        $this->assertCount(2, $resultado['datos'][0]['piezas']); // 2 piezas en inventario

        // Verificar catálogo 20
        $this->assertEquals(20, $resultado['datos'][1]['id_catalogo']);
        $this->assertEquals('Stick CCM', $resultado['datos'][1]['articulo']);
        $this->assertCount(1, $resultado['datos'][1]['piezas']);
    }

    // Caso 23: Consulta con filtro dinámico por coincidencia en nombre o estado
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-23')]
    public function testConsultarConFiltroTexto(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->exactly(2))
            ->method('bindValue')
            ->willReturnCallback(function ($param, $value) {
                if ($param === ':filtro1' || $param === ':filtro2') {
                    $this->assertEquals('%Casco%', $value);
                }
                return true;
            });
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('AND (c.nombre LIKE :filtro1 OR es.nombre LIKE :filtro2)'))->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'consultar',
            'filtro' => 'Casco'
        ]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
    }

    // Caso 24: Consulta con filtro específico por id_catalogo
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-24')]
    public function testConsultarConFiltroCatalogo(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('bindValue')
            ->with(':id_catalogo', 5)
            ->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('AND c.id_catalogo = :id_catalogo'))->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'      => 'consultar',
            'id_catalogo' => 5
        ]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
    }

    // Caso 25: Consulta con filtro específico por id_estado
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-25')]
    public function testConsultarConFiltroEstado(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('bindValue')
            ->with(':id_estado', 2)
            ->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('AND e.id_estado = :id_estado'))->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'    => 'consultar',
            'id_estado' => 2
        ]);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
    }

    // Caso 26: Error de base de datos durante la consulta agrupada
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-26')]
    public function testConsultarAgrupadoErrorBaseDeDatos(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error de conexión al listar inventario'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos(['accion' => 'consultar']);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DB_CONNECTION, $resultado['codigo']);
    }

    // Caso 27: Búsqueda individual de un artículo específico por código
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-27')]
    public function testBuscarArticuloPorIdExitoso(): void
    {
        // Arrange
        $registro = [
            ['codigo_articulo' => 15, 'id_catalogo' => 2, 'id_estado' => 1, 'codigo_club' => 'CL-0015', 'estatus' => 1]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($registro);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('SELECT * FROM articulos_inventario WHERE codigo_articulo = :id'))->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act - Probamos tanto a través de ProcesarDatos como la llamada directa
        $resultado = $modelo->ProcesarDatos([
            'accion'          => 'buscar',
            'codigo_articulo' => 15
        ]);
        $resultadoDirecto = $modelo->Buscar(15);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(15, $resultado['datos'][0]['codigo_articulo']);
        $this->assertEquals('CL-0015', $resultado['datos'][0]['codigo_club']);
        $this->assertEquals('buscar', $resultadoDirecto['accion']);
    }

    // Caso 28: Error de base de datos durante la búsqueda individual
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-28')]
    public function testBuscarArticuloErrorBaseDeDatos(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error al consultar artículo'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Buscar(15);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al consultar artículo', $resultado['mensaje']);
    }

    // Caso 29: Consulta de artículos libres disponibles para asignación
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-29')]
    public function testConsultarArticulosLibresExitoso(): void
    {
        // Arrange
        $articulosLibresMock = [
            ['codigo_articulo' => 1, 'codigo_club' => 'CL-0001', 'id_catalogo' => 2, 'articulo' => 'Casco', 'talla' => 'M'],
            ['codigo_articulo' => 2, 'codigo_club' => 'CL-0002', 'id_catalogo' => 3, 'articulo' => 'Stick', 'talla' => '']
        ];

        $stmtQuery = $this->createMock(PDOStatement::class);
        $stmtQuery->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($articulosLibresMock);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('query')->with($this->stringContains('WHERE e.estatus = 1'))->willReturn($stmtQuery);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ConsultarArticulosLibres();

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('CL-0001', $resultado['datos'][0]['codigo_club']);
    }

    // Caso 30: Error de base de datos durante la consulta de artículos libres
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-30')]
    public function testConsultarArticulosLibresErrorBaseDeDatos(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('query')->willThrowException(new Exception('Error de base de datos al listar libres'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ConsultarArticulosLibres();

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals([], $resultado['datos']);
    }
}
