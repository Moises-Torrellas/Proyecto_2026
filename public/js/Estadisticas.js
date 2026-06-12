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
    MultiConsulta();
    const $inputs = $('#goles, #asistencias, #penalizaciones, #goles_c, #partido, #average');

    // 1. Establecer el valor inicial en 0 si están vacíos al cargar
    $inputs.each(function () {
        if ($(this).val() === "") {
            $(this).val("0");
        }
    });

    // 2. Al hacer clic o seleccionar el input (focus)
    $inputs.on('focus', function () {
        if ($(this).val() === "0") {
            $(this).val("");
        }
    });

    // 3. Al salir del input (blur)
    $inputs.on('blur', function () {
        // Usamos $.trim() para ignorar si el usuario solo dejó espacios en blanco
        if ($.trim($(this).val()) === "") {
            $(this).val("0");
        }
    });

    inicializarPaginador();

    Validacion("goles", /^[0-9]*$/, /^[0-9]{1,3}$/, "Ingrese una cantidad válida (0-999)", "proceso");
    Validacion("asistencias", /^[0-9]*$/, /^[0-9]{1,3}$/, "Ingrese una cantidad válida (0-999)", "proceso");
    Validacion("penalizaciones", /^[0-9]*$/, /^[0-9]{1,3}$/, "Ingrese una cantidad válida (0-999)", "proceso");
    Validacion("goles_c", /^[0-9]*$/, /^[0-9]{1,3}$/, "Ingrese una cantidad válida (0-999)", "proceso");
    Validacion("partido", /^[0-9]*$/, /^[0-9]{1,3}$/, "Debe ser al menos 1 partido", "proceso");
    Validacion("average", /^[0-9]*\.?[0-9]{0,2}$/, /^[0-9]+(\.[0-9]{1,2})?$/, "Formato decimal inválido (ej: 1.50)", "proceso");

    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar estas Estadisticas?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar estas Estadisticas?', function (confirmado) {
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
    $('#torneo').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });
    $('#atleta').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });

    $("#incluir").on("click", function () {
        limpia();
        $('#goles, #asistencias, #penalizaciones, #goles_c, #partido, #average').val("0");
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Estadisticas");
        $("#titulo_modal").text("Registrar Estadisticas");
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();

        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar las estadisticas de los atletas que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevas Estadisticas', description: 'Si pulsa aqui se abrira un modal para ingresar un nuevas Estadisticas', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Estadisticas Registrados', description: 'Aqui se mostraran todos las estadisticas agrupadas por atletas.', position: 'top' }
            },
            {
                element: '#rowsPerPage',
                popover: { title: 'Registros Deseados', description: 'Aqui podra seleccionar la cantidad de registros que quiere que se muestren.', position: 'top' }
            },
            {
                element: '#botonera',
                popover: { title: 'Cambiar de Pagina', description: 'Botones para cambiar de página.', position: 'top' }
            },
        ];

        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });

});

function validarEnvio(proceso) {
    // 1. Validación de Selects
    if ($('#torneo').val() === null || $('#torneo').val() === "") {
        muestraMensaje("error", 2000, "Error", "Debe seleccionar un torneo");
        return false;
    }
    if ($('#atleta').val() === null || $('#atleta').val() === "") {
        muestraMensaje("error", 2000, "Error", "Debe seleccionar un atleta");
        return false;
    }
console.log("Valor de goles:", $('#goles').val());
    if (validarkeyup(/^[0-9]{1,3}$/, $('#goles'), $("#goles_spam"), "Cantidad no válida (0-999)", true)) {
        muestraMensaje("error", 2000, "Error", "Campo 'Goles' inválido");
        return false;
    }
    if (validarkeyup(/^[0-9]{1,3}$/, $('#asistencias'), $("#asistencias_spam"), "Cantidad no válida (0-999)", true)) {
        muestraMensaje("error", 2000, "Error", "Campo 'Asistencias' inválido");
        return false;
    }
    if (validarkeyup(/^[0-9]{1,3}$/, $('#penalizaciones'), $("#penalizaciones_spam"), "Cantidad no válida (0-999)", true)) {
        muestraMensaje("error", 2000, "Error", "Campo 'Penalizaciones' inválido");
        return false;
    }
    if (validarkeyup(/^[0-9]{1,3}$/, $('#goles_c'), $("#goles_c_spam"), "Cantidad no válida (0-999)", true)) {
        muestraMensaje("error", 2000, "Error", "Campo 'Goles en contra' inválido");
        return false;
    }
    if (validarkeyup(/^[1-9]{1}[0-9]{0,2}$/, $('#partido'), $("#partido_spam"), "Debe ser al menos 1 partido", true)) {
        muestraMensaje("error", 2000, "Error", "Campo 'Partidos Jugados' inválido");
        return false;
    }

    return true; // Si todo es correcto
}

function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}
function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar estas Estadisticas?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
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
        let atributosExtra = ""; // Variable para guardar los límites de edad

        if (idSelect === 'torneo' && campo1 && campo2) {
            textoMostrar = `${escapeHTML(dato[campo1])} - ${escapeHTML(dato[campo2])}`;
        }
        else if (idSelect === 'atleta' && campo1 && campo2 && campo3) {
            textoMostrar = `${escapeHTML(dato[campo1])} ${escapeHTML(dato[campo2])} - ${escapeHTML(dato[campo3])}`;
        }
        else {
            textoMostrar = escapeHTML(String(dato[campo1]));
        }

        // Se agregan los atributosExtra a la etiqueta <option>
        var linea = `<option value="${dato[campoId]}" ${atributosExtra}>${textoMostrar}</option>`;
        select.append(linea);
    });
}

function modificar(datos) {
    // 1. Configurar la acción del botón de procesamiento y los títulos del modal
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Estadística");
    $("#titulo_modal").text("Modificar Estadística");

    // 2. Asegurar la visibilidad de todas las columnas del formulario
    $('#torneo').closest('.colum').show();
    $('#atleta').closest('.colum').show();
    $('#goles').closest('.colum').show();
    $('#asistencias').closest('.colum').show();
    $('#penalizaciones').closest('.colum').show();
    $('#goles_c').closest('.colum').show();
    $('#partido').closest('.colum').show();
    $('#average').closest('.colum').show();

    // 3. Asignar los valores devueltos por el método Buscar() a cada input/select
    $('#id').val(datos[0].id_estadisticas);
    $('#torneo').val(datos[0].id_torneo);
    $('#atleta').val(datos[0].id_atleta);
    $('#goles').val(datos[0].goles);
    $('#asistencias').val(datos[0].asistencias);
    $('#penalizaciones').val(datos[0].penalizaciones);
    $('#goles_c').val(datos[0].goles_contra);      // Mapea con 'goles_contra' del SELECT
    $('#partido').val(datos[0].partidos_jugados);   // Mapea con 'partidos_jugados' del SELECT
    $('#average').val(datos[0].average);

    $('#torneo').trigger('change');
    $('#atleta').trigger('change');
    abrirModal();
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
                if (lee.accion == "MultiConsulta") {
                    construirSelect('torneo', lee.torneos, 'id_torneo', 'nombre', 'fecha_inicio');
                    construirSelect('atleta', lee.atletas, 'id_atleta', 'nombres', 'apellidos', 'doc_identidad');
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
                    // 1. Cerramos la alerta de espera de inmediato
                    cerrarAlertaEspara();

                    // 2. Cerramos el modal del formulario
                    cerrarModal();

                    // 3. Mostramos el mensaje de éxito (dura 2000ms en pantalla)
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
