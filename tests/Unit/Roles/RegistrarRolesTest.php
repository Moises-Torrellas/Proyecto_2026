<?php

namespace tests\Unit\Roles;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRoles;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/RolesMockTrait.php';

/**
 * RF-ROL-03: El sistema debe permitir registrar roles con nombre (obligatorio y único)
 * y descripción (opcional).
 *
 * Escenarios:
 *  E1. Registro con nombre y descripción.
 *  E2. Registro solo con nombre.
 *  E3. Registro con un nombre ya existente.
 *  E4. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitRoles')]
#[Group('registrarRoles')]
class RegistrarRolesTest extends TestCase
{
    use RolesMockTrait;

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'accion'      => 'incluir',
            'nombre'      => 'entrenador auxiliar',
            'descripcion' => 'apoya al entrenador principal',
        ], $sobrescribir);
    }

    // Caso 1 (E1): Registro exitoso con nombre y descripción
    public function testRegistroExitoso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock());

        $resultado = (new ModeloRoles())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame(['accion' => 'exito'], $resultado);

        $insert = $this->sentenciaEjecutada('INSERT INTO `roles`');
        $this->assertStringContainsString('`nombre_rol`, `descripcion`', $insert['sql']);
        $this->assertSame([
            ':nombre'      => 'Entrenador Auxiliar',
            ':descripcion' => 'Apoya Al Entrenador Principal',
        ], $insert['params']);
    }

    // Caso 2 (E2): Registro exitoso sin descripción (usa el valor por defecto de la BD)
    public function testRegistroSinDescripcion(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock());

        $resultado = (new ModeloRoles())->procesarDatos($this->obtenerDatosValidos(['descripcion' => '']));

        $this->assertSame('exito', $resultado['accion']);

        $insert = $this->sentenciaEjecutada('INSERT INTO `roles`');
        $this->assertStringNotContainsString('descripcion', $insert['sql']);
        $this->assertSame([':nombre' => 'Entrenador Auxiliar'], $insert['params']);
    }

    // Caso 3 (E3): Nombre de rol duplicado
    public function testNombreDuplicado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock(['roles.nombre_rol.1' => 1]));

        $resultado = (new ModeloRoles())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('INSERT INTO `roles`'));
    }

    // Caso 4 (E4): Error de base de datos al registrar
    public function testErrorBaseDatosAlRegistrar(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'INSERT INTO `roles`' => ['error' => new Exception('Error al insertar rol')],
        ]));

        $resultado = (new ModeloRoles())->procesarDatos($this->obtenerDatosValidos());

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al insertar rol', $resultado['codigo']);
    }
}
