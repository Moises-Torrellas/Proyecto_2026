$('#busqueda').off('keyup').on('keyup', busqueda);
let timerBusqueda;
let listaConceptosGlobal = [];
let monedaBaseGlobal = null;

function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}

function consultarAtletas() {
    let datos = new FormData();
    datos.append('accion', 'consultarA');
    enviaAjax(datos);
}

function consultarConceptos() {
    let datos = new FormData();
    datos.append('accion', 'consultarCo');
    enviaAjax(datos);
}

function consultarMonedaBase() {
    let datos = new FormData();
    datos.append('accion', 'consultarMoneda');
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

function calcularVencimientoAutomatico(fechaEmisionValor) {
    if (!fechaEmisionValor) return;
    let fecha = new Date(fechaEmisionValor + 'T00:00:00');
    fecha.setDate(fecha.getDate() + 30);
    let año = fecha.getFullYear();
    let mes = String(fecha.getMonth() + 1).padStart(2, '0');
    let dia = String(fecha.getDate()).padStart(2, '0');
    $('#fecha_vencimiento').val(`${año}-${mes}-${dia}`);
}


$(document).ready(function () {
    inicializarPaginador();
    $('#id_atleta').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });
    $('#id_concepto').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });
    consultarAtletas();
    consultarConceptos();

    $('#fecha_emision').on('change', function () {
        calcularVencimientoAutomatico($(this).val());
    });

    $('#id_concepto').on('change', function () {
        let idSeleccionado = $(this).val();
        if (idSeleccionado && listaConceptosGlobal.length > 0) {
            let conceptoEncontrado = listaConceptosGlobal.find(c => c.codigo_concepto == idSeleccionado);
            if (conceptoEncontrado && conceptoEncontrado.monto) {
                let montoBase = parseFloat(conceptoEncontrado.monto).toFixed(2);
                $('#monto_total').val(montoBase).trigger('input');
            }
        }
    });

    $("#monto_total").on("input", function () {
        var input = $(this).val().replace(/[^0-9.]/g, '');
        if ((input.match(/\./g) || []).length > 1) {
            input = input.substring(0, input.length - 1);
        }
        $(this).val(input);
    });

    Validacion("monto_total", /^[0-9.]*$/, /^[0-9]+(\.[0-9]{1,2})?$/, "Monto inválido (Ej: 50 o 50.50)", "proceso");

    $('#proceso').on('click', function () {
        let accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere generar esta(s) cuenta(s) por cobrar?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'incluir');
                        enviaAjax(datos);
                    }
                });
            }
        } else if (accion == "modificar") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere modificar este cargo?', function (confirmado) {
                    if (confirmado) {
                        $('#estatus').prop('disabled', false);
                        $('#id_atleta').prop('disabled', false);
                        $('#id_concepto').prop('disabled', false);
                        $('#fecha_emision').prop('readonly', false);
                        $('#fecha_vencimiento').prop('readonly', false);
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'modificar');
                        $('#estatus').prop('disabled', true);
                        $('#id_atleta').prop('disabled', true);
                        $('#id_concepto').prop('disabled', true);
                        if ($("#proceso").data("accion") === "modificar") {
                            $('#fecha_emision').prop('readonly', true);
                            $('#fecha_vencimiento').prop('readonly', true);
                        }
                        enviaAjax(datos);
                    }
                });
            }
        } else if (accion == "generar") {
            confirmar('¿Está seguro que quiere generar un reporte de cuentas por cobrar?', function (confirmado) {
                if (confirmado) {
                    abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    enviaAjax(datos);
                }
            });
        }
    });

    $("#incluir").on("click", function () {
        limpia();
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Cargo");
        $('#titulo_modal').text('Nueva Cuenta por Cobrar');
        $('#id_atleta').val(null).trigger('change');
        $('#id_concepto').val(null).trigger('change');
        $('#id_atleta').prop('disabled', false);
        $('#id_concepto').prop('disabled', false);
        $('#monto_pendiente').val('');
        $('#fecha_emision').prop('readonly', false);
        $('#fecha_vencimiento').prop('readonly', false);
        let fechaLocal = new Date();
        let año = fechaLocal.getFullYear();
        let mes = String(fechaLocal.getMonth() + 1).padStart(2, '0');
        let dia = String(fechaLocal.getDate()).padStart(2, '0');
        let hoy = `${año}-${mes}-${dia}`;
        $('#fecha_emision').val(hoy);
        calcularVencimientoAutomatico(hoy);
        $('#estatus').val('Pendiente');
        $('#estatus').prop('disabled', true);
        $('#monto_pendiente').prop('readonly', true);
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Reporte de Cuentas");
        abrirModal();
    });
});

