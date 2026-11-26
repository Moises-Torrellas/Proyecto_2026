<?php

declare(strict_types=1);

namespace Tests\Acceptance\Smoke;

use Codeception\Attribute\DataProvider;
use Codeception\Attribute\Group;
use Codeception\Example;
use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Humo (Smoke Testing) con Codeception
 * Módulo: Palmarés (Historial Deportivo > Palmarés)
 *
 * Valida la disponibilidad crítica, renderizado, navegación e integridad
 * del módulo de Palmarés sin ejecutar mutaciones destructivas en la BD.
 */
#[Group('smoke')]
#[Group('palmares')]
final class PalmaresCest
{
    private const SECCION           = 'Historial Deportivo';
    private const MODULO_HREF       = 'Palmares';
    private const MODULO_TITULO     = 'Palmarés';

    // Selectores principales de la vista
    private const TITULO            = '#titulo';
    private const BUSQUEDA          = '#busqueda';
    private const BTN_NUEVO         = '#incluir';
    private const BTN_REPORTE       = '#generar';
    private const BTN_AYUDA         = '#ayuda';

    // Selectores de pestañas y contenedores
    private const TAB_INDIVIDUAL    = '.pestana-btn[data-target="#tab-individual"]';
    private const TAB_GRUPAL        = '.pestana-btn[data-target="#tab-grupal"]';
    private const PANEL_INDIVIDUAL  = 'div#tab-individual';
    private const PANEL_GRUPAL      = 'div#tab-grupal';
    private const RESULTADOS_IND    = '#resultadoconsulta-ind';
    private const RESULTADOS_GRP    = '#resultadoconsulta-grp';
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const LISTADO_VACIO     = '.listado_vacio';

    // Selectores del modal y formulario
    private const MODAL             = '#modal';
    private const MODAL_TITULO      = '#titulo_modal';
    private const MODAL_CERRAR      = '#cerrar_modal';
    private const MODAL_FORM        = '#f';
    private const SWAL_POPUP        = '.swal2-popup';
    private const SWAL_CONFIRM      = 'button.swal2-confirm';
    private const SWAL_CANCEL       = 'button.swal2-cancel';

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
     * Humo: entrar al módulo Palmarés desde el menú y comprobar que responde y se renderiza.
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
        $I->seeElement(self::RESULTADOS_IND . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS_IND . ' ' . self::LISTADO_VACIO);

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
        $I->seeElement(self::BTN_AYUDA);
        $I->seeElement(self::TAB_INDIVIDUAL);
        $I->seeElement(self::TAB_GRUPAL);

        // El listado respondió con registros o estado vacío
        $I->seeElement(self::RESULTADOS_IND . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS_IND . ' ' . self::LISTADO_VACIO);
        $I->seeElementInDOM(self::RESULTADOS_GRP);

        // El modal existe en el DOM pero debe permanecer oculto
        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: Alternancia visual de pestañas (Individual / Grupal)
     */
    public function ProbarAlternanciaDePestanas(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $I->waitForElementVisible(self::TAB_INDIVIDUAL, 5);

        // Inicialmente pestaña Individual está activa
        $I->seeElement(self::TAB_INDIVIDUAL . '.activa');

        // Cambiar a Grupal
        $I->click(self::TAB_GRUPAL);
        $I->waitForElement(self::TAB_GRUPAL . '.activa', 5);
        $I->waitForElement(self::PANEL_GRUPAL . '.activa', 5);
        $I->seeElement(self::TAB_GRUPAL . '.activa');

        // Regresar a Individual
        $I->click(self::TAB_INDIVIDUAL);
        $I->waitForElement(self::TAB_INDIVIDUAL . '.activa', 5);
        $I->waitForElement(self::PANEL_INDIVIDUAL . '.activa', 5);
        $I->seeElement(self::TAB_INDIVIDUAL . '.activa');

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: El diálogo de selección de tipo abre y se cierra con Cancelar sin afectar la vista.
     */
    public function ProbarDialogoNuevoYCancelacion(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $I->waitForElementVisible(self::BTN_NUEVO, 10);

        // Clic en Nuevo Palmarés
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Qué tipo de Palmarés desea registrar?', 5, self::SWAL_POPUP);

        // Cancelar
        $I->click(self::SWAL_CANCEL);
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: El modal de registro abre tras seleccionar tipo y se cierra limpiamente.
     */
    public function ProbarModalDeRegistro(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $I->waitForElementVisible(self::BTN_NUEVO, 10);

        // Abrir diálogo y seleccionar Individual
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->click(self::SWAL_CONFIRM);
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Modal visible con título y formulario
        $I->waitForElementVisible(self::MODAL, 5);
        $I->seeElement(self::MODAL_FORM);
        $I->waitForText('Registrar Palmarés Individual', 5, self::MODAL_TITULO);

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
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS_IND);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: El tour interactivo de ayuda inicia correctamente y se cierra limpiamente.
     */
    public function ProbarActivacionTourGuiado(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $I->waitForElementVisible(self::BTN_AYUDA, 10);

        $I->click(self::BTN_AYUDA);

        $selectorPopover = '#driver-popover-item, .driver-popover';
        $I->waitForElementVisible($selectorPopover, 5);
        $I->waitForText('Barra de Búsqueda', 5, $selectorPopover);

        // Cerrar tour limpiamente
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

        $I->waitForElementNotVisible($selectorPopover, 5);
        $this->verificarSinErrores($I);
    }

    protected function modulosProvider(): array
    {
        return [
            'Palmares' => ['href' => self::MODULO_HREF, 'titulo' => self::MODULO_TITULO],
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
