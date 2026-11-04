<?php

namespace tests\Unit\EstadoFisico;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadoFisico;
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
#[Group('unitEstadoFisico')]
#[Group('RF-03')]
#[Group('ESC-03')]
class ModificarEstadoFisicoTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloEstadoFisico
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloEstadoFisico();
    }

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $datos = [
            'accion'       => 'modificar',
            'id_estado'    => '1',
            'nombre'       => 'Excelente',
            'nivel_estado' => '1'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 7: Modificación exitosa conservando su propio nombre
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-07')]
    public function testModificarEstadoFisicoExitosoMismoNombre(): void
    {
        // Arrange
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(1); // Es su propio nombre

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtPropio, $stmtUpdate) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, ':id')) {
                return $stmtPropio;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Estado físico modificado exitosamente.', $resultado['mensaje']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);
    }

    // Caso 8: Modificación exitosa cambiando a un nuevo nombre no duplicado
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-08')]
    public function testModificarEstadoFisicoExitosoNuevoNombre(): void
    {
        // Arrange
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(0); // Cambió de nombre

        $stmtGeneral = $this->createMock(PDOStatement::class);
        $stmtGeneral->method('execute')->willReturn(true);
        $stmtGeneral->method('fetchColumn')->willReturn(0); // Nadie más lo tiene

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtPropio, $stmtGeneral, $stmtUpdate) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, ':id')) {
                return $stmtPropio;
            }
            if (str_contains($sql, 'COUNT(*)') && !str_contains($sql, ':id')) {
                return $stmtGeneral;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos(['nombre' => 'Óptimo']));

        // Assert
        $this->assertEquals('modificar', $resultado['accion']);
        $this->assertEquals('Estado físico modificado exitosamente.', $resultado['mensaje']);
    }

    // Caso 9: Rechazo si el nuevo nombre ya pertenece a otro registro
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-09')]
    public function testModificarNombreDuplicadoEnOtroRegistro(): void
    {
        // Arrange
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(0); // No es su propio nombre

        $stmtGeneral = $this->createMock(PDOStatement::class);
        $stmtGeneral->method('execute')->willReturn(true);
        $stmtGeneral->method('fetchColumn')->willReturn(1); // Pertenece a otro registro

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtPropio, $stmtGeneral) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, ':id')) {
                return $stmtPropio;
            }
            return $stmtGeneral;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos(['nombre' => 'Dañado']));

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe otro estado físico registrado con este nombre.', $resultado['mensaje']);
    }

    // Caso 10: Error de base de datos durante la modificación
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-10')]
    public function testErrorBaseDeDatosEnModificar(): void
    {
        // Arrange
        $stmtPropio = $this->createMock(PDOStatement::class);
        $stmtPropio->method('execute')->willReturn(true);
        $stmtPropio->method('fetchColumn')->willReturn(1);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Error de sintaxis o conexión'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtPropio, $stmtUpdate) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, ':id')) {
                return $stmtPropio;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error al modificar:', $resultado['mensaje']);
    }
}
