<?php

namespace tests\Unit\Usuarios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloUsuarios;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/UsuariosMockTrait.php';

/**
 * RF-USU-04: El sistema debe permitir modificar los datos de un usuario. La cédula, el
 * correo y el teléfono no pueden pertenecer a otro usuario (activo o eliminado), el rol
 * debe existir y la contraseña solo se cambia si se envía una nueva. A los usuarios de
 * nivel de rol 1 y 2 no se les puede cambiar el rol ni la contraseña desde este módulo.
 * Al cambiar el rol se eliminan las excepciones de permisos del usuario.
 *
 * Escenarios:
 *  E1. Modificación válida sin cambiar contraseña ni rol.
 *  E2. Modificación con nueva contraseña.
 *  E3. Cambio de rol de un usuario de nivel 3 o superior.
 *  E4. Intento de cambiar rol/contraseña a un usuario de nivel 1 o 2.
 *  E5. Cédula, correo o teléfono usados por un usuario eliminado.
 *  E6. Cédula, correo o teléfono usados por otro usuario activo.
 *  E7. Rol inexistente.
 *  E8. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('modificarUsuarios')]
class ModificarUsuariosTest extends TestCase
{
    use UsuariosMockTrait;

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $base = [
            'accion'   => 'modificar',
            'id'       => 5,
            'cedula'   => '27123456',
            'nombre'   => 'carlos',
            'apellido' => 'mendoza',
            'telefono' => '04241234567',
            'correo'   => 'carlos@correo.com',
            'roles_id' => 3,
            'foto'     => 'user_27123456.png',
        ];

        return array_merge($base, $sobrescribir);
    }

    /** Por defecto: el rol existe y cédula/correo/teléfono son los propios del usuario. */
    private function conteosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'roles.id_rol.null'       => 1,
            'propia.cedulaUsuario'    => 1,
            'propia.correo'           => 1,
            'propia.telefonoUsuario'  => 1,
        ], $sobrescribir);
    }

    private function respuestasRolActual(int $idRol = 3, int $nivel = 3, array $extra = []): array
    {
        return array_merge($extra, [
            'SELECT u.id_rol, r.nivel_rol' => ['fetch' => ['id_rol' => $idRol, 'nivel_rol' => $nivel]],
        ]);
    }

    private function ejecutar(array $conteos, array $respuestas, array $datos = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos($datos));
    }

    // Caso 1 (E1): Modificación exitosa sin contraseña y sin cambio de rol
    public function testModificacionExitosa(): void
    {
        $resultado = $this->ejecutar($this->conteosValidos(), $this->respuestasRolActual());

        $this->assertSame(['accion' => 'exito'], $resultado);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertNotNull($update);
        $this->assertStringNotContainsString('pass_hash', $update['sql']);
        $this->assertSame(5, $update['params'][':id']);
        $this->assertSame('Carlos', $update['params'][':nombre']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM excepciones'));
    }

    // Caso 2 (E2): Modificación con nueva contraseña la actualiza cifrada
    public function testModificacionConContraseña(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(),
            $this->respuestasRolActual(),
            ['contraseña' => 'Nueva.Clave456']
        );

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertStringContainsString('`pass_hash` = :contra', $update['sql']);
        $this->assertTrue(password_verify('Nueva.Clave456', $update['params'][':contra']));
    }

    // Caso 3 (E3): Cambio de rol elimina las excepciones de permisos del usuario
    public function testCambioRolEliminaExcepciones(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(),
            $this->respuestasRolActual(3, 3),
            ['roles_id' => 4]
        );

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame(4, $this->sentenciaEjecutada('UPDATE `usuarios`')['params'][':rol']);

        $delete = $this->sentenciaEjecutada('DELETE FROM excepciones');
        $this->assertNotNull($delete);
        $this->assertSame(5, $delete['params'][':idUsuario']);
    }

    // Caso 4 (E4): Usuario de nivel 1 conserva su rol y su contraseña
    public function testNivel1NoCambiaRolNiContraseña(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(),
            $this->respuestasRolActual(1, 1),
            ['roles_id' => 4, 'contraseña' => 'Nueva.Clave456']
        );

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertSame(1, $update['params'][':rol']);
        $this->assertStringNotContainsString('pass_hash', $update['sql']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM excepciones'));
    }

    // Caso 5 (E4): Usuario de nivel 2 conserva su rol y su contraseña
    public function testNivel2NoCambiaRolNiContraseña(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(),
            $this->respuestasRolActual(2, 2),
            ['roles_id' => 4, 'contraseña' => 'Nueva.Clave456']
        );

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertSame(2, $update['params'][':rol']);
        $this->assertStringNotContainsString('pass_hash', $update['sql']);
    }

    // Caso 6 (E5): Cédula perteneciente a un usuario eliminado no se puede reutilizar
    public function testCedulaDeUsuarioEliminado(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['usuarios.cedulaUsuario.0' => 1]),
            $this->respuestasRolActual()
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_CEDULA . '0', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    // Caso 7 (E5): Correo perteneciente a un usuario eliminado no se puede reutilizar
    public function testCorreoDeUsuarioEliminado(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['usuarios.correo.0' => 1]),
            $this->respuestasRolActual()
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL . '0', $resultado['codigo']);
    }

    // Caso 8 (E5): Teléfono perteneciente a un usuario eliminado no se puede reutilizar
    public function testTelefonoDeUsuarioEliminado(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['usuarios.telefonoUsuario.0' => 1]),
            $this->respuestasRolActual()
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE . '0', $resultado['codigo']);
    }

    // Caso 9 (E6): Cédula usada por otro usuario activo
    public function testCedulaDuplicada(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['propia.cedulaUsuario' => 0, 'usuarios.cedulaUsuario.1' => 1]),
            $this->respuestasRolActual()
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_CEDULA, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    // Caso 10 (E6): Correo usado por otro usuario activo
    public function testCorreoDuplicado(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['propia.correo' => 0, 'usuarios.correo.1' => 1]),
            $this->respuestasRolActual()
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL, $resultado['codigo']);
    }

    // Caso 11 (E6): Teléfono usado por otro usuario activo
    public function testTelefonoDuplicado(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['propia.telefonoUsuario' => 0, 'usuarios.telefonoUsuario.1' => 1]),
            $this->respuestasRolActual()
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE, $resultado['codigo']);
    }

    // Caso 12 (E6): Cambiar a una cédula nueva que nadie usa es válido
    public function testCambioCedulaLibre(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['propia.cedulaUsuario' => 0]),
            $this->respuestasRolActual(),
            ['cedula' => '30111222']
        );

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame('30111222', $this->sentenciaEjecutada('UPDATE `usuarios`')['params'][':cedula']);
    }

    // Caso 13 (E7): Rol inexistente
    public function testRolNoExiste(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(['roles.id_rol.null' => 0]),
            $this->respuestasRolActual(),
            ['roles_id' => 99]
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
    }

    // Caso 14 (E8): Error de base de datos al actualizar
    public function testErrorBaseDatosAlModificar(): void
    {
        $resultado = $this->ejecutar(
            $this->conteosValidos(),
            $this->respuestasRolActual(3, 3, ['UPDATE `usuarios`' => ['error' => new Exception('Error al actualizar')]])
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al actualizar', $resultado['codigo']);
    }
}
