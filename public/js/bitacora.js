let offset_bitacora = 0;
let isCargarMas = false;

$('#busqueda').off('keyup').on('keyup', busqueda);
let timerBusqueda;
function consultar() {
    offset_bitacora = 0;
    isCargarMas = false;
    let datos = new FormData();
    datos.append('accion', 'consultar');
    datos.append('offset', offset_bitacora);
    enviaAjax(datos);
}
function busqueda() {
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(function () {
        offset_bitacora = 0;
        isCargarMas = false;
        let valorBusqueda = $('#busqueda').val();
        let datos = new FormData();
        datos.append('accion', 'consultar');
        datos.append('filtro', valorBusqueda);
        datos.append('offset', offset_bitacora);
        enviaAjax(datos);
    }, 500);
}
$(document).ready(function () {
    $(document).on('click', '#btn_cargar_mas', function() {
        offset_bitacora += 100;
        isCargarMas = true;
        let valorBusqueda = $('#busqueda').val();
        let datos = new FormData();
        datos.append('accion', 'consultar');
        datos.append('filtro', valorBusqueda);
        datos.append('offset', offset_bitacora);
        enviaAjax(datos);
    });

    cargarUsuarios();
    cargarModulos();

    $('#filtro_usuario').select2({
        placeholder: "Todos los Usuarios",
        allowClear: true,
        dropdownParent: $('#contenedor_modal'),
    });

    $('#filtro_modulo').select2({
        placeholder: "Todos los Módulos",
        allowClear: true,
        dropdownParent: $('#contenedor_modal'),
    });

    inicializarPaginador();
    $('#proceso').on('click', function () {
        let accion = $(this).data("accion");
        if (accion == "generar") {
            opcionesReporte(function(formato) {
                abrirAlertaEspara('Se esta generando el reporte', 'Espere un momento');
                let datos = new FormData($('#f')[0]);
                datos.append('accion', 'generar');
                datos.append('formato', formato);
                enviaAjax(datos);
            });
        }
    });
    
    $("#generar").on("click", function () {
        limpia();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Filtros del Reporte de Bitácora");
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
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF o Excel.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child',
                popover: { title: 'Registros', description: 'Aqui se mostraran todos los registros.', position: 'top' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .icono_flecha_detalle',
                popover: { title: 'Detalles', description: 'Si pulsas aqui podras ver los detalles de la acción en la bitácora, incluyendo datos previos y nuevos.', position: 'left' },
                onNext: () => {
                    const el = document.querySelector('#resultadoconsulta .listado_contenedor_grupal:first-child .listado_item');
                    if(el) {
                        el.click(); // Abre el detalle
                    }
                }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .listado_detalle_oculto',
                popover: { title: 'Información del Registro', description: 'Acá podras ver que campos fueron alterados por la acción del usuario.', position: 'top' },
                onPrevious: () => {
                    const el = document.querySelector('#resultadoconsulta .listado_contenedor_grupal:first-child .listado_item');
                    if(el && document.querySelector('#resultadoconsulta .listado_contenedor_grupal:first-child .listado_detalle_oculto').style.display === 'block') {
                        el.click(); // Cierra el detalle si retrocede
                    }
                },
                onNext: () => {
                    const el = document.querySelector('#resultadoconsulta .listado_contenedor_grupal:first-child .listado_item');
                    if(el && document.querySelector('#resultadoconsulta .listado_contenedor_grupal:first-child .listado_detalle_oculto').style.display === 'block') {
                        el.click(); // Cierra el detalle para continuar
                    }
                }
            },
            {
                element: '#btn_cargar_mas',
                popover: { title: 'Cargar Más', description: 'Si hay muchos registros, este botón te permitirá cargar los siguientes 100 registros de la bitácora.', position: 'top' },
                onPrevious: () => {
                    const el = document.querySelector('#resultadoconsulta .listado_contenedor_grupal:first-child .listado_item');
                    if(el && document.querySelector('#resultadoconsulta .listado_contenedor_grupal:first-child .listado_detalle_oculto').style.display !== 'block') {
                        el.click(); // Vuelve a abrir si retrocede
                    }
                }
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
            },
        ];

        // Iniciar tour
        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });
});



function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    if (isCargarMas) {
        contenedor.append(htmlRecibido);
    } else {
        contenedor.html(htmlRecibido);
    }

    let divMas = $('#tiene_mas_datos').last();
    if (divMas.length > 0) {
        let count = parseInt(divMas.attr('data-count'));
        if (count === 100) {
            $('#btn_cargar_mas').css('display', 'inline-block');
        } else {
            $('#btn_cargar_mas').css('display', 'none');
        }
        divMas.remove();
    } else {
        $('#btn_cargar_mas').css('display', 'none');
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
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

function cargarUsuarios() {
    let datos = new FormData();
    datos.append('accion', 'consultar_usuarios');
    enviaAjax(datos);
}

function cargarModulos() {
    let datos = new FormData();
    datos.append('accion', 'consultar_modulos');
    enviaAjax(datos);
}

function construirSelectUsuario(datos) {
    var select = $('#filtro_usuario');
    select.empty();
    select.append('<option value="">Todos los Usuarios</option>');
    datos.forEach(dato => {
        var linea = `<option value="${dato.idUsuario}">${escapeHTML(dato.cedulaUsuario)} - ${escapeHTML(dato.nombreUsuario)} ${escapeHTML(dato.apellidoUsuario)}</option>`;
        select.append(linea);
    });
    select.trigger('change');
}

function construirSelectModulo(datos) {
    var select = $('#filtro_modulo');
    select.empty();
    select.append('<option value="">Todos los Módulos</option>');
    datos.forEach(dato => {
        var linea = `<option value="${dato.id_modulo}">${escapeHTML(dato.nombre_modulo)}</option>`;
        select.append(linea);
    });
    select.trigger('change');
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
                else if (lee.accion == "consultar_usuarios") {
                    construirSelectUsuario(lee.datos);
                }
                else if (lee.accion == "consultar_modulos") {
                    construirSelectModulo(lee.datos);
                }
                else if (lee.accion == "reporte") {
                    cerrarAlertaEspara();
                    muestraMensaje("success", 2000, "Creado Exitosamente", 'Se ha generado el reporte');
                    setTimeout(function () {
                        window.open(lee.archivo, '_blank');
                    }, 2000);
                    cerrarModal();
                    limpia();
                }
                else if (lee.accion == "eliminar") {
                    if (lee.resultado == 1) {
                        muestraMensaje("success", 2000, "Eliminacion Exitosa", lee.mensaje);
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