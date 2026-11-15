<?php

namespace tests\Unit\Permisos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPermisos;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/PermisosMockTrait.php';

/**
 * RF-PER-06: El sistema debe mostrar la matriz de permisos de un rol y de un usuario,
 * indicando cuáles están asignados. Para un usuario, el permiso asignado combina los
 * permisos de su rol con sus excepciones. Los módulos internos del sistema
 * (1, 2, 3, 4, 5 y 8) no se muestran en la matriz.
 *
 * Escenarios:
 *  E1. Matriz de permisos de un rol.
 *  E2. Matriz de permisos de un usuario.
 *  E3. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitPermisos')]
#[Group('permisosAsignados')]
class PermisosAsignadosTest extends TestCase
{
    use PermisosMockTrait;

    // Caso 1 (E1): Matriz de permisos de un rol
    public function testPermisosRoles(): void
    {
        $filas = [['id_rol' => 3, 'id_modulo' => 6, 'id_permiso' => 1, 'asignado' => 1]];
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos m' => ['fetchAll' => $filas],
        ]));

        $resultado = (new ModeloPermisos())->permisosRoles(3);

        $this->assertSame('CargarPermisos', $resultado['accion']);
        $this->assertSame($filas, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('FROM modulos m');
        $this->assertStringContainsString('NOT IN (4, 5, 8, 1, 2, 3)', $sentencia['sql']);
        $this->assertStringContainsString('LEFT JOIN permisos_rol', $sentencia['sql']);
        $this->assertSame([':id1' => 3, ':id2' => 3, ':id3' => 3], $sentencia['params']);
    }

    // Caso 2 (E3): Error al cargar la matriz de un rol
    public function testPermisosRolesError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos m' => ['error' => new Exception('Error de consulta')],
        ]));

        $resultado = (new ModeloPermisos())->permisosRoles(3);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de consulta', $resultado['mensaje']);
    }

    // Caso 3 (E2): Matriz de permisos de un usuario con rol y excepciones
    public function testPermisosUsuarios(): void
    {
        $filas = [
            ['idUsuario' => 5, 'id_permiso' => 1, 'asignado' => 0, 'excepcion' => 0],
            ['idUsuario' => 5, 'id_permiso' => 2, 'asignado' => 1, 'excepcion' => 2],
        ];
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos m' => ['fetchAll' => $filas],
        ]));

        $resultado = (new ModeloPermisos())->permisosUsuarios(5);

        $this->assertSame('CargarPermisosUsuario', $resultado['accion']);
        $this->assertSame($filas, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('FROM modulos m');
        $this->assertStringContainsString('NOT IN (4, 5, 8, 1, 2, 3)', $sentencia['sql']);
        $this->assertStringContainsString('LEFT JOIN permisos_rol', $sentencia['sql']);
        $this->assertStringContainsString('LEFT JOIN excepciones', $sentencia['sql']);
        $this->assertSame([':id1' => 5, ':id2' => 5, ':id3' => 5, ':id4' => 5], $sentencia['params']);
    }

    // Caso 4 (E3): Error al cargar la matriz de un usuario
    public function testPermisosUsuariosError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos m' => ['error' => new Exception('Error de consulta')],
        ]));

        $resultado = (new ModeloPermisos())->permisosUsuarios(5);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de consulta', $resultado['mensaje']);
    }
}
