<?php

namespace tests\Unit\Usuarios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloUsuarios;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

require_once __DIR__ . '/UsuariosMockTrait.php';

/**
 * RF-USU-01: El sistema debe validar y normalizar los datos recibidos antes de procesar
 * cualquier operación sobre usuarios.
 *
 * Escenarios:
 *  E1. Se envía la petición sin datos.
 *  E2. Se envía una acción que el módulo no reconoce.
 *  E3. Se envían datos con espacios y mayúsculas/minúsculas mezcladas.
 *  E4. Se envía una contraseña (debe almacenarse cifrada, nunca en texto plano).
 *  E5. No se envía contraseña.
 */
#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('validarDatosUsuarios')]
class ValidarDatosUsuariosTest extends TestCase
{
    use UsuariosMockTrait;

    private function procesarSinEjecutar(ModeloUsuarios $modelo, array $datos): void
    {
        try {
            $modelo->procesarDatos($datos + ['accion' => 'accion_inexistente']);
        } catch (Exception $e) {
            // Se ignora la excepción de acción inválida para inspeccionar los datos asignados
        }
    }

    private function propiedad(ModeloUsuarios $modelo, string $nombre): mixed
    {
        return (new ReflectionClass($modelo))->getProperty($nombre)->getValue($modelo);
    }

    // Caso 1 (E1): Arreglo de datos vacío lanza excepción
    public function testDatosVacios(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        (new ModeloUsuarios())->procesarDatos([]);
    }

    // Caso 2 (E2): Acción no reconocida lanza excepción
    public function testAccionInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Acción no válida.');

        (new ModeloUsuarios())->procesarDatos(['accion' => 'accion_inexistente']);
    }

    // Caso 3 (E2): Petición sin acción lanza excepción
    public function testSinAccion(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Acción no válida.');

        (new ModeloUsuarios())->procesarDatos(['cedula' => '12345678']);
    }

    // Caso 4 (E3): Nombre y apellido se guardan en formato título y sin espacios sobrantes
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

    // Caso 5 (E3): El correo se guarda en minúsculas y sin espacios
    public function testNormalizaCorreo(): void
    {
        $modelo = new ModeloUsuarios();
        $this->procesarSinEjecutar($modelo, ['correo' => '  Usuario.PRUEBA@Correo.COM ']);

        $this->assertSame('usuario.prueba@correo.com', $this->propiedad($modelo, 'correo'));
    }

    // Caso 6 (E3): Cédula y teléfono se recortan; rol, foto e id se asignan tal cual
    public function testAsignaCamposBasicos(): void
    {
        $modelo = new ModeloUsuarios();
        $this->procesarSinEjecutar($modelo, [
            'id'       => 7,
            'cedula'   => ' 12345678 ',
            'telefono' => ' 04141234567 ',
            'roles_id' => 3,
            'foto'     => 'user_12345678.png',
            'bloqueo'  => 1,
        ]);

        $this->assertSame(7, $this->propiedad($modelo, 'id'));
        $this->assertSame('12345678', $this->propiedad($modelo, 'cedula'));
        $this->assertSame('04141234567', $this->propiedad($modelo, 'telefono'));
        $this->assertSame(3, $this->propiedad($modelo, 'rol'));
        $this->assertSame('user_12345678.png', $this->propiedad($modelo, 'foto'));
        $this->assertSame(1, $this->propiedad($modelo, 'bloqueo'));
    }

    // Caso 7 (E4): La contraseña se cifra con BCRYPT y se marca para actualizar
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

    // Caso 8 (E5): Sin contraseña no se marca para actualizar
    public function testSinContraseña(): void
    {
        $modelo = new ModeloUsuarios();
        $this->procesarSinEjecutar($modelo, ['contraseña' => '']);

        $this->assertNull($this->propiedad($modelo, 'contraseña'));
        $this->assertFalse($this->propiedad($modelo, 'actualizar_contraseña'));
    }

    // Caso 9 (E3): Valores por defecto cuando no se envían campos
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
