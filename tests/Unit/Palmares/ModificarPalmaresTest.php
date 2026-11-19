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
#[Group('RF-03')]
#[Group('ESC-04')]
#[Group('ESC-05')]
class ModificarPalmaresTest extends TestCase
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
    // ESCENARIO 04: Modificación de Palmarés Individual
    // ==========================================

    // Caso 17: Modificación exitosa de un palmarés individual
    #[Group('RF-03')]
    #[Group('ESC-04')]
    #[Group('CP-17')]
    public function testModificarPalmaresIndividualExitoso(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtDetalle = $this->createMock(PDOStatement::class);
        $stmtDetalle->method('execute')->willReturn(true);
        $stmtDetalle->method('fetchColumn')->willReturn(25);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtDetalle, $stmtUpdate
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'detalles_participacion')) {
                return $stmtDetalle;
            }
            if (str_contains($sql, 'UPDATE palmares_individual')) {
                return $stmtUpdate;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);
        $mockPremios->expects($this->once())->method('validarTipoPremio')->with(2, 'I');

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'id'            => 10,
            'torneo'        => 3,
            'atleta'        => 7,
            'premio'        => 2,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 18: Rechazo de modificación individual cuando el premio no existe
    #[Group('RF-03')]
    #[Group('ESC-04')]
    #[Group('CP-18')]
    public function testModificarPalmaresIndividualPremioNoExiste(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(0);

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
            'accion'        => 'modificar',
            'id'            => 10,
            'torneo'        => 3,
            'atleta'        => 7,
            'premio'        => 999,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 19: Rechazo de modificación individual si el atleta no tiene participación en el torneo
    #[Group('RF-03')]
    #[Group('ESC-04')]
    #[Group('CP-19')]
    public function testModificarPalmaresIndividualAtletaSinParticipacion(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtDetalle = $this->createMock(PDOStatement::class);
        $stmtDetalle->method('execute')->willReturn(true);
        $stmtDetalle->method('fetchColumn')->willReturn(false); // Sin participación

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtDetalle
        ) {
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
            'accion'        => 'modificar',
            'id'            => 10,
            'torneo'        => 3,
            'atleta'        => 7,
            'premio'        => 2,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El atleta no cuenta con una participación registrada en este torneo.', $resultado['codigo']);
    }

    // Caso 20: Rechazo de modificación individual por validación de tipo de premio fallida
    #[Group('RF-03')]
    #[Group('ESC-04')]
    #[Group('CP-20')]
    public function testModificarPalmaresIndividualTipoPremioInvalido(): void
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
        $mockPremios->method('validarTipoPremio')->willThrowException(new Exception('El premio no corresponde al tipo individual.'));

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'id'            => 10,
            'torneo'        => 3,
            'atleta'        => 7,
            'premio'        => 2,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El premio no corresponde al tipo individual.', $resultado['codigo']);
    }

    // ==========================================
    // ESCENARIO 05: Modificación de Palmarés Grupal
    // ==========================================

    // Caso 21: Modificación exitosa de un palmarés grupal
    #[Group('RF-03')]
    #[Group('ESC-05')]
    #[Group('CP-21')]
    public function testModificarPalmaresGrupalExitoso(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtPart = $this->createMock(PDOStatement::class);
        $stmtPart->method('execute')->willReturn(true);
        $stmtPart->method('fetchColumn')->willReturn(33);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('commit')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtPart, $stmtUpdate
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'participaciones')) {
                return $stmtPart;
            }
            if (str_contains($sql, 'UPDATE palmares_grupal')) {
                return $stmtUpdate;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);
        $mockPremios->expects($this->once())->method('validarTipoPremio')->with(5, 'G');

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'id'            => 15,
            'torneo'        => 4,
            'equipo'        => 8,
            'premio'        => 5,
            'tipo_palmares' => 'grupal'
        ]);

        $this->assertEquals('exito', $resultado['accion']);
    }

    // Caso 22: Rechazo de modificación grupal si el equipo no tiene participación en el torneo
    #[Group('RF-03')]
    #[Group('ESC-05')]
    #[Group('CP-22')]
    public function testModificarPalmaresGrupalEquipoSinParticipacion(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtPart = $this->createMock(PDOStatement::class);
        $stmtPart->method('execute')->willReturn(true);
        $stmtPart->method('fetchColumn')->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtPart
        ) {
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
            'accion'        => 'modificar',
            'id'            => 15,
            'torneo'        => 4,
            'equipo'        => 8,
            'premio'        => 5,
            'tipo_palmares' => 'grupal'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El equipo no cuenta con una participación registrada en este torneo.', $resultado['codigo']);
    }

    // Caso 23: Rechazo de modificación grupal por validación de tipo de premio fallida
    #[Group('RF-03')]
    #[Group('ESC-05')]
    #[Group('CP-23')]
    public function testModificarPalmaresGrupalTipoPremioInvalido(): void
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
        $mockPremios->method('validarTipoPremio')->willThrowException(new Exception('El premio no corresponde al tipo grupal.'));

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'id'            => 15,
            'torneo'        => 4,
            'equipo'        => 8,
            'premio'        => 5,
            'tipo_palmares' => 'grupal'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El premio no corresponde al tipo grupal.', $resultado['codigo']);
    }

    // Caso 24: Error de base de datos durante la modificación con reversión transaccional
    #[Group('RF-03')]
    #[Group('ESC-05')]
    #[Group('CP-24')]
    public function testModificarPalmaresFalloBDConRollback(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtDetalle = $this->createMock(PDOStatement::class);
        $stmtDetalle->method('execute')->willReturn(true);
        $stmtDetalle->method('fetchColumn')->willReturn(25);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Error de deadlock en actualización'));

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())->method('beginTransaction')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);
        $pdoMock->expects($this->once())->method('rollBack')->willReturn(true);

        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtDetalle, $stmtUpdate
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'detalles_participacion')) {
                return $stmtDetalle;
            }
            return $stmtUpdate;
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'id'            => 10,
            'torneo'        => 3,
            'atleta'        => 7,
            'premio'        => 2,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error de deadlock en actualización', $resultado['codigo']);
    }
}
