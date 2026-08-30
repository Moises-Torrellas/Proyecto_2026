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
    inicializarPaginador();
    $('#monto').on('input', function () {
        let entrada = $(this).val().replace(/[^0-9]/g, '');
        if (entrada === '' || entrada === '00') {
            $(this).val(SIMBOLO_BASE + ' 0.00');
            return;
        }
        let valorFlotante = parseFloat(entrada) / 100;
        let valorFormateado = valorFlotante.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
            useGrouping: false
        });
        $(this).val(SIMBOLO_BASE + ' ' + valorFormateado);
    });

    $('#monto').on('focus', function () {
        if ($(this).val() === '' || $(this).val().replace(/[^0-9]/g, '') === '') {
            $(this).val(SIMBOLO_BASE + ' 0.00');
        }
    });
    
    // Validaciones
    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");
    Validacion("monto", /^[0-9.\b]*$/, /^\d+(\.\d{1,2})?$/, "Monto inválido", "proceso");
    Validacion("dias", /^[0-9]*$/, /^[0-9]{1,3}$/, "Solo números hasta tres digitos", "proceso");

    $('#proceso').on('click', function () {
        let accion = $(this).data("accion");
        if (accion == "incluir" || accion == "modificar") {
            if (validarEnvio(accion)) {
                let msj = accion == "incluir" ? "¿Esta seguro que quiere registrar el Concepto?" : "¿Esta seguro que quiere modificar el Concepto?";
                confirmar(msj, function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        let montoStr = $('#monto').val() || "";
                        datos.set('monto', montoStr.replace(/[^0-9.]/g, ''));
                        datos.append('accion', accion);
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "generar") {
            opcionesReporte(function(formato) {
                abrirAlertaEspara('Se esta generando el reporte', 'Espere un momento');
                var datos = new FormData($('#f')[0]);
                datos.append('accion', 'generar');
                datos.append('formato', formato);
                enviaAjax(datos);
            });
        }
    });

    $("#incluir").on("click", function () {
        limpia();
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Concepto");
        $("#titulo_modal").text("Registrar Concepto de cargo");
        $('#frecuencia option[value="Todas"]').remove();
        
        $('#nombre').closest('.colum').show();
        $('#monto').closest('.colum').show();
        $('#dias').closest('.colum').show();

        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        if ($('#frecuencia option[value="Todas"]').length === 0) {
            $('#frecuencia').prepend('<option value="Todas" selected>Todas las frecuencias</option>');
        }
        
        $('#nombre').closest('.colum').hide();
        $('#monto').closest('.colum').hide();
        $('#dias').closest('.colum').hide();

        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            { element: '#busqueda', popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar el Concepto de cargo que necesites.', position: 'bottom' } },
            { element: '#incluir', popover: { title: 'Nuevo Concepto de cargo', description: 'Si pulsa aqui se abrira un modal para ingresar un nuevo Concepto de cargo', position: 'bottom' } },
            { element: '#generar', popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira una alerta para generar un reporte en PDF o EXCEL.', position: 'left' } },
            { element: '#resultadoconsulta', popover: { title: 'Conceptos de cargo Registrados', description: 'Aqui se mostraran todos los Conceptos de cargo registrados.', position: 'top' } },
            { element: '#cbt_v', popover: { title: 'Modificar Concepto de cargo', description: 'Si pulsa aqui se abrira un modal para modificar el Conceptos de cargo seleccionado.', position: 'left' } },
            { element: '#cbt_r', popover: { title: 'Eliminar Concepto de cargo', description: 'Si pulsa aqui eliminara el Concepto de cargo seleccionado.', position: 'left' } },
            { element: '#cbt_t', popover: { title: 'Desactivar Concepto de cargo', description: 'Si pulsa aqui desactivará o activará el Concepto de cargo seleccionado.', position: 'left' } },
            { element: '#rowsPerPage', popover: { title: 'Registros Deseados', description: 'Aqui podra seleccionar la cantidad de registros que quiere que se muestren.', position: 'top' } },
            { element: '#botonera', popover: { title: 'Cambiar de Pagina', description: 'Botones para cambiar de página.', position: 'top' } },
            { element: '#cantidad', popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de Concepto de cargos cargados.', position: 'top' } },
        ];
        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });
});

function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}

function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar este proceso de cargo?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}

function cambiarEstatus(id, estadoActual) {
    let accionTexto = (estadoActual == 1) ? 'desactivar' : 'activar';
    confirmar(`¿Está seguro que desea ${accionTexto} este concepto de cargo?`, function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'estatus');
            datos.append('id', id);
            datos.append('estatus', estadoActual);
            enviaAjax(datos);
        }
    });
}

function validarEnvio(proceso) {
    if (proceso !== "generar") {
        if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, $("#nombre"), $("#nombre_spam"), "Solo letras  entre 3 y 30 caracteres", true)) {
            muestraMensaje("error", 2000, "Error", "Tiene que ingresar un nombre valido");
            return false;
        }
        else if ($('#frecuencia').val() == "" || $('#frecuencia').val() == null) {
            muestraMensaje("error", 2000, "Error", "Debe elegir la frecuencia");
            return false;
        } else if (validarkeyup(/^[0-9]{0,10}$/, $('#dias'), $("#dias_spam"), "Solo números de hasta 3 digitos.", true)) {
            muestraMensaje("error", 2000, "Error", "Tiene que ingresar un numero de dias valido");
            return false;
        }
    }
    return true;
}

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar concepto de cargo");
    $("#titulo_modal").text("Modificar concepto de cargo");
    
    $('#nombre').closest('.colum').show();
    $('#monto').closest('.colum').show();
    $('#dias').closest('.colum').show();

    $('#id').val(datos[0].codigo_concepto);
    $('#nombre').val(datos[0].nombre);
    
    let montoNumerico = parseFloat(datos[0].monto);
    let montoFormateado = (typeof SIMBOLO_BASE !== 'undefined' ? SIMBOLO_BASE : '$') + ' ' + montoNumerico.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        useGrouping: false
    });
    $('#monto').val(montoFormateado);
    $('#frecuencia option[value="Todas"]').remove();
    $('#frecuencia').val(datos[0].frecuencia);
    $('#dias').val(datos[0].dias_gracia);
    abrirModal();
}

function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    contenedor.html(htmlRecibido);

    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
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
                if (lee.accion == "incluir") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                } else if (lee.accion == "estatus") {
                    consultar();
                    muestraMensaje("success", 2000, "Actualización Exitosa", lee.mensaje);
                } else if (lee.accion == "eliminar") {
                    consultar();
                    muestraMensaje("success", 2000, "Eliminacion Exitosa", lee.mensaje);
                } else if (lee.accion == "modificar") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Modificacion Exitosa", lee.mensaje);
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
                    if (typeof Swal !== 'undefined') Swal.close(); 
                    muestraMensaje("error", 2000, "Error", lee.mensaje);
                }
            } catch (e) {
                if (typeof Swal !== 'undefined') Swal.close(); 
                alert("Error en JSON " + e.name);
            }
        },
        error: function (request, status, err) {
            if (typeof Swal !== 'undefined') Swal.close(); 
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + request.status + " " + err);
            }
        },
        complete: function () { },
    });
}