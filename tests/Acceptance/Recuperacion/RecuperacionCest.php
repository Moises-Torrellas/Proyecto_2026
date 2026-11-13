<?php

declare(strict_types=1);

namespace Tests\Acceptance\Recuperacion;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('recuperacion')]
#[Group('interfaz')]
final class RecuperacionCest
{
    private const RUTA_MODULO           = '/Recuperacion';
    private const RUTA_LOGIN            = '/';
    
    // Textos de encabezado e interfaz
    private const TITULO_PAGINA         = 'Recuperar';
    private const TITULO_ENCABEZADO     = '¡Hola No Te Preocupes!';
    private const SUBTITULO_ENCABEZADO  = 'Podemos Recuperar tu';
    private const TEXTO_SEPARADOR       = 'Recuperar Contraseña';

    // Secciones (Pasos del Asistente)
    private const SEC_1                 = '#sec_1';
    private const SEC_2                 = '#sec_2';
    private const SEC_3                 = '#sec_3';

    // Paso 1: Cédula
    private const INPUT_CEDULA          = '#cedula';
    private const BTN_ENVIAR_CODIGO     = '#comprobar';
    private const SPAN_ERROR_CEDULA     = '#spam_cedula';

    // Paso 2: Código de Verificación
    private const INPUT_CODIGO          = '#codigo';
    private const BTN_COMPROBAR_CODIGO  = '#comprobarCodigo';
    private const BTN_REENVIAR_CODIGO   = '#reenviar';
    private const CONTADOR_REENVIO      = '#contador';

    // Paso 3: Nueva Contraseña
    private const INPUT_CLAVE           = '#contraseña';
    private const INPUT_CLAVE_R         = '#contraseña_r';
    private const BTN_CAMBIAR_CLAVE     = '#cambiar';

    // Navegación
    private const ENLACE_OLVIDO_CLAVE   = '¿Has olvidado tu contraseña?';
    private const BTN_VOLVER_INICIO     = 'Volver Al Inicio de Sesión';

    public function _before(AcceptanceTester $I): void
    {
    }

