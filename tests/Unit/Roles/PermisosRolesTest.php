<?php

namespace tests\Unit\Roles;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRoles;
use App\modelo\ModeloPermisos;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/RolesMockTrait.php';

/**
 * RF-ROL-07: El sistema debe permitir ver y asignar los permisos de un rol. Al guardar,
 * los permisos anteriores del rol se reemplazan por los seleccionados (valor 1).
 *
 * Escenarios:
 *  E1. Cargar los permisos de un rol.
 *  E2. Falla al cargar los permisos.
 *  E3. Guardar una selección de permisos.
 *  E4. Guardar sin permisos seleccionados.
 *  E5. Falla de la base de datos al guardar.
 */
#[Group('unit')]
#[Group('unitRoles')]
#[Group('permisosRoles')]
class PermisosRolesTest extends TestCase
{
    use RolesMockTrait;

    private function modeloConPermisos(array|Exception $respuesta): ModeloRoles
    {
        $permisoMock = $this->createMock(ModeloPermisos::class);
        if ($respuesta instanceof Exception) {
            $permisoMock->method('permisosRoles')->willThrowException($respuesta);
        } else {
            $permisoMock->method('permisosRoles')->willReturn($respuesta);
        }

        $modelo = new ModeloRoles();
        $modelo->setPermiso($permisoMock);
        return $modelo;
    }

    private function guardar(array $permisos, array $respuestas = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock([], $respuestas));
        return (new ModeloRoles())->procesarDatos([
            'accion'   => 'guardar_permisos',
            'id'       => '5',
            'permisos' => $permisos,
        ]);
    }

    // Caso 1 (E1): Cargar los permisos de un rol
    public function testCargarPermisos(): void
    {
        $filas = [
            ['id_modulo' => 6, 'id_permiso' => 1, 'clave' => 'ingresar_atletas', 'asignado' => 1],
            ['id_modulo' => 6, 'id_permiso' => 2, 'clave' => 'registrar_atleta', 'asignado' => 0],
        ];
        $modelo = $this->modeloConPermisos(['accion' => 'CargarPermisos', 'datos' => $filas]);

        $resultado = $modelo->CargarPermisos(5);

        $this->assertSame('CargarPermisos', $resultado['accion']);
        $this->assertSame($filas, $resultado['datos']);
    }

    // Caso 2 (E2): Excepción al cargar los permisos devuelve error
    public function testCargarPermisosExcepcion(): void
    {
        $modelo = $this->modeloConPermisos(new Exception('Sin conexión'));

        $resultado = $modelo->CargarPermisos(5);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Sin conexión', $resultado['mensaje']);
    }

    // Caso 3 (E2): Si la consulta de permisos responde con error, el error se informa
    public function testCargarPermisosRespuestaError(): void
    {
        $modelo = $this->modeloConPermisos(['accion' => 'error', 'mensaje' => 'Error de consulta']);

        $resultado = $modelo->CargarPermisos(5);

        $this->assertSame(
            'error',
            $resultado['accion'],
            'CargarPermisos() no informa el error devuelto por ModeloPermisos::permisosRoles().'
        );
    }

    // Caso 4 (E3): Guardar reemplaza los permisos del rol por los seleccionados
    public function testGuardarPermisos(): void
    {
        $resultado = $this->guardar(['1' => 1, '4' => '1', '7' => 0]);

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame([':id' => 5], $this->sentenciaEjecutada('DELETE FROM `permisos_rol`')['params']);

        $inserts = $this->sentenciasEjecutadas('INSERT INTO `permisos_rol`');
        $this->assertCount(2, $inserts);
        $this->assertSame([':id_rol' => 5, ':id_permiso' => 1], $inserts[0]['params']);
        $this->assertSame([':id_rol' => 5, ':id_permiso' => 4], $inserts[1]['params']);
    }

    // Caso 5 (E4): Guardar sin permisos deja el rol sin permisos
    public function testGuardarSinPermisos(): void
    {
        $resultado = $this->guardar([]);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertNotNull($this->sentenciaEjecutada('DELETE FROM `permisos_rol`'));
        $this->assertCount(0, $this->sentenciasEjecutadas('INSERT INTO `permisos_rol`'));
    }

    // Caso 6 (E5): Error de base de datos al guardar permisos
    public function testErrorBaseDatosAlGuardarPermisos(): void
    {
        $resultado = $this->guardar([1 => 1], [
            'INSERT INTO `permisos_rol`' => ['error' => new Exception('Error al guardar permisos')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al guardar permisos', $resultado['codigo']);
    }
}
