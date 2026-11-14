<?php

namespace tests\Unit\Roles;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRoles;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/RolesMockTrait.php';

/**
 * RF-ROL-06: El sistema debe permitir consultar el detalle (nombre y descripción) de un rol.
 *
 * Escenarios:
 *  E1. Buscar un rol existente.
 *  E2. Buscar un rol inexistente.
 *  E3. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitRoles')]
#[Group('buscarRoles')]
class BuscarRolesTest extends TestCase
{
    use RolesMockTrait;

    // Caso 1 (E1): Buscar un rol existente devuelve sus datos
    public function testBuscarExitoso(): void
    {
        $fila = ['nombre_rol' => 'Entrenador', 'id_rol' => 3, 'descripcion' => 'Dirige los entrenamientos'];
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM `roles`' => ['fetchAll' => [$fila]],
        ]));

        $resultado = (new ModeloRoles())->procesarDatos(['accion' => 'buscar', 'id' => '3']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([$fila], $resultado['datos']);
        $this->assertSame([':id' => 3], $this->sentenciaEjecutada('FROM `roles`')['params']);
    }

    // Caso 2 (E2): Buscar un rol inexistente devuelve datos vacíos
    public function testBuscarNoEncontrado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM `roles`' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloRoles())->procesarDatos(['accion' => 'buscar', 'id' => '99']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    // Caso 3 (E3): Error de base de datos al buscar
    public function testErrorBaseDatosAlBuscar(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM `roles`' => ['error' => new Exception('Error de consulta')],
        ]));

        $resultado = (new ModeloRoles())->procesarDatos(['accion' => 'buscar', 'id' => '3']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de consulta', $resultado['mensaje']);
    }
}
