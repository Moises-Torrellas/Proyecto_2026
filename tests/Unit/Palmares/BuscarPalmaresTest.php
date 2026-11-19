<?php

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
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitPalmares')]
#[Group('RF-06')]
#[Group('ESC-09')]
class BuscarPalmaresTest extends TestCase
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

    // Caso 34: Búsqueda exitosa de palmarés individual por su identificador
    #[Group('RF-06')]
    #[Group('ESC-09')]
    #[Group('CP-34')]
    public function testBuscarPalmaresIndividualExitoso(): void
    {
        $datosEsperados = [
            [
                'codigo_individual' => 12,
                'id_premio'         => 3,
                'id_atleta'         => 8,
                'id_torneo'         => 2,
                'nombres'           => 'Luis',
                'apellidos'         => 'Navarro',
                'cedula'            => 'V-26123456'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('bindValue')->with(':id', 12, PDO::PARAM_INT)->willReturn(true);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($datosEsperados);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE pi.codigo_individual = :id'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->BuscarIndividual(12);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEquals($datosEsperados, $resultado['datos']);
        $this->assertEquals('Luis', $resultado['datos'][0]['nombres']);
        $this->assertEquals('V-26123456', $resultado['datos'][0]['cedula']);
    }

    // Caso 35: Captura de excepción y retorno de estructura de error en búsqueda individual
    #[Group('RF-06')]
    #[Group('ESC-09')]
    #[Group('CP-35')]
    public function testBuscarPalmaresIndividualFalloCapturaExcepcion(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Fallo al consultar registro individual'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->BuscarIndividual(99);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Fallo al consultar registro individual', $resultado['mensaje']);
    }

    // Caso 36: Búsqueda exitosa de palmarés grupal por su identificador
    #[Group('RF-06')]
    #[Group('ESC-09')]
    #[Group('CP-36')]
    public function testBuscarPalmaresGrupalExitoso(): void
    {
        $datosEsperados = [
            [
                'codigo_grupal' => 20,
                'id_premio'     => 1,
                'id_equipo'     => 4,
                'id_torneo'     => 3,
                'nombre_equipo' => 'Tigres del Norte'
            ]
        ];

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())->method('bindValue')->with(':id', 20, PDO::PARAM_INT)->willReturn(true);
        $stmtMock->expects($this->once())->method('execute')->willReturn(true);
        $stmtMock->expects($this->once())->method('fetchAll')->with(PDO::FETCH_ASSOC)->willReturn($datosEsperados);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE pg.codigo_grupal = :id'))
            ->willReturn($stmtMock);

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->BuscarGrupal(20);

        $this->assertEquals('buscar', $resultado['accion']);
        $this->assertEquals($datosEsperados, $resultado['datos']);
        $this->assertEquals('Tigres del Norte', $resultado['datos'][0]['nombre_equipo']);
    }

    // Caso 37: Captura de excepción y retorno de estructura de error en búsqueda grupal
    #[Group('RF-06')]
    #[Group('ESC-09')]
    #[Group('CP-37')]
    public function testBuscarPalmaresGrupalFalloCapturaExcepcion(): void
    {
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willThrowException(new PDOException('Fallo al consultar registro grupal'));

        $this->inyectarPdoMock($pdoMock);

        $modelo = new ModeloPalmares();
        $resultado = $modelo->BuscarGrupal(88);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('Fallo al consultar registro grupal', $resultado['mensaje']);
    }
}
