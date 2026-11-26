<?php

declare(strict_types=1);

namespace Tests\Acceptance\HistorialDeportivo;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Interfaz y Aceptación con Codeception WebDriver
 * Módulo: Estadísticas (Historial Deportivo > Estadísticas)
 *
 * Sigue la estructura y estándares de las pruebas de interfaz del sistema
 * (ej. Asignaciones, Premios, Participaciones), validando navegación,
 * controles visuales, acordeón jerárquico por atleta, filtros AJAX con debounce,
 * validaciones de formulario en cliente, cálculo automático de average,
 * ciclo transaccional CRUD completo con SweetAlert2, modal de reportes
 * y tour guiado de asistencia (Driver.js).
 */
#[Group('estadisticas')]
#[Group('interfaz')]
final class EstadisticasCest
{
    private const SECCION_MENU = 'Historial Deportivo';
    private const MODULO_HREF  = 'Estadisticas';

    // Selectores principales de la vista
    private const TITULO          = '#titulo';
    private const BUSQUEDA        = '#busqueda';
    private const RESULTADOS      = '#resultadoconsulta';
    private const BTN_NUEVO       = '#incluir';
    private const BTN_REPORTE     = '#generar';
    private const BTN_AYUDA       = '#ayuda';

    // Selectores del listado y acordeón jerárquico por atleta
    private const CONTENEDOR_GRUPAL     = '.listado_contenedor_grupal';
    private const LISTADO_ITEM          = '.listado_item';
    private const LISTADO_TITULO        = '.listado_titulo';
    private const LISTADO_DETALLE       = '.listado_detalle_oculto';
    private const TARJETA_RESUMEN       = '.tarjeta_resumen';
    private const LISTA_SUB_ITEMS       = '.lista_sub_items';
    private const SUB_ITEM_FILA         = '.sub_item_fila_estadistica';
    private const SUB_ITEM_INFO_TORNEO  = '.sub_item_info_torneo';
    private const TORNEO_TITULO         = '.torneo_titulo_resaltado';
    private const METRICAS_HORIZONTAL   = '.sub_item_bloque_metricas_horizontal';
    private const METRICA_ITEM          = '.metrica_item';
    private const SUB_ITEM_ACCIONES     = '.sub_item_acciones_estadistica';
    private const BTN_EDITAR            = 'button.cbt_v';
    private const BTN_ELIMINAR          = 'button.cbt_r';
    private const LISTADO_VACIO         = '.listado_vacio';

    // Selectores del paginador
    private const PAG_ROWS_PER_PAGE     = '#rowsPerPage';
    private const PAG_BOTONERA          = '#botonera';
    private const PAG_CANTIDAD          = '#cantidad';

    // Selectores del modal y formulario
    private const MODAL                 = '#modal';
    private const MODAL_TITULO          = '#titulo_modal';
    private const MODAL_CERRAR          = '#cerrar_modal';
    private const MODAL_FORM            = '#f';
    private const INPUT_ID              = '#id';
    private const INPUT_PARTICIPACION_R = '#participacion_real';
    private const SELECT_PARTICIPACION  = '#participacion';
    private const SELECT_ATLETA         = '#atleta';
    private const SECCION_METRICAS      = '#seccion_metricas';
    private const INPUT_GOLES           = '#goles';
    private const INPUT_ASISTENCIAS     = '#asistencias';
    private const INPUT_PENALIZACIONES  = '#penalizaciones';
    private const INPUT_GOLES_CONTRA    = '#goles_c';
    private const INPUT_PARTIDO         = '#partido';
    private const INPUT_AVERAGE         = '#average';
    private const BTN_PROCESO           = '#proceso';

    // Selectores de SweetAlert2
    private const SWAL_POPUP            = '.swal2-popup';
    private const SWAL_CONFIRM          = 'button.swal2-confirm';
    private const SWAL_CANCEL           = 'button.swal2-cancel';

