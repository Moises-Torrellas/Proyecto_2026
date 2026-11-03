<?php

namespace tests\Unit\Representantes;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRepresentantes;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitRepresentantes')]
#[Group('consultarRepresentantes')]
class ConsultarRepresentantesTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    private function inyectarPdoMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);
    }

    private function obtenerFilaRepresentante(array $sobrescribir = []): array
    {
        $base = [
            'codigo_representante' => 1,
            'cedula'               => '12345678',
            'tipo_doc'             => 'V',
            'nombre'               => 'Carlos Alberto',
            'apellido'             => 'Perez Gonzalez',
            'telefono'             => '0414-1234567',
            'direccion'            => 'Av. Lara con Calle 10',
            'correo'               => 'carlos@correo.com',
            'instagram'            => '@carlosperez'
        ];

        return array_merge($base, $sobrescribir);
    }

    // Caso 1: Consulta general sin filtros
    public function testConsultarGeneral(): void
    {
        $filas = [$this->obtenerFilaRepresentante()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Carlos Alberto', $resultado['datos'][0]['nombre']);
    }

    // Caso 2: Consulta aplicando filtro de búsqueda por texto
    public function testConsultarConFiltro(): void
    {
        $filas = [$this->obtenerFilaRepresentante(['nombre' => 'Maria'])];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->with($this->callback(function ($params) {
            return isset($params[':f1']) && $params[':f1'] === '%Maria%';
        }))->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->Consultar(['filtro' => 'Maria']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 3: Consulta invocada mediante ProcesarDatos con acción generar
    public function testConsultarPorGenerar(): void
    {
        $filas = [$this->obtenerFilaRepresentante()];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->ProcesarDatos([
            'accion'       => 'generar',
            'cedula'       => '1234',
            'nacionalidad' => 'V'
        ]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 4: Consulta que no encuentra ningún resultado
    public function testConsultarVacio(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->Consultar(['filtro' => 'Inexistente']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 5: Error de base de datos durante la consulta
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error al consultar'));
        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloRepresentantes();
        $resultado = $modelo->Consultar();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Error al consultar', $resultado['mensaje']);
    }
}

