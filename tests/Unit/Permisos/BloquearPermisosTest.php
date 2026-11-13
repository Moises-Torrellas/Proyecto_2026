<?php

namespace tests\Unit\Permisos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPermisos;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/PermisosMockTrait.php';

/**
 * RF-PER-05: El sistema debe permitir bloquear y desbloquear permisos. Se envía el estado
 * actual (1 = activo, 2 = bloqueado) y el sistema lo alterna. Si el permiso no existe se
 * informa el error, en la clave "codigo" que lee el controlador.
 *
 * Escenarios:
 *  E1. Bloquear un permiso activo.
 *  E2. Desbloquear un permiso bloqueado.
 *  E3. Permiso inexistente.
 *  E4. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitPermisos')]
#[Group('bloquearPermisos')]
class BloquearPermisosTest extends TestCase
{
    use PermisosMockTrait;

    private function ejecutar(string $bloqueo, array $respuestas = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock([], $respuestas));
        return (new ModeloPermisos())->ProcesarDatos(['accion' => 'bloquear', 'id' => '9', 'bloqueo' => $bloqueo]);
    }

    // Caso 1 (E1): Bloquear un permiso activo (1 -> 2)
    public function testBloquear(): void
    {
        $resultado = $this->ejecutar('1');

        $this->assertSame(['accion' => 'exito'], $resultado);
        $this->assertSame(
            [':estado' => 2, ':id' => '9'],
            $this->sentenciaEjecutada('UPDATE `permisos` SET `estatus`')['params']
        );
    }

    // Caso 2 (E2): Desbloquear un permiso bloqueado (2 -> 1)
    public function testDesbloquear(): void
    {
        $resultado = $this->ejecutar('2');

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame(
            [':estado' => 1, ':id' => '9'],
            $this->sentenciaEjecutada('UPDATE `permisos` SET `estatus`')['params']
        );
    }

    // Caso 3 (E3): Permiso inexistente (ninguna fila actualizada)
    public function testPermisoNoExiste(): void
    {
        $resultado = $this->ejecutar('1', ['UPDATE `permisos`' => ['rowCount' => 0]]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(
            'No se encontró el permiso o el estatus ya era el solicitado.',
            $this->textoError($resultado)
        );
    }

    // Caso 4 (E3): El error se devuelve en la clave "codigo" que lee el controlador
    public function testErrorEnClaveCodigo(): void
    {
        $resultado = $this->ejecutar('1', ['UPDATE `permisos`' => ['rowCount' => 0]]);

        $this->assertArrayHasKey(
            'codigo',
            $resultado,
            'Bloqueo() devuelve el error en "mensaje", pero el controlador lee "codigo".'
        );
    }

    // Caso 5 (E4): Error de base de datos al bloquear
    public function testErrorBaseDatosAlBloquear(): void
    {
        $resultado = $this->ejecutar('1', [
            'UPDATE `permisos`' => ['error' => new Exception('Error al bloquear permiso')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al bloquear permiso', $this->textoError($resultado));
    }
}
