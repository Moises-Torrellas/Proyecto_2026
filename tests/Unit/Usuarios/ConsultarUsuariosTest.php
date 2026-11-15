<?php

namespace tests\Unit\Usuarios;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloUsuarios;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/UsuariosMockTrait.php';

/**
 * RF-USU-02: El sistema debe listar los usuarios registrados y permitir filtrarlos
 * (buscador general de la tabla y filtros específicos del reporte).
 *
 * Escenarios:
 *  E1. Listado general sin filtros.
 *  E2. Búsqueda general desde la tabla.
 *  E3. Filtros específicos del reporte (cédula, nombre, apellido, rol).
 *  E4. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitUsuarios')]
#[Group('consultarUsuarios')]
class ConsultarUsuariosTest extends TestCase
{
    use UsuariosMockTrait;

    private function filas(): array
    {
        return [
            ['idUsuario' => 1, 'cedulaUsuario' => '12345678', 'nombreUsuario' => 'Juan', 'apellidoUsuario' => 'Pérez', 'nombre_rol' => 'Administrador'],
            ['idUsuario' => 2, 'cedulaUsuario' => '87654321', 'nombreUsuario' => 'Ana', 'apellidoUsuario' => 'Gómez', 'nombre_rol' => 'Entrenador'],
        ];
    }

    // Caso 1 (E1): Listado exitoso sin filtros
    public function testConsultaExitosaSinFiltros(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => $this->filas()],
        ]));

        $resultado = (new ModeloUsuarios())->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_usuarios');
        $this->assertStringContainsString('ORDER BY idUsuario ASC', $sentencia['sql']);
        $this->assertSame([], $sentencia['params']);
    }

    // Caso 2 (E1): Listado vacío cuando no hay usuarios
    public function testConsultaSinRegistros(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloUsuarios())->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    // Caso 3 (E2): La búsqueda general filtra por cédula, nombre, apellido, correo y rol
    public function testBusquedaGeneral(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => [$this->filas()[1]]],
        ]));

        $resultado = (new ModeloUsuarios())->Consultar(['filtro' => 'Ana']);

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_usuarios');
        $this->assertStringContainsString('nombre_rol LIKE :f5', $sentencia['sql']);
        foreach ([':f1', ':f2', ':f3', ':f4', ':f5'] as $param) {
            $this->assertSame('%Ana%', $sentencia['params'][$param]);
        }
    }

    // Caso 4 (E3): Reporte filtrado por cédula, nombre y apellido
    public function testReporteConFiltrosEspecificos(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => [$this->filas()[0]]],
        ]));

        $resultado = (new ModeloUsuarios())->procesarDatos([
            'accion'   => 'reporte',
            'cedula'   => '1234',
            'nombre'   => 'juan',
            'apellido' => 'pérez',
        ]);

        $this->assertSame('consultar', $resultado['accion']);

        $params = $this->sentenciaEjecutada('vista_consulta_usuarios')['params'];
        $this->assertSame('1234%', $params[':cedula']);
        $this->assertSame('%Juan%', $params[':nombre']);
        $this->assertSame('%Pérez%', $params[':apellido']);
        $this->assertArrayNotHasKey(':rol', $params);
    }

    // Caso 5 (E3): Reporte filtrado por rol (el id se convierte a entero)
    public function testReporteFiltradoPorRol(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => $this->filas()],
        ]));

        (new ModeloUsuarios())->procesarDatos(['accion' => 'reporte', 'roles_id' => '2']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_usuarios');
        $this->assertStringContainsString('id_rol = :rol', $sentencia['sql']);
        $this->assertSame(2, $sentencia['params'][':rol']);
    }

    // Caso 6 (E3): El rol "0" (todos) no se aplica como filtro
    public function testReporteRolTodosNoFiltra(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['fetchAll' => $this->filas()],
        ]));

        (new ModeloUsuarios())->procesarDatos(['accion' => 'reporte', 'roles_id' => '0']);

        $sentencia = $this->sentenciaEjecutada('vista_consulta_usuarios');
        $this->assertStringNotContainsString(':rol', $sentencia['sql']);
    }

    // Caso 7 (E4): Error de base de datos devuelve mensaje controlado
    public function testErrorBaseDatos(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'vista_consulta_usuarios' => ['error' => new Exception('Tabla no encontrada')],
        ]));

        $resultado = (new ModeloUsuarios())->Consultar();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Ocurrio un error al listar los usuarios', $resultado['mensaje']);
    }
}
