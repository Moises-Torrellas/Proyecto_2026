$('#busqueda').off('keyup').on('keyup', busqueda);
let timerBusqueda;

function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}

// Consultar Atletas para el Select
function consultarAtletas() {
    let datos = new FormData();
    datos.append('accion', 'consultarA');
    enviaAjax(datos);
}

// Consultar Conceptos para el Select
function consultarConceptos() {
    let datos = new FormData();
    datos.append('accion', 'consultarCo');
    enviaAjax(datos);
}

// Consultar Monedas para el Select
function consultarMonedas() {
    let datos = new FormData();
    datos.append('accion', 'consultarM');
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
    consultarAtletas();
    consultarConceptos();
    consultarMonedas();

    // Validar entrada de monto para que solo acepte números y punto decimal
    $("#monto_total").on("input", function () {
        var input = $(this).val().replace(/[^0-9.]/g, '');
        // Evitar múltiples puntos
        if ((input.match(/\./g) || []).length > 1) {
            input = input.substring(0, input.length - 1);
        }
        $(this).val(input);

        // Si estamos incluyendo, el monto pendiente es igual al monto total
        if ($("#proceso").data("accion") === "incluir") {
            $("#monto_pendiente").val(input);
        }
    });

    Validacion("monto_total", /^[0-9.]*$/, /^[0-9]+(\.[0-9]{1,2})?$/, "Monto inválido (Ej: 50 o 50.50)", "proceso");

    $('#proceso').on('click', function () {
        let accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere generar esta cuenta por cobrar?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar este cargo?', function (confirmado) {
                    if (confirmado) {
                        // Habilitamos temporalmente los campos para que viajen en el FormData
                        $('#estatus').prop('disabled', false);
                        $('#id_atleta').prop('disabled', false);
                        $('#id_concepto').prop('disabled', false);
                        $('#id_moneda').prop('disabled', false);

                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'modificar');

                        // Los volvemos a deshabilitar visualmente
                        $('#estatus').prop('disabled', true);
                        $('#id_atleta').prop('disabled', true);
                        $('#id_concepto').prop('disabled', true);
                        $('#id_moneda').prop('disabled', true);

                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "generar") {
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

    // Configuración de Select2
    $('#id_atleta').select2({
        placeholder: "Selecciona un Atleta",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });

    $('#id_concepto').select2({
        placeholder: "Selecciona un Concepto",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });

    $('#id_moneda').select2({
        placeholder: "Selecciona una Moneda",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });

    $("#incluir").on("click", function () {
        limpia();

        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Cargo");
        $("#titulo_modal").text("Nueva Cuenta por Cobrar");

        $('#id_atleta').val(null).trigger('change');
        $('#id_concepto').val(null).trigger('change');
        $('#id_moneda').val(null).trigger('change');

        $('#id_atleta').prop('disabled', false);
        $('#id_concepto').prop('disabled', false);
        $('#id_moneda').prop('disabled', false);

        $('#monto_pendiente').val('');
        $('#fecha_emision').val(new Date().toISOString().split('T')[0]);
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

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Búsqueda', description: 'Aquí puedes buscar cargos por atleta o concepto.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevo Cargo', description: 'Si pulsa aquí se abrirá un modal para generar un nuevo cargo a un atleta.', position: 'bottom' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Cuentas Registradas', description: 'Aquí se mostrarán todas las cuentas por cobrar y su estatus.', position: 'top' }
            }
        ];
        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });
});

function validarEnvio(proceso) {
    if ($('#id_atleta').val() == "" || $('#id_atleta').val() == null) {
        muestraMensaje("error", 2000, "Error", "Debe seleccionar un atleta");
        return false;
    }

    if ($('#id_concepto').val() == "" || $('#id_concepto').val() == null) {
        muestraMensaje("error", 2000, "Error", "Debe seleccionar un concepto");
        return false;
    }

    if ($('#id_moneda').val() == "" || $('#id_moneda').val() == null) {
        muestraMensaje("error", 2000, "Error", "Debe seleccionar una moneda");
        return false;
    }

    if (validarkeyup(/^[0-9]+(\.[0-9]{1,2})?$/, $("#monto_total"), $("#monto_total_spam"), "Formato inválido", true)) {
        muestraMensaje("error", 2000, "Error", "Debe ingresar un monto válido (Ej. 50 o 50.50)");
        return false;
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
    $("#titulo_modal").text("Modificar Cuenta por Cobrar");

    $('#id').val(datos[0].id_cobrar);
    $('#id_atleta').val(datos[0].id_atleta).trigger('change');
    $('#id_concepto').val(datos[0].id_concepto).trigger('change');
    $('#id_moneda').val(datos[0].id_moneda).trigger('change');

    $('#monto_total').val(datos[0].monto_total);
    $('#monto_pendiente').val(datos[0].monto_pendiente);

    let fecha = datos[0].fecha_emision.split(' ')[0];
    $('#fecha_emision').val(fecha);

    let estatusBD = datos[0].estatus == '0' ? 'Pendiente' : datos[0].estatus;
    $('#estatus').val(estatusBD).trigger('change');

    $('#monto_pendiente').prop('readonly', true);
    $('#estatus').prop('disabled', true);
    $('#id_atleta').prop('disabled', true);
    $('#id_concepto').prop('disabled', true);
    $('#id_moneda').prop('disabled', true);

    abrirModal();
}

function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');

    // Inyectamos directamente el bloque HTML estructurado que procesó el servidor
    contenedor.html(htmlRecibido);

    // Reactivamos las librerías visuales y los comportamientos estéticos
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

function construirSelect(idSelect, datos, campoId, campo1, campo2 = null) {
    var select = $('#' + idSelect);
    select.empty();
    select.append('<option value="" selected disabled>Seleccione una opción</option>');

    datos.forEach(dato => {
        let textoMostrar = "";
        if (campo1 && campo2) {
            textoMostrar = `${escapeHTML(dato[campo1])} ${escapeHTML(dato[campo2])}`;
        } else {
            textoMostrar = escapeHTML(String(dato[campo1]));
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
        timeout: 10000,
        success: function (respuesta) {
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta);
                return;
            }
            try {
                var lee = JSON.parse(respuesta);
                if (lee.accion == "consultarA") {
                    construirSelect('id_atleta', lee.datos, 'id_atleta', 'nombre', 'apellido');
                } else if (lee.accion == "consultarCo") {
                    construirSelect('id_concepto', lee.datos, 'id_concepto', 'nombre');
                } else if (lee.accion == "consultarM") {
                    construirSelect('id_moneda', lee.datos, 'id_moneda', 'nombre');
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
                }
                else if (lee.accion == "error") {
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
