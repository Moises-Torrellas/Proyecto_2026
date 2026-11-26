<?php

declare(strict_types=1);

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
    $configPath = dirname(__DIR__, 3) . '/config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
}

if (!defined('DUPLICATE')) {
    define('DUPLICATE', '004');
}
if (!defined('INVALID_ID')) {
    define('INVALID_ID', '003');
}
if (!defined('ASSOCIATES')) {
    define('ASSOCIATES', '006');
}
if (!defined('VALIDATION')) {
    define('VALIDATION', '007');
}
if (!defined('DB_CONNECTION')) {
    define('DB_CONNECTION', '500');
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

    private function configurarPdoConTransaccion(): PDO
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('beginTransaction')->willReturn(true);
        $pdoMock->method('commit')->willReturn(true);
        $pdoMock->method('rollBack')->willReturn(true);
        $pdoMock->method('inTransaction')->willReturn(true);

        return $pdoMock;
    }

    // ==========================================
    // ESCENARIO 04: Modificación de Palmarés Individual
    // ==========================================

    // Caso 17: Modificación exitosa de un palmarés individual
    #[Group('RF-03')]
    #[Group('ESC-04')]
    #[Group('CP-17')]
    #[Group('escenario_exito')]
    public function test_deberia_modificarPalmaresIndividualExitosamente_cuando_datosSonValidos(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtExistePalmares = $this->createMock(PDOStatement::class);
        $stmtExistePalmares->method('execute')->willReturn(true);
        $stmtExistePalmares->method('fetchColumn')->willReturn(1);

        $stmtDetalle = $this->createMock(PDOStatement::class);
        $stmtDetalle->method('execute')->willReturn(true);
        $stmtDetalle->method('fetchColumn')->willReturn(25);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtExistePalmares, $stmtDetalle, $stmtUpdate
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'palmares_individual') && str_contains($sql, 'COUNT(*)')) {
                return $stmtExistePalmares;
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
    #[Group('escenario_error')]
    public function test_deberia_rechazarModificacionIndividual_cuando_premioNoExiste(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->configurarPdoConTransaccion();
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

    // Caso 19: Rechazo de modificación individual cuando el registro de palmarés individual no existe
    #[Group('RF-03')]
    #[Group('ESC-04')]
    #[Group('CP-19')]
    #[Group('escenario_error')]
    public function test_deberia_rechazarModificacionIndividual_cuando_palmaresNoExiste(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtExistePalmares = $this->createMock(PDOStatement::class);
        $stmtExistePalmares->method('execute')->willReturn(true);
        $stmtExistePalmares->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtExistePalmares
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'palmares_individual') && str_contains($sql, 'COUNT(*)')) {
                return $stmtExistePalmares;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'id'            => 999,
            'torneo'        => 3,
            'atleta'        => 7,
            'premio'        => 2,
            'tipo_palmares' => 'individual'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 20: Rechazo de modificación individual si el atleta no tiene participación en el torneo
    #[Group('RF-03')]
    #[Group('ESC-04')]
    #[Group('CP-20')]
    #[Group('escenario_error')]
    public function test_deberia_rechazarModificacionIndividual_cuando_atletaNoTieneParticipacion(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtExistePalmares = $this->createMock(PDOStatement::class);
        $stmtExistePalmares->method('execute')->willReturn(true);
        $stmtExistePalmares->method('fetchColumn')->willReturn(1);

        $stmtDetalle = $this->createMock(PDOStatement::class);
        $stmtDetalle->method('execute')->willReturn(true);
        $stmtDetalle->method('fetchColumn')->willReturn(false);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtExistePalmares, $stmtDetalle
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'palmares_individual') && str_contains($sql, 'COUNT(*)')) {
                return $stmtExistePalmares;
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

    // Caso 21: Rechazo de modificación individual por validación de tipo de premio fallida
    #[Group('RF-03')]
    #[Group('ESC-04')]
    #[Group('CP-21')]
    #[Group('escenario_error')]
    public function test_deberia_rechazarModificacionIndividual_cuando_tipoPremioEsInvalido(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtExistePalmares = $this->createMock(PDOStatement::class);
        $stmtExistePalmares->method('execute')->willReturn(true);
        $stmtExistePalmares->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtExistePalmares
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'palmares_individual') && str_contains($sql, 'COUNT(*)')) {
                return $stmtExistePalmares;
            }
            return $this->createMock(PDOStatement::class);
        });

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

    // Caso 22: Modificación exitosa de un palmarés grupal
    #[Group('RF-03')]
    #[Group('ESC-05')]
    #[Group('CP-22')]
    #[Group('escenario_exito')]
    public function test_deberia_modificarPalmaresGrupalExitosamente_cuando_datosSonValidos(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtExistePalmares = $this->createMock(PDOStatement::class);
        $stmtExistePalmares->method('execute')->willReturn(true);
        $stmtExistePalmares->method('fetchColumn')->willReturn(1);

        $stmtPart = $this->createMock(PDOStatement::class);
        $stmtPart->method('execute')->willReturn(true);
        $stmtPart->method('fetchColumn')->willReturn(33);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtExistePalmares, $stmtPart, $stmtUpdate
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'palmares_grupal') && str_contains($sql, 'COUNT(*)')) {
                return $stmtExistePalmares;
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

    // Caso 23: Rechazo de modificación grupal cuando el registro de palmarés grupal no existe
    #[Group('RF-03')]
    #[Group('ESC-05')]
    #[Group('CP-23')]
    #[Group('escenario_error')]
    public function test_deberia_rechazarModificacionGrupal_cuando_palmaresNoExiste(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtExistePalmares = $this->createMock(PDOStatement::class);
        $stmtExistePalmares->method('execute')->willReturn(true);
        $stmtExistePalmares->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtExistePalmares
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'palmares_grupal') && str_contains($sql, 'COUNT(*)')) {
                return $stmtExistePalmares;
            }
            return $this->createMock(PDOStatement::class);
        });

        $this->inyectarPdoMock($pdoMock);

        $mockPremios = $this->createMock(ModeloPremios::class);

        $modelo = new ModeloPalmares();
        $modelo->setModeloPremios($mockPremios);

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'id'            => 999,
            'torneo'        => 4,
            'equipo'        => 8,
            'premio'        => 5,
            'tipo_palmares' => 'grupal'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 24: Rechazo de modificación grupal si el equipo no tiene participación en el torneo
    #[Group('RF-03')]
    #[Group('ESC-05')]
    #[Group('CP-24')]
    #[Group('escenario_error')]
    public function test_deberia_rechazarModificacionGrupal_cuando_equipoNoTieneParticipacion(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtExistePalmares = $this->createMock(PDOStatement::class);
        $stmtExistePalmares->method('execute')->willReturn(true);
        $stmtExistePalmares->method('fetchColumn')->willReturn(1);

        $stmtPart = $this->createMock(PDOStatement::class);
        $stmtPart->method('execute')->willReturn(true);
        $stmtPart->method('fetchColumn')->willReturn(false);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtExistePalmares, $stmtPart
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'palmares_grupal') && str_contains($sql, 'COUNT(*)')) {
                return $stmtExistePalmares;
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

    // Caso 25: Rechazo de modificación grupal por validación de tipo de premio fallida
    #[Group('RF-03')]
    #[Group('ESC-05')]
    #[Group('CP-25')]
    #[Group('escenario_error')]
    public function test_deberia_rechazarModificacionGrupal_cuando_tipoPremioEsInvalido(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtExistePalmares = $this->createMock(PDOStatement::class);
        $stmtExistePalmares->method('execute')->willReturn(true);
        $stmtExistePalmares->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtExistePalmares
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'palmares_grupal') && str_contains($sql, 'COUNT(*)')) {
                return $stmtExistePalmares;
            }
            return $this->createMock(PDOStatement::class);
        });

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

    // Caso 26: Error de base de datos durante la modificación con reversión transaccional
    #[Group('RF-03')]
    #[Group('ESC-05')]
    #[Group('CP-26')]
    #[Group('escenario_error')]
    public function test_deberia_revertirTransaccionYRetornarError_cuando_fallaActualizacionSQL(): void
    {
        $stmtExistePremio = $this->createMock(PDOStatement::class);
        $stmtExistePremio->method('execute')->willReturn(true);
        $stmtExistePremio->method('fetchColumn')->willReturn(1);

        $stmtExistePalmares = $this->createMock(PDOStatement::class);
        $stmtExistePalmares->method('execute')->willReturn(true);
        $stmtExistePalmares->method('fetchColumn')->willReturn(1);

        $stmtDetalle = $this->createMock(PDOStatement::class);
        $stmtDetalle->method('execute')->willReturn(true);
        $stmtDetalle->method('fetchColumn')->willReturn(25);

        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willThrowException(new PDOException('Error de deadlock en actualización'));

        $pdoMock = $this->configurarPdoConTransaccion();
        $pdoMock->method('prepare')->willReturnCallback(function (string $sql) use (
            $stmtExistePremio, $stmtExistePalmares, $stmtDetalle, $stmtUpdate
        ) {
            if (str_contains($sql, 'premios')) {
                return $stmtExistePremio;
            }
            if (str_contains($sql, 'palmares_individual') && str_contains($sql, 'COUNT(*)')) {
                return $stmtExistePalmares;
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
