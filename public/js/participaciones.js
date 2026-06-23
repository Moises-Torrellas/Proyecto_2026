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

function MultiConsulta() {
    let datos = new FormData();
    datos.append('accion', 'MultiConsulta');
    enviaAjax(datos);
}

$(document).ready(function () {
    inicializarPaginador();
    MultiConsulta();

    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar esta participacion?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar esta participacion?', function (confirmado) {
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

    // Ajustado para codigo_torneo y codigo_equipo
    $('#codigo_torneo').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });
    $('#codigo_equipo').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });

    $("#incluir").on("click", function () {
        limpia();

        // Limpiar el campo oculto
        $('#codigo_participacion').val("");

        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Participacion");
        $("#titulo_modal").text("Registrar Participacion");
        $('#codigo_torneo').trigger('change'); // Ajustado
        $('#codigo_equipo').trigger('change'); // Ajustado
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();

        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        $('#codigo_torneo').trigger('change'); // Ajustado
        $('#codigo_equipo').trigger('change'); // Ajustado
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar al representante que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nueva Participacion', description: 'Si pulsa aqui se abrira un modal para ingresar una nueva participacion', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Posiciones Registradas', description: 'Aqui se mostraran todos las posiciones registradas.', position: 'top' }
            },
            {
                element: '#cbt_v',
                popover: { title: 'Modificar Participacion', description: 'Si pulsa aqui se abrira un modal para modificar la participacion seleccionada.', position: 'left' }
            },
            {
                element: '#cbt_r',
                popover: { title: 'Eliminar Participacion', description: 'Si pulsa aqui eliminara la participacion seleccionada.', position: 'left' }
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
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de posiciones cargados.', position: 'top' }
            },
        ];

        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });

});

function validarEnvio(proceso) {
    // 1. Validación de Selects (Ajustados)
    if ($('#codigo_torneo').val() === null || $('#codigo_torneo').val() === "") {
        muestraMensaje("error", 2000, "Error", "Debe seleccionar un torneo");
        return false;
    }
    if ($('#codigo_equipo').val() === null || $('#codigo_equipo').val() === "") {
        muestraMensaje("error", 2000, "Error", "Debe seleccionar un equipo");
        return false;
    }

    return true;
}

// Recibe codigo_participacion
function buscar(codigo_participacion) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('codigo_participacion', codigo_participacion);
    enviaAjax(datos);
}

// Recibe codigo_participacion
function eliminar(codigo_participacion) {
    confirmar('¿Está seguro que quiere eliminar esta participacion?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('codigo_participacion', codigo_participacion); // Ajustado
            enviaAjax(datos);
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

function construirSelect(idSelect, datos, campoId, campo1, campo2 = null, campo3 = null, campo4 = null) {
    var select = $('#' + idSelect);
    select.empty();
    select.append('<option value="" selected disabled>Seleccione una opción</option>');

    datos.forEach(dato => {
        let textoMostrar = "";
        let atributosExtra = "";

        if (idSelect === 'codigo_torneo' && campo1 && campo2) { // Ajustado a codigo_torneo
            textoMostrar = `${escapeHTML(dato[campo1])} - ${escapeHTML(dato[campo2])}`;
        }
        else if (idSelect === 'codigo_equipo') { // Ajustado a codigo_equipo
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

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Estadística");
    $("#titulo_modal").text("Modificar Participacion");
    
    // ** CORRECCIÓN: Asignamos el valor de la base de datos al input hidden para que funcione el UPDATE **
    $('#codigo_participacion').val(datos[0].codigo_participacion); 
    
    $('#codigo_torneo').val(datos[0].codigo_torneo).trigger('change'); // Ajustado
    $('#codigo_equipo').val(datos[0].codigo_equipo).trigger('change'); // Ajustado
    abrirModal();
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
                if (lee.accion == "MultiConsulta") {
                    construirSelect('codigo_torneo', lee.torneo, 'codigo_torneo', 'nombre', 'fecha_inicio');
                    construirSelect('codigo_equipo', lee.equipo, 'codigo_equipo', 'nombre');
                } else if (lee.accion == "incluir") {
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