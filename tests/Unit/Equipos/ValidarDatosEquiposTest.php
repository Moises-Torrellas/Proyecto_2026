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

    public function testDatosVaciosLanzaExcepcion(): void
    {
        $modelo = $this->crearModeloConPDOMock();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar el registro del equipo.');

        $modelo->ProcesarDatos([]);
    }

    public function testAccionInvalidaLanzaExcepcion(): void
    {
        $modelo = $this->crearModeloConPDOMock();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada para el equipo no es válida.');

        $modelo->ProcesarDatos(['accion' => 'accion_no_existente']);
    }

    public function testIdInvalidoNoEnteroLanzaExcepcion(): void
    {
        $modelo = $this->crearModeloConPDOMock();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        $modelo->ProcesarDatos(['accion' => 'incluir', 'id' => 'abc#12']);
    }

    public function testNombreInvalidoLanzaExcepcion(): void
    {
        $modelo = $this->crearModeloConPDOMock();
        $casos = [
            'caracteres especiales' => 'Cannibals@#$Equipo',
            'longitud corta'        => 'CA',
            'longitud excesiva'     => 'Equipo de Hockey Extremo de Lara Campeones'
        ];

        foreach ($casos as $tipo => $nombre) {
            try {
                $modelo->ProcesarDatos(['accion' => 'incluir', 'nombre' => $nombre]);
                $this->fail("Se esperaba excepción para nombre con [$tipo] y no se lanzó.");
            } catch (Exception $e) {
                $this->assertEquals('Nombre inválido. Solo se permiten letras, números, espacios y guiones.', $e->getMessage());
            }
        }
    }

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

    public function testGuardarDetallesEquipoIdInvalidoLanzaExcepcion(): void
    {
        $modelo = $this->crearModeloConPDOMock();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ID de equipo inválido.');

        $modelo->GuardarDetallesEquipo(0, [1, 2]);
    }
}
