<?php

namespace tests\Unit\Asignaciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloArticulosInventario;
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
#[Group('unitAsignaciones')]
#[Group('RF-02')]
#[Group('ESC-02')]
class RegistrarAsignacionesTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloAsignaciones
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloAsignaciones();
    }

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $datos = [
            'accion'           => 'incluir',
            'codigo_atleta'    => '1',
            'codigo_articulo'  => '10',
            'fecha_asignacion' => date('Y-m-d')
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 11: Registro individual exitoso
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-11')]
    public function testRegistroIndividualExitoso(): void
    {
        // Arrange
        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetchColumn')->willReturn(1); // Atleta existe

        $stmtArticulo = $this->createMock(PDOStatement::class);
        $stmtArticulo->method('execute')->willReturn(true);
        $stmtArticulo->method('fetch')->willReturn([
            'estatus'         => 1,
            'codigo_club'     => 'CL-0010',
            'codigo_posicion' => null
        ]);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtAtleta, $stmtArticulo, $stmtInsert) {
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtAtleta;
            }
            if (str_contains($sql, 'FROM articulos_inventario')) {
                return $stmtArticulo;
            }
            return $stmtInsert;
        });

        $articulosMock = $this->createMock(ModeloArticulosInventario::class);
        $articulosMock->expects($this->once())
            ->method('CambiarEstatus')
            ->with('10', 2, $pdoMock)
            ->willReturn(true);

        $modelo = $this->crearModeloConPDOMock($pdoMock);
        $modelo->setArticulos($articulosMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals(1, $resultado['total']);
        $this->assertEquals('Asignación procesada.', $resultado['mensaje']);
    }

    // Caso 12: Registro en lote exitoso (múltiples artículos)
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-12')]
    public function testRegistroEnLoteExitoso(): void
    {
        // Arrange
        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetchColumn')->willReturn(1);

        $stmtArticulo = $this->createMock(PDOStatement::class);
        $stmtArticulo->method('execute')->willReturn(true);
        $stmtArticulo->method('fetch')->willReturnOnConsecutiveCalls(
            ['estatus' => 1, 'codigo_club' => 'CL-0010', 'codigo_posicion' => null],
            ['estatus' => 1, 'codigo_club' => 'CL-0011', 'codigo_posicion' => null]
        );

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtAtleta, $stmtArticulo, $stmtInsert) {
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtAtleta;
            }
            if (str_contains($sql, 'FROM articulos_inventario')) {
                return $stmtArticulo;
            }
            return $stmtInsert;
        });

        $articulosMock = $this->createMock(ModeloArticulosInventario::class);
        $articulosMock->expects($this->exactly(2))
            ->method('CambiarEstatus')
            ->willReturn(true);

        $modelo = $this->crearModeloConPDOMock($pdoMock);
        $modelo->setArticulos($articulosMock);

        $datos = $this->obtenerDatosValidos(['articulos' => ['10', '11']]);

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals(2, $resultado['total']);
    }

    // Caso 13: Rechazo por atleta inexistente
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-13')]
    public function testAtletaNoExiste(): void
    {
        // Arrange
        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetchColumn')->willReturn(0); // Atleta no existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtAtleta);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack');

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_ATLETA_NO_EXISTE', $resultado['codigo']);
    }

    // Caso 14: Rechazo por artículo inexistente en inventario
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-14')]
    public function testArticuloNoExiste(): void
    {
        // Arrange
        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetchColumn')->willReturn(1);

        $stmtArticulo = $this->createMock(PDOStatement::class);
        $stmtArticulo->method('execute')->willReturn(true);
        $stmtArticulo->method('fetch')->willReturn(false); // Artículo no encontrado

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtAtleta, $stmtArticulo) {
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtAtleta;
            }
            return $stmtArticulo;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos(['codigo_articulo' => '999']));

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_EQUIPO_NO_EXISTE', $resultado['codigo']);
        $this->assertEquals('999', $resultado['articulo_error']);
    }

    // Caso 15: Rechazo por artículo no disponible (ocupado o dañado)
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-15')]
    public function testArticuloOcupado(): void
    {
        // Arrange
        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetchColumn')->willReturn(1);

        $stmtArticulo = $this->createMock(PDOStatement::class);
        $stmtArticulo->method('execute')->willReturn(true);
        $stmtArticulo->method('fetch')->willReturn([
            'estatus'         => 2, // Ya ocupado
            'codigo_club'     => 'CL-0010',
            'codigo_posicion' => null
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtAtleta, $stmtArticulo) {
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtAtleta;
            }
            return $stmtArticulo;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_EQUIPO_OCUPADO', $resultado['codigo']);
        $this->assertEquals('CL-0010', $resultado['articulo_error']);
    }

    // Caso 16: Rechazo por incompatibilidad de posición deportiva
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-16')]
    public function testPosicionNoCoincide(): void
    {
        // Arrange
        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetchColumn')->willReturn(1);

        $stmtArticulo = $this->createMock(PDOStatement::class);
        $stmtArticulo->method('execute')->willReturn(true);
        $stmtArticulo->method('fetch')->willReturn([
            'estatus'         => 1,
            'codigo_club'     => 'CL-PADS-01',
            'codigo_posicion' => 3 // Requiere Portero
        ]);

        $stmtInscripcion = $this->createMock(PDOStatement::class);
        $stmtInscripcion->method('execute')->willReturn(true);
        $stmtInscripcion->method('fetchColumn')->willReturn(1); // Atleta es Delantero

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtAtleta, $stmtArticulo, $stmtInscripcion) {
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtAtleta;
            }
            if (str_contains($sql, 'FROM articulos_inventario')) {
                return $stmtArticulo;
            }
            return $stmtInscripcion;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_POSICION_NO_COINCIDE', $resultado['codigo']);
        $this->assertEquals('CL-PADS-01', $resultado['articulo_error']);
    }

    // Caso 17: Atomicidad en lote (Rollback si uno falla)
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-17')]
    public function testRollbackEnLoteSiUnoFalla(): void
    {
        // Arrange
        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetchColumn')->willReturn(1);

        $stmtArticulo = $this->createMock(PDOStatement::class);
        $stmtArticulo->method('execute')->willReturn(true);
        $stmtArticulo->method('fetch')->willReturnOnConsecutiveCalls(
            ['estatus' => 1, 'codigo_club' => 'CL-0010', 'codigo_posicion' => null], // 1ro válido
            ['estatus' => 2, 'codigo_club' => 'CL-0011', 'codigo_posicion' => null]  // 2do ocupado
        );

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack');
        $pdoMock->expects($this->never())->method('commit');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtAtleta, $stmtArticulo, $stmtInsert) {
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtAtleta;
            }
            if (str_contains($sql, 'FROM articulos_inventario')) {
                return $stmtArticulo;
            }
            return $stmtInsert;
        });

        $articulosMock = $this->createMock(ModeloArticulosInventario::class);

        $modelo = $this->crearModeloConPDOMock($pdoMock);
        $modelo->setArticulos($articulosMock);

        $datos = $this->obtenerDatosValidos(['articulos' => ['10', '11']]);

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_EQUIPO_OCUPADO', $resultado['codigo']);
        $this->assertEquals('CL-0011', $resultado['articulo_error']);
    }

    // Caso 18: Manejo de error de base de datos durante la inserción
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-18')]
    public function testErrorBaseDeDatosEnInsercion(): void
    {
        // Arrange
        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetchColumn')->willReturn(1);

        $stmtArticulo = $this->createMock(PDOStatement::class);
        $stmtArticulo->method('execute')->willReturn(true);
        $stmtArticulo->method('fetch')->willReturn([
            'estatus'         => 1,
            'codigo_club'     => 'CL-0010',
            'codigo_posicion' => null
        ]);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willThrowException(new PDOException('Fallo al insertar'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtAtleta, $stmtArticulo, $stmtInsert) {
            if (str_contains($sql, 'FROM `atletas`')) {
                return $stmtAtleta;
            }
            if (str_contains($sql, 'FROM articulos_inventario')) {
                return $stmtArticulo;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_BD', $resultado['codigo']);
    }
}
