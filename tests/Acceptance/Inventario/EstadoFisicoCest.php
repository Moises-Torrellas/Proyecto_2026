<?php

declare(strict_types=1);

namespace Tests\Acceptance\Inventario;

use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Interfaz y Aceptación con Codeception WebDriver
 * Módulo: Estado Físico (Inventario > Estado Físico)
 */
final class EstadoFisicoCest
{
    private const SECCION_MENU = 'Inventario';
    private const MODULO_HREF  = 'EstadoFisico';

    // Selectores principales de la vista
    private const TITULO          = '#titulo';
    private const BUSQUEDA        = '#busqueda';
    private const RESULTADOS      = '#resultadoconsulta';
    private const BTN_NUEVO       = '#incluir';
    private const BTN_REPORTE     = '#generar';

    // Selectores del modal y formulario
    private const MODAL           = '#modal';
    private const MODAL_TITULO    = '#titulo_modal';
    private const MODAL_CERRAR    = '#cerrar_modal';
    private const MODAL_FORM      = '#f';
    private const INPUT_NOMBRE    = '#nombre';
    private const SELECT_NIVEL    = '#nivel_estado';
    private const BTN_PROCESO     = '#proceso';
    private const CONT_NOMBRE     = '#contenedor_nombre';

    // Selectores de SweetAlert2
    private const SWAL_POPUP      = '.swal2-popup';
    private const SWAL_CONFIRM    = 'button.swal2-confirm';
    private const SWAL_CANCEL     = 'button.swal2-cancel';

    // Datos para pruebas CRUD
    private const NOMBRE_TEST      = 'Prueba Ui Automatizada';
    private const NOMBRE_MODIFICAR = 'Prueba Ui Modificada';

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
    // ESCENARIO 1: NAVEGACIÓN Y RENDERIZADO DE INTERFAZ
    // =========================================================================

    /**
     * CP-EF-01: Carga y Estructura Base del Módulo
     */
    public function probarCargaYEstructuraBase(AcceptanceTester $I): void
    {
        // 1. Expandir la sección Inventario en el menú lateral
        $I->click($this->xpathSeccion(self::SECCION_MENU));
        $enlace = sprintf('a[href="%s"]', self::MODULO_HREF);
        $I->waitForElementVisible($enlace, 5);
        $I->see('Estado Físico', $enlace);
        $I->click($enlace);

        // 2. Verificar URL y Título principal
        $I->waitForText('Estado Físico', 10, self::TITULO);
        $I->seeInCurrentUrl(self::MODULO_HREF);

        // 3. Verificar controles principales de la interfaz
        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::RESULTADOS);

        // 4. Verificar que el listado se renderiza (registros o listado vacío)
        $I->seeElement(self::RESULTADOS . ' .listado_item, ' . self::RESULTADOS . ' .listado_vacio');

        // 5. El modal debe existir en el DOM pero permanecer oculto inicialmente
        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeElement(self::MODAL);

        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 2: BÚSQUEDA Y FILTRO DINÁMICO (AJAX)
    // =========================================================================

    /**
     * CP-EF-02: Búsqueda con coincidencias en tiempo real
     */
    public function probarBusquedaConCoincidencias(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayRegistros = $I->executeJS("return $('.listado_item').length > 0;");
        if ($hayRegistros) {
            $nombreEstado = $I->executeJS("return $('.listado_item:first span').text().trim().split(' ')[0];");
            if (!empty($nombreEstado)) {
                $I->fillField(self::BUSQUEDA, $nombreEstado);
                $I->waitForText($nombreEstado, 10, self::RESULTADOS);
                $I->seeElement(self::RESULTADOS . ' .listado_item');
            }
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EF-03: Búsqueda sin coincidencias (filtro AJAX)
     */
    public function probarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, 'cadena_inexistente_xyz_999');

        // Espera respuesta AJAX y renderizado de lista vacía
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EF-04: Restablecer búsqueda al vaciar el input
     */
    public function probarRestablecerBusqueda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, '');

