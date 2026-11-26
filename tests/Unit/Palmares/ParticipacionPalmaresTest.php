<?php

declare(strict_types=1);

namespace tests\Unit\Palmares;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPalmares;
use App\modelo\Conexion;
use PDO;
use PDOException;
use PDOStatement;
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
#[Group('RF-07')]
#[Group('ESC-10')]
class ParticipacionPalmaresTest extends TestCase
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
    // ESCENARIO 10: Consulta de Participantes del Torneo para Asignación
    // ==========================================

    // Caso 38: Consulta exitosa de atletas participantes en un torneo específico
    #[Group('RF-07')]
    #[Group('ESC-10')]
    #[Group('CP-38')]
    #[Group('escenario_exito')]
    public function test_deberia_obtenerAtletasParticipantesExitosamente_cuando_torneoTieneAtletas(): void
    {
        $filas = [
            [
                'id_atleta'      => 10,
                'nombres'        => 'Gabriel',
                'apellidos'      => 'Mendoza',
                'doc_identidad'  => 'V-28999888'
            ],
            [
                'id_atleta'      => 12,
                'nombres'        => 'Samuel',
                'apellidos'      => 'Rojas',
                'doc_identidad'  => 'R-15444333'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([':torneo' => 5])
            ->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE p.codigo_torneo = :torneo'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'atletas_participacion',
            'torneo' => 5
        ]);

        $this->assertEquals('atletas_participacion', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('Gabriel', $resultado['datos'][0]['nombres']);
        $this->assertEquals('V-28999888', $resultado['datos'][0]['doc_identidad']);
        $this->assertEquals('R-15444333', $resultado['datos'][1]['doc_identidad']);
    }

    // Caso 39: Manejo de excepción en consulta de atletas participantes
    #[Group('RF-07')]
    #[Group('ESC-10')]
    #[Group('CP-39')]
    #[Group('escenario_error')]
    public function test_deberia_capturarExcepcionYRetornarError_cuando_fallaConsultaAtletasSQL(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error al consultar atletas de torneo'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'atletas_participacion',
            'torneo' => 5
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error al consultar atletas de torneo', $resultado['codigo']);
    }

    // Caso 40: Consulta exitosa de equipos participantes en un torneo específico
    #[Group('RF-07')]
    #[Group('ESC-10')]
    #[Group('CP-40')]
    #[Group('escenario_exito')]
    public function test_deberia_obtenerEquiposParticipantesExitosamente_cuando_torneoTieneEquipos(): void
    {
        $filas = [
            [
                'id_equipos' => 3,
                'nombre'     => 'Águilas del Zulia'
            ],
            [
                'id_equipos' => 7,
                'nombre'     => 'Cardenales de Lara'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([':torneo' => 2])
            ->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE p.codigo_torneo = :torneo'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'equipos_participacion',
            'torneo' => 2
        ]);

        $this->assertEquals('equipos_participacion', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('Águilas del Zulia', $resultado['datos'][0]['nombre']);
        $this->assertEquals('Cardenales de Lara', $resultado['datos'][1]['nombre']);
    }

    // Caso 41: Manejo de excepción en consulta de equipos participantes
    #[Group('RF-07')]
    #[Group('ESC-10')]
    #[Group('CP-41')]
    #[Group('escenario_error')]
    public function test_deberia_capturarExcepcionYRetornarError_cuando_fallaConsultaEquiposSQL(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error al consultar equipos de torneo'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'equipos_participacion',
            'torneo' => 2
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error al consultar equipos de torneo', $resultado['codigo']);
    }
}
