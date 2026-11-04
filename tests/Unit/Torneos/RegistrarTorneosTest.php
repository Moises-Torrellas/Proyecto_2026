<?php

namespace tests\Unit\Torneos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloTorneos;
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
#[Group('unitTorneos')]
#[Group('RF-02')]
#[Group('ESC-02')]
class RegistrarTorneosTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(PDO $pdoMock): ModeloTorneos
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloTorneos();
    }

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $datos = [
            'accion'       => 'incluir',
            'nombre'       => 'Copa Oro Hockey',
            'fecha_inicio' => date('Y-m-d', strtotime('+3 days')),
            'fecha_fin'    => date('Y-m-d', strtotime('+7 days')),
            'ubicacion'    => 'Pista de Hielo Barquisimeto',
            'estatus'      => '1'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 14: Inclusión exitosa de un torneo con datos válidos
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-14')]
    public function testIncluirTorneoExitoso(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0); // No existe torneo duplicado

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsert) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'torneos')) {
                return $stmtCheck;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 15: Rechazo de inclusión cuando ya existe un torneo con el mismo nombre
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-15')]
    public function testIncluirNombreDuplicadoRechazado(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(1); // Ya existe un torneo con este nombre

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtCheck);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe un torneo registrado con este nombre.', $resultado['codigo']);
    }

    // Caso 16: Error de base de datos durante el INSERT
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-16')]
    public function testIncluirErrorBaseDeDatosEnInsercion(): void
    {
        // Arrange
        $stmtCheck = $this->createMock(PDOStatement::class);
        $stmtCheck->method('execute')->willReturn(true);
        $stmtCheck->method('fetchColumn')->willReturn(0);

        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willThrowException(new PDOException('Error de comunicación con el motor SQL'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheck, $stmtInsert) {
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'torneos')) {
                return $stmtCheck;
            }
            return $stmtInsert;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error de comunicación con el motor SQL', $resultado['codigo']);
    }
}

