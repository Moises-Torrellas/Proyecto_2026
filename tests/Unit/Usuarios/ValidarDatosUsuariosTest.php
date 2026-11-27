<?php

namespace tests\Unit\Usuarios;

use App\modelo\Conexion;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloUsuarios;
use Exception;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('validarDatosUsuarios')]
class ValidarDatosUsuariosTest extends TestCase
{
    protected array $sentencias = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $_SESSION = [];
        $this->sentencias = [];
        parent::tearDown();
    }

    private function procesarSinEjecutar(ModeloUsuarios $modelo, array $datos): void
    {
        try {
            $modelo->procesarDatos($datos + ['accion' => 'accion_inexistente']);
        } catch (Exception $e) {
        }
    }

    private function propiedad(ModeloUsuarios $modelo, string $nombre): mixed
    {
        return (new ReflectionClass($modelo))->getProperty($nombre)->getValue($modelo);
    }

    public function testDatosVacios(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        (new ModeloUsuarios())->procesarDatos([]);
    }

    public function testAccionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Acción no válida.');

        (new ModeloUsuarios())->procesarDatos(['accion' => 'accion_inexistente']);
    }

    public function testSinAccion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Acción no válida.');

        (new ModeloUsuarios())->procesarDatos(['cedula' => '12345678']);
    }

    public function testNormalizaNombreYApellido(): void
    {
        $modelo = new ModeloUsuarios();
        $this->procesarSinEjecutar($modelo, [
            'nombre'   => '  maRÍa joSÉ ',
            'apellido' => ' pÉREZ gonzález  ',
        ]);

        $this->assertSame('María José', $this->propiedad($modelo, 'nombre'));
        $this->assertSame('Pérez González', $this->propiedad($modelo, 'apellido'));
    }

    public function testNormalizaCorreo(): void
    {
        $modelo = new ModeloUsuarios();
        $this->procesarSinEjecutar($modelo, ['correo' => '  Usuario.PRUEBA@Correo.COM ']);

        $this->assertSame('usuario.prueba@correo.com', $this->propiedad($modelo, 'correo'));
    }

    public function testAsignaCamposBasicos(): void
    {
        $modelo = new ModeloUsuarios();
        $this->procesarSinEjecutar($modelo, [
            'id'       => 7,
            'cedula'   => ' 12345678 ',
            'telefono' => ' 0414-1234567 ',
            'roles_id' => 3,
            'foto'     => 'user_12345678.png',
            'bloqueo'  => 1,
        ]);

        $this->assertSame(7, $this->propiedad($modelo, 'id'));
        $this->assertSame('12345678', $this->propiedad($modelo, 'cedula'));
        $this->assertSame('0414-1234567', $this->propiedad($modelo, 'telefono'));
        $this->assertSame(3, $this->propiedad($modelo, 'rol'));
        $this->assertSame('user_12345678.png', $this->propiedad($modelo, 'foto'));
        $this->assertSame(1, $this->propiedad($modelo, 'bloqueo'));
    }

    public function testContraseñaSeCifra(): void
    {
        $modelo = new ModeloUsuarios();
        $this->procesarSinEjecutar($modelo, ['contraseña' => 'Clave.Segura123']);

        $hash = $this->propiedad($modelo, 'contraseña');

        $this->assertNotSame('Clave.Segura123', $hash);
        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(password_verify('Clave.Segura123', $hash));
        $this->assertTrue($this->propiedad($modelo, 'actualizar_contraseña'));
    }

    public function testSinContraseña(): void
    {
        $modelo = new ModeloUsuarios();
        $this->procesarSinEjecutar($modelo, ['contraseña' => '']);

        $this->assertNull($this->propiedad($modelo, 'contraseña'));
        $this->assertFalse($this->propiedad($modelo, 'actualizar_contraseña'));
    }

    public function testValoresPorDefecto(): void
    {
        $modelo = new ModeloUsuarios();
        $this->procesarSinEjecutar($modelo, ['otro' => 'x']);

        $this->assertNull($this->propiedad($modelo, 'id'));
        $this->assertSame('', $this->propiedad($modelo, 'cedula'));
        $this->assertSame('', $this->propiedad($modelo, 'nombre'));
        $this->assertSame('', $this->propiedad($modelo, 'bloqueo'));
        $this->assertNull($this->propiedad($modelo, 'rol'));
        $this->assertSame([], $this->propiedad($modelo, 'permisos_seleccionados'));
    }
}
