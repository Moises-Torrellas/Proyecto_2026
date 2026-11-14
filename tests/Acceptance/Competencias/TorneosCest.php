<?php

declare(strict_types=1);

namespace Tests\Acceptance\Competencias;

use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Interfaz y Aceptación con Codeception WebDriver
 * Módulo: Torneos (Competencias > Torneos)
 */
final class TorneosCest
{
    private const SECCION_MENU = 'Competencias';
    private const MODULO_HREF  = 'Torneos';

    // Selectores principales de la vista
    private const TITULO          = '#titulo';
    private const BUSQUEDA        = '#busqueda';
    private const RESULTADOS      = '#resultadoconsulta';
    private const BTN_NUEVO       = '#incluir';
    private const BTN_REPORTE     = '#generar';

    // Selectores del modal y formulario
    private const MODAL               = '#modal';
    private const MODAL_TITULO        = '#titulo_modal';
    private const MODAL_CERRAR        = '#cerrar_modal';
    private const MODAL_FORM          = '#f';
    private const INPUT_NOMBRE        = '#nombre';
    private const SELECT_ESTATUS      = '#estatus';
    private const INPUT_FECHA_INICIO  = '#fecha_inicio';
    private const INPUT_FECHA_FIN     = '#fecha_fin';
    private const INPUT_UBICACION     = '#ubicacion';
    private const BTN_PROCESO         = '#proceso';

    // Selectores de SweetAlert2
    private const SWAL_POPUP      = '.swal2-popup';
    private const SWAL_CONFIRM    = 'button.swal2-confirm';
    private const SWAL_CANCEL     = 'button.swal2-cancel';

    // Datos para pruebas CRUD
    private const NOMBRE_TEST       = 'Torneo Apertura Ui Test';
    private const NOMBRE_MODIFICAR  = 'Torneo Apertura Ui Modificado';
    private const UBICACION_ORIGINAL = 'Estadio Principal Barquisimeto';
    private const UBICACION_NUEVA    = 'Polideportivo Máximo Viloria';

