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
            opcionesReporte(function(formato) {
                abrirAlertaEspara('Se esta generando el reporte', 'Espere un momento');
                var datos = new FormData($('#f')[0]);
                datos.append('accion', 'generar');
                datos.append('formato', formato);
                enviaAjax(datos);
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
        $('#nombre').closest('.colum').show();
        $('#ubicacion').closest('.colum').show();
        
        let localDate = new Date();
        localDate.setMinutes(localDate.getMinutes() - localDate.getTimezoneOffset());
        let hoy = localDate.toISOString().split('T')[0];
        
        let fpInicio = document.querySelector("#fecha_inicio")._flatpickr;
        if (fpInicio) { fpInicio.setDate(hoy); } else { $('#fecha_inicio').val(hoy); }
        
        let fpFin = document.querySelector("#fecha_fin")._flatpickr;
        if (fpFin) { fpFin.setDate(hoy); } else { $('#fecha_fin').val(hoy); }

        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        $('#nombre').closest('.colum').hide();
        $('#ubicacion').closest('.colum').hide();
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Búsqueda', description: 'Aquí puedes buscar el torneo que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevo Torneo', description: 'Si pulsa aquí se abrirá un modal para registrar un nuevo torneo.', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aquí se abrirá un modal para generar un reporte en PDF o EXCEL.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Torneos Registrados', description: 'Aquí se mostrarán todos los torneos registrados.', position: 'top' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child',
                popover: { title: 'Registro de Torneo', description: 'Este es un registro individual de un torneo. Aquí puedes ver sus detalles y acciones.', position: 'top' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .listado_col_acciones button:nth-of-type(1)',
                popover: { title: 'Modificar Torneo', description: 'Si pulsa aquí se abrirá un modal para modificar este torneo.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .listado_col_acciones button:nth-of-type(2)',
                popover: { title: 'Eliminar Torneo', description: 'Si pulsa aquí eliminará este torneo.', position: 'left' }
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
    
    $('#nombre').closest('.colum').show();
    $('#ubicacion').closest('.colum').show();
    
    // Llenamos el formulario con los datos de la BD ajustado a codigo_torneo
    $('#codigo_torneo').val(datos[0].codigo_torneo);
    $('#nombre').val(datos[0].nombre);
    let fInicio = datos[0].fecha_inicio ? datos[0].fecha_inicio.split(' ')[0] : '';
    let fFin = datos[0].fecha_fin ? datos[0].fecha_fin.split(' ')[0] : '';
    
    let fpInicio = document.querySelector("#fecha_inicio")._flatpickr;
    if (fpInicio) { fpInicio.setDate(fInicio); } else { $('#fecha_inicio').val(fInicio); }
    
    let fpFin = document.querySelector("#fecha_fin")._flatpickr;
    if (fpFin) { fpFin.setDate(fFin); } else { $('#fecha_fin').val(fFin); }
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
                else if (lee.accion == "reporte") {
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