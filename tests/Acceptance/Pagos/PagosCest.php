<?php

declare(strict_types=1);

namespace Tests\Acceptance\Pagos;

use Codeception\Attribute\Group;
use Tests\Support\AcceptanceTester;

if (!defined('_DB_NAME_')) {
    require_once __DIR__ . '/../../../config/config.php';
}

#[Group('pagos')]
#[Group('interfaz')]
final class PagosCest
{
    private const RUTA_MODULO                   = '/Pagos';
    private const TITULO_MODULO                 = 'Pagos';

    // Selectores del modal principal de pagos / reportes
    private const SELECT_CUENTA                 = '#cuenta';
    private const SELECT_METODO                 = '#metodo';
    private const SELECT_MONEDA                 = '#moneda';
    private const INPUT_TOTAL_PAGAR             = '#total_pagar';
    private const INPUT_MONTO                   = '#monto';
    private const INPUT_MONTO_CAMBIO             = '#monto_cambio';
    private const INPUT_FECHA                   = '#fecha';
    private const INPUT_FECHA_F                 = '#fecha_f';
    private const SELECT_TASA                   = '#tasa';
    private const INPUT_REFERENCIA              = '#referencia';
    private const CHECKBOX_ANULADOS             = '#anulados';

    // Selectores del modal secundario (Registro de Vuelto)
    private const MODAL_SECUNDARIO_CONTENEDOR   = '#secundario_modal_contenedor';
    private const MODAL_SECUNDARIO              = '#secundario_modal';
    private const MODAL_SECUNDARIO_CERRAR       = '#cerrar_modal_Secundario';
    private const INPUT_VUELTO_BASE             = '#monto_vuelto_base';
    private const SELECT_MONEDA_VUELTO          = '#codigo_moneda_vuelto';
    private const SELECT_METODO_VUELTO          = '#codigo_metodo_vuelto';
    private const SELECT_TASA_VUELTO            = '#tasa_vuelto';
    private const INPUT_MONTO_VUELTO            = '#monto_vuelto';
    private const INPUT_REFERENCIA_VUELTO       = '#referencia_vuelto';
    private const INPUT_FECHA_VUELTO            = '#fecha_vuelto';
    private const BTN_PROCESO_VUELTO            = '#proceso_vuelto';

    // Selectores en las filas y detalles de pagos
    private const FILA_PAGO                     = '.listado_item';
    private const DETALLE_OCULTO                = '.listado_detalle_oculto';
    private const BTN_ANULAR_PAGO               = '.listado_col_acciones .cbt_r';
    private const BTN_VUELTO_PAGO               = '.listado_col_acciones .cbt_v';
    private const BADGE_ANULADO                 = '.estatus_r';

    public function _before(AcceptanceTester $I): void
    {
        // Login con snapshot + navegación a /Pagos + comprobación de título y limpieza de overlays
        $I->irAlModulo(self::RUTA_MODULO, self::TITULO_MODULO);
    }

