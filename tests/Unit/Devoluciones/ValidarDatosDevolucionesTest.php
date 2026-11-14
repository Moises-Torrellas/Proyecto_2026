<?php

namespace tests\Unit\Devoluciones;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloDevoluciones;
use App\modelo\Conexion;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitDevoluciones')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosDevolucionesTest extends TestCase
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

    // Caso 1: Acción no válida o desconocida
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    public function testAccionInvalida(): void
    {
        // Arrange
        $modelo = new ModeloDevoluciones();
        $datos = [
            'accion' => 'accion_invalida'
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Acción no válida', $resultado['codigo']);
    }

    // Caso 2: Arreglo de datos vacío o sin acción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    public function testDatosVacios(): void
    {
        // Arrange
        $modelo = new ModeloDevoluciones();
        $datos = [];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Acción no válida', $resultado['codigo']);
    }

    // Caso 3: Sanitización de observación con espacios en blanco
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    public function testObservacionSanitizada(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);
        $datos = [
            'accion'      => 'consultar',
            'observacion' => '   Devuelto con desgaste normal   '
        ];

        // Act
        $resultado = $modelo->ProcesarDatos($datos);

        // Assert
        $reflector = new ReflectionClass(ModeloDevoluciones::class);
        $propObservacion = $reflector->getProperty('observacion');
        $valorObservacion = $propObservacion->getValue($modelo);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertEquals('Devuelto con desgaste normal', $valorObservacion);
    }
}

