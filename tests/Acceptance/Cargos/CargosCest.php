<?php

declare(strict_types=1);

namespace Tests\Acceptance\Cargos;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('cargos')]
#[Group('interfaz')]
final class CargosCest
{
    private const RUTA_MODULO           = '/Cargos';
    private const TITULO_MODULO         = 'Cargos';

    // Selectores específicos del módulo de Cargos (Cuentas por Cobrar)
    private const BTN_GENERAR           = '#generar';
    private const SELECT_ATLETA         = '#id_atleta';
    private const SELECT_CONCEPTO       = '#id_concepto';
    private const INPUT_MONTO_TOTAL     = '#monto_total';
    private const INPUT_FECHA_EMISION   = '#fecha_emision';
    private const SELECT_ESTATUS        = '#estatus';
    private const CONT_CAMPOS_CARGO     = '#campos_cargo';
    private const CONT_RANGO_FECHAS     = '#rango_fechas';

    // Selectores en las filas y tarjetas de cargos
    private const CONT_SUB_ITEMS        = '.lista_sub_items';
    private const FILA_SUB_ITEM         = '.sub_item_fila';
    private const TARJETA_RESUMEN       = '.tarjeta_resumen';
    private const BTN_MODIFICAR_CARGO   = '.sub_item_acciones .cbt_v';
    private const BTN_ANULAR_CARGO      = '.sub_item_acciones .cbt_r';

    public function _before(AcceptanceTester $I): void
    {
        // Login con snapshot + navegación a /Cargos + verificación de título y limpieza de overlays
        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
    }

