<?php

declare(strict_types=1);

namespace Tests\Acceptance\HistorialDeportivo;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

/**
 * Pruebas de Interfaz y Aceptación con Codeception WebDriver
 * Módulo: Premios (Historial Deportivo > Premios)
 *
 * Sigue la estructura y buenas prácticas de pruebas de interfaz del sistema
 * (ej. Asignaciones, Catálogo, Torneos), validando la navegación, controles visuales,
 * filtros AJAX, validaciones del formulario en cliente, ciclo CRUD completo,
 * modal de reportes y tour guiado de asistencia (Driver.js).
 */
#[Group('premios')]
#[Group('interfaz')]
final class PremiosCest
{
    private const SECCION_MENU = 'Historial Deportivo';
    private const MODULO_HREF  = 'Premios';

    // Selectores principales de la vista
    private const TITULO          = '#titulo';
    private const BUSQUEDA        = '#busqueda';
    private const RESULTADOS      = '#resultadoconsulta';
    private const BTN_NUEVO       = '#incluir';
    private const BTN_REPORTE     = '#generar';
    private const BTN_AYUDA       = '#ayuda';

    // Selectores del listado y tarjetas
    private const CONTENEDOR_GRUPAL = '.listado_contenedor_grupal';
    private const LISTADO_ITEM      = '.listado_item';
    private const LISTADO_VACIO     = '.listado_vacio';
    private const BTN_EDITAR        = 'button.cbt_v';
    private const BTN_ELIMINAR      = 'button.cbt_r';

    // Selectores del modal y formulario
    private const MODAL             = '#modal';
    private const MODAL_TITULO      = '#titulo_modal';
    private const MODAL_CERRAR      = '#cerrar_modal';
    private const MODAL_FORM        = '#f';
    private const INPUT_ID          = '#codigo_premio';
    private const INPUT_NOMBRE      = '#nombre';
    private const SELECT_TIPO       = '#tipo';
    private const BTN_PROCESO       = '#proceso';

    // Selectores de SweetAlert2
    private const SWAL_POPUP        = '.swal2-popup';
    private const SWAL_CONFIRM      = 'button.swal2-confirm';
    private const SWAL_CANCEL       = 'button.swal2-cancel';