        // Debe restablecer la lista completa
        $I->waitForElement(self::RESULTADOS . ' .listado_item, ' . self::RESULTADOS . ' .listado_vacio', 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 3: COMPORTAMIENTO Y VALIDACIÓN DEL MODAL DE REGISTRO
    // =========================================================================

    /**
     * CP-EF-05: Apertura y Cierre del Modal de Registro
     */
    public function probarAperturaYCierreModal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        // Modal debe ser visible con su título y formulario
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Registrar Nuevo Estado Físico', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->see('Registrar Estado', self::BTN_PROCESO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EF-06: Validación por Campos Vacíos al Intentar Registrar
     */
    public function probarValidacionCamposVacios(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Intentar registrar sin datos
        $I->click(self::BTN_PROCESO);

        // Esperar SweetAlert de error de validación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->see('Debe ingresar un nombre válido para el estado físico.', self::SWAL_POPUP);

        // Cerrar alerta y modal
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->click(self::MODAL_CERRAR);
    }

    /**
     * CP-EF-07: Validación con Datos Erróneos en Input Nombre (Números y Símbolos)
     */
    public function probarValidacionFormatoNombreErroneo(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Ingresar datos erróneos que violan el formato (números y caracteres especiales)
        $I->fillField(self::INPUT_NOMBRE, 'Estado 123 #$%');
        $I->selectOption(self::SELECT_NIVEL, '1');
        $I->click(self::BTN_PROCESO);

        // Esperar SweetAlert de error de validación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->see('Debe ingresar un nombre válido para el estado físico.', self::SWAL_POPUP);

        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EF-08: Validación de Nivel de Condición Faltante
     */
    public function probarValidacionNivelCondicionFaltante(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Llenar nombre pero dejar nivel sin seleccionar
        $I->fillField(self::INPUT_NOMBRE, 'Estado Temporal');
        $I->click(self::BTN_PROCESO);

        // Esperar SweetAlert de error de validación de nivel
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->see('Tiene que elegir una opción de nivel.', self::SWAL_POPUP);

        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->click(self::MODAL_CERRAR);
    }

    // =========================================================================
    // ESCENARIO 4: CICLO DE VIDA DEL REGISTRO (CRUD COMPLETO EN UI)
    // =========================================================================

    /**
     * CP-EF-07: Registro Exitoso con Diálogo de Confirmación
     */
    public function probarRegistroExitoso(AcceptanceTester $I): void
    {
        $this->limpiarRegistrosResiduales($I);
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Rellenar formulario
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $I->selectOption(self::SELECT_NIVEL, '1'); // 1 = Buen Estado
        $I->click(self::BTN_PROCESO);

        // Diálogo SweetAlert de confirmación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere registrar este estado físico?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito (esperar respuesta AJAX)
        $I->waitForText('Registro Exitoso', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Modal debe cerrarse automáticamente y el nuevo registro debe listarse
        $I->waitForElementNotVisible(self::MODAL, 5);
        $I->waitForText(self::NOMBRE_TEST, 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EF-08: Control de Duplicados en Interfaz
     */
    public function probarControlDeDuplicados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Reintentar registrar el mismo nombre
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $I->selectOption(self::SELECT_NIVEL, '1');
        $I->click(self::BTN_PROCESO);

        // Confirmar en SweetAlert
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere registrar este estado físico?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Mensaje de error por duplicidad (esperar respuesta AJAX)
        $I->waitForText('Ya existe un estado físico registrado con este nombre.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EF-09: Modificación Exitosa de Estado Físico
     */
    public function probarModificacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // Localizar el botón de modificar del registro creado
        $xpathBotonModificar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_v")]',
            self::NOMBRE_TEST
        );
        $I->waitForElementVisible($xpathBotonModificar, 10);
        $I->click($xpathBotonModificar);

        // Esperar apertura de modal de edición y carga de datos
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Modificar Estado Físico', 5, self::MODAL_TITULO);
        $I->seeInField(self::INPUT_NOMBRE, self::NOMBRE_TEST);

        // Modificar datos
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_MODIFICAR);
        $I->selectOption(self::SELECT_NIVEL, '2'); // 2 = Desgaste Medio
        $I->click(self::BTN_PROCESO);

        // Confirmar modificación
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere modificar este estado físico?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito (esperar respuesta AJAX)
        $I->waitForText('Modificación Exitosa', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // Verificar actualización en la vista
        $I->waitForElementNotVisible(self::MODAL, 5);
        $I->waitForText(self::NOMBRE_MODIFICAR, 10, self::RESULTADOS);
        $I->see('Desgaste Medio', self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EF-10: Cancelación de Eliminación de Estado Físico
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

        // Confirmación en SweetAlert: Presionar "NO"
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere eliminar este estado físico?', 5, self::SWAL_POPUP);
        $I->click('NO');

        // El registro sigue existiendo en el listado
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->see(self::NOMBRE_MODIFICAR, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-EF-11: Eliminación Exitosa de Estado Físico
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
        $I->waitForText('¿Está seguro que quiere eliminar este estado físico?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // Alerta de éxito (esperar respuesta AJAX)
        $I->waitForText('Eliminación Exitosa', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // El registro desaparece de la vista
        $I->waitForElementNotVisible($xpathBotonEliminar, 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 5: MODAL DE REPORTES
    // =========================================================================

    /**
     * CP-EF-12: Apertura y Configuración del Modal de Generar Reporte
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

        // En modo reporte el campo nombre se oculta
        $I->dontSeeElement(self::CONT_NOMBRE);

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

    private function verificarSinErroresPhp(AcceptanceTester $I): void
    {
        foreach (self::TEXTOS_DE_ERROR as $texto) {
            $I->dontSee($texto);
        }
    }
}

