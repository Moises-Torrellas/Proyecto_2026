<?php

namespace tests\Unit\Permisos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPermisos;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/PermisosMockTrait.php';

/**
 * RF-PER-02: El sistema debe listar los permisos agrupados por módulo (ordenados por módulo
 * y luego por permiso), permitir buscarlos por nombre o clave y consultar el detalle de uno.
 *
 * Escenarios:
 *  E1. Listado general.
 *  E2. Búsqueda por nombre o clave.
 *  E3. Detalle de un permiso.
 *  E4. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitPermisos')]
#[Group('consultarPermisos')]
class ConsultarPermisosTest extends TestCase
{
    use PermisosMockTrait;

    private function filas(): array
    {
        return [
            ['id_permiso' => 1, 'nombre_permiso' => 'Ingresar Atletas', 'clave' => 'ingresar_atletas', 'id_modulo' => 6, 'nombre_modulo' => 'Atletas'],
            ['id_permiso' => 2, 'nombre_permiso' => 'Registrar Atleta', 'clave' => 'registrar_atleta', 'id_modulo' => 6, 'nombre_modulo' => 'Atletas'],
        ];
    }

    // Caso 1 (E1): Listado ordenado por módulo y permiso
    public function testConsultaExitosa(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_permisos' => ['fetchAll' => $this->filas()],
        ]));

        $resultado = (new ModeloPermisos())->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_permisos');
        $this->assertStringContainsString('ORDER BY id_modulo ASC, id_permiso ASC', $sentencia['sql']);
        $this->assertSame([], $sentencia['params']);
    }

    // Caso 2 (E2): La búsqueda filtra por nombre o clave del permiso
    public function testBusqueda(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_permisos' => ['fetchAll' => [$this->filas()[1]]],
        ]));

        $resultado = (new ModeloPermisos())->Consultar(['filtro' => 'registrar']);

        $this->assertCount(1, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_permisos');
        $this->assertStringContainsString('nombre_permiso LIKE :f1 OR clave LIKE :f2', $sentencia['sql']);
        $this->assertSame([':f1' => '%registrar%', ':f2' => '%registrar%'], $sentencia['params']);
    }

    // Caso 3 (E4): Error de base de datos al listar
    public function testConsultaError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_permisos' => ['error' => new Exception('Vista no encontrada')],
        ]));

        $resultado = (new ModeloPermisos())->Consultar();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Vista no encontrada', $resultado['mensaje']);
    }

    // Caso 4 (E3): Detalle de un permiso existente
    public function testBuscarExitoso(): void
    {
        $fila = ['id_permiso' => 2, 'id_modulo' => 6, 'nombre' => 'Registrar Atleta', 'clave' => 'registrar_atleta', 'estatus' => 1];
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM permisos WHERE id_permiso' => ['fetchAll' => [$fila]],
        ]));

        $resultado = (new ModeloPermisos())->ProcesarDatos(['accion' => 'buscar', 'id' => '2']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([$fila], $resultado['datos']);
        $this->assertSame([':id' => '2'], $this->sentenciaEjecutada('FROM permisos WHERE id_permiso')['params']);
    }

    // Caso 5 (E3): Detalle de un permiso inexistente devuelve datos vacíos
    public function testBuscarNoEncontrado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM permisos WHERE id_permiso' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloPermisos())->ProcesarDatos(['accion' => 'buscar', 'id' => '999']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    // Caso 6 (E4): Error de base de datos al buscar
    public function testBuscarError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM permisos WHERE id_permiso' => ['error' => new Exception('Error de consulta')],
        ]));

        $resultado = (new ModeloPermisos())->ProcesarDatos(['accion' => 'buscar', 'id' => '2']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de consulta', $resultado['mensaje']);
    }
}
