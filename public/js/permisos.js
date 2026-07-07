$('#busqueda').off('keyup').on('keyup', busqueda);
let timerBusqueda;
function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}
function consultarModulos() {
    let datos = new FormData();
    datos.append('accion', 'consultarModulos');
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
    consultarModulos();
    inicializarPaginador();

    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,100}$/, "Solo letras entre 3 y 30 caracteres", "proceso");
    Validacion("descripcion", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,100}$/, "Solo letras entre 3 y 30 caracteres");
    Validacion("clave", /^[a-z_]*$/, /^[a-z_]{5,50}$/, "Formato: accion_entidad (5-50 caracteres)", "proceso");

    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este rol?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar este rol?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'modificar');
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "permisos") {
            confirmar('¿Está seguro que quiere guardar los permisos?', function (confirmado) {
                if (confirmado) {
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'guardar_permisos');
                    enviaAjax(datos);
                }
            });
        }
    });

    $('#modulo').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('#contenedor_modal'),
    });

    $("#incluir").on("click", function () {
        limpia();
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Permiso");
        $("#titulo_modal").text("Registrar Permiso");
        $('#clave').closest('.colum').show();
        $('#modulo').closest('.colum').show();

        abrirModal();
    });

    /* $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar al registro que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevo Registro', description: 'Si pulsa aqui se abrira un modal para registrar un nuevo rol', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Registros', description: 'Aqui se mostraran todos los registros.', position: 'top' }
            },
            {
                element: '#cbt_v',
                popover: { title: 'Modificar Registro', description: 'Si pulsa aqui se abrira un modal para modificar el registro seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_r',
                popover: { title: 'Eliminar Registro', description: 'Si pulsa aqui eliminara el registro seleccionado.', position: 'left' }
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
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de usuarios registrados.', position: 'top' }
            },
        ];

        // Iniciar tour
        const driver = iniciarTourConPasos(pasos);
        driver.start();
    }); */

});

function validarEnvio(proceso) {
    if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,100}$/,
        $('#nombre'), $("#nombre_spam"), "Solo letras entre 3 y 30 caracteres")) {
        muestraMensaje("error", 2000, "Error", "Solo puede ingresar letra, Maximo 30 caracteres");
        return false;
    }
    if (proceso === 'incluir') {
        if (validarkeyup(/^[a-z_]{5,50}$/,
            $('#clave'), $("#clave_spam"), "El formato debe ser 'accion_entidad' (5-50 caracteres)", true)) {
            muestraMensaje("error", 2000, "Error", "La clave debe tener el formato accion_entidad y medir entre 5 y 50 caracteres");
            return false;
        }
        else if ($('#modulo').val() == "" || $('#modulo').val() == null) {
            muestraMensaje("error", 2000, "Error", "Debe elegir un modulo.");
            return false;
        }
    }

    return true;
}

function bloquear(id, b) {
    let texto = (b == 1) ? 'bloquear' : 'desbloquear';
    confirmar(`¿Está seguro que quiere ${texto} este permiso? Este permiso se ${texto} para todos los roles que lo tengan vinculado`, function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'bloquear');
            datos.append('id', id);
            datos.append('bloqueo', b);
            enviaAjax(datos);

        }
    });
}
function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}

function modificar(datos) {
    limpia();
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Permiso");
    $("#titulo_modal").text("Modificar Permiso");
    $('#clave').closest('.colum').hide();
    $('#modulo').closest('.colum').hide();
    $('#id').val(datos[0].id_permiso);
    $('#nombre').val(datos[0].nombre);
    $('#descripcion').val(datos[0].descripcion);
    abrirModal();
}


function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    contenedor.html(htmlRecibido);
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

function construirSelect(idSelect, datos, campoId, campo1, campo2 = null, campo3 = null) {
    var select = $('#' + idSelect);
    select.empty();
    select.append('<option value="" selected disabled>Seleccione una opción</option>');

    datos.forEach(dato => {
        let textoMostrar = "";
        let atributosExtra = "";

        if (idSelect === 'modulo' && campo1) {
            textoMostrar = `${escapeHTML(dato[campo1])}`;
        }
        else {
            textoMostrar = escapeHTML(String(dato[campo1]));
        }
        var linea = `<option value="${dato[campoId]}" ${atributosExtra}>${textoMostrar}</option>`;
        select.append(linea);
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
    return texto.replace(/[&<>"']/g, m => caracteres[m]);
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
                if (lee.accion == "consultar") {
                    crearConsulta(lee.datos);
                }
                else if (lee.accion == "buscar") {
                    modificar(lee.datos);
                }
                else if (lee.accion == "incluir") {
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                    consultar();
                    limpia();
                    cerrarModal();
                } else if (lee.accion == "consultarModulos") {
                    construirSelect('modulo', lee.modulos, 'id_modulo', 'nombre_modulo');
                } else if (lee.accion == "modificar") {
                    muestraMensaje("success", 2000, "Modificacion Exitosa", lee.mensaje);
                    consultar();
                    limpia();
                    cerrarModal();
                } else if (lee.accion == "eliminar") {
                    muestraMensaje("success", 2000, "Eliminacion Exitosa", lee.mensaje);
                    consultar();
                } else if (lee.accion == "bloquear") {
                    muestraMensaje("success", 2000, "Exitosa", lee.mensaje);
                    consultar();
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
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + request + status + err);
            }
        },
        complete: function () { },
    });
}