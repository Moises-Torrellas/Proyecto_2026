<?php

namespace tests\Unit\Atletas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloAtletas;
use App\modelo\Conexion;
use Exception;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitAtletas')]
#[Group('eliminarAtletas')]
class EliminarAtletasTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    /**
     * Inyecta el mock de PDO en la instancia singleton de Conexion.
     */
    private function crearModeloConPDOMock(PDO $pdoMock): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, $pdoMock);
    }

    // Caso 1: El atleta no existe en la base de datos
    public function testAtletaNoExisteLanzaError(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(0); // 0 atletas encontrados

        $pdoMock->method('prepare')->willReturn($stmtMock);
        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '999'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 2: El atleta posee cargos activos asociados
    public function testAtletaConCargosActivosNoPuedeEliminarse(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'FROM atletas WHERE')) {
                $stmt->method('fetchColumn')->willReturn(1); // Atleta existe
            } elseif (str_contains($sql, 'FROM cargos WHERE')) {
                $stmt->method('fetchColumn')->willReturn(1); // Tiene cargos activos
            } else {
                $stmt->method('fetchColumn')->willReturn(0);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '10'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES, $resultado['codigo']);
    }

    // Caso 3: El atleta posee asignaciones activas asociadas
    public function testAtletaConAsignacionesActivasNoPuedeEliminarse(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'FROM atletas WHERE')) {
                $stmt->method('fetchColumn')->willReturn(1); // Atleta existe
            } elseif (str_contains($sql, 'FROM cargos WHERE')) {
                $stmt->method('fetchColumn')->willReturn(0); // No tiene cargos
            } elseif (str_contains($sql, 'FROM asignaciones WHERE')) {
                $stmt->method('fetchColumn')->willReturn(2); // Tiene asignaciones activas
            } else {
                $stmt->method('fetchColumn')->willReturn(0);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '10'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(ASSOCIATES . '1', $resultado['codigo']);
    }

    // Caso 4: Retiro/Eliminación exitosa con motivo personalizado
    public function testEliminarAtletaExitosoConMotivoPersonalizado(): void
    {
        $consultasEjecutadas = [];
        $parametrosRetiro = null;

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$consultasEjecutadas, &$parametrosRetiro) {
            $consultasEjecutadas[] = $sql;
            $stmt = $this->createMock(PDOStatement::class);

            if (str_contains($sql, 'INSERT INTO retiros')) {
                $stmt->method('execute')->willReturnCallback(function ($params) use (&$parametrosRetiro) {
                    $parametrosRetiro = $params;
                    return true;
                });
            } else {
                $stmt->method('execute')->willReturn(true);
            }

            if (str_contains($sql, 'FROM atletas WHERE')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'FROM cargos WHERE') || str_contains($sql, 'FROM asignaciones WHERE')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } elseif (str_contains($sql, 'SELECT MAX(codigo_inscripcion)')) {
                $stmt->method('fetchColumn')->willReturn(45); // Código de la última inscripción
            } else {
                $stmt->method('fetchColumn')->willReturn(1);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'eliminar',
            'id'            => '10',
            'motivo_retiro' => 'Mudanza de ciudad'
        ]);

        $this->assertEquals('exito', $resultado['accion']);

        $consultasTexto = implode(' ', $consultasEjecutadas);
        $this->assertStringContainsString('UPDATE inscripciones SET estatus = 2', $consultasTexto);
        $this->assertStringContainsString('INSERT INTO retiros', $consultasTexto);

        $this->assertNotNull($parametrosRetiro);
        $this->assertEquals(45, $parametrosRetiro[':ci']);
        $this->assertEquals('Mudanza de ciudad', $parametrosRetiro[':motivo']);
    }

    // Caso 5: Retiro/Eliminación exitosa con motivo por defecto (Retiro Voluntario)
    public function testEliminarAtletaExitosoConMotivoPorDefecto(): void
    {
        $parametrosRetiro = null;

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) use (&$parametrosRetiro) {
            $stmt = $this->createMock(PDOStatement::class);

            if (str_contains($sql, 'INSERT INTO retiros')) {
                $stmt->method('execute')->willReturnCallback(function ($params) use (&$parametrosRetiro) {
                    $parametrosRetiro = $params;
                    return true;
                });
            } else {
                $stmt->method('execute')->willReturn(true);
            }

            if (str_contains($sql, 'FROM atletas WHERE')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } elseif (str_contains($sql, 'FROM cargos WHERE') || str_contains($sql, 'FROM asignaciones WHERE')) {
                $stmt->method('fetchColumn')->willReturn(0);
            } elseif (str_contains($sql, 'SELECT MAX(codigo_inscripcion)')) {
                $stmt->method('fetchColumn')->willReturn(12);
            } else {
                $stmt->method('fetchColumn')->willReturn(1);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '5'
        ]);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertNotNull($parametrosRetiro);
        $this->assertEquals('Retiro Voluntario', $parametrosRetiro[':motivo']);
    }

    // Caso 6: Error o fallo en la base de datos ejecuta rollback
    public function testErrorEnBaseDeDatosRealizaRollback(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function ($sql) {
            if (str_contains($sql, 'UPDATE inscripciones SET estatus = 2')) {
                throw new Exception('Fallo de conexión al actualizar inscripción');
            }

            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);

            if (str_contains($sql, 'FROM atletas WHERE')) {
                $stmt->method('fetchColumn')->willReturn(1);
            } else {
                $stmt->method('fetchColumn')->willReturn(0);
            }
            return $stmt;
        });

        $this->crearModeloConPDOMock($pdoMock);

        $modelo = new ModeloAtletas();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'eliminar',
            'id'     => '10'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Fallo de conexión al actualizar inscripción', $resultado['codigo']);
    }
}
