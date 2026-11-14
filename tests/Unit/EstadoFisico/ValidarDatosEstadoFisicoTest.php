<?php

namespace tests\Unit\EstadoFisico;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadoFisico;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitEstadoFisico')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosEstadoFisicoTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    // Caso 1: Arreglo de datos vacío lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    public function testDatosVaciosLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloEstadoFisico();
        $datos = [];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 2: Acción desconocida o inválida lanza excepción
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    public function testAccionInvalidaLanzaExcepcion(): void
    {
        // Arrange
        $modelo = new ModeloEstadoFisico();
        $datos = [
            'accion' => 'accion_no_valida',
            'nombre' => 'Excelente'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        // Act
        $modelo->ProcesarDatos($datos);
    }

    // Caso 3: Conversión de nombre a formato título (Title Case)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    public function testConversionNombreTitleCase(): void
    {
        // Arrange
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(\PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $reflectorConex = new ReflectionClass(Conexion::class);
        $propiedad = $reflectorConex->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        $modelo = new ModeloEstadoFisico();
        $datos = [
            'accion'       => 'buscar',
            'id_estado'    => 1,
            'nombre'       => '   buen estado físico   ',
            'nivel_estado' => 2
        ];

        // Act
        $modelo->ProcesarDatos($datos);

        // Assert
        $reflector = new ReflectionClass(ModeloEstadoFisico::class);
        $propNombre = $reflector->getProperty('nombre');
        $valorNombre = $propNombre->getValue($modelo);

        $this->assertEquals('Buen Estado Físico', $valorNombre);
    }
}
