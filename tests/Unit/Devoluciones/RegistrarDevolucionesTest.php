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
#[Group('RF-02')]
#[Group('ESC-02')]
class RegistrarDevolucionesTest extends TestCase
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
            'accion'           => 'incluir',
            'id_asignacion'    => '1',
            'id_estado'        => '1',
            'fecha_devolucion' => date('Y-m-d'),
            'observacion'      => 'Devuelto en perfecto estado'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 4: Registro exitoso de devolución
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-04')]
    public function testRegistroDevolucionExitoso(): void
    {
        // Arrange
        $stmtCheckAsig = $this->createMock(PDOStatement::class);
        $stmtCheckAsig->method('execute')->willReturn(true);
        $stmtCheckAsig->method('fetchColumn')->willReturn(1); // Asignación activa existe

        $stmtSP = $this->createMock(PDOStatement::class);
        $stmtSP->method('execute')->willReturn(true);

        $stmtId = $this->createMock(PDOStatement::class);
        $stmtId->method('execute')->willReturn(true);
        $stmtId->method('fetchColumn')->willReturn(42); // ID de devolución generado

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckAsig, $stmtSP, $stmtId) {
            if (str_contains($sql, 'FROM asignaciones WHERE id_asignacion = ? AND estatus = 1')) {
                return $stmtCheckAsig;
            }
            if (str_contains($sql, 'CALL ProcesarDevolucionSegura')) {
                return $stmtSP;
            }
            return $stmtId;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Devolución procesada y equipo liberado.', $resultado['mensaje']);
        $this->assertEquals(42, $resultado['id_devolucion']);
    }

    // Caso 5: Rechazo si la asignación no existe o no está activa
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-05')]
    public function testAsignacionInvalidaOYaDevuelta(): void
    {
        // Arrange
        $stmtCheckAsig = $this->createMock(PDOStatement::class);
        $stmtCheckAsig->method('execute')->willReturn(true);
        $stmtCheckAsig->method('fetchColumn')->willReturn(false); // No existe o estatus != 1

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtCheckAsig);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('La asignación no existe, no es válida o ya fue devuelta.', $resultado['mensaje']);
    }

    // Caso 6: Error de base de datos durante la llamada al procedimiento almacenado
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-06')]
    public function testErrorBaseDeDatosEnProcedimientoAlmacenado(): void
    {
        // Arrange
        $stmtCheckAsig = $this->createMock(PDOStatement::class);
        $stmtCheckAsig->method('execute')->willReturn(true);
        $stmtCheckAsig->method('fetchColumn')->willReturn(1);

        $stmtSP = $this->createMock(PDOStatement::class);
        $stmtSP->method('execute')->willThrowException(new PDOException('Fallo interno del procedimiento almacenado'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckAsig, $stmtSP) {
            if (str_contains($sql, 'FROM asignaciones WHERE id_asignacion = ? AND estatus = 1')) {
                return $stmtCheckAsig;
            }
            return $stmtSP;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Fallo interno del procedimiento almacenado', $resultado['mensaje']);
    }
}

