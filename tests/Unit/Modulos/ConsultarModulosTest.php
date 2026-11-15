<?php

namespace tests\Unit\Modulos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloModulos;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/ModulosMockTrait.php';

/**
 * RF-MOD-02: El sistema debe listar los módulos ordenados por id, ocultando los módulos
 * internos del sistema (ids 4, 5 y 8), permitir buscarlos por nombre y consultar el
 * detalle de un módulo.
 *
 * Escenarios:
 *  E1. Listado general.
 *  E2. Búsqueda por nombre.
 *  E3. Detalle de un módulo.
 *  E4. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitModulos')]
#[Group('consultarModulos')]
class ConsultarModulosTest extends TestCase
{
    use ModulosMockTrait;

    private function filas(): array
    {
        return [
            ['id_modulo' => 6, 'nombre_modulo' => 'Atletas', 'descripcion' => 'Gestión de atletas', 'icono' => 'user', 'estatus' => 1],
            ['id_modulo' => 7, 'nombre_modulo' => 'Representantes', 'descripcion' => 'Gestión de representantes', 'icono' => 'users', 'estatus' => 1],
        ];
    }

    // Caso 1 (E1): Listado ordenado que excluye los módulos internos 4, 5 y 8
    public function testConsultaExitosa(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos' => ['fetchAll' => $this->filas()],
        ]));

        $resultado = (new ModeloModulos())->Consultar();

        $this->assertSame('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('FROM modulos');
        $this->assertStringContainsString('id_modulo NOT IN (4, 5, 8)', $sentencia['sql']);
        $this->assertStringContainsString('ORDER BY id_modulo ASC', $sentencia['sql']);
        $this->assertSame([], $sentencia['params']);
    }

    // Caso 2 (E2): La búsqueda filtra por nombre del módulo
    public function testBusquedaPorNombre(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos' => ['fetchAll' => [$this->filas()[0]]],
        ]));

        $resultado = (new ModeloModulos())->Consultar(['filtro' => 'Atle']);

        $this->assertCount(1, $resultado['datos']);

        $sentencia = $this->sentenciaEjecutada('FROM modulos');
        $this->assertStringContainsString('nombre_modulo LIKE :f1', $sentencia['sql']);
        $this->assertSame([':f1' => '%Atle%'], $sentencia['params']);
    }

    // Caso 3 (E4): Error de base de datos al listar
    public function testConsultaError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos' => ['error' => new Exception('Tabla no encontrada')],
        ]));

        $resultado = (new ModeloModulos())->Consultar();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Tabla no encontrada', $resultado['mensaje']);
    }

    // Caso 4 (E3): Detalle de un módulo existente
    public function testBuscarExitoso(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos WHERE id_modulo' => ['fetchAll' => [$this->filas()[0]]],
        ]));

        $resultado = (new ModeloModulos())->ProcesarDatos(['accion' => 'buscar', 'id' => '6']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([$this->filas()[0]], $resultado['datos']);
        $this->assertSame([':id' => '6'], $this->sentenciaEjecutada('FROM modulos WHERE id_modulo')['params']);
    }

    // Caso 5 (E3): Detalle de un módulo inexistente devuelve datos vacíos
    public function testBuscarNoEncontrado(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos WHERE id_modulo' => ['fetchAll' => []],
        ]));

        $resultado = (new ModeloModulos())->ProcesarDatos(['accion' => 'buscar', 'id' => '999']);

        $this->assertSame('buscar', $resultado['accion']);
        $this->assertSame([], $resultado['datos']);
    }

    // Caso 6 (E4): Error de base de datos al buscar
    public function testBuscarError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([], [
            'FROM modulos WHERE id_modulo' => ['error' => new Exception('Error de consulta')],
        ]));

        $resultado = (new ModeloModulos())->ProcesarDatos(['accion' => 'buscar', 'id' => '6']);

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Error de consulta', $resultado['mensaje']);
    }
}
