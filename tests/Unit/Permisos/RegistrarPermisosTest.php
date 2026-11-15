<?php

namespace tests\Unit\Permisos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPermisos;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/PermisosMockTrait.php';

/**
 * RF-PER-03: El sistema debe permitir registrar permisos asociados a un módulo activo, con
 * nombre y clave técnica únicos y descripción opcional. La clave identifica al permiso en
 * las sesiones de los usuarios, por lo que no puede repetirse aunque el permiso que la usa
 * esté bloqueado. Los errores deben devolverse con los códigos que interpreta el controlador
 * (DUPLICATE_NAME, ASSOCIATES) para mostrar el mensaje adecuado al usuario.
 *
 * Escenarios:
 *  E1. Registro con todos los datos.
 *  E2. Registro sin descripción.
 *  E3. Módulo inexistente o inactivo.
 *  E4. Clave técnica repetida.
 *  E5. Nombre repetido.
 *  E6. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitPermisos')]
#[Group('registrarPermisos')]
class RegistrarPermisosTest extends TestCase
{
    use PermisosMockTrait;

    private function obtenerDatosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'accion'      => 'incluir',
            'modulo'      => '6',
            'nombre'      => 'reinscribir atleta',
            'clave'       => 'reinscribir_atleta',
            'descripcion' => 'permite reinscribir atletas retirados',
        ], $sobrescribir);
    }

    private function ejecutar(array $conteos, array $respuestas = [], array $datos = []): array
    {
        $this->inyectarPdoMock($this->crearPdoMock($conteos, $respuestas));
        return (new ModeloPermisos())->ProcesarDatos($this->obtenerDatosValidos($datos));
    }

    // Caso 1 (E1): Registro exitoso con descripción
    public function testRegistroExitoso(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1]);

        $this->assertSame(['accion' => 'exito'], $resultado);

        $insert = $this->sentenciaEjecutada('INSERT INTO `permisos`');
        $this->assertStringContainsString('(id_modulo, nombre, clave, descripcion)', $insert['sql']);
        $this->assertSame([
            ':id_modulo'   => '6',
            ':nombre'      => 'Reinscribir Atleta',
            ':clave'       => 'reinscribir_atleta',
            ':descripcion' => 'Permite Reinscribir Atletas Retirados',
        ], $insert['params']);
    }

    // Caso 2 (E2): Registro sin descripción (usa el valor por defecto de la BD)
    public function testRegistroSinDescripcion(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1], [], ['descripcion' => '']);

        $this->assertSame('exito', $resultado['accion']);

        $insert = $this->sentenciaEjecutada('INSERT INTO `permisos`');
        $this->assertStringNotContainsString('descripcion', $insert['sql']);
        $this->assertArrayNotHasKey(':descripcion', $insert['params']);
    }

    // Caso 3 (E3): Módulo inexistente o inactivo es rechazado
    public function testModuloNoExiste(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 0]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertNull($this->sentenciaEjecutada('INSERT INTO `permisos`'));
    }

    // Caso 4 (E3): El error de módulo inexistente usa el código que entiende el controlador
    public function testModuloNoExisteCodigo(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 0]);

        $this->assertSame(
            ASSOCIATES,
            $resultado['codigo'],
            'El controlador espera ASSOCIATES; con otro código el usuario ve "Ocurrió un error inesperado".'
        );
    }

    // Caso 5 (E4): Clave técnica usada por un permiso activo
    public function testClaveDuplicada(): void
    {
        // Un permiso activo con esa clave aparece tanto al filtrar por estatus 1 como sin filtrar
        $resultado = $this->ejecutar([
            'modulos.id_modulo.1' => 1,
            'permisos.clave.1'    => 1,
            'permisos.clave.null' => 1,
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('La clave técnica del permiso ya está registrada.', $resultado['codigo']);
        $this->assertNull($this->sentenciaEjecutada('INSERT INTO `permisos`'));
    }

    // Caso 6 (E4): Clave técnica usada por un permiso bloqueado también se rechaza
    public function testClaveDePermisoBloqueado(): void
    {
        $resultado = $this->ejecutar([
            'modulos.id_modulo.1' => 1,
            'permisos.clave.null' => 1,
            'permisos.clave.2'    => 1,
        ]);

        $this->assertSame('error', $resultado['accion'], 'Se registró una clave que ya usa un permiso bloqueado.');
        $this->assertNull($this->sentenciaEjecutada('INSERT INTO `permisos`'));
    }

    // Caso 7 (E5): Nombre usado por otro permiso
    public function testNombreDuplicado(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1, 'permisos.nombre.1' => 1]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertNull($this->sentenciaEjecutada('INSERT INTO `permisos`'));
    }

    // Caso 8 (E5): El error de nombre duplicado usa el código que entiende el controlador
    public function testNombreDuplicadoCodigo(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1, 'permisos.nombre.1' => 1]);

        $this->assertSame(
            DUPLICATE_NAME,
            $resultado['codigo'],
            'El controlador espera DUPLICATE_NAME; con otro código el usuario ve "Ocurrió un error inesperado".'
        );
    }

    // Caso 9 (E6): Error de base de datos al registrar
    public function testErrorBaseDatosAlRegistrar(): void
    {
        $resultado = $this->ejecutar(['modulos.id_modulo.1' => 1], [
            'INSERT INTO `permisos`' => ['error' => new Exception('Error al insertar permiso')],
        ]);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al insertar permiso', $resultado['codigo']);
    }
}
