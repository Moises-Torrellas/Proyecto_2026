<?php

declare(strict_types=1);

namespace Tests\Acceptance\HistorialDeportivo;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Interfaz y Aceptación con Codeception WebDriver
 * Módulo: Palmarés (Historial Deportivo > Palmarés)
 *
 * Sigue la estructura y buenas prácticas de pruebas de interfaz del sistema
 * (ej. Asignaciones, Premios, Catálogo, Torneos), validando la navegación,
 * renderizado de controles, alternancia de pestañas (Individual/Grupal),
 * filtros AJAX en tiempo real, acordeón jerárquico por atleta/equipo,
 * modales de selección de tipo, validaciones de formulario en cliente,
 * ciclo CRUD completo (inclusión, modificación y retiro), diálogo de reportes
 * y tour guiado de asistencia (Driver.js).
 */
#[Group('palmares')]
#[Group('interfaz')]
final class PalmaresCest
{
    private const SECCION_MENU = 'Historial Deportivo';
    private const MODULO_HREF  = 'Palmares';

    // Selectores principales de la vista
    private const TITULO          = '#titulo';
    private const BUSQUEDA        = '#busqueda';
    private const BTN_NUEVO       = '#incluir';
    private const BTN_REPORTE     = '#generar';
    private const BTN_AYUDA       = '#ayuda';

    // Selectores de pestañas
    private const PESTANAS_HEADER  = '.pestanas-header';
    private const TAB_INDIVIDUAL   = '.pestana-btn[data-target="#tab-individual"]';
    private const TAB_GRUPAL       = '.pestana-btn[data-target="#tab-grupal"]';
    private const PANEL_INDIVIDUAL = 'div#tab-individual';
    private const PANEL_GRUPAL     = 'div#tab-grupal';
    private const RESULTADOS_IND   = '#resultadoconsulta-ind';
    private const RESULTADOS_GRP   = '#resultadoconsulta-grp';

    // Selectores del listado y acordeón jerárquico
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const LISTADO_ITEM      = '.listado_item';
    private const LISTADO_TITULO    = '.listado_titulo';
    private const LISTADO_DETALLE   = '.listado_detalle_oculto';
    private const LISTADO_VACIO     = '.listado_vacio';
    private const SUB_ITEM_FILA     = '.sub_item_fila';
    private const SUB_ITEM_TITULO   = '.sub_item_titulo';
    private const SUB_ITEM_ACCIONES = '.sub_item_acciones';
    private const BTN_EDITAR        = 'button.cbt_v';
    private const BTN_ELIMINAR      = 'button.cbt_r';

    // Selectores del paginador
    private const PAG_ROWS_IND      = '#rowsPerPage-ind';
    private const PAG_ROWS_GRP      = '#rowsPerPage-grp';
    private const PAG_BOTONERA_IND  = '#botonera-ind';
    private const PAG_BOTONERA_GRP  = '#botonera-grp';
    private const PAG_CANTIDAD_IND  = '#cantidadRegistros-ind';
    private const PAG_CANTIDAD_GRP  = '#cantidadRegistros-grp';

    // Selectores del modal y formulario
    private const MODAL             = '#modal';
    private const MODAL_TITULO      = '#titulo_modal';
    private const MODAL_CERRAR      = '#cerrar_modal';
    private const MODAL_FORM        = '#f';
    private const INPUT_ID          = '#id';
    private const INPUT_TIPO        = '#tipo_palmares';
    private const SECCION_IND       = '#seccion_individual';
    private const SELECT_TORNEO_IND = '#torneo_ind';
    private const SELECT_PREMIO_IND = '#premio_ind';
    private const SELECT_ATLETA     = '#atleta';
    private const SECCION_GRP       = '#seccion_grupal';
    private const SELECT_TORNEO_GRP = '#torneo_grp';
    private const SELECT_PREMIO_GRP = '#premio_grp';
    private const SELECT_EQUIPO     = '#equipo';
    private const BTN_PROCESO       = '#proceso';

