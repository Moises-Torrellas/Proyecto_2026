<?php

declare(strict_types=1);

namespace Tests\Acceptance\Smoke;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Tests\Support\AcceptanceTester;

final class RolesCest
{
    private const SECCION           = 'Seguridad';
    private const MODULO_HREF       = 'Roles';
    private const MODULO_TITULO     = 'Roles';

    private const ROL_EXISTENTE     = 'Entrenador';

    private const TITULO            = '#titulo';
    private const RESULTADOS        = '#resultadoconsulta';
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const BUSQUEDA          = '#busqueda';
    private const BTN_NUEVO         = '#incluir';
    private const BTN_REPORTE       = '#generar';
    private const BTN_PERMISOS      = '#resultadoconsulta .cbt_m';

    private const MODAL             = '#modal';
    private const MODAL_TITULO      = '#titulo_modal';
    private const MODAL_CERRAR      = '#cerrar_modal';
    private const MODAL_FORM        = '#f';
    private const BTN_PROCESO       = '#proceso';
    private const FILA_NOMBRE       = '#row_nombre';
    private const FILA_PERMISOS     = '#row_permisos';
    private const TAB_ASIGNADOS     = '.permisos_tab[data-tab="asignados"]';
    private const TAB_NO_ASIGNADOS  = '.permisos_tab[data-tab="no_asignados"]';

    private const CAMPOS_REGISTRO = ['#nombre', '#descripcion'];

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
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::RESULTADOS);

        $I->seeElement($this->selectorListado());
        $I->see(self::ROL_EXISTENTE, self::RESULTADOS);

        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

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

        $I->waitForText('Registrar Rol', 5, self::MODAL_TITULO);
        $I->see('Registrar Rol', self::BTN_PROCESO);

        foreach (self::CAMPOS_REGISTRO as $campo) {
            $I->seeElement($campo);
        }

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->verificarSinErrores($I);
    }

    public function ProbarModalDeReporte(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BTN_REPORTE, 10);

        $I->click(self::BTN_REPORTE);
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Generar Reporte', 5, self::MODAL_TITULO);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->verificarSinErrores($I);
    }

    public function ProbarModalDePermisos(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BTN_PERMISOS, 10);

        $I->click(self::BTN_PERMISOS);
        $I->waitForElementVisible(self::MODAL, 10);
        $I->waitForText('Permisos del Rol', 10, self::MODAL_TITULO);
        $I->see('Guardar Permisos', self::BTN_PROCESO);

        $I->seeElement(self::FILA_PERMISOS);
        $I->dontSeeElement(self::FILA_NOMBRE);
        $I->seeElement(self::TAB_ASIGNADOS);
        $I->seeElement(self::TAB_NO_ASIGNADOS);

        $I->waitForJS(
            "return document.querySelectorAll('#tabla_permisos_asignados *, #tabla_permisos_no_asignados *').length > 0;",
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

        $I->fillField(self::BUSQUEDA, self::ROL_EXISTENTE);
        $I->waitForText(self::ROL_EXISTENTE, 10, self::RESULTADOS);
        $I->seeElement(self::RESULTADOS . ' .listado_item');

        $this->verificarSinErrores($I);
    }

    public function ProbarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $I->fillField(self::BUSQUEDA, 'rol_inexistente_smoke_xyz_999');
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);

        $this->verificarSinErrores($I);
    }

    protected function modulosProvider(): array
    {
        return [
            'Roles' => ['href' => self::MODULO_HREF, 'titulo' => self::MODULO_TITULO],
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
