<?php

declare(strict_types=1);

namespace Tests\Acceptance\Inventario;

use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Interfaz y Aceptación con Codeception WebDriver
 * Módulo: Catálogo de Artículos (Inventario > Catálogo)
 */
final class CatalogoCest
{
    private const SECCION_MENU = 'Inventario';
    private const MODULO_HREF  = 'Catalogo';

    // Selectores principales de la vista
    private const TITULO          = '#titulo';
    private const BUSQUEDA        = '#busqueda';
    private const RESULTADOS      = '#resultadoconsulta';
    private const BTN_NUEVO       = '#incluir';
    private const BTN_REPORTE     = '#generar';
    private const BTN_AYUDA       = '#ayuda';

    // Selectores del listado
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const LISTADO_ITEM      = '.listado_item';
    private const LISTADO_TITULO    = '.listado_titulo';
    private const LISTADO_SUBTITULO = '.listado_subtitulo';

    // Selectores del modal y formulario
    private const MODAL               = '#modal';
    private const MODAL_TITULO        = '#titulo_modal';
    private const MODAL_CERRAR        = '#cerrar_modal';
    private const MODAL_FORM          = '#f';
    private const INPUT_ID            = '#id_catalogo';
    private const INPUT_NOMBRE        = '#nombre';
    private const SELECT_CATEGORIA    = '#id_categoria';
    private const INPUT_STOCK_MINIMO  = '#stock_minimo';
    private const SELECT_TALLA        = '#talla';
    private const SELECT_POSICION     = '#codigo_posicion';
    private const BTN_PROCESO         = '#proceso';

    // Selectores de SweetAlert2
    private const SWAL_POPUP          = '.swal2-popup';
    private const SWAL_CONFIRM        = 'button.swal2-confirm';
    private const SWAL_CANCEL         = 'button.swal2-cancel';

    // Datos de prueba (en Title Case debido a mb_convert_case MB_CASE_TITLE en el backend)
    private const NOMBRE_TEST         = 'Cono Entrenamiento Ui Test';
    private const NOMBRE_MODIFICAR    = 'Cono Entrenamiento Ui Modificado';
    private const STOCK_MINIMO        = '15';
    private const STOCK_MODIFICAR     = '25';

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
     * CP-CA-01: Carga y Estructura Base del Módulo
     */
    public function probarCargaYEstructuraBase(AcceptanceTester $I): void
    {
        // 1. Expandir sección Inventario en el menú lateral
        $I->click($this->xpathSeccion(self::SECCION_MENU));
        $enlace = sprintf('a[href="%s"]', self::MODULO_HREF);
        $I->waitForElementVisible($enlace, 5);
        $I->see('Catálogo', $enlace);
        $I->click($enlace);

        // 2. Verificar URL y Título principal
        $I->waitForText('Catálogo', 10, self::TITULO);
        $I->seeInCurrentUrl(self::MODULO_HREF);

        // 3. Verificar controles principales de la interfaz
        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::RESULTADOS);

