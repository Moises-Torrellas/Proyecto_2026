<?php

namespace tests\Unit\CategoriaCatalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCategoriaCatalogo;
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
#[Group('unitCategoriaCatalogo')]
#[Group('RF-05')]
#[Group('ESC-05')]
class ConsultarCategoriaCatalogoTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloCategoriaCatalogo
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloCategoriaCatalogo();
    }

    // Caso 19: Consulta general sin filtros
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-19')]
    public function testConsultarGeneralExitoso(): void
    {
        // Arrange
        $registrosEsperados = [
            ['id_categoria' => 2, 'nombre' => 'Cascos', 'tipo_talla' => 'Letras', 'descripcion' => ''],
            ['id_categoria' => 3, 'nombre' => 'Stick', 'tipo_talla' => 'Categorico', 'descripcion' => '']
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($registrosEsperados);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('SELECT * FROM categoria_catalogo WHERE 1=1 ORDER BY id_categoria ASC'))
                ->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('Cascos', $resultado['datos'][0]['nombre']);
    }

    // Caso 20: Consulta filtrada por parámetro dinámico de texto
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-20')]
    public function testConsultarConFiltroTexto(): void
    {
        // Arrange
        $registrosEsperados = [
            ['id_categoria' => 2, 'nombre' => 'Cascos', 'tipo_talla' => 'Letras', 'descripcion' => '']
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                return isset($params[':f1']) && $params[':f1'] === '%Cascos%';
            }))
            ->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($registrosEsperados);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('AND nombre LIKE :f1'))
                ->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar(['filtro' => 'Cascos']);

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 21: Consulta con propiedad interna $nombre establecida
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-21')]
    public function testConsultarConPropiedadNombreDefinida(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                return isset($params[':nombre']) && $params[':nombre'] === '%Stick%';
            }))
            ->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('AND nombre LIKE :nombre'))
                ->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Establecer propiedad privada nombre vía Reflection
        $reflector = new ReflectionClass(ModeloCategoriaCatalogo::class);
        $propNombre = $reflector->getProperty('nombre');
        $propNombre->setValue($modelo, 'Stick');

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals('consultar', $resultado['accion']);
    }

    // Caso 22: Error de base de datos durante la consulta general
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-22')]
    public function testConsultarErrorBaseDeDatos(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error de conexión con la base de datos'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Consultar();

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error al listar:', $resultado['mensaje']);
    }

    // Caso 23: Búsqueda específica por identificador pasando parámetro directo a Buscar($id)
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-23')]
    public function testBuscarPorIdParametroDirecto(): void
    {
        // Arrange
        $registro = [
            ['id_categoria' => 2, 'nombre' => 'Cascos', 'tipo_talla' => 'Letras', 'descripcion' => '']
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($registro);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->stringContains('SELECT * FROM categoria_catalogo WHERE id_categoria = :id_categoria'))
                ->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Buscar(2);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals(2, $resultado['datos'][0]['id_categoria']);
        $this->assertEquals('Cascos', $resultado['datos'][0]['nombre']);
    }

    // Caso 24: Búsqueda específica a través de ProcesarDatos con accion 'buscar'
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-24')]
    public function testBuscarViaProcesarDatos(): void
    {
        // Arrange
        $registro = [
            ['id_categoria' => 3, 'nombre' => 'Stick', 'tipo_talla' => 'Categorico', 'descripcion' => '']
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($registro);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos([
            'accion'       => 'buscar',
            'id_categoria' => '3'
        ]);

        // Assert
        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Stick', $resultado['datos'][0]['nombre']);
    }

    // Caso 25: Error de base de datos durante la búsqueda específica
    #[Group('RF-05')]
    #[Group('ESC-05')]
    #[Group('CP-25')]
    public function testBuscarErrorBaseDeDatos(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error al ejecutar SELECT'));

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->Buscar(2);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al ejecutar SELECT', $resultado['mensaje']);
    }
}

