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
    ModeloBancario("#tasa");

    Validacion("monto", /^[0-9.\b]*$/, /^\d+(\.\d{1,2})?$/, "Monto inválido", "proceso");
    Validacion("tasa", /^[0-9.\b]*$/, /^\d+(\.\d{1,4})?$/, "Tasa inválida", "proceso");
    Validacion("referencia", /^[a-zA-Z0-9\-\_\b]*$/, /^[a-zA-Z0-9\-\_]+$/, "Referencia inválida", "proceso");
    Validacion("fecha", /^[0-9\b-]*$/, /^\d{4}-\d{2}-\d{2}$/, "Seleccione una fecha válida", "proceso");

    $('#proceso').on('click', function () {
        let accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
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
        dropdownParent: $('.contenedor_modal'),
    });
    $('#metodo').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });
    $('#moneda').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });

    $("#incluir").on("click", function () {
        limpia();

        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Pago");
        $("#titulo_modal").text("Registrar Pago");

        $('#cuenta').val(null).trigger('change');
        $('#metodo').val(null).trigger('change');
        $('#moneda').val(null).trigger('change');
        $('#referencia').prop('disabled', false).removeClass("campo_deshabilitado");
        
        // Limpieza de mensajes dinámicos de ayuda
        $('#detalles_deuda_ayuda').html('');
        $('#monto_equivalente_ayuda').html('');
        $('#label_tasa').text("Tasa de cambio");

        // Forzar fecha del sistema y bloquear a solo lectura para evitar errores de API histórica
        let hoy = new Date().toISOString().split('T')[0];
        $('#fecha').val(hoy).attr('readonly', true);
        
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();

        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar al Atleta que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevo Atleta', description: 'Si pulsa aqui se abrira un modal para ingresar un nuevo Atleta', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Atletas Registrados', description: 'Aqui se mostraran todos los Atletas registrados.', position: 'top' }
            },
            {
                element: '#registro',
                popover: { title: 'Registro de un Atleta', description: 'Aqui se mostrara la informacion de un Atleta si pulsa el registro se desplegara mas informacion.', position: 'bottom' }
            },
            {
                element: '#cbt_v',
                popover: { title: 'Modificar Atletas', description: 'Si pulsa aqui se abrira un modal para modificar el Atleta seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_r',
                popover: { title: 'Eliminar Atleta', description: 'Si pulsa aqui eliminara el Atleta seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_sec',
                popover: { title: 'Generar Curriculum', description: 'Si pulsa aqui generara un curriculum del Atleta seleccionado.', position: 'left' }
            },
            {
                element: '#rowsPerPage',
                popover: { title: 'Registros Deseados', description: 'Aqui podra seleccionar la cantidad de registros que quiere que se muestren.', position: 'top' }
            },
            {
                element: '#botonera',
                popover: { title: 'Cambiar de Pagina', description: 'Botones para cambiar de página.', position: 'top' }
            },
            {
                element: '#cantidad',
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de representantes cargados.', position: 'top' }
            },
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
            $('#referencia').prop('disabled', true).addClass("campo_deshabilitado");
            $('#referencia').val('');
        }
    });

    // Evento dinámico interactivo al seleccionar una Cuenta por Cobrar
    $('#cuenta').on('change', function () {
        let idsCobrar = $(this).val();
        if (!idsCobrar || idsCobrar.length === 0) {
            cuentaSeleccionadaActual = null;
            $('#detalles_deuda_ayuda').html(''); 
            return;
        }
        
        cuentaSeleccionadaActual = listadoCuentas.find(c => c.id_cobrar == idsCobrar[0]);
        
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

        // Inyección visual en el modal de los datos de cobro pendientes
        $('#detalles_deuda_ayuda').html(htmlDeudas);

        solicitarTasaAPI();
    });

    // Al cambiar la moneda de pago, recalculamos la tasa de cambio de la divisa
    $('#moneda').on('change', function () {
        solicitarTasaAPI();
    });

    // Listener en tiempo real para el cálculo visual de amortización
    $('#monto, #tasa').on('input', function () {
        recalcularAmortizacion();
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

    // Si es la misma moneda, fijamos la paridad en 1.00 sin consumir recursos externos
    if (isoCuenta === isoPago) {
        $('#tasa').val('1.0000').attr('readonly', true);
        $('#label_tasa').html(`Tasa de cambio <span style="color: #28a745; font-size:12px;">(Misma moneda: 1 ${isoPago} = 1 ${isoCuenta})</span>`);
        recalcularAmortizacion();
        return;
    }

    // Feedback visual previo a la carga asíncrona
    $('#label_tasa').html(`Tasa de cambio <span style="color: #007bff; font-size:11px;">(Convirtiendo de ${isoPago} a ${isoCuenta})</span>`);

    let datos = new FormData();
    datos.append('accion', 'consultarTasa');
    datos.append('moneda_base', isoCuenta);
    datos.append('moneda_pago', isoPago);
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
                if (lee.exito) {
                    $('#tasa').val(parseFloat(lee.tasa).toFixed(4)).attr('readonly', false);
                    $('#label_tasa').html(`Tasa de Cambio <strong style="color: #007bff;">(${isoPago} ➔ ${isoCuenta})</strong>`);
                    recalcularAmortizacion();
                } else {
                    muestraMensaje("error", 3000, "Aviso de Tasa", lee.mensaje);
                }
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
        let montoAmortizado = monto / tasa;
        
        let simDeuda = cuentaSeleccionadaActual.moneda_simbolo;
        let isoDeuda = cuentaSeleccionadaActual.moneda_abreviatura.toUpperCase();
        
        let idMonedaPago = $('#moneda').val();
        let monedaPagoObj = listadoMonedas.find(m => m.id_moneda == idMonedaPago);
        let isoPago = monedaPagoObj ? monedaPagoObj.abreviatura.toUpperCase() : '';

        // Bloque dinámico explicativo de equivalencias para amortizar montos
        $('#monto_equivalente_ayuda').html(`
            <div style="background-color: #e2f0d9; border-left: 4px solid #385723; padding: 8px; border-radius: 4px; margin-top: 5px; font-size: 13px;">
                🟢 Los <strong>${monto.toFixed(2)} ${isoPago}</strong> ingresados equivalen a: 
                <strong style="font-size: 14px; color: #385723;">${montoAmortizado.toFixed(2)} ${simDeuda} (${isoDeuda})</strong> que se restarán del saldo pendiente.
            </div>
        `);
    } else {
        $('#monto_equivalente_ayuda').html('');
    }
}

