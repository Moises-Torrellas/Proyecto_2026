<?php

declare(strict_types=1);

namespace Tests\Acceptance\Inventario;

use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Interfaz y Aceptación con Codeception WebDriver
 * Módulo: Devoluciones (Inventario > Devoluciones)
 */
final class DevolucionesCest
{
    private const SECCION_MENU = 'Inventario';
    private const MODULO_HREF  = 'Devoluciones';

    // Selectores principales de la vista
    private const TITULO          = '#titulo';
    private const BUSQUEDA        = '#busqueda';
    private const RESULTADOS      = '#resultadoconsulta';
    private const BTN_NUEVO       = '#btn_nuevo';
    private const BTN_REPORTE     = '#generar';
    private const BTN_AYUDA       = '#ayuda';

    // Selectores del listado y acordeón grupal por atleta
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const LISTADO_ITEM      = '.listado_item';
    private const LISTADO_TITULO    = '.listado_titulo';
    private const LISTADO_DETALLE   = '.listado_detalle_oculto';
    private const TARJETA_RESUMEN   = '.tarjeta_resumen';
    private const SUB_ITEM_FILA     = '.sub_item_fila';
    private const SUB_ITEM_TITULO   = '.sub_item_titulo';
    private const SUB_ITEM_ACCIONES = '.sub_item_acciones';
    private const BTN_EDITAR        = 'button.cbt_v';
    private const BTN_ANULAR        = 'button.cbt_r';

    // Selectores del modal y formulario
    private const MODAL               = '#modal';
    private const MODAL_TITULO        = '#titulo_modal';
    private const MODAL_CERRAR        = '#cerrar_modal';
    private const MODAL_FORM          = '#f';
    private const INPUT_ID            = '#id_devolucion';
    private const SELECT_ASIGNACION   = '#id_asignacion';
    private const SELECT_ESTADO       = '#id_estado';
    private const INPUT_FECHA         = '#fecha_devolucion';
    private const INPUT_OBSERVACION   = '#observacion';
    private const BTN_GUARDAR         = '#btn_guardar';

    // Selectores de reporte
    private const ROW_ATLETA_REPORTE  = '#row_atleta_reporte';
    private const COL_FECHA_HASTA     = '#col_fecha_hasta';

    // Selectores de SweetAlert2
    private const SWAL_POPUP      = '.swal2-popup';
    private const SWAL_CONFIRM    = 'button.swal2-confirm';
    private const SWAL_CANCEL     = 'button.swal2-cancel';

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
     * CP-DV-01: Carga y Estructura Base del Módulo
     */
    public function probarCargaYEstructuraBase(AcceptanceTester $I): void
    {
        // 1. Expandir sección Inventario en el menú lateral
        $I->click($this->xpathSeccion(self::SECCION_MENU));
        $enlace = sprintf('a[href="%s"]', self::MODULO_HREF);
        $I->waitForElementVisible($enlace, 5);
        $I->see('Devoluciones', $enlace);
        $I->click($enlace);

        // 2. Verificar URL y Título principal
        $I->waitForText('Devoluciones', 10, self::TITULO);
        $I->seeInCurrentUrl(self::MODULO_HREF);

        // 3. Verificar controles principales de la interfaz
        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::RESULTADOS);

