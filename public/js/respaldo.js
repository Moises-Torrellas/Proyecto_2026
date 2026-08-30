let timerBusqueda;

$(document).ready(function () {
    inicializarPaginador();

    $('#busqueda').off('keyup').on('keyup', busqueda);

    // Botón para generar un nuevo respaldo
    $("#btn_generar").on("click", function () {
        confirmar('¿Desea crear un nuevo respaldo de la base de datos en este momento?', function (confirmado) {
            if (confirmado) {
                abrirAlertaEspara('Creando Respaldo', 'Conectando con la base de datos, por favor espere...');
                let datos = new FormData();
                datos.append('accion', 'generar');
                enviaAjaxRespaldo(datos);
            }
        });
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#titulo',
                popover: { title: 'Mantenimiento BD', description: 'Aquí puedes gestionar los respaldos de la base de datos.', position: 'bottom' }
            },
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar un respaldo por su nombre o fecha.', position: 'bottom' }
            },
            {
                element: '#btn_generar',
                popover: { title: 'Crear Punto de Restauración', description: 'Este botón te permite crear un nuevo respaldo del sistema.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child',
                popover: { title: 'Registros de Respaldo', description: 'Aquí se listarán todos los respaldos que has generado.', position: 'top' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .cbt_v',
                popover: { title: 'Restaurar', description: 'Al presionar este botón, el sistema se restaurará al punto seleccionado (Todos los datos actuales se reemplazarán).', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .cbt_r',
                popover: { title: 'Eliminar', description: 'Permite eliminar definitivamente un respaldo.', position: 'left' }
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
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de registros en pantalla.', position: 'top' }
            }
        ];
        
        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });
});

function busqueda() {
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(function () {
        let valorBusqueda = $('#busqueda').val();
        let datos = new FormData();
        datos.append('accion', 'consultar');
        datos.append('filtro', valorBusqueda);
        enviaAjaxRespaldo(datos);
    }, 500);
}

function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjaxRespaldo(datos);
}

function restaurar(archivo) {
    confirmar(`¡ADVERTENCIA CRÍTICA! ¿Está seguro que desea restaurar el sistema a la versión: ${archivo}? Se sobreescribirán todos los datos actuales.`, function (confirmado) {
        if (confirmado) {
            abrirAlertaEspara('Restaurando Sistema', 'Inyectando sentencias SQL, el sistema no responderá por unos segundos...');
            let datos = new FormData();
            datos.append('accion', 'restaurar');
            datos.append('archivo', archivo);
            enviaAjaxRespaldo(datos);
        }
    });
}

function eliminar(archivo) {
    confirmar(`¿Eliminar permanentemente el respaldo ${archivo} del servidor?`, function (confirmado) {
        if (confirmado) {
            let datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('archivo', archivo);
            enviaAjaxRespaldo(datos);
        }
    });
}

function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    contenedor.html(htmlRecibido);

    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

function enviaAjaxRespaldo(datos) {
    var token = $('meta[name="csrf-token"]').attr('content');

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
        timeout: 30000,
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
                else if (lee.accion == "generar") {
                    cerrarModal();
                    consultar();
                    muestraMensaje("success", 3000, "Éxito", lee.mensaje);
                }
                else if (lee.accion == "restaurar" || lee.accion == "eliminar") {
                    cerrarModal();
                    consultar();
                    muestraMensaje("success", 3000, "Proceso Completado", lee.mensaje);
                }
                else if (lee.accion == "error") {
                    cerrarModal();
                    muestraMensaje("error", 4000, "Error del Sistema", lee.mensaje);
                }
            } catch (e) {
                alert("Error procesando respuesta: " + e.message);
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 3000, "Tiempo de espera agotado", "El proceso está tardando demasiado.");
            } else {
                muestraMensaje("error", 3000, "Error", "Fallo de conexión con el servidor.");
            }
        }
    });
}