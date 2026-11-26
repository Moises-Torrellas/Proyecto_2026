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
            $this->enviarLoginRespetandoLimite();
            $this->saveSessionSnapshot('login');
        }
    }

    /**
     * Pulsa "Iniciar Sesión" respetando el límite del login (3 intentos por minuto por IP,
     * RateLimit(3, 60) en Inicio.php). Si el sistema responde "Acceso restringido. Por favor,
     * espera N segundos.", espera esos segundos y reintenta.
     */
    public function enviarLoginRespetandoLimite(): void
    {
        for ($intento = 1; $intento <= 3; $intento++) {
            $this->click('Iniciar Sesión');
            $this->waitForJS(
                "return document.body.innerText.includes('Inicio Validado') || document.body.innerText.includes('Acceso restringido');",
                10
            );
            $texto = (string)$this->executeJS('return document.body.innerText;');
            if (str_contains($texto, 'Inicio Validado')) {
                return;
            }
            $segundos = preg_match('/espera (\d+) segundos/', $texto, $m) ? (int)$m[1] : 5;
            $this->despejarOverlays();
            $this->wait(min(61, $segundos + 1));
        }
        $this->waitForText('Inicio Validado', 10);
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
        $this->waitForElementNotVisible('.swal2-container', 5);
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
     * Espera hasta que un input o select tome un valor específico (útil para campos auto-rellenados por JS).
     */
    public function waitForValue(string $selector, string $valorEsperado, int $timeout = 5): void
    {
        $selJson = json_encode($selector);
        $valJson = json_encode($valorEsperado);
        $this->waitForJS(
            "return (function(){ var el = document.querySelector({$selJson}); return el && String(el.value) === String({$valJson}); })()",
            $timeout
        );
    }

    /**
     * Garantiza que exista al menos un atleta con inscripción activa (estatus = 1)
     * cuando se ejecuta Cobranzas de forma aislada. Si ya existe uno (ej. creado por AtletasCest),
     * lo reutiliza sin insertar nada.
     */
    public function asegurarAtletaActivoEnBD(): void
    {
        if (!defined('_DB_HOST_') || !defined('_DB_NAME_')) {
            require_once dirname(__DIR__, 2) . '/config/config.php';
        }

        try {
            $pdo = new \PDO(
                'mysql:host=' . _DB_HOST_ . ';dbname=' . _DB_NAME_ . ';charset=utf8mb4',
                _DB_USER_,
                _DB_PASS_,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $activos = (int) $pdo->query("SELECT COUNT(*) FROM vista_atletas WHERE estatus = 1")->fetchColumn();
            if ($activos > 0) {
                return;
            }

            $idCat = $pdo->query("SELECT codigo_categoria FROM categorias WHERE edad_min <= 60 LIMIT 1")->fetchColumn();
            if (!$idCat) {
                $pdo->exec("INSERT INTO categorias (nombre, edad_min, edad_max) VALUES ('Senior', 18, 50)");
                $idCat = $pdo->lastInsertId();
            }

            $idPos = $pdo->query("SELECT codigo_posicion FROM posiciones LIMIT 1")->fetchColumn();
            if (!$idPos) {
                $pdo->exec("INSERT INTO posiciones (nombre, abreviatura, descripcion) VALUES ('Delantero', 'DE', 'Atacante')");
                $idPos = $pdo->lastInsertId();
            }

            $pdo->exec("INSERT INTO atletas (p_nombre, s_nombre, p_apellidos, s_apellidos, genero, fecha_nac, lugar_nacimiento, foto)
                        VALUES ('Carlos', 'Eduardo', 'Perez', 'Silva', 'H', '2000-05-15', 'Barquisimeto', 'default.png')");
            $idAtleta = $pdo->lastInsertId();

            $doc = '29' . substr((string) time(), -6);
            $pdo->prepare("INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (?, 'V', ?)")
                ->execute([$idAtleta, $doc]);
            $pdo->prepare("INSERT INTO contacto_atleta (codigo_atleta, direccion, telefono, correo, municipio, instagram)
                           VALUES (?, 'Barquisimeto', '0414-5551234', ?, 'Iribarren', '@carlos_test')")
                ->execute([$idAtleta, "carlos_{$doc}@testmail.com"]);
            $pdo->prepare("INSERT INTO datos_medicos (codigo_atleta, tipo_sangre, es_alergico, alergias_detalle)
                           VALUES (?, 'O+', 0, '')")
                ->execute([$idAtleta]);
            $pdo->prepare("INSERT INTO inscripciones (codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus, talla_pantalon, talla_franela, talla_calzado)
                           VALUES (?, ?, ?, 99, 70.00, 175, CURDATE(), 1, 'M', 'M', '42')")
                ->execute([$idAtleta, $idCat, $idPos]);
        } catch (\Throwable $e) {
            // Silencioso para no interrumpir la ejecución UI
        }
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
