<?php

namespace tests\Unit\Recuperacion;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRecuperacion;
use Exception;
use PHPUnit\Framework\Attributes\Group;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('recuperacion')]
#[Group('reenviarCodigoRecuperacion')]
class ReenviarCodigoRecuperacionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    private function crearModeloConSimulador(bool $fallarEnvio = false, bool $lanzarExcepcion = false): ModeloRecuperacion
    {
        return new class($fallarEnvio, $lanzarExcepcion) extends ModeloRecuperacion {
            private bool $fallar;
            private bool $lanzar;

            public function __construct(bool $fallar, bool $lanzar)
            {
                parent::__construct();
                $this->fallar = $fallar;
                $this->lanzar = $lanzar;
            }

            protected function enviarCorreo($mensaje): array
            {
                if ($this->lanzar) {
                    throw new Exception('Fallo inesperado en servicio de correo');
                }

                if ($this->fallar) {
                    return ['accion' => 'error', 'mensaje' => 'No se pudo enviar el correo. Error: Conexión rechazada'];
                }

                $nuevoCodigo = '789012';
                $_SESSION['codigo_verificacion'] = $nuevoCodigo;
                $_SESSION['codigo_timestamp'] = time();
                $_SESSION['codigo_intentos'] = 0;

                return ['accion' => 'comprobar', 'mensaje' => $mensaje];
            }
        };
    }

    /**
     * Caso 1: Reenvío de código exitoso actualiza el código en sesión y resetea intentos.
     */
    public function testReenviarCodigoExitoso(): void
    {
        $_SESSION['correo'] = 'atleta@cannibals.com';
        $_SESSION['destinatario'] = 'Atleta Cannibals';
        $_SESSION['codigo_verificacion'] = '111111';
        $_SESSION['codigo_intentos'] = 3;

        $modelo = $this->crearModeloConSimulador(false, false);
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'reenviar'
        ]);

        $this->assertEquals('comprobar', $resultado['accion']);
        $this->assertEquals('Se le envio otro código al correo.', $resultado['mensaje']);
        $this->assertEquals('789012', $_SESSION['codigo_verificacion']);
        $this->assertEquals(0, $_SESSION['codigo_intentos']);
    }

    /**
     * Caso 2: Reenvío invocado directamente mediante el método Reenviar().
     */
    public function testReenviarMetodoDirecto(): void
    {
        $_SESSION['correo'] = 'atleta@cannibals.com';
        $_SESSION['destinatario'] = 'Atleta Cannibals';

        $modelo = $this->crearModeloConSimulador(false, false);
        $resultado = $modelo->Reenviar();

        $this->assertEquals('comprobar', $resultado['accion']);
        $this->assertEquals('Se le envio otro código al correo.', $resultado['mensaje']);
    }

    /**
     * Caso 3: Fallo en el reenvío de correo retorna estructura de error.
     */
    public function testReenviarCodigoFalloEnvioRetornaError(): void
    {
        $_SESSION['correo'] = 'atleta@cannibals.com';
        $_SESSION['destinatario'] = 'Atleta Cannibals';

        $modelo = $this->crearModeloConSimulador(true, false);
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'reenviar'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertStringContainsString('No se pudo enviar el correo', $resultado['mensaje']);
    }

    /**
     * Caso 4: Excepción no controlada durante el reenvío es capturada de forma segura.
     */
    public function testReenviarCodigoExcepcionCapturaError(): void
    {
        $modelo = $this->crearModeloConSimulador(false, true);
        $resultado = $modelo->ProcesarDatos([
            'accion' => 'reenviar'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Fallo inesperado en servicio de correo', $resultado['mensaje']);
    }
}

