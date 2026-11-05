<?php

declare(strict_types=1);

namespace Tests\Acceptance\Competencias;

use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Interfaz y Aceptación con Codeception WebDriver
 * Módulo: Equipos (Competencias > Equipos)
 */
final class EquiposCest
{
    private const SECCION_MENU = 'Competencias';
    private const MODULO_HREF  = 'Equipos';

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
    private const INPUT_ID            = '#id';
    private const INPUT_NOMBRE        = '#nombre';
    private const ROW_NOMBRE          = '#row_nombre';
    private const SELECT_EQUIPO_REP   = '#id_equipo';
    private const ROW_EQUIPO_REP      = '#row_equipo_reporte';
    private const SECCION_LOTE        = '#seccion_lote_equipo';
    private const SELECT_LOTE_ATLETA  = '#lote_atleta';
    private const BTN_AGREGAR_LOTE    = '#btn_agregar_lote_equipo';
    private const BTN_VACIAR_LOTE     = '#btn_vaciar_lote_equipo';
    private const CONTADOR_LOTE       = '#contador_lote_equipo';
    private const CUERPO_LOTE         = '#cuerpo_lote_equipo';
    private const BTN_PROCESO         = '#proceso';

    // Selectores de SweetAlert2
    private const SWAL_POPUP      = '.swal2-popup';
    private const SWAL_CONFIRM    = 'button.swal2-confirm';
    private const SWAL_CANCEL     = 'button.swal2-cancel';

    // Datos para pruebas CRUD
    private const NOMBRE_TEST      = 'Cannibals Ui Test Team';
    private const NOMBRE_MODIFICAR = 'Cannibals Ui Test Modificado';

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
     * CP-EQ-01: Carga y Estructura Base del Módulo
     */
    public function probarCargaYEstructuraBase(AcceptanceTester $I): void
    {
        // 1. Expandir la sección Competencias en el menú lateral
        $I->click($this->xpathSeccion(self::SECCION_MENU));
        $enlace = sprintf('a[href="%s"]', self::MODULO_HREF);
        $I->waitForElementVisible($enlace, 5);
        $I->see('Equipos', $enlace);
        $I->click($enlace);

        // 2. Verificar URL y Título principal
        $I->waitForText('Equipos', 10, self::TITULO);
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
     * CP-EQ-02: Búsqueda sin coincidencias (filtro AJAX)
     */
    public function probarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, 'equipo_inexistente_xyz_999');

