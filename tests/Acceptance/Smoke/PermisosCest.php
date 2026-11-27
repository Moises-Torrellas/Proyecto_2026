<?php

declare(strict_types=1);

namespace Tests\Acceptance\Smoke;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Tests\Support\AcceptanceTester;

final class PermisosCest
{
    private const SECCION           = 'Seguridad';
    private const MODULO_HREF       = 'Permisos';
    private const MODULO_TITULO     = 'Permisos';

    private const PERMISO_CLAVE     = 'ingresar_usuarios';
    private const PERMISO_NOMBRE    = 'Ingresar A Usuarios';
    private const PERMISO_MODULO    = 'Usuarios';

    private const TITULO            = '#titulo';
    private const RESULTADOS        = '#resultadoconsulta';
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const BUSQUEDA          = '#busqueda';
    private const BTN_NUEVO         = '#incluir';
    private const DETALLE_OCULTO    = '.listado_detalle_oculto';
    private const BTN_MODIFICAR     = '#resultadoconsulta .listado_detalle_oculto .cbt_v';

    private const MODAL             = '#modal';
    private const MODAL_TITULO      = '#titulo_modal';
    private const MODAL_CERRAR      = '#cerrar_modal';
    private const MODAL_FORM        = '#f';
    private const BTN_PROCESO       = '#proceso';
    private const SELECT_MODULO     = '#modulo';

    private const CAMPOS_REGISTRO = ['#nombre', '#descripcion', '#clave'];

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
            $I->fillField('cedula', '12345678');
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
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::RESULTADOS);

        $I->seeElement($this->selectorListado());
        $I->seeElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL);

        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErrores($I);
    }

    public function ProbarDetalleDeModulo(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $this->buscarPermisoExistente($I);

        $item = self::RESULTADOS . ' .listado_item';
        $detalle = self::RESULTADOS . ' ' . self::DETALLE_OCULTO;

        $I->dontSeeElement($detalle);
        $I->click($item);
        $I->waitForElementVisible($detalle, 10);
        $I->see(self::PERMISO_NOMBRE, $detalle);

        $this->verificarSinErrores($I);
    }

    public function ProbarModalDeRegistro(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BTN_NUEVO, 10);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);
        $I->seeElement(self::MODAL_FORM);

        $I->waitForText('Registrar Permiso', 5, self::MODAL_TITULO);
        $I->see('Registrar Permiso', self::BTN_PROCESO);

        foreach (self::CAMPOS_REGISTRO as $campo) {
            $I->seeElement($campo);
        }

        $I->waitForJS(
            "return document.querySelectorAll('" . self::SELECT_MODULO . " option').length > 0;",
            10
        );

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->verificarSinErrores($I);
    }

    public function ProbarModalDeModificacion(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $this->buscarPermisoExistente($I);
        $I->click(self::RESULTADOS . ' .listado_item');
        $I->waitForElementVisible(self::BTN_MODIFICAR, 10);

        $I->click(self::BTN_MODIFICAR);
        $I->waitForElementVisible(self::MODAL, 10);
        $I->seeElement(self::MODAL_FORM);
        $I->waitForText('Modificar Permiso', 10, self::MODAL_TITULO);
        $I->see('Modificar Permiso', self::BTN_PROCESO);

        $I->seeInField('#nombre', self::PERMISO_NOMBRE);
        $I->seeElement('#descripcion');

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->verificarSinErrores($I);
    }

    public function ProbarBusquedaConResultados(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $this->buscarPermisoExistente($I);
        $I->see(self::PERMISO_MODULO, self::RESULTADOS . ' .listado_titulo');

        $this->verificarSinErrores($I);
    }

    public function ProbarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $I->fillField(self::BUSQUEDA, 'permiso_inexistente_smoke_xyz_999');
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);

        $this->verificarSinErrores($I);
    }

    protected function modulosProvider(): array
    {
        return [
            'Permisos' => ['href' => self::MODULO_HREF, 'titulo' => self::MODULO_TITULO],
        ];
    }

    private function buscarPermisoExistente(AcceptanceTester $I): void
    {
        $I->fillField(self::BUSQUEDA, self::PERMISO_CLAVE);
        $I->waitForJS(
            "var g = document.querySelectorAll('" . self::RESULTADOS . " " . self::CONTENEDOR_GRUPAL . "');"
            . " return g.length === 1 && g[0].textContent.includes('" . self::PERMISO_NOMBRE . "');",
            10
        );
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
