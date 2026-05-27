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

    // El buscador se mantiene seguro dentro del ready
    $('#busqueda').off('keyup').on('keyup', busqueda);

    // Validación de Nombre del Premio
    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");

    // Botón centralizado de procesos
    $('#proceso').on('click', function () {
        var accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este premio?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar este premio?', function (confirmado) {
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
                    abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    enviaAjax(datos);
                }
            });
        }
    });

    // Evento para abrir el modal en modo inclusión
    $("#incluir").on("click", function () {
        limpia();
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Premio");
        $("#titulo_modal").text("Registrar Premio");
        
        // Garantizamos visibilidad de los campos en el DOM
        $('#nombre').closest('.colum').show();
        $('#tipo').closest('.colum').show();
        
        // Empezamos sin opción válida seleccionada para forzar al usuario a elegir una
        $('#tipo').val("").trigger('change'); 
        
        abrirModal();
    });

    // Evento para abrir el modal en modo reporte
    $("#generar").on("click", function () {
        limpia();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        
        // Garantizamos visibilidad para permitir reportes filtrados opcionales
        $('#nombre').closest('.colum').show();
        $('#tipo').closest('.colum').show();
        
        // Por defecto empieza en la opción "Todos" (valor vacío)
        $('#tipo').val("").trigger('change'); 
        
        abrirModal();
    });

    // Tour guiado interactivo
    $('#ayuda').on('click', function () {
        const pasos = [
            { element: '#busqueda', popover: { title: 'Barra de Búsqueda', description: 'Aquí puedes buscar el premio por su nombre o tipo.', position: 'bottom' } },
            { element: '#incluir', popover: { title: 'Nuevo Premio', description: 'Abre el formulario para registrar un nuevo premio.', position: 'bottom' } },
            { element: '#generar', popover: { title: 'Generar Reportes', description: 'Abre el modal para exportar el listado de premios a PDF.', position: 'left' } },
            { element: '#resultadoconsulta', popover: { title: 'Premios Registrados', description: 'Aquí se desplegará el palmarés cargado.', position: 'top' } },
            { element: '#cbt_v', popover: { title: 'Modificar Premios', description: 'Pulsando este botón podrás editar la información.', position: 'left' } },
            { element: '#cbt_r', popover: { title: 'Eliminar Premio', description: 'Quita el premio seleccionado del sistema.', position: 'left' } },
            { element: '#rowsPerPage', popover: { title: 'Registros Deseados', description: 'Configura la cantidad de filas visibles por tabla.', position: 'top' } },
            { element: '#botonera', popover: { title: 'Cambiar de Página', description: 'Navega a través de las páginas del listado.', position: 'top' } },
            { element: '#cantidad', popover: { title: 'Cantidad Total', description: 'Muestra la métrica total de premios cargados.', position: 'top' } },
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
    confirmar('¿Está seguro que quiere eliminar este premio?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}

function validarEnvio(proceso) {
    if (proceso === "generar") {
        return true; // Al generar reportes, un valor vacío significa "Todos"
    }

    if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, $("#nombre"), $("#nombre_spam"), "Solo letras entre 3 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un nombre válido");
        return false;
    }
    
    // Al incluir o modificar, obligatoriamente debe ser 'I' o 'G'
    var valorTipo = $('#tipo').val();
    if (valorTipo !== "I" && valorTipo !== "G") {
        muestraMensaje("error", 2000, "Error", "Tiene que elegir una opción válida para el tipo de premio");
        return false;
    }
    
    return true;
}

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Premio");
    $("#titulo_modal").text("Modificar Premio");

    // Aseguramos que los campos estén visibles si se llamó previamente al botón generar
    $('#nombre').closest('.colum').show();
    $('#tipo').closest('.colum').show();

    $('#id').val(datos[0].id_premio);
    $('#nombre').val(datos[0].nombre);
    
    // Forzamos mayúsculas para emparejar la selección con las opciones del HTML
    if (datos[0].tipo) {
        $('#tipo').val(datos[0].tipo.trim().toUpperCase()).trigger('change');
    }

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
        timeout: 10000,
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
                } else if (lee.accion == "eliminar") {
                    consultar();
                    muestraMensaje("success", 2000, "Eliminación Exitosa", lee.mensaje);
                } else if (lee.accion == "modificar") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Modificación Exitosa", lee.mensaje);
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
        complete: function () { }
    });
}