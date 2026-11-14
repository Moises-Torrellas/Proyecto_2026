<?php

namespace tests\Unit\Usuarios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloUsuarios;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/UsuariosMockTrait.php';

/**
 * RF-USU-08: El sistema debe permitir ver y personalizar los permisos de un usuario.
 * Los permisos parten de los de su rol; las diferencias se guardan como excepciones:
 *  - tipo 0 (denegar): el rol tiene el permiso pero se desmarcó para el usuario.
 *  - tipo 1 (permitir): el rol no tiene el permiso pero se marcó para el usuario.
 *
 * Escenarios:
 *  E1. Cargar los permisos de un usuario.
 *  E2. Guardar permisos quitando y agregando permisos respecto al rol.
 *  E3. Guardar permisos idénticos a los del rol (sin excepciones).
 *  E4. Falla de la base de datos al guardar.
 */
#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('permisosUsuarios')]
class PermisosUsuariosTest extends TestCase
{
    use UsuariosMockTrait;

    /** Rol con permisos 1 y 2; el sistema tiene permisos 1, 2 y 3. */
    private function respuestasPermisos(array $extra = []): array
    {
        return array_merge($extra, [
            'FROM permisos_rol' => ['fetchAll' => [1, 2]],
            'SELECT id_permiso FROM permisos' => ['fetchAll' => [1, 2, 3]],
        ]);
    }

    private function guardar(array $permisos, array $extra = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock([], $this->respuestasPermisos($extra)));
        return (new ModeloUsuarios())->procesarDatos([
            'accion'   => 'guardar_permisos_usuario',
            'id'       => 5,
            'permisos' => $permisos,
        ]);
    }

    // Caso 1 (E1): Cargar los permisos de un usuario
    public function testCargarPermisosUsuario(): void
    {
        $filas = [
            ['id_modulo' => 6, 'id_permiso' => 1, 'clave' => 'ingresar_atletas', 'asignado' => 1, 'excepcion' => 2],
            ['id_modulo' => 6, 'id_permiso' => 2, 'clave' => 'registrar_atleta', 'asignado' => 0, 'excepcion' => 0],
        ];
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos m' => ['fetchAll' => $filas],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos(['accion' => 'CargarPermisosUsuario', 'id' => 5]);

        $this->assertSame('CargarPermisosUsuario', $resultado['accion']);
        $this->assertSame($filas, $resultado['datos']);
        $this->assertSame(5, $this->sentenciaEjecutada('FROM modulos m')['params'][':id1']);
    }

    // Caso 2 (E2): Quitar un permiso del rol y agregar uno fuera del rol genera las excepciones
    public function testGuardarPermisosConExcepciones(): void
    {
        // Se desmarca el permiso 1 (del rol), se mantiene el 2 y se agrega el 3
        $resultado = $this->guardar([2 => 1, 3 => 1]);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $this->assertSame([':id' => 5], $this->sentenciaEjecutada('DELETE FROM `excepciones`')['params']);

        $inserts = $this->sentenciasEjecutadas('INSERT INTO `excepciones`');
        $this->assertCount(2, $inserts);
        $this->assertSame([':id_usuario' => 5, ':id_permiso' => 1, ':tipo' => 0], $inserts[0]['params']);
        $this->assertSame([':id_usuario' => 5, ':id_permiso' => 3, ':tipo' => 1], $inserts[1]['params']);
    }

    // Caso 3 (E3): Permisos iguales a los del rol no generan excepciones
    public function testGuardarPermisosIgualesAlRol(): void
    {
        $resultado = $this->guardar([1 => 1, 2 => 1]);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertNotNull($this->sentenciaEjecutada('DELETE FROM `excepciones`'));
        $this->assertCount(0, $this->sentenciasEjecutadas('INSERT INTO `excepciones`'));
    }

    // Caso 4 (E2): Sin permisos seleccionados se deniegan todos los del rol
    public function testGuardarSinPermisosDeniegaLosDelRol(): void
    {
        $resultado = $this->guardar([]);

        $this->assertSame('exito', $resultado['accion']);

        $inserts = $this->sentenciasEjecutadas('INSERT INTO `excepciones`');
        $this->assertCount(2, $inserts);
        foreach ($inserts as $insert) {
            $this->assertSame(0, $insert['params'][':tipo']);
        }
    }

    // Caso 5 (E4): Error de base de datos al guardar permisos
    public function testErrorBaseDatosAlGuardarPermisos(): void
    {
        $resultado = $this->guardar([3 => 1], [
            'INSERT INTO `excepciones`' => ['error' => new Exception('Error al guardar excepciones')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al guardar excepciones', $resultado['codigo']);
    }
}