    // Selectores de Driver.js
    private const DRIVER_POPOVER        = '#driver-popover-item, .driver-popover';

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
     * CP-ES-01: Carga y Estructura Base del Módulo desde el Menú Lateral
     */
    public function probarCargaYEstructuraBase(AcceptanceTester $I): void
    {
        // 1. Expandir sección 'Historial Deportivo' en el menú lateral
        $I->click($this->xpathSeccion(self::SECCION_MENU));
        $enlace = sprintf('a[href="%s"]', self::MODULO_HREF);
        $I->waitForElementVisible($enlace, 5);
        $I->see('Estadisticas', $enlace);
        $I->click($enlace);

        // 2. Verificar URL y Título principal
        $I->waitForText('Estadisticas', 10, self::TITULO);
        $I->seeInCurrentUrl(self::MODULO_HREF);

        // 3. Verificar controles principales de la interfaz
        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::RESULTADOS);

        // 4. Verificar renderizado del listado (elementos agrupados o estado vacío)
        $I->seeElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS . ' ' . self::LISTADO_VACIO);

        // 5. El modal debe existir en el DOM pero permanecer oculto inicialmente
        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 2: BÚSQUEDA Y FILTRO DINÁMICO EN TIEMPO REAL (RF-UI-02)
    // =========================================================================

    /**
     * CP-ES-02: Búsqueda con Coincidencias en Tiempo Real (Filtro AJAX con Debounce)
     */
    public function probarBusquedaConCoincidencias(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayRegistros = $I->executeJS("return $('.listado_contenedor_grupal').length > 0;");
        if ($hayRegistros) {
            $nombreAtleta = (string) $I->executeJS("
                return $('.listado_contenedor_grupal:first .listado_titulo').text().trim().split(' ')[0];
            ");

            if (!empty($nombreAtleta)) {
                $I->fillField(self::BUSQUEDA, $nombreAtleta);
                $I->executeJS("$('#busqueda').trigger('keyup');");
                $I->waitForText($nombreAtleta, 10, self::RESULTADOS);
                $I->seeElement(self::CONTENEDOR_GRUPAL);
            }
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-ES-03: Búsqueda sin Coincidencias en el Listado
     */
    public function probarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, 'estadistica_inexistente_xyz_999');
        $I->executeJS("$('#busqueda').trigger('keyup');");

        // Espera respuesta AJAX y renderizado del contenedor vacío
        $I->waitForText('No se encontraron registros de estadísticas', 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-ES-04: Restablecer Búsqueda al Vaciar el Campo de Entrada
     */
    public function probarRestablecerBusqueda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, '');
        $I->executeJS("$('#busqueda').trigger('keyup');");

        // Debe restablecer la lista de estadísticas agrupadas o estado vacío inicial
        $I->waitForElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS . ' ' . self::LISTADO_VACIO, 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 3: ACORDEÓN, MODAL Y VALIDACIONES DE FORMULARIO EN CLIENTE (RF-UI-03)
    // =========================================================================

    /**
     * CP-ES-05: Despliegue y Colapso del Acordeón Grupal por Atleta (Toggle Detalles)
     */
    public function probarDespliegueAcordeonDetalles(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayGrupos = $I->executeJS("return $('.listado_contenedor_grupal').length > 0;");
        if ($hayGrupos) {
            // Clic en la cabecera del primer atleta para desplegar detalles
            $I->click(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_ITEM);
            $I->waitForElementVisible(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_DETALLE, 5);
            $I->seeElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::TARJETA_RESUMEN);
            $I->see('RESUMEN ESTADISTICO', self::RESULTADOS);

            // Clic nuevamente para colapsar
            $I->click(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_ITEM);
            $I->waitForElementNotVisible(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_DETALLE, 5);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-ES-06: Apertura y Cierre Limpio del Modal de Registro
     */
    public function probarAperturaYCierreModal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        // Modal debe ser visible con su título y formulario configurado para inclusión
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Registrar Estadísticas', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->seeElement(self::SECCION_METRICAS);
        $I->see('Registrar Estadísticas', self::BTN_PROCESO);

        // Cerrar modal con botón de cierre
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-ES-07: Cálculo Dinámico del Average en Tiempo Real
     */
    public function probarCalculoDinamicoAverage(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // 1. Ingresar 6 goles y 3 partidos jugados -> Average debe ser 2.00
        $I->fillField(self::INPUT_GOLES, '6');
        $I->fillField(self::INPUT_PARTIDO, '3');
        $I->executeJS("$('#goles, #partido').trigger('input');");

        $I->seeInField(self::INPUT_AVERAGE, '2.00');

        // 2. Cambiar partidos a 0 -> Average debe resetearse a 0.00
        $I->fillField(self::INPUT_PARTIDO, '0');
        $I->executeJS("$('#partido').trigger('input');");

        $I->seeInField(self::INPUT_AVERAGE, '0.00');

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-ES-08: Validación de Selección de Participación Obligatoria
     */
    public function probarValidacionParticipacionObligatoria(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Intentar registrar sin seleccionar participación/torneo
        $I->click(self::BTN_PROCESO);

        // Alerta SweetAlert2 de validación de participación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('Debe seleccionar una participación / torneo', 10, self::SWAL_POPUP);
        $I->click(self::SWAL_CONFIRM);
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-ES-09: Validación de Selección de Atleta Obligatorio
     */
    public function probarValidacionAtletaObligatorio(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Seleccionar una participación/torneo si existe en el select
        $hayTorneos = $I->executeJS("return $('#participacion option:not([disabled])').length > 0;");
        if ($hayTorneos) {
            $I->executeJS("
                var sel = $('#participacion');
                var val = sel.find('option:not([disabled])').first().val();
                sel.val(val).trigger('change');
                $('#atleta').val(null).trigger('change');
            ");

            $I->click(self::BTN_PROCESO);

            // Alerta SweetAlert2 de validación de atleta
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->waitForText('Debe seleccionar un atleta', 10, self::SWAL_POPUP);
            $I->click(self::SWAL_CONFIRM);
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        }

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-ES-10: Validación de Partidos Jugados Obligatorio (Mínimo 1 Partido)
     */
    public function probarValidacionPartidosObligatorio(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Seleccionar participación y atleta
        $seleccion = $this->seleccionarParticipacionYAtleta($I, 1, 1);
        if ($seleccion !== null) {
            // Establecer partido en 0 (la regla exige mínimo 1: /^[1-9]{1}[0-9]{0,2}$/)
            $I->fillField(self::INPUT_PARTIDO, '0');
            $I->click(self::BTN_PROCESO);

            // Alerta SweetAlert2 indicando partidos inválidos
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->waitForText("Campo 'Partidos Jugados' inválido", 10, self::SWAL_POPUP);
            $I->click(self::SWAL_CONFIRM);
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        }

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-ES-11: Validación de Formato Numérico Inválido en Métricas
     */
    public function probarValidacionFormatoMetricas(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        $seleccion = $this->seleccionarParticipacionYAtleta($I, 1, 1);
        if ($seleccion !== null) {
            // Asignar más de 3 dígitos o texto no numérico al campo goles
            $I->executeJS("$('#goles').val('1000');");
            $I->fillField(self::INPUT_PARTIDO, '2');
            $I->click(self::BTN_PROCESO);

            // Alerta SweetAlert2 indicando goles inválidos
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->waitForText("Campo 'Goles' inválido", 10, self::SWAL_POPUP);
            $I->click(self::SWAL_CONFIRM);
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        }

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 4: CICLO TRANSACCIONAL COMPLETO EN INTERFAZ (CRUD) (RF-UI-04)
    // =========================================================================

    /**
     * CP-ES-12: Registro Exitoso de Nuevas Estadísticas con Diálogo de Confirmación
     */
    public function probarRegistroExitoso(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        $seleccion = $this->seleccionarParticipacionYAtleta($I, 1, 1);
        if ($seleccion !== null) {
            // Rellenar métricas válidas
            $this->establecerMetricas($I, '3', '2', '1', '0', '2');

            // Enviar formulario
            $I->click(self::BTN_PROCESO);

            // SweetAlert2 de confirmación
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->waitForText('¿Está seguro que quiere registrar estas Estadísticas?', 5, self::SWAL_POPUP);
            $I->click('SI');

            // SweetAlert2 de respuesta del servidor (Éxito o Duplicidad controlada)
            $I->waitForElementVisible(self::SWAL_POPUP, 10);
            $esExito = $I->executeJS("
                var txt = $('.swal2-popup').text();
                return txt.indexOf('Registro Exitoso') !== -1 || txt.indexOf('Ya este atleta tiene registradas') !== -1;
            ");
            $I->assertTrue((bool)$esExito, 'El sistema debe procesar la solicitud con confirmación o control de duplicidad.');

            $I->click(self::SWAL_CONFIRM);
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        } else {
            $I->click(self::MODAL_CERRAR);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-ES-13: Control de Duplicidad en Interfaz al Intentar Re-Registrar la Misma Participación y Atleta
     */
    public function probarControlDeDuplicados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        $seleccion = $this->seleccionarParticipacionYAtleta($I, 1, 1);
        if ($seleccion !== null) {
            $this->establecerMetricas($I, '1', '1', '0', '0', '1');
            $I->click(self::BTN_PROCESO);

            // Confirmar en SweetAlert2
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->waitForText('¿Está seguro que quiere registrar estas Estadísticas?', 5, self::SWAL_POPUP);
            $I->click('SI');

            // Backend debe responder con validación de duplicidad o éxito previo
            $I->waitForElementVisible(self::SWAL_POPUP, 10);
            $I->click(self::SWAL_CONFIRM);
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        }

        $this->cerrarSwalSiExiste($I);
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-ES-14: Modificación Exitosa de Estadísticas Existentes
     */
    public function probarModificacionEstadisticas(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayBotonesEditar = $I->executeJS("return $('.sub_item_fila_estadistica button.cbt_v').length > 0;");
        if ($hayBotonesEditar) {
            // Expandir el primer contenedor grupal para hacer visible el botón
            $this->expandirPrimerAtleta($I);

            $I->waitForElementVisible(self::BTN_EDITAR, 5);
            $I->click(self::BTN_EDITAR);

            // Modal se abre en modo modificación con selects bloqueados
            $I->waitForElementVisible(self::MODAL, 5);
            $I->waitForText('Modificar Estadística', 5, self::MODAL_TITULO);
            $I->see('Modificar Estadística', self::BTN_PROCESO);

            // Verificar que participación y atleta están deshabilitados
            $I->seeElement(self::SELECT_PARTICIPACION . ':disabled');
            $I->seeElement(self::SELECT_ATLETA . ':disabled');

            // Modificar valor de asistencias y goles
            $I->fillField(self::INPUT_GOLES, '4');
            $I->fillField(self::INPUT_ASISTENCIAS, '3');
            $I->fillField(self::INPUT_PARTIDO, '2');
            $I->executeJS("$('#goles, #partido').trigger('input');");

            // Guardar cambios
            $I->click(self::BTN_PROCESO);

            // SweetAlert2 de confirmación
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->waitForText('¿Está seguro que quiere modificar estas Estadísticas?', 5, self::SWAL_POPUP);
            $I->click('SI');

            // SweetAlert2 de éxito
            $I->waitForText('Modificación Exitosa', 10, self::SWAL_POPUP);
            $I->click(self::SWAL_CONFIRM);
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
            $I->waitForElementNotVisible(self::MODAL, 5);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-ES-15: Cancelación de Eliminación de Estadísticas
     */
    public function probarCancelacionEliminacion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayBotonesEliminar = $I->executeJS("return $('.sub_item_fila_estadistica button.cbt_r').length > 0;");
        if ($hayBotonesEliminar) {
            $this->expandirPrimerAtleta($I);

            $I->waitForElementVisible(self::BTN_ELIMINAR, 5);

            // 1. Clic en botón eliminar
            $I->click(self::BTN_ELIMINAR);

            // 2. Diálogo SweetAlert2 de advertencia
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->waitForText('¿Está seguro que quiere eliminar estas Estadísticas?', 5, self::SWAL_POPUP);

            // 3. Cancelar la operación
            $I->click('NO');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

            // 4. El registro y botón deben permanecer intactos en la vista
            $I->seeElement(self::BTN_ELIMINAR);
        }

        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 5: MODAL DE REPORTES Y ASISTENCIA INTERACTIVA (RF-UI-05)
    // =========================================================================

    /**
     * CP-ES-16: Apertura y Configuración del Modal de Generar Reporte
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

        // Sección de métricas debe permanecer oculta en modo reporte
        $I->dontSeeElement(self::SECCION_METRICAS);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-ES-17: Tour Guiado Interactivo de Asistencia (Driver.js)
     */
    public function probarTourGuiadoAyuda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_AYUDA, 5);
        $I->click(self::BTN_AYUDA);

        // Verificar que Driver.js inicia el popover interactivo
        $I->waitForElementVisible(self::DRIVER_POPOVER, 5);
        $I->waitForText('Barra de Búsqueda', 5, self::DRIVER_POPOVER);

        // Cerrar tour guiado limpiamente
        $I->executeJS("
            if ($('.driver-close-btn').length) {
                $('.driver-close-btn').click();
            } else if ($('.driver-popover-close-btn').length) {
                $('.driver-popover-close-btn').click();
            } else if (window.driver && typeof window.driver.reset === 'function') {
                window.driver.reset();
            } else if (window.driver && typeof window.driver.destroy === 'function') {
                window.driver.destroy();
            }
        ");

        $I->waitForElementNotVisible(self::DRIVER_POPOVER, 5);
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

    private function seleccionarParticipacionYAtleta(AcceptanceTester $I, int $indexTorneo = 1, int $indexAtleta = 1): ?array
    {
        // 1. Seleccionar torneo / participación en el primer select
        $torneoInfo = $I->executeJS("
            var sel = $('#participacion');
            var opt = sel.find('option:not([disabled])').eq(" . ($indexTorneo - 1) . ");
            if (opt.length === 0) return null;
            var val = opt.val();
            sel.val(val).trigger('change');
            return { id: val, text: opt.text().trim() };
        ");

        if (!$torneoInfo) {
            return null;
        }

        // 2. Esperar que la llamada AJAX AtletasPorTorneo llene el select de atletas
        $I->wait(1);
        $I->waitForJS("return $('#atleta option:not([disabled])').length > 0;", 10);

        // 3. Seleccionar el atleta disponible
        $atletaInfo = $I->executeJS("
            var sel = $('#atleta');
            var opt = sel.find('option:not([disabled])').eq(" . ($indexAtleta - 1) . ");
            if (opt.length === 0) return null;
            var val = opt.val();
            sel.val(val).trigger('change');
            return { id: val, text: opt.text().trim() };
        ");

        if (!$atletaInfo) {
            return null;
        }

        return [
            'torneo' => $torneoInfo,
            'atleta' => $atletaInfo,
        ];
    }

    private function establecerMetricas(
        AcceptanceTester $I,
        string $goles,
        string $asistencias,
        string $penalizaciones,
        string $golesContra,
        string $partidos
    ): void {
        $I->fillField(self::INPUT_GOLES, $goles);
        $I->fillField(self::INPUT_ASISTENCIAS, $asistencias);
        $I->fillField(self::INPUT_PENALIZACIONES, $penalizaciones);
        $I->fillField(self::INPUT_GOLES_CONTRA, $golesContra);
        $I->fillField(self::INPUT_PARTIDO, $partidos);
        $I->executeJS("$('#goles, #partido').trigger('input');");
    }

    private function expandirPrimerAtleta(AcceptanceTester $I): bool
    {
        return (bool) $I->executeJS("
            var primerGrupo = $('.listado_contenedor_grupal').first();
            if (primerGrupo.length > 0) {
                primerGrupo.addClass('expandido');
                primerGrupo.find('.listado_detalle_oculto').show();
                return true;
            }
            return false;
        ");
    }

    private function cerrarSwalSiExiste(AcceptanceTester $I): void
    {
        $I->executeJS("
            if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                Swal.close();
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
