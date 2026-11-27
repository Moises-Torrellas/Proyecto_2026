<?php

namespace tests\Unit\Bitacora;

use App\modelo\Conexion;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use App\modelo\ModeloBitacora;
use PHPUnit\Framework\Attributes\Group;

if (!defined('INVALID_ID')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('unitBitacora')]
#[Group('registrarBitacoraControlador')]
class RegistrarBitacoraControladorTest extends TestCase
{
    protected array $sentencias = [];

    protected function tearDown(): void
    {
        $reflector = new ReflectionClass(Conexion::class);
        $reflector->getProperty('instanciaSG')->setValue(null, null);
        $reflector->getProperty('instancia')->setValue(null, null);

        $_SESSION = [];
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->sentencias = [];
        parent::tearDown();
    }

    public function testRegistraConSesion(): void
    {
        $_SESSION['id'] = 4;
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0)';

        $bitacora = $this->createMock(ModeloBitacora::class);
        $bitacora->expects($this->once())
            ->method('RegistrarAccion')
            ->with(103, 'Eliminó el Rol: Secretaria', 4, '{"nombre":"Secretaria"}', '', 'Mozilla/5.0 (Windows NT 10.0)');

        registrarBitacora($bitacora, 103, 'Eliminó el Rol: Secretaria', '{"nombre":"Secretaria"}');
    }

    public function testRegistraSoloMensaje(): void
    {
        $_SESSION['id'] = 4;
        $_SERVER['HTTP_USER_AGENT'] = 'Firefox';

        $bitacora = $this->createMock(ModeloBitacora::class);
        $bitacora->expects($this->once())
            ->method('RegistrarAccion')
            ->with(103, 'Ingreso al Modulo', 4, '', '', 'Firefox');

        registrarBitacora($bitacora, 103, 'Ingreso al Modulo');
    }

    public function testNoRegistraSinSesion(): void
    {
        $_SESSION = [];

        $bitacora = $this->createMock(ModeloBitacora::class);
        $bitacora->expects($this->never())->method('RegistrarAccion');

        registrarBitacora($bitacora, 103, 'Ingreso al Modulo');
    }

    public function testSinObjetoBitacora(): void
    {
        $_SESSION['id'] = 4;

        registrarBitacora(null, 103, 'Ingreso al Modulo');

        $this->assertTrue(true, 'registrarBitacora() no debe fallar si no recibe la bitácora.');
    }

    public function testRecortaEntornoA50(): void
    {
        $_SESSION['id'] = 4;
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0';

        $bitacora = $this->createMock(ModeloBitacora::class);
        $bitacora->expects($this->once())
            ->method('RegistrarAccion')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->callback(fn($entorno) => strlen($entorno) === 50
                    && $entorno === substr($_SERVER['HTTP_USER_AGENT'], 0, 50))
            );

        registrarBitacora($bitacora, 103, 'Ingreso al Modulo');
    }

    public function testEntornoDesconocido(): void
    {
        $_SESSION['id'] = 4;
        unset($_SERVER['HTTP_USER_AGENT']);

        $bitacora = $this->createMock(ModeloBitacora::class);
        $bitacora->expects($this->once())
            ->method('RegistrarAccion')
            ->with(103, 'Ingreso al Modulo', 4, '', '', 'Desconocido');

        registrarBitacora($bitacora, 103, 'Ingreso al Modulo');
    }
}
