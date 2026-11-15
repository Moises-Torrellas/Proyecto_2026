<?php

namespace tests\Unit\Usuarios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloUsuarios;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/UsuariosMockTrait.php';

/**
 * RF-USU-05: El sistema debe permitir eliminar (desactivar) usuarios. El Super Usuario
 * (id 1) y los usuarios con nivel de rol 1 o 2 no pueden eliminarse. Al eliminar un
 * usuario se eliminan sus excepciones de permisos.
 *
 * Escenarios:
 *  E1. Eliminación de un usuario válido.
 *  E2. Intento de eliminar al Super Usuario.
 *  E3. Usuario inexistente o ya eliminado.
 *  E4. Usuario con nivel de rol 1 o 2.
 *  E5. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('eliminarUsuarios')]
class EliminarUsuariosTest extends TestCase
{
    use UsuariosMockTrait;

    private function ejecutar(int $id, array $conteos, array $respuestas = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloUsuarios())->procesarDatos(['accion' => 'eliminar', 'id' => $id]);
    }

    private function nivel(int $nivel, array $extra = []): array
    {
        return array_merge($extra, ['SELECT r.nivel_rol' => ['fetchColumn' => $nivel]]);
    }

    // Caso 1 (E1): Eliminación lógica exitosa
    public function testEliminarExitoso(): void
    {
        $resultado = $this->ejecutar(5, ['usuarios.idUsuario.1' => 1], $this->nivel(3));

        $this->assertSame(['accion' => 'exito'], $resultado);

        $update = $this->sentenciaEjecutada('UPDATE `usuarios`');
        $this->assertStringContainsString('`estatus` = 0', $update['sql']);
        $this->assertSame(5, $update['params'][':id']);

        $delete = $this->sentenciaEjecutada('DELETE FROM excepciones');
        $this->assertSame(5, $delete['params'][':idUsuario']);
    }

    // Caso 2 (E2): El Super Usuario no se puede eliminar
    public function testNoEliminaSuperUsuario(): void
    {
        $resultado = $this->ejecutar(1, ['usuarios.idUsuario.1' => 1], $this->nivel(1));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(ASSOCIATES, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    // Caso 3 (E3): Usuario inexistente o ya eliminado
    public function testUsuarioNoExiste(): void
    {
        $resultado = $this->ejecutar(50, ['usuarios.idUsuario.1' => 0]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
    }

    // Caso 4 (E4): Usuario con nivel de rol 1 no se puede eliminar
    public function testNoEliminaNivel1(): void
    {
        $resultado = $this->ejecutar(5, ['usuarios.idUsuario.1' => 1], $this->nivel(1));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('ROLE_RULES_4', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `usuarios`'));
    }

    // Caso 5 (E4): Usuario con nivel de rol 2 no se puede eliminar
    public function testNoEliminaNivel2(): void
    {
        $resultado = $this->ejecutar(5, ['usuarios.idUsuario.1' => 1], $this->nivel(2));

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('ROLE_RULES_4', $resultado['codigo']);
    }

    // Caso 6 (E5): Error de base de datos al eliminar
    public function testErrorBaseDatosAlEliminar(): void
    {
        $resultado = $this->ejecutar(
            5,
            ['usuarios.idUsuario.1' => 1],
            $this->nivel(3, ['UPDATE `usuarios`' => ['error' => new Exception('Error al eliminar')]])
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al eliminar', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM excepciones'));
    }
}
