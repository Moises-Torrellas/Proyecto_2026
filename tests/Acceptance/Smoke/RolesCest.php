<?php

declare(strict_types=1);

namespace Tests\Acceptance\Smoke;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Humo (Smoke Testing) con Codeception
 * Módulo: Roles (Seguridad > Roles)
 *
 * Valida la disponibilidad crítica, renderizado, navegación e integridad
 * del módulo de Roles sin ejecutar mutaciones destructivas en la BD.
 */
final class RolesCest
{
    private const SECCION           = 'Seguridad';
    private const MODULO_HREF       = 'Roles';
    private const MODULO_TITULO     = 'Roles';

    // Rol de nivel 3 que siempre existe en la BD (el listado no muestra los niveles 1 y 2)
    private const ROL_EXISTENTE     = 'Entrenador';

    // Selectores principales de la vista
    private const TITULO            = '#titulo';
    private const RESULTADOS        = '#resultadoconsulta';
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const BUSQUEDA          = '#busqueda';
    private const BTN_NUEVO         = '#incluir';
    private const BTN_REPORTE       = '#generar';
    private const BTN_PERMISOS      = '#resultadoconsulta .cbt_m';

    // Selectores del modal y formulario
    private const MODAL             = '#modal';
    private const MODAL_TITULO      = '#titulo_modal';
    private const MODAL_CERRAR      = '#cerrar_modal';
    private const MODAL_FORM        = '#f';
    private const BTN_PROCESO       = '#proceso';
    private const FILA_NOMBRE       = '#row_nombre';
    private const FILA_PERMISOS     = '#row_permisos';
    private const TAB_ASIGNADOS     = '.permisos_tab[data-tab="asignados"]';
    private const TAB_NO_ASIGNADOS  = '.permisos_tab[data-tab="no_asignados"]';

    /** Campos del formulario de registro de rol. */
    private const CAMPOS_REGISTRO = ['#nombre', '#descripcion'];

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
        $this->esperarCargaDePagina($I);
    }

    /**
     * Humo: entrar al módulo Roles desde el menú y comprobar que responde y se renderiza.
     */
    #[DataProvider('modulosProvider')]
    public function ProbarCargaDeModulo(AcceptanceTester $I, Example $example): void
    {
        $href = $example['href'];

        // Entrar desde el menú
        $I->click($this->xpathSeccion(self::SECCION));
        $enlace = sprintf('a[href="%s"]', $href);
        $I->waitForElementVisible($enlace, 5);
        $I->click($enlace);

        // La vista cargó
        $I->waitForText($example['titulo'], 10, self::TITULO);
        $this->esperarCargaDePagina($I);
        $I->seeInCurrentUrl($href);

        // La consulta a la base de datos respondió: hay registros o mensaje de vacío
        $I->seeElement($this->selectorListado());

        // Sin errores de PHP
        $this->verificarSinErrores($I);
    }

    /**
     * Humo: Acceso directo por URL y verificación de controles estructurales esenciales.
     */
    public function ProbarAccesoDirectoYControlesBase(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForText(self::MODULO_TITULO, 10, self::TITULO);
        $I->seeInCurrentUrl(self::MODULO_HREF);

        // Controles esenciales de la interfaz
        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::RESULTADOS);

        // El listado respondió y muestra el rol que siempre existe
        $I->seeElement($this->selectorListado());
        $I->see(self::ROL_EXISTENTE, self::RESULTADOS);

        // El modal existe en el DOM pero debe permanecer oculto
        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: El modal de registro abre con su formulario y se cierra limpiamente sin persistir datos.
     */
    public function ProbarModalDeRegistro(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BTN_NUEVO, 10);

        // Abrir modal
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);
        $I->seeElement(self::MODAL_FORM);

        // Título y botón del modal cargados dinámicamente por JS
        $I->waitForText('Registrar Rol', 5, self::MODAL_TITULO);
        $I->see('Registrar Rol', self::BTN_PROCESO);

        // Campos del formulario de registro
        foreach (self::CAMPOS_REGISTRO as $campo) {
            $I->seeElement($campo);
        }

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: El modal de reporte abre con su título y se cierra sin generar archivos.
     */
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

    /**
     * Humo: El modal de permisos de un rol carga la matriz de permisos desde la BD con sus
     * pestañas, y se cierra sin guardar cambios.
     */
    public function ProbarModalDePermisos(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BTN_PERMISOS, 10);

        $I->click(self::BTN_PERMISOS);
        $I->waitForElementVisible(self::MODAL, 10);
        $I->waitForText('Permisos del Rol', 10, self::MODAL_TITULO);
        $I->see('Guardar Permisos', self::BTN_PROCESO);

        // Se muestra la matriz de permisos en lugar del formulario de nombre
        $I->seeElement(self::FILA_PERMISOS);
        $I->dontSeeElement(self::FILA_NOMBRE);
        $I->seeElement(self::TAB_ASIGNADOS);
        $I->seeElement(self::TAB_NO_ASIGNADOS);

        // Entre ambas pestañas se cargó al menos un permiso
        $I->waitForJS(
            "return document.querySelectorAll('#tabla_permisos_asignados *, #tabla_permisos_no_asignados *').length > 0;",
            10
        );

        // Cerrar sin guardar
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: La búsqueda rápida vía AJAX encuentra un rol existente.
     */
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

    /**
     * Humo: La búsqueda rápida vía AJAX responde adecuadamente ante cadenas sin coincidencia.
     */
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

    /**
     * Cada vista muestra una capa de carga (#loader) a pantalla completa que main.js oculta
     * cuando la página termina de cargar. Mientras está visible intercepta los clics.
     */
    private function esperarCargaDePagina(AcceptanceTester $I): void
    {
        $I->waitForElementNotVisible('#loader', 30);
        $this->desactivarAnimaciones($I);
    }

    /**
     * Los modales aparecen con transiciones CSS de 0.2 s. Si la ventana de Chrome queda detrás
     * de otra, Windows pausa esas transiciones y el modal sigue "invisible" para WebDriver
     * aunque ya esté abierto. Sin animaciones los cambios son inmediatos.
     */
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
