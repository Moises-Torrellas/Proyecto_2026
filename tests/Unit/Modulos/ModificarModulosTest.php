<?php

namespace tests\Unit\Modulos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloModulos;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/ModulosMockTrait.php';

/**
 * RF-MOD-03: El sistema debe permitir modificar el nombre y la descripción de un módulo
 * activo. El nombre no puede pertenecer a otro módulo; conservar el propio nombre es
 * válido. Los errores se devuelven con los códigos que interpreta el controlador
 * (INVALID_ID, DUPLICATE_NAME).
 *
 * Escenarios:
 *  E1. Modificación con datos válidos.
 *  E2. El módulo conserva su propio nombre.
 *  E3. Módulo inexistente o inactivo.
 *  E4. Nombre usado por otro módulo.
 *  E5. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitModulos')]
#[Group('modificarModulos')]
class ModificarModulosTest extends TestCase
{
    use ModulosMockTrait;

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'accion'      => 'modificar',
            'id'          => '6',
            'nombre'      => 'gestión de atletas',
            'descripcion' => 'registro y control de atletas',
        ], $sobrescribir);
    }

    private function ejecutar(array $conteos, array $respuestas = [], array $datos = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloModulos())->ProcesarDatos($this->obtenerDatosValidos($datos));
    }

    // Caso 1 (E1): Modificación exitosa de nombre y descripción
    public function testModificacionExitosa(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1]);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $update = $this->sentenciaEjecutada('UPDATE `modulos`');
        $this->assertStringNotContainsString('icono', $update['sql']);
        $this->assertStringNotContainsString('estatus', $update['sql']);
        $this->assertSame([
            ':nombre'      => 'Gestión De Atletas',
            ':descripcion' => 'Registro Y Control De Atletas',
            ':id'          => '6',
        ], $update['params']);
    }

    // Caso 2 (E2): El módulo puede guardar su mismo nombre sin considerarse duplicado
    public function testConservaSuPropioNombre(): void
    {
        $resultado = $this->ejecutar([
            'modulos.id_modulo.1'     => 1,
            'propia.nombre_modulo'    => 1,
            'modulos.nombre_modulo.1' => 1,
        ]);

        $this->assertSame('exito', $resultado['accion']);
        $this->assertNotNull($this->sentenciaEjecutada('UPDATE `modulos`'));
    }

    // Caso 3 (E3): Módulo inexistente o inactivo
    public function testModuloNoExiste(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 0]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(INVALID_ID, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `modulos`'));
    }

    // Caso 4 (E4): Nombre usado por otro módulo
    public function testNombreDuplicado(): void
    {
        $resultado = $this->ejecutar([
            'modulos.id_modulo.1'     => 1,
            'propia.nombre_modulo'    => 0,
            'modulos.nombre_modulo.1' => 1,
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame(DUPLICATE_NAME, $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('UPDATE `modulos`'));
    }

    // Caso 5 (E4): Cambiar a un nombre libre es válido
    public function testCambioANombreLibre(): void
    {
        $resultado = $this->ejecutar(
            ['modulos.id_modulo.1' => 1, 'propia.nombre_modulo' => 0, 'modulos.nombre_modulo.1' => 0],
            [],
            ['nombre' => 'deportistas']
        );

        $this->assertSame('exito', $resultado['accion']);
        $this->assertSame('Deportistas', $this->sentenciaEjecutada('UPDATE `modulos`')['params'][':nombre']);
    }

    // Caso 6 (E5): Error de base de datos al modificar
    public function testErrorBaseDatosAlModificar(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1], [
            'UPDATE `modulos`' => ['error' => new Exception('Error al actualizar módulo')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al actualizar módulo', $resultado['codigo']);
    }
}
