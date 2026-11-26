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
#[Group('RF-05')]
#[Group('ESC-07')]
#[Group('ESC-08')]
class ConsultarPalmaresTest extends TestCase
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
    // ESCENARIO 07: Consulta de Palmarés Individual
    // ==========================================

    // Caso 28: Consulta general de palmarés individual con agrupación correcta de premios
    #[Group('RF-05')]
    #[Group('ESC-07')]
    #[Group('CP-28')]
    #[Group('escenario_exito')]
    public function test_deberia_consultarIndividualConAgrupacionCorrecta_cuando_noHayFiltros(): void
    {
        $filas = [
            [
                'id_atleta'        => 1,
                'atleta_nombres'   => 'Carlos',
                'atleta_apellidos' => 'Pérez',
                'atleta_foto'      => 'carlos.jpg',
                'id_individual'    => 10,
                'id_premio'        => 2,
                'nombre_premio'    => 'Mejor Portero',
                'id_torneo'        => 1,
                'nombre_torneo'    => 'Copa Apertura',
                'fecha_torneo'     => '2026-05-10'
            ],
            [
                'id_atleta'        => 1,
                'atleta_nombres'   => 'Carlos',
                'atleta_apellidos' => 'Pérez',
                'atleta_foto'      => 'carlos.jpg',
                'id_individual'    => 11,
                'id_premio'        => 3,
                'nombre_premio'    => 'Jugador Más Valioso',
                'id_torneo'        => 2,
                'nombre_torneo'    => 'Copa Clausura',
                'fecha_torneo'     => '2026-11-15'
            ],
            [
                'id_atleta'        => 2,
                'atleta_nombres'   => 'Andrés',
                'atleta_apellidos' => 'Gómez',
                'atleta_foto'      => 'andres.jpg',
                'id_individual'    => 12,
                'id_premio'        => 1,
                'nombre_premio'    => 'Máximo Goleador',
                'id_torneo'        => 1,
                'nombre_torneo'    => 'Copa Apertura',
                'fecha_torneo'     => '2026-05-10'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->with([])->willReturn(true);
        $stmtMock->expects($this->once())->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM vista_palmares_individual WHERE 1=1'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->ConsultarIndividual();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(2, $resultado['datos']);

        // Primer atleta agrupado (Carlos Pérez con 2 premios)
        $atleta1 = $resultado['datos'][0];
        $this->assertEquals(1, $atleta1['id_atleta']);
        $this->assertEquals('Carlos', $atleta1['atleta_nombres']);
        $this->assertEquals(2, $atleta1['total_premios']);
        $this->assertCount(2, $atleta1['premios']);
        $this->assertEquals('Mejor Portero', $atleta1['premios'][0]['nombre_premio']);
        $this->assertEquals('Jugador Más Valioso', $atleta1['premios'][1]['nombre_premio']);

        // Segundo atleta agrupado (Andrés Gómez con 1 premio)
        $atleta2 = $resultado['datos'][1];
        $this->assertEquals(2, $atleta2['id_atleta']);
        $this->assertEquals(1, $atleta2['total_premios']);
        $this->assertEquals('Máximo Goleador', $atleta2['premios'][0]['nombre_premio']);
    }

    // Caso 29: Consulta de palmarés individual con filtro de búsqueda
    #[Group('RF-05')]
    #[Group('ESC-07')]
    #[Group('CP-29')]
    #[Group('escenario_exito')]
    public function test_deberia_filtrarPalmaresIndividual_cuando_seEnviaCadenaDeFiltro(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                ':f1' => '%Pérez%',
                ':f2' => '%Pérez%',
                ':f3' => '%Pérez%',
                ':f4' => '%Pérez%'
            ])
            ->willReturn(true);
        $stmtMock->expects($this->once())->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('atleta_nombres LIKE :f1 OR'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->ConsultarIndividual(['filtro' => 'Pérez']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 30: Manejo de excepción en consulta individual
    #[Group('RF-05')]
    #[Group('ESC-07')]
    #[Group('CP-30')]
    #[Group('escenario_error')]
    public function test_deberia_capturarExcepcionYRetornarError_cuando_fallaConsultaIndividualSQL(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error de lectura en vista_palmares_individual'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->ConsultarIndividual();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error de lectura en vista_palmares_individual', $resultado['mensaje']);
    }

    // ==========================================
    // ESCENARIO 08: Consulta de Palmarés Grupal
    // ==========================================

    // Caso 31: Consulta general de palmarés grupal con agrupación correcta de premios
    #[Group('RF-05')]
    #[Group('ESC-08')]
    #[Group('CP-31')]
    #[Group('escenario_exito')]
    public function test_deberia_consultarGrupalConAgrupacionCorrecta_cuando_noHayFiltros(): void
    {
        $filas = [
            [
                'id_equipo'      => 5,
                'nombre_equipo'  => 'Leones FC',
                'codigo_grupal'  => 101,
                'id_premio'      => 1,
                'nombre_premio'  => 'Campeón del Torneo',
                'id_torneo'      => 1,
                'nombre_torneo'  => 'Torneo Nacional 2026',
                'fecha_torneo'   => '2026-08-20'
            ],
            [
                'id_equipo'      => 5,
                'nombre_equipo'  => 'Leones FC',
                'codigo_grupal'  => 102,
                'id_premio'      => 4,
                'nombre_premio'  => 'Equipo Fair Play',
                'id_torneo'      => 2,
                'nombre_torneo'  => 'Copa Oro 2026',
                'fecha_torneo'   => '2026-10-10'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('execute')->with([])->willReturn(true);
        $stmtMock->expects($this->once())->method('fetchAll')->willReturn($filas);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT * FROM vista_palmares_grupal WHERE 1=1'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->ConsultarGrupal();

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertCount(1, $resultado['datos']);

        $equipo = $resultado['datos'][0];
        $this->assertEquals(5, $equipo['id_equipo']);
        $this->assertEquals('Leones FC', $equipo['nombre_equipo']);
        $this->assertEquals(2, $equipo['total_premios']);
        $this->assertCount(2, $equipo['premios']);
        $this->assertEquals(101, $equipo['premios'][0]['codigo_grupal']);
        $this->assertEquals('Campeón del Torneo', $equipo['premios'][0]['nombre_premio']);
        $this->assertEquals(102, $equipo['premios'][1]['codigo_grupal']);
        $this->assertEquals('Equipo Fair Play', $equipo['premios'][1]['nombre_premio']);
    }

    // Caso 32: Consulta de palmarés grupal con filtro de búsqueda
    #[Group('RF-05')]
    #[Group('ESC-08')]
    #[Group('CP-32')]
    #[Group('escenario_exito')]
    public function test_deberia_filtrarPalmaresGrupal_cuando_seEnviaCadenaDeFiltro(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                ':f1' => '%Leones%',
                ':f2' => '%Leones%',
                ':f3' => '%Leones%'
            ])
            ->willReturn(true);
        $stmtMock->expects($this->once())->method('fetchAll')->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('nombre_equipo LIKE :f1 OR'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->ConsultarGrupal(['filtro' => 'Leones']);

        $this->assertEquals('consultar', $resultado['accion']);
        $this->assertEmpty($resultado['datos']);
    }

    // Caso 33: Manejo de excepción en consulta grupal
    #[Group('RF-05')]
    #[Group('ESC-08')]
    #[Group('CP-33')]
    #[Group('escenario_error')]
    public function test_deberia_capturarExcepcionYRetornarError_cuando_fallaConsultaGrupalSQL(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Error de lectura en vista_palmares_grupal'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->ConsultarGrupal();

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Error de lectura en vista_palmares_grupal', $resultado['mensaje']);
    }
}
