<?php

namespace tests\Unit\Roles;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRoles;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

require_once __DIR__ . '/RolesMockTrait.php';

/**
 * RF-ROL-01: El sistema debe validar los datos recibidos antes de procesar cualquier
 * operación sobre roles: el id debe ser numérico y el nombre solo puede contener letras
 * (incluidas tildes y ñ) y espacios, con un máximo de 30 caracteres. Los textos se
 * normalizan a formato título.
 *
 * Escenarios:
 *  E1. Se envía la petición sin datos.
 *  E2. Se envía una acción que el módulo no reconoce.
 *  E3. Se envía un id no numérico.
 *  E4. Se envía un nombre con caracteres no permitidos o demasiado largo.
 *  E5. Se envían datos válidos con espacios y mayúsculas/minúsculas mezcladas.
 */
#[Group('unit')]
#[Group('unitRoles')]
#[Group('validarDatosRoles')]
class ValidarDatosRolesTest extends TestCase
{
    use RolesMockTrait;

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

    // Caso 1 (E1): Arreglo de datos vacío lanza excepción
    public function testDatosVacios(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        (new ModeloRoles())->procesarDatos([]);
    }

    // Caso 2 (E2): Acción no reconocida lanza excepción
    public function testAccionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Acción no válida.');

        (new ModeloRoles())->procesarDatos(['accion' => 'accion_inexistente']);
    }

    // Caso 3 (E3): Id con letras es rechazado
    public function testIdNoNumerico(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        (new ModeloRoles())->procesarDatos(['accion' => 'buscar', 'id' => '5abc']);
    }

    // Caso 4 (E3): Id con intento de inyección SQL es rechazado
    public function testIdConInyeccion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Id inválido.');

        (new ModeloRoles())->procesarDatos(['accion' => 'eliminar', 'id' => '1 OR 1=1']);
    }

    // Caso 5 (E4): Nombre con números es rechazado
    public function testNombreConNumeros(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        (new ModeloRoles())->procesarDatos(['accion' => 'incluir', 'nombre' => 'Entrenador 2']);
    }

    // Caso 6 (E4): Nombre con caracteres especiales es rechazado
    public function testNombreConCaracteresEspeciales(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        (new ModeloRoles())->procesarDatos(['accion' => 'incluir', 'nombre' => "<script>alert('x')</script>"]);
    }

    // Caso 7 (E4): Nombre de más de 30 caracteres es rechazado
    public function testNombreDemasiadoLargo(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Nombre inválido.');

        (new ModeloRoles())->procesarDatos(['accion' => 'incluir', 'nombre' => str_repeat('a', 31)]);
    }

    // Caso 8 (E5): Nombre válido con tildes y ñ, de exactamente 30 caracteres, es aceptado
    public function testNombreLimiteValido(): void
    {
        $modelo = new ModeloRoles();
        $this->procesarSinEjecutar($modelo, ['nombre' => 'Técnico de Logística y Montaña']);

        $this->assertSame('Técnico De Logística Y Montaña', $this->propiedad($modelo, 'nombre'));
    }

    // Caso 9 (E5): Nombre y descripción se normalizan a formato título sin espacios sobrantes
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

    // Caso 10 (E5): Valores por defecto cuando no se envían campos
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
