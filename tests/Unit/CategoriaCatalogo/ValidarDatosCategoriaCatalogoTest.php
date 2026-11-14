<?php

namespace tests\Unit\CategoriaCatalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCategoriaCatalogo;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use PDO;
use PDOStatement;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitCategoriaCatalogo')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosCategoriaCatalogoTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    public function testDatosVaciosLanzaExcepcion(): void
    {
        $modelo = new ModeloCategoriaCatalogo();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo->ProcesarDatos([]);
    }

    public function testAccionInvalidaLanzaExcepcion(): void
    {
        $modelo = new ModeloCategoriaCatalogo();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        $modelo->ProcesarDatos(['accion' => 'accion_no_valida', 'nombre' => 'Cascos']);
    }

    public function testValidarCamposInvalidosLanzaExcepcion(): void
    {
        $modelo = new ModeloCategoriaCatalogo();
        $casos = [
            'id categoria invalido' => [['accion' => 'buscar', 'id_categoria' => '12abc', 'nombre' => 'Cascos'], 'Id inválido.'],
            'nombre menor a 2 car'  => [['accion' => 'incluir', 'nombre' => 'C'], 'Nombre de categoría inválido.'],
            'descripcion invalida'  => [['accion' => 'incluir', 'nombre' => 'Cascos', 'descripcion' => 'Descripción #1 con @ símbolos'], 'Descripción inválida.']
        ];

        foreach ($casos as $nombre => [$datos, $mensajeEsperado]) {
            try {
                $modelo->ProcesarDatos($datos);
                $this->fail("Se esperaba excepción en caso [$nombre] y no se lanzó.");
            } catch (Exception $e) {
                $this->assertEquals($mensajeEsperado, $e->getMessage(), "Mensaje incorrecto en [$nombre]");
            }
        }
    }

    public function testConversionNombreTitleCaseYTrim(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $reflectorConex = new ReflectionClass(Conexion::class);
        $propiedad = $reflectorConex->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        $modelo = new ModeloCategoriaCatalogo();
        $modelo->ProcesarDatos([
            'accion'       => 'buscar',
            'id_categoria' => '1',
            'nombre'       => '   cascos de portero   ',
            'tipo_talla'   => 'Letras'
        ]);

        $reflector = new ReflectionClass(ModeloCategoriaCatalogo::class);
        $this->assertEquals('Cascos De Portero', $reflector->getProperty('nombre')->getValue($modelo));
    }
}
