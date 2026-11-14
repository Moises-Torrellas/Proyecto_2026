<?php

namespace tests\Unit\Permisos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPermisos;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/PermisosMockTrait.php';

/**
 * RF-PER-04: El sistema debe permitir modificar el nombre y la descripción de un permiso
 * existente (la clave técnica y el módulo no se modifican). El nombre no puede pertenecer
 * a otro permiso. Si no se envía descripción no debe enviarse NULL a la base de datos,
 * porque la columna no lo admite. Los errores deben devolverse en la clave "codigo",
 * que es la que lee el controlador.
 *
 * Escenarios:
 *  E1. Modificación con nombre y descripción.
 *  E2. Modificación sin descripción.
 *  E3. El permiso conserva su propio nombre.
 *  E4. Permiso inexistente.
 *  E5. Nombre usado por otro permiso.
 *  E6. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitPermisos')]
#[Group('modificarPermisos')]
class ModificarPermisosTest extends TestCase
{
    use PermisosMockTrait;

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'accion'      => 'modificar',
            'id'          => '9',
            'nombre'      => 'reinscribir atleta',
            'descripcion' => 'permite reinscribir atletas',
        ], $sobrescribir);
    }

    private function ejecutar(array $conteos, array $respuestas = [], array $datos = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloPermisos())->ProcesarDatos($this->obtenerDatosValidos($datos));
    }

    // Caso 1 (E1): Modificación exitosa de nombre y descripción
    public function testModificacionExitosa(): void
    {
        $resultado = $this->ejecutar(['permisos.id_permiso.1' => 1]);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $update = $this->sentenciaEjecutada('UPDATE `permisos`');
        $this->assertStringNotContainsString('clave', $update['sql']);
        $this->assertStringNotContainsString('id_modulo', $update['sql']);
        $this->assertSame([
            ':nombre'      => 'Reinscribir Atleta',
            ':descripcion' => 'Permite Reinscribir Atletas',
            ':id'          => '9',
        ], $update['params']);
    }

    // Caso 2 (E2): Sin descripción no se envía NULL a una columna NOT NULL
    public function testModificacionSinDescripcion(): void
    {
        $resultado = $this->ejecutar(['permisos.id_permiso.1' => 1], [], ['descripcion' => '']);

        $this->assertSame('exito', $resultado['accion']);

        $update = $this->sentenciaEjecutada('UPDATE `permisos`');
        $this->assertFalse(
            array_key_exists(':descripcion', $update['params']) && $update['params'][':descripcion'] === null,
            'Se envía descripcion = NULL y la columna `descripcion` es NOT NULL.'
        );
    }

    // Caso 3 (E3): El permiso puede guardar su mismo nombre
    public function testConservaSuPropioNombre(): void
    {
        $resultado = $this->ejecutar([
            'permisos.id_permiso.1' => 1,
            'propia.nombre'         => 1,
            'permisos.nombre.1'     => 1,
        ]);

        $this->assertSame('exito', $resultado['accion']);
    }

    // Caso 4 (E4): Permiso inexistente
    public function testPermisoNoExiste(): void
    {
        $resultado = $this->ejecutar(['permisos.id_permiso.1' => 0]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('El permiso seleccionado no existe.', $this->textoError($resultado));
        $this->assertNull($this->sentenciaEjecutada('UPDATE `permisos`'));
    }

    // Caso 5 (E5): Nombre usado por otro permiso
    public function testNombreDuplicado(): void
    {
        $resultado = $this->ejecutar([
            'permisos.id_permiso.1' => 1,
            'propia.nombre'         => 0,
            'permisos.nombre.1'     => 1,
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Ya existe otro permiso con ese nombre.', $this->textoError($resultado));
        $this->assertNull($this->sentenciaEjecutada('UPDATE `permisos`'));
    }

    // Caso 6 (E4/E5): Los errores se devuelven en la clave "codigo" que lee el controlador
    public function testErrorEnClaveCodigo(): void
    {
        $resultado = $this->ejecutar(['permisos.id_permiso.1' => 0]);

        $this->assertArrayHasKey(
            'codigo',
            $resultado,
            'Modificar() devuelve el error en "mensaje", pero el controlador lee "codigo".'
        );
    }

    // Caso 7 (E6): Error de base de datos al modificar
    public function testErrorBaseDatosAlModificar(): void
    {
        $resultado = $this->ejecutar(['permisos.id_permiso.1' => 1], [
            'UPDATE `permisos`' => ['error' => new Exception('Error al actualizar permiso')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al actualizar permiso', $this->textoError($resultado));
    }
}
