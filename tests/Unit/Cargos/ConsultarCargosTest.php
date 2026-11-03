<?php

namespace tests\Unit\Cargos;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloCargos;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

#[Group('unit')]
#[Group('unitCargos')]
#[Group('consultarCargos')]
class ConsultarCargosTest extends TestCase
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

    // Caso 1: Consulta general sin filtros
    public function testConsultarGeneral(): void
    {
        $filasEsperadas = [
            [
                'id_cobrar' => 1,
                'atleta_nombre' => 'José',
                'atleta_apellido' => 'Altuve',
                'concepto_nombre' => 'Mensualidad',
                'fecha_emision' => '2026-03-01'
            ]
        ];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($filasEsperadas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'SELECT * FROM vista_cargos WHERE 1=1')
                && str_contains($sql, 'ORDER BY id_atleta, fecha_emision DESC')
                && !str_contains($sql, 'LIKE :f1');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('José', $resultado['datos'][0]['atleta_nombre']);
    }

    // Caso 2: Consulta con filtro de texto global
    public function testConsultarConFiltroTexto(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->with($this->callback(function (array $params) {
            return isset($params[':f1']) && $params[':f1'] === '%José%'
                && isset($params[':f8']) && $params[':f8'] === '%José%';
        }))->willReturn(true);
        $stmt->method('fetchAll')->willReturn([
            ['id_cobrar' => 1, 'atleta_nombre' => 'José']
        ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'LIKE :f1') && str_contains($sql, 'LIKE :f8');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->Consultar(['filtro' => 'José']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
    }

    // Caso 3: Consulta con filtros específicos de atleta, concepto y rango de fechas
    public function testConsultarConFiltrosEspecificos(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->with($this->callback(function (array $params) {
            return ($params[':atleta'] ?? null) === '5,6'
                && ($params[':concepto'] ?? null) === '2'
                && ($params[':fecha_inicio'] ?? null) === '2026-01-01'
                && ($params[':fecha_fin'] ?? null) === '2026-03-31';
        }))->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->with($this->callback(function (string $sql) {
            return str_contains($sql, 'FIND_IN_SET(id_atleta, :atleta)')
                && str_contains($sql, 'id_concepto = :concepto')
                && str_contains($sql, 'fecha_emision >= :fecha_inicio')
                && str_contains($sql, 'fecha_emision <= :fecha_fin');
        }))->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->Consultar([
            'atleta'       => '5,6',
            'concepto'     => '2',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin'    => '2026-03-31'
        ]);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 4: Consulta sin registros retornados
    public function testConsultarSinRegistros(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmt);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->Consultar();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertIsArray($resultado['datos']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 5: Error de base de datos durante la consulta
    public function testErrorBaseDatos(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new Exception('Error de conexión a la BD'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloCargos();
        $resultado = $modelo->Consultar();

        $this->assertEquals('error', $resultado['accion']);
    }
}

