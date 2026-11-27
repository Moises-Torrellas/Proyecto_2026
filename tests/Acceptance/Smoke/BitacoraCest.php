<?php

declare(strict_types=1);

namespace Tests\Acceptance\Smoke;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Tests\Support\AcceptanceTester;

final class BitacoraCest
{
    private const SECCION           = 'Seguridad';
    private const MODULO_HREF       = 'Bitacora';
    private const MODULO_TITULO     = 'Bitacora';

    private const CEDULA_ADMIN      = '12345678';
    private const ACCION_INGRESO    = 'Ingreso al Modulo';

    private const TITULO            = '#titulo';
    private const RESULTADOS        = '#resultadoconsulta';
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const BUSQUEDA          = '#busqueda';
    private const BTN_REPORTE       = '#generar';
    private const DETALLE_OCULTO    = '.listado_detalle_oculto';

    private const MODAL             = '#modal';
    private const MODAL_TITULO      = '#titulo_modal';
    private const MODAL_CERRAR      = '#cerrar_modal';
    private const MODAL_FORM        = '#f';
    private const BTN_PROCESO       = '#proceso';
    private const FILTRO_MODULO     = '#filtro_modulo';
    private const FILTRO_USUARIO    = '#filtro_usuario';
    private const FECHA_INICIO_VISIBLE = '#fecha_inicio + input[type="text"]';
    private const FECHA_FIN_VISIBLE    = '#fecha_fin + input[type="text"]';

    private const TEXTOS_DE_ERROR = [
        'Fatal error',
        'Parse error',
        'Warning:',
        'Notice:',
        'Deprecated:',
        'Uncaught',
        'Stack trace',
        'Not Found',
        'Acceso denegado',
    ];

    public function _before(AcceptanceTester $I): void
    {
        $I->amOnPage('/');

        if ($I->loadSessionSnapshot('login')) {
            $I->amOnPage('/Principal');
        } else {
            $I->waitForElement('input[name="cedula"]', 5);
            $I->fillField('cedula', self::CEDULA_ADMIN);
            $I->fillField('contraseña', 'Admin456$');
            $I->click('Iniciar Sesión');
            $I->waitForText('Inicio Validado', 10);
            $I->saveSessionSnapshot('login');
        }

        $I->waitForText('Panel de Control', 10);
        $I->waitForElementVisible('.nav_lateral', 5);
        $this->esperarCargaDePagina($I);
    }

    #[DataProvider('modulosProvider')]
    public function ProbarCargaDeModulo(AcceptanceTester $I, Example $example): void
    {
        $href = $example['href'];

        $I->click($this->xpathSeccion(self::SECCION));
        $enlace = sprintf('a[href="%s"]', $href);
        $I->waitForElementVisible($enlace, 5);
        $I->click($enlace);

        $I->waitForText($example['titulo'], 10, self::TITULO);
        $this->esperarCargaDePagina($I);
        $I->seeInCurrentUrl($href);

        $I->seeElement($this->selectorListado());

        $this->verificarSinErrores($I);
    }

    public function ProbarAccesoDirectoYControlesBase(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForText(self::MODULO_TITULO, 10, self::TITULO);
        $I->seeInCurrentUrl(self::MODULO_HREF);

        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::RESULTADOS);

        $primero = self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child';
        $I->seeElement($primero);
        $I->see(self::MODULO_TITULO, $primero . ' .listado_titulo');
        $I->see(self::ACCION_INGRESO, $primero);

        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErrores($I);
    }

    public function ProbarDetalleDeRegistro(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $primero = self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child';
        $detalle = $primero . ' ' . self::DETALLE_OCULTO;

        $I->dontSeeElement($detalle);
        $I->click($primero . ' .listado_item');
        $I->waitForElementVisible($detalle, 10);
        $I->see('Detalles de la Acción', $detalle);
        $I->see(self::CEDULA_ADMIN, $detalle);
        $I->see('Entorno', $detalle);

        $this->verificarSinErrores($I);
    }

    public function ProbarModalDeReporte(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BTN_REPORTE, 10);

        $I->click(self::BTN_REPORTE);
        $I->waitForElementVisible(self::MODAL, 10);
        $I->seeElement(self::MODAL_FORM);
        $I->waitForText('Filtros del Reporte', 10, self::MODAL_TITULO);
        $I->see('Generar Reporte', self::BTN_PROCESO);

        $I->seeElementInDOM('#fecha_inicio');
        $I->seeElementInDOM('#fecha_fin');
        $I->seeElement(self::FECHA_INICIO_VISIBLE);
        $I->seeElement(self::FECHA_FIN_VISIBLE);

        $I->waitForJS(
            "return !!document.getElementById('fecha_inicio')._flatpickr"
            . " && !!document.getElementById('fecha_fin')._flatpickr;",
            5
        );

        $I->waitForJS(
            "return document.querySelectorAll('" . self::FILTRO_MODULO . " option').length > 1"
            . " && document.querySelectorAll('" . self::FILTRO_USUARIO . " option').length > 1;",
            10
        );

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->verificarSinErrores($I);
    }

    public function ProbarBusquedaConResultados(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $I->fillField(self::BUSQUEDA, self::CEDULA_ADMIN);

        $I->waitForJS(
            "var g = document.querySelectorAll('" . self::RESULTADOS . " " . self::CONTENEDOR_GRUPAL . "');"
            . " return g.length > 0 && Array.from(g).every(e => e.textContent.includes('" . self::CEDULA_ADMIN . "'));",
            10
        );

        $this->verificarSinErrores($I);
    }

    public function ProbarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $I->fillField(self::BUSQUEDA, 'bitacora_inexistente_smoke_xyz_999');
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);

        $this->verificarSinErrores($I);
    }

    protected function modulosProvider(): array
    {
        return [
            'Bitacora' => ['href' => self::MODULO_HREF, 'titulo' => self::MODULO_TITULO],
        ];
    }

    private function selectorListado(): string
    {
        return self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', '
            . self::RESULTADOS . ' .listado_item, '
            . self::RESULTADOS . ' .listado_vacio';
    }

    private function esperarCargaDePagina(AcceptanceTester $I): void
    {
        $I->waitForElementNotVisible('#loader', 30);
        $this->desactivarAnimaciones($I);
    }

    private function desactivarAnimaciones(AcceptanceTester $I): void
    {
        $I->executeJS(
            "if (!document.getElementById('smoke_sin_animaciones')) {"
            . " var s = document.createElement('style'); s.id = 'smoke_sin_animaciones';"
            . " s.textContent = '*, *::before, *::after { transition: none !important; animation: none !important; }';"
            . " document.head.appendChild(s); }"
            . " if (window.jQuery) { jQuery.fx.off = true; }"
        );
    }

    private function xpathSeccion(string $nombre): string
    {
        return sprintf(
            '//li[contains(@class, "nav_identificador")][.//span[contains(@class, "identificador_texto") and contains(normalize-space(.), "%s")]]',
            $nombre
        );
    }

    private function verificarSinErrores(AcceptanceTester $I): void
    {
        foreach (self::TEXTOS_DE_ERROR as $texto) {
            $I->dontSee($texto);
        }
    }
}
