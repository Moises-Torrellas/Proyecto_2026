<?php

declare(strict_types=1);

namespace Tests\Acceptance\Inventario;

use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Interfaz y Aceptación con Codeception WebDriver
 * Módulo: Asignaciones (Inventario > Asignaciones)
 */
final class AsignacionesCest
{
    private const SECCION_MENU = 'Inventario';
    private const MODULO_HREF  = 'Asignaciones';

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
    private const MODAL                    = '#modal';
    private const MODAL_TITULO             = '#titulo_modal';
    private const MODAL_CERRAR             = '#cerrar_modal';
    private const MODAL_FORM               = '#f';
    private const INPUT_ID                 = '#id_asignacion';
    private const SELECT_ATLETA            = '#codigo_atleta';
    private const INPUT_FECHA              = '#fecha_asignacion';
    private const BTN_GUARDAR              = '#btn_guardar';

    // Sección de selección por lote (Inclusión)
    private const SECCION_LOTE             = '#seccion_lote_asig';
    private const SELECT_LOTE_ARTICULO     = '#lote_articulo';
    private const BTN_AGREGAR_LOTE         = '#btn_agregar_lote_asig';
    private const BTN_VACIAR_LOTE          = '#btn_vaciar_lote_asig';
    private const CONTADOR_LOTE            = '#contador_lote_asig';
    private const CUERPO_LOTE              = '#cuerpo_lote_asig';

    // Sección de modificación individual
    private const ROW_ARTICULO_MODIFICAR   = '#row_articulo_modificar';
    private const SELECT_ARTICULO_MODIFICAR= '#codigo_articulo';

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
     * CP-AS-01: Carga y Estructura Base del Módulo
     */
    public function probarCargaYEstructuraBase(AcceptanceTester $I): void
    {
        // 1. Expandir sección Inventario en el menú lateral
        $I->click($this->xpathSeccion(self::SECCION_MENU));
        $enlace = sprintf('a[href="%s"]', self::MODULO_HREF);
        $I->waitForElementVisible($enlace, 5);
        $I->see('Asignaciones', $enlace);
        $I->click($enlace);

        // 2. Verificar URL y Título principal
        $I->waitForText('Asignaciones', 10, self::TITULO);
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
     * CP-AS-02: Búsqueda con Coincidencias en Tiempo Real
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
     * CP-AS-03: Búsqueda sin coincidencias (filtro AJAX con debounce)
     */
    public function probarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, 'atleta_inexistente_xyz_999');

        // Espera respuesta AJAX y renderizado de lista vacía
        $I->waitForText('No se encontraron registros de asignaciones', 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AS-04: Restablecer búsqueda al vaciar el input
     */
    public function probarRestablecerBusqueda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, '');

        // Debe restablecer la lista de asignaciones agrupadas
        $I->waitForElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS . ' .listado_vacio', 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 3: ACORDEÓN, MODAL Y VALIDACIONES CON DATOS ERRÓNEOS (RF-UI-03)
    // =========================================================================

