<?php

declare(strict_types=1);

namespace Tests\Acceptance\Inventario;

use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Interfaz y Aceptación con Codeception WebDriver
 * Módulo: Categorías de Catálogo (Inventario > Categorías de Catálogo)
 */
final class CategoriaCatalogoCest
{
    private const SECCION_MENU = 'Inventario';
    private const MODULO_HREF  = 'CategoriaCatalogo';

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
    private const LISTADO_VACIO     = '.listado_vacio';

    // Selectores del modal y formulario
    private const MODAL               = '#modal';
    private const MODAL_TITULO        = '#titulo_modal';
    private const MODAL_CERRAR        = '#cerrar_modal';
    private const MODAL_FORM          = '#f';
    private const INPUT_ID            = '#id_categoria';
    private const INPUT_NOMBRE        = '#nombre';
    private const INPUT_DESCRIPCION   = '#descripcion';
    private const SELECT_TIPO_TALLA   = '#tipo_talla';
    private const BTN_PROCESO         = '#proceso';

    // Selectores de SweetAlert2
    private const SWAL_POPUP          = '.swal2-popup';
    private const SWAL_CONFIRM        = 'button.swal2-confirm';
    private const SWAL_CANCEL         = 'button.swal2-cancel';

    // Datos de prueba (en Title Case debido a mb_convert_case MB_CASE_TITLE en el backend)
    private const NOMBRE_TEST            = 'Accesorios Entrenamiento Ui';
    private const NOMBRE_MODIFICAR       = 'Accesorios Modificados Ui';
    private const DESCRIPCION_TEST       = 'Equipamiento Para Practica';
    private const DESCRIPCION_MODIFICADA = 'Articulos Auxiliares';

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
     * CP-CC-01: Carga y Estructura Base del Módulo
     */
    public function probarCargaYEstructuraBase(AcceptanceTester $I): void
    {
        // 1. Expandir sección Inventario en el menú lateral
        $I->click($this->xpathSeccion(self::SECCION_MENU));
        $enlace = sprintf('a[href="%s"]', self::MODULO_HREF);
        $I->waitForElementVisible($enlace, 5);
        $I->see('Categorías de Catálogo', $enlace);
        $I->click($enlace);

        // 2. Verificar URL y Título principal
        $I->waitForText('Categorías de Catálogo', 10, self::TITULO);
        $I->seeInCurrentUrl(self::MODULO_HREF);

        // 3. Verificar controles principales de la interfaz
        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::RESULTADOS);

