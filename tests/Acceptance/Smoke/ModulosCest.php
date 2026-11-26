<?php

declare(strict_types=1);

namespace Tests\Acceptance\Smoke;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Humo (Smoke Testing) con Codeception
 * Módulo: Modulos (Seguridad > Modulos)
 *
 * Valida la disponibilidad crítica, renderizado, navegación e integridad
 * del módulo de Modulos sin ejecutar mutaciones destructivas en la BD.
 */
final class ModulosCest
{
    private const SECCION           = 'Seguridad';
    private const MODULO_HREF       = 'Modulos';
    private const MODULO_TITULO     = 'Modulos';

    // Módulo del sistema que siempre existe en la BD
    private const MODULO_EXISTENTE  = 'Usuarios';

    // Módulo interno de sesión que el listado nunca debe mostrar
    private const MODULO_INTERNO    = 'Inicio de Sesion';

    // Selectores principales de la vista
    private const TITULO            = '#titulo';
    private const RESULTADOS        = '#resultadoconsulta';
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const BUSQUEDA          = '#busqueda';
    private const BTN_MODIFICAR     = '#resultadoconsulta .cbt_v';

    // Selectores del modal y formulario
    private const MODAL             = '#modal';
    private const MODAL_TITULO      = '#titulo_modal';
    private const MODAL_CERRAR      = '#cerrar_modal';
    private const MODAL_FORM        = '#f';
    private const BTN_PROCESO       = '#proceso';

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
     * Humo: entrar al módulo Modulos desde el menú y comprobar que responde y se renderiza.
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
        $I->seeElement(self::RESULTADOS);

        // El listado respondió con los módulos del sistema, sin los internos de sesión
        $I->seeElement($this->selectorListado());
        $I->see(self::MODULO_EXISTENTE, self::RESULTADOS . ' .listado_titulo');
        $I->dontSee(self::MODULO_INTERNO, self::RESULTADOS);

        // El modal existe en el DOM pero debe permanecer oculto
        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: El modal de modificación abre con los datos del módulo cargados desde la BD
     * y se cierra limpiamente sin persistir cambios.
     */
    public function ProbarModalDeModificacion(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        // Dejar en pantalla solo el módulo de Usuarios
        $this->buscarModuloExistente($I);
        $I->waitForElementVisible(self::BTN_MODIFICAR, 10);

        // Abrir modal de modificación
        $I->click(self::BTN_MODIFICAR);
        $I->waitForElementVisible(self::MODAL, 10);
        $I->seeElement(self::MODAL_FORM);
        $I->waitForText('Modificar Modulo', 10, self::MODAL_TITULO);
        $I->see('Modificar Modulo', self::BTN_PROCESO);

        // Los datos del módulo llegaron desde la BD
        $I->seeInField('#nombre', self::MODULO_EXISTENTE);
        $I->seeElement('#descripcion');

        // Cerrar sin guardar
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: La búsqueda rápida vía AJAX encuentra un módulo existente por su nombre.
     */
    public function ProbarBusquedaConResultados(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $this->buscarModuloExistente($I);
        $I->see(self::MODULO_EXISTENTE, self::RESULTADOS . ' .listado_titulo');

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

        $I->fillField(self::BUSQUEDA, 'modulo_inexistente_smoke_xyz_999');
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);

        $this->verificarSinErrores($I);
    }

    protected function modulosProvider(): array
    {
        return [
            'Modulos' => ['href' => self::MODULO_HREF, 'titulo' => self::MODULO_TITULO],
        ];
    }

    /**
     * Escribe el nombre del módulo existente en el buscador y espera a que el listado se
     * redibuje mostrando solo ese módulo.
     */
    private function buscarModuloExistente(AcceptanceTester $I): void
    {
        $I->fillField(self::BUSQUEDA, self::MODULO_EXISTENTE);
        $I->waitForJS(
            "var t = document.querySelectorAll('" . self::RESULTADOS . " .listado_titulo');"
            . " return t.length === 1 && t[0].textContent.trim() === '" . self::MODULO_EXISTENTE . "';",
            10
        );
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
