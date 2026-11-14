<?php

declare(strict_types=1);

namespace Tests\Acceptance\Competencias;

use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Interfaz y Aceptación con Codeception WebDriver
 * Módulo: Participaciones (Competencias > Participaciones)
 */
final class ParticipacionesCest
{
    private const SECCION_MENU = 'Competencias';
    private const MODULO_HREF  = 'Participaciones';

    // Selectores principales de la vista
    private const TITULO          = '#titulo';
    private const BUSQUEDA        = '#busqueda';
    private const RESULTADOS      = '#resultadoconsulta';
    private const BTN_NUEVO       = '#incluir';
    private const BTN_REPORTE     = '#generar';
    private const BTN_AYUDA       = '#ayuda';

    // Selectores de acordeón y listado
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const LISTADO_ITEM      = '.listado_item';
    private const DETALLE_OCULTO    = '.listado_detalle_oculto';
    private const TARJETA_RESUMEN   = '.tarjeta_resumen';
    private const SUB_ITEM_FILA     = '.sub_item_fila';
    private const SUB_ITEM_TITULO   = '.sub_item_titulo';

    // Selectores del modal y formulario
    private const MODAL            = '#modal';
    private const MODAL_TITULO     = '#titulo_modal';
    private const MODAL_CERRAR     = '#cerrar_modal';
    private const MODAL_FORM       = '#f';
    private const INPUT_ID         = '#codigo_participacion';
    private const SELECT_TORNEO    = '#codigo_torneo';
    private const SELECT_EQUIPO    = '#codigo_equipo';
    private const BTN_PROCESO      = '#proceso';

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
     * CP-PA-01: Carga y Estructura Base del Módulo
     */
    public function probarCargaYEstructuraBase(AcceptanceTester $I): void
    {
        // 1. Expandir sección Competencias en menú lateral
        $I->click($this->xpathSeccion(self::SECCION_MENU));
        $enlace = sprintf('a[href="%s"]', self::MODULO_HREF);
        $I->waitForElementVisible($enlace, 5);
        $I->see('Participaciones', $enlace);
        $I->click($enlace);

        // 2. Verificar URL y Título principal
        $I->waitForText('Participaciones', 10, self::TITULO);
        $I->seeInCurrentUrl(self::MODULO_HREF);

        // 3. Verificar controles principales de la interfaz
        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::RESULTADOS);

        // 4. Verificar renderizado del contenedor de resultados (grupos o estado vacío)
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
     * CP-PA-02: Búsqueda con Coincidencias en Tiempo Real
     */
    public function probarBusquedaConCoincidencias(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayGrupos = $I->executeJS("return $('.listado_contenedor_grupal').length > 0;");
        if ($hayGrupos) {
            $nombreTorneo = $I->executeJS("return $('.listado_contenedor_grupal:first .listado_titulo').text().trim().split(' ')[0];");
            if (!empty($nombreTorneo)) {
                $I->fillField(self::BUSQUEDA, $nombreTorneo);
                $I->waitForText($nombreTorneo, 10, self::RESULTADOS);
                $I->seeElement(self::CONTENEDOR_GRUPAL);
            }
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PA-03: Búsqueda sin coincidencias (filtro AJAX con debounce)
     */
    public function probarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, 'participacion_inexistente_xyz_999');

        // Espera respuesta AJAX y renderizado de lista vacía
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PA-04: Restablecer búsqueda al vaciar el input
     */
    public function probarRestablecerBusqueda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, '');

        // Debe restablecer la lista de participaciones
        $I->waitForElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS . ' .listado_vacio', 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 3: ACORDEÓN GRUPAL Y VALIDACIONES DE FORMULARIO (RF-UI-03)
    // =========================================================================

