<?php

declare(strict_types=1);

namespace Tests\Acceptance\Smoke;

use Codeception\Attribute\DataProvider;
use Codeception\Attribute\Group;
use Codeception\Example;
use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Humo (Smoke Testing) con Codeception
 * Módulo: Estadísticas (Historial Deportivo > Estadísticas)
 *
 * Valida la disponibilidad crítica, renderizado, navegación e integridad
 * del módulo de Estadísticas sin ejecutar mutaciones destructivas en la BD.
 */
#[Group('smoke')]
#[Group('estadisticas')]
final class EstadisticasCest
{
    private const SECCION           = 'Historial Deportivo';
    private const MODULO_HREF       = 'Estadisticas';
    private const MODULO_TITULO     = 'Estadisticas';

    // Selectores principales de la vista
    private const TITULO            = '#titulo';
    private const RESULTADOS        = '#resultadoconsulta';
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const LISTADO_VACIO     = '.listado_vacio';
    private const BUSQUEDA          = '#busqueda';
    private const BTN_NUEVO         = '#incluir';
    private const BTN_REPORTE       = '#generar';
    private const BTN_AYUDA         = '#ayuda';

    // Selectores del modal y formulario
    private const MODAL             = '#modal';
    private const MODAL_TITULO      = '#titulo_modal';
    private const MODAL_CERRAR      = '#cerrar_modal';
    private const MODAL_FORM        = '#f';
    private const SECCION_METRICAS  = '#seccion_metricas';
    private const BTN_PROCESO       = '#proceso';

    // Selectores de Driver.js
    private const DRIVER_POPOVER    = '#driver-popover-item, .driver-popover';

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
    }

    /**
     * Humo: Entrar al módulo Estadísticas desde el menú y comprobar que responde y se renderiza.
     */
    #[DataProvider('modulosProvider')]
    public function ProbarCargaDeModulo(AcceptanceTester $I, Example $example): void
    {
        $href = $example['href'];

        // Entrar desde el menú lateral
        $I->click($this->xpathSeccion(self::SECCION));
        $enlace = sprintf('a[href="%s"]', $href);
        $I->waitForElementVisible($enlace, 3);
        $I->click($enlace);

        // La vista cargó correctamente
        $I->waitForText($example['titulo'], 10, self::TITULO);
        $I->seeInCurrentUrl($href);

        // La consulta a la base de datos respondió: hay registros o contenedor de lista vacía
        $I->seeElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS . ' ' . self::LISTADO_VACIO);

        // Sin errores de PHP en pantalla
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
        $I->seeElement(self::BTN_AYUDA);
        $I->seeElement(self::RESULTADOS);

        // El listado respondió con registros o estado vacío
        $I->seeElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS . ' ' . self::LISTADO_VACIO);

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
        $I->seeElement(self::SECCION_METRICAS);
        $I->see('Registrar Estadísticas', self::BTN_PROCESO);

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
     * Humo: El modal de reporte abre configurado para generación de reportes y se cierra limpiamente.
     */
    public function ProbarModalDeReporte(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $I->waitForElementVisible(self::BTN_REPORTE, 10);

        // Abrir modal de reporte
        $I->click(self::BTN_REPORTE);
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Generar Reporte', 5, self::MODAL_TITULO);
        $I->see('Generar Reporte', self::BTN_PROCESO);
        $I->dontSeeElement(self::SECCION_METRICAS);

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

        $I->fillField(self::BUSQUEDA, 'atleta_inexistente_smoke_xyz_999');
        $I->executeJS("$('#busqueda').trigger('keyup');");
        $I->waitForText('No se encontraron registros de estadísticas', 10, self::RESULTADOS);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: Apertura y cierre del tour interactivo de ayuda (Driver.js).
     */
    public function ProbarTourGuiadoAyuda(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $I->waitForElementVisible(self::BTN_AYUDA, 10);

        $I->click(self::BTN_AYUDA);
        $I->waitForElementVisible(self::DRIVER_POPOVER, 5);
        $I->waitForText('Barra de Búsqueda', 5, self::DRIVER_POPOVER);

        // Cerrar tour guiado
        $I->executeJS("
            if ($('.driver-close-btn').length) {
                $('.driver-close-btn').click();
            } else if ($('.driver-popover-close-btn').length) {
                $('.driver-popover-close-btn').click();
            } else if (window.driver && typeof window.driver.reset === 'function') {
                window.driver.reset();
            } else if (window.driver && typeof window.driver.destroy === 'function') {
                window.driver.destroy();
            }
        ");

        $I->waitForElementNotVisible(self::DRIVER_POPOVER, 5);
        $this->verificarSinErrores($I);
    }

    protected function modulosProvider(): array
    {
        return [
            'Estadisticas' => ['href' => self::MODULO_HREF, 'titulo' => self::MODULO_TITULO],
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
