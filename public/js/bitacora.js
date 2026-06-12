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
    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "generar") {
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
    $("#generar").on("click", function () {
        limpia();
        limpia_Tablas();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar al registro que necesites.', position: 'bottom' }
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
    });
});

function eliminar(id) {
    if (window.permisos.eliminar) {
        confirmar('¿Está seguro que quiere eliminar este Usuario?', function (confirmado) {
            if (confirmado) {
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('token', $("#token").val());
                datos.append('id', id);
                enviaAjax(datos);
            }
        });
    } else {
        muestraMensaje("error", 3000, "Error", 'No tienes los permisos para eliminar un usuario.');
    }

}

function crearConsulta(datos) {
    const contenedor = $('#resultadoconsulta');
    contenedor.empty();

    if (datos.length === 0) {
        contenedor.append('<div class="listado_vacio"><p>No se encontraron registros</p></div>');
    } else {
        datos.forEach(dato => {
            var fechaPartes = dato.fecha.split('-'); // ["2025", "05", "26"]
            var fechaLocal = new Date(fechaPartes[0], fechaPartes[1] - 1, fechaPartes[2]); // Año, mes (0-index), día
            var fechaFormateada = fechaLocal.toLocaleDateString('es-ES');

            var horaFormateada = new Date('1970-01-01T' + dato.hora).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
            let registro = `
                <div class="listado_contenedor_grupal">
                    <div class="listado_item" onclick="toggleDetalles(this)">
                        <div class="listado_col_datos">
                            <div class="listado_dato_grupo">
                                <small>Usuario</small>
                                <span>${dato.nombreUsuario} ${dato.apellidoUsuario}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Cedula</small>
                                <span>${dato.cedulaUsuario}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Modulo</small>
                                <span>${dato.nombre_modulo}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Accion</small>
                                <span>${escapeHTML(dato.acciones)}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Hora y Fecha</small>
                                <span>${horaFormateada} ${fechaFormateada}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            contenedor.append(registro);
        });
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
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
            try {
                var lee = JSON.parse(respuesta);
                if (lee.accion == "consultar") {
                    crearConsulta(lee.datos);
                }
                else if (lee.accion == "eliminar") {
                    if (lee.resultado == 1) {
                        muestraMensaje("success", 2000, "Correcto", lee.mensaje);
                        consultar();
                    } else {
                        muestraMensaje("error", 2000, "Error", lee.mensaje);
                    }

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