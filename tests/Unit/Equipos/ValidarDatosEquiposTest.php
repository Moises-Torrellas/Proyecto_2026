<?php

namespace tests\Unit\Equipos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEquipos;
use App\modelo\Conexion;
use PDO;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitEquipos')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosEquiposTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function crearModeloConPDOMock(?PDO $pdoMock = null): ModeloEquipos
    {
        if ($pdoMock === null) {
            $pdoMock = $this->createMock(PDO::class);
        }
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        return new ModeloEquipos();
    }

    // Caso 01: Envío de arreglo vacío a ProcesarDatos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    public function testDatosVaciosLanzaExcepcion(): void
    {
        $modelo = $this->crearModeloConPDOMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar el registro del equipo.');

        $modelo->ProcesarDatos([]);
    }

    // Caso 02: Envío de acción no válida en el arreglo de datos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    public function testAccionInvalidaLanzaExcepcion(): void
    {
        $modelo = $this->crearModeloConPDOMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada para el equipo no es válida.');

        $modelo->ProcesarDatos([
            'accion' => 'accion_no_existente'
        ]);
    }

    // Caso 03: Validación de ID no entero o inválido
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    public function testIdInvalidoNoEnteroLanzaExcepcion(): void
    {
        $modelo = $this->crearModeloConPDOMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        $modelo->ProcesarDatos([
            'accion' => 'incluir',
            'id'     => 'abc#12'
        ]);
    }

    // Caso 04: Nombre con caracteres especiales no permitidos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-04')]
    public function testNombreInvalidoPorCaracteresEspeciales(): void
    {
        $modelo = $this->crearModeloConPDOMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido. Solo se permiten letras, números, espacios y guiones.');

        $modelo->ProcesarDatos([
            'accion' => 'incluir',
            'nombre' => 'Cannibals@#$Equipo'
        ]);
    }

    // Caso 05: Nombre con longitud menor a la requerida (< 3 caracteres)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-05')]
    public function testNombreInvalidoPorLongitudCorta(): void
    {
        $modelo = $this->crearModeloConPDOMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido. Solo se permiten letras, números, espacios y guiones.');

        $modelo->ProcesarDatos([
            'accion' => 'incluir',
            'nombre' => 'CA'
        ]);
    }

    // Caso 06: Nombre con longitud superior a 30 caracteres
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-06')]
    public function testNombreInvalidoPorLongitudExcesiva(): void
    {
        $modelo = $this->crearModeloConPDOMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido. Solo se permiten letras, números, espacios y guiones.');

        $modelo->ProcesarDatos([
            'accion' => 'incluir',
            'nombre' => 'Equipo de Hockey Extremo de Lara Campeones'
        ]);
    }

    // Caso 07: Nombre válido con letras, números, guiones y normalización Title Case
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-07')]
    public function testNombreValidoConGuionesYNumerosAceptado(): void
    {
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([
            ['id_equipos' => 1, 'nombre' => 'Cannibals Sub-20']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $modelo = $this->crearModeloConPDOMock($pdoMock);

        $resultado = $modelo->ProcesarDatos([
            'accion' => 'buscar',
            'id'     => 1,
            'nombre' => 'cannibals sub-20'
        ]);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 08: Invocación de GuardarDetallesEquipo con ID de equipo inválido (<= 0)
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-08')]
    public function testGuardarDetallesEquipoIdInvalidoLanzaExcepcion(): void
    {
        $modelo = $this->crearModeloConPDOMock();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ID de equipo inválido.');

        $modelo->GuardarDetallesEquipo(0, [1, 2]);
    }
}

