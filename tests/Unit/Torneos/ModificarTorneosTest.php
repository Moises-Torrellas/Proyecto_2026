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
#[Group('RF-03')]
#[Group('ESC-03')]
class ModificarTorneosTest extends TestCase
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

    private function obtenerDatosModificarValidos(array $sobrescribir = []): array
    {
        $datos = [
            'accion'        => 'modificar',
            'codigo_torneo' => 1,
            'nombre'        => 'Torneo Clausura 2026',
            'fecha_inicio'  => date('Y-m-d', strtotime('+10 days')),
            'fecha_fin'     => date('Y-m-d', strtotime('+20 days')),
            'ubicacion'     => 'Complejo Deportivo Central',
            'estatus'       => '1'
        ];

        return array_merge($datos, $sobrescribir);
    }

    // Caso 17: Modificación exitosa actualizando nombre sin colisión
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-17')]
    public function testModificarTorneoConNuevoNombreExitoso(): void
    {
        // Arrange
        $stmtCheckPropia = $this->createMock(PDOStatement::class);
        $stmtCheckPropia->method('execute')->willReturn(true);
        $stmtCheckPropia->method('fetchColumn')->willReturn(0); // El nombre cambió respecto al que tenía

        $stmtCheckGlobal = $this->createMock(PDOStatement::class);
        $stmtCheckGlobal->method('execute')->willReturn(true);
        $stmtCheckGlobal->method('fetchColumn')->willReturn(0); // No existe otro torneo con este nuevo nombre

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckPropia, $stmtCheckGlobal, $stmtUpdate) {
            if (str_contains($sql, 'codigo_torneo = :id')) {
                return $stmtCheckPropia;
            }
            if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'torneos')) {
                return $stmtCheckGlobal;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 18: Modificación exitosa conservando su propio nombre
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-18')]
    public function testModificarTorneoConservandoMismoNombreExitoso(): void
    {
        // Arrange
        $stmtCheckPropia = $this->createMock(PDOStatement::class);
        $stmtCheckPropia->method('execute')->willReturn(true);
        $stmtCheckPropia->method('fetchColumn')->willReturn(1); // Es el mismo torneo conservando su nombre

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckPropia, $stmtUpdate) {
            if (str_contains($sql, 'codigo_torneo = :id')) {
                return $stmtCheckPropia;
            }
            return $stmtUpdate;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 19: Rechazo de modificación cuando el nuevo nombre ya pertenece a otro torneo registrado
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-19')]
    public function testModificarTorneoNombreDuplicadoEnOtroTorneoRechazado(): void
    {
        // Arrange
        $stmtCheckPropia = $this->createMock(PDOStatement::class);
        $stmtCheckPropia->method('execute')->willReturn(true);
        $stmtCheckPropia->method('fetchColumn')->willReturn(0); // No es su nombre actual

        $stmtCheckGlobal = $this->createMock(PDOStatement::class);
        $stmtCheckGlobal->method('execute')->willReturn(true);
        $stmtCheckGlobal->method('fetchColumn')->willReturn(1); // Ya existe en otro registro

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckPropia, $stmtCheckGlobal) {
            if (str_contains($sql, 'codigo_torneo = :id')) {
                return $stmtCheckPropia;
            }
            return $stmtCheckGlobal;
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Ya existe otro torneo registrado con este nombre.', $resultado['codigo']);
    }

    // Caso 20: Captura de excepción en base de datos durante el UPDATE
    #[Group('RF-03')]
    #[Group('ESC-03')]
    #[Group('CP-20')]
    public function testModificarTorneoFalloBaseDeDatos(): void
    {
        // Arrange
        $stmtCheckPropia = $this->createMock(PDOStatement::class);
        $stmtCheckPropia->method('execute')->willReturn(true);
        $stmtCheckPropia->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use ($stmtCheckPropia) {
            if (str_contains($sql, 'codigo_torneo = :id')) {
                return $stmtCheckPropia;
            }
            throw new PDOException('Error de conexión simulado al actualizar torneo');
        });

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        // Act
        $resultado = $modelo->ProcesarDatos($this->obtenerDatosModificarValidos());

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error de conexión simulado', $resultado['codigo']);
    }
}

