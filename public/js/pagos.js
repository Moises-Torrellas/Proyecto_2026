let listadoCuentas = [];
let listadoMonedas = [];
let cuentaSeleccionadaActual = null;

function MultiConsulta() {
    let datos = new FormData();
    datos.append('accion', 'MultiConsulta');
    enviaAjax(datos);
}

$('#busqueda').off('keyup').on('keyup', busqueda);
let timerBusqueda;

function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}

function busqueda() {
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(function () {
        let valorBusqueda = $('#busqueda').val();
        let datos = new FormData();
        datos.append('accion', 'consultar');
        datos.append('filtro', valorBusqueda);
        enviaAjax(datos);
    }, 500);
}

$(document).ready(function () {
    MultiConsulta();
    inicializarPaginador();
    ModeloBancario("#monto");
    // ModeloBancario("#tasa");

    Validacion("monto", /^[0-9.\b]*$/, /^\d+(\.\d{1,2})?$/, "Monto inválido", "proceso");
    Validacion("tasa", /^[0-9.\b]*$/, /^\d+(\.\d{1,4})?$/, "Tasa inválida", "proceso");
    Validacion("referencia", /^[a-zA-Z0-9\-\_\b]*$/, /^[a-zA-Z0-9\-\_]+$/, "Referencia inválida", "proceso");
    Validacion("fecha", /^[0-9\b-]*$/, /^\d{4}-\d{2}-\d{2}$/, "Seleccione una fecha válida", "proceso");
    Validacion("fecha_f", /^[0-9\b-]*$/, /^\d{4}-\d{2}-\d{2}$/, "Seleccione una fecha válida", "proceso");

    $('#proceso').on('click', function () {
        let accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                // Verificar si hay exceso
                let monto = parseFloat($('#monto_cambio').val()) || 0;
                let idsCobrar = $('#cuenta').val() || [];
                let deudaTotalPendiente = 0;
                idsCobrar.forEach(id => {
                    let c = listadoCuentas.find(cuenta => cuenta.id_cobrar == id);
                    if (c) deudaTotalPendiente += parseFloat(c.monto_pendiente) || 0;
                });

                let saldoRestante = deudaTotalPendiente - monto;
                if (saldoRestante < 0) {
                    // Hay exceso, mostrar modal de vuelto
                    $('#monto_vuelto').val(Math.abs(saldoRestante).toFixed(2));
                    $('#fecha_vuelto').val($('#fecha').val()); // misma fecha por defecto

                    // Construir selects
                    construirSelect('codigo_moneda_vuelto', listadoMonedas, 'codigo_moneda', 'simbolo', 'nombre');
                    construirSelect('codigo_metodo_vuelto', listadoMetodosVuelto, 'codigo_metodo', 'nombre');

                    $('#codigo_moneda_vuelto').select2({
                        placeholder: "Selecciona una Moneda",
                        dropdownParent: $('#secundario_modal_contenedor') // Debe coincidir con el ID del modal
                    });

                    $('#codigo_metodo_vuelto').select2({
                        placeholder: "Selecciona un Método",
                        dropdownParent: $('#secundario_modal_contenedor') // Debe coincidir con el ID del modal
                    });

                    abrirModalSecundario();
                    return;
                }

                confirmar('¿Está seguro que quiere registrar este pago?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'incluir');
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "modificar") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere modificar este pago?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        var fotoActual = $("#proceso").data("foto_actual");
                        datos.append('foto_actual', fotoActual);
                        datos.append('accion', 'modificar');
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "generar") {
            confirmar('¿Está seguro que quiere generar un reporte?', function (confirmado) {
                if (confirmado) {
                    abrirAlertaEspara('Se esta generando el reporte', 'Espere un momento');
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    enviaAjax(datos);
                }
            });
        }
    });

    $('#cuenta').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('#contenedor_modal'),
    });
    $('#metodo').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('#contenedor_modal'),
    });
    $('#moneda').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('#contenedor_modal'),
    });

    $("#incluir").on("click", function () {
        limpia();

        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Pago");
        $("#titulo_modal").text("Registrar Pago");
        $('#cuenta').closest('.colum').show();
        $('#monto').closest('.colum').show();
        $('#tasa').closest('.colum').show();
        $('#referencia').closest('.colum').show();
        $('#fecha_f').closest('.colum').hide();
        $('#anulados').closest('.colum').hide();
        $('#monto_cambio').closest('.colum').show();
        $('#cuenta').val(null).trigger('change');
        $('#metodo').val(null).trigger('change');
        $('#moneda').val(null).trigger('change');
        $('#monto').val('');
        $('#tasa').val('');
        $('#referencia').val('');

        $('#referencia').prop('disabled', false).removeClass("campo_deshabilitado");

        // Limpieza de mensajes dinámicos de ayuda
        $('#detalles_deuda_ayuda').html('');
        $('#monto_equivalente_ayuda').html('');
        $('#label_tasa').text("Tasa de cambio");

        let fechaLocal = new Date(); // <-- Agrega esta línea
        let año = fechaLocal.getFullYear();
        let mes = String(fechaLocal.getMonth() + 1).padStart(2, '0');
        let dia = String(fechaLocal.getDate()).padStart(2, '0');
        let hoy = `${año}-${mes}-${dia}`;
        $('#fecha').val(hoy);

        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();

        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");

        $('#cuenta').closest('.colum').hide();
        $('#monto').closest('.colum').hide();
        $('#tasa').closest('.colum').hide();
        $('#referencia').closest('.colum').hide();
        $('#fecha_f').closest('.colum').show();
        $('#anulados').closest('.colum').show();
        $('#monto_cambio').closest('.colum').hide();
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Búsqueda', description: 'Aquí puedes filtrar los pagos registrados rápidamente.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Registrar Pago', description: 'Presiona aquí para abrir el formulario de registro de un nuevo pago.', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Permite generar listados detallados de ingresos en formato PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Historial de Pagos', description: 'Aquí se muestra la tabla con todos los movimientos financieros.', position: 'top' }
            },
            {
                element: '#registro',
                popover: { title: 'Detalle de Fila', description: 'Haz clic en cualquier registro para expandir los detalles específicos del pago.', position: 'bottom' }
            },
            {
                element: '#cbt_v',
                popover: { title: 'Modificar Datos', description: 'Permite editar la información de un cobro seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_r',
                popover: { title: 'Anular Transacción', description: 'Si el pago fue erróneo, puedes anularlo especificando un motivo.', position: 'left' }
            },
            {
                element: '#rowsPerPage',
                popover: { title: 'Registros por Página', description: 'Ajusta cuántos elementos deseas visualizar a la vez.', position: 'top' }
            },
            {
                element: '#botonera',
                popover: { title: 'Paginación', description: 'Navega entre las diferentes páginas del historial.', position: 'top' }
            }
        ];

        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });

    $('#metodo').on('change', function () {
        let necesitaReferencia = undefined;

        if ($(this).data('select2')) {
            let dataSelect2 = $(this).select2('data');
            if (dataSelect2 && dataSelect2.length > 0 && dataSelect2[0].element) {
                necesitaReferencia = $(dataSelect2[0].element).attr('data-nec_ref');
            }
        }

        if (necesitaReferencia === undefined) {
            necesitaReferencia = $(this).find('option:selected').attr('data-nec_ref');
        }

        if (necesitaReferencia !== undefined && String(necesitaReferencia).trim() === "1") {
            $('#referencia').prop('disabled', false).removeClass("campo_deshabilitado");
        } else {
            $('#referencia').prop('disabled', true).addClass("campo_deshabilitated");
            $('#referencia').val('');
        }
    });

    // Evento dinámico al seleccionar una Cuenta por Cobrar
    $('#cuenta').on('change', function () {
        let idsCobrar = $(this).val();
        if (!idsCobrar || idsCobrar.length === 0) {
            cuentaSeleccionadaActual = null;
            $('#detalles_deuda_ayuda').html('');
            $('#monto_equivalente_ayuda').html('');
            return;
        }

        // Validación de protección multimoneda en selección múltiple
        let primeraCuenta = listadoCuentas.find(c => c.id_cobrar == idsCobrar[0]);
        let monedaIncompatible = false;

        idsCobrar.forEach(id => {
            let cuenta = listadoCuentas.find(c => c.id_cobrar == id);
            if (cuenta && cuenta.moneda_abreviatura !== primeraCuenta.moneda_abreviatura) {
                monedaIncompatible = true;
            }
        });

        if (monedaIncompatible) {
            muestraMensaje("error", 3500, "Monedas Diferentes", "No puedes agrupar deudas con distintas monedas base en un mismo pago.");
            // Revertimos la última selección inválida
            idsCobrar.pop();
            $(this).val(idsCobrar).trigger('change.select2');
            return;
        }

        cuentaSeleccionadaActual = primeraCuenta;

        let htmlDeudas = '<div class="alerta-info-deuda" style="background-color: #f0f7ff; border-left: 4px solid #007bff; padding: 10px; margin-top: 5px; border-radius: 4px;"><p style="margin: 0; font-size: 13px; color: #333;">⚠️ <strong>Cuentas seleccionadas:</strong></p><ul style="margin: 5px 0 0 20px;">';

        idsCobrar.forEach(id => {
            let cuenta = listadoCuentas.find(c => c.id_cobrar == id);
            if (cuenta) {
                let pendiente = parseFloat(cuenta.monto_pendiente).toFixed(2);
                let simboloMoneda = cuenta.moneda_simbolo;
                htmlDeudas += `<li style="font-size: 12px; color: #555;">${cuenta.concepto_nombre} - ${cuenta.atleta_nombre}: <span style="color: #dc3545; font-weight: bold;">${pendiente} ${simboloMoneda}</span></li>`;
            }
        });
        htmlDeudas += '</ul></div>';

        $('#detalles_deuda_ayuda').html(htmlDeudas);
        solicitarTasaAPI();
    });

    // Recalcular al cambiar la moneda de pago o fecha
    $('#moneda, #fecha').on('change', function () {
        solicitarTasaAPI();
    });

    // Listener en tiempo real para amortización y monto al cambio
    $('#monto, #tasa').on('input change', function () {
        recalcularAmortizacion();
    });

    $('#cerrar_modal_Secundario').on('click', function () {
        $('#secundario_modal_contenedor').removeClass('mostrar');
        $('#secundario_modal').addClass('ocultar');
        $('#f_vuelto')[0].reset();
    });

    $('#proceso_vuelto').on('click', function () {
        if ($('#codigo_moneda_vuelto').val() == "" || $('#codigo_moneda_vuelto').val() == null) {
            muestraMensaje("error", 2000, "Error", "Debe seleccionar una moneda");
            return false;
        }
        if ($('#codigo_metodo_vuelto').val() == "" || $('#codigo_metodo_vuelto').val() == null) {
            muestraMensaje("error", 2000, "Error", "Debe seleccionar un método de pago");
            return false;
        }

        confirmar('¿Está seguro que quiere registrar el pago y el vuelto?', function (confirmado) {
            if (confirmado) {
                // Combinar datos del pago y del vuelto
                var datos = new FormData($('#f')[0]);
                var datosVuelto = new FormData($('#f_vuelto')[0]);

                for (var pair of datosVuelto.entries()) {
                    datos.append(pair[0], pair[1]);
                }

                datos.append('accion', 'registrar_vuelto');
                enviaAjax(datos);

                $('#secundario_modal_contenedor').removeClass('mostrar');
                $('#secundario_modal').addClass('ocultar');
                $('#f_vuelto')[0].reset();
            }
        });
    });
});

