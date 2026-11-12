<?php

declare(strict_types=1);

namespace Tests\Acceptance\Smoke;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Humo (Smoke Testing) con Codeception
 * Módulo: Torneos (Competencias > Torneos)
 *
 * Valida la disponibilidad crítica, renderizado, navegación e integridad
 * del módulo de Torneos sin ejecutar mutaciones destructivas en la BD.
 */
final class TorneosCest
{
    private const SECCION        = 'Competencias';
    private const MODULO_HREF    = 'Torneos';
    private const MODULO_TITULO  = 'Torneos';

    // Selectores principales de la vista
    private const TITULO         = '#titulo';
    private const RESULTADOS     = '#resultadoconsulta';
    private const BUSQUEDA       = '#busqueda';
    private const BTN_NUEVO      = '#incluir';
    private const BTN_REPORTE    = '#generar';

    // Selectores del modal y formulario
    private const MODAL          = '#modal';
    private const MODAL_TITULO   = '#titulo_modal';
    private const MODAL_CERRAR   = '#cerrar_modal';
    private const MODAL_FORM     = '#f';

    /** Si algo de esto aparece en pantalla, la vista está rota. */
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
        $I->amOnPage('/'); // necesario para que el navegador esté en tu dominio antes de cargar cookies

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
    }

    /**
     * Humo: entrar al módulo Torneos desde el menú y comprobar que responde y se renderiza.
     */
    #[DataProvider('modulosProvider')]
    public function ProbarCargaDeModulo(AcceptanceTester $I, Example $example): void
    {
        $href = $example['href'];

        // Entrar desde el menú
        $I->click($this->xpathSeccion(self::SECCION));
        $enlace = sprintf('a[href="%s"]', $href);
        $I->waitForElementVisible($enlace, 3);
        $I->click($enlace);

        // La vista cargó
        $I->waitForText($example['titulo'], 10, self::TITULO);
        $I->seeInCurrentUrl($href);

        // La consulta a la base de datos respondió: hay registros o mensaje de vacío
        $I->seeElement(self::RESULTADOS . ' .listado_item, ' . self::RESULTADOS . ' .listado_vacio');

        // Sin errores de PHP
        $this->verificarSinErrores($I);
    }

    /**
     * Humo: Acceso directo por URL y verificación de controles estructurales esenciales.
     */
    public function ProbarAccesoDirectoYControlesBase(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $I->waitForText(self::MODULO_TITULO, 10, self::TITULO);
        $I->seeInCurrentUrl(self::MODULO_HREF);

        // Controles esenciales de la interfaz
        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::RESULTADOS);

        // El listado respondió con registros o estado vacío
        $I->seeElement(self::RESULTADOS . ' .listado_item, ' . self::RESULTADOS . ' .listado_vacio');

        // El modal existe en el DOM pero debe permanecer oculto
        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: El modal de registro abre, muestra título y formulario, y se cierra limpiamente sin persistir datos.
     */
    public function ProbarModalDeRegistro(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $I->waitForElementVisible(self::BTN_NUEVO, 10);

        // Abrir modal
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);
        $I->seeElement(self::MODAL_FORM);

        // Título del modal cargado dinámicamente por JS
        $I->waitForJS(
            "return document.querySelector('" . self::MODAL_TITULO . "').textContent.trim().length > 0;",
            5
        );

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: La búsqueda rápida vía AJAX responde adecuadamente ante cadenas sin coincidencia.
     */
    public function ProbarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $I->fillField(self::BUSQUEDA, 'torneo_inexistente_smoke_xyz_999');
        $I->waitForText('No se encontraron torneos registrados', 10, self::RESULTADOS);

        $this->verificarSinErrores($I);
    }

    protected function modulosProvider(): array
    {
        return [
            'Torneos' => ['href' => self::MODULO_HREF, 'titulo' => self::MODULO_TITULO],
        ];
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

