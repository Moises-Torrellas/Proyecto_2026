$('#busqueda').off('keyup').on('keyup', busqueda);
let timerBusqueda;
let listaConceptosGlobal = []; // Para almacenar temporalmente los montos de los conceptos

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

// Función auxiliar para calcular +30 días reactivamente
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
    consultarAtletas();
    consultarConceptos();
    consultarMonedas();

    // Evento reactivo para cuando cambie la fecha de emisión
    $('#fecha_emision').on('change', function () {
        calcularVencimientoAutomatico($(this).val());
    });

    // Evento reactivo para auto-cargar el monto al seleccionar un concepto
    $('#id_concepto').on('change', function () {
        let idSeleccionado = $(this).val();
        if (idSeleccionado && listaConceptosGlobal.length > 0) {
            // Buscamos el concepto coincidente dentro del array global
            let conceptoEncontrado = listaConceptosGlobal.find(c => c.id_concepto == idSeleccionado);
            if (conceptoEncontrado && conceptoEncontrado.monto) {
                let montoBase = parseFloat(conceptoEncontrado.monto).toFixed(2);
                $('#monto_total').val(montoBase).trigger('input');
            }
        }
    });

    // Validar entrada de monto para que solo acepte números y punto decimal
    $("#monto_total").on("input", function () {
        var input = $(this).val().replace(/[^0-9.]/g, '');
        if ((input.match(/\./g) || []).length > 1) {
            input = input.substring(0, input.length - 1);
        }
        $(this).val(input);

        // El monto pendiente se auto-iguala al monto total durante la inclusión
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
                        // Habilitamos los campos temporalmente para compilar el FormData completo
                        $('#estatus').prop('disabled', false);
                        $('#id_atleta').prop('disabled', false);
                        $('#id_concepto').prop('disabled', false);
                        $('#id_moneda').prop('disabled', false);
                        $('#fecha_emision').prop('readonly', false);
                        $('#fecha_vencimiento').prop('readonly', false);

                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'modificar');

                        // Bloqueamos visualmente de nuevo
                        $('#estatus').prop('disabled', true);
                        $('#id_atleta').prop('disabled', true);
                        $('#id_concepto').prop('disabled', true);
                        $('#id_moneda').prop('disabled', true);
                        if ($("#proceso").data("accion") === "modificar") {
                            $('#fecha_emision').prop('readonly', true);
                            $('#fecha_vencimiento').prop('readonly', true);
                        }

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
        
        // Habilitamos la edición de fechas para un nuevo registro
        $('#fecha_emision').prop('readonly', false);
        $('#fecha_vencimiento').prop('readonly', false);

        // Inicializamos las fechas por defecto en el cliente
        let fechaLocal = new Date();
        let año = fechaLocal.getFullYear();
        let mes = String(fechaLocal.getMonth() + 1).padStart(2, '0');
        let dia = String(fechaLocal.getDate()).padStart(2, '0');
        let hoy = `${año}-${mes}-${dia}`;

        // Asignamos la fecha de emisión correcta (Día 23)
        $('#fecha_emision').val(hoy);
        
        // El vencimiento se calculará automáticamente sumando 30 días en base al día 23
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

    if ($('#fecha_emision').val() == "") {
        muestraMensaje("error", 2000, "Error", "Debe ingresar una fecha de emisión");
        return false;
    }

    if ($('#fecha_vencimiento').val() == "") {
        muestraMensaje("error", 2000, "Error", "Debe ingresar una fecha de vencimiento");
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

    // Cargamos ambas fechas provenientes de la BD
    let fechaEmi = datos[0].fecha_emision.split(' ')[0];
    let fechaVen = datos[0].fecha_vencimiento.split(' ')[0];
    $('#fecha_emision').val(fechaEmi);
    $('#fecha_vencimiento').val(fechaVen);

    let estatusBD = datos[0].estatus == '0' ? 'Pendiente' : datos[0].estatus;
    $('#estatus').val(estatusBD).trigger('change');

    // Al modificar, las fechas quedan bloqueadas para mantener la consistencia histórica del cargo
    $('#fecha_emision').prop('readonly', true);
    $('#fecha_vencimiento').prop('readonly', true);

    $('#monto_pendiente').prop('readonly', true);
    $('#estatus').prop('disabled', true);
    $('#id_atleta').prop('disabled', true);
    $('#id_concepto').prop('disabled', true);
    $('#id_moneda').prop('disabled', true);

    abrirModal();
}

function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    contenedor.html(htmlRecibido);

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
                    // CACHEADO: Guardamos la lista completa en la variable global para tener acceso al monto
                    listaConceptosGlobal = lee.datos;
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
    if($('#f')[0]) $('#f')[0].reset();
    $('.select2').val(null).trigger('change');
    $("#proceso").data("accion", "incluir");
    $("#proceso").text("Registrar Cargo");
}