<?php

declare(strict_types=1);

namespace Tests\Acceptance\Smoke;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Humo (Smoke Testing) con Codeception
 * Módulo: Permisos (Seguridad > Permisos)
 *
 * Valida la disponibilidad crítica, renderizado, navegación e integridad
 * del módulo de Permisos sin ejecutar mutaciones destructivas en la BD.
 */
final class PermisosCest
{
    private const SECCION           = 'Seguridad';
    private const MODULO_HREF       = 'Permisos';
    private const MODULO_TITULO     = 'Permisos';

    // Permiso que siempre existe en la BD (lo usa el menú para mostrar Usuarios)
    private const PERMISO_CLAVE     = 'ingresar_usuarios';
    private const PERMISO_NOMBRE    = 'Ingresar A Usuarios';
    private const PERMISO_MODULO    = 'Usuarios';

    // Selectores principales de la vista
    private const TITULO            = '#titulo';
    private const RESULTADOS        = '#resultadoconsulta';
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const BUSQUEDA          = '#busqueda';
    private const BTN_NUEVO         = '#incluir';
    private const DETALLE_OCULTO    = '.listado_detalle_oculto';
    private const BTN_MODIFICAR     = '#resultadoconsulta .listado_detalle_oculto .cbt_v';

    // Selectores del modal y formulario
    private const MODAL             = '#modal';
    private const MODAL_TITULO      = '#titulo_modal';
    private const MODAL_CERRAR      = '#cerrar_modal';
    private const MODAL_FORM        = '#f';
    private const BTN_PROCESO       = '#proceso';
    private const SELECT_MODULO     = '#modulo';

    /** Campos del formulario de registro de permiso. */
    private const CAMPOS_REGISTRO = ['#nombre', '#descripcion', '#clave'];

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
     * Humo: entrar al módulo Permisos desde el menú y comprobar que responde y se renderiza.
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
        $I->seeElement(self::RESULTADOS);

        // El listado respondió agrupado por módulo
        $I->seeElement($this->selectorListado());
        $I->seeElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL);

        // El modal existe en el DOM pero debe permanecer oculto
        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: Al pulsar un módulo del listado se despliegan sus permisos.
     */
    public function ProbarDetalleDeModulo(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        // Filtrar para que solo el módulo de Usuarios quede en pantalla
        $this->buscarPermisoExistente($I);

        $item = self::RESULTADOS . ' .listado_item';
        $detalle = self::RESULTADOS . ' ' . self::DETALLE_OCULTO;

        $I->dontSeeElement($detalle);
        $I->click($item);
        $I->waitForElementVisible($detalle, 10);
        $I->see(self::PERMISO_NOMBRE, $detalle);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: El modal de registro abre con su formulario completo y la lista de módulos cargada
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
        $I->waitForText('Registrar Permiso', 5, self::MODAL_TITULO);
        $I->see('Registrar Permiso', self::BTN_PROCESO);

        // Campos del formulario de registro
        foreach (self::CAMPOS_REGISTRO as $campo) {
            $I->seeElement($campo);
        }

        // La lista de módulos se llenó con la consulta a la BD
        $I->waitForJS(
            "return document.querySelectorAll('" . self::SELECT_MODULO . " option').length > 0;",
            10
        );

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: El modal de modificación abre con los datos del permiso cargados desde la BD
     * y se cierra limpiamente sin persistir cambios.
     */
    public function ProbarModalDeModificacion(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        // Desplegar los permisos del módulo de Usuarios
        $this->buscarPermisoExistente($I);
        $I->click(self::RESULTADOS . ' .listado_item');
        $I->waitForElementVisible(self::BTN_MODIFICAR, 10);

        // Abrir modal de modificación
        $I->click(self::BTN_MODIFICAR);
        $I->waitForElementVisible(self::MODAL, 10);
        $I->seeElement(self::MODAL_FORM);
        $I->waitForText('Modificar Permiso', 10, self::MODAL_TITULO);
        $I->see('Modificar Permiso', self::BTN_PROCESO);

        // Los datos del permiso llegaron desde la BD
        $I->seeInField('#nombre', self::PERMISO_NOMBRE);
        $I->seeElement('#descripcion');

        // Cerrar sin guardar
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->verificarSinErrores($I);
    }

    /**
     * Humo: La búsqueda rápida vía AJAX encuentra un permiso existente por su clave.
     */
    public function ProbarBusquedaConResultados(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $this->esperarCargaDePagina($I);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $this->buscarPermisoExistente($I);
        $I->see(self::PERMISO_MODULO, self::RESULTADOS . ' .listado_titulo');

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

    /**
     * Escribe la clave del permiso existente en el buscador y espera a que el listado se
     * redibuje con un solo módulo que contenga ese permiso.
     */
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
