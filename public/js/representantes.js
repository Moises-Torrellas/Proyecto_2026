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

    $("#cedula").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        if (input.length > 4) {
            input = input.substring(0, 8);
        }
        $(this).val(input);
    });

    $("#telefono").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        if (input.length > 4) {
            input = input.substring(0, 4) + '-' + input.substring(4, 11);
        }
        $(this).val(input);
    });

    // Validación de Cédula
    Validacion("cedula", /^[0-9\b]*$/, /^[0-9]{7,8}$/, "Minimo 7 maximo 8 digitos, solo numeros", "proceso");

    // Validación de Nombre
    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");

    // Validación de Apellido
    Validacion("apellido", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");

    // Validación de Teléfono
    Validacion("telefono", /^[0-9\-\b]*$/, /^[0-9]{4}[-]{1}[0-9]{7}$/, "El formato es 0400-0000000");

    Validacion("nacionalidad", /^[VEP]$/, /^[VEP]$/, "Solo puede ingresar V, E o P", "proceso");

    Validacion("direccion", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,100}$/, "Solo letras entre 3 y 150 caracteres", "proceso");

    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este representante?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar este representante?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'modificar');
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "generar") {
            confirmar('¿Está seguro que quiere generar un reporte?', function (confirmado) {
                if (confirmado) {
                    abrirAlertaEspara('Se esta generando el reporte', 'Espere un momento')
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
        $("#proceso").text("Registrar Representante");
        $("#titulo_modal").text("Registrar Representante");
        $('#direccion').closest('.colum').show();
        $('#telefono').closest('.colum').show();
        $('#apellido').closest('.colum').show();
        $('#nombre').closest('.colum').show();
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();

        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        $('#telefono').closest('.colum').hide();
        $('#direccion').closest('.colum').hide();
        $('#apellido').closest('.colum').hide();
        $('#nombre').closest('.colum').hide();
        $('#nacionalidad').val(null).trigger('change');
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar al representante que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevo Representante', description: 'Si pulsa aqui se abrira un modal para ingresar un nuevo representante', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Representantes Registrados', description: 'Aqui se mostraran todos los representantes registrados.', position: 'top' }
            },
            {
                element: '#cbt_v',
                popover: { title: 'Modificar Representantes', description: 'Si pulsa aqui se abrira un modal para modificar el representante seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_r',
                popover: { title: 'Eliminar Representante', description: 'Si pulsa aqui eliminara el representante seleccionado.', position: 'left' }
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

});

function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}
function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar este representante?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}

function validarEnvio(proceso) {
    if ($('#nacionalidad option:selected').val() == null) {
        muestraMensaje("error", 2000, "Error", "Tiene que elegir una opción de nacionalidad");
        return false;
    }
    else if (validarkeyup(/^[0-9]{7,8}$/, $('#cedula'),
        $("#cedula_spam"), "Minimo 7 maximo 8 digitos, solo numeros", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar una cedula valida");
        return false;
    }
    else if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
        $("#nombre"), $("#nombre_spam"), "Solo letras  entre 3 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un nombre valido");
        return false;
    }
    else if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
        $('#apellido'), $("#apellido_spam"), "Solo letras entre 3 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un apellido valido");
        return false;
    }
    else if (validarkeyup(/^[0-9]{4}[-]{1}[0-9]{7}$/,
        $('#telefono'), $("#telefono_spam"), "El formato es 0400-000000", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un telefono valido");
        return false;
    }
    else if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,100}$/,
        $('#direccion'), $("#direccion_spam"), "Solo letras entre 3 y 100 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar una direccion valida");
        return false;
    }
    return true;
}

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Representante");
    $("#titulo_modal").text("Modificar Representante");
    $('#direccion').closest('.colum').show();
    $('#telefono').closest('.colum').show();
    $('#apellido').closest('.colum').show();
    $('#nombre').closest('.colum').show();
    $('#id').val(datos[0].id_representante);
    $('#cedula').val(datos[0].cedula);
    $('#nombre').val(datos[0].nombre);
    $('#apellido').val(datos[0].apellido);
    $('#telefono').val(datos[0].telefono);
    $('#direccion').val(datos[0].direccion);

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
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
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
                }
                else if (lee.accion == "reporte") {
                    // 1. Cerramos la alerta de espera de inmediato
                    cerrarAlertaEspara();

                    // 2. Cerramos el modal del formulario
                    cerrarModal();

                    // 3. Mostramos el mensaje de éxito (dura 2000ms en pantalla)
                    muestraMensaje("success", 1000, "Creado Exitosamente", 'Se ha generado el reporte');
                    setTimeout(function () {
                        const enlaceFantasma = document.createElement('a');
                        enlaceFantasma.href = lee.archivo;
                        enlaceFantasma.target = '_blank';
                        document.body.appendChild(enlaceFantasma);
                        enlaceFantasma.click();
                        document.body.removeChild(enlaceFantasma);
                    }, 1000);
                }
                else if (lee.accion == "error") {
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
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + request + status + err);
            }
        },
        complete: function () { },
    });
}