function validarEnvio(accion) {
    if (accion == "incluir" || accion == "modificar") {
        let atletasSeleccionados = $('#id_atleta').val();
        if (!atletasSeleccionados || atletasSeleccionados.length === 0) {
            muestraMensaje("error", 2000, "Error", "Debe Seleccionar al menos un Atleta");
            return false;
        }
        if ($('#id_concepto').val() == "" || $('#id_concepto').val() == null) {
            muestraMensaje("error", 2000, "Error", "Debe Seleccionar un Concepto");
            return false;
        }
        if ($('#monto_total').val() == "") {
            muestraMensaje("error", 2000, "Error", "Debe Llenar el Monto Total");
            return false;
        }
        if ($('#fecha_emision').val() == "") {
            muestraMensaje("error", 2000, "Error", "Debe Llenar la Fecha de Emision");
            return false;
        }
        return true;
    }
    return true;
}

function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}

function anular(id) {
    confirmar('¿Desea bloquear este cargo? Se marcará como Anulado y no podrá ser modificado.', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('id', id);
            datos.append('accion', 'eliminar');
            enviaAjax(datos);
        }
    });
}

function modificar(datos) {
    limpia();
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Cargo");
    $('#titulo_modal').text('Modificar Cargo');
    $('#id').val(datos[0].id_cobrar);
    $('#id_atleta').val(datos[0].id_atleta).trigger('change');
    $('#id_concepto').val(datos[0].id_concepto).trigger('change');
    $('#monto_total').val(datos[0].monto_total ?? datos[0].monto_personalizado);
    let fechaEmi = datos[0].fecha_emision.split(' ')[0];
    $('#fecha_emision').val(fechaEmi);
    let estatusBD = parseInt(datos[0].estatus, 10);
    let estatusTexto = estatusBD === 3 ? 'Anulado' : (estatusBD === 2 ? 'Pagado' : 'Pendiente');
    $('#estatus').val(estatusTexto).trigger('change');
    $('#fecha_emision').prop('readonly', true);
    $('#estatus').prop('disabled', true);
    $('#id_atleta').prop('disabled', true);
    $('#id_concepto').prop('disabled', true);
    abrirModal();
}

function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    contenedor.html(htmlRecibido);
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

function construirSelect(idSelect, datos, campoId, campo1, campo2 = null, campo3 = null, campo4 = null) {
    var select = $('#' + idSelect);
    const esMultipleAtleta = idSelect === 'id_atleta' && select.prop('multiple');
    select.empty();
    if (!esMultipleAtleta) {
        select.append('<option value=""></option>');
    }
    datos.forEach(dato => {
        let textoMostrar = "";
        if (idSelect === 'id_atleta' && campo1 && campo2 && campo3 && campo4) {
            textoMostrar = `${escapeHTML(dato[campo1])} ${escapeHTML(dato[campo2])} - ${escapeHTML(dato[campo3])} / ${escapeHTML(dato[campo4])}`;
        } else if (idSelect === 'id_concepto' && campo1 && campo2) {
            textoMostrar = `${escapeHTML(dato[campo1])} - ${dato[campo2]}`;
        }
        var linea = `<option value="${dato[campoId]}">${textoMostrar}</option>`;
        select.append(linea);
    });
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
                if (lee.accion == "consultarA") {
                    construirSelect('id_atleta', lee.datos, 'codigo_atleta', 'p_nombre', 'p_apellidos', 'documento_identidad', 'categoria');
                } else if (lee.accion == "consultarCo") {
                    listaConceptosGlobal = lee.datos;
                    construirSelect('id_concepto', lee.datos, 'codigo_concepto', 'nombre', 'monto');
                } else if (lee.accion == "consultarMoneda") {
                    monedaBaseGlobal = lee.datos;
                    mostrarMonedaCargo(monedaBaseGlobal);
                } else if (lee.accion == "incluir") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                } else if (lee.accion == "eliminar") {
                    consultar();
                    muestraMensaje("success", 2000, "Anulación Exitosa", lee.mensaje);
                } else if (lee.accion == "modificar") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Modificación Exitosa", lee.mensaje);
                } else if (lee.accion == "buscar") {
                    modificar(lee.datos);
                } else if (lee.accion == "error") {
                    muestraMensaje("error", 2000, "Error", lee.mensaje);
                }
            } catch (e) {
                alert("Error en JSON: " + e.message);
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + status + " " + err);
            }
        }
    });
}

function escapeHTML(texto) {
    if (!texto) return '';
    var caracteres = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(texto).replace(/[&<>"']/g, m => caracteres[m]);
}

function toggleDetalles(elemento) {
    $(elemento).next('.listado_detalle_oculto').slideToggle();
    $(elemento).find('.icono_flecha_detalle').toggleClass('rotar_flecha');
}

function limpia() {
    if ($('#f')[0]) $('#f')[0].reset();
    $('#id_atleta').val(null).trigger('change');
    $('#id_concepto').val(null).trigger('change');
    $("#proceso").data("accion", "incluir");
    $("#proceso").text("Registrar Cargo");
}