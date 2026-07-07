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

    // 2. Validaciones en tiempo real
    Validacion("nombre", /^[A-Za-z0-9\-\b\s]*$/, /^[A-Za-z0-9\-\b\s]{2,30}$/, "Permitido entre 2 y 30 caracteres", "proceso");
    Validacion("ubicacion", /^[A-Za-z0-9\s.,#-]*$/, /^[A-Za-z0-9\s.,#-]{5,150}$/, "Permitido entre 5 y 150 caracteres", "proceso");

    // 3. Lógica de los Botones Guardar/Modificar
    $('#proceso').on('click', function () {
        let accion = $(this).data("accion");
        
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este torneo?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar este torneo?', function (confirmado) {
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
                    abrirAlertaEspara('Se está generando el reporte', 'Espere un momento')
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    enviaAjax(datos);
                }
            });
        }
    });

    // 4. Botones de la vista (Abrir Modales)
    $("#incluir").on("click", function () {
        limpia(); // Limpia el formulario
        $("#codigo_torneo").val(""); // Cambiado de id a codigo_torneo
        $('#estatus').val("").trigger('change'); // Limpia el select
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Torneo");
        $("#titulo_modal").text("Registrar Nuevo Torneo");
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        abrirModal();
    });
});

// --- FUNCIONES LÓGICAS GLOBALES ---

function buscar(codigo_torneo) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('codigo_torneo', codigo_torneo); // Ajustado
    enviaAjax(datos);
}

function eliminar(codigo_torneo) {
    confirmar('¿Está seguro que quiere eliminar este torneo?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('codigo_torneo', codigo_torneo); // Ajustado
            enviaAjax(datos);
        }
    });
}

function validarEnvio(proceso) {
    // Validar Select de Estatus
    if ($('#estatus option:selected').val() === "") {
        muestraMensaje("error", 2000, "Error", "Tiene que elegir el estatus del torneo");
        return false;
    }
    // Validar Nombre
    if (validarkeyup(/^[A-Za-z0-9\-\b\s]{2,30}$/, $("#nombre"), $("#nombre_spam"), "Permitido entre 2 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Debe ingresar un nombre de torneo válido");
        return false;
    }
    // Validar Fechas Vacías
    if ($('#fecha_inicio').val() === "") {
        muestraMensaje("error", 2000, "Error", "Debe seleccionar una fecha de inicio");
        return false;
    }
    if ($('#fecha_fin').val() === "") {
        muestraMensaje("error", 2000, "Error", "Debe seleccionar una fecha de fin");
        return false;
    }
    
    // Validación lógica: Fecha inicio no puede ser mayor a la fecha final
    let fechaInicio = new Date($('#fecha_inicio').val());
    let fechaFin = new Date($('#fecha_fin').val());
    
    if (fechaInicio > fechaFin) {
        muestraMensaje("error", 3500, "Error de Fechas", "La fecha de inicio no puede ser posterior a la fecha de finalización");
        return false;
    }

    // Validar Ubicación
    if (validarkeyup(/^[A-Za-z0-9\s.,#-]{5,150}$/, $("#ubicacion"), $("#ubicacion_spam"), "Permitido entre 5 y 150 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Debe ingresar una ubicación válida");
        return false;
    }

    return true;
}

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Torneo");
    $("#titulo_modal").text("Modificar Torneo");
    
    // Llenamos el formulario con los datos de la BD ajustado a codigo_torneo
    $('#codigo_torneo').val(datos[0].codigo_torneo);
    $('#nombre').val(datos[0].nombre);
    $('#fecha_inicio').val(datos[0].fecha_inicio);
    $('#fecha_fin').val(datos[0].fecha_fin);
    $('#ubicacion').val(datos[0].ubicacion);
    $('#estatus').val(datos[0].estatus).trigger('change');

    abrirModal();
}



function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');

    contenedor.html(htmlRecibido);

    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

function escapeHTML(texto) {
    if (!texto) return "";
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
        url: "", // Se envía a la misma ruta actual (/Torneos)
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
                } 
                else if (lee.accion == "eliminar") {
                    consultar();
                    muestraMensaje("success", 2000, "Eliminación Exitosa", lee.mensaje);
                } 
                else if (lee.accion == "modificar") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Modificación Exitosa", lee.mensaje);
                } 
                else if (lee.accion == "buscar") {
                    modificar(lee.datos);
                }
                else if (lee.accion == "error") {
                    muestraMensaje("error", 3000, "Error", lee.mensaje);
                }
            } catch (e) {
                alert("Error procesando los datos: " + e.message);
                console.error(respuesta);
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 3000, "Error de Conexión", "Revisa la consola. Código: " + request.status);
            }
        }
    });
}