    /**
     * CP-AS-05: Despliegue y Colapso del Acordeón Grupal por Atleta
     */
    public function probarDespliegueAcordeonDetalles(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayGrupos = $I->executeJS("return $('.listado_contenedor_grupal').length > 0;");
        if ($hayGrupos) {
            // Clic en la cabecera del primer grupo para desplegar detalles
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
     * CP-AS-06: Apertura y Cierre del Modal de Registro
     */
    public function probarAperturaYCierreModal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        // Modal debe ser visible con su título y formulario configurado para lote
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Registrar Asignación', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->seeElement(self::SECCION_LOTE);
        $I->dontSeeElement(self::ROW_ARTICULO_MODIFICAR);
        $I->see('Confirmar Préstamo', self::BTN_GUARDAR);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AS-07: Validación con Fecha Errónea o Formato Inválido
     */
    public function probarValidacionFechaErronea(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Ingresar una fecha con formato erróneo
        $this->establecerFecha($I, 'fecha-invalida-999');
        $I->click(self::BTN_GUARDAR);

        // SweetAlert2 de validación de fecha
        $I->waitForText('La fecha de asignación es obligatoria y debe ser válida (AAAA-MM-DD).', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AS-08: Validación de Atleta Obligatorio al Registrar
     */
    public function probarValidacionAtletaObligatorio(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Fecha válida pero sin atleta seleccionado
        $this->establecerFecha($I, date('Y-m-d'));
        $I->executeJS("$('#codigo_atleta').val(null).trigger('change');");
        $I->click(self::BTN_GUARDAR);

        // SweetAlert2 de error de validación
        $I->waitForText('Debe seleccionar un atleta de la lista.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AS-09: Validación de Selección de Artículo Vacío al Agregar al Lote
     */
    public function probarValidacionArticuloVacioEnLote(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Clic en agregar al lote sin seleccionar artículo
        $I->click(self::BTN_AGREGAR_LOTE);

        // SweetAlert2 de validación
        $I->waitForText('Seleccione un artículo de la lista antes de agregarlo.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AS-10: Validación de Lote Vacío al Confirmar Préstamo
     */
    public function probarValidacionLoteVacio(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Seleccionar atleta pero dejar el lote de artículos vacío
        $this->seleccionarAtleta($I, 1);
        $this->establecerFecha($I, date('Y-m-d'));
        $I->click(self::BTN_GUARDAR);

        // SweetAlert2 de validación de lote vacío
        $I->waitForText('Debe agregar al menos un artículo a la lista de asignación.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 4: CICLO TRANSACCIONAL DE ASIGNACIÓN (LOTE, EDICIÓN, ANULACIÓN) (RF-UI-04)
    // =========================================================================

    /**
     * CP-AS-11: Gestión Dinámica del Lote de Asignación (Agregar y Vaciar)
     */
    public function probarGestionDinamicaLote(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Seleccionar artículo disponible y agregarlo
        $art = $this->seleccionarArticuloLote($I, 1);
        if (!empty($art['codigo'])) {
            $I->click(self::BTN_AGREGAR_LOTE);

            // Contador de artículos agregados incrementa
            $I->waitForText('1', 5, self::CONTADOR_LOTE);
            $I->seeElement(self::CUERPO_LOTE . ' .item_lote');

            // Vaciar la lista
            $I->click(self::BTN_VACIAR_LOTE);
            $I->waitForText('0', 5, self::CONTADOR_LOTE);
            $I->see('Aún no hay artículos seleccionados', self::CUERPO_LOTE);
        }

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AS-12: Registro Exitoso de Asignación por Lote con Diálogo de Confirmación
     */
    public function probarRegistroExitoso(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // 1. Seleccionar atleta y fecha
        $atleta = $this->seleccionarAtleta($I, 1);
        $this->establecerFecha($I, date('Y-m-d'));

        // 2. Agregar artículo disponible al lote
        $art = $this->seleccionarArticuloLote($I, 1);
        if (!empty($art['codigo'])) {
            $I->click(self::BTN_AGREGAR_LOTE);
            $I->waitForText('1', 5, self::CONTADOR_LOTE);

            // 3. Confirmar Préstamo
            $I->click(self::BTN_GUARDAR);

            // 4. Diálogo SweetAlert2 de confirmación
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->click('SI');

            // 5. Alerta de éxito
            $I->waitForText('Operación Exitosa', 10, self::SWAL_POPUP);
            $I->click('Entendido');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

            // 6. Modal cerrado y lista actualizada
            $I->waitForElementNotVisible(self::MODAL, 5);
        } else {
            $I->click(self::MODAL_CERRAR);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AS-13: Modificación Individual de Asignación
     */
    public function probarModificacionAsignacion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayAsignaciones = $I->executeJS("return $('.sub_item_fila button.cbt_v').length > 0;");
        if ($hayAsignaciones) {
            // Expandir el primer contenedor grupal para hacer visible el botón
            $this->expandirPrimerAtleta($I);

            $I->waitForElementVisible(self::BTN_EDITAR, 5);
            $I->click(self::BTN_EDITAR);

            // Modal se abre en modo edición individual
            $I->waitForElementVisible(self::MODAL, 5);
            $I->waitForText('Modificar Asignación', 5, self::MODAL_TITULO);
            $I->seeElement(self::ROW_ARTICULO_MODIFICAR);
            $I->dontSeeElement(self::SECCION_LOTE);
            $I->see('Guardar Cambios', self::BTN_GUARDAR);

            // Guardar cambios
            $I->click(self::BTN_GUARDAR);

            // Diálogo de confirmación SweetAlert2
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->click('SI');

            // Alerta de éxito
            $I->waitForText('Operación Exitosa', 10, self::SWAL_POPUP);
            $I->click('Entendido');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
            $I->waitForElementNotVisible(self::MODAL, 5);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AS-14: Cancelación y Ejecución de Anulación de Asignación
     */
    public function probarAnulacionAsignacion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayAsignaciones = $I->executeJS("return $('.sub_item_fila button.cbt_r').length > 0;");
        if ($hayAsignaciones) {
            $this->expandirPrimerAtleta($I);

            $I->waitForElementVisible(self::BTN_ANULAR, 5);

            // 1. Intentar anular pero CANCELAR
            $I->click(self::BTN_ANULAR);
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->waitForText('¿Está seguro que quiere anular esta asignación y liberar el artículo?', 5, self::SWAL_POPUP);
            $I->click('NO');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

            // El botón y el registro siguen existiendo
            $I->seeElement(self::BTN_ANULAR);

            // 2. Ejecutar anulación confirmada
            $I->click(self::BTN_ANULAR);
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->click('SI');

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
     * CP-AS-15: Apertura y Configuración del Modal de Generar Reporte
     */
    public function probarModalGenerarReporte(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_REPORTE, 5);
        $I->click(self::BTN_REPORTE);

        // Modal debe abrirse configurado para reporte
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Generar Reporte', 5, self::MODAL_TITULO);
        $I->see('Generar Reporte', self::BTN_GUARDAR);

        // Campos de registro ocultos, campos de reporte visibles
        $I->dontSeeElement(self::SELECT_ATLETA);
        $I->dontSeeElement(self::SECCION_LOTE);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AS-16: Tour Guiado Interactivo de Asistencia (Driver.js)
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

    private function seleccionarAtleta(AcceptanceTester $I, int $index = 1): array
    {
        $res = $I->executeJS("
            var sel = $('#codigo_atleta');
            var opt = sel.find('option:not([disabled])').eq(" . ($index - 1) . ");
            var val = opt.val();
            var text = opt.text();
            sel.val(val).trigger('change');
            return { codigo: val, nombre: text.trim() };
        ");

        return is_array($res) ? $res : ['codigo' => '1', 'nombre' => 'Diego Reinoso'];
    }

    private function seleccionarArticuloLote(AcceptanceTester $I, int $index = 1): array
    {
        $res = $I->executeJS("
            var sel = $('#lote_articulo');
            var opt = sel.find('option:not([disabled])').eq(" . ($index - 1) . ");
            var val = opt.val();
            var text = opt.text();
            sel.val(val).trigger('change');
            return { codigo: val, nombre: text.trim() };
        ");

        return is_array($res) ? $res : ['codigo' => '1', 'nombre' => 'Pads'];
    }

    private function establecerFecha(AcceptanceTester $I, string $fecha): void
    {
        $I->executeJS(sprintf("
            var fp = document.querySelector('#fecha_asignacion')._flatpickr;
            if (fp) {
                fp.setDate('%s');
            } else {
                $('#fecha_asignacion').val('%s');
            }
        ", addslashes($fecha), addslashes($fecha)));
    }

    private function expandirPrimerAtleta(AcceptanceTester $I): void
    {
        $I->executeJS("
            var primerGrupo = $('.listado_contenedor_grupal').first();
            if (primerGrupo.length > 0) {
                primerGrupo.addClass('expandido');
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