    /** Textos que indican fallos críticos en el backend si aparecen en pantalla */
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
            $I->waitForElement('input[name="cedula"]', 10);
            $I->fillField('cedula', '12345678');
            $I->fillField('contraseña', 'Admin456$');
            $I->click('Iniciar Sesión');
            $I->waitForText('Inicio Validado', 10);
            $I->saveSessionSnapshot('login');
        }

        $I->waitForText('Panel de Control', 10);
        $I->waitForElementVisible('.nav_lateral', 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 1: NAVEGACIÓN Y RENDERIZADO DE INTERFAZ (RF-UI-01)
    // =========================================================================

    /**
     * CP-TR-01: Carga y Estructura Base del Módulo
     */
    public function probarCargaYEstructuraBase(AcceptanceTester $I): void
    {
        // 1. Expandir la sección Competencias en el menú lateral
        $I->click($this->xpathSeccion(self::SECCION_MENU));
        $enlace = sprintf('a[href="%s"]', self::MODULO_HREF);
        $I->waitForElementVisible($enlace, 5);
        $I->see('Torneos', $enlace);
        $I->click($enlace);

        // 2. Verificar URL y Título principal
        $I->waitForText('Torneos', 10, self::TITULO);
        $I->seeInCurrentUrl(self::MODULO_HREF);

        // 3. Verificar controles principales de la interfaz
        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::RESULTADOS);

        // 4. Verificar renderizado del listado (registros o listado vacío)
        $I->seeElement(self::RESULTADOS . ' .listado_item, ' . self::RESULTADOS . ' .listado_vacio');

        // 5. El modal debe existir en el DOM pero permanecer oculto inicialmente
        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 2: BÚSQUEDA Y FILTRO DINÁMICO VIA AJAX (RF-UI-02)
    // =========================================================================

    /**
     * CP-TR-02: Búsqueda con Coincidencias en Tiempo Real
     */
    public function probarBusquedaConCoincidencias(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayRegistros = $I->executeJS("return $('.listado_item').length > 0;");
        if ($hayRegistros) {
            $nombreTorneo = $I->executeJS("return $('.listado_item:first .listado_titulo').text().trim().split(' ')[0];");
            if (!empty($nombreTorneo)) {
                $I->fillField(self::BUSQUEDA, $nombreTorneo);
                $I->waitForText($nombreTorneo, 10, self::RESULTADOS);
                $I->seeElement(self::RESULTADOS . ' .listado_item');
            }
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-TR-03: Búsqueda sin coincidencias (filtro AJAX)
     */
    public function probarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, 'torneo_inexistente_xyz_999');

        // Espera respuesta AJAX y renderizado de lista vacía
        $I->waitForText('No se encontraron torneos registrados', 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-TR-04: Restablecer búsqueda al vaciar el input
     */
    public function probarRestablecerBusqueda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, '');

        // Debe restablecer la lista de torneos
        $I->waitForElement(self::RESULTADOS . ' .listado_item, ' . self::RESULTADOS . ' .listado_vacio', 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 3: COMPORTAMIENTO DEL MODAL Y VALIDACIONES FRONTEND (RF-UI-03)
    // =========================================================================

    /**
     * CP-TR-05: Apertura y Cierre del Modal de Registro
     */
    public function probarAperturaYCierreModal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        // Modal debe ser visible con su título y formulario
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Registrar Nuevo Torneo', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->see('Registrar Torneo', self::BTN_PROCESO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-TR-06: Validación de Estatus Vacío al Intentar Registrar
     */
    public function probarValidacionEstatusVacio(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Intentar registrar sin seleccionar estatus
        $I->click(self::BTN_PROCESO);

        // SweetAlert de error de validación de estatus
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->see('Tiene que elegir el estatus del torneo', self::SWAL_POPUP);

        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->click(self::MODAL_CERRAR);
    }

    /**
     * CP-TR-07: Validación de Nombre Vacío o Inválido
     */
    public function probarValidacionNombreInvalido(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Seleccionar estatus pero dejar nombre vacío
        $I->selectOption(self::SELECT_ESTATUS, '1'); // 1 = Por Disputarse
        $I->click(self::BTN_PROCESO);

        // SweetAlert de error de validación de nombre
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->see('Debe ingresar un nombre de torneo válido', self::SWAL_POPUP);

        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->click(self::MODAL_CERRAR);
    }

    /**
     * CP-TR-08: Validación con Datos Erróneos en Ubicación (< 5 caracteres o inválida)
     */
    public function probarValidacionUbicacionErronea(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        $I->selectOption(self::SELECT_ESTATUS, '1');
        $I->fillField(self::INPUT_NOMBRE, 'Torneo Ubicación Inválida');
        $this->establecerFechas($I, date('Y-m-d'), date('Y-m-d', strtotime('+7 days')));

        // Ingresar ubicación con datos erróneos (< 5 caracteres)
        $I->fillField(self::INPUT_UBICACION, 'Gym');
        $I->click(self::BTN_PROCESO);

        // SweetAlert de error de validación de ubicación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->see('Debe ingresar una ubicación válida', self::SWAL_POPUP);

        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-TR-09: Validación de Incoherencia Cronológica de Fechas
     */
    public function probarValidacionIncoherenciaFechas(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        $I->selectOption(self::SELECT_ESTATUS, '1');
        $I->fillField(self::INPUT_NOMBRE, 'Torneo Fechas Incoherentes');

        // Configurar fecha de inicio POSTERIOR a fecha de fin
        $this->establecerFechas($I, '2026-12-20', '2026-12-10');

        $I->fillField(self::INPUT_UBICACION, 'Gimnasio Los Horcones');
        $I->click(self::BTN_PROCESO);

        // SweetAlert de error de consistencia cronológica
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->see('La fecha de inicio no puede ser posterior a la fecha de finalización', self::SWAL_POPUP);

        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->click(self::MODAL_CERRAR);
    }

    /**
     * CP-TR-08: Validación de Ubicación Vacía o Inválida
     */
    public function probarValidacionUbicacionVacia(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        $I->selectOption(self::SELECT_ESTATUS, '1');
        $I->fillField(self::INPUT_NOMBRE, 'Torneo Sin Ubicacion');
        $this->establecerFechas($I, '2026-11-15', '2026-11-20');

        // Ubicación vacía
        $I->fillField(self::INPUT_UBICACION, '');
        $I->click(self::BTN_PROCESO);

        // SweetAlert de error de ubicación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->see('Debe ingresar una ubicación válida', self::SWAL_POPUP);

        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->click(self::MODAL_CERRAR);
    }

    // =========================================================================
    // ESCENARIO 4: CICLO DE VIDA DEL REGISTRO (CRUD COMPLETO EN UI) (RF-UI-04)
    // =========================================================================

    /**
     * CP-TR-09: Registro Exitoso con Diálogo de Confirmación
     */
    public function probarRegistroExitoso(AcceptanceTester $I): void
    {
        $this->limpiarRegistrosResiduales($I);
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Llenar formulario completo
        $I->selectOption(self::SELECT_ESTATUS, '1'); // Por Disputarse
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $this->establecerFechas($I, '2026-11-20', '2026-11-25');
        $I->fillField(self::INPUT_UBICACION, self::UBICACION_ORIGINAL);
        $I->click(self::BTN_PROCESO);

        // Diálogo SweetAlert de confirmación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere registrar este torneo?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito (esperar respuesta AJAX)
        $I->waitForText('Registro Exitoso', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Modal debe cerrarse y el nuevo torneo debe listarse
        $I->waitForElementNotVisible(self::MODAL, 5);
        $I->waitForText(self::NOMBRE_TEST, 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-TR-10: Control de Duplicados en Interfaz (Unicidad de Nombre)
     */
    public function probarControlDeDuplicados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Reintentar registrar con el mismo nombre ya guardado
        $I->selectOption(self::SELECT_ESTATUS, '1');
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $this->establecerFechas($I, '2026-11-20', '2026-11-25');
        $I->fillField(self::INPUT_UBICACION, self::UBICACION_ORIGINAL);
        $I->click(self::BTN_PROCESO);

        // Confirmar en SweetAlert
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere registrar este torneo?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Mensaje de error por duplicidad (esperar respuesta AJAX)
        $I->waitForText('Ya existe un torneo registrado con este nombre.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-TR-11: Modificación Exitosa de Torneo
     */
    public function probarModificacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // Localizar el botón de modificar del torneo creado
        $xpathBotonModificar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_v")]',
            self::NOMBRE_TEST
        );
        $I->waitForElementVisible($xpathBotonModificar, 10);
        $I->click($xpathBotonModificar);

        // Esperar modal de modificación y comprobación de datos
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Modificar Torneo', 5, self::MODAL_TITULO);
        $I->seeInField(self::INPUT_NOMBRE, self::NOMBRE_TEST);

        // Modificar datos
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_MODIFICAR);
        $I->fillField(self::INPUT_UBICACION, self::UBICACION_NUEVA);
        $I->click(self::BTN_PROCESO);

        // Confirmar modificación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere modificar este torneo?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito (esperar respuesta AJAX)
        $I->waitForText('Modificación Exitosa', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Verificar actualización en la vista
        $I->waitForElementNotVisible(self::MODAL, 5);
        $I->waitForText(self::NOMBRE_MODIFICAR, 10, self::RESULTADOS);
        $I->see(self::UBICACION_NUEVA, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-TR-12: Cancelación de Eliminación de Torneo
     */
    public function probarCancelacionDeEliminacion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // Localizar el botón de eliminar
        $xpathBotonEliminar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_r")]',
            self::NOMBRE_MODIFICAR
        );
        $I->waitForElementVisible($xpathBotonEliminar, 10);
        $I->click($xpathBotonEliminar);

        // Diálogo SweetAlert: pulsar "NO"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere eliminar este torneo?', 5, self::SWAL_POPUP);
        $I->click('NO');

        // El registro sigue visible en el listado
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->see(self::NOMBRE_MODIFICAR, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-TR-13: Eliminación Exitosa de Torneo
     */
    public function probarEliminacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // Localizar el botón de eliminar
        $xpathBotonEliminar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_r")]',
            self::NOMBRE_MODIFICAR
        );
        $I->waitForElementVisible($xpathBotonEliminar, 10);
        $I->click($xpathBotonEliminar);

        // Confirmar con "SI"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere eliminar este torneo?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito (esperar respuesta AJAX)
        $I->waitForText('Eliminación Exitosa', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // El torneo desaparece de la vista (base de datos limpia)
        $I->waitForElementNotVisible($xpathBotonEliminar, 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 5: MODAL DE REPORTES (RF-UI-05)
    // =========================================================================

    /**
     * CP-TR-14: Apertura y Configuración del Modal de Generar Reporte
     */
    public function probarModalGenerarReporte(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_REPORTE, 5);
        $I->click(self::BTN_REPORTE);

        // Modal debe abrirse configurado para reporte
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Generar Reporte', 5, self::MODAL_TITULO);
        $I->see('Generar Reporte', self::BTN_PROCESO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    private function irAlModulo(AcceptanceTester $I): void
    {
        $I->amOnPage('/' . self::MODULO_HREF);
        $I->waitForElementVisible(self::TITULO, 10);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * Limpia registros residuales generados por pruebas previas interrumpidas
     */
    private function limpiarRegistrosResiduales(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        foreach ([self::NOMBRE_TEST, self::NOMBRE_MODIFICAR] as $nombre) {
            $hayResidual = $I->executeJS("
                var encontrado = false;
                document.querySelectorAll('.listado_item span').forEach(function(el) {
                    if (el.textContent.trim() === '{$nombre}') {
                        encontrado = true;
                    }
                });
                return encontrado;
            ");

            if ($hayResidual) {
                $xpathBotonEliminar = sprintf(
                    '//div[contains(@class, "listado_item")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_r")]',
                    $nombre
                );
                $I->click($xpathBotonEliminar);
                $I->waitForElementVisible(self::SWAL_POPUP, 5);
                $I->click('SI');
                $I->waitForText('Eliminación Exitosa', 10, self::SWAL_POPUP);
                $I->click('Entendido');
                $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
                $I->waitForElementNotVisible($xpathBotonEliminar, 10);
            }
        }
    }

    private function xpathSeccion(string $nombre): string
    {
        return sprintf(
            '//li[contains(@class, "nav_identificador")][.//span[contains(@class, "identificador_texto") and contains(normalize-space(.), "%s")]]',
            $nombre
        );
    }

    private function establecerFechas(AcceptanceTester $I, string $fechaInicio, string $fechaFin): void
    {
        $I->executeJS(sprintf("
            var fi = document.querySelector('#fecha_inicio');
            if (fi && fi._flatpickr) { fi._flatpickr.setDate('%s'); } else if (fi) { fi.value = '%s'; }
            var ff = document.querySelector('#fecha_fin');
            if (ff && ff._flatpickr) { ff._flatpickr.setDate('%s'); } else if (ff) { ff.value = '%s'; }
        ", $fechaInicio, $fechaInicio, $fechaFin, $fechaFin));
    }

    private function verificarSinErroresPhp(AcceptanceTester $I): void
    {
        foreach (self::TEXTOS_DE_ERROR as $texto) {
            $I->dontSee($texto);
        }
    }
}