    /**
     * Prueba 1: Verifica la carga inicial de la interfaz del módulo de Pagos.
     */
    #[Group('carga', 'interfaz')]
    public function probarCargaInicialDeInterfaz(AcceptanceTester $I): void
    {
        $I->see(self::TITULO_MODULO, AcceptanceTester::TITULO_PAGINA);
        $I->seeElement(AcceptanceTester::INPUT_BUSQUEDA);
        $I->seeElement(AcceptanceTester::BTN_NUEVO);
        $I->seeElement(AcceptanceTester::BTN_REPORTE);
        $I->seeElement(AcceptanceTester::CONT_RESULTADOS);

        // Modales en el DOM pero ocultos al inicio
        $I->seeElementInDOM(AcceptanceTester::MODAL);
        $I->dontSeeElement(AcceptanceTester::MODAL);

        $I->seeElementInDOM(self::MODAL_SECUNDARIO);
        $I->dontSeeElement(self::MODAL_SECUNDARIO);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 2: Validaciones de campos requeridos antes de registrar un pago.
     */
    #[Group('validacion')]
    public function probarValidacionesDeFormulario(AcceptanceTester $I): void
    {
        // 1. Abrir modal de nuevo pago
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Pago', AcceptanceTester::MODAL_TITULO);
        $I->despejarOverlays();
        $I->wait(1);

        // 2. Intentar registrar sin seleccionar cuentas por cobrar
        $I->executeJS("document.querySelector('#proceso').click();");
        $I->waitForText('Debe seleccionar una cuenta por cobrar', 5);
        $I->aceptarSweetAlert();

        // 3. Seleccionar cuenta pero dejar método de pago vacío
        $I->waitForJS("return $('#cuenta option').length > 0;", 5);
        $I->executeJS("
            let ctaVal = $('#cuenta option:first').val();
            if (ctaVal) {
                $('#cuenta').val([ctaVal]).trigger('change');
            }
        ");
        $I->wait(1);
        $I->executeJS("document.querySelector('#proceso').click();");
        $I->waitForText('Debe seleccionar un método de pago', 5);
        $I->aceptarSweetAlert();

        // 4. Seleccionar método pero dejar moneda vacía
        $I->waitForJS("return $('#metodo option:not([value=\"\"])').length > 0;", 5);
        $I->executeJS("
            let metVal = $('#metodo option:not([value=\"\"])').first().val();
            if (metVal) {
                $('#metodo').val(metVal).trigger('change');
            }
            $('#moneda').val('').trigger('change');
        ");
        $I->wait(1);
        $I->executeJS("document.querySelector('#proceso').click();");
        $I->waitForText('Debe seleccionar una moneda', 5);
        $I->aceptarSweetAlert();

        // 5. Cerrar modal limpiamente
        $I->click(AcceptanceTester::MODAL_CERRAR);
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 3: Registro exitoso de un pago con amortización y confirmación SweetAlert2.
     */
    #[Group('registro', 'crud')]
    public function probarRegistrarPago(AcceptanceTester $I): void
    {
        $this->asegurarCargoPendiente();

        // 1. Abrir modal
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Registrar Pago', AcceptanceTester::MODAL_TITULO);

        // Esperar que MultiConsulta llene los selectores
        $I->waitForJS("return $('#cuenta option').length > 0 && $('#metodo option:not([value=\"\"])').length > 0 && $('#moneda option:not([value=\"\"])').length > 0;", 5);

        $referenciaTest = '88' . substr((string) time(), -6);

        // 2. Configurar cargo, moneda y método concordantes para pago 1:1
        $datosPago = $I->executeJS("
            let ctaOpt = $('#cuenta option:first');
            let ctaId = ctaOpt.val();
            $('#cuenta').val([ctaId]).trigger('change');

            // Buscar moneda idéntica al cargo para fijar tasa 1.00
            let ctaObj = listadoCuentas.find(c => c.codigo_cargo == ctaId);
            let monedaAbrev = ctaObj ? ctaObj.moneda_abreviatura : null;
            let idMoneda = null;

            if (monedaAbrev) {
                let monObj = listadoMonedas.find(m => m.abreviatura.toUpperCase() === monedaAbrev.toUpperCase());
                if (monObj) idMoneda = monObj.codigo_moneda;
            }
            if (!idMoneda) {
                idMoneda = $('#moneda option:not([value=\"\"])').first().val();
            }
            $('#moneda').val(idMoneda).trigger('change');

            // Seleccionar primer método disponible
            let metId = $('#metodo option:not([value=\"\"])').first().val();
            $('#metodo').val(metId).trigger('change');

            // Asignar monto a pagar
            $('#monto').val('10.00').trigger('input');

            // Asignar referencia si el método la requiere
            let refVal = " . json_encode($referenciaTest) . ";
            if (!$('#referencia').prop('disabled')) {
                $('#referencia').val(refVal).trigger('input');
            }

            return {
                cargoId: ctaId,
                referencia: refVal,
                atleta: ctaObj ? ctaObj.p_nombre : ''
            };
        ");

        $I->wait(1);

        // 3. Procesar registro
        $I->click(AcceptanceTester::BTN_PROCESO);

        // 4. Confirmar en SweetAlert
        $I->waitForText('¿Está seguro que quiere registrar este pago?', 5);
        $I->confirmarSweetAlert();

        // 5. Verificar mensaje de éxito
        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 6. Verificar presencia en el listado
        $I->waitForElementVisible(self::FILA_PAGO, 5);
        $I->verificarSinErrores();
    }

    /**
     * Prueba 4: Buscador de pagos en tiempo real con debounce y validación de término inexistente.
     */
    #[Group('buscador')]
    public function probarBuscadorPagos(AcceptanceTester $I): void
    {
        // 1. Búsqueda con término inexistente
        $I->buscarEnListado('NONEXISTENT_PAGO_XYZ_999');
        $I->waitForElementVisible(AcceptanceTester::LISTADO_VACIO, 5);
        $I->see('No se encontraron registros de pagos', AcceptanceTester::LISTADO_VACIO);

        // 2. Limpiar buscador y verificar recuperación del listado
        $I->limpiarBuscador();
        $I->waitForElementVisible(self::FILA_PAGO, 5);
        $I->dontSee('No se encontraron registros de pagos');

        $I->verificarSinErrores();
    }

    /**
     * Prueba 5: Despliegue interactivo del detalle del pago (acordeón con desglose financiero y de cargos).
     */
    #[Group('detalle')]
    public function probarVerDetallePago(AcceptanceTester $I): void
    {
        $I->waitForElementVisible(self::FILA_PAGO, 5);

        // 1. Clic para expandir el primer registro de pago
        $I->executeJS("document.querySelector('" . self::FILA_PAGO . "').click();");
        $I->wait(1);

        // 2. Verificar que se muestre el contenedor de detalles expandidos
        $I->waitForElementVisible(self::DETALLE_OCULTO, 5);
        $I->see('Detalles Financieros del Pago General', self::DETALLE_OCULTO);
        $I->see('Metodo de Pago', self::DETALLE_OCULTO);
        $I->see('Desglose de Cuentas Abonadas:', self::DETALLE_OCULTO);

        // 3. Clic nuevamente para contraer
        $I->executeJS("document.querySelector('" . self::FILA_PAGO . "').click();");
        $I->wait(1);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 6: Apertura e interacción con el modal secundario de Registro de Vuelto.
     */
    #[Group('vuelto', 'interfaz')]
    public function probarInterfazModalVuelto(AcceptanceTester $I): void
    {
        // 1. Abrir modal secundario de vuelto mediante la función JS del módulo
        $I->executeJS("abrirModalVuelto(1, '20.00', '$ USD');");

        // 2. Verificar que el modal secundario sea visible con sus componentes
        $I->waitForElementVisible(self::MODAL_SECUNDARIO, 5);
        $I->see('Registrar Vuelto', self::MODAL_SECUNDARIO_CONTENEDOR . ' .titulo_modal');
        $I->seeInField(self::INPUT_VUELTO_BASE, '20.00');

        $I->seeElement(self::SELECT_MONEDA_VUELTO);
        $I->seeElement(self::SELECT_METODO_VUELTO);
        $I->seeElement(self::INPUT_MONTO_VUELTO);
        $I->seeElement(self::BTN_PROCESO_VUELTO);

        // 3. Cerrar modal secundario limpiamente
        $I->click(self::MODAL_SECUNDARIO_CERRAR);
        $I->waitForElementNotVisible(self::MODAL_SECUNDARIO, 5);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 7: Diálogo interactivo para generación de reportes financieros en PDF o Excel.
     */
    #[Group('reporte')]
    public function probarGenerarReportePagos(AcceptanceTester $I): void
    {
        // 1. Clic en botón Generar Reporte
        $I->click(AcceptanceTester::BTN_REPORTE);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->see('Generar Reporte', AcceptanceTester::MODAL_TITULO);

        // 2. Verificar que los campos de reporte estén activos y los de pago ocultos
        $I->waitForElementVisible('.custom-checkbox', 5);
        $I->seeElementInDOM(self::CHECKBOX_ANULADOS);
        $I->see('Fecha Fin');
        $I->see('Incluir Pagos Anulados');
        $I->dontSeeElement('#monto');

        // 3. Clic en Generar Reporte para abrir el diálogo SweetAlert de formatos
        $I->wait(1);
        $I->executeJS("document.querySelector('#proceso').click();");
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
     * Prueba 8: Anulación de un pago con motivo justificado y actualización de estatus.
     */
    #[Group('anular', 'crud')]
    public function probarAnularPago(AcceptanceTester $I): void
    {
        // 1. Asegurar que haya un pago activo que anular
        $pagoActivo = $I->executeJS("
            let btnAnular = document.querySelector('" . self::BTN_ANULAR_PAGO . "');
            return btnAnular !== null;
        ");

        if (!$pagoActivo) {
            $this->probarRegistrarPago($I);
        }

        $I->waitForElementVisible(self::BTN_ANULAR_PAGO, 5);
        $I->despejarOverlays();

        // 2. Clic en anular pago
        $I->executeJS("document.querySelector('" . self::BTN_ANULAR_PAGO . "').click();");

        // 3. Confirmar SweetAlert prompt solicitando motivo
        $I->waitForText('¿Está seguro que quiere anular este pago?', 5);
        $I->responderSweetAlertPrompt('Error en datos bancarios del comprobante del cliente');

        // 4. Verificar mensaje de éxito
        $I->waitForText('Retiro Exitoso', 10);
        $I->aceptarSweetAlert();

        // 5. Verificar que el estatus ahora muestre 'Anulado'
        $I->waitForElementVisible(self::BADGE_ANULADO, 5);
        $I->see('Anulado', self::BADGE_ANULADO);

        $I->verificarSinErrores();
    }

    /**
     * Prueba 9: Ciclo completo del pago (Registro -> Búsqueda -> Expansión Detalle -> Anulación con Motivo).
     */
    #[Group('ciclo_completo')]
    public function probarCicloCompletoPago(AcceptanceTester $I): void
    {
        $this->asegurarCargoPendiente();

        $refCiclo = '99' . substr((string) (time() + 5), -6);

        // 1. REGISTRAR PAGO
        $I->click(AcceptanceTester::BTN_NUEVO);
        $I->waitForElementVisible(AcceptanceTester::MODAL, 5);
        $I->waitForJS("return $('#cuenta option').length > 0 && $('#metodo option:not([value=\"\"])').length > 0;", 5);

        $I->executeJS("
            let ctaOpt = $('#cuenta option:first');
            let ctaId = ctaOpt.val();
            $('#cuenta').val([ctaId]).trigger('change');

            let ctaObj = listadoCuentas.find(c => c.codigo_cargo == ctaId);
            let monedaAbrev = ctaObj ? ctaObj.moneda_abreviatura : null;
            let idMoneda = null;

            if (monedaAbrev) {
                let monObj = listadoMonedas.find(m => m.abreviatura.toUpperCase() === monedaAbrev.toUpperCase());
                if (monObj) idMoneda = monObj.codigo_moneda;
            }
            if (!idMoneda) {
                idMoneda = $('#moneda option:not([value=\"\"])').first().val();
            }
            $('#moneda').val(idMoneda).trigger('change');

            let metId = $('#metodo option:not([value=\"\"])').first().val();
            $('#metodo').val(metId).trigger('change');

            $('#monto').val('15.00').trigger('input');

            let ref = " . json_encode($refCiclo) . ";
            if (!$('#referencia').prop('disabled')) {
                $('#referencia').val(ref).trigger('input');
            }
        ");

        $I->wait(1);
        $I->click(AcceptanceTester::BTN_PROCESO);
        $I->waitForText('¿Está seguro que quiere registrar este pago?', 5);
        $I->confirmarSweetAlert();
        $I->waitForText('Registro Exitoso', 10);
        $I->aceptarSweetAlert();
        $I->waitForElementNotVisible(AcceptanceTester::MODAL, 5);

        // 2. BUSCADOR POR REFERENCIA
        $I->buscarEnListado($refCiclo);
        $I->wait(1);

        // 3. EXPANDIR DETALLE
        $I->waitForElementVisible(self::FILA_PAGO, 5);
        $I->executeJS("document.querySelector('" . self::FILA_PAGO . "').click();");
        $I->wait(1);
        $I->waitForElementVisible(self::DETALLE_OCULTO, 5);
        $I->see('Detalles Financieros del Pago General', self::DETALLE_OCULTO);

        // 4. ANULAR PAGO DEL CICLO
        $I->waitForElementVisible(self::BTN_ANULAR_PAGO, 5);
        $I->executeJS("document.querySelector('" . self::BTN_ANULAR_PAGO . "').click();");
        $I->waitForText('¿Está seguro que quiere anular este pago?', 5);
        $I->responderSweetAlertPrompt('Anulación de prueba para completar ciclo de vida');
        $I->waitForText('Retiro Exitoso', 10);
        $I->aceptarSweetAlert();

        // 5. VERIFICAR ESTATUS ANULADO Y LIMPIAR BUSCADOR
        $I->waitForElementVisible(self::BADGE_ANULADO, 5);
        $I->see('Anulado', self::BADGE_ANULADO);
        $I->limpiarBuscador();

        $I->verificarSinErrores();
    }

    /**
     * Helper privado: Asegura que exista al menos un cargo pendiente en la BD para vincular pagos.
     */
    private function asegurarCargoPendiente(): void
    {
        try {
            $pdo = new \PDO(
                'mysql:host=' . _DB_HOST_ . ';dbname=' . _DB_NAME_ . ';charset=' . _DB_CHARSET_,
                _DB_USER_,
                _DB_PASS_
            );
            $stmt = $pdo->query("SELECT COUNT(*) FROM cargos WHERE estatus = 1");
            $total = (int) $stmt->fetchColumn();

            if ($total === 0) {
                $idAtleta = $pdo->query("SELECT codigo_atleta FROM atletas WHERE estatus = 1 LIMIT 1")->fetchColumn();
                $idConcepto = $pdo->query("SELECT codigo_concepto FROM conceptos WHERE estatus = 1 LIMIT 1")->fetchColumn();
                $idMoneda = $pdo->query("SELECT codigo_moneda FROM monedas WHERE estatus = 1 LIMIT 1")->fetchColumn();

                if ($idAtleta && $idConcepto && $idMoneda) {
                    $insert = $pdo->prepare(
                        "INSERT INTO cargos (codigo_concepto, codigo_atleta, monto_total, fecha_emision, codigo_moneda, estatus)
                         VALUES (?, ?, '50.00', CURDATE(), ?, 1)"
                    );
                    $insert->execute([$idConcepto, $idAtleta, $idMoneda]);
                }
            }
        } catch (\Throwable $e) {
            // No interrumpir si hay alguna restricción de conexión directa
        }
    }
}

