<?php

declare(strict_types=1);

namespace tests\Unit\Estadisticas;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloEstadisticas;
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
    define('DUPLICATE', '010');
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
if (!defined('EMPTY_SELECTION')) {
    define('EMPTY_SELECTION', '009');
}
if (!defined('DB_CONNECTION')) {
    define('DB_CONNECTION', '500');
}

#[Group('unit')]
#[Group('unitEstadisticas')]
#[Group('RF-07')]
#[Group('ESC-07')]
class AuxiliaresEstadisticasTest extends TestCase
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
    // ESCENARIO 07: Consultas Auxiliares y Validaciones de Dependencias
    // ==========================================

    // Caso 33: validarEstadisticaDuplicada retorna verdadero si ya existe registro activo
    #[Group('RF-07')]
    #[Group('ESC-07')]
    #[Group('CP-33')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarVerdadero_cuando_existeEstadisticaDuplicada(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindValue')->willReturn(true);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1); // Cuenta > 0

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')
            ->with($this->stringContains('SELECT COUNT(*) FROM detalles_participacion'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->validarEstadisticaDuplicada(2, 5);

        // Assert
        $this->assertTrue($resultado);
    }

    // Caso 34: validarEstadisticaDuplicada retorna falso si no existe registro
    #[Group('RF-07')]
    #[Group('ESC-07')]
    #[Group('CP-34')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarFalso_cuando_noExisteEstadisticaDuplicada(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindValue')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->validarEstadisticaDuplicada(2, 5);

        // Assert
        $this->assertFalse($resultado);
    }

    // Caso 35: validarEstadisticaDuplicadaPropia retorna verdadero si otro registro coincide
    #[Group('RF-07')]
    #[Group('ESC-07')]
    #[Group('CP-35')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarVerdadero_cuando_existeEstadisticaDuplicadaEnOtroRegistro(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindValue')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(1);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')
            ->with($this->stringContains('codigo_dtll_prtc !='))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->validarEstadisticaDuplicadaPropia(2, 5, 10);

        // Assert
        $this->assertTrue($resultado);
    }

    // Caso 36: validarEstadisticaDuplicadaPropia retorna falso si ningún otro registro coincide
    #[Group('RF-07')]
    #[Group('ESC-07')]
    #[Group('CP-36')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarFalso_cuando_noExisteEstadisticaDuplicadaPropia(): void
    {
        // Arrange
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('bindValue')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchColumn')->willReturn(0);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->validarEstadisticaDuplicadaPropia(2, 5, 10);

        // Assert
        $this->assertFalse($resultado);
    }

    // Caso 37: ConsultarTorneosConParticipacion retorna torneos finalizados con participaciones
    #[Group('RF-07')]
    #[Group('ESC-07')]
    #[Group('CP-37')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarTorneosConParticipacion_cuando_existenTorneosFinalizados(): void
    {
        // Arrange
        $torneos = [
            ['codigo_torneo' => 1, 'nombre' => 'Copa Apertura 2026', 'fecha_inicio' => '2026-02-01'],
            ['codigo_torneo' => 2, 'nombre' => 'Copa Clausura 2026', 'fecha_inicio' => '2026-07-01']
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($torneos);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')
            ->with($this->stringContains('WHERE t.estatus = 3'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->ConsultarTorneosConParticipacion();

        // Assert
        $this->assertEquals('torneos', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);
        $this->assertEquals('Copa Apertura 2026', $resultado['datos'][0]['nombre']);
    }

    // Caso 38: ConsultarTorneosConParticipacion maneja excepción y retorna error
    #[Group('RF-07')]
    #[Group('ESC-07')]
    #[Group('CP-38')]
    #[Group('escenario_error')]
    public function test_deberia_retornarEstructuraError_cuando_fallaConsultaTorneosConParticipacion(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error de conexión a la BD'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->ConsultarTorneosConParticipacion();

        // Assert
        $this->assertEquals('error', $resultado['accion']);
    }

    // Caso 39: ConsultarAtletasPorTorneo retorna lista de atletas asociados a la participación del torneo
    #[Group('RF-07')]
    #[Group('ESC-07')]
    #[Group('CP-39')]
    #[Group('escenario_exito')]
    public function test_deberia_retornarAtletasPorTorneo_cuando_existenParticipantes(): void
    {
        // Arrange
        $atletas = [
            [
                'codigo_atleta'       => 10,
                'p_nombre'            => 'Diego',
                'p_apellidos'         => 'Rojas',
                'categoria'           => 'Sub-18',
                'codigo_participacion'=> 4,
                'documento_identidad' => 'V-25896321'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('bindValue')->with(':torneo', 3, PDO::PARAM_INT)->willReturn(true);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($atletas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')
            ->with($this->stringContains('WHERE p.codigo_torneo = :torneo'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->ConsultarAtletasPorTorneo(3);

        // Assert
        $this->assertEquals('atletas_participacion', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);
        $this->assertEquals('Diego', $resultado['datos'][0]['p_nombre']);
        $this->assertEquals('V-25896321', $resultado['datos'][0]['documento_identidad']);
    }

    // Caso 40: ConsultarAtletasPorTorneo maneja excepción y retorna error
    #[Group('RF-07')]
    #[Group('ESC-07')]
    #[Group('CP-40')]
    #[Group('escenario_error')]
    public function test_deberia_retornarEstructuraError_cuando_fallaConsultaAtletasPorTorneo(): void
    {
        // Arrange
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Fallo al obtener atletas'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloEstadisticas();

        // Act
        $resultado = $modelo->ConsultarAtletasPorTorneo(3);

        // Assert
        $this->assertEquals('error', $resultado['accion']);
    }
}