        // 4. Verificar renderizado del listado (items de catálogo o listado vacío)
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
     * CP-CA-02: Búsqueda sin coincidencias (filtro AJAX con debounce)
     */
    public function probarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, 'articulo_inexistente_xyz_999');

        // Espera respuesta AJAX y renderizado de lista vacía
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CA-03: Restablecer búsqueda al vaciar el input
     */
    public function probarRestablecerBusqueda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, '');

        // Debe restablecer la lista de artículos del catálogo
        $I->waitForElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS . ' .listado_vacio', 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 3: APERTURA DE MODAL Y VALIDACIONES DE FORMULARIO (RF-UI-03)
    // =========================================================================

    /**
     * CP-CA-04: Apertura y Cierre del Modal de Registro
     */
    public function probarAperturaYCierreModal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        // Modal debe ser visible con su título y formulario configurado para nuevo artículo
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Nuevo Artículo', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->seeElement(self::INPUT_NOMBRE);
        $I->seeElement(self::SELECT_CATEGORIA);
        $I->seeElement(self::INPUT_STOCK_MINIMO);
        $I->see('Registrar Artículo', self::BTN_PROCESO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CA-05: Validación de Selección de Categoría Obligatoria
     */
    public function probarValidacionCategoriaObligatoria(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Intentar registrar sin seleccionar categoría
        $this->limpiarCategoria($I);
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $I->fillField(self::INPUT_STOCK_MINIMO, self::STOCK_MINIMO);
        $I->click(self::BTN_PROCESO);

        // SweetAlert2 de error de validación
        $I->waitForText('Debe seleccionar una categoría.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CA-06: Validación de Formato y Longitud de Nombre de Artículo
     */
    public function probarValidacionNombreArticulo(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Seleccionar categoría pero dejar nombre inválido (< 3 caracteres)
        $this->seleccionarCategoria($I, 1);
        $I->fillField(self::INPUT_NOMBRE, 'Ab');
        $I->fillField(self::INPUT_STOCK_MINIMO, self::STOCK_MINIMO);
        $I->click(self::BTN_PROCESO);

        // SweetAlert2 de error de validación de nombre
        $I->waitForText('Tiene que ingresar un nombre válido.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CA-07: Validación de Cantidad Válida para Stock Mínimo
     */
    public function probarValidacionStockMinimo(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Llenar categoría y nombre válidos, pero vaciar stock mínimo
        $this->seleccionarCategoria($I, 1);
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $I->fillField(self::INPUT_STOCK_MINIMO, '');
        $I->click(self::BTN_PROCESO);

        // SweetAlert2 de error de validación de stock mínimo
        $I->waitForText('Tiene que ingresar una cantidad válida para el stock mínimo.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 4: CICLO DE VIDA DEL ARTÍCULO DE CATÁLOGO (CRUD COMPLETO EN UI) (RF-UI-04)
    // =========================================================================

    /**
     * CP-CA-08: Registro Exitoso de Artículo en Catálogo con Diálogo de Confirmación
     */
    public function probarRegistroExitoso(AcceptanceTester $I): void
    {
        $this->limpiarRegistrosResiduales($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // 1. Completar formulario de catálogo
        $this->seleccionarCategoria($I, 1);
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $I->fillField(self::INPUT_STOCK_MINIMO, self::STOCK_MINIMO);

        // 2. Enviar formulario
        $I->click(self::BTN_PROCESO);

        // 3. Diálogo SweetAlert2 de confirmación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere registrar este artículo?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // 4. Alerta de éxito tras respuesta AJAX
        $I->waitForText('Registro Exitoso', 10, self::SWAL_POPUP);
        $I->waitForText('Artículo registrado exitosamente en el catálogo.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 5. El modal se cierra y el nuevo artículo se refleja en la lista
        $I->waitForElementNotVisible(self::MODAL, 5);
        $I->waitForText(self::NOMBRE_TEST, 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CA-09: Modificación Exitosa de Artículo en Catálogo
     */
    public function probarModificacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // 1. Localizar el botón de modificar del artículo registrado
        $xpathBotonModificar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(@class, "listado_titulo") and contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_v")]',
            self::NOMBRE_TEST
        );
        $I->waitForElementVisible($xpathBotonModificar, 10);
        $I->click($xpathBotonModificar);

        // 2. Modal abre en modo edición con datos cargados
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Modificar Catálogo', 5, self::MODAL_TITULO);
        $I->seeInField(self::INPUT_NOMBRE, self::NOMBRE_TEST);

        // 3. Modificar nombre y stock mínimo
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_MODIFICAR);
        $I->fillField(self::INPUT_STOCK_MINIMO, self::STOCK_MODIFICAR);
        $I->click(self::BTN_PROCESO);

        // 4. Confirmar modificación en SweetAlert2
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere modificar este artículo?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // 5. Alerta de éxito
        $I->waitForText('Modificación Exitosa', 10, self::SWAL_POPUP);
        $I->waitForText('Catálogo modificado exitosamente.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 6. Verificar actualización en el listado
        $I->waitForElementNotVisible(self::MODAL, 5);
        $I->waitForText(self::NOMBRE_MODIFICAR, 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CA-10: Cancelación de Eliminación de Artículo de Catálogo
     */
    public function probarCancelacionDeEliminacion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // 1. Localizar botón de eliminar del artículo modificado
        $xpathBotonEliminar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(@class, "listado_titulo") and contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_r")]',
            self::NOMBRE_MODIFICAR
        );
        $I->waitForElementVisible($xpathBotonEliminar, 10);
        $I->click($xpathBotonEliminar);

        // 2. SweetAlert2 de confirmación: Presionar "NO"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere eliminar este artículo del catálogo?', 5, self::SWAL_POPUP);
        $I->click('NO');

        // 3. El registro debe permanecer visible en el listado
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->see(self::NOMBRE_MODIFICAR, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CA-11: Eliminación Exitosa de Artículo de Catálogo
     */
    public function probarEliminacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // 1. Localizar botón de eliminar
        $xpathBotonEliminar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(@class, "listado_titulo") and contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_r")]',
            self::NOMBRE_MODIFICAR
        );
        $I->waitForElementVisible($xpathBotonEliminar, 10);
        $I->click($xpathBotonEliminar);

        // 2. Confirmar eliminación con "SI"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere eliminar este artículo del catálogo?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // 3. Alerta de éxito
        $I->waitForText('Eliminación Exitosa', 10, self::SWAL_POPUP);
        $I->waitForText('Artículo eliminado exitosamente.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 4. El registro desaparece de la vista
        $I->waitForElementNotVisible($xpathBotonEliminar, 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 5: MODAL DE REPORTES, ASISTENCIA INTERACTIVA Y DINAMISMO DE TALLAS (RF-UI-05)
    // =========================================================================

    /**
     * CP-CA-12: Apertura y Configuración del Modal de Generar Reporte
     */
    public function probarModalGenerarReporte(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_REPORTE, 5);
        $I->click(self::BTN_REPORTE);

        // Modal debe abrirse configurado para generación de reporte
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Reporte de Catálogo', 5, self::MODAL_TITULO);
        $I->see('Generar Reporte', self::BTN_PROCESO);

        // En modo reporte los campos individuales de nombre, stock y posición se ocultan
        $I->dontSeeElement(self::INPUT_NOMBRE);
        $I->dontSeeElement(self::INPUT_STOCK_MINIMO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CA-13: Tour Guiado Interactivo de Asistencia (Driver.js)
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

    /**
     * CP-CA-14: Actualización Dinámica del Selector de Tallas según Categoría
     */
    public function probarActualizacionDinamicaTallas(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // 1. Al abrir inicialmente sin categoría, talla tiene opción por defecto
        $I->see('Seleccione una categoría primero', self::SELECT_TALLA);

        // 2. Al seleccionar una categoría, el selector de talla se actualiza dinámicamente con opciones
        $this->seleccionarCategoria($I, 1);
        $I->waitForElement(self::SELECT_TALLA . ' option[value=""]', 5);
        $I->see('Ninguna', self::SELECT_TALLA);

        // 3. Cerrar modal
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

    private function xpathSeccion(string $nombre): string
    {
        return sprintf(
            '//li[contains(@class, "nav_identificador")][.//span[contains(@class, "identificador_texto") and contains(normalize-space(.), "%s")]]',
            $nombre
        );
    }

    private function seleccionarCategoria(AcceptanceTester $I, int $indexCategoria = 1): array
    {
        $res = $I->executeJS("
            var selCat = $('#id_categoria');
            var optCat = selCat.find('option:not([disabled])').eq(" . ($indexCategoria - 1) . ");
            var valCat = optCat.val();
            var textCat = optCat.text();

            selCat.val(valCat).trigger('change');

            return {
                idCategoria: valCat,
                nombreCategoria: textCat.trim()
            };
        ");

        return is_array($res) ? $res : [
            'idCategoria' => '1',
            'nombreCategoria' => 'Indumentaria Deportiva'
        ];
    }

    private function limpiarCategoria(AcceptanceTester $I): void
    {
        $I->executeJS("$('#id_categoria').val(null).trigger('change');");
    }

    private function limpiarRegistrosResiduales(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        foreach ([self::NOMBRE_TEST, self::NOMBRE_MODIFICAR] as $nombre) {
            $hayResidual = $I->executeJS("
                var encontrado = false;
                document.querySelectorAll('.listado_item .listado_titulo').forEach(function(el) {
                    if (el.textContent.trim() === '{$nombre}') {
                        encontrado = true;
                    }
                });
                return encontrado;
            ");

            if ($hayResidual) {
                $xpathBotonEliminar = sprintf(
                    '//div[contains(@class, "listado_item")][.//span[contains(@class, "listado_titulo") and contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_r")]',
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

    private function verificarSinErroresPhp(AcceptanceTester $I): void
    {
        foreach (self::TEXTOS_DE_ERROR as $texto) {
            $I->dontSee($texto);
        }
    }
}

