<?php

namespace tests\Unit\Bitacora;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloBitacora;
use Exception;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/BitacoraMockTrait.php';

/**
 * RF-BIT-04: El sistema debe ofrecer las listas de usuarios y de módulos, ordenadas
 * alfabéticamente, para usarlas como opciones de filtro en la bitácora y su reporte.
 *
 * Escenarios:
 *  E1. Cargar la lista de usuarios.
 *  E2. Cargar la lista de módulos.
 *  E3. Falla de la base de datos.
 */
#[Group('unit')]
#[Group('unitBitacora')]
#[Group('consultarCatalogosBitacora')]
class ConsultarCatalogosBitacoraTest extends TestCase
{
    use BitacoraMockTrait;

    // Caso 1 (E1): Lista de usuarios ordenada por nombre, sin datos sensibles
    public function testConsultarUsuarios(): void
    {
        $filas = [
            ['idUsuario' => 2, 'cedulaUsuario' => '87654321', 'nombreUsuario' => 'Ana', 'apellidoUsuario' => 'Gómez'],
            ['idUsuario' => 1, 'cedulaUsuario' => '12345678', 'nombreUsuario' => 'Juan', 'apellidoUsuario' => 'Pérez'],
        ];
        $this->inyectarPdoMock($this->crearPdoMock([
            'FROM usuarios' => ['fetchAll' => $filas],
        ]));

        $resultado = (new ModeloBitacora())->consultarUsuarios();

        $this->assertSame('consultar_usuarios', $resultado['accion']);
        $this->assertSame($filas, $resultado['datos']);

        $sql = $this->sentenciaEjecutada('FROM usuarios')['sql'];
        $this->assertStringContainsString('ORDER BY nombreUsuario ASC', $sql);
        $this->assertStringNotContainsString('pass_hash', $sql);
        $this->assertStringNotContainsString('*', $sql);
    }

    // Caso 2 (E3): Error de base de datos al cargar usuarios
    public function testConsultarUsuariosError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'FROM usuarios' => ['error' => new Exception('Tabla no encontrada')],
        ]));

        $resultado = (new ModeloBitacora())->consultarUsuarios();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Tabla no encontrada', $resultado['mensaje']);
    }

    // Caso 3 (E2): Lista de módulos ordenada por nombre
    public function testConsultarModulos(): void
    {
        $filas = [
            ['id_modulo' => 6, 'nombre_modulo' => 'Atletas'],
            ['id_modulo' => 103, 'nombre_modulo' => 'Roles'],
        ];
        $this->inyectarPdoMock($this->crearPdoMock([
            'FROM modulos' => ['fetchAll' => $filas],
        ]));

        $resultado = (new ModeloBitacora())->consultarModulos();

        $this->assertSame('consultar_modulos', $resultado['accion']);
        $this->assertSame($filas, $resultado['datos']);
        $this->assertStringContainsString(
            'ORDER BY nombre_modulo ASC',
            $this->sentenciaEjecutada('FROM modulos')['sql']
        );
    }

    // Caso 4 (E3): Error de base de datos al cargar módulos
    public function testConsultarModulosError(): void
    {
        $this->inyectarPdoMock($this->crearPdoMock([
            'FROM modulos' => ['error' => new Exception('Tabla no encontrada')],
        ]));

        $resultado = (new ModeloBitacora())->consultarModulos();

        $this->assertSame('error', $resultado['accion']);
        $this->assertSame('Tabla no encontrada', $resultado['mensaje']);
    }
}
