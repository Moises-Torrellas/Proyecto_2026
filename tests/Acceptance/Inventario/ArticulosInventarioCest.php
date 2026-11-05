<?php

declare(strict_types=1);

namespace Tests\Acceptance\Inventario;

use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Interfaz y Aceptación con Codeception WebDriver
 * Módulo: Inventario de Artículos (Inventario > Inventario de Artículos)
 */
final class ArticulosInventarioCest
{
    private const SECCION_MENU = 'Inventario';
    private const MODULO_HREF  = 'ArticulosInventario';

    // Selectores principales de la vista
    private const TITULO          = '#titulo';
    private const BUSQUEDA        = '#busqueda';
    private const RESULTADOS      = '#resultadoconsulta';
    private const BTN_NUEVO       = '#btn_nuevo';
    private const BTN_REPORTE     = '#generar';
    private const BTN_AYUDA       = '#ayuda';

    // Selectores del listado y acordeón grupal
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const LISTADO_ITEM      = '.listado_item';
    private const LISTADO_DETALLE   = '.listado_detalle_oculto';
    private const SUB_ITEM_FILA     = '.sub_item_fila';
    private const SUB_ITEM_TITULO   = '.sub_item_titulo';

    // Selectores del modal y formulario
    private const MODAL               = '#modal';
    private const MODAL_TITULO        = '#titulo_modal';
    private const MODAL_CERRAR        = '#cerrar_modal';
    private const MODAL_FORM          = '#f';
    private const INPUT_ID            = '#codigo_articulo';
    private const BTN_GUARDAR         = '#btn_guardar';

    // Selectores específicos de la sección por lote
    private const SECCION_LOTE        = '#seccion_lote';
    private const SELECT_LOTE_CAT     = '#lote_catalogo';
    private const INPUT_LOTE_CANT     = '#lote_cantidad';
    private const SELECT_LOTE_EST     = '#lote_estado';
    private const BTN_AGREGAR_LOTE    = '#btn_agregar_lote';
    private const BTN_VACIAR_LOTE     = '#btn_vaciar_lote';
    private const CONTADOR_LOTE       = '#contador_lote';
    private const CUERPO_LOTE         = '#cuerpo_lote';

    // Selectores de la sección individual (modificación y reporte)
    private const SECCION_INDIVIDUAL  = '#seccion_individual';
    private const COL_CATALOGO        = '#col_catalogo';
    private const SELECT_ID_CATALOGO  = '#id_catalogo';
    private const SELECT_ID_ESTADO    = '#id_estado';

    // Selectores de SweetAlert2
    private const SWAL_POPUP          = '.swal2-popup';
    private const SWAL_CONFIRM        = 'button.swal2-confirm';
    private const SWAL_CANCEL         = 'button.swal2-cancel';

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
     * CP-AI-01: Carga y Estructura Base del Módulo
     */
    public function probarCargaYEstructuraBase(AcceptanceTester $I): void
    {
        // 1. Expandir sección Inventario en el menú lateral
        $I->click($this->xpathSeccion(self::SECCION_MENU));
        $enlace = sprintf('a[href="%s"]', self::MODULO_HREF);
        $I->waitForElementVisible($enlace, 5);
        $I->see('Inventario de Artículos', $enlace);
        $I->click($enlace);

        // 2. Verificar URL y Título principal
        $I->waitForText('Inventario de Artículos', 10, self::TITULO);
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
     * CP-AI-02: Búsqueda sin coincidencias (filtro AJAX con debounce)
     */
    public function probarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, 'articulo_inexistente_xyz_999');

        // Espera respuesta AJAX y renderizado de lista vacía
        $I->waitForText('No hay artículos registrados en el inventario.', 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AI-03: Restablecer búsqueda al vaciar el input
     */
    public function probarRestablecerBusqueda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, '');

        // Debe restablecer la lista de artículos agrupados
        $I->waitForElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS . ' .listado_vacio', 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 3: ACORDEÓN GRUPAL Y GESTIÓN DINÁMICA DE LOTE (RF-UI-03)
    // =========================================================================

