<?php

namespace tests\Unit\Asignaciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloAsignaciones;
use App\modelo\ModeloArticulosInventario;
use App\modelo\Conexion;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitAsignaciones')]
#[Group('RF-03')]
#[Group('ESC-03')]
class ModificarAsignacionesTest extends TestCase
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
            'accion'           => 'modificar',
            'id_asignacion'    => '1',
            'codigo_atleta'    => '2',
            'codigo_articulo'  => '20',
            'fecha_asignacion' => '2026-05-10'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 19: Modificación exitosa cambiando de artículo
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-19')]
    public function testModificarCambiandoArticuloExitoso(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1); // Asignación existe

        $stmtOld = $this->createMock(PDOStatement::class);
        $stmtOld->method('execute')->willReturn(true);
        $stmtOld->method('fetch')->willReturn([
            'codigo_articulo' => '10', // Artículo anterior
            'estatus'         => 1
        ]);

        $stmtNuevoArticulo = $this->createMock(PDOStatement::class);
        $stmtNuevoArticulo->method('execute')->willReturn(true);
        $stmtNuevoArticulo->method('fetch')->willReturn([
            'estatus'         => 1, // Disponible
            'codigo_posicion' => null
        ]);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExiste, $stmtOld, $stmtNuevoArticulo, $stmtUpdate) {
            if (str_contains($sql, 'SELECT COUNT(*) FROM `asignaciones`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'SELECT codigo_articulo, estatus FROM asignaciones')) {
                return $stmtOld;
            }
            if (str_contains($sql, 'FROM articulos_inventario')) {
                return $stmtNuevoArticulo;
            }
            return $stmtUpdate;
        });

        $articulosMock = $this->createMock(ModeloArticulosInventario::class);
        $articulosMock->expects($this->exactly(2))
            ->method('CambiarEstatus')
            ->willReturn(true);

        $modelo = $this->crearModeloConPDOMock($pdoMock);
        $modelo->setArticulos($articulosMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos(['codigo_articulo' => '20']));

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Modificación exitosa.', $resultado['mensaje']);
    }

    // Caso 20: Modificación exitosa conservando el mismo artículo
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-20')]
    public function testModificarMismoArticuloExitoso(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtOld = $this->createMock(PDOStatement::class);
        $stmtOld->method('execute')->willReturn(true);
        $stmtOld->method('fetch')->willReturn([
            'codigo_articulo' => '10',
            'estatus'         => 1
        ]);

        $stmtCheckPosicion = $this->createMock(PDOStatement::class);
        $stmtCheckPosicion->method('execute')->willReturn(true);
        $stmtCheckPosicion->method('fetchColumn')->willReturn(null); // Sin restricción de posición

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExiste, $stmtOld, $stmtCheckPosicion, $stmtUpdate) {
            if (str_contains($sql, 'SELECT COUNT(*) FROM `asignaciones`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'SELECT codigo_articulo, estatus FROM asignaciones')) {
                return $stmtOld;
            }
            if (str_contains($sql, 'FROM articulos_inventario')) {
                return $stmtCheckPosicion;
            }
            return $stmtUpdate;
        });

        $articulosMock = $this->createMock(ModeloArticulosInventario::class);
        $articulosMock->expects($this->never())->method('CambiarEstatus');

        $modelo = $this->crearModeloConPDOMock($pdoMock);
        $modelo->setArticulos($articulosMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos(['codigo_articulo' => '10']));

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Modificación exitosa.', $resultado['mensaje']);
    }

    // Caso 21: Rechazo por asignación inexistente
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-21')]
    public function testModificarAsignacionNoExiste(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0); // Asignación no existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtExiste);
        $pdoMock->method('inTransaction')->willReturn(true);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_NO_EXISTE', $resultado['codigo']);
    }

    // Caso 22: Rechazo al modificar asignación no activa (estatus != 1)
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-22')]
    public function testModificarAsignacionEstatusInvalido(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtOld = $this->createMock(PDOStatement::class);
        $stmtOld->method('execute')->willReturn(true);
        $stmtOld->method('fetch')->willReturn([
            'codigo_articulo' => '10',
            'estatus'         => 2 // Ya devuelta
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExiste, $stmtOld) {
            if (str_contains($sql, 'SELECT COUNT(*) FROM `asignaciones`')) {
                return $stmtExiste;
            }
            return $stmtOld;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_ESTATUS', $resultado['codigo']);
    }

    // Caso 23: Rechazo si el nuevo artículo está ocupado
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-23')]
    public function testModificarNuevoArticuloOcupado(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtOld = $this->createMock(PDOStatement::class);
        $stmtOld->method('execute')->willReturn(true);
        $stmtOld->method('fetch')->willReturn([
            'codigo_articulo' => '10',
            'estatus'         => 1
        ]);

        $stmtNuevoArticulo = $this->createMock(PDOStatement::class);
        $stmtNuevoArticulo->method('execute')->willReturn(true);
        $stmtNuevoArticulo->method('fetch')->willReturn([
            'estatus'         => 2, // Ocupado
            'codigo_posicion' => null
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExiste, $stmtOld, $stmtNuevoArticulo) {
            if (str_contains($sql, 'SELECT COUNT(*) FROM `asignaciones`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'SELECT codigo_articulo, estatus FROM asignaciones')) {
                return $stmtOld;
            }
            return $stmtNuevoArticulo;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos(['codigo_articulo' => '20']));

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_EQUIPO_NO_DISPONIBLE', $resultado['codigo']);
    }

    // Caso 24: Rechazo por incompatibilidad de posición al modificar
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-24')]
    public function testModificarPosicionNoCoincide(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtOld = $this->createMock(PDOStatement::class);
        $stmtOld->method('execute')->willReturn(true);
        $stmtOld->method('fetch')->willReturn([
            'codigo_articulo' => '10',
            'estatus'         => 1
        ]);

        $stmtNuevoArticulo = $this->createMock(PDOStatement::class);
        $stmtNuevoArticulo->method('execute')->willReturn(true);
        $stmtNuevoArticulo->method('fetch')->willReturn([
            'estatus'         => 1,
            'codigo_posicion' => 3 // Portero
        ]);

        $stmtAtleta = $this->createMock(PDOStatement::class);
        $stmtAtleta->method('execute')->willReturn(true);
        $stmtAtleta->method('fetchColumn')->willReturn(1); // Delantero

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExiste, $stmtOld, $stmtNuevoArticulo, $stmtAtleta) {
            if (str_contains($sql, 'SELECT COUNT(*) FROM `asignaciones`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'SELECT codigo_articulo, estatus FROM asignaciones')) {
                return $stmtOld;
            }
            if (str_contains($sql, 'FROM articulos_inventario')) {
                return $stmtNuevoArticulo;
            }
            return $stmtAtleta;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos(['codigo_articulo' => '20']));

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_POSICION_NO_COINCIDE', $resultado['codigo']);
    }
}

