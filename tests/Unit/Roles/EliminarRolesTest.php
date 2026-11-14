<?php

namespace tests\Unit\Roles;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRoles;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/RolesMockTrait.php';

/**
 * RF-ROL-05: El sistema debe permitir eliminar roles. Los roles protegidos (id 1 y 2)
 * no se pueden eliminar, tampoco un rol que tenga usuarios activos asociados, y no se
 * puede eliminar un rol que no existe. Todas las verificaciones se hacen sobre la base
 * de datos de seguridad, donde están las tablas de roles y usuarios.
 *
 * Escenarios:
 *  E1. Eliminación de un rol existente sin usuarios.
 *  E2. Intento de eliminar un rol protegido.
 *  E3. Rol con usuarios activos asociados.
 *  E4. Rol inexistente.
 *  E5. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitRoles')]
#[Group('eliminarRoles')]
class EliminarRolesTest extends TestCase
{
    use RolesMockTrait;

    private function ejecutar(int $id, array $conteos, array $respuestas = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloRoles())->procesarDatos(['accion' => 'eliminar', 'id' => (string) $id]);
    }

    // Caso 1 (E1): Eliminación exitosa de un rol existente sin usuarios asociados
    public function testEliminarExitoso(): void
    {
        $resultado = $this->ejecutar(5, [
            'usuarios.id_rol.1'  => 0,
            'roles.id_rol.null'  => 1,
        ]);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame([':id' => 5], $this->sentenciaEjecutada('DELETE FROM roles')['params']);
    }

    // Caso 2 (E2): El rol protegido id 1 (Super Usuario) no se puede eliminar
    public function testNoEliminaRolProtegido1(): void
    {
        $resultado = $this->ejecutar(1, ['roles.id_rol.null' => 1]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM roles'));
    }

    // Caso 3 (E2): El rol protegido id 2 no se puede eliminar
    public function testNoEliminaRolProtegido2(): void
    {
        $resultado = $this->ejecutar(2, ['roles.id_rol.null' => 1]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM roles'));
    }

    // Caso 4 (E3): Rol con usuarios activos asociados no se puede eliminar
    public function testRolConUsuariosAsociados(): void
    {
        $resultado = $this->ejecutar(5, [
            'usuarios.id_rol.1' => 3,
            'roles.id_rol.null' => 1,
        ]);

        $this->assertSame('error', $resultado['accion'], 'Se eliminó un rol que tiene usuarios activos asociados.');
        $this->assertSame(ASSOCIATES, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM roles'));
    }

    // Caso 5 (E4): Rol inexistente
    public function testRolNoExiste(): void
    {
        $resultado = $this->ejecutar(50, [
            'usuarios.id_rol.1' => 0,
            'roles.id_rol.null' => 0,
        ]);

        $this->assertSame('error', $resultado['accion'], 'Se intentó eliminar un rol que no existe.');
        $this->assertSame(INVALID_ID . '0', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('DELETE FROM roles'));
    }

    // Caso 6 (E1): Las verificaciones se hacen solo sobre la base de datos de seguridad
    public function testNoConsultaBaseDatosGeneral(): void
    {
        $this->ejecutar(5, ['usuarios.id_rol.1' => 0, 'roles.id_rol.null' => 1]);

        $this->assertSame(
            [],
            $this->sentenciasBdGeneral,
            'Eliminar() consultó la base de datos general en lugar de la de seguridad.'
        );
    }

    // Caso 7 (E5): Error de base de datos al eliminar
    public function testErrorBaseDatosAlEliminar(): void
    {
        $resultado = $this->ejecutar(
            5,
            ['usuarios.id_rol.1' => 0, 'roles.id_rol.null' => 1],
            ['DELETE FROM roles' => ['error' => new Exception('Error al eliminar rol')]]
        );

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al eliminar rol', $resultado['codigo']);
    }
}
