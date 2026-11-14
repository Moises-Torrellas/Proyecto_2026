<?php

namespace tests\Unit\Roles;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRoles;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/RolesMockTrait.php';

/**
 * RF-ROL-02: El sistema debe listar los roles registrados, ocultando los roles protegidos
 * (nivel 1 Super Usuario y nivel 2), y permitir filtrarlos por nombre tanto desde el
 * buscador de la tabla como desde el reporte.
 *
 * Escenarios:
 *  E1. Listado general sin filtros.
 *  E2. Búsqueda por nombre desde la tabla.
 *  E3. Reporte filtrado por nombre.
 *  E4. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitRoles')]
#[Group('consultarRoles')]
class ConsultarRolesTest extends TestCase
{
    use RolesMockTrait;

    private function filas(): array
    {
        return [
            ['id_rol' => 3, 'nombre_rol' => 'Entrenador', 'descripcion' => 'Dirige los entrenamientos', 'nivel_rol' => 3],
            ['id_rol' => 4, 'nombre_rol' => 'Secretaria', 'descripcion' => 'Gestiona los pagos', 'nivel_rol' => 3],
        ];
    }

    // Caso 1 (E1): Listado exitoso que excluye los roles protegidos de nivel 1 y 2
    public function testConsultaExitosa(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['fetchAll' => $this->filas()],
        ]));

        $resultado = (new ModeloRoles())->consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('FROM roles');
        $this->assertStringContainsString('nivel_rol != 1', $sentencia['sql']);
        $this->assertStringContainsString('nivel_rol != 2', $sentencia['sql']);
        $this->assertStringContainsString('ORDER BY id_rol ASC', $sentencia['sql']);
        $this->assertSame([], $sentencia['params']);
    }

    // Caso 2 (E1): Listado vacío cuando solo existen roles protegidos
    public function testConsultaSinRegistros(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloRoles())->consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    // Caso 3 (E2): La búsqueda desde la tabla filtra por nombre del rol
    public function testBusquedaPorNombre(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['fetchAll' => [$this->filas()[0]]],
        ]));

        $resultado = (new ModeloRoles())->consultar(['filtro' => 'Entre']);

        $this->assertCount(1, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('FROM roles');
        $this->assertStringContainsString('nombre_rol LIKE :f1', $sentencia['sql']);
        $this->assertSame('%Entre%', $sentencia['params'][':f1']);
    }

    // Caso 4 (E3): El reporte sin filtros lista todos los roles visibles
    public function testReporteSinFiltros(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['fetchAll' => $this->filas()],
        ]));

        $resultado = (new ModeloRoles())->procesarDatos(['accion' => 'reporte']);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
    }

    // Caso 5 (E3): El reporte filtrado por nombre aplica el filtro en la consulta
    // (el controlador envía el campo "nombre" desde el modal de reporte)
    public function testReporteFiltradoPorNombre(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['fetchAll' => [$this->filas()[0]]],
        ]));

        (new ModeloRoles())->procesarDatos(['accion' => 'reporte', 'nombre' => 'Entrenador']);

        $sentencia = $this->sentenciaEjecutada('FROM roles');
        $this->assertStringContainsString(
            'nombre_rol LIKE',
            $sentencia['sql'],
            'El filtro "nombre" del reporte no se aplica en la consulta.'
        );
    }

    // Caso 6 (E4): Error de base de datos devuelve mensaje controlado
    public function testErrorBaseDatos(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM roles' => ['error' => new Exception('Tabla no encontrada')],
        ]));

        $resultado = (new ModeloRoles())->consultar();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error al listar roles: Tabla no encontrada', $resultado['mensaje']);
    }
}
