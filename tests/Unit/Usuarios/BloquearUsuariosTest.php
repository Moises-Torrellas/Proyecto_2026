<?php

namespace tests\Unit\Usuarios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloUsuarios;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/UsuariosMockTrait.php';

/**
 * RF-USU-06: El sistema debe permitir bloquear y desbloquear el acceso de un usuario.
 * Al desbloquear se reinician los intentos fallidos de inicio de sesión. El Super Usuario
 * y los usuarios de nivel de rol 1 o 2 no pueden ser bloqueados.
 * (bloqueo: 1 = permitido, 2 = bloqueado; se envía el estado actual y se alterna)
 *
 * Escenarios:
 *  E1. Bloquear un usuario activo.
 *  E2. Desbloquear un usuario bloqueado.
 *  E3. Intento de bloquear al Super Usuario.
 *  E4. Usuario inexistente.
 *  E5. Usuario con nivel de rol 1 o 2.
 *  E6. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('bloquearUsuarios')]
class BloquearUsuariosTest extends TestCase
{
    use UsuariosMockTrait;

    private function ejecutar(int $id, $bloqueo, array $conteos, array $respuestas = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloUsuarios())->procesarDatos(['accion' => 'bloquear', 'id' => $id, 'bloqueo' => $bloqueo]);
    }

    private function nivel(int $nivel, array $extra = []): array
    {
        return array_merge($extra, ['SELECT r.nivel_rol' => ['fetchColumn' => $nivel]]);
    }

    // Caso 1 (E1): Bloquear un usuario (estado 1 -> 2) sin reiniciar intentos
    public function testBloquearUsuario(): void
    {
        $resultado = $this->ejecutar(5, 1, ['usuarios.idUsuario.1' => 1], $this->nivel(3));

        $this->assertSame(['accion' => 'exito'], $resultado);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios` SET `bloqueo`');
        $this->assertSame([':estado' => 2, ':id' => 5], $update['params']);
        $this->assertStringNotContainsString('intentos_fallidos', $update['sql']);
    }

    // Caso 2 (E2): Desbloquear un usuario (estado 2 -> 1) reinicia los intentos fallidos
    public function testDesbloquearUsuario(): void
    {
        $resultado = $this->ejecutar(5, 2, ['usuarios.idUsuario.1' => 1], $this->nivel(3));

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios` SET `bloqueo`');
        $this->assertSame([':estado' => 1, ':id' => 5], $update['params']);
        $this->assertStringContainsString('`intentos_fallidos` = 0', $update['sql']);
    }

    // Caso 3 (E3): El Super Usuario no puede ser bloqueado
    public function testNoBloqueaSuperUsuario(): void
    {
        $resultado = $this->ejecutar(1, 1, ['usuarios.idUsuario.1' => 1], $this->nivel(1));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(ASSOCIATES, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    // Caso 4 (E4): Usuario inexistente
    public function testUsuarioNoExiste(): void
    {
        $resultado = $this->ejecutar(50, 1, ['usuarios.idUsuario.1' => 0]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
    }

    // Caso 5 (E5): Usuario de nivel de rol 1 no puede ser bloqueado
    public function testNoBloqueaNivel1(): void
    {
        $resultado = $this->ejecutar(5, 1, ['usuarios.idUsuario.1' => 1], $this->nivel(1));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('ROLE_RULES_5', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    // Caso 6 (E5): Usuario de nivel de rol 2 no puede ser bloqueado
    public function testNoBloqueaNivel2(): void
    {
        $resultado = $this->ejecutar(5, 1, ['usuarios.idUsuario.1' => 1], $this->nivel(2));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('ROLE_RULES_5', $resultado['codigo']);
    }

    // Caso 7 (E6): Error de base de datos al bloquear
    public function testErrorBaseDatosAlBloquear(): void
    {
        $resultado = $this->ejecutar(
            5,
            1,
            ['usuarios.idUsuario.1' => 1],
            $this->nivel(3, ['UPDATE `usuarios`' => ['error' => new Exception('Error al bloquear')]])
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al bloquear', $resultado['codigo']);
    }
}
