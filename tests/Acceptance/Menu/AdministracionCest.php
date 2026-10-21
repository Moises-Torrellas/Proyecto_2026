<?php

declare(strict_types=1);

namespace Tests\Acceptance\Interfaz;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Tests\Support\AcceptanceTester;

final class AdministracionInterfazCest
{
    private const SECCION = 'Administración';

    // Elementos comunes a todas las vistas
    private const TITULO       = '#titulo';
    private const BUSQUEDA     = '#busqueda';
    private const RESULTADOS   = '#resultadoconsulta';
    private const BTN_NUEVO    = '#incluir';
    private const MODAL        = '#modal';
    private const MODAL_CERRAR = '#cerrar_modal';
    private const MODAL_TITULO = '#titulo_modal';
    private const MODAL_FORM   = '#f';

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
        $this->verificarSinErrores($I);
    }

    /** La sección Administración aparece en el menú y muestra todos sus enlaces. */
    public function ProbarMenuAdministracion(AcceptanceTester $I): void
    {
        $I->seeElement($this->xpathSeccion(self::SECCION));
        $I->click($this->xpathSeccion(self::SECCION));

        foreach ($this->modulosProvider() as $modulo) {
            $I->waitForElementVisible(sprintf('a[href="%s"]', $modulo['href']), 3);
        }
    }

    /**
     * Navegando desde el menú: la vista carga completa y su listado se renderiza.
     */
    #[DataProvider('modulosProvider')]
    public function ProbarCargaDeModulo(AcceptanceTester $I, Example $example): void
    {
        $href = $example['href'];

        // 1. Expandir la sección y entrar al módulo desde el menú
        $I->click($this->xpathSeccion(self::SECCION));
        $enlace = sprintf('a[href="%s"]', $href);
        $I->waitForElementVisible($enlace, 3);
        $I->see($example['texto_menu'], $enlace);
        $I->click($enlace);

        // 2. Título y URL
        $I->waitForText($example['titulo'], 10, self::TITULO);
        $I->seeInCurrentUrl($href);
        $I->seeElement('.nav_lateral');

        // 3. Controles principales de la vista
        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::RESULTADOS);

        // 4. El listado se renderizó: hay registros o el mensaje de "vacío", pero algo se dibujó
        $I->seeElement(self::RESULTADOS . ' .listado_item, ' . self::RESULTADOS . ' .listado_vacio');

        // 5. El modal existe pero arranca cerrado
        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        // 6. Sin errores de PHP
        $this->verificarSinErrores($I);
    }

    /**
     * El modal de registro abre, trae título y formulario, y se cierra.
     * No guarda nada, así que no deja datos de prueba en la base.
     */
    #[DataProvider('modulosProvider')]
    public function ProbarModalDeRegistro(AcceptanceTester $I, Example $example): void
    {
        $I->amOnPage('/' . $example['href']);
        $I->waitForElementVisible(self::BTN_NUEVO, 10);

        // Abrir
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);
        $I->seeElement(self::MODAL_FORM);

        // El JS de la vista puso el título al modal (comprueba que main.js y el JS del módulo cargaron)
        $I->waitForJS(
            "return document.querySelector('" . self::MODAL_TITULO . "').textContent.trim().length > 0;",
            5
        );

        // Cerrar
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->verificarSinErrores($I);
    }

    /**
     * La búsqueda filtra el listado vía AJAX: con un texto inexistente debe mostrar el mensaje de vacío.
     */
    #[DataProvider('modulosProvider')]
    public function ProbarBusquedaSinResultados(AcceptanceTester $I, Example $example): void
    {
        $I->amOnPage('/' . $example['href']);
        $I->waitForElementVisible(self::BUSQUEDA, 10);

        $I->fillField(self::BUSQUEDA, 'no_existe');
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);

        $this->verificarSinErrores($I);
    }

    protected function modulosProvider(): array
    {
        return [
            'Atletas'        => ['href' => 'Atletas',        'texto_menu' => 'Atletas',        'titulo' => 'Atletas'],
            'Representantes' => ['href' => 'Representantes', 'texto_menu' => 'Representantes', 'titulo' => 'Representantes'],
            'Posiciones'     => ['href' => 'Posiciones',     'texto_menu' => 'Posiciones',     'titulo' => 'Posiciones'],
            'Categorias'     => ['href' => 'Categorias',     'texto_menu' => 'Categorías',     'titulo' => 'Categorías'],
        ];
    }

    // ---------------------------------------------------------------- helpers

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