function solicitarTasaAPI() {
    if (!cuentaSeleccionadaActual) return;

    let idMonedaPago = $('#moneda').val();
    let fecha = $('#fecha').val();

    if (!idMonedaPago || !fecha) {
        $('#label_tasa').text("Tasa de cambio");
        return;
    }

    let monedaPagoObj = listadoMonedas.find(m => m.id_moneda == idMonedaPago);
    if (!monedaPagoObj) return;

    let isoCuenta = cuentaSeleccionadaActual.moneda_abreviatura.toUpperCase();
    let isoPago = monedaPagoObj.abreviatura.toUpperCase();

    if (isoCuenta === isoPago) {
        let selectTasa = $('#tasa');
        selectTasa.empty();
        selectTasa.append('<option value="1.0000" selected>Misma Moneda (1.0000)</option>');
        selectTasa.prop('disabled', true);
        $('#label_tasa').html(`Tasa de cambio <span style="color: #28a745; font-size:12px;">(Misma moneda: 1 ${isoPago} = 1 ${isoCuenta})</span>`);
        recalcularAmortizacion();
        return;
    }

    $('#label_tasa').html(`Tasa de cambio <span style="color: #007bff; font-size:11px;">(Convirtiendo de ${isoPago} a ${isoCuenta})</span>`);

    let datos = new FormData();
    datos.append('accion', 'consultar_tasas_disponibles');
    datos.append('codigo_moneda', idMonedaPago);
    datos.append('fecha', fecha);

    $.ajax({
        url: "",
        type: "POST",
        contentType: false,
        processData: false,
        data: datos,
        beforeSend: function (request) {
            request.setRequestHeader("X-CSRF-TOKEN", token);
        },
        success: function (respuesta) {
            try {
                let lee = JSON.parse(respuesta);
                let selectTasa = $('#tasa');
                selectTasa.empty();

                if (lee.accion === 'exito' && lee.datos && lee.datos.length > 0) {
                    selectTasa.append('<option value="" disabled selected>Seleccione una tasa</option>');
                    lee.datos.forEach(t => {
                        let tipoLabel = (t.tipo === 'automatica') ? 'Automática' : 'Manual';
                        selectTasa.append(`<option value="${parseFloat(t.valor_tasa).toFixed(4)}">${tipoLabel} - ${parseFloat(t.valor_tasa).toFixed(4)} ${t.simbolo}</option>`);
                    });
                    selectTasa.prop('disabled', false);
                    $('#label_tasa').html(`Seleccione Tasa <strong style="color: #007bff;">(${isoPago} ➔ ${isoCuenta})</strong>`);
                } else {
                    selectTasa.append('<option value="" disabled selected>No hay tasas disponibles</option>');
                    selectTasa.prop('disabled', true);
                    muestraMensaje("error", 3000, "Aviso de Tasa", "No se encontraron tasas disponibles para esta fecha.");
                }
                recalcularAmortizacion();
            } catch (e) {
                console.error("Error procesando tasa", e);
            }
        }
    });
}