    // Selectores de SweetAlert2
    private const SWAL_POPUP        = '.swal2-popup';
    private const SWAL_CONFIRM      = 'button.swal2-confirm';
    private const SWAL_DENY         = 'button.swal2-deny';
    private const SWAL_CANCEL       = 'button.swal2-cancel';

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
     * CP-PAL-01: Carga y Estructura Base del Módulo desde el Menú Lateral
     */
    public function probarCargaYEstructuraBase(AcceptanceTester $I): void
    {
        // 1. Expandir sección 'Historial Deportivo' en el menú lateral
        $I->click($this->xpathSeccion(self::SECCION_MENU));
        $enlace = sprintf('a[href="%s"]', self::MODULO_HREF);
        $I->waitForElementVisible($enlace, 5);
        $I->see('Palmarés', $enlace);
        $I->click($enlace);

        // 2. Verificar URL y Título principal
        $I->waitForText('Palmarés', 10, self::TITULO);
        $I->seeInCurrentUrl(self::MODULO_HREF);

        // 3. Verificar controles principales de la interfaz
        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::BTN_AYUDA);

        // 4. Verificar presencia del encabezado de pestañas
        $I->seeElement(self::PESTANAS_HEADER);
        $I->seeElement(self::TAB_INDIVIDUAL);
        $I->seeElement(self::TAB_GRUPAL);

        // 5. Verificar que pestaña Individual esté activa por defecto
        $I->seeElement(self::TAB_INDIVIDUAL . '.activa');
        $I->seeElement(self::PANEL_INDIVIDUAL . '.activa');

        // 6. Verificar contenedores de resultados (elementos agrupados o estado vacío)
        $I->seeElement(self::RESULTADOS_IND . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS_IND . ' ' . self::LISTADO_VACIO);
        $I->seeElementInDOM(self::RESULTADOS_GRP);