    /**
     * CP-AI-04: Despliegue y Colapso del Acordeón por Catálogo (Toggle Detalles)
     */
    public function probarDespliegueAcordeonDetalles(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayGrupos = $I->executeJS("return $('.listado_contenedor_grupal').length > 0;");
        if ($hayGrupos) {
            // Clic en la cabecera del primer grupo de catálogo para desplegar piezas
            $I->click(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_ITEM);
            $I->waitForElementVisible(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_DETALLE, 5);
            $I->seeElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::SUB_ITEM_FILA);

            // Clic nuevamente para colapsar
            $I->click(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_ITEM);
            $I->waitForElementNotVisible(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::LISTADO_DETALLE, 5);
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AI-05: Apertura y Cierre del Modal de Registro
     */
    public function probarAperturaYCierreModal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        // Modal debe ser visible con su título y formulario configurado para lote
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Registrar Artículos en Inventario', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->seeElement(self::SECCION_LOTE);
        $I->dontSeeElement(self::SECCION_INDIVIDUAL);
        $I->see('Registrar', self::BTN_GUARDAR);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AI-06: Validación de Catálogo no Seleccionado al Agregar al Lote
     */
    public function probarValidacionCatalogoNoSeleccionado(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Intentar agregar al lote sin seleccionar artículo del catálogo
        $I->click(self::BTN_AGREGAR_LOTE);

        // SweetAlert2 de error de validación
        $I->waitForText('Seleccione el artículo del catálogo que desea agregar.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AI-07: Validación de Registro con Lote Vacío
     */
    public function probarValidacionLoteVacio(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Clic en Registrar con 0 artículos en la lista del lote
        $I->click(self::BTN_GUARDAR);

        // SweetAlert2 de validación
        $I->waitForText('Agregue al menos un artículo a la lista antes de registrar.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AI-08: Gestión Dinámica del Lote (Agregar, Contar y Vaciar Artículos)
     */
    public function probarGestionDinamicaLote(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Seleccionar catálogo, estado y cantidad = 2
        $this->configurarLote($I, 1, 1, 2);
        $I->click(self::BTN_AGREGAR_LOTE);

        // El contador del lote se incrementa a 2
        $I->waitForText('2', 5, self::CONTADOR_LOTE);
        $I->seeNumberOfElements(self::CUERPO_LOTE . ' .item_lote', 2);

        // Vaciar la lista con el botón de vaciado
        $I->click(self::BTN_VACIAR_LOTE);
        $I->waitForText('0', 5, self::CONTADOR_LOTE);
        $I->see('Aún no hay artículos en la lista', self::CUERPO_LOTE);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 4: CICLO DE VIDA DEL ARTÍCULO FÍSICO (CRUD COMPLETO EN UI) (RF-UI-04)
    // =========================================================================

    /**
     * CP-AI-09: Registro Exitoso de Artículo en Lote con Diálogo de Confirmación
     */
    public function probarRegistroExitoso(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Agregar 1 artículo al lote
        $datos = $this->configurarLote($I, 1, 1, 1);
        $I->click(self::BTN_AGREGAR_LOTE);
        $I->waitForText('1', 5, self::CONTADOR_LOTE);

        // Proceder a guardar
        $I->click(self::BTN_GUARDAR);

        // Diálogo SweetAlert2 de confirmación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Registrar este artículo en el inventario?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito tras AJAX
        $I->waitForText('Artículo registrado con el código', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Modal debe cerrarse y el nuevo artículo debe listarse bajo el catálogo
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->expandirPrimerCatalogo($I);
        $I->waitForElementVisible(self::SUB_ITEM_FILA, 10);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AI-10: Modificación Exitosa de Condición / Estado Físico
     */
    public function probarModificacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);
        $this->expandirPrimerCatalogo($I);

        // Localizar el botón de modificar (lápiz) de la primera pieza
        $btnModificar = self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::SUB_ITEM_FILA . ':first-child button.cbt_v';
        $I->waitForElementVisible($btnModificar, 10);
        $I->click($btnModificar);

        // Esperar modal de modificación
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Modificar Estado Físico', 5, self::MODAL_TITULO);
        $I->seeElement(self::SECCION_INDIVIDUAL);
        $I->dontSeeElement(self::SECCION_LOTE);
        $I->dontSeeElement(self::COL_CATALOGO);

        // Cambiar condición física a opción 2 en Select2
        $this->cambiarEstadoFisico($I, 2);
        $I->click(self::BTN_GUARDAR);

        // Confirmar modificación en SweetAlert2
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Guardar los cambios?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito tras AJAX
        $I->waitForText('Artículo actualizado correctamente.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Modal cerrado y actualización en vista
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->expandirPrimerCatalogo($I);
        $I->seeElement(self::SUB_ITEM_FILA);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AI-11: Cancelación de Retiro de Artículo
     */
    public function probarCancelacionDeRetiro(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);
        $this->expandirPrimerCatalogo($I);

        // Localizar botón de retirar (papelera cbt_r) de una pieza disponible
        $btnRetirar = self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::SUB_ITEM_FILA . ':first-child button.cbt_r';
        $I->waitForElementVisible($btnRetirar, 10);
        $I->click($btnRetirar);

        // Diálogo SweetAlert2: pulsar "NO"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Desea retirar este artículo del inventario?', 5, self::SWAL_POPUP);
        $I->click('NO');

        // El registro sigue visible en el listado
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $this->expandirPrimerCatalogo($I);
        $I->seeElement($btnRetirar);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AI-12: Retiro Exitoso y Reincorporación de Artículo
     */
    public function probarRetiroYReincorporacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);
        $this->expandirPrimerCatalogo($I);

        // 1. Retirar pieza disponible
        $btnRetirar = self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::SUB_ITEM_FILA . ':first-child button.cbt_r';
        $I->waitForElementVisible($btnRetirar, 10);
        $I->click($btnRetirar);

        // Confirmar retiro con "SI"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Desea retirar este artículo del inventario?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito tras proceso almacenado
        $I->waitForText('El artículo ha sido retirado del inventario.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 2. Reincorporar pieza retirada (botón cbt_a)
        $this->expandirPrimerCatalogo($I);
        $btnReincorporar = self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ':first-child ' . self::SUB_ITEM_FILA . ':first-child button.cbt_a';
        $I->waitForElementVisible($btnReincorporar, 10);
        $I->click($btnReincorporar);

        // Confirmar reincorporación con "SI"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Desea reincorporar este artículo al inventario disponible?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito
        $I->waitForText('Artículo reincorporado como Disponible.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // La pieza vuelve a estar Disponible
        $this->expandirPrimerCatalogo($I);
        $I->seeElement($btnRetirar);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 5: MODAL DE REPORTES Y ASISTENCIA INTERACTIVA (RF-UI-05)
    // =========================================================================

    /**
     * CP-AI-13: Apertura y Configuración del Modal de Generar Reporte
     */
    public function probarModalGenerarReporte(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_REPORTE, 5);
        $I->click(self::BTN_REPORTE);

        // Modal debe abrirse configurado para reporte
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Generar Reporte de Inventario', 5, self::MODAL_TITULO);
        $I->see('Generar Reporte', self::BTN_GUARDAR);

        // Las opciones de catálogo y estado para filtrado deben ser visibles
        $I->seeElement(self::SECCION_INDIVIDUAL);
        $I->seeElement(self::COL_CATALOGO);
        $I->dontSeeElement(self::SECCION_LOTE);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-AI-14: Interacción y Despliegue del Tour Guiado de Ayuda
     */
    public function probarTourGuiadoAyuda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_AYUDA, 5);
        $I->click(self::BTN_AYUDA);

        // Verificar que Driver.js inicia el popover interactivo
        $I->waitForElementVisible('.driver-popover', 5);
        $I->waitForText('Búsqueda', 5, '.driver-popover');

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

    private function configurarLote(AcceptanceTester $I, int $indexCatalogo = 1, int $indexEstado = 1, int $cantidad = 1): array
    {
        $res = $I->executeJS("
            var selCat = $('#lote_catalogo');
            var selEst = $('#lote_estado');

            var optCat = selCat.find('option:not([disabled])').eq(" . ($indexCatalogo - 1) . ");
            var optEst = selEst.find('option:not([disabled])').eq(" . ($indexEstado - 1) . ");

            var valCat = optCat.val();
            var textCat = optCat.text();

            var valEst = optEst.val();
            var textEst = optEst.text();

            selCat.val(valCat).trigger('change');
            selEst.val(valEst).trigger('change');
            $('#lote_cantidad').val({$cantidad});

            return {
                idCatalogo: valCat,
                nombreCatalogo: textCat.trim(),
                idEstado: valEst,
                nombreEstado: textEst.trim(),
                cantidad: {$cantidad}
            };
        ");

        return is_array($res) ? $res : [
            'idCatalogo' => '1',
            'nombreCatalogo' => 'Disco de Ultimate Frisbee',
            'idEstado' => '1',
            'nombreEstado' => 'Buen Estado',
            'cantidad' => $cantidad
        ];
    }

    private function cambiarEstadoFisico(AcceptanceTester $I, int $indexEstado = 2): array
    {
        $res = $I->executeJS("
            var selEst = $('#id_estado');
            var optEst = selEst.find('option:not([disabled])').eq(" . ($indexEstado - 1) . ");
            var valEst = optEst.val();
            var textEst = optEst.text();

            selEst.val(valEst).trigger('change');

            return {
                idEstado: valEst,
                nombreEstado: textEst.trim()
            };
        ");

        return is_array($res) ? $res : [
            'idEstado' => '2',
            'nombreEstado' => 'Desgaste Medio'
        ];
    }

    private function expandirPrimerCatalogo(AcceptanceTester $I): void
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

