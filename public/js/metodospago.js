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
    // 1. Cargar la tabla al iniciar
    inicializarPaginador();

    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Permitido entre 2 y 30 caracteres", "proceso");


    // 3. Lógica de los Botones Guardar/Modificar
    $('#proceso').on('click', function () {
        let accion = $(this).data("accion");

        if (accion == "incluir") {
            if (validarEnvio(accion)) { // Descomentado para que valide antes de incluir
                confirmar('¿Está seguro que quiere registrar este método de pago?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar este método de pago?', function (confirmado) {
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

    // 4. Botones de la vista
    $("#incluir").on("click", function () {
        limpia(); // Limpia el formulario
        $("#id").val(""); // Este es el input hidden en tu form (asegúrate de que en el backend reciba id_metodos)
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Método");
        $("#titulo_modal").text("Registrar Nuevo Método de Pago");
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

function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}

function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar este método de pago?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}

function validarEnvio(proceso) {
    // Validar Nombre
    if (validarkeyup(/^[A-Za-z0-9\sñÑáéíóúÁÉÍÓÚ]{2,30}$/, $("#nombre"), $("#nombre_spam"), "Permitido entre 2 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Debe ingresar un nombre de método válido");
        return false;
    }

    // Validar Necesita Referencia (Asumiendo que no puede estar vacío)
    if ($("#nec_referencia").val() === null || $("#nec_referencia").val() === "") {
        muestraMensaje("error", 2000, "Error", "Debe indicar si el método necesita referencia");
        // Opcional: mostrar un span de error si tienes uno configurado
        // $("#nec_referencia_spam").text("Campo requerido").show(); 
        return false;
    }
    return true;
}

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Método");
    $("#titulo_modal").text("Modificar Método de Pago");

    // Llenamos el formulario con los datos recibidos de la BD
    $('#id').val(datos[0].codigo_metodo);
    $('#nombre').val(datos[0].nombre);
    $('#nec_referencia').val(datos[0].nec_referencia);
    $('#estatus').val(datos[0].estatus);

    abrirModal();
}

let botonPresionado = null
function bloquear(id, b, elemento) {
    let texto = (b == 1) ? 'bloquear' : 'desbloquear';
    confirmar(`¿Está seguro que quiere ${texto} este Metodo?`, function (confirmado) {
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

function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');

    // Inyectamos directamente el string de tarjetas HTML que escupió el PHP
    contenedor.html(htmlRecibido);

    // Ejecutamos tus inicializadores estéticos y paginadores normales
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

function escapeHTML(texto) {
    if (!texto) return '';
    var caracteres = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(texto).replace(/[&<>"']/g, m => caracteres[m]);
}

var token = $('meta[name="csrf-token"]').attr('content');

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
                else if (lee.accion == "bloquear") {
                    muestraMensaje("success", 2000, "Bloqueo Existoso", lee.mensaje);
                    consultar();
                }
                else if (lee.accion == "buscar") {
                    modificar(lee.datos);
                }
                else if (lee.accion == "generar") {
                    // Cerramos la alerta de espera de SweetAlert (o la que uses)
                    if (typeof Swal !== 'undefined') {
                        Swal.close(); 
                    } else if (typeof cerrarModal === 'function') {
                        cerrarModal(); 
                    }

                    // Decodificamos y abrimos el PDF
                    if (lee.pdf) {
                        const byteCharacters = atob(lee.pdf);
                        const byteNumbers = new Array(byteCharacters.length);
                        for (let i = 0; i < byteCharacters.length; i++) {
                            byteNumbers[i] = byteCharacters.charCodeAt(i);
                        }
                        const byteArray = new Uint8Array(byteNumbers);
                        const file = new Blob([byteArray], { type: 'application/pdf' });
                        
                        const fileURL = URL.createObjectURL(file);
                        window.open(fileURL, '_blank');
                        
                        muestraMensaje("success", 2000, "Éxito", "Reporte generado correctamente");
                    } else {
                        muestraMensaje("error", 3000, "Error", "No se recibió el PDF");
                    }
                }
                else if (lee.accion == "error") {
                    if (typeof Swal !== 'undefined') Swal.close(); 
                    muestraMensaje("error", 3000, "Error", lee.mensaje);
                }
            } catch (e) {
                if (typeof Swal !== 'undefined') Swal.close(); 
                alert("Error procesando los datos: " + e.message);
                console.error(respuesta);
            }
        },
        error: function (request, status, err) {
            if (typeof Swal !== 'undefined') Swal.close(); 
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 3000, "Error de Conexión", "Revisa la consola. Código: " + request.status);
                console.error("Detalle del error:", request.responseText);
            }
        }
    });
}