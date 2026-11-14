<?php

namespace tests\Unit\Devoluciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloDevoluciones;
use App\modelo\ModeloAsignaciones;
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
#[Group('RF-04')]
#[Group('ESC-04')]
class AnularDevolucionesTest extends TestCase
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

    // Caso 11: Anulación exitosa cuando el artículo no está en uso
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-11')]
    public function testAnulacionExitosaSinReasignacion(): void
    {
        // Arrange
        $stmtCheckDev = $this->createMock(PDOStatement::class);
        $stmtCheckDev->method('execute')->willReturn(true);
        $stmtCheckDev->method('fetchColumn')->willReturn(1); // Devolución existe

        $stmtAsig = $this->createMock(PDOStatement::class);
        $stmtAsig->method('execute')->willReturn(true);
        $stmtAsig->method('fetchColumn')->willReturn(5); // id_asignacion = 5

        $stmtEq = $this->createMock(PDOStatement::class);
        $stmtEq->method('execute')->willReturn(true);
        $stmtEq->method('fetchColumn')->willReturn(10); // codigo_articulo = 10

        $stmtCheckUso = $this->createMock(PDOStatement::class);
        $stmtCheckUso->method('execute')->willReturn(true);
        $stmtCheckUso->method('fetchColumn')->willReturn(0); // 0 asignaciones activas (libre)

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('execute')->willReturn(true);

        $stmtUpdateAsig = $this->createMock(PDOStatement::class);
        $stmtUpdateAsig->method('execute')->willReturn(true);

        $stmtUpdateArt = $this->createMock(PDOStatement::class);
        $stmtUpdateArt->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtCheckDev, $stmtAsig, $stmtEq, $stmtCheckUso, $stmtDelete, $stmtUpdateAsig, $stmtUpdateArt
        ) {
            if (str_contains($sql, 'FROM devoluciones WHERE id_devolucion = ?') && !str_contains($sql, 'SELECT id_asignacion')) {
                return $stmtCheckDev;
            }
            if (str_contains($sql, 'SELECT id_asignacion FROM devoluciones')) {
                return $stmtAsig;
            }
            if (str_contains($sql, 'SELECT codigo_articulo FROM asignaciones')) {
                return $stmtEq;
            }
            if (str_contains($sql, 'SELECT COUNT(*) FROM asignaciones')) {
                return $stmtCheckUso;
            }
            if (str_contains($sql, 'DELETE FROM devoluciones')) {
                return $stmtDelete;
            }
            if (str_contains($sql, 'UPDATE asignaciones SET estatus = 1')) {
                return $stmtUpdateAsig;
            }
            return $stmtUpdateArt;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'           => 'anular',
            'id_devolucion'    => '1',
            'motivo_anulacion' => 'Error de digitación'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Anulación procesada, equipo reasignado.', $resultado['mensaje']);
    }

    // Caso 12: Rechazo por artículo ya reasignado a otro atleta
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-12')]
    public function testRechazoPorArticuloYaReasignado(): void
    {
        // Arrange
        $stmtCheckDev = $this->createMock(PDOStatement::class);
        $stmtCheckDev->method('execute')->willReturn(true);
        $stmtCheckDev->method('fetchColumn')->willReturn(1);

        $stmtAsig = $this->createMock(PDOStatement::class);
        $stmtAsig->method('execute')->willReturn(true);
        $stmtAsig->method('fetchColumn')->willReturn(5);

        $stmtEq = $this->createMock(PDOStatement::class);
        $stmtEq->method('execute')->willReturn(true);
        $stmtEq->method('fetchColumn')->willReturn(10);

        $stmtCheckUso = $this->createMock(PDOStatement::class);
        $stmtCheckUso->method('execute')->willReturn(true);
        $stmtCheckUso->method('fetchColumn')->willReturn(1); // En uso (> 0)

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckDev, $stmtAsig, $stmtEq, $stmtCheckUso) {
            if (str_contains($sql, 'FROM devoluciones WHERE id_devolucion = ?') && !str_contains($sql, 'SELECT id_asignacion')) {
                return $stmtCheckDev;
            }
            if (str_contains($sql, 'SELECT id_asignacion FROM devoluciones')) {
                return $stmtAsig;
            }
            if (str_contains($sql, 'SELECT codigo_articulo FROM asignaciones')) {
                return $stmtEq;
            }
            return $stmtCheckUso;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'        => 'anular',
            'id_devolucion' => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(
            'No se puede anular la devolución. El artículo ya ha sido reasignado a otro atleta y está en uso.',
            $resultado['mensaje']
        );
    }

    // Caso 13: Rechazo si el registro de devolución a anular no existe
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-13')]
    public function testAnulacionDevolucionNoExiste(): void
    {
        // Arrange
        $stmtCheckDev = $this->createMock(PDOStatement::class);
        $stmtCheckDev->method('execute')->willReturn(true);
        $stmtCheckDev->method('fetchColumn')->willReturn(false); // No existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtCheckDev);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'        => 'anular',
            'id_devolucion' => '999'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El registro que intenta anular ya no existe en el sistema.', $resultado['mensaje']);
    }

    // Caso 14: Anulación exitosa utilizando el objeto inyectado ModeloAsignaciones
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-14')]
    public function testAnulacionConObjetoAsignacionesInyectado(): void
    {
        // Arrange
        $stmtCheckDev = $this->createMock(PDOStatement::class);
        $stmtCheckDev->method('execute')->willReturn(true);
        $stmtCheckDev->method('fetchColumn')->willReturn(1);

        $stmtAsig = $this->createMock(PDOStatement::class);
        $stmtAsig->method('execute')->willReturn(true);
        $stmtAsig->method('fetchColumn')->willReturn(5);

        $stmtEq = $this->createMock(PDOStatement::class);
        $stmtEq->method('execute')->willReturn(true);
        $stmtEq->method('fetchColumn')->willReturn(10);

        $stmtCheckUso = $this->createMock(PDOStatement::class);
        $stmtCheckUso->method('execute')->willReturn(true);
        $stmtCheckUso->method('fetchColumn')->willReturn(0);

        $stmtDelete = $this->createMock(PDOStatement::class);
        $stmtDelete->method('execute')->willReturn(true);

        $stmtUpdateArt = $this->createMock(PDOStatement::class);
        $stmtUpdateArt->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (
            $stmtCheckDev, $stmtAsig, $stmtEq, $stmtCheckUso, $stmtDelete, $stmtUpdateArt
        ) {
            if (str_contains($sql, 'FROM devoluciones WHERE id_devolucion = ?') && !str_contains($sql, 'SELECT id_asignacion')) {
                return $stmtCheckDev;
            }
            if (str_contains($sql, 'SELECT id_asignacion FROM devoluciones')) {
                return $stmtAsig;
            }
            if (str_contains($sql, 'SELECT codigo_articulo FROM asignaciones')) {
                return $stmtEq;
            }
            if (str_contains($sql, 'SELECT COUNT(*) FROM asignaciones')) {
                return $stmtCheckUso;
            }
            if (str_contains($sql, 'DELETE FROM devoluciones')) {
                return $stmtDelete;
            }
            return $stmtUpdateArt;
        });

        $asigMock = $this->createMock(ModeloAsignaciones::class);
        $asigMock->expects($this->once())
            ->method('CambiarEstatusAsignacion')
            ->with(5, 1, $pdoMock)
            ->willReturn(true);

        $modelo = $this->crearModeloConPDOMock($pdoMock);
        $modelo->setAsignaciones($asigMock);

        $datos = [
            'accion'        => 'anular',
            'id_devolucion' => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Anulación procesada, equipo reasignado.', $resultado['mensaje']);
    }

    // Caso 15: Error de base de datos durante la anulación
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-15')]
    public function testErrorBaseDeDatosEnAnulacion(): void
    {
        // Arrange
        $stmtCheckDev = $this->createMock(PDOStatement::class);
        $stmtCheckDev->method('execute')->willReturn(true);
        $stmtCheckDev->method('fetchColumn')->willReturn(1);

        $stmtAsig = $this->createMock(PDOStatement::class);
        $stmtAsig->method('execute')->willReturn(true);
        $stmtAsig->method('fetchColumn')->willReturn(5);

        $stmtEq = $this->createMock(PDOStatement::class);
        $stmtEq->method('execute')->willThrowException(new PDOException('Error de lectura en bloqueo'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckDev, $stmtAsig, $stmtEq) {
            if (str_contains($sql, 'FROM devoluciones WHERE id_devolucion = ?') && !str_contains($sql, 'SELECT id_asignacion')) {
                return $stmtCheckDev;
            }
            if (str_contains($sql, 'SELECT id_asignacion FROM devoluciones')) {
                return $stmtAsig;
            }
            return $stmtEq;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'        => 'anular',
            'id_devolucion' => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de lectura en bloqueo', $resultado['mensaje']);
    }
}