        // 4. Verificar renderizado del listado (grupos o estado vacío)
        $I->seeElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS . ' .listado_vacio');

        // 5. El modal debe existir en el DOM pero permanecer oculto inicialmente
        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 2: BÚSQUEDA Y FILTRO DINÁMICO EN TIEMPO REAL (RF-UI-02)
    // =========================================================================

    /**
     * CP-DV-02: Búsqueda con Coincidencias en Tiempo Real
     */
    public function probarBusquedaConCoincidencias(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayRegistros = $I->executeJS("return $('.listado_contenedor_grupal').length > 0;");
        if ($hayRegistros) {
            $nombreAtleta = $I->executeJS("return $('.listado_contenedor_grupal:first .listado_titulo').text().trim().split(' ')[0];");
            if (!empty($nombreAtleta)) {
                $I->fillField(self::BUSQUEDA, $nombreAtleta);
                $I->waitForText($nombreAtleta, 10, self::RESULTADOS);
                $I->seeElement(self::CONTENEDOR_GRUPAL);
            }
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-DV-03: Búsqueda sin coincidencias (filtro AJAX con debounce)
     */
    public function probarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, 'devolucion_inexistente_xyz_999');

        // Espera respuesta AJAX y renderizado de lista vacía
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-DV-04: Restablecer búsqueda al vaciar el input
     */
    public function probarRestablecerBusqueda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, '');

        // Debe restablecer la lista de devoluciones agrupadas
        $I->waitForElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS . ' .listado_vacio', 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 3: ACORDEÓN, MODAL Y VALIDACIONES CON DATOS ERRÓNEOS (RF-UI-03)
    // =========================================================================

    /**
     * CP-DV-05: Despliegue y Colapso del Acordeón Grupal por Atleta
     */
    public function probarDespliegueAcordeonDetalles(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayGrupos = $I->executeJS("return $('.listado_contenedor_grupal').length > 0;");
        if ($hayGrupos) {
            // Clic en la cabecera para desplegar detalle
            $I->click(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_ITEM);
            $I->waitForElementVisible(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_DETALLE, 5);
            $I->seeElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::TARJETA_RESUMEN);

            // Clic nuevamente para colapsar
            $I->click(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_ITEM);
            $I->waitForElementNotVisible(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_DETALLE, 5);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-DV-06: Apertura y Cierre del Modal de Registro
     */
    public function probarAperturaYCierreModal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        // Modal debe ser visible con su título y campos
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Registrar Devolución', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->seeElement(self::SELECT_ASIGNACION);
        $I->seeElement(self::SELECT_ESTADO);
        $I->seeElement(self::INPUT_FECHA);
        $I->see('Confirmar', self::BTN_GUARDAR);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-DV-07: Validación de Campos Obligatorios Vacíos al Registrar
     */
    public function probarValidacionCamposObligatoriosVacios(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Vaciar campos obligatorios
        $I->executeJS("
            $('#id_asignacion').val('').trigger('change');
            $('#id_estado').val('').trigger('change');
            $('#fecha_devolucion').val('');
        ");
        $I->click(self::BTN_GUARDAR);

        // SweetAlert2 de validación
        $I->waitForText('Complete los campos obligatorios.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-DV-08: Validación con Fecha Errónea o Vacía
     */
    public function probarValidacionFechaErronea(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Seleccionar asignación y estado pero dejar fecha vacía
        $this->seleccionarAsignacion($I, 1);
        $this->seleccionarEstadoFisico($I, 1);
        $I->executeJS("
            var fp = document.querySelector('#fecha_devolucion')._flatpickr;
            if (fp) fp.clear();
            $('#fecha_devolucion').val('');
        ");
        $I->click(self::BTN_GUARDAR);

        // SweetAlert2 de validación
        $I->waitForText('Complete los campos obligatorios.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-DV-09: Validación de Asignación no Seleccionada
     */
    public function probarValidacionAsignacionFaltante(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Fecha y estado seleccionados pero sin asignación
        $this->seleccionarEstadoFisico($I, 1);
        $this->establecerFecha($I, date('Y-m-d'));
        $I->executeJS("$('#id_asignacion').val('').trigger('change');");
        $I->click(self::BTN_GUARDAR);

        // SweetAlert2 de validación
        $I->waitForText('Complete los campos obligatorios.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 4: CICLO TRANSACCIONAL DE DEVOLUCIÓN (REGISTRO, EDICIÓN, ANULACIÓN) (RF-UI-04)
    // =========================================================================

    /**
     * CP-DV-10: Registro Exitoso de Devolución
     */
    public function probarRegistroExitoso(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Verificar si hay asignaciones activas disponibles para devolver
        $asig = $this->seleccionarAsignacion($I, 1);
        if (!empty($asig['id'])) {
            $this->seleccionarEstadoFisico($I, 1);
            $this->establecerFecha($I, date('Y-m-d'));
            $I->fillField(self::INPUT_OBSERVACION, 'Equipo entregado en buenas condiciones');

            $I->click(self::BTN_GUARDAR);

            // Alerta de éxito tras confirmación en backend
            $I->waitForText('Operación Exitosa', 10, self::SWAL_POPUP);
            $I->click('Entendido');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

            // Modal debe cerrarse y listado actualizarse
            $I->waitForElementNotVisible(self::MODAL, 5);
        } else {
            $I->click(self::MODAL_CERRAR);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-DV-11: Modificación de Datos de Devolución
     */
    public function probarModificacionDevolucion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayDevoluciones = $I->executeJS("return $('.sub_item_fila button.cbt_v').length > 0;");
        if ($hayDevoluciones) {
            $this->expandirPrimerAtleta($I);

            $I->waitForElementVisible(self::BTN_EDITAR, 5);
            $I->click(self::BTN_EDITAR);

            // Modal abre en modo modificación
            $I->waitForElementVisible(self::MODAL, 5);
            $I->waitForText('Modificar Devolución', 5, self::MODAL_TITULO);
            $I->see('Guardar Cambios', self::BTN_GUARDAR);

            // Cambiar observación o estado físico
            $this->seleccionarEstadoFisico($I, 2);
            $I->fillField(self::INPUT_OBSERVACION, 'Observación modificada en prueba automatizada');
            $I->click(self::BTN_GUARDAR);

            // Alerta de éxito
            $I->waitForText('Operación Exitosa', 10, self::SWAL_POPUP);
            $I->click('Entendido');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
            $I->waitForElementNotVisible(self::MODAL, 5);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-DV-12: Cancelación de Anulación de Devolución
     */
    public function probarCancelacionAnulacion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayDevoluciones = $I->executeJS("return $('.sub_item_fila button.cbt_r').length > 0;");
        if ($hayDevoluciones) {
            $this->expandirPrimerAtleta($I);

            $I->waitForElementVisible(self::BTN_ANULAR, 5);
            $I->click(self::BTN_ANULAR);

            // SweetAlert2 con prompt de motivo: pulsar "NO" para cancelar
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->click('NO');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

            // El botón y el registro siguen presentes
            $I->seeElement(self::BTN_ANULAR);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-DV-13: Anulación Exitosa de Devolución con Motivo
     */
    public function probarAnulacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayDevoluciones = $I->executeJS("return $('.sub_item_fila button.cbt_r').length > 0;");
        if ($hayDevoluciones) {
            $this->expandirPrimerAtleta($I);

            $I->waitForElementVisible(self::BTN_ANULAR, 5);
            $I->click(self::BTN_ANULAR);

            // Escribir motivo en el input de SweetAlert2 y confirmar con "SI"
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->fillField('.swal2-input', 'Anulación de prueba automatizada');
            $I->click('SI');

            // Alerta de éxito
            $I->waitForText('Operación Exitosa', 10, self::SWAL_POPUP);
            $I->click('Entendido');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        }

        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 5: MODAL DE REPORTES Y ASISTENCIA INTERACTIVA (RF-UI-05)
    // =========================================================================

    /**
     * CP-DV-14: Apertura y Configuración del Modal de Generar Reporte
     */
    public function probarModalGenerarReporte(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_REPORTE, 5);
        $I->click(self::BTN_REPORTE);

        // Modal debe abrirse configurado para reporte
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Generar Reporte de Devoluciones', 5, self::MODAL_TITULO);
        $I->see('Generar Reporte', self::BTN_GUARDAR);

        // Controles de reporte visibles, asignación y observación ocultos
        $I->seeElement(self::ROW_ATLETA_REPORTE);
        $I->dontSeeElement(self::SELECT_ASIGNACION);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-DV-15: Tour Guiado Interactivo de Asistencia (Driver.js)
     */
    public function probarTourGuiadoAyuda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_AYUDA, 5);
        $I->click(self::BTN_AYUDA);

        // Verificar que Driver.js inicia el popover interactivo
        $I->waitForElementVisible('.driver-popover', 5);
        $I->waitForText('Barra de Búsqueda', 5, '.driver-popover');

        // Cerrar tour guiado
        $I->executeJS("
            if (window.driver && typeof window.driver.destroy === 'function') {
                window.driver.destroy();
            } else {
                $('.driver-popover-close-btn').click();
            }
        ");

        $I->waitForElementNotVisible('.driver-popover', 5);
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

    private function xpathSeccion(string $nombre): string
    {
        return sprintf(
            '//li[contains(@class, "nav_identificador")][.//span[contains(@class, "identificador_texto") and contains(normalize-space(.), "%s")]]',
            $nombre
        );
    }

    private function seleccionarAsignacion(AcceptanceTester $I, int $index = 1): array
    {
        $res = $I->executeJS("
            var sel = $('#id_asignacion');
            var opt = sel.find('option:not([disabled])').eq(" . ($index - 1) . ");
            var val = opt.val();
            var text = opt.text();
            sel.val(val).trigger('change');
            return { id: val, texto: text.trim() };
        ");

        return is_array($res) ? $res : ['id' => '1', 'texto' => 'Pads (Diego Reinoso)'];
    }

    private function seleccionarEstadoFisico(AcceptanceTester $I, int $index = 1): array
    {
        $res = $I->executeJS("
            var sel = $('#id_estado');
            var opt = sel.find('option:not([disabled])').eq(" . ($index - 1) . ");
            var val = opt.val();
            var text = opt.text();
            sel.val(val).trigger('change');
            return { id: val, texto: text.trim() };
        ");

        return is_array($res) ? $res : ['id' => '1', 'texto' => 'Excelente'];
    }

    private function establecerFecha(AcceptanceTester $I, string $fecha): void
    {
        $I->executeJS(sprintf("
            var fp = document.querySelector('#fecha_devolucion')._flatpickr;
            if (fp) {
                fp.setDate('%s');
            } else {
                $('#fecha_devolucion').val('%s');
            }
        ", addslashes($fecha), addslashes($fecha)));
    }

    private function expandirPrimerAtleta(AcceptanceTester $I): void
    {
        $I->executeJS("
            var primerGrupo = $('.listado_contenedor_grupal').first();
            if (primerGrupo.length > 0) {
                primerGrupo.find('.listado_detalle_oculto').show();
            }
        ");
    }

    private function verificarSinErroresPhp(AcceptanceTester $I): void
    {
        foreach (self::TEXTOS_DE_ERROR as $texto) {
            $I->dontSee($texto);
        }
    }
}

