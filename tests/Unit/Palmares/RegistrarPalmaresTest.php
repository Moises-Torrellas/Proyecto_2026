<?php

namespace tests\Unit\Palmares;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPalmares;
use App\modelo\ModeloPremios;
use App\modelo\Conexion;
use PDO;
use PDOException;
use PDOStatement;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitPalmares')]
#[Group('RF-02')]
#[Group('ESC-02')]
#[Group('ESC-03')]
class RegistrarPalmaresTest extends TestCase
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

    // ==========================================
    // ESCENARIO 02: Inclusión de Palmarés Individual
    // ==========================================

    // Caso 07: Inclusión exitosa de un palmarés individual
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-07')]
    public function testIncluirPalmaresIndividualExitoso(): void
    {
        // 1. Verificación existencia de premio
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        // 2. Obtener codigo_dtll_prtc
        $stmtDetalle = $this->createMock(PDOStatement::class);
        $stmtDetalle->method('execute')->willReturn(true);
        $stmtDetalle->method('fetchColumn')->willReturn(45);

        // 3. Verificación de no duplicado
        $stmtDup = $this->createMock(PDOStatement::class);
        $stmtDup->method('execute')->willReturn(true);
        $stmtDup->method('fetchColumn')->willReturn(0);

        // 4. Inserción
        $stmtInsert = $this->createMock(PDOStatement::class);
        $stmtInsert->method('execute')->willReturn(true);

        // 5. Obtener ID generado
        $stmtId = $this->createMock(PDOStatement::class);
        $stmtId->method('execute')->willReturn(true);
        $stmtId->method('fetchColumn')->willReturn(101);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtDetalle, $stmtDup, $stmtInsert, $stmtId
        ) {
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'detalles_participacion')) {
                return $stmtDetalle;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'palmares_individual')) {
                return $stmtDup;
            }
            if (str_contains($sql, 'INSERT INTO palmares_individual')) {
                return $stmtInsert;
            }
            if (str_contains($sql, 'SELECT codigo_individual FROM palmares_individual')) {
                return $stmtId;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);
        $mockPremios->expects($this->once())->method('validarTipoPremio')->with(3, 'I');

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'torneo'        => 1,
            'atleta'        => 5,
            'premio'        => 3,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals(101, $modelo->getId());
    }

    // Caso 08: Rechazo de palmarés individual cuando el premio no existe
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-08')]
    public function testIncluirPalmaresIndividualPremioNoExiste(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(0); // Premio inexistente

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->with($this->stringContains('premios'))->willReturn($stmtExistePremio);

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'torneo'        => 1,
            'atleta'        => 5,
            'premio'        => 999,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 09: Rechazo de palmarés individual por tipo de premio incorrecto
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-09')]
    public function testIncluirPalmaresIndividualTipoPremioIncorrecto(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturn($stmtExistePremio);

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);
        $mockPremios->method('validarTipoPremio')->willThrowException(new Exception('El tipo de premio seleccionado no es válido.'));

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'torneo'        => 1,
            'atleta'        => 5,
            'premio'        => 2,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El tipo de premio seleccionado no es válido.', $resultado['codigo']);
    }

    // Caso 10: Rechazo de palmarés individual si el atleta no tiene participación en el torneo
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-10')]
    public function testIncluirPalmaresIndividualAtletaSinParticipacion(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtDetalle = $this->createMock(PDOStatement::class);
        $stmtDetalle->method('execute')->willReturn(true);
        $stmtDetalle->method('fetchColumn')->willReturn(false); // No hay participación

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistePremio, $stmtDetalle) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            return $stmtDetalle;
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'torneo'        => 1,
            'atleta'        => 5,
            'premio'        => 3,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El atleta no cuenta con una participación registrada en este torneo.', $resultado['codigo']);
    }

    // Caso 11: Rechazo de palmarés individual por registro duplicado
    #[Group('RF-02')]
    #[Group('ESC-02')]
    #[Group('CP-11')]
    public function testIncluirPalmaresIndividualDuplicado(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtDetalle = $this->createMock(PDOStatement::class);
        $stmtDetalle->method('execute')->willReturn(true);
        $stmtDetalle->method('fetchColumn')->willReturn(45);

        $stmtDup = $this->createMock(PDOStatement::class);
        $stmtDup->method('execute')->willReturn(true);
        $stmtDup->method('fetchColumn')->willReturn(1); // Ya existe

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtDetalle, $stmtDup
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'detalles_participacion')) {
                return $stmtDetalle;
            }
            return $stmtDup;
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'torneo'        => 1,
            'atleta'        => 5,
            'premio'        => 3,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE, $resultado['codigo']);
    }

    // ==========================================
    // ESCENARIO 03: Inclusión de Palmarés Grupal
    // ==========================================

    // Caso 12: Inclusión exitosa de un palmarés grupal con llamada a SP
    #[Group('RF-02')]
    #[Group('ESC-03')]
    #[Group('CP-12')]
    public function testIncluirPalmaresGrupalExitoso(): void
    {
        // 1. Verificación existencia premio
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        // 2. Participación del equipo
        $stmtPart = $this->createMock(PDOStatement::class);
        $stmtPart->method('execute')->willReturn(true);
        $stmtPart->method('fetchColumn')->willReturn(18);

        // 3. Verificación de duplicado
        $stmtDup = $this->createMock(PDOStatement::class);
        $stmtDup->method('execute')->willReturn(true);
        $stmtDup->method('fetchColumn')->willReturn(0);

        // 4. Stored Procedure ProcesarPalmaresGrupal
        $stmtSP = $this->createMock(PDOStatement::class);
        $stmtSP->method('execute')->willReturn(true);
        $stmtSP->expects($this->once())->method('closeCursor')->willReturn(true);

        // 5. Obtener ID generado
        $stmtId = $this->createMock(PDOStatement::class);
        $stmtId->method('execute')->willReturn(true);
        $stmtId->method('fetchColumn')->willReturn(50);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtPart, $stmtDup, $stmtSP, $stmtId
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'SELECT codigo_participacion FROM participaciones')) {
                return $stmtPart;
            }
            if (str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'palmares_grupal')) {
                return $stmtDup;
            }
            if (str_contains($sql, 'CALL ProcesarPalmaresGrupal')) {
                return $stmtSP;
            }
            if (str_contains($sql, 'SELECT codigo_grupal FROM palmares_grupal')) {
                return $stmtId;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);
        $mockPremios->expects($this->once())->method('validarTipoPremio')->with(4, 'G');

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'torneo'        => 2,
            'equipo'        => 7,
            'premio'        => 4,
            'tipo_palmares' => 'grupal'
        ]);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals(50, $modelo->getId());
    }

    // Caso 13: Inclusión de palmarés grupal cuando el SP ya ejecutó commit internamente
    #[Group('RF-02')]
    #[Group('ESC-03')]
    #[Group('CP-13')]
    public function testIncluirPalmaresGrupalCommitInternoSP(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtPart = $this->createMock(PDOStatement::class);
        $stmtPart->method('execute')->willReturn(true);
        $stmtPart->method('fetchColumn')->willReturn(18);

        $stmtDup = $this->createMock(PDOStatement::class);
        $stmtDup->method('execute')->willReturn(true);
        $stmtDup->method('fetchColumn')->willReturn(0);

        $stmtSP = $this->createMock(PDOStatement::class);
        $stmtSP->method('execute')->willReturn(true);
        $stmtSP->method('closeCursor')->willReturn(true);

        $stmtId = $this->createMock(PDOStatement::class);
        $stmtId->method('execute')->willReturn(true);
        $stmtId->method('fetchColumn')->willReturn(51);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        // Simula que inTransaction es true pero commit lanza excepción de no active transaction
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->method('commit')->willThrowException(new \PDOException('There is no active transaction'));

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtPart, $stmtDup, $stmtSP, $stmtId
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'participaciones')) {
                return $stmtPart;
            }
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtDup;
            }
            if (str_contains($sql, 'CALL ProcesarPalmaresGrupal')) {
                return $stmtSP;
            }
            return $stmtId;
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'torneo'        => 2,
            'equipo'        => 7,
            'premio'        => 4,
            'tipo_palmares' => 'grupal'
        ]);

        $this->assertEquals('exito', $resultado['accion']);
        $this->assertEquals(51, $modelo->getId());
    }

    // Caso 14: Rechazo de palmarés grupal si el equipo no tiene participación en el torneo
    #[Group('RF-02')]
    #[Group('ESC-03')]
    #[Group('CP-14')]
    public function testIncluirPalmaresGrupalEquipoSinParticipacion(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtPart = $this->createMock(PDOStatement::class);
        $stmtPart->method('execute')->willReturn(true);
        $stmtPart->method('fetchColumn')->willReturn(false); // No tiene participación

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use ($stmtExistePremio, $stmtPart) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            return $stmtPart;
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'torneo'        => 2,
            'equipo'        => 99,
            'premio'        => 4,
            'tipo_palmares' => 'grupal'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El equipo no cuenta con una participación registrada en este torneo.', $resultado['codigo']);
    }

    // Caso 15: Rechazo de palmarés grupal por duplicado en el mismo torneo
    #[Group('RF-02')]
    #[Group('ESC-03')]
    #[Group('CP-15')]
    public function testIncluirPalmaresGrupalDuplicado(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtPart = $this->createMock(PDOStatement::class);
        $stmtPart->method('execute')->willReturn(true);
        $stmtPart->method('fetchColumn')->willReturn(18);

        $stmtDup = $this->createMock(PDOStatement::class);
        $stmtDup->method('execute')->willReturn(true);
        $stmtDup->method('fetchColumn')->willReturn(1); // Duplicado

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtPart, $stmtDup
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'participaciones')) {
                return $stmtPart;
            }
            return $stmtDup;
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'torneo'        => 2,
            'equipo'        => 7,
            'premio'        => 4,
            'tipo_palmares' => 'grupal'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(DUPLICATE, $resultado['codigo']);
    }

    // Caso 16: Error de base de datos con rollback en inclusión grupal
    #[Group('RF-02')]
    #[Group('ESC-03')]
    #[Group('CP-16')]
    public function testIncluirPalmaresGrupalFalloBDConRollback(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtPart = $this->createMock(PDOStatement::class);
        $stmtPart->method('execute')->willReturn(true);
        $stmtPart->method('fetchColumn')->willReturn(18);

        $stmtDup = $this->createMock(PDOStatement::class);
        $stmtDup->method('execute')->willReturn(true);
        $stmtDup->method('fetchColumn')->willReturn(0);

        $stmtSP = $this->createMock(PDOStatement::class);
        $stmtSP->method('execute')->willThrowException(new PDOException('Fallo al ejecutar procedimiento almacenado'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtPart, $stmtDup, $stmtSP
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'participaciones')) {
                return $stmtPart;
            }
            if (str_contains($sql, 'SELECT COUNT(*)')) {
                return $stmtDup;
            }
            return $stmtSP;
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'torneo'        => 2,
            'equipo'        => 7,
            'premio'        => 4,
            'tipo_palmares' => 'grupal'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Fallo al ejecutar procedimiento almacenado', $resultado['codigo']);
    }
}
