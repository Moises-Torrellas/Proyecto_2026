<?php

namespace tests\Unit\Bitacora;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloBitacora;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/BitacoraMockTrait.php';

/**
 * RF-BIT-02: Los módulos registran sus acciones mediante la función registrarBitacora()
 * (app/controlador/Base.php). Solo se registra cuando hay un usuario con sesión iniciada,
 * se identifica al usuario de la sesión y el entorno (navegador) se guarda recortado a
 * 50 caracteres, que es el tamaño de la columna en la base de datos.
 *
 * Escenarios:
 *  E1. Usuario con sesión iniciada realiza una acción.
 *  E2. No hay sesión iniciada.
 *  E3. No se dispone del objeto de bitácora.
 *  E4. Navegador con un identificador muy largo o desconocido.
 */
#[Group('unit')]
#[Group('unitBitacora')]
#[Group('registrarBitacoraControlador')]
class RegistrarBitacoraControladorTest extends TestCase
{
    use BitacoraMockTrait;

    // Caso 1 (E1): Se registra la acción con el usuario de la sesión y los datos enviados
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

    // Caso 2 (E1): Sin datos previos ni nuevos se envían cadenas vacías
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

    // Caso 3 (E2): Sin sesión iniciada no se registra nada
    public function testNoRegistraSinSesion(): void
    {
        $_SESSION = [];

        $bitacora = $this->createMock(ModeloBitacora::class);
        $bitacora->expects($this->never())->method('RegistrarAccion');

        registrarBitacora($bitacora, 103, 'Ingreso al Modulo');
    }

    // Caso 4 (E3): Sin objeto de bitácora no se produce ningún error
    public function testSinObjetoBitacora(): void
    {
        $_SESSION['id'] = 4;

        registrarBitacora(null, 103, 'Ingreso al Modulo');

        $this->assertTrue(true, 'registrarBitacora() no debe fallar si no recibe la bitácora.');
    }

    // Caso 5 (E4): El entorno se recorta a 50 caracteres
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

    // Caso 6 (E4): Si el navegador no se identifica, el entorno es "Desconocido"
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
