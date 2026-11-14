<?php

namespace tests\Unit\Usuarios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloUsuarios;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/UsuariosMockTrait.php';

/**
 * RF-USU-07: El sistema debe permitir consultar el detalle de un usuario activo
 * (incluyendo el nivel de su rol) y registrar la fecha de su último ingreso.
 *
 * Escenarios:
 *  E1. Buscar un usuario activo existente.
 *  E2. Buscar un usuario inexistente o eliminado.
 *  E3. Falla de la base de datos al buscar.
 *  E4. Registrar el último ingreso del usuario.
 */
#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('buscarUsuarios')]
class BuscarUsuariosTest extends TestCase
{
    use UsuariosMockTrait;

    // Caso 1 (E1): Buscar usuario activo devuelve sus datos y el nivel de rol
    public function testBuscarExitoso(): void
    {
        $fila = [
            'idUsuario' => 5, 'cedulaUsuario' => '27123456', 'nombreUsuario' => 'Carlos',
            'apellidoUsuario' => 'Mendoza', 'correo' => 'carlos@correo.com', 'id_rol' => 3, 'nivel_rol' => 3,
        ];
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM `usuarios` u' => ['fetchAll' => [$fila]],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos(['accion' => 'buscar', 'id' => 5]);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame($fila, $resultado['datos'][0]);

        $sentencia = $this->sentenciaEjecutada('FROM `usuarios` u');
        $this->assertStringContainsString('u.estatus=1', $sentencia['sql']);
        $this->assertSame(5, $sentencia['params'][':id']);
    }

    // Caso 2 (E2): Usuario inexistente o eliminado devuelve datos vacíos
    public function testBuscarNoEncontrado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM `usuarios` u' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos(['accion' => 'buscar', 'id' => 99]);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    // Caso 3 (E3): Error de base de datos al buscar
    public function testErrorBaseDatosAlBuscar(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM `usuarios` u' => ['error' => new Exception('Error de consulta')],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos(['accion' => 'buscar', 'id' => 5]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de consulta', $resultado['mensaje']);
    }

    // Caso 4 (E4): Registrar el último ingreso actualiza la fecha del usuario
    public function testRegistrarUltimoIngreso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock());

        $resultado = (new ModeloUsuarios())->registrarUltimoIngreso(5);

        $this->assertSame('exito', $resultado['accion']);
        $sentencia = $this->sentenciaEjecutada('`ultimo_ingreso` = NOW()');
        $this->assertSame(5, $sentencia['params'][':id']);
    }

    // Caso 5 (E4): Error al registrar el último ingreso
    public function testErrorRegistrarUltimoIngreso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'ultimo_ingreso' => ['error' => new Exception('Sin conexión')],
        ]));

        $resultado = (new ModeloUsuarios())->registrarUltimoIngreso(5);

        $this->assertSame('error', $resultado['accion']);
        $this->assertStringContainsString('Sin conexión', $resultado['mensaje']);
    }
}