    /**
     * Prueba 1: Verifica la carga inicial de la interfaz del módulo de Cargos.
     */
    #[Group('carga', 'interfaz')]
    public function probarCargaInicialDeInterfaz(AcceptanceTester $I): void
    {
        $I->see(self::TITULO_MODULO, AcceptanceTester::TITULO_PAGINA);
        $I->seeElement(AcceptanceTester::INPUT_BUSQUEDA);
        $I->seeElement(AcceptanceTester::BTN_NUEVO);
        $I->seeElement(self::BTN_GENERAR);
        $I->seeElement(AcceptanceTester::CONT_RESULTADOS);

        // El modal existe en el DOM pero debe estar oculto al iniciar
        $I->seeElementInDOM(AcceptanceTester::MODAL);
        $I->dontSeeElement(AcceptanceTester::MODAL);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 2: Validaciones de campos requeridos antes de permitir registrar un cargo.
     */
    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        // 1. Abrir modal de nuevo cargo
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Nuevo Cargo', AcceptanceTester::MODAL_TITULO);

        // 2. Intentar registrar sin atletas seleccionados
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Debe Seleccionar al menos un Atleta', 5);
        $I->aceptarSweetAlert();

        // 3. Seleccionar un atleta pero dejar concepto vacío
        $I->waitForJS("return $('#id_atleta option').length > 0;", 5);
        $I->executeJS("
            let atletaVal = $('#id_atleta option:first').val();
            if (atletaVal) {
                $('#id_atleta').val([atletaVal]).trigger('change');
            }
        ");
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('Debe Seleccionar un Concepto', 5);
        $I->aceptarSweetAlert();

        // 4. Cerrar el modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);
        $I->verificarSinErrores();
    }

    /**
     * Prueba 3: Registro exitoso de un cargo individual con confirmación de SweetAlert2.
     */
    #[Group('registro', 'crud')]
    public function probarRegistrarCargo(AcceptanceTester $I): void
    {
        // 1. Abrir modal
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Nuevo Cargo', AcceptanceTester::MODAL_TITULO);

        // Esperar que las peticiones AJAX de atletas y conceptos pueblen los selectores
        $I->waitForJS("return $('#id_atleta option').length > 0 && $('#id_concepto option:not([value=\"\"])').length > 0;", 5);

        // 2. Seleccionar atleta y concepto dinámicamente y fijar fecha en periodo futuro
        $datosCargo = $I->executeJS("
            let optAtleta = $('#id_atleta option:first');
            let atletaId = optAtleta.val();
            let atletaTexto = optAtleta.text().trim();
            $('#id_atleta').val([atletaId]).trigger('change');

            let optConcepto = $('#id_concepto option:not([value=\"\"])').first();
            let conceptoId = optConcepto.val();
            let conceptoTexto = optConcepto.text().trim();
            $('#id_concepto').val(conceptoId).trigger('change');

            // Establecer fecha de emisión futura (3 meses) para evitar colisión de frecuencia
            let d = new Date();
            d.setMonth(d.getMonth() + 3);
            let y = d.getFullYear();
            let m = String(d.getMonth() + 1).padStart(2, '0');
            let fechaStr = y + '-' + m + '-01';

            let fInput = document.getElementById('fecha_emision');
            if (fInput && fInput._flatpickr) {
                fInput._flatpickr.setDate(fechaStr, true);
            }
            $('#fecha_emision').val(fechaStr).trigger('change');

            return {
                atletaId: atletaId,
                atletaNombre: atletaTexto.split('-')[0].trim(),
                conceptoId: conceptoId,
                conceptoNombre: conceptoTexto.split('-')[0].trim(),
                fechaEmision: fechaStr
            };
        ");

        $I->wait(1);

        // 3. Procesar registro
        $I->click(AcceptanceTester::BTN_PROCESO);

        // 4. Confirmar en SweetAlert
        $I->waitForText('¿Esta seguro que quiere generar esto(s) cargos(s)?', 5);
        $I->confirmarSweetAlert();

        // 5. Verificar mensaje de éxito
        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 6. Verificar que el atleta aparezca en los resultados
        $primerNombre = !empty($datosCargo['atletaNombre']) ? explode(' ', (string)$datosCargo['atletaNombre'])[0] : '';
        if ($primerNombre !== '') {
            $I->buscarEnListado($primerNombre);
            $I->waitForElementVisible(AcceptanceTester::ITEM_LISTADO, 5);
            $I->see($primerNombre, AcceptanceTester::CONT_RESULTADOS);
            $I->limpiarBuscador();
        } else {
            $I->waitForElementVisible(AcceptanceTester::ITEM_LISTADO, 5);
        }

        $I->verificarSinErrores();
    }

    /**
     * Prueba 4: Buscador de cargos en tiempo real con debounce y validación de término inexistente.
     */
    #[Group('buscador')]
    public function probarBuscadorCargos(AcceptanceTester $I): void
    {
        // 1. Búsqueda con término inexistente
        $I->buscarEnListado('NONEXISTENT_CARGO_XYZ_999');
        $I->waitForElementVisible(AcceptanceTester::LISTADO_VACIO, 5);
        $I->see('No se encontraron registros', AcceptanceTester::LISTADO_VACIO);

        // 2. Limpiar buscador y verificar recuperación del listado
        $I->limpiarBuscador();
        $I->waitForElementVisible(AcceptanceTester::ITEM_LISTADO, 5);
        $I->dontSee('No se encontraron registros');

        $I->verificarSinErrores();
    }

    /**
     * Prueba 5: Despliegue interactivo (acordeón) de las cuentas y facturas de un atleta.
     */
    #[Group('detalle')]
    public function probarVerDetalleCargosAtleta(AcceptanceTester $I): void
    {
        $I->waitForElementVisible(AcceptanceTester::ITEM_LISTADO, 5);

        // 1. Clic para expandir el primer registro de atleta
        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);

        // 2. Verificar que se muestre el contenedor de detalles y resumen general de deuda
        $I->waitForElementVisible(self::TARJETA_RESUMEN, 5);
        $I->see('Estado de Cuenta General', self::TARJETA_RESUMEN);
        $I->see('Deuda Total:', self::TARJETA_RESUMEN);

        // 3. Verificar fila de cargo y sus fechas y montos
        $I->waitForElementVisible(self::FILA_SUB_ITEM, 5);
        $I->seeElement('.sub_item_fechas');
        $I->seeElement('.sub_item_montos');

        // 4. Clic nuevamente para contraer
        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 6: Modificación del monto de una cuenta por cobrar.
     */
    #[Group('modificar', 'crud')]
    public function probarModificarCargo(AcceptanceTester $I): void
    {
        $I->waitForElementVisible(AcceptanceTester::ITEM_LISTADO, 5);

        // 1. Expandir el primer atleta para ver sus cargos
        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);

        // 2. Verificar presencia del botón modificar y abrir modal de edición
        $I->waitForElementVisible(self::BTN_MODIFICAR_CARGO, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_MODIFICAR_CARGO . "').click();");

        // 3. Verificar modal de modificación y campos bloqueados
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Modificar Cargo', AcceptanceTester::MODAL_TITULO);

        // 4. Modificar el monto total
        $I->executeJS("$('#monto_total').val('55.00').trigger('input');");

        // 5. Procesar modificación
        $I->click(AcceptanceTester::BTN_PROCESO);

        // 6. Confirmar en SweetAlert
        $I->waitForText('¿Esta seguro que quiere modificar este cargo?', 5);
        $I->confirmarSweetAlert();

        // 7. Verificar mensaje de éxito
        $I->waitForText('Modificación Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 7: Diálogo interactivo para generación de reportes en PDF o Excel.
     */
    #[Group('reporte')]
    public function probarGenerarReporteCargos(AcceptanceTester $I): void
    {
        // 1. Clic en botón Generar Reporte
        $I->click(self::BTN_GENERAR);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Reporte de Cargos', AcceptanceTester::MODAL_TITULO);

        // 2. Verificar que los campos de cargo estén ocultos y se muestre rango de fechas
        $I->waitForElementVisible(self::CONT_RANGO_FECHAS, 5);

        // 3. Clic en Generar Reporte para abrir el diálogo SweetAlert de formatos
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿En qué formato desea generar el reporte?', 5);
        $I->see('PDF');
        $I->see('Excel');

        // 4. Cancelar diálogo para evitar descargas en la prueba
        $I->cancelarSweetAlert();

        // 5. Cerrar modal de reporte limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 8: Anulación de un cargo por cobrar con bloqueo definitivo.
     */
    #[Group('anular', 'crud')]
    public function probarAnularCargo(AcceptanceTester $I): void
    {
        $I->waitForElementVisible(AcceptanceTester::ITEM_LISTADO, 5);

        // 1. Expandir el atleta
        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);

        // 2. Clic en botón anular
        $I->waitForElementVisible(self::BTN_ANULAR_CARGO, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_ANULAR_CARGO . "').click();");

        // 3. Confirmar bloqueo en SweetAlert
        $I->waitForText('¿Desea bloquear este cargo?', 5);
        $I->confirmarSweetAlert();

        // 4. Verificar mensaje de éxito
        $I->waitForText('Anulación Exitosa', 10);
        $I->aceptarSweetAlert();

        // 5. Expandir nuevamente y verificar que el cargo aparezca como Anulado
        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);
        $I->waitForElementVisible(self::FILA_SUB_ITEM, 5);
        $I->see('Anulado', self::FILA_SUB_ITEM);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 9: Ciclo de vida completo de un cargo (Registro -> Detalle -> Búsqueda -> Modificar -> Reporte -> Anular).
     */
    #[Group('ciclo_completo')]
    public function probarCicloCompletoCargo(AcceptanceTester $I): void
    {
        // 1. Registrar Cargo
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);

        $I->waitForJS("return $('#id_atleta option').length > 0 && $('#id_concepto option:not([value=\"\"])').length > 0;", 5);

        $datosCiclo = $I->executeJS("
            let optAtleta = $('#id_atleta option:first');
            let atletaId = optAtleta.val();
            let atletaTexto = optAtleta.text().trim();
            $('#id_atleta').val([atletaId]).trigger('change');

            let optConcepto = $('#id_concepto option:not([value=\"\"])').first();
            let conceptoId = optConcepto.val();
            let conceptoTexto = optConcepto.text().trim();
            $('#id_concepto').val(conceptoId).trigger('change');

            let d = new Date();
            d.setMonth(d.getMonth() + 4); // 4 meses a futuro
            let y = d.getFullYear();
            let m = String(d.getMonth() + 1).padStart(2, '0');
            let fechaStr = y + '-' + m + '-01';

            let fInput = document.getElementById('fecha_emision');
            if (fInput && fInput._flatpickr) {
                fInput._flatpickr.setDate(fechaStr, true);
            }
            $('#fecha_emision').val(fechaStr).trigger('change');

            return {
                atletaNombre: atletaTexto.split('-')[0].trim()
            };
        ");

        $I->wait(1);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Esta seguro que quiere generar esto(s) cargos(s)?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 2. Búsqueda y Verificación
        $nombreCiclo = !empty($datosCiclo['atletaNombre']) ? explode(' ', (string)$datosCiclo['atletaNombre'])[0] : '';
        if ($nombreCiclo !== '') {
            $I->buscarEnListado($nombreCiclo);
            $I->waitForElementVisible(AcceptanceTester::ITEM_LISTADO, 5);
            $I->see($nombreCiclo, AcceptanceTester::CONT_RESULTADOS);
        } else {
            $I->waitForElementVisible(AcceptanceTester::ITEM_LISTADO, 5);
        }

        // 3. Ver Detalle
        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);
        $I->waitForElementVisible(self::TARJETA_RESUMEN, 5);

        // 4. Modificar
        $I->waitForElementVisible(self::BTN_MODIFICAR_CARGO, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_MODIFICAR_CARGO . "').click();");
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->executeJS("$('#monto_total').val('65.00').trigger('input');");
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Esta seguro que quiere modificar este cargo?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Modificación Exitosa', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 5. Anular
        $I->buscarEnListado($nombreCiclo);
        $I->waitForElementVisible(AcceptanceTester::ITEM_LISTADO, 5);
        $I->executeJS("document.querySelector('" . AcceptanceTester::ITEM_LISTADO . "').click();");
        $I->wait(1);
        $I->waitForElementVisible(self::BTN_ANULAR_CARGO, 5);
        $I->despejarOverlays();
        $I->executeJS("document.querySelector('" . self::BTN_ANULAR_CARGO . "').click();");
        $I->waitForText('¿Desea bloquear este cargo?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Anulación Exitosa', 10);
        $I->aceptarSweetAlert();

        // 6. Restaurar buscador
        $I->limpiarBuscador();
        $I->verificarSinErrores();
    }
}