function recalcularAmortizacion() {
    let monto = parseFloat($('#monto').val()) || 0;
    let tasa = parseFloat($('#tasa').val()) || 0;

    if (monto > 0 && tasa > 0 && cuentaSeleccionadaActual) {
        // Cálculo del monto convertido (Ej: 18000 Bs / 40 Tasa = 450 USD)
        let montoAmortizado = monto / tasa;

        // 1. Llenamos AUTOMÁTICAMENTE tu input real del formulario con 2 decimales
        $('#monto_cambio').val(montoAmortizado.toFixed(2));

        // 2. Mantenemos el bloque visual de ayuda por si quieres mostrarle el desglose al usuario
        let simDeuda = cuentaSeleccionadaActual.moneda_simbolo;
        let isoDeuda = cuentaSeleccionadaActual.moneda_abreviatura.toUpperCase();

        let idMonedaPago = $('#moneda').val();
        let monedaPagoObj = listadoMonedas.find(m => m.id_moneda == idMonedaPago);
        let isoPago = monedaPagoObj ? monedaPagoObj.abreviatura.toUpperCase() : '';

        let idsCobrar = $('#cuenta').val() || [];
        let deudaTotalPendiente = 0;
        idsCobrar.forEach(id => {
            let c = listadoCuentas.find(cuenta => cuenta.id_cobrar == id);
            if (c) deudaTotalPendiente += parseFloat(c.monto_pendiente) || 0;
        });

        let saldoRestante = deudaTotalPendiente - montoAmortizado;
        let desgloseSaldoHtml = "";

        if (saldoRestante > 0) {
            desgloseSaldoHtml = `<br/>📉 El saldo pendiente total pasará de <strong>${deudaTotalPendiente.toFixed(2)} ${simDeuda}</strong> a <strong style="color: #dc3545;">${saldoRestante.toFixed(2)} ${simDeuda}</strong>.`;
        } else if (saldoRestante === 0) {
            desgloseSaldoHtml = `<br/>🎉 ¡La deuda seleccionada quedará <strong>totalmente saldada</strong>!`;
        } else {
            desgloseSaldoHtml = `<br/>🪙 Quedará un saldo a favor de <strong style="color: #28a745;">${Math.abs(saldoRestante).toFixed(2)} ${simDeuda}</strong>.`;
        }

        $('#monto_equivalente_ayuda').html(`
            <div style="background-color: #e2f0d9; border-left: 4px solid #385723; padding: 10px; border-radius: 4px; margin-top: 8px; font-size: 13px; color: #222;">
                🟢 Los <strong>${monto.toFixed(2)} ${isoPago}</strong> ingresados equivalen al cambio a: 
                <strong style="font-size: 14px; color: #385723;">${montoAmortizado.toFixed(2)} ${simDeuda} (${isoDeuda})</strong>.${desgloseSaldoHtml}
            </div>
        `);
    } else {
        // Si los campos están vacíos, reseteamos tanto el input como el mensaje de ayuda
        $('#monto_cambio').val('');
        $('#monto_equivalente_ayuda').html('');
    }
}

