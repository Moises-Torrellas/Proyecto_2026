<?php

namespace tests\Unit\Roles;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRoles;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/RolesMockTrait.php';

/**
 * RF-ROL-04: El sistema debe permitir modificar el nombre y la descripción de un rol
 * existente. El nombre no puede pertenecer a otro rol; conservar el propio nombre es válido.
 *
 * Escenarios:
 *  E1. Modificación válida con nombre y descripción.
 *  E2. Modificación solo del nombre.
 *  E3. El rol conserva su propio nombre.
 *  E4. Rol inexistente.
 *  E5. Nombre ya usado por otro rol.
 *  E6. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitRoles')]
#[Group('modificarRoles')]
class ModificarRolesTest extends TestCase
{
    use RolesMockTrait;

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'accion'      => 'modificar',
            'id'          => '5',
            'nombre'      => 'preparador fisico',
            'descripcion' => 'planifica la preparación física',
        ], $sobrescribir);
    }

    private function ejecutar(array $conteos, array $respuestas = [], array $datos = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloRoles())->procesarDatos($this->obtenerDatosValidos($datos));
    }

    // Caso 1 (E1): Modificación exitosa de nombre y descripción
    public function testModificacionExitosa(): void
    {
        $resultado = $this->ejecutar(['roles.id_rol.1' => 1]);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $update = $this->sentenciaEjecutada('UPDATE `roles`');
        $this->assertStringContainsString('`descripcion` = :descripcion', $update['sql']);
        $this->assertSame([
            ':id'          => 5,
            ':nombre'      => 'Preparador Fisico',
            ':descripcion' => 'Planifica La Preparación Física',
        ], $update['params']);
    }

    // Caso 2 (E2): Modificación sin descripción no la sobrescribe
    public function testModificacionSinDescripcion(): void
    {
        $resultado = $this->ejecutar(['roles.id_rol.1' => 1], [], ['descripcion' => '']);

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `roles`');
        $this->assertStringNotContainsString('descripcion', $update['sql']);
        $this->assertSame([':id' => 5, ':nombre' => 'Preparador Fisico'], $update['params']);
    }

    // Caso 3 (E3): El rol puede guardar su mismo nombre sin considerarse duplicado
    public function testConservaSuPropioNombre(): void
    {
        $resultado = $this->ejecutar([
            'roles.id_rol.1'      => 1,
            'propia.nombre_rol'   => 1,
            'roles.nombre_rol.1'  => 1,
        ]);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertNotNull($this->sentenciaEjecutada('UPDATE `roles`'));
    }

    // Caso 4 (E4): Rol inexistente
    public function testRolNoExiste(): void
    {
        $resultado = $this->ejecutar(['roles.id_rol.1' => 0]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `roles`'));
    }

    // Caso 5 (E5): Nombre ya usado por otro rol
    public function testNombreDuplicado(): void
    {
        $resultado = $this->ejecutar([
            'roles.id_rol.1'     => 1,
            'propia.nombre_rol'  => 0,
            'roles.nombre_rol.1' => 1,
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `roles`'));
    }

    // Caso 6 (E6): Error de base de datos al modificar
    public function testErrorBaseDatosAlModificar(): void
    {
        $resultado = $this->ejecutar(['roles.id_rol.1' => 1], [
            'UPDATE `roles`' => ['error' => new Exception('Error al actualizar rol')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al actualizar rol', $resultado['codigo']);
    }
}
