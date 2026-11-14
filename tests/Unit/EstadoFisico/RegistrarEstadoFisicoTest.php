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
#[Group('RF-02')]
#[Group('ESC-02')]
class RegistrarEstadoFisicoTest extends TestCase
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
            'accion'       => 'incluir',
            'nombre'       => 'Excelente',
            'nivel_estado' => '1'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 4: Registro exitoso de estado físico
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-04')]
    public function testIncluirEstadoFisicoExitoso(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0); // No existe duplicado

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsert) {
            if (str_contains($sql, 'SELECT COUNT(*) FROM `estado_fisico`')) {
                return $stmtCheck;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('incluir', $resultado['accion']);
        $this->assertEquals('Estado físico registrado exitosamente.', $resultado['mensaje']);
        $this->assertArrayHasKey('datos_nuevos', $resultado);
    }

    // Caso 5: Rechazo por nombre de estado físico duplicado
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-05')]
    public function testIncluirNombreDuplicado(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(1); // Ya existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtCheck);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe un estado físico registrado con este nombre.', $resultado['mensaje']);
    }

    // Caso 6: Error de base de datos durante la inserción
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-06')]
    public function testErrorBaseDeDatosEnIncluir(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willThrowException(new PDOException('Error de conexión o clave duplicada'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsert) {
            if (str_contains($sql, 'SELECT COUNT(*) FROM `estado_fisico`')) {
                return $stmtCheck;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error al incluir:', $resultado['mensaje']);
    }
}