        // 4. Verificar renderizado del listado (items de categorías o listado vacío)
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
     * CP-CC-02: Búsqueda con Coincidencias en Tiempo Real
     */
    public function probarBusquedaConCoincidencias(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayItems = $I->executeJS("return $('.listado_item').length > 0;");
        if ($hayItems) {
            $nombreCat = $I->executeJS("return $('.listado_titulo:first').text().trim().split(' ')[0];");
            if (!empty($nombreCat)) {
                $I->fillField(self::BUSQUEDA, $nombreCat);
                $I->waitForText($nombreCat, 10, self::RESULTADOS);
                $I->seeElement(self::LISTADO_ITEM);
            }
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CC-03: Búsqueda sin coincidencias (filtro AJAX con debounce)
     */
    public function probarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, 'categoria_inexistente_xyz_999');

        // Espera respuesta AJAX y renderizado de lista vacía
        $I->waitForText('No se encontraron categorías registradas', 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CC-03: Restablecer búsqueda al vaciar el input
     */
    public function probarRestablecerBusqueda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, '');

        // Debe restablecer la lista de categorías registradas
        $I->waitForElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS . ' ' . self::LISTADO_VACIO, 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 3: APERTURA DE MODAL Y VALIDACIONES DE FORMULARIO (RF-UI-03)
    // =========================================================================

    /**
     * CP-CC-04: Apertura y Cierre del Modal de Registro
     */
    public function probarAperturaYCierreModal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        // Modal debe ser visible con su título y formulario
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Registrar Nueva Categoría', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->seeElement(self::INPUT_NOMBRE);
        $I->seeElement(self::INPUT_DESCRIPCION);
        $I->seeElement(self::SELECT_TIPO_TALLA);
        $I->see('Registrar Categoría', self::BTN_PROCESO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CC-05: Validación de Nombre Obligatorio / Vacío al Registrar
     */
    public function probarValidacionNombreVacio(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Clic en registrar sin ingresar nombre
        $I->fillField(self::INPUT_NOMBRE, '');
        $I->click(self::BTN_PROCESO);

        // SweetAlert2 de error de validación
        $I->waitForText('Debe ingresar un nombre de categoría válido', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CC-06: Validación de Longitud Mínima de Nombre
     */
    public function probarValidacionLongitudMinimaNombre(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Ingresar solo 1 carácter (el mínimo es 2)
        $I->fillField(self::INPUT_NOMBRE, 'A');
        $I->click(self::BTN_PROCESO);

        // SweetAlert2 de error de validación
        $I->waitForText('Debe ingresar un nombre de categoría válido', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CC-07: Validación de Formato con Caracteres Erróneos en Nombre
     */
    public function probarValidacionFormatoNombreErroneo(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Ingresar números y caracteres especiales no permitidos en el nombre
        $I->fillField(self::INPUT_NOMBRE, '12345 @#!');
        $I->click(self::BTN_PROCESO);

        // SweetAlert2 de error de validación
        $I->waitForText('Debe ingresar un nombre de categoría válido', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CC-08: Validación de Formato de Descripción
     */
    public function probarValidacionFormatoDescripcion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Ingresar nombre válido pero descripción con caracteres inválidos (números o símbolos)
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $I->fillField(self::INPUT_DESCRIPCION, 'Descripcion 123 @#');
        $I->click(self::BTN_PROCESO);

        // SweetAlert2 de error de validación de descripción
        $I->waitForText('Debe ingresar una descripción válida', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 4: CICLO DE VIDA DE LA CATEGORÍA DE CATÁLOGO (CRUD COMPLETO EN UI) (RF-UI-04)
    // =========================================================================

    /**
     * CP-CC-08: Registro Exitoso de Categoría con Diálogo de Confirmación
     */
    public function probarRegistroExitoso(AcceptanceTester $I): void
    {
        $this->limpiarRegistrosResiduales($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // 1. Completar formulario de categoría
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $I->fillField(self::INPUT_DESCRIPCION, self::DESCRIPCION_TEST);
        $this->seleccionarTipoTalla($I, 'Letras');

        // 2. Enviar formulario
        $I->click(self::BTN_PROCESO);

        // 3. Diálogo SweetAlert2 de confirmación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere registrar esta categoría?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // 4. Alerta de éxito tras respuesta AJAX
        $I->waitForText('Registro Exitoso', 10, self::SWAL_POPUP);
        $I->waitForText('Categoría registrada exitosamente.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 5. El modal se cierra y la nueva categoría se refleja en la lista
        $I->waitForElementNotVisible(self::MODAL, 5);
        $I->waitForText(self::NOMBRE_TEST, 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CC-09: Control de Duplicados en Interfaz
     */
    public function probarControlDeDuplicados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Reintentar registrar con el mismo nombre ya existente
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $I->fillField(self::INPUT_DESCRIPCION, self::DESCRIPCION_TEST);
        $I->click(self::BTN_PROCESO);

        // Confirmar en SweetAlert
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere registrar esta categoría?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Mensaje de error del backend por duplicidad
        $I->waitForText('Ya existe una categoría registrada con este nombre.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CC-10: Modificación Exitosa de Categoría
     */
    public function probarModificacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // 1. Localizar el botón de modificar de la categoría creada
        $xpathBotonModificar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_v")]',
            self::NOMBRE_TEST
        );
        $I->waitForElementVisible($xpathBotonModificar, 10);
        $I->click($xpathBotonModificar);

        // 2. Modal abre en modo edición con datos cargados
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Modificar Categoría', 5, self::MODAL_TITULO);
        $I->seeInField(self::INPUT_NOMBRE, self::NOMBRE_TEST);

        // 3. Modificar nombre, descripción y tipo de talla
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_MODIFICAR);
        $I->fillField(self::INPUT_DESCRIPCION, self::DESCRIPCION_MODIFICADA);
        $this->seleccionarTipoTalla($I, 'Numerico');
        $I->click(self::BTN_PROCESO);

        // 4. Confirmar modificación en SweetAlert2
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere modificar esta categoría?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // 5. Alerta de éxito
        $I->waitForText('Modificación Exitosa', 10, self::SWAL_POPUP);
        $I->waitForText('Categoría modificada exitosamente.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 6. Verificar actualización en el listado
        $I->waitForElementNotVisible(self::MODAL, 5);
        $I->waitForText(self::NOMBRE_MODIFICAR, 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CC-11: Cancelación de Eliminación de Categoría
     */
    public function probarCancelacionDeEliminacion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // 1. Localizar botón de eliminar de la categoría modificada
        $xpathBotonEliminar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_r")]',
            self::NOMBRE_MODIFICAR
        );
        $I->waitForElementVisible($xpathBotonEliminar, 10);
        $I->click($xpathBotonEliminar);

        // 2. SweetAlert2 de confirmación: Presionar "NO"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere eliminar esta categoría?', 5, self::SWAL_POPUP);
        $I->click('NO');

        // 3. El registro debe permanecer visible en el listado
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->see(self::NOMBRE_MODIFICAR, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CC-12: Eliminación Exitosa de Categoría
     */
    public function probarEliminacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // 1. Localizar botón de eliminar
        $xpathBotonEliminar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_r")]',
            self::NOMBRE_MODIFICAR
        );
        $I->waitForElementVisible($xpathBotonEliminar, 10);
        $I->click($xpathBotonEliminar);

        // 2. Confirmar eliminación con "SI"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere eliminar esta categoría?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // 3. Alerta de éxito
        $I->waitForText('Eliminación Exitosa', 10, self::SWAL_POPUP);
        $I->waitForText('Categoría eliminada exitosamente.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 4. El registro desaparece de la vista
        $I->waitForElementNotVisible($xpathBotonEliminar, 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 5: MODAL DE REPORTES Y ASISTENCIA INTERACTIVA (RF-UI-05)
    // =========================================================================

    /**
     * CP-CC-13: Apertura y Configuración del Modal de Generar Reporte
     */
    public function probarModalGenerarReporte(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_REPORTE, 5);
        $I->click(self::BTN_REPORTE);

        // Modal debe abrirse configurado para generación de reporte
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Generar Reporte', 5, self::MODAL_TITULO);
        $I->see('Generar Reporte', self::BTN_PROCESO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-CC-14: Tour Guiado Interactivo de Asistencia (Driver.js)
     */
    public function probarTourGuiadoAyuda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_AYUDA, 5);
        $I->click(self::BTN_AYUDA);

        // Verificar que Driver.js inicia el popover interactivo
        $I->waitForElementVisible('.driver-popover', 5);
        $I->waitForText('Búsqueda Rápida', 5, '.driver-popover');

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

    private function seleccionarTipoTalla(AcceptanceTester $I, string $tipoTalla): void
    {
        $I->executeJS(sprintf("
            var sel = $('#tipo_talla');
            sel.val('%s').trigger('change');
        ", addslashes($tipoTalla)));
    }

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

    private function verificarSinErroresPhp(AcceptanceTester $I): void
    {
        foreach (self::TEXTOS_DE_ERROR as $texto) {
            $I->dontSee($texto);
        }
    }
}