function construirSelect(idSelect, datos, campoId, campo1, campo2 = null, campo3 = null, campo4 = null, campo5 = null, campo6 = null) {
    var select = $('#' + idSelect);
    select.empty();

    if (!select.prop('multiple')) {
        select.append('<option value="" selected disabled>Seleccione una opción</option>');
    }

    datos.forEach(dato => {
        let textoMostrar = "";
        let atributosExtra = "";

        if (idSelect === 'moneda' && campo1 && campo2) {
            textoMostrar = `${dato[campo1]} ${dato[campo2]}`;
        }
        else if (idSelect === 'metodo' && campo1 && campo2) {
            textoMostrar = `${dato[campo1]}`;
            atributosExtra = `data-nec_ref="${dato[campo2]}"`;
        }

        else if (idSelect === 'cuenta' && campo1 && campo2 && campo3) {
            textoMostrar = `${dato[campo6]} - ${dato[campo1]} ${dato[campo4]}${dato[campo5]} - ${dato[campo2]} ${dato[campo3]}`;
        }

        else {
            textoMostrar = escapeHTML(String(dato[campo1]));
        }

        var linea = `<option value="${dato[campoId]}" ${atributosExtra}>${textoMostrar}</option>`;
        select.append(linea);
    });
}

function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    contenedor.html(htmlRecibido);

    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