        // Espera respuesta AJAX y renderizado de lista vacía
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EQ-03: Restablecer búsqueda al vaciar el input
     */
    public function probarRestablecerBusqueda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, '');

        // Debe restablecer la lista de equipos
        $I->waitForElement(self::RESULTADOS . ' .listado_item, ' . self::RESULTADOS . ' .listado_vacio', 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 3: MODAL Y GESTIÓN DINÁMICA DEL LOTE DE ATLETAS (RF-UI-03)
    // =========================================================================

    /**
     * CP-EQ-04: Apertura y Cierre del Modal de Registro
     */
    public function probarAperturaYCierreModal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        // Modal debe ser visible con su título y formulario
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Registrar Equipo', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->see('Registrar Equipo', self::BTN_PROCESO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EQ-05: Validación de Nombre Vacío al Intentar Registrar
     */
    public function probarValidacionNombreVacio(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Clic en registrar sin datos
        $I->click(self::BTN_PROCESO);

        // SweetAlert2 de error de validación de nombre
        $I->waitForText('Tiene que ingresar un nombre válido.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
    }

    /**
     * CP-EQ-06: Validación de Selección de Atleta Vacío en el Lote
     */
    public function probarValidacionAtletaVacioEnLote(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Intentar agregar atleta sin haber seleccionado uno
        $I->click(self::BTN_AGREGAR_LOTE);

        // SweetAlert2 de error de validación
        $I->waitForText('Seleccione un atleta de la lista antes de agregarlo.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
    }

    /**
     * CP-EQ-07: Validación de Equipo sin Atletas Asignados
     */
    public function probarValidacionSinAtletasAsignados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Llenar nombre pero sin agregar atletas al lote
        $I->fillField(self::INPUT_NOMBRE, 'Equipo Temporal Sin Atletas');
        $I->click(self::BTN_PROCESO);

        // SweetAlert2 de error de regla de negocio
        $I->waitForText('Debe agregar al menos un atleta a la lista del equipo.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
    }

    /**
     * CP-EQ-08: Gestión Dinámica del Lote (Agregar y Vaciar Atletas)
     */
    public function probarGestionDinamicaLote(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Seleccionar primer atleta disponible en Select2 y agregarlo
        $this->seleccionarPrimerAtletaDisponible($I);
        $I->click(self::BTN_AGREGAR_LOTE);

        // El contador del lote se incrementa a 1
        $I->waitForText('1', 5, self::CONTADOR_LOTE);
        $I->seeElement(self::CUERPO_LOTE . ' .item_lote');

        // Vaciar la lista con el botón de vaciado
        $I->click(self::BTN_VACIAR_LOTE);
        $I->waitForText('0', 5, self::CONTADOR_LOTE);
        $I->see('Aún no hay atletas en el equipo', self::CUERPO_LOTE);

        $I->click(self::MODAL_CERRAR);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 4: CICLO DE VIDA DEL REGISTRO (CRUD COMPLETO EN UI) (RF-UI-04)
    // =========================================================================

    /**
     * CP-EQ-09: Registro Exitoso con Diálogo de Confirmación
     */
    public function probarRegistroExitoso(AcceptanceTester $I): void
    {
        $this->limpiarRegistrosResiduales($I);
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Llenar nombre del equipo
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);

        // Agregar al menos 1 atleta al lote
        $this->seleccionarPrimerAtletaDisponible($I);
        $I->click(self::BTN_AGREGAR_LOTE);
        $I->waitForText('1', 5, self::CONTADOR_LOTE);

        // Proceder a guardar
        $I->click(self::BTN_PROCESO);

        // Diálogo SweetAlert2 de confirmación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere registrar este equipo', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito (esperar respuesta AJAX)
        $I->waitForText('Equipo registrado exitosamente.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Modal debe cerrarse y el nuevo equipo debe aparecer en la lista
        $I->waitForElementNotVisible(self::MODAL, 5);
        $I->waitForText(self::NOMBRE_TEST, 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EQ-10: Control de Duplicados en Interfaz (Unicidad de Nombre)
     */
    public function probarControlDeDuplicados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Reintentar registrar con el mismo nombre ya guardado
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $this->seleccionarPrimerAtletaDisponible($I);
        $I->click(self::BTN_AGREGAR_LOTE);
        $I->waitForText('1', 5, self::CONTADOR_LOTE);

        $I->click(self::BTN_PROCESO);

        // Confirmar en SweetAlert2
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere registrar este equipo', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Mensaje de error por duplicidad (esperar respuesta AJAX)
        $I->waitForText('Ya existe un equipo registrado con ese nombre.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EQ-11: Modificación Exitosa de Equipo
     */
    public function probarModificacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // Localizar el botón de modificar del equipo creado
        $xpathBotonModificar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_v")]',
            self::NOMBRE_TEST
        );
        $I->waitForElementVisible($xpathBotonModificar, 10);
        $I->click($xpathBotonModificar);

        // Esperar modal de modificación y precarga de datos
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Modificar Equipo', 5, self::MODAL_TITULO);
        $I->seeInField(self::INPUT_NOMBRE, self::NOMBRE_TEST);

        // Modificar nombre
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_MODIFICAR);
        $I->click(self::BTN_PROCESO);

        // Confirmar modificación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere modificar este equipo', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito (esperar respuesta AJAX)
        $I->waitForText('Equipo modificado exitosamente.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Verificar actualización en la vista
        $I->waitForElementNotVisible(self::MODAL, 5);
        $I->waitForText(self::NOMBRE_MODIFICAR, 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EQ-12: Cancelación de Eliminación de Equipo
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

        // Diálogo SweetAlert2: pulsar "NO"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere eliminar este registro?', 5, self::SWAL_POPUP);
        $I->click('NO');

        // El registro sigue visible en el listado
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->see(self::NOMBRE_MODIFICAR, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EQ-13: Eliminación Exitosa de Equipo
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
        $I->waitForText('¿Está seguro que quiere eliminar este registro?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito (esperar respuesta AJAX)
        $I->waitForText('Equipo eliminado exitosamente.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // El equipo desaparece de la vista (base de datos limpia)
        $I->waitForElementNotVisible($xpathBotonEliminar, 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 5: MODAL DE REPORTES (RF-UI-05)
    // =========================================================================

    /**
     * CP-EQ-14: Apertura y Configuración del Modal de Generar Reporte
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

        // Los campos de nombre y lote de atletas se ocultan
        $I->dontSeeElement(self::ROW_NOMBRE);
        $I->dontSeeElement(self::SECCION_LOTE);

        // El selector de equipo para reporte debe estar visible
        $I->seeElement(self::ROW_EQUIPO_REP);

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
                $I->waitForText('Equipo eliminado exitosamente.', 10, self::SWAL_POPUP);
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

    private function seleccionarPrimerAtletaDisponible(AcceptanceTester $I): void
    {
        $I->executeJS("
            var sel = document.querySelector('#lote_atleta');
            if (sel && sel.options.length > 1) {
                sel.selectedIndex = 1;
                $(sel).trigger('change');
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

