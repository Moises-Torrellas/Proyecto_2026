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

    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");

    $('#proceso').on('click', function () {
        let accion = $(this).data("accion");
        
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este estado físico?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar este estado físico?', function (confirmado) {
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
                    if(typeof abrirAlertaEspara === 'function') abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    enviaAjax(datos);
                }
            });
        }
    });

    $("#incluir").on("click", function () {
        limpia(); 
        $("#id_estado").val("");
        
        // Ajustes visuales para el formulario
        $("#contenedor_nombre").show();
        $("#opcion_default").prop("disabled", true).text("Seleccione...");

        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Estado");
        $("#titulo_modal").text("Registrar Nuevo Estado Físico");
        abrirModal(); 
    });

    $("#generar").on("click", function () {
        limpia();
        
        // Ajustes visuales: ocultamos el nombre, y convertimos la opción vacía en "Todos"
        $("#contenedor_nombre").hide();
        $("#opcion_default").prop("disabled", false).text("Todos los niveles");
        $("#nivel_estado").val("");

        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        abrirModal();
    });
    
    // Limpiar manual
    $("#limpiar").on("click", function() {
        limpia();
    });
});

// --- FUNCIONES LÓGICAS GLOBALES ---

function buscar(id_estado) { 
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id_estado', id_estado);
    enviaAjax(datos);
}

function eliminar(id_estado) { 
    confirmar('¿Está seguro que quiere eliminar este estado físico?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id_estado', id_estado);
            enviaAjax(datos);
        }
    });
}

function validarEnvio(proceso) {
    if (proceso !== "generar") {
        if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, $("#nombre"), $("#nombre_spam"), "Permitido entre 3 y 30 caracteres solo letras", true)) {
            muestraMensaje("error", 2000, "Error", "Debe ingresar un nombre válido para el estado físico.");
            return false;
        }
        else if ($('#nivel_estado option:selected').val() == null || $('#nivel_estado option:selected').val() == "") {
            muestraMensaje("error", 2000, "Error", "Tiene que elegir una opción de nivel.");
            return false;
        }
    }
    return true;
}

function modificar(datos) {
    limpia();
    
    // Devolvemos a la normalidad el diseño por si se abrió el modal de reporte antes
    $("#contenedor_nombre").show();
    $("#opcion_default").prop("disabled", true).text("Seleccione...");

    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Estado");
    $("#titulo_modal").text("Modificar Estado Físico");
    
    $('#id_estado').val(datos[0].id_estado);
    $('#nombre').val(datos[0].nombre);
    $('#nivel_estado').val(datos[0].nivel_estado);

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
    var caracteres = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return texto ? texto.replace(/[&<>"']/g, m => caracteres[m]) : '';
}

function limpia() {
    if($('#f')[0]) {
        $('#f')[0].reset();
        $('#nivel_estado').val(""); // Resetear select
    }
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
                else if (lee.accion === "reporte") {
                    // Cierra la alerta y el modal
                    if(typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
                    if(typeof cerrarModal === 'function') cerrarModal();
                    
                    muestraMensaje("success", 1000, "Reporte Generado", 'Se ha generado el reporte');
                    
                    // Técnica del enlace fantasma para abrir el PDF
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
                    if(typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
                    muestraMensaje("error", 3000, "Error", lee.mensaje);
                }
            } catch (e) {
                alert("Error procesando los datos: " + e.message);
                console.error(respuesta); 
            }
        },
        error: function (request, status, err) {
            if(typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 3000, "Error de Conexión", "Revisa la consola. Código: " + request.status);
                console.error("Detalle del error:", request.responseText);
            }
        }
    });
}