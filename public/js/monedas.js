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
    consultar();


    // Validación de Nombre
    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");

    Validacion("abreviatura", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{2,4}$/, "Solo letras entre 2 y 4 caracteres", "proceso");

    Validacion("simbolo", /^[A-Za-z\u00f1\u00d1$€£]*$/, /^[A-Za-z\u00f1\u00d1$€£]{1,5}$/, "Ingrese un símbolo válido (Ej: $, Bs, €)", "proceso"
    );

    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar esta moneda?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar esta moneda?', function (confirmado) {
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
        $("#proceso").text("Registrar Moneda");
        $("#titulo_modal").text("Registrar Moneda");
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();

        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        $('#nacionalidad').val(null).trigger('change');
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar la moneda que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nueva Moneda', description: 'Si pulsa aqui se abrira un modal para ingresar una nueva moneda', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Monedas Registradas', description: 'Aqui se mostraran todos las monedas registradas.', position: 'top' }
            },
            {
                element: '#cbt_v',
                popover: { title: 'Modificar Moneda', description: 'Si pulsa aqui se abrira un modal para modificar la moneda seleccionada.', position: 'left' }
            },
            {
                element: '#cbt_r',
                popover: { title: 'Eliminar Moneda', description: 'Si pulsa aqui eliminara la moneda seleccionada.', position: 'left' }
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
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de monedas cargadas.', position: 'top' }
            },
        ];

        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });

});

function validarEnvio(proceso) {
    if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
        $("#nombre"), $("#nombre_spam"), "Solo letras  entre 3 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un nombre valido");
        return false;
    }
    else if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{2,4}$/,
        $('#abreviatura'), $("#abreviatura_spam"), "Solo letras entre 2 y 4 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar una abreviatura valido");
        return false;
    }
    else if (validarkeyup(/^[A-Za-z\u00f1\u00d1$€£]{1,5}$/,
        $('#simbolo'), $("#simbolo_spam"), "Ingrese un símbolo válido (Ej: $, Bs, €)", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un simbolo valido");
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
function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar esta moneda?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Moneda");
    $("#titulo_modal").text("Modificar Moneda");
    $('#id').val(datos[0].id_moneda);
    $('#nombre').val(datos[0].nombre);
    $('#abreviatura').val(datos[0].abreviatura);
    $('#simbolo').val(datos[0].simbolo);

    abrirModal();
}

let botonPresionado = null
function bloquear(id, b, elemento) {
    let texto = (b == 1) ? 'bloquear' : 'desbloquear';
    confirmar(`¿Está seguro que quiere ${texto} esta Moneda?`, function (confirmado) {
        if (confirmado) {
            botonPresionado = elemento;
            var datos = new FormData();
            datos.append('accion', 'bloquear');
            datos.append('id', id);
            datos.append('bloqueo', b);
            enviaAjax(datos);

        }
    });
}

// Reemplaza por completo tu función anterior en monedas.js
function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');

    // Inyectamos directamente el string de tarjetas HTML que escupió el PHP
    contenedor.html(htmlRecibido);

    // Ejecutamos tus inicializadores estéticos y paginadores normales
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
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
            // 1. COMPROBACIÓN CRÍTICA: ¿Es HTML? (Se evalúa ANTES de parsear JSON)
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta);
                return; // Cortamos el flujo de inmediato de forma exitosa
            }

            // 2. PROCESAMIENTO JSON: Si no es HTML, obligatoriamente es una respuesta JSON
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
                } else if (lee.accion == "bloquear") {
                    muestraMensaje("success", 2000, "Bloqueo Exitosa", lee.mensaje);
                    consultar();
                } else if (lee.accion == "error") {
                    muestraMensaje("error", 2000, "Error", lee.mensaje);
                }
            } catch (e) {
                console.error("Error al procesar JSON:", e, respuesta);
                alert("Error en JSON: " + e.message);
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + status + " " + err);
            }
        },
        complete: function () { },
    });
}