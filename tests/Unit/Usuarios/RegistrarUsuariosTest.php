<?php

namespace tests\Unit\Usuarios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloUsuarios;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/UsuariosMockTrait.php';

/**
 * RF-USU-03: El sistema debe permitir registrar usuarios con cédula, nombre, apellido,
 * teléfono, correo, contraseña, rol y foto. La cédula, el correo y el teléfono deben ser
 * únicos entre los usuarios activos, el rol debe existir y la contraseña es obligatoria.
 * Si la cédula pertenece a un usuario eliminado, el usuario se reactiva con los nuevos datos.
 *
 * Escenarios:
 *  E1. Registro de un usuario nuevo con datos válidos.
 *  E2. Registro de una cédula que pertenecía a un usuario eliminado (reactivación).
 *  E3. Registro sin contraseña.
 *  E4. Registro con un rol inexistente.
 *  E5. Registro con cédula, correo o teléfono ya usados por un usuario activo.
 *  E6. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('registrarUsuarios')]
class RegistrarUsuariosTest extends TestCase
{
    use UsuariosMockTrait;

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        $base = [
            'accion'     => 'incluir',
            'cedula'     => '27123456',
            'nombre'     => 'carlos',
            'apellido'   => 'mendoza',
            'telefono'   => '04241234567',
            'correo'     => 'Carlos.Mendoza@Correo.com',
            'contraseña' => 'Clave.Segura123',
            'roles_id'   => 3,
            'foto'       => 'user_27123456.png',
        ];

        return array_merge($base, $sobrescribir);
    }

    private function conteosValidos(array $sobrescribir = []): array
    {
        return array_merge(['roles.id_rol.null' => 1], $sobrescribir);
    }

    // Caso 1 (E1): Registro exitoso de un usuario nuevo mediante el procedimiento almacenado
    public function testRegistroExitoso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos()));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame(['accion' => 'exito'], $resultado);

        $llamada = $this->sentenciaEjecutada('CALL pa_incluir_usuario');
        $this->assertNotNull($llamada);
        $this->assertSame('27123456', $llamada['params'][':cedula']);
        $this->assertSame('Carlos', $llamada['params'][':nombre']);
        $this->assertSame('Mendoza', $llamada['params'][':apellido']);
        $this->assertSame('carlos.mendoza@correo.com', $llamada['params'][':correo']);
        $this->assertSame(3, $llamada['params'][':rol']);
        $this->assertTrue(password_verify('Clave.Segura123', $llamada['params'][':contra']));
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    // Caso 2 (E1): Se registra en la BD el usuario de sesión que realiza la operación (auditoría)
    public function testRegistraUsuarioDeSesionParaAuditoria(): void
    {
        $_SESSION['idUsuario'] = 4;
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos()));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('exito', $resultado['accion']);
        $this->assertNotNull($this->sentenciaEjecutada('SET @usuario_actual = 4'));
    }

    // Caso 3 (E2): Reactivación de un usuario eliminado con la misma cédula
    public function testReactivacionUsuarioEliminado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock(
            $this->conteosValidos(['usuarios.cedulaUsuario.0' => 1]),
            ['SELECT idUsuario FROM usuarios' => ['fetchColumn' => 9]]
        ));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertNotNull($update);
        $this->assertStringContainsString('`estatus` = 1', $update['sql']);
        $this->assertSame('27123456', $update['params'][':cedula']);
        $this->assertNull($this->sentenciaEjecutada('CALL pa_incluir_usuario'));
    }

    // Caso 4 (E3): Registro sin contraseña es rechazado
    public function testRegistroSinContraseña(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos()));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos(['contraseña' => '']));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(VALIDATION, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('CALL pa_incluir_usuario'));
    }

    // Caso 5 (E4): Registro con un rol inexistente
    public function testRolNoExiste(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock(['roles.id_rol.null' => 0]));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos(['roles_id' => 99]));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
    }

    // Caso 6 (E5): Cédula duplicada en un usuario activo
    public function testCedulaDuplicada(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(['usuarios.cedulaUsuario.1' => 1])));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_CEDULA, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('CALL pa_incluir_usuario'));
    }

    // Caso 7 (E5): Correo duplicado en un usuario activo
    public function testCorreoDuplicado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(['usuarios.correo.1' => 1])));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_EMAIL, $resultado['codigo']);
    }

    // Caso 8 (E5): Teléfono duplicado en un usuario activo
    public function testTelefonoDuplicado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(['usuarios.telefonoUsuario.1' => 1])));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_PHONE, $resultado['codigo']);
    }

    // Caso 9 (E6): Error de base de datos al ejecutar el registro
    public function testErrorBaseDatosAlRegistrar(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock($this->conteosValidos(), [
            'CALL pa_incluir_usuario' => ['error' => new Exception('Error en el procedimiento')],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error en el procedimiento', $resultado['codigo']);
    }
}