function eliminar(id) {
    confirmarAnulacion('¿Está seguro que quiere anular este pago?', function (motivo) {
        if (motivo !== false) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            datos.append('motivo_anulacion', motivo);
            enviaAjax(datos);
        }
    });
}

function escapeHTML(texto) {
    var caracteres = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(texto).replace(/[&<>"']/g, m => caracteres[m]);
}

function validarEnvio(accion) {
    if (accion == "incluir" || accion == "modificar") {
        if ($('#cuenta').val() == "" || $('#cuenta').val() == null || $('#cuenta').val().length == 0) {
            muestraMensaje("error", 2000, "Error", "Debe seleccionar una cuenta por cobrar");
            return false;
        }
        if ($('#metodo').val() == "" || $('#metodo').val() == null) {
            muestraMensaje("error", 2000, "Error", "Debe seleccionar un método de pago");
            return false;
        }
        if ($('#moneda').val() == "" || $('#moneda').val() == null) {
            muestraMensaje("error", 2000, "Error", "Debe seleccionar una moneda");
            return false;
        }
        if ($('#monto').val() == "") {
            muestraMensaje("error", 2000, "Error", "Debe ingresar un monto");
            return false;
        }
        if ($('#tasa').val() == "") {
            muestraMensaje("error", 2000, "Error", "Debe ingresar una tasa de cambio");
            return false;
        }
        if ($('#fecha').val() == "") {
            muestraMensaje("error", 2000, "Error", "Debe seleccionar una fecha");
            return false;
        } else {
            let fechaIngresada = $('#fecha').val();
            let hoy = new Date();
            let mes = (hoy.getMonth() + 1).toString().padStart(2, '0');
            let dia = hoy.getDate().toString().padStart(2, '0');
            let fechaActualStr = hoy.getFullYear() + '-' + mes + '-' + dia;
            if (fechaIngresada > fechaActualStr) {
                muestraMensaje("error", 2000, "Error", "La fecha del pago no puede ser futura");
                return false;
            }
        }
        if (!$('#referencia').prop('disabled') && $('#referencia').val() == "") {
            muestraMensaje("error", 2000, "Error", "Debe ingresar la referencia del pago");
            return false;
        }
    }
    return true;
}

var token = $('meta[name="csrf-token"]').attr('content');
function enviaAjax(datos) {
    $.ajax({
        async: true,
        url: "",
        type: "POST",
        contentType: false,
        data: datos,
        processData: false,
        cache: false,
        beforeSend: function (request) {
            request.setRequestHeader("X-CSRF-TOKEN", token);
        },
        timeout: 120000,
        success: function (respuesta) {
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta);
                return;
            }
            try {
                var lee = JSON.parse(respuesta);
                if (lee.accion == "MultiConsulta") {
                    listadoMonedas = lee.monedas;
                    listadoCuentas = lee.cuentas;
                    listadoMetodosVuelto = lee.metodos;

                    construirSelect('moneda', lee.monedas, 'codigo_moneda', 'simbolo', 'nombre');
                    construirSelect('metodo', lee.metodos, 'codigo_metodo', 'nombre', 'nec_referencia');
                    construirSelect('cuenta', lee.cuentas, 'codigo_cargo', 'concepto', 'p_nombre', 'p_apellidos', 'monto_total', 'simbolo_moneda', 'fecha_emision');
                } else if (lee.accion == "incluir") {
                    consultar();
                    limpia();
                    $('#monto_equivalente_ayuda').html('');
                    $('#detalles_deuda_ayuda').html('');
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                    cerrarModal();
                    MultiConsulta();

                    if (lee.vuelto && parseFloat(lee.vuelto) > 0) {
                        $('#codigo_pago_vuelto').val(lee.id_pago);
                        $('#monto_vuelto').val(parseFloat(lee.vuelto).toFixed(2));

                        let hoyObj = new Date();
                        let mesObj = (hoyObj.getMonth() + 1).toString().padStart(2, '0');
                        let diaObj = hoyObj.getDate().toString().padStart(2, '0');
                        let fechaLocalVuelto = hoyObj.getFullYear() + '-' + mesObj + '-' + diaObj;
                        $('#fecha_vuelto').val(fechaLocalVuelto);

                        // Dentro del bloque: if (lee.vuelto && parseFloat(lee.vuelto) > 0) { ...

                        construirSelect('codigo_moneda_vuelto', listadoMonedas, 'codigo_moneda', 'simbolo', 'nombre');
                        construirSelect('codigo_metodo_vuelto', listadoMetodosVuelto, 'codigo_metodo', 'nombre', 'nec_referencia');

                        // 🔥 SOLUCIÓN: Unificar el ID del modal para evitar problemas de capa (z-index)
                        $('#codigo_moneda_vuelto').select2({
                            placeholder: "Selecciona una Moneda",
                            dropdownParent: $('#secundario_modal_contenedor')
                        });
                        $('#codigo_metodo_vuelto').select2({
                            placeholder: "Selecciona un Método",
                            dropdownParent: $('#secundario_modal_contenedor')
                        });

                        abrirModalSecundario();
                    }
                } else if (lee.accion == "exito_vuelto") {
                    $('#modal_vuelto').removeClass('mostrar');
                    $('#vuelto_modal_content').removeClass('mostrar_modal').addClass('ocultar');
                    $('#f_vuelto')[0].reset();
                    muestraMensaje("success", 2000, "Vuelto Registrado", lee.mensaje);
                    consultar();
                    MultiConsulta();
                    cerrarModalSecundario();
                } else if (lee.accion == "eliminar") {
                    consultar();
                    MultiConsulta();
                    muestraMensaje("success", 2000, "Retiro Exitoso", lee.mensaje);
                } else if (lee.accion == "buscar") {
                    modificar(lee.datos);
                } else if (lee.accion == "reporte") {
                    cerrarAlertaEspara();
                    cerrarModal();
                    muestraMensaje("success", 1000, "Creado Exitosamente", 'Se ha generado el reporte');
                    setTimeout(function () {
                        const enlaceFantasma = document.createElement('a');
                        enlaceFantasma.href = lee.archivo;
                        enlaceFantasma.target = '_blank';
                        document.body.appendChild(enlaceFantasma);
                        enlaceFantasma.click();
                        document.body.removeChild(enlaceFantasma);
                    }, 1000);
                } else if (lee.accion == "error") {
                    muestraMensaje("error", 2000, "Error", lee.mensaje);
                }
            } catch (e) {
                alert("Error en JSON " + e.name);
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + status + err);
            }
        }
    });
}