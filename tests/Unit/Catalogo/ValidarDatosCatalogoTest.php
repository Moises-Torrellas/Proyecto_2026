<?php

namespace tests\Unit\Catalogo;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCatalogo;
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
#[Group('unitCatalogo')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosCatalogoTest extends TestCase
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
        $modelo = new ModeloCatalogo();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo->ProcesarDatos([]);
    }

    public function testAccionInvalidaLanzaExcepcion(): void
    {
        $modelo = new ModeloCatalogo();
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La accion no es valida');

        $modelo->ProcesarDatos(['accion' => 'accion_no_valida', 'nombre' => 'Casco Hockey']);
    }

    public function testValidarCamposInvalidosLanzaExcepcion(): void
    {
        $modelo = new ModeloCatalogo();
        $casos = [
            'id catalogo invalido' => [['accion' => 'buscar', 'id_catalogo' => '10abc', 'nombre' => 'Casco Hockey'], 'Id inválido.'],
            'nombre menor a 3 car' => [['accion' => 'incluir', 'nombre' => 'AB'], 'Nombre inválido.'],
            'id categoria invalido'=> [['accion' => 'incluir', 'nombre' => 'Casco Hockey', 'id_categoria' => 'cat-1'], 'Categoría inválida.'],
            'stock minimo no entero'=> [['accion' => 'incluir', 'nombre' => 'Casco Hockey', 'id_categoria' => '1', 'stock_minimo' => 'cinco'], 'Stock mínimo debe ser un número entero.'],
            'talla con caracteres' => [['accion' => 'incluir', 'nombre' => 'Casco Hockey', 'id_categoria' => '1', 'talla' => 'Talla@Pro!'], 'Talla inválida.'],
            'posicion con letras'  => [['accion' => 'incluir', 'nombre' => 'Casco Hockey', 'id_categoria' => '1', 'codigo_posicion' => 'portero_1'], 'Posición inválida.']
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

    public function testNormalizacionNombreYTalla(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $reflectorConex = new ReflectionClass(Conexion::class);
        $propiedad = $reflectorConex->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);

        $modelo = new ModeloCatalogo();
        $modelo->ProcesarDatos([
            'accion'          => 'buscar',
            'id_catalogo'     => '1',
            'nombre'          => '   casco de arquero cannibals   ',
            'id_categoria'    => '2',
            'stock_minimo'    => '5',
            'talla'           => '   xl / l   ',
            'codigo_posicion' => '3'
        ]);

        $reflector = new ReflectionClass(ModeloCatalogo::class);
        $this->assertEquals('Casco De Arquero Cannibals', $reflector->getProperty('nombre')->getValue($modelo));
        $this->assertEquals('XL / L', $reflector->getProperty('talla')->getValue($modelo));
    }
}