    // Datos para pruebas transaccionales (Title Case requerido por ModeloPremios)
    private const NOMBRE_TEST       = 'Premio Excelencia Ui Test';
    private const NOMBRE_MODIFICAR  = 'Premio Excelencia Ui Modificado';
    private const TIPO_INDIVIDUAL   = 'I';
    private const TIPO_GRUPAL       = 'G';

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
     * CP-PR-01: Carga y Estructura Base del Módulo desde el Menú Lateral
     */
    public function probarCargaYEstructuraBase(AcceptanceTester $I): void
    {
        // 1. Expandir sección 'Historial Deportivo' en el menú lateral
        $I->click($this->xpathSeccion(self::SECCION_MENU));
        $enlace = sprintf('a[href="%s"]', self::MODULO_HREF);
        $I->waitForElementVisible($enlace, 5);
        $I->see('Premios', $enlace);
        $I->click($enlace);

        // 2. Verificar URL y Título principal
        $I->waitForText('Premios', 10, self::TITULO);
        $I->seeInCurrentUrl(self::MODULO_HREF);

        // 3. Verificar controles principales de la interfaz
        $I->seeElement(self::BUSQUEDA);
        $I->seeElement(self::BTN_NUEVO);
        $I->seeElement(self::BTN_REPORTE);
        $I->seeElement(self::RESULTADOS);

        // 4. Verificar renderizado del listado (elementos o estado vacío)
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
     * CP-PR-02: Búsqueda con Coincidencias en Tiempo Real (Filtro AJAX con Debounce)
     */
    public function probarBusquedaConCoincidencias(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $hayRegistros = $I->executeJS("return $('.listado_contenedor_grupal').length > 0;");
        if ($hayRegistros) {
            $nombrePremio = (string) $I->executeJS("
                return $('.listado_contenedor_grupal:first .listado_dato_grupo span').first().text().trim();
            ");

            if (!empty($nombrePremio)) {
                $I->fillField(self::BUSQUEDA, $nombrePremio);
                $I->waitForText($nombrePremio, 10, self::RESULTADOS);
                $I->seeElement(self::CONTENEDOR_GRUPAL);
            }
        }

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PR-03: Búsqueda sin Coincidencias en el Listado
     */
    public function probarBusquedaSinResultados(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, 'premio_inexistente_xyz_999');

        // Espera respuesta AJAX y renderizado del mensaje de lista vacía
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PR-04: Restablecer Búsqueda al Vaciar el Campo de Entrada
     */
    public function probarRestablecerBusqueda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BUSQUEDA, 5);
        $I->fillField(self::BUSQUEDA, '');
        $I->executeJS("$('#busqueda').trigger('keyup');");

        // Debe restablecer la lista de premios o el estado vacío original
        $I->waitForElement(self::RESULTADOS . ' ' . self::CONTENEDOR_GRUPAL . ', ' . self::RESULTADOS . ' ' . self::LISTADO_VACIO, 10);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 3: MODAL Y VALIDACIONES DE FORMULARIO EN CLIENTE (RF-UI-03)
    // =========================================================================

    /**
     * CP-PR-05: Apertura y Cierre Limpio del Modal de Registro
     */
    public function probarAperturaYCierreModal(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);

        // Modal debe ser visible con su título y formulario configurado para inclusión
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Registrar Premio', 5, self::MODAL_TITULO);
        $I->seeElement(self::MODAL_FORM);
        $I->seeElement(self::INPUT_NOMBRE);
        $I->seeElement(self::SELECT_TIPO);
        $I->see('Registrar Premio', self::BTN_PROCESO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PR-06: Validación de Tipo de Premio Obligatorio
     */
    public function probarValidacionTipoObligatorio(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Ingresar un nombre válido pero dejar el tipo sin seleccionar
        $I->fillField(self::INPUT_NOMBRE, 'Trofeo Campeonato');
        $this->seleccionarTipo($I, '');
        $I->click(self::BTN_PROCESO);

        // Alerta SweetAlert2 de validación de tipo
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('Tiene que elegir una opción válida para el tipo de premio', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PR-07: Validación de Nombre de Premio Obligatorio
     */
    public function probarValidacionNombreObligatorio(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // Seleccionar tipo pero dejar el campo nombre vacío
        $this->seleccionarTipo($I, self::TIPO_INDIVIDUAL);
        $I->fillField(self::INPUT_NOMBRE, '');
        $I->click(self::BTN_PROCESO);

        // Alerta SweetAlert2 de validación de nombre
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('Tiene que ingresar un nombre válido', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PR-08: Validación de Formato de Nombre Inválido (Longitud Menor o Caracteres Inválidos)
     */
    public function probarValidacionNombreFormatoInvalido(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // 1. Longitud menor a 3 caracteres
        $this->seleccionarTipo($I, self::TIPO_INDIVIDUAL);
        $I->fillField(self::INPUT_NOMBRE, 'AB');
        $I->click(self::BTN_PROCESO);

        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('Tiene que ingresar un nombre válido', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 2. Caracteres especiales no permitidos
        $I->fillField(self::INPUT_NOMBRE, 'Premio @#$%');
        $I->click(self::BTN_PROCESO);

        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('Tiene que ingresar un nombre válido', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 4: CICLO TRANSACCIONAL COMPLETO DEL PREMIO (CRUD) (RF-UI-04)
    // =========================================================================

    /**
     * CP-PR-09: Registro Exitoso de un Nuevo Premio con Confirmación SweetAlert2
     */
    public function probarRegistroExitoso(AcceptanceTester $I): void
    {
        $this->limpiarRegistrosResiduales($I);
        $this->irAlModulo($I);

        // 1. Abrir modal de nuevo premio
        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        // 2. Rellenar formulario con datos válidos
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $this->seleccionarTipo($I, self::TIPO_INDIVIDUAL);

        // 3. Enviar formulario
        $I->click(self::BTN_PROCESO);

        // 4. Confirmar en el diálogo de pregunta SweetAlert2
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere registrar este premio?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // 5. Verificar mensaje de éxito
        $I->waitForText('Registro Exitoso', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 6. Modal cerrado y premio visible en la lista
        $I->waitForElementNotVisible(self::MODAL, 5);
        $I->fillField(self::BUSQUEDA, self::NOMBRE_TEST);
        $I->executeJS("$('#busqueda').trigger('keyup');");
        $I->wait(1);
        $I->waitForText(self::NOMBRE_TEST, 10, self::RESULTADOS);
        $I->see('Individual', self::RESULTADOS);

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PR-10: Validación de Nombre de Premio Duplicado
     */
    public function probarValidacionNombreDuplicado(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // Intentar registrar el mismo nombre existente
        $I->waitForElementVisible(self::BTN_NUEVO, 5);
        $I->click(self::BTN_NUEVO);
        $I->waitForElementVisible(self::MODAL, 5);

        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_TEST);
        $this->seleccionarTipo($I, self::TIPO_INDIVIDUAL);
        $I->click(self::BTN_PROCESO);

        // Confirmar envío
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->click('SI');

        // Backend debe responder con error de nombre duplicado
        $I->waitForText('Ya existe un Premio registrado con ese Nombre.', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PR-11: Modificación Exitosa de un Premio
     */
    public function probarModificacionPremio(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        // 1. Filtrar el registro a modificar
        $I->fillField(self::BUSQUEDA, self::NOMBRE_TEST);
        $I->executeJS("$('#busqueda').trigger('keyup');");
        $I->wait(1);
        $I->waitForText(self::NOMBRE_TEST, 10, self::RESULTADOS);

        // 2. Hacer clic en el botón modificar de ese registro
        $xpathEditar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_v")]',
            self::NOMBRE_TEST
        );
        $I->waitForElementVisible($xpathEditar, 5);
        $I->click($xpathEditar);

        // 3. Modal se abre en modo modificación con los datos precargados
        $I->waitForElementVisible(self::MODAL, 5);
        $I->waitForText('Modificar Premio', 5, self::MODAL_TITULO);
        $I->see('Modificar Premio', self::BTN_PROCESO);

        // 4. Modificar el nombre y cambiar el tipo a Grupal
        $I->fillField(self::INPUT_NOMBRE, self::NOMBRE_MODIFICAR);
        $this->seleccionarTipo($I, self::TIPO_GRUPAL);

        // 5. Guardar cambios
        $I->click(self::BTN_PROCESO);
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere modificar este premio?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // 6. Verificar mensaje de éxito
        $I->waitForText('Modificación Exitosa', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);
        $I->waitForElementNotVisible(self::MODAL, 5);

        // 7. Verificar cambios reflejados en el listado
        $I->fillField(self::BUSQUEDA, self::NOMBRE_MODIFICAR);
        $I->executeJS("$('#busqueda').trigger('keyup');");
        $I->wait(1);
        $I->waitForText(self::NOMBRE_MODIFICAR, 10, self::RESULTADOS);
        $I->see('Grupal', self::RESULTADOS);

        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PR-12: Cancelación de Eliminación de Premio
     */
    public function probarCancelacionEliminacion(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->fillField(self::BUSQUEDA, self::NOMBRE_MODIFICAR);
        $I->executeJS("$('#busqueda').trigger('keyup');");
        $I->wait(1);
        $I->waitForText(self::NOMBRE_MODIFICAR, 10, self::RESULTADOS);

        $xpathEliminar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_r")]',
            self::NOMBRE_MODIFICAR
        );
        $I->waitForElementVisible($xpathEliminar, 5);

        // 1. Clic en eliminar y cancelar
        $I->click($xpathEliminar);
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere eliminar este premio?', 5, self::SWAL_POPUP);
        $I->click('NO');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 2. El registro sigue visible en el listado
        $I->see(self::NOMBRE_MODIFICAR, self::RESULTADOS);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PR-13: Eliminación Confirmada y Exitosa de Premio
     */
    public function probarEliminacionExitosa(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->fillField(self::BUSQUEDA, self::NOMBRE_MODIFICAR);
        $I->executeJS("$('#busqueda').trigger('keyup');");
        $I->wait(1);
        $I->waitForText(self::NOMBRE_MODIFICAR, 10, self::RESULTADOS);

        $xpathEliminar = sprintf(
            '//div[contains(@class, "listado_item")][.//span[contains(normalize-space(.), "%s")]]//button[contains(@class, "cbt_r")]',
            self::NOMBRE_MODIFICAR
        );
        $I->waitForElementVisible($xpathEliminar, 5);

        // 1. Clic en eliminar y confirmar
        $I->click($xpathEliminar);
        $I->waitForElementVisible(self::SWAL_POPUP, 5);
        $I->waitForText('¿Está seguro que quiere eliminar este premio?', 5, self::SWAL_POPUP);
        $I->click('SI');

        // 2. Confirmación de éxito
        $I->waitForText('Eliminación Exitosa', 10, self::SWAL_POPUP);
        $I->click('Entendido');
        $I->waitForElementNotVisible(self::SWAL_POPUP, 5);

        // 3. El registro ya no existe en el sistema
        $I->fillField(self::BUSQUEDA, self::NOMBRE_MODIFICAR);
        $I->executeJS("$('#busqueda').trigger('keyup');");
        $I->wait(1);
        $I->waitForText('No se encontraron registros', 10, self::RESULTADOS);

        $this->verificarSinErroresPhp($I);
    }

    // =========================================================================
    // ESCENARIO 5: MODAL DE REPORTES Y ASISTENCIA INTERACTIVA (RF-UI-05)
    // =========================================================================

    /**
     * CP-PR-14: Apertura y Configuración del Modal de Generar Reporte
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

        // El campo nombre se oculta, el filtro de tipo permanece visible
        $I->dontSeeElement(self::INPUT_NOMBRE);
        $I->seeElement(self::SELECT_TIPO);

        // Cerrar modal
        $I->click(self::MODAL_CERRAR);
        $I->waitForElementNotVisible(self::MODAL, 5);
        $this->verificarSinErroresPhp($I);
    }

    /**
     * CP-PR-15: Tour Guiado Interactivo de Asistencia (Driver.js)
     */
    public function probarTourGuiadoAyuda(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        $I->waitForElementVisible(self::BTN_AYUDA, 5);
        $I->click(self::BTN_AYUDA);

        // Verificar que Driver.js inicia el popover interactivo
        $I->waitForElementVisible('.driver-popover', 5);
        $I->waitForText('Barra de Búsqueda', 5, '.driver-popover');

        // Cerrar tour guiado limpiamente
        $I->executeJS("
            if (window.driver && typeof window.driver.destroy === 'function') {
                window.driver.destroy();
            } else if (window.driverObj && typeof window.driverObj.destroy === 'function') {
                window.driverObj.destroy();
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

    private function seleccionarTipo(AcceptanceTester $I, string $tipo): void
    {
        $I->executeJS(sprintf("
            var \$sel = $('#tipo');
            \$sel.val('%s').trigger('change');
        ", addslashes($tipo)));
    }

    /**
     * Limpia registros residuales creados por ejecuciones de prueba previas
     */
    private function limpiarRegistrosResiduales(AcceptanceTester $I): void
    {
        $this->irAlModulo($I);

        foreach ([self::NOMBRE_TEST, self::NOMBRE_MODIFICAR] as $nombre) {
            $hayResidual = $I->executeJS(sprintf("
                var encontrado = false;
                document.querySelectorAll('.listado_item span').forEach(function(el) {
                    if (el.textContent.trim().toLowerCase() === '%s'.toLowerCase()) {
                        encontrado = true;
                    }
                });
                return encontrado;
            ", addslashes($nombre)));

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
                $I->wait(1);
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
