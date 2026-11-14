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
#[Group('RF-04')]
#[Group('ESC-04')]
class AnularAsignacionesTest extends TestCase
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

    // Caso 25: Anulación exitosa
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-25')]
    public function testAnulacionExitosa(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1); // Asignación existe

        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn('10'); // Código del artículo asignado

        $stmtAnular = $this->createMock(PDOStatement::class);
        $stmtAnular->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExiste, $stmtCheck, $stmtAnular) {
            if (str_contains($sql, 'SELECT COUNT(*) FROM `asignaciones`')) {
                return $stmtExiste;
            }
            if (str_contains($sql, 'SELECT codigo_articulo FROM asignaciones')) {
                return $stmtCheck;
            }
            return $stmtAnular;
        });

        $articulosMock = $this->createMock(ModeloArticulosInventario::class);
        $articulosMock->expects($this->once())
            ->method('CambiarEstatus')
            ->with('10', 1, $pdoMock)
            ->willReturn(true);

        $modelo = $this->crearModeloConPDOMock($pdoMock);
        $modelo->setArticulos($articulosMock);

        $datos = [
            'accion'        => 'anular',
            'id_asignacion' => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals('Asignación anulada.', $resultado['mensaje']);
    }

    // Caso 26: Rechazo al anular asignación inexistente
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-26')]
    public function testAnulacionAsignacionNoExiste(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(0); // Asignación no existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtExiste);
        $pdoMock->method('inTransaction')->willReturn(true);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'        => 'anular',
            'id_asignacion' => '999'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_NO_EXISTE', $resultado['codigo']);
    }

    // Caso 27: Manejo de error de base de datos durante anulación
    #[Group('RF-04')]
    #[Group('ESC-04')]
    #[Group('CP-27')]
    public function testErrorBaseDeDatosEnAnulacion(): void
    {
        // Arrange
        $stmtExiste = $this->createMock(PDOStatement::class);
        $stmtExiste->method('execute')->willReturn(true);
        $stmtExiste->method('fetchColumn')->willReturn(1);

        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willThrowException(new PDOException('Fallo al bloquear registro'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack');

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtExiste, $stmtCheck) {
            if (str_contains($sql, 'SELECT COUNT(*) FROM `asignaciones`')) {
                return $stmtExiste;
            }
            return $stmtCheck;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $datos = [
            'accion'        => 'anular',
            'id_asignacion' => '1'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('ERR_BD', $resultado['codigo']);
    }
}

