<?php

declare(strict_types=1);

namespace Tests\Acceptance\Smoke;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Humo (Smoke Testing) con Codeception
 * Módulo: Usuarios (Seguridad > Usuarios)
 *
 * Valida la disponibilidad crítica, renderizado, navegación e integridad
 * del módulo de Usuarios sin ejecutar mutaciones destructivas en la BD.
 */
final class UsuariosCest
{
    private const SECCION           = 'Seguridad';
    private const MODULO_HREF       = 'Usuarios';
    private const MODULO_TITULO     = 'Usuarios';

    // Usuario administrador con el que se inicia sesión (siempre existe en la BD)
    private const CEDULA_ADMIN      = '12345678';

    // Selectores principales de la vista
    private const TITULO            = '#titulo';
    private const RESULTADOS        = '#resultadoconsulta';
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const BUSQUEDA          = '#busqueda';
    private const BTN_NUEVO         = '#incluir';
    private const BTN_REPORTE       = '#generar';

    // Selectores del modal y formulario
    private const MODAL             = '#modal';
    private const MODAL_TITULO      = '#titulo_modal';
    private const MODAL_CERRAR      = '#cerrar_modal';
    private const MODAL_FORM        = '#f';
    private const BTN_PROCESO       = '#proceso';
    private const SELECT_ROLES      = '#roles';

    /** Campos del formulario de registro de usuario. */
    private const CAMPOS_REGISTRO = ['#cedula', '#nombre', '#apellido', '#telefono', '#correo', '#contraseña'];

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

    /**
     * Humo: entrar al módulo Usuarios desde el menú y comprobar que responde y se renderiza.
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

        // El listado respondió y muestra al menos al administrador con sesión iniciada
        $I->seeElement($this->selectorListado());
        $I->seeElement(self::RESULTADOS . ' .listado_item');

        // El modal existe en el DOM pero debe permanecer oculto
        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: El modal de registro abre con su formulario completo y la lista de roles cargada
     * desde la BD, y se cierra limpiamente sin persistir datos.
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
        $I->waitForText('Registrar Usuario', 5, self::MODAL_TITULO);
        $I->see('Registrar Usuario', self::BTN_PROCESO);

        // Campos del formulario de registro
        foreach (self::CAMPOS_REGISTRO as $campo) {
            $I->seeElementInDOM($campo);
        }

        // La lista de roles se llenó con la consulta a la BD
        $I->waitForJS(
            "return document.querySelectorAll('" . self::SELECT_ROLES . " option').length > 0;",
            10
        );

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
     * Humo: La búsqueda rápida vía AJAX encuentra al administrador por su cédula.
     */
    public function ProbarBusquedaConResultados(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $I->fillField(self::BUSQUEDA, self::CEDULA_ADMIN);
        $I->waitForText(self::CEDULA_ADMIN, 10, self::RESULTADOS);
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

        $I->fillField(self::BUSQUEDA, 'usuario_inexistente_smoke_xyz_999');
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);

        $this->verificarSinErrores($I);
    }

    protected function modulosProvider(): array
    {
        return [
            'Usuarios' => ['href' => self::MODULO_HREF, 'titulo' => self::MODULO_TITULO],
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
