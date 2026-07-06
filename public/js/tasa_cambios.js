function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}

function consultarMonedas() {
    let datos = new FormData();
    datos.append('accion', 'consultarM');
    enviaAjax(datos);
}

$(document).ready(function () {
    inicializarPaginador();
    consultarMonedas();

    $('#codigo_moneda').select2({
        placeholder: "Selecciona una Moneda",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });

    $("#sincronizar_btn").on("click", function () {
        limpia();
        $("#accion_modal").val("sincronizar");
        $("#proceso").text("Sincronizar");
        $("#titulo_modal").text("Sincronizar Tasa del Día");
        $("#div_monto_manual").hide();
        abrirModal();
    });

    $("#actualizar_btn").on("click", function () {
        limpia();
        $("#accion_modal").val("registrar");
        $("#proceso").text("Guardar Tasa");
        $("#titulo_modal").text("Actualizar Tasa Manualmente");
        $("#div_monto_manual").show();
        abrirModal();
    });

    Validacion("tasa_bolivares", /^[0-9.]*$/, /^[0-9]+(\.[0-9]{1,4})?$/, "Formato inválido (Ej: 36.5000)", "proceso");

    $('#proceso').on('click', function () {
        let accion = $("#accion_modal").val();
        if (accion == "sincronizar") {
            if ($('#codigo_moneda').val() == "" || $('#codigo_moneda').val() == null) {
                muestraMensaje("error", 2000, "Error", "Debe seleccionar una moneda");
                return;
            }
            confirmar('¿Desea obtener la tasa de cambio actual desde el API?', function (confirmado) {
                if (confirmado) {
                    abrirAlertaEspara('Sincronizando', 'Consultando API de tasas de cambio...');
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'sincronizar');
                    enviaAjax(datos);
                }
            });
        }
        else if (accion == "registrar") {
            if ($('#codigo_moneda').val() == "" || $('#codigo_moneda').val() == null) {
                muestraMensaje("error", 2000, "Error", "Debe seleccionar una moneda");
                return;
            }
            if ($('#tasa_bolivares').val() == "") {
                muestraMensaje("error", 2000, "Error", "Debe ingresar una tasa");
                return;
            }
            confirmar('¿Está seguro que quiere guardar esta tasa manual?', function (confirmado) {
                if (confirmado) {
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'registrar');
                    enviaAjax(datos);
                }
            });
        }
    });
});

function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    contenedor.html(htmlRecibido);
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
}

function construirSelect(idSelect, datos, campoId, campo1) {
    var select = $('#' + idSelect);
    select.empty();
    select.append('<option value="" selected disabled>Seleccione una opción</option>');

    datos.forEach(dato => {
        let textoMostrar = escapeHTML(String(dato[campo1]));
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
                if (lee.accion == "consultarM") {
                    construirSelect('codigo_moneda', lee.datos, 'codigo_moneda', 'nombre');
                } else if (lee.accion == "exito") {
                    consultar();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Éxito", lee.mensaje);
                }else if (lee.accion == "error") {
                    Swal.close();
                    muestraMensaje("error", 2000, "Error", lee.mensaje);
                }
            } catch (e) {
                Swal.close();
                alert("Error en JSON: " + e.message);
            }
        },
        error: function (request, status, err) {
            Swal.close();
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

function limpia() {
    if($('#f')[0]) $('#f')[0].reset();
    $('.select2').val(null).trigger('change');
}