function construirSelect(idSelect, datos, campoId, campo1, campo2 = null, campo3 = null, campo4 = null, campo5= null) {
    var select = $('#' + idSelect);
    select.empty();
    
    // Evitar poner la opción vacía como seleccionable en selects múltiples
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
            textoMostrar = `${dato[campo1]} ${dato[campo4]}${dato[campo5]} - ${dato[campo2]} ${dato[campo3]}`;
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
        timeout: 10000,
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

                    construirSelect('moneda', lee.monedas, 'id_moneda', 'simbolo', 'nombre');
                    construirSelect('metodo', lee.metodos, 'id_metodos', 'nombre', 'nec_referencia');
                    construirSelect('cuenta', lee.cuentas, 'id_cobrar', 'concepto_nombre', 'atleta_nombre', 'atleta_apellido', 'moneda_simbolo', 'monto_pendiente');
                } else if (lee.accion == "incluir") {
                    consultar();
                    limpia();
                    $('#monto_equivalente_ayuda').html('');
                    $('#detalles_deuda_ayuda').html('');
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                } else if (lee.accion == "eliminar") {
                    consultar();
                    muestraMensaje("success", 2000, "Retiro Exitoso", lee.mensaje);
                } else if (lee.accion == "modificar") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Modificacion Exitosa", lee.mensaje);
                } else if (lee.accion == "buscar") {
                    modificar(lee.datos);
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