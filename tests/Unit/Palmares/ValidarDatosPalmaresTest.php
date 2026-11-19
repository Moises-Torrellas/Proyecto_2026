<?php

namespace tests\Unit\Palmares;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloPalmares;
use App\modelo\ModeloPremios;
use App\modelo\ModeloParticipaciones;
use App\modelo\Conexion;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitPalmares')]
#[Group('RF-01')]
#[Group('ESC-01')]
class ValidarDatosPalmaresTest extends TestCase
{
    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $propiedad = $reflector->getProperty('instancia');
        $propiedad->setValue(null, null);
        parent::tearDown();
    }

    // Caso 01: Rechazo de datos vacíos en ProcesarDatos
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-01')]
    public function testDatosVaciosLanzaExcepcion(): void
    {
        $modelo = new ModeloPalmares();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        $modelo->ProcesarDatos([]);
    }

    // Caso 02: Rechazo de acción no válida o desconocida
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-02')]
    public function testAccionInvalidaLanzaExcepcion(): void
    {
        $modelo = new ModeloPalmares();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('La acción solicitada para el palmarés no es válida.');

        $modelo->ProcesarDatos([
            'accion' => 'accion_inexistente',
            'torneo' => 1,
            'premio' => 2
        ]);
    }

    // Caso 03: Asignación y almacenamiento correcto de propiedades en el modelo
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-03')]
    public function testAsignacionPropiedadesModeloYGetId(): void
    {
        $modelo = new ModeloPalmares();
        $reflector = new ReflectionClass($modelo);

        try {
            $modelo->ProcesarDatos([
                'id'            => 10,
                'torneo'        => 5,
                'atleta'        => 8,
                'equipo'        => 12,
                'premio'        => 3,
                'tipo_palmares' => 'individual',
                'accion'        => 'accion_desconocida'
            ]);
        } catch (Exception $e) {
            // Se espera la excepción de acción inválida, pero las propiedades ya se asignaron
        }

        $this->assertEquals(10, $modelo->getId());
        $this->assertEquals(5, $reflector->getProperty('id_torneo')->getValue($modelo));
        $this->assertEquals(8, $reflector->getProperty('id_atleta')->getValue($modelo));
        $this->assertEquals(12, $reflector->getProperty('id_equipo')->getValue($modelo));
        $this->assertEquals(3, $reflector->getProperty('id_premio')->getValue($modelo));
        $this->assertEquals('individual', $reflector->getProperty('tipo_palmares')->getValue($modelo));
    }

    // Caso 04: Inclusión rechazada cuando el modelo de Premios no está configurado
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-04')]
    public function testInclusionSinModeloPremiosRetornaError(): void
    {
        $modelo = new ModeloPalmares();

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'incluir',
            'torneo'        => 1,
            'premio'        => 2,
            'tipo_palmares' => 'individual',
            'atleta'        => 3
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Modelos requeridos no configurados.', $resultado['codigo']);
    }

    // Caso 05: Modificación rechazada cuando el modelo de Premios no está configurado
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-05')]
    public function testModificacionSinModeloPremiosRetornaError(): void
    {
        $modelo = new ModeloPalmares();

        $resultado = $modelo->ProcesarDatos([
            'accion'        => 'modificar',
            'id'            => 1,
            'torneo'        => 1,
            'premio'        => 2,
            'tipo_palmares' => 'individual',
            'atleta'        => 3
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Modelos requeridos no configurados.', $resultado['codigo']);
    }

    // Caso 06: Inyección de modelos auxiliares mediante setters
    #[Group('RF-01')]
    #[Group('ESC-01')]
    #[Group('CP-06')]
    public function testSettersModelosAuxiliares(): void
    {
        $modelo = new ModeloPalmares();
        $reflector = new ReflectionClass($modelo);

        $mockParticipaciones = $this->createMock(ModeloParticipaciones::class);
        $mockPremios = $this->createMock(ModeloPremios::class);

        $modelo->setModeloParticipaciones($mockParticipaciones);
        $modelo->setModeloPremios($mockPremios);

        $propPart = $reflector->getProperty('modeloParticipaciones')->getValue($modelo);
        $propPrem = $reflector->getProperty('modeloPremios')->getValue($modelo);

        $this->assertSame($mockParticipaciones, $propPart);
        $this->assertSame($mockPremios, $propPrem);
    }
}
