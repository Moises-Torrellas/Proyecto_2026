<?php

namespace tests\Unit\Roles;

use App\modelo\Conexion;
use Exception;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRoles;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitRoles')]
#[Group('validarDatosRoles')]
class ValidarDatosRolesTest extends TestCase
{
    protected array $sentencias = [];

    protected array $sentenciasBdGeneral = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $this->sentencias = [];
        $this->sentenciasBdGeneral = [];
        parent::tearDown();
    }

    private function procesarSinEjecutar(ModeloRoles $modelo, array $datos): void
    {
        try {
            $modelo->procesarDatos($datos + ['accion' => 'accion_inexistente']);
        } catch (Exception $e) {
            if ($e->getMessage() !== 'Acción no válida.') {
                throw $e;
            }
        }
    }

    private function propiedad(ModeloRoles $modelo, string $nombre): mixed
    {
        return (new ReflectionClass($modelo))->getProperty($nombre)->getValue($modelo);
    }

    public function testDatosVacios(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        (new ModeloRoles())->procesarDatos([]);
    }

    public function testAccionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Acción no válida.');

        (new ModeloRoles())->procesarDatos(['accion' => 'accion_inexistente']);
    }

    public function testIdNoNumerico(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        (new ModeloRoles())->procesarDatos(['accion' => 'buscar', 'id' => '5abc']);
    }

    public function testIdConInyeccion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        (new ModeloRoles())->procesarDatos(['accion' => 'eliminar', 'id' => '1 OR 1=1']);
    }

    public function testNombreConNumeros(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        (new ModeloRoles())->procesarDatos(['accion' => 'incluir', 'nombre' => 'Entrenador 2']);
    }

    public function testNombreConCaracteresEspeciales(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        (new ModeloRoles())->procesarDatos(['accion' => 'incluir', 'nombre' => "<script>alert('x')</script>"]);
    }

    public function testNombreDemasiadoLargo(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        (new ModeloRoles())->procesarDatos(['accion' => 'incluir', 'nombre' => str_repeat('a', 31)]);
    }

    public function testNombreLimiteValido(): void
    {
        $modelo = new ModeloRoles();
        $this->procesarSinEjecutar($modelo, ['nombre' => 'Técnico de Logística y Montaña']);

        $this->assertSame('Técnico De Logística Y Montaña', $this->propiedad($modelo, 'nombre'));
    }

    public function testNormalizaTextos(): void
    {
        $modelo = new ModeloRoles();
        $this->procesarSinEjecutar($modelo, [
            'id'          => '7',
            'nombre'      => '  entrenador auxiliar ',
            'descripcion' => ' apoya al ENTRENADOR principal ',
            'permisos'    => [3 => 1],
        ]);

        $this->assertSame(7, $this->propiedad($modelo, 'id'));
        $this->assertSame('Entrenador Auxiliar', $this->propiedad($modelo, 'nombre'));
        $this->assertSame('Apoya Al Entrenador Principal', $this->propiedad($modelo, 'descripcion'));
        $this->assertSame([3 => 1], $this->propiedad($modelo, 'permisos_seleccionados'));
    }

    public function testValoresPorDefecto(): void
    {
        $modelo = new ModeloRoles();
        $this->procesarSinEjecutar($modelo, ['otro' => 'x']);

        $this->assertSame(0, $this->propiedad($modelo, 'id'));
        $this->assertSame('', $this->propiedad($modelo, 'nombre'));
        $this->assertSame('', $this->propiedad($modelo, 'descripcion'));
        $this->assertSame([], $this->propiedad($modelo, 'permisos_seleccionados'));
    }
}