    /**
     * Prueba 1: Verifica la carga inicial correcta de la interfaz de recuperación de contraseña.
     */
    #[Group('carga', 'interfaz')]
    public function probarCargaInicialDeInterfaz(AcceptanceTester $I): void
    {
        $I->amOnPage(self::RUTA_MODULO);

        // Validar textos y títulos clave
        $I->see(self::TITULO_ENCABEZADO);
        $I->see(self::SUBTITULO_ENCABEZADO);
        $I->see(self::TEXTO_SEPARADOR);

        // Validar elementos del paso 1
        $I->waitForElementVisible(self::INPUT_CEDULA, 5);
        $I->seeElement(self::BTN_ENVIAR_CODIGO);
        $I->see(self::BTN_VOLVER_INICIO);

        // Los pasos 2 y 3 deben existir en el DOM pero no estar visibles inicialmente
        $I->seeElementInDOM(self::INPUT_CODIGO);
        $I->seeElementInDOM(self::INPUT_CLAVE);
        $I->seeElementInDOM(self::INPUT_CLAVE_R);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 2: Verifica la navegación desde la vista de login hacia la vista de recuperación.
     */
    #[Group('navegacion')]
    public function probarNavegacionDesdeLoginHaciaRecuperacion(AcceptanceTester $I): void
    {
        $I->amOnPage(self::RUTA_LOGIN);
        $I->waitForElementVisible('input[name="cedula"]', 5);

        // Hacer clic en el enlace "¿Has olvidado tu contraseña?"
        $I->see(self::ENLACE_OLVIDO_CLAVE);
        $I->click(self::ENLACE_OLVIDO_CLAVE);

        // Verificar que aterrice en la pantalla de recuperación
        $I->waitForElementVisible(self::INPUT_CEDULA, 5);
        $I->see(self::TITULO_ENCABEZADO);
        $I->see(self::TEXTO_SEPARADOR);
        $I->verificarSinErrores();
    }

    /**
     * Prueba 3: Verifica el retorno al inicio de sesión mediante el botón correspondiente.
     */
    #[Group('navegacion')]
    public function probarNavegacionRetornoAlLogin(AcceptanceTester $I): void
    {
        $I->amOnPage(self::RUTA_MODULO);
        $I->waitForElementVisible(self::INPUT_CEDULA, 5);

        // Hacer clic en "Volver Al Inicio de Sesión"
        $I->click(self::BTN_VOLVER_INICIO);

        // Verificar retorno a la pantalla de login
        $I->waitForElementVisible('input[name="cedula"]', 5);
        $I->see('Iniciar Sesión');
        $I->verificarSinErrores();
    }

    /**
     * Prueba 4: Valida el rechazo en frontend al ingresar una cédula con formato inválido.
     */
    #[Group('validacion')]
    public function probarValidacionCedulaFormatoInvalido(AcceptanceTester $I): void
    {
        $I->amOnPage(self::RUTA_MODULO);
        $I->waitForElementVisible(self::INPUT_CEDULA, 5);

        // Ingresar cédula corta (menos de 7 dígitos)
        $I->fillField(self::INPUT_CEDULA, '1234');
        $I->click(self::BTN_ENVIAR_CODIGO);

        // Esperar el mensaje de alerta de validación
        $I->waitForText('Tiene que ingresar una cedula valida', 5);
    }

    /**
     * Prueba 5: Valida el rechazo en frontend al intentar enviar con la cédula vacía.
     */
    #[Group('validacion')]
    public function probarValidacionCedulaVacia(AcceptanceTester $I): void
    {
        $I->amOnPage(self::RUTA_MODULO);
        $I->waitForElementVisible(self::INPUT_CEDULA, 5);

        // Dejar campo vacío y hacer clic
        $I->fillField(self::INPUT_CEDULA, '');
        $I->click(self::BTN_ENVIAR_CODIGO);

        // Debe saltar la alerta de validación requerida
        $I->waitForText('Tiene que ingresar una cedula valida', 5);
    }

    /**
     * Prueba 6: Valida la respuesta segura ante una cédula sintácticamente correcta pero no registrada.
     */
    #[Group('seguridad')]
    public function probarSolicitudConCedulaNoRegistrada(AcceptanceTester $I): void
    {
        $I->amOnPage(self::RUTA_MODULO);
        $I->waitForElementVisible(self::INPUT_CEDULA, 5);

        // Cédula válida de 8 dígitos pero inexistente en el sistema
        $I->fillField(self::INPUT_CEDULA, '99999999');
        $I->click(self::BTN_ENVIAR_CODIGO);

        // Debe responder con el mensaje seguro que previene la enumeración de usuarios
        $I->waitForText('Si la cédula está registrada, recibirás un correo.', 10);
        $I->verificarSinErrores();
    }

    /**
     * Prueba 7: Verifica la existencia y configuración de los componentes de seguridad en las secciones posteriores.
     */
    #[Group('estructura')]
    public function probarEstructuraComponentesSeguridad(AcceptanceTester $I): void
    {
        $I->amOnPage(self::RUTA_MODULO);

        // Comprobar elementos de paso 2 en el DOM
        $I->seeElementInDOM(self::INPUT_CODIGO);
        $I->seeElementInDOM(self::BTN_COMPROBAR_CODIGO);
        $I->seeElementInDOM(self::BTN_REENVIAR_CODIGO);
        $I->seeElementInDOM(self::CONTADOR_REENVIO);

        // Comprobar elementos de paso 3 en el DOM
        $I->seeElementInDOM(self::INPUT_CLAVE);
        $I->seeElementInDOM(self::INPUT_CLAVE_R);
        $I->seeElementInDOM(self::BTN_CAMBIAR_CLAVE);

        // Comprobar íconos para mostrar/ocultar contraseñas
        $I->seeNumberOfElements('.icon_input_ojo', 2);

        $I->verificarSinErrores();
    }
}

