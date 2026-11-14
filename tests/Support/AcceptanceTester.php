<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Inherited Methods
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    // =========================================================================
    // SELECTORES GLOBALES REUTILIZABLES (Módulos CRUD)
    // =========================================================================
    public const TITULO_PAGINA     = '#titulo';
    public const INPUT_BUSQUEDA    = '#busqueda';
    public const BTN_NUEVO         = '#incluir';
    public const BTN_REPORTE       = '#generar';
    public const CONT_RESULTADOS   = '#resultadoconsulta';
    public const ITEM_LISTADO      = '.listado_item';
    public const LISTADO_VACIO     = '.listado_vacio';
    public const BTN_MODIFICAR     = '#resultadoconsulta .cbt_v';
    public const BTN_ELIMINAR      = '#resultadoconsulta .cbt_r';

    public const MODAL             = '#modal';
    public const MODAL_CONTENEDOR  = '#contenedor_modal';
    public const MODAL_TITULO      = '#titulo_modal';
    public const MODAL_CERRAR      = '#cerrar_modal';
    public const FORMULARIO        = '#f';
    public const INPUT_ID          = '#id';
    public const INPUT_NOMBRE      = '#nombre';
    public const INPUT_APELLIDO    = '#apellido';
    public const INPUT_CEDULA      = '#cedula';
    public const INPUT_TELEFONO    = '#telefono';
    public const INPUT_DIRECCION   = '#direccion';
    public const INPUT_CORREO      = '#correo';
    public const INPUT_DESCRIPCION = '#descripcion';
    public const BTN_PROCESO       = '#proceso';

    public const TEXTOS_DE_ERROR = [
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

    // =========================================================================
    // ACCIONES COMUNES REUTILIZABLES
    // =========================================================================

    /**
     * Inicia sesión con snapshot para no autenticar en cada test desde cero.
     */
    public function iniciarSesionComoAdmin(string $cedula = '12345678', string $clave = 'Admin456$'): void
    {
        $this->amOnPage('/');

        if (!$this->loadSessionSnapshot('login')) {
            $this->waitForElement('input[name="cedula"]', 5);
            $this->fillField('cedula', $cedula);
            $this->fillField('contraseña', $clave);
            $this->click('Iniciar Sesión');
            $this->waitForText('Inicio Validado', 10);
            $this->saveSessionSnapshot('login');
        }
    }

    /**
     * Navega a un módulo específico, comprueba su título y despeja la pantalla.
     */
    public function irAlModulo(string $ruta, string $tituloEsperado): void
    {
        $this->iniciarSesionComoAdmin();
        $this->amOnPage($ruta);
        $this->waitForText($tituloEsperado, 10, self::TITULO_PAGINA);
        $this->waitForElementVisible(self::INPUT_BUSQUEDA, 5);
        $this->despejarOverlays();
        $this->verificarSinErrores();
    }

    /**
     * Cierra cualquier alerta o toast flotante que pueda bloquear clics en la pantalla.
     */
    public function despejarOverlays(): void
    {
        $this->executeJS("if (typeof Swal !== 'undefined' && Swal.isVisible()) { Swal.close(); }");
        $this->executeJS("document.querySelectorAll('.swal2-container').forEach(function(el){ el.remove(); }); if (document.body) { document.body.classList.remove('swal2-shown', 'swal2-toast-shown'); }");
        $this->executeJS("if (typeof flatpickr !== 'undefined') { document.querySelectorAll('.flatpickr-calendar.open').forEach(el => el.classList.remove('open')); }");
    }

    /**
     * Realiza una búsqueda respetando el debounce de 500ms de JavaScript.
     */
    public function buscarEnListado(string $texto): void
    {
        $this->fillField(self::INPUT_BUSQUEDA, $texto);
        $this->wait(1);
    }

    /**
     * Limpia la barra de búsqueda y dispara el evento keyup para restaurar la lista.
     */
    public function limpiarBuscador(): void
    {
        $this->fillField(self::INPUT_BUSQUEDA, '');
        $this->executeJS("$('#busqueda').trigger('keyup');");
        $this->wait(1);
        $this->waitForElementVisible(self::ITEM_LISTADO, 5);
    }

    /**
     * Hace clic en el botón de modificar del listado de forma segura.
     */
    public function clickModificar(): void
    {
        $this->waitForElementVisible(self::BTN_MODIFICAR, 5);
        $this->despejarOverlays();
        $this->executeJS("document.querySelector('" . self::BTN_MODIFICAR . "').click();");
    }

    /**
     * Hace clic en el botón de eliminar del listado de forma segura.
     */
    public function clickEliminar(): void
    {
        $this->waitForElementVisible(self::BTN_ELIMINAR, 5);
        $this->despejarOverlays();
        $this->executeJS("document.querySelector('" . self::BTN_ELIMINAR . "').click();");
    }

    /**
     * Confirma una pregunta de SweetAlert ("SI").
     */
    public function confirmarSweetAlert(): void
    {
        $this->waitForElementVisible('.swal2-confirm', 5);
        $this->click('SI');
    }

    /**
     * Cierra un mensaje informativo de SweetAlert ("Entendido").
     */
    public function aceptarSweetAlert(): void
    {
        $this->waitForElementVisible('.swal2-confirm', 5);
        $this->click('Entendido');
    }

    /**
     * Hace clic en el botón de cancelar / no en un diálogo SweetAlert.
     */
    public function cancelarSweetAlert(): void
    {
        $this->waitForElementVisible('.swal2-cancel', 5);
        $this->executeJS("document.querySelector('.swal2-cancel').click();");
        $this->wait(1);
        $this->despejarOverlays();
    }

    /**
     * Completa y confirma un diálogo SweetAlert con campo de texto (ej. anulación o motivo de retiro).
     */
    public function responderSweetAlertPrompt(string $texto): void
    {
        $this->waitForElementVisible('.swal2-input', 5);
        $this->fillField('.swal2-input', $texto);
        $this->click('SI');
    }

    /**
     * Verifica que en la vista no aparezcan errores de PHP ni de seguridad.
     */
    public function verificarSinErrores(): void
    {
        foreach (self::TEXTOS_DE_ERROR as $texto) {
            $this->dontSee($texto);
        }
    }
}
