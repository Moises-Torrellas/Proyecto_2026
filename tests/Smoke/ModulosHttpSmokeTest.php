<?php

namespace Tests\Smoke;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('smoke')]
#[Group('smokeHttp')]
class ModulosHttpSmokeTest extends TestCase
{
    private string $baseUrl;
    private static ?string $archivoCookie = null;

    private const TEXTOS_DE_ERROR = [
        'Fatal error',
        'Parse error',
        'Warning:',
        'Notice:',
        'Deprecated:',
        'Uncaught Exception',
        'Uncaught Error',
        'Stack trace',
        'SQLSTATE',
        'No se pudo conectar a la base de datos'
    ];

    public static function setUpBeforeClass(): void
    {
        self::$archivoCookie = sys_get_temp_dir() . '/smoke_cookie_' . uniqid() . '.txt';
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$archivoCookie && file_exists(self::$archivoCookie)) {
            @unlink(self::$archivoCookie);
        }
    }

    protected function setUp(): void
    {
        $this->baseUrl = getenv('APP_URL') ?: 'http://localhost/Proyecto_2026/public';

        if (!$this->verificarServidorDisponible()) {
            $this->markTestSkipped("El servidor web local en [{$this->baseUrl}] no está accesible o no se tienen permisos de red.");
        }
    }

    private function verificarServidorDisponible(): bool
    {
        $ch = @curl_init($this->baseUrl . '/Inicio');
        if (!$ch) {
            return false;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SmokeTest/1.0 (PHPUnit)');
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $resultado = @curl_exec($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($resultado !== false && $codigoHttp > 0);
    }

    private function ejecutarPeticion(string $ruta, string $metodo = 'GET', array $datos = [], array $cabeceras = []): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($ruta, '/');
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SmokeTest/1.0 (PHPUnit)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (self::$archivoCookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, self::$archivoCookie);
            curl_setopt($ch, CURLOPT_COOKIEFILE, self::$archivoCookie);
        }

        if ($metodo === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datos));
        }

        if (!empty($cabeceras)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $cabeceras);
        }

        $cuerpo = curl_exec($ch);
        $codigoHttp = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $urlRedireccion = (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL);

        curl_close($ch);

        return [
            'codigo'      => $codigoHttp,
            'cuerpo'      => (string)$cuerpo,
            'redireccion' => $urlRedireccion
        ];
    }

    // Caso 1: La ruta pública de Inicio responde con código 200 y sin errores PHP
    public function testRutaInicioRespondeHttp200YSinErrores(): void
    {
        $respuesta = $this->ejecutarPeticion('Inicio');

        $this->assertEquals(200, $respuesta['codigo'], 'La página de Inicio debe responder con código HTTP 200.');
        $this->assertNotEmpty($respuesta['cuerpo'], 'La página de Inicio debe retornar contenido HTML.');

        foreach (self::TEXTOS_DE_ERROR as $error) {
            $this->assertStringNotContainsString(
                $error,
                $respuesta['cuerpo'],
                "La respuesta no debe contener mensajes de error de PHP [{$error}]."
            );
        }
    }

    // Caso 2: La ruta pública de Recuperación responde con código 200 y sin errores PHP
    public function testRutaRecuperacionRespondeHttp200YSinErrores(): void
    {
        $respuesta = $this->ejecutarPeticion('Recuperacion');

        $this->assertEquals(200, $respuesta['codigo'], 'La página de Recuperación debe responder con código HTTP 200.');
        $this->assertNotEmpty($respuesta['cuerpo']);

        foreach (self::TEXTOS_DE_ERROR as $error) {
            $this->assertStringNotContainsString(
                $error,
                $respuesta['cuerpo'],
                "La respuesta no debe contener mensajes de error de PHP [{$error}]."
            );
        }
    }

    // Caso 3: Una ruta inexistente maneja el error 404 de forma limpia y sin errores fatales
    public function testRutaInexistenteManeja404SinErroresFatales(): void
    {
        $respuesta = $this->ejecutarPeticion('RutaInexistenteParaSmokeTest');

        $this->assertNotEmpty($respuesta['cuerpo']);
        $this->assertStringNotContainsString('Fatal error', $respuesta['cuerpo']);
        $this->assertStringNotContainsString('Parse error', $respuesta['cuerpo']);
        $this->assertStringNotContainsString('Uncaught', $respuesta['cuerpo']);
    }

    // Caso 4: Intentar acceder a módulos protegidos sin sesión redirige a Inicio (código 302)
    public function testRedireccionDeSeguridadEnModulosProtegidos(): void
    {
        $url = rtrim($this->baseUrl, '/') . '/Atletas';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SmokeTest/1.0 (PHPUnit)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_exec($ch);
        $codigoHttp = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redireccion = (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);

        $this->assertEquals(302, $codigoHttp, 'Acceder a un módulo protegido sin sesión debe producir una redirección HTTP 302.');
        $this->assertStringContainsString('Inicio', $redireccion, 'La redirección de seguridad debe apuntar a la ruta de Inicio.');
    }
}
