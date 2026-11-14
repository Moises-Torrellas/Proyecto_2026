<?php

namespace tests\Unit\Recuperacion;

use PHPUnit\Framework\TestCase;
use App\modelo\ModeloRecuperacion;
use PHPUnit\Framework\Attributes\Group;

if (!defined('EMPTY_SELECTION')) {
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

#[Group('unit')]
#[Group('recuperacion')]
#[Group('comprobarCodigoRecuperacion')]
class ComprobarCodigoRecuperacionTest extends TestCase
{
    private ModeloRecuperacion $modelo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->modelo = new ModeloRecuperacion();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * Caso 1: Código correcto dentro del tiempo límite valida al usuario y activa bandera de verificación.
     */
    public function testComprobarCodigoCorrectoValidaUsuarioExitosamente(): void
    {
        $_SESSION['codigo_verificacion'] = '849201';
        $_SESSION['codigo_timestamp'] = time();
        $_SESSION['codigo_intentos'] = 0;

        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'comprobarCodigo',
            'codigo' => '849201'
        ]);

        $this->assertEquals('comprobarCodigo', $resultado['accion']);
        $this->assertEquals('Código Validado.', $resultado['mensaje']);
        $this->assertTrue($_SESSION['verificacion'], 'La bandera de verificación debe quedar en true.');
        $this->assertArrayNotHasKey('codigo_verificacion', $_SESSION, 'El código debe eliminarse tras su validación.');
        $this->assertArrayNotHasKey('codigo_timestamp', $_SESSION);
        $this->assertArrayNotHasKey('codigo_intentos', $_SESSION);
    }

    /**
     * Caso 2: Código incorrecto incrementa el contador de intentos y retorna error.
     */
    public function testComprobarCodigoIncorrectoIncrementaIntentos(): void
    {
        $_SESSION['codigo_verificacion'] = '849201';
        $_SESSION['codigo_timestamp'] = time();
        $_SESSION['codigo_intentos'] = 1;

        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'comprobarCodigo',
            'codigo' => '000000'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El código no es correcto.', $resultado['mensaje']);
        $this->assertEquals(2, $_SESSION['codigo_intentos'], 'Los intentos fallidos deben incrementarse.');
        $this->assertArrayNotHasKey('verificacion', $_SESSION);
    }

    /**
     * Caso 3: Código expirado tras superar los 30 minutos (1800 segundos).
     */
    public function testComprobarCodigoExpiradoMayorA30Minutos(): void
    {
        $_SESSION['codigo_verificacion'] = '849201';
        $_SESSION['codigo_timestamp'] = time() - 1805; // 30 minutos y 5 segundos atrás
        $_SESSION['codigo_intentos'] = 0;

        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'comprobarCodigo',
            'codigo' => '849201'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El código ha expirado. Solicita uno nuevo.', $resultado['mensaje']);
        $this->assertArrayNotHasKey('codigo_verificacion', $_SESSION);
        $this->assertArrayNotHasKey('codigo_timestamp', $_SESSION);
        $this->assertArrayNotHasKey('codigo_intentos', $_SESSION);
        $this->assertArrayNotHasKey('verificacion', $_SESSION);
    }

    /**
     * Caso 4: Ausencia de timestamp en sesión se evalúa como código expirado.
     */
    public function testComprobarCodigoSinTimestampEnSesionTrataComoExpirado(): void
    {
        $_SESSION['codigo_verificacion'] = '849201';
        // Sin definir $_SESSION['codigo_timestamp']

        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'comprobarCodigo',
            'codigo' => '849201'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('El código ha expirado. Solicita uno nuevo.', $resultado['mensaje']);
    }

    /**
     * Caso 5: Exceder el límite de 5 intentos bloquea el código e invalida la sesión.
     */
    public function testComprobarCodigoLimiteDeCincoIntentosSuperado(): void
    {
        $_SESSION['codigo_verificacion'] = '849201';
        $_SESSION['codigo_timestamp'] = time();
        $_SESSION['codigo_intentos'] = 5; // El próximo intento será el número 6

        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'comprobarCodigo',
            'codigo' => '111111'
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Demasiados intentos. Solicita un nuevo código.', $resultado['mensaje']);
        $this->assertArrayNotHasKey('codigo_verificacion', $_SESSION);
        $this->assertArrayNotHasKey('codigo_timestamp', $_SESSION);
        $this->assertArrayNotHasKey('codigo_intentos', $_SESSION);
        $this->assertArrayNotHasKey('verificacion', $_SESSION);
    }

    /**
     * Caso 6: Si los intentos se superaron, incluso el código correcto es rechazado por seguridad.
     */
    public function testComprobarCodigoCorrectoPeroConLimiteDeIntentosExcedidoEsRechazado(): void
    {
        $_SESSION['codigo_verificacion'] = '849201';
        $_SESSION['codigo_timestamp'] = time();
        $_SESSION['codigo_intentos'] = 5;

        $resultado = $this->modelo->ProcesarDatos([
            'accion' => 'comprobarCodigo',
            'codigo' => '849201' // Código correcto pero ya en el 6to intento
        ]);

        $this->assertEquals('error', $resultado['accion']);
        $this->assertEquals('Demasiados intentos. Solicita un nuevo código.', $resultado['mensaje']);
        $this->assertArrayNotHasKey('verificacion', $_SESSION);
    }
}

