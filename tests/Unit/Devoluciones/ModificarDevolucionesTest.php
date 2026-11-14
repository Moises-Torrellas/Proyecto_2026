<?php

namespace tests\Unit\Devoluciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloDevoluciones;
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
#[Group('unitDevoluciones')]
#[Group('RF-03')]
#[Group('ESC-03')]
class ModificarDevolucionesTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloDevoluciones
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloDevoluciones();
    }

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $datos = [
            'accion'           => 'modificar',
            'id_devolucion'    => '1',
            'id_asignacion'    => '5',
            'id_estado'        => '1',
            'fecha_devolucion' => '2026-05-15',
            'observacion'      => 'Observación actualizada'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 7: Modificación exitosa sin cambio de estado físico
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-07')]
    public function testModificarDevolucionSinCambioDeEstado(): void
    {
        // Arrange
        $stmtCheckDev = $this->createMock(PDOStatement::class);
        $stmtCheckDev->method('execute')->willReturn(true);
        $stmtCheckDev->method('fetchColumn')->willReturn(1); // Devolución existe

        $stmtEq = $this->createMock(PDOStatement::class);
        $stmtEq->method('execute')->willReturn(true);
        $stmtEq->method('fetch')->willReturn([
            'codigo_articulo' => '10',
            'id_estado'        => '1' // Mismo estado
        ]);

        $stmtUpdateDev = $this->createMock(PDOStatement::class);
        $stmtUpdateDev->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckDev, $stmtEq, $stmtUpdateDev) {
            if (str_contains($sql, 'FROM devoluciones WHERE id_devolucion = ?')) {
                return $stmtCheckDev;
            }
            if (str_contains($sql, 'SELECT a.codigo_articulo, d.id_estado')) {
                return $stmtEq;
            }
            return $stmtUpdateDev;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos(['id_estado' => '1']));

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Modificación exitosa.', $resultado['mensaje']);
    }

    // Caso 8: Modificación exitosa con cambio de estado físico en inventario
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-08')]
    public function testModificarDevolucionConCambioDeEstadoFisico(): void
    {
        // Arrange
        $stmtCheckDev = $this->createMock(PDOStatement::class);
        $stmtCheckDev->method('execute')->willReturn(true);
        $stmtCheckDev->method('fetchColumn')->willReturn(1);

        $stmtEq = $this->createMock(PDOStatement::class);
        $stmtEq->method('execute')->willReturn(true);
        $stmtEq->method('fetch')->willReturn([
            'codigo_articulo' => '10',
            'id_estado'        => '1' // Estado previo: Excelente (1)
        ]);

        $stmtUpdateDev = $this->createMock(PDOStatement::class);
        $stmtUpdateDev->method('execute')->willReturn(true);

        $stmtUpdateArt = $this->createMock(PDOStatement::class);
        $stmtUpdateArt->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckDev, $stmtEq, $stmtUpdateDev, $stmtUpdateArt) {
            if (str_contains($sql, 'FROM devoluciones WHERE id_devolucion = ?')) {
                return $stmtCheckDev;
            }
            if (str_contains($sql, 'SELECT a.codigo_articulo, d.id_estado')) {
                return $stmtEq;
            }
            if (str_contains($sql, 'UPDATE articulos_inventario SET id_estado = ?')) {
                return $stmtUpdateArt;
            }
            return $stmtUpdateDev;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act (id_estado cambia a 3: Dañado)
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos(['id_estado' => '3']));

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Modificación exitosa.', $resultado['mensaje']);
    }

    // Caso 9: Rechazo si el registro de devolución no existe
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-09')]
    public function testModificarDevolucionNoExiste(): void
    {
        // Arrange
        $stmtCheckDev = $this->createMock(PDOStatement::class);
        $stmtCheckDev->method('execute')->willReturn(true);
        $stmtCheckDev->method('fetchColumn')->willReturn(false); // No existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtCheckDev);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos(['id_devolucion' => '999']));

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El registro de devolución que intenta modificar no existe.', $resultado['mensaje']);
    }

    // Caso 10: Error de base de datos durante la modificación
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-10')]
    public function testErrorBaseDeDatosEnModificacion(): void
    {
        // Arrange
        $stmtCheckDev = $this->createMock(PDOStatement::class);
        $stmtCheckDev->method('execute')->willReturn(true);
        $stmtCheckDev->method('fetchColumn')->willReturn(1);

        $stmtEq = $this->createMock(PDOStatement::class);
        $stmtEq->method('execute')->willReturn(true);
        $stmtEq->method('fetch')->willReturn([
            'codigo_articulo' => '10',
            'id_estado'        => '1'
        ]);

        $stmtUpdateDev = $this->createMock(PDOStatement::class);
        $stmtUpdateDev->method('execute')->willThrowException(new PDOException('Error de deadlock en modificación'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckDev, $stmtEq, $stmtUpdateDev) {
            if (str_contains($sql, 'FROM devoluciones WHERE id_devolucion = ?')) {
                return $stmtCheckDev;
            }
            if (str_contains($sql, 'SELECT a.codigo_articulo, d.id_estado')) {
                return $stmtEq;
            }
            return $stmtUpdateDev;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de deadlock en modificación', $resultado['mensaje']);
    }
}