        // 7. El modal debe existir en el DOM pero permanecer oculto inicialmente
        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PAL-02: Alternancia y Control de Pestañas (Individual y Grupal)
     */
    public function probarAlternanciaDePestanas(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // 1. Inicialmente pestaña Individual está activa
        $I->seeElement(self::TAB_INDIVIDUAL . '.activa');
        $I->seeElement(self::PANEL_INDIVIDUAL . '.activa');
        $I->dontSeeElement(self::PANEL_GRUPAL . '.activa');

        // 2. Clic en pestaña Grupal
        $I->click(self::TAB_GRUPAL);
        $I->waitForElement(self::TAB_GRUPAL . '.activa', 5);
        $I->waitForElement(self::PANEL_GRUPAL . '.activa', 5);
        $I->seeElement(self::TAB_GRUPAL . '.activa');
        $I->dontSeeElement(self::TAB_INDIVIDUAL . '.activa');
        $I->dontSeeElement(self::PANEL_INDIVIDUAL . '.activa');

        // 3. Clic nuevamente en pestaña Individual
        $I->click(self::TAB_INDIVIDUAL);
        $I->waitForElement(self::TAB_INDIVIDUAL . '.activa', 5);
        $I->waitForElement(self::PANEL_INDIVIDUAL . '.activa', 5);
        $I->seeElement(self::TAB_INDIVIDUAL . '.activa');
        $I->dontSeeElement(self::TAB_GRUPAL . '.activa');

        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 2: BÚSQUEDA Y FILTRADO DINÁMICO EN TIEMPO REAL (RF-UI-02)
    // =========================================================================

    /**
     * CP-PAL-03: Búsqueda con Coincidencias en Tiempo Real
     */
    public function probarBusquedaConCoincidencias(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayRegistros = $I->executeJS("return $('#resultadoconsulta-ind .listado_contenedor_grupal').length > 0;");
        if ($hayRegistros) {
            $nombreAtleta = (string) $I->executeJS("
                return $('#resultadoconsulta-ind .listado_contenedor_grupal:first .listado_titulo').text().trim().split(' ')[0];
            ");

            if (!empty($nombreAtleta)) {
                $I->fillField(self::BUSQUEDA, $nombreAtleta);
                $I->executeJS("$('#busqueda').trigger('keyup');");
                $I->waitForText($nombreAtleta, 10, self::RESULTADOS_IND);
                $I->seeElement(self::RESULTADOS_IND . ' ' . self::CONTENEDOR_GRUPAL);
            }
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PAL-04: Búsqueda sin Coincidencias (Estado Vacío)
     */
    public function probarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, 'atleta_o_equipo_inexistente_xyz_999');
        $I->executeJS("$('#busqueda').trigger('keyup');");

        // Espera respuesta AJAX y renderizado del mensaje de lista vacía
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS_IND);
        $I->seeElement(self::RESULTADOS_IND . ' ' . self::LISTADO_VACIO);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PAL-05: Restablecer Búsqueda al Vaciar el Campo de Entrada
     */
    public function probarRestablecerBusqueda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, '');
        $I->executeJS("$('#busqueda').trigger('keyup');");

        // Debe restablecer la lista de palmarés o el estado vacío original
        $I->waitForElement(self::RESULTADOS_IND . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS_IND . ' ' . self::LISTADO_VACIO, 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 3: ACORDEÓN JERÁRQUICO Y MODALES DE ASIGNACIÓN (RF-UI-03)
    // =========================================================================

    /**
     * CP-PAL-06: Despliegue y Colapso del Acordeón Jerárquico de Palmarés
     */
    public function probarDespliegueAcordeonDetalles(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayGrupos = $I->executeJS("return $('#resultadoconsulta-ind .listado_contenedor_grupal').length > 0;");
        if ($hayGrupos) {
            $primerGrupo = self::RESULTADOS_IND . ' .listado_contenedor_grupal:first-child';

            // 1. Clic en la cabecera del primer grupo para desplegar detalles
            $I->click($primerGrupo . ' ' . self::LISTADO_ITEM);
            $I->waitForElementVisible($primerGrupo . ' ' . self::LISTADO_DETALLE, 5);
            $I->seeElement($primerGrupo . ' ' . self::SUB_ITEM_FILA);

            // 2. Clic nuevamente para colapsar
            $I->click($primerGrupo . ' ' . self::LISTADO_ITEM);
            $I->waitForElementNotVisible($primerGrupo . ' ' . self::LISTADO_DETALLE, 5);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PAL-07: Diálogo de Selección de Tipo de Palmarés y Cancelación
     */
    public function probarDialogoSeleccionTipoYCancelacion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        // Debe aparecer SweetAlert preguntando tipo de palmarés
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Qué tipo de Palmarés desea registrar?', 5, self::SWAL_POPUP);
        $I->seeElement(self::SWAL_CONFIRM); // Botón Individual
        $I->seeElement(self::SWAL_DENY);    // Botón Grupal
        $I->seeElement(self::SWAL_CANCEL);  // Botón Cancelar

        // Cancelar el diálogo
        $I->click(self::SWAL_CANCEL);
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // El modal principal no debe haberse abierto
        $I->dontSeeElement(self::MODAL);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PAL-08: Apertura y Cierre Limpio del Modal de Palmarés Individual
     */
    public function probarAperturaYCierreModalIndividual(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->click(self::SWAL_CONFIRM); // Seleccionar Individual
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Modal debe ser visible configurado para palmarés individual
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Registrar Palmarés Individual', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->seeElement(self::SECCION_IND);
        $I->dontSeeElement(self::SECCION_GRP);
        $I->see('Registrar Palmarés', self::BTN_PROCESO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PAL-09: Apertura y Cierre Limpio del Modal de Palmarés Grupal
     */
    public function probarAperturaYCierreModalGrupal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->click(self::SWAL_DENY); // Seleccionar Grupal
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Modal debe ser visible configurado para palmarés grupal
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Registrar Palmarés Grupal', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->seeElement(self::SECCION_GRP);
        $I->dontSeeElement(self::SECCION_IND);
        $I->see('Registrar Palmarés', self::BTN_PROCESO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 4: VALIDACIONES DE FORMULARIO EN CLIENTE (RF-UI-04)
    // =========================================================================

    /**
     * CP-PAL-10: Validación de Torneo Obligatorio en Palmarés Individual
     */
    public function probarValidacionTorneoObligatorioIndividual(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);
        $this->abrirModalPorTipo($I, 'individual');

        // No seleccionar torneo e intentar registrar
        $this->seleccionarSelect2($I, 'torneo_ind', '');
        $I->click(self::BTN_PROCESO);

        // Alerta SweetAlert2 de validación de torneo
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('Debe seleccionar un torneo', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PAL-11: Validación de Premio Obligatorio en Palmarés Individual
     */
    public function probarValidacionPremioObligatorioIndividual(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);
        $this->abrirModalPorTipo($I, 'individual');

        // Asegurar que torneo tenga valor seleccionado y premio vacío
        $this->asegurarValorSelect2($I, 'torneo_ind', '1', 'Torneo Finalizado (2026)');
        $this->seleccionarSelect2($I, 'premio_ind', '');
        $I->click(self::BTN_PROCESO);

        // Alerta SweetAlert2 de validación de premio
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('Debe seleccionar un premio individual', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PAL-12: Validación de Atleta Obligatorio en Palmarés Individual
     */
    public function probarValidacionAtletaObligatorioIndividual(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);
        $this->abrirModalPorTipo($I, 'individual');

        // Asegurar torneo y premio seleccionados pero atleta vacío
        $this->asegurarValorSelect2($I, 'torneo_ind', '1', 'Torneo Finalizado (2026)');
        $this->asegurarValorSelect2($I, 'premio_ind', '1', 'Premio Individual');
        $this->seleccionarSelect2($I, 'atleta', '');
        $I->click(self::BTN_PROCESO);

        // Alerta SweetAlert2 de validación de atleta
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('Debe seleccionar un atleta', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PAL-13: Validación de Torneo y Equipo Obligatorios en Palmarés Grupal
     */
    public function probarValidacionCamposObligatoriosGrupal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);
        $this->abrirModalPorTipo($I, 'grupal');

        // 1. Torneo vacío
        $this->seleccionarSelect2($I, 'torneo_grp', '');
        $I->click(self::BTN_PROCESO);

        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('Debe seleccionar un torneo', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 2. Torneo seleccionado, premio grupal vacío
        $this->asegurarValorSelect2($I, 'torneo_grp', '1', 'Torneo Finalizado (2026)');
        $this->seleccionarSelect2($I, 'premio_grp', '');
        $I->click(self::BTN_PROCESO);

        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('Debe seleccionar un premio grupal', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 3. Torneo y premio seleccionados, equipo vacío
        $this->asegurarValorSelect2($I, 'premio_grp', '1', 'Premio Grupal');
        $this->seleccionarSelect2($I, 'equipo', '');
        $I->click(self::BTN_PROCESO);

        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('Debe seleccionar un equipo', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 5: CICLO TRANSACCIONAL COMPLETO (INCLUSIÓN, EDICIÓN, RETIRO) (RF-UI-05)
    // =========================================================================

    /**
     * CP-PAL-14: Registro Exitoso de Palmarés con Diálogo de Confirmación
     */
    public function probarRegistroExitosoPalmares(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);
        $this->abrirModalPorTipo($I, 'individual');

        // 1. Seleccionar torneo disponible
        $torneo = $this->seleccionarPrimerOpcionDisponible($I, 'torneo_ind');
        $I->wait(1); // Esperar carga AJAX de atletas dependientes

        // 2. Seleccionar premio individual
        $premio = $this->seleccionarPrimerOpcionDisponible($I, 'premio_ind');

        // 3. Seleccionar atleta participante
        $atleta = $this->seleccionarPrimerOpcionDisponible($I, 'atleta');

        if (!empty($torneo) && !empty($premio) && !empty($atleta)) {
            $I->click(self::BTN_PROCESO);

            // 4. Confirmar en diálogo SweetAlert2
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->waitForText('¿Está seguro que quiere registrar este palmarés?', 5, self::SWAL_POPUP);
            $I->click('SI');

            // 5. Verificar resultado (éxito o mensaje de duplicado si ya fue asignado)
            $I->waitForElementVisible(self::SWAL_POPUP, 10);
            $esExito = $I->executeJS("
                return $('.swal2-title').text().includes('Registro Exitoso') || 
                       $('.swal2-html-container').text().includes('exitosamente') ||
                       $('.swal2-html-container').text().includes('ya tiene registrado');
            ");
            $I->assertTrue((bool) $esExito, 'El sistema debe responder con resultado controlado para el registro de palmarés.');
            $I->click('Entendido');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        }

        $I->executeJS("$('#cerrar_modal').click();");
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PAL-15: Modificación de Palmarés Existente
     */
    public function probarModificacionPalmares(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayPalmares = $I->executeJS("return $('#resultadoconsulta-ind .sub_item_fila button.cbt_v').length > 0;");
        if ($hayPalmares) {
            // Expandir el primer contenedor grupal para hacer visible el botón de modificar
            $this->expandirPrimerContenedor($I, 'individual');

            $I->waitForElementVisible(self::RESULTADOS_IND . ' ' . self::BTN_EDITAR, 5);
            $I->click(self::RESULTADOS_IND . ' ' . self::BTN_EDITAR);

            // Modal se abre en modo modificación con título adaptado
            $I->waitForElementVisible(self::MODAL, 5);
            $I->waitForText('Modificar Palmarés', 5, self::MODAL_TITULO);
            $I->see('Modificar Palmarés', self::BTN_PROCESO);

            // Seleccionar otra opción de premio si está disponible
            $this->seleccionarSegundaOpcionDisponible($I, 'premio_ind');

            // Guardar cambios
            $I->click(self::BTN_PROCESO);

            // Diálogo de confirmación SweetAlert2
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->waitForText('¿Está seguro que quiere modificar este palmarés?', 5, self::SWAL_POPUP);
            $I->click('SI');

            // Verificar respuesta controlada
            $I->waitForElementVisible(self::SWAL_POPUP, 10);
            $I->click('Entendido');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
            $I->waitForElementNotVisible(self::MODAL, 5);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PAL-16: Cancelación y Ejecución de Eliminación de Palmarés
     */
    public function probarEliminacionPalmares(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayPalmares = $I->executeJS("return $('#resultadoconsulta-ind .sub_item_fila button.cbt_r').length > 0;");
        if ($hayPalmares) {
            $this->expandirPrimerContenedor($I, 'individual');

            $I->waitForElementVisible(self::RESULTADOS_IND . ' ' . self::BTN_ELIMINAR, 5);

            // 1. Clic en eliminar y CANCELAR en la alerta
            $I->click(self::RESULTADOS_IND . ' ' . self::BTN_ELIMINAR);
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->waitForText('¿Estás seguro de que quieres eliminar este palmarés', 5, self::SWAL_POPUP);
            $I->click('NO');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

            // El botón y registro siguen presentes
            $I->seeElement(self::RESULTADOS_IND . ' ' . self::BTN_ELIMINAR);

            // 2. Clic en eliminar y CONFIRMAR
            $I->click(self::RESULTADOS_IND . ' ' . self::BTN_ELIMINAR);
            $I->waitForElementVisible(self::SWAL_POPUP, 5);
            $I->click('SI');

            // Confirmación de éxito
            $I->waitForElementVisible(self::SWAL_POPUP, 10);
            $I->click('Entendido');
            $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        }

        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 6: MODAL DE REPORTES Y ASISTENCIA INTERACTIVA (RF-UI-06)
    // =========================================================================

    /**
     * CP-PAL-17: Diálogo de Generar Reporte y Configuración Modal
     */
    public function probarModalGenerarReporte(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_REPORTE, 5);
        $I->click(self::BTN_REPORTE);

        // SweetAlert de selección de tipo para reporte
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Para qué tipo de Palmarés desea el reporte?', 5, self::SWAL_POPUP);
        $I->click(self::SWAL_CONFIRM); // Seleccionar Individual
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Modal debe abrirse configurado para reporte individual
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Reporte Palmarés Individual', 5, self::MODAL_TITULO);
        $I->see('Generar Reporte', self::BTN_PROCESO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PAL-18: Tour Guiado Interactivo de Asistencia (Driver.js)
     */
    public function probarTourGuiadoAyuda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_AYUDA, 5);
        $I->click(self::BTN_AYUDA);

        // Verificar que Driver.js inicia el popover interactivo
        $selectorPopover = '#driver-popover-item, .driver-popover';
        $I->waitForElementVisible($selectorPopover, 5);
        $I->waitForText('Barra de Búsqueda', 5, $selectorPopover);

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

        $I->waitForElementNotVisible($selectorPopover, 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 7: PAGINACIÓN Y CONTROL DE VISTA (RF-UI-07)
    // =========================================================================

    /**
     * CP-PAL-19: Control de Paginación y Selector de Filas por Página
     */
    public function probarControlPaginacion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // Verificar controles de paginación de la pestaña Individual
        $I->seeElement(self::PAG_ROWS_IND);
        $I->seeElementInDOM(self::PAG_BOTONERA_IND);
        $I->seeElement(self::PAG_CANTIDAD_IND);

        // Cambiar cantidad de filas por página a 20
        $I->selectOption(self::PAG_ROWS_IND, '20');
        $I->seeOptionIsSelected(self::PAG_ROWS_IND, '20');

        // Alternar a pestaña Grupal y verificar controles correspondientes
        $I->click(self::TAB_GRUPAL);
        $I->waitForElement(self::PANEL_GRUPAL . '.activa', 5);

        $I->seeElement(self::PAG_ROWS_GRP);
        $I->seeElementInDOM(self::PAG_BOTONERA_GRP);
        $I->seeElement(self::PAG_CANTIDAD_GRP);

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

    private function abrirModalPorTipo(AcceptanceTester $I, string $tipo): void
    {
        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::SWAL_POPUP, 5);

        if ($tipo === 'individual') {
            $I->click(self::SWAL_CONFIRM);
        } else {
            $I->click(self::SWAL_DENY);
        }

        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->waitForElementVisible(self::MODAL, 5);
    }

    private function expandirPrimerContenedor(AcceptanceTester $I, string $tipo = 'individual'): void
    {
        $selectorContainer = ($tipo === 'individual') ? self::RESULTADOS_IND : self::RESULTADOS_GRP;
        $I->executeJS(sprintf("
            var primerGrupo = $('%s .listado_contenedor_grupal').first();
            if (primerGrupo.length > 0) {
                primerGrupo.addClass('expandido');
                primerGrupo.find('.listado_detalle_oculto').show();
            }
        ", addslashes($selectorContainer)));
    }

    private function seleccionarSelect2(AcceptanceTester $I, string $selectId, string $valor): void
    {
        $I->executeJS(sprintf("
            var \$sel = $('#%s');
            \$sel.val(%s).trigger('change');
        ", addslashes($selectId), empty($valor) ? 'null' : "'" . addslashes($valor) . "'"));
    }

    private function asegurarValorSelect2(AcceptanceTester $I, string $selectId, string $fallbackVal = '1', string $fallbackText = 'Opción Prueba'): string
    {
        $res = (string) $I->executeJS(sprintf("
            var \$sel = $('#%s');
            var opt = \$sel.find('option:not([disabled])').filter(function() { return $(this).val() !== ''; }).first();
            if (opt.length === 0) {
                \$sel.append(new Option('%s', '%s', true, true));
            } else {
                \$sel.val(opt.val());
            }
            \$sel.trigger('change');
            return \$sel.val();
        ", addslashes($selectId), addslashes($fallbackText), addslashes($fallbackVal)));

        return $res;
    }

    private function seleccionarPrimerOpcionDisponible(AcceptanceTester $I, string $selectId): string
    {
        $val = (string) $I->executeJS(sprintf("
            var \$sel = $('#%s');
            var opt = \$sel.find('option:not([disabled])').filter(function() { return $(this).val() !== ''; }).first();
            if (opt.length > 0) {
                var v = opt.val();
                \$sel.val(v).trigger('change');
                return v;
            }
            return '';
        ", addslashes($selectId)));

        return $val;
    }

    private function seleccionarSegundaOpcionDisponible(AcceptanceTester $I, string $selectId): string
    {
        $val = (string) $I->executeJS(sprintf("
            var \$sel = $('#%s');
            var opts = \$sel.find('option:not([disabled])').filter(function() { return $(this).val() !== ''; });
            if (opts.length > 1) {
                var v = opts.eq(1).val();
                \$sel.val(v).trigger('change');
                return v;
            } else if (opts.length === 1) {
                var v = opts.eq(0).val();
                \$sel.val(v).trigger('change');
                return v;
            }
            return '';
        ", addslashes($selectId)));

        return $val;
    }

    private function verificarSinErroresPhp(AcceptanceTester $I): void
    {
        foreach (self::TEXTOS_DE_ERROR as $texto) {
            $I->dontSee($texto);
        }
    }
}