    /**
     * CP-PA-04: Despliegue y Colapso del Acordeón Grupal (Toggle Detalles)
     */
    public function probarDespliegueAcordeonDetalles(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // Si existen grupos de torneos registrados, probar expansión interactiva
        $hayGrupos = $I->executeJS("return $('.listado_contenedor_grupal').length > 0;");
        if ($hayGrupos) {
            // Clic en la cabecera del primer torneo para desplegar
            $I->click(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_ITEM);
            $I->waitForElementVisible(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::DETALLE_OCULTO, 5);
            $I->seeElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::TARJETA_RESUMEN);

            // Clic nuevamente para colapsar
            $I->click(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_ITEM);
            $I->waitForElementNotVisible(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::DETALLE_OCULTO, 5);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PA-05: Apertura y Cierre del Modal de Registro
     */
    public function probarAperturaYCierreModal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        // Modal debe ser visible con su título y formulario
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Registrar Participacion', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->see('Registrar Participacion', self::BTN_PROCESO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PA-06: Validación de Torneo no Seleccionado
     */
    public function probarValidacionTorneoNoSeleccionado(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Intentar registrar sin seleccionar torneo
        $I->click(self::BTN_PROCESO);

        // SweetAlert2 de error de validación
        $I->waitForText('Debe seleccionar un torneo', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PA-07: Validación de Equipo no Seleccionado
     */
    public function probarValidacionEquipoNoSeleccionado(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Seleccionar solo torneo en Select2 y dejar equipo vacío
        $I->executeJS("
            var selTorneo = $('#codigo_torneo');
            var valTorneo = selTorneo.find('option:not([disabled])').first().val();
            selTorneo.val(valTorneo).trigger('change');
            $('#codigo_equipo').val('').trigger('change');
        ");

        $I->click(self::BTN_PROCESO);

        // SweetAlert2 de error de validación
        $I->waitForText('Debe seleccionar un equipo', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 4: CICLO DE VIDA DEL REGISTRO (CRUD COMPLETO EN UI) (RF-UI-04)
    // =========================================================================

    /**
     * CP-PA-08: Registro Exitoso con Diálogo de Confirmación
     */
    public function probarRegistroExitoso(AcceptanceTester $I): void
    {
        $datos = $this->prepararInscripcionLimpia($I);

        $this->irAlModulo($I);
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Seleccionar torneo y equipo
        $this->seleccionarTorneoYEquipo($I, 1, 1);

        // Proceder a guardar
        $I->click(self::BTN_PROCESO);

        // Diálogo SweetAlert2 de confirmación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere registrar esta participacion?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito (esperar respuesta AJAX)
        $I->waitForText('Participacion registrada exitosamente.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Modal debe cerrarse y el equipo debe listarse bajo el torneo
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->expandirTorneo($I, $datos['nombreTorneo']);
        $I->waitForText($datos['nombreEquipo'], 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PA-09: Control de Duplicados en Interfaz (Regla de Unicidad Grupal)
     */
    public function probarControlDeDuplicados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Reintentar registrar la misma combinación torneo-equipo ya registrada
        $this->seleccionarTorneoYEquipo($I, 1, 1);
        $I->click(self::BTN_PROCESO);

        // Confirmar en SweetAlert2
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere registrar esta participacion?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Mensaje de error por duplicidad (esperar respuesta AJAX)
        $I->waitForText('Este equipo ya se encuentra inscrito en el torneo.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PA-10: Modificación Exitosa de Inscripción
     */
    public function probarModificacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // Obtenemos los nombres seleccionados
        $infoInicial = $this->obtenerInfoOpcion($I, 1, 1);
        $infoModificar = $this->obtenerInfoOpcion($I, 1, 2);

        // Asegurar que el equipo alternativo no esté ya inscrito
        $this->limpiarInscripcionSiExiste($I, $infoInicial['nombreTorneo'], $infoModificar['nombreEquipo']);

        $this->irAlModulo($I);
        $this->expandirTorneo($I, $infoInicial['nombreTorneo']);

        // Localizar el botón de modificar del equipo 1
        $xpathBotonModificar = sprintf(
            '//div[contains(@class, "sub_item_fila")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_v")]',
            $infoInicial['nombreEquipo']
        );
        $I->waitForElementVisible($xpathBotonModificar, 10);
        $I->click($xpathBotonModificar);

        // Esperar modal de modificación
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Modificar Participacion', 5, self::MODAL_TITULO);

        // Cambiar equipo a la segunda opción disponible
        $this->cambiarEquipo($I, 2);
        $I->click(self::BTN_PROCESO);

        // Confirmar modificación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere modificar esta participacion?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito (esperar respuesta AJAX)
        $I->waitForText('Participación modificada exitosamente.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Verificar actualización en la vista
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->expandirTorneo($I, $infoInicial['nombreTorneo']);
        $I->waitForText($infoModificar['nombreEquipo'], 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PA-11: Cancelación de Eliminación de Participación
     */
    public function probarCancelacionDeEliminacion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $infoModificar = $this->obtenerInfoOpcion($I, 1, 2);
        $this->expandirTorneo($I, $infoModificar['nombreTorneo']);

        // Localizar botón eliminar
        $xpathBotonEliminar = sprintf(
            '//div[contains(@class, "sub_item_fila")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_r")]',
            $infoModificar['nombreEquipo']
        );
        $I->waitForElementVisible($xpathBotonEliminar, 10);
        $I->click($xpathBotonEliminar);

        // Diálogo SweetAlert2: pulsar "NO"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere eliminar esta participacion?', 5, self::SWAL_POPUP);
        $I->click('NO');

        // El registro sigue visible en el listado
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $this->expandirTorneo($I, $infoModificar['nombreTorneo']);
        $I->see($infoModificar['nombreEquipo'], self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PA-12: Eliminación Exitosa de Participación
     */
    public function probarEliminacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $infoModificar = $this->obtenerInfoOpcion($I, 1, 2);
        $this->expandirTorneo($I, $infoModificar['nombreTorneo']);

        // Localizar botón eliminar
        $xpathBotonEliminar = sprintf(
            '//div[contains(@class, "sub_item_fila")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_r")]',
            $infoModificar['nombreEquipo']
        );
        $I->waitForElementVisible($xpathBotonEliminar, 10);
        $I->click($xpathBotonEliminar);

        // Confirmar con "SI"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere eliminar esta participacion?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito (esperar respuesta AJAX)
        $I->waitForText('Participación eliminada exitosamente.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // El registro del equipo desaparece de la vista
        $I->waitForElementNotVisible($xpathBotonEliminar, 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 5: MODAL DE REPORTES Y ASISTENCIA INTERACTIVA (RF-UI-05)
    // =========================================================================

    /**
     * CP-PA-13: Apertura y Configuración del Modal de Generar Reporte
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

    /**
     * CP-PA-14: Interacción y Despliegue del Tour Guiado de Ayuda
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

    private function seleccionarTorneoYEquipo(AcceptanceTester $I, int $indexTorneo = 1, int $indexEquipo = 1): array
    {
        $res = $I->executeJS("
            var selTorneo = $('#codigo_torneo');
            var selEquipo = $('#codigo_equipo');

            var optTorneo = selTorneo.find('option:not([disabled])').eq(" . ($indexTorneo - 1) . ");
            var optEquipo = selEquipo.find('option:not([disabled])').eq(" . ($indexEquipo - 1) . ");

            var valTorneo = optTorneo.val();
            var textTorneo = optTorneo.text();

            var valEquipo = optEquipo.val();
            var textEquipo = optEquipo.text();

            selTorneo.val(valTorneo).trigger('change');
            selEquipo.val(valEquipo).trigger('change');

            var nombreTorneo = textTorneo.split(' - ')[0].trim();

            return {
                idTorneo: valTorneo,
                nombreTorneo: nombreTorneo,
                textoTorneoCompleto: textTorneo,
                idEquipo: valEquipo,
                nombreEquipo: textEquipo.trim()
            };
        ");

        return is_array($res) ? $res : [
            'idTorneo' => '1',
            'nombreTorneo' => 'Torneo Test',
            'textoTorneoCompleto' => 'Torneo Test - 2026-11-20',
            'idEquipo' => '1',
            'nombreEquipo' => 'Cannibals Lara U17',
        ];
    }

    private function cambiarEquipo(AcceptanceTester $I, int $indexEquipo = 2): array
    {
        $res = $I->executeJS("
            var selEquipo = $('#codigo_equipo');
            var optEquipo = selEquipo.find('option:not([disabled])').eq(" . ($indexEquipo - 1) . ");
            var valEquipo = optEquipo.val();
            var textEquipo = optEquipo.text();

            selEquipo.val(valEquipo).trigger('change');

            return {
                idEquipo: valEquipo,
                nombreEquipo: textEquipo.trim()
            };
        ");

        return is_array($res) ? $res : [
            'idEquipo' => '2',
            'nombreEquipo' => 'Coyotes De Carabobo',
        ];
    }

    private function obtenerInfoOpcion(AcceptanceTester $I, int $indexTorneo = 1, int $indexEquipo = 1): array
    {
        $res = $I->executeJS("
            var optTorneo = $('#codigo_torneo').find('option:not([disabled])').eq(" . ($indexTorneo - 1) . ");
            var optEquipo = $('#codigo_equipo').find('option:not([disabled])').eq(" . ($indexEquipo - 1) . ");

            return {
                idTorneo: optTorneo.val(),
                nombreTorneo: optTorneo.text().split(' - ')[0].trim(),
                idEquipo: optEquipo.val(),
                nombreEquipo: optEquipo.text().trim()
            };
        ");

        return is_array($res) ? $res : [
            'idTorneo' => '1',
            'nombreTorneo' => 'Torneo Test',
            'idEquipo' => ($indexEquipo === 1 ? '1' : '2'),
            'nombreEquipo' => ($indexEquipo === 1 ? 'Cannibals Lara U17' : 'Coyotes De Carabobo'),
        ];
    }

    private function expandirTorneo(AcceptanceTester $I, string $nombreTorneo): void
    {
        $I->executeJS(sprintf("
            $('.listado_contenedor_grupal').each(function() {
                var titulo = $(this).find('.listado_titulo').text().trim();
                if (titulo.indexOf('%s') !== -1) {
                    if (!$(this).hasClass('expandido')) {
                        $(this).addClass('expandido');
                        $(this).find('.listado_detalle_oculto').show();
                    }
                }
            });
        ", addslashes($nombreTorneo)));
    }

    private function limpiarInscripcionSiExiste(AcceptanceTester $I, string $nombreTorneo, string $nombreEquipo): void
    {
        $this->irAlModulo($I);
        $I->waitForElementVisible(self::RESULTADOS, 10);

        $fueClickeado = $I->executeJS(sprintf("
            var clickeado = false;
            $('.listado_contenedor_grupal').each(function() {
                var titulo = $(this).find('.listado_titulo').text().trim();
                if (titulo.indexOf('%s') !== -1) {
                    var container = $(this);
                    container.addClass('expandido');
                    container.find('.listado_detalle_oculto').show();
                    container.find('.sub_item_fila').each(function() {
                        var eq = $(this).find('.sub_item_titulo').text().trim();
                        if (eq === '%s') {
                            var btnEliminar = $(this).find('button.cbt_r');
                            if (btnEliminar.length > 0) {
                                btnEliminar.click();
                                clickeado = true;
                                return false;
                            }
                        }
                    });
                }
            });
            return clickeado;
        ", addslashes($nombreTorneo), addslashes($nombreEquipo)));

        if ($fueClickeado) {
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->waitForText('¿Está seguro que quiere eliminar esta participacion?', 5, self::SWAL_POPUP);
            $I->click('SI');
            $I->waitForText('Participación eliminada exitosamente.', 10, self::SWAL_POPUP);
            $I->click('Entendido');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        }
    }

    private function prepararInscripcionLimpia(AcceptanceTester $I): array
    {
        $this->irAlModulo($I);
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);
        $datos = $this->seleccionarTorneoYEquipo($I, 1, 1);
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);

        $this->limpiarInscripcionSiExiste($I, $datos['nombreTorneo'], $datos['nombreEquipo']);
        return $datos;
    }

    private function verificarSinErroresPhp(AcceptanceTester $I): void
    {
        foreach (self::TEXTOS_DE_ERROR as $texto) {
            $I->dontSee($texto);
        }
    }
}
