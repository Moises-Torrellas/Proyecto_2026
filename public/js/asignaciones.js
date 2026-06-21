let timerBusqueda;

$('#busqueda').off('keyup').on('keyup', busqueda);

function MultiConsulta() {
    let datos = new FormData();
    datos.append('accion', 'MultiConsulta');
    enviaAjax(datos);
}

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
    MultiConsulta();
    
    if (typeof Validacion === 'function') {
        Validacion("fecha_inicio", /^[0-9\b-]*$/, /^\d{4}-\d{2}-\d{2}$/, "Seleccione una fecha válida", "btn_guardar");
    }

    $('#id_atleta').select2({ placeholder: "Seleccione un atleta...", allowClear: true, dropdownParent: $('.contenedor_modal') });
    $('#id_equipamiento').select2({ placeholder: "Seleccione una pieza...", allowClear: true, dropdownParent: $('.contenedor_modal') });

    $('#btn_guardar').on('click', function () {
        let accion = $(this).data("accion");
        
        if (accion === "incluir" || accion === "modificar") {
            if (validarEnvio(accion)) {
                let textoConfirmacion = accion === "incluir" ? '¿Está seguro que quiere registrar esta asignación?' : '¿Está seguro que quiere modificar esta asignación?';
                confirmar(textoConfirmacion, function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', accion);
                        datos.append('fecha_asignacion', $('#fecha_inicio').val());
                        enviaAjax(datos);
                    }
                });
            }
        } 
        else if (accion === "generar") {
            confirmar('¿Está seguro que quiere generar un reporte?', function (confirmado) {
                if (confirmado) {
                    if (typeof abrirAlertaEspara === 'function') abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    datos.append('filtro', $('#busqueda').val());
                    enviaAjax(datos);
                }
            });
        }
    });

    $("#btn_nuevo").on("click", function () {
        limpia(); 
        $("#btn_guardar").data("accion", "incluir");
        $("#btn_guardar").text("Confirmar Préstamo");
        $("#titulo_modal").text("Registrar Asignación");
        $("#lbl_fecha").text("Fecha de Asignación");

        $('#row_atleta').show();
        $('#col_equipamiento').show();
        $('#col_fecha_fin').hide();
        $('#row_anulados').hide();
        
        let hoy = new Date().toISOString().split('T')[0];
        $('#fecha_inicio').val(hoy);
        $('#id_atleta').val(null).trigger('change');
        $('#id_equipamiento').val(null).trigger('change');
        
        abrirModal();
    });
    $("#generar").on("click", function () {
        limpia();
        $("#btn_guardar").data("accion", "generar");
        $("#btn_guardar").text("Generar PDF");
        $("#titulo_modal").text("Generar Reporte");
        $("#lbl_fecha").text("Fecha Inicio");

        // Ocultamos los campos de registro, mostramos los de reporte
        $('#row_atleta').hide();
        $('#col_equipamiento').hide();
        $('#col_fecha_fin').show();
        $('#row_anulados').show();

        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            { element: '#busqueda', popover: { title: 'Búsqueda', description: 'Aquí puedes buscar por nombre de atleta o CI.', position: 'bottom' } },
            { element: '#btn_nuevo', popover: { title: 'Nueva Asignación', description: 'Registra un préstamo de equipo.', position: 'bottom' } },
            { element: '#generar', popover: { title: 'Generar Reporte', description: 'Descarga un archivo PDF de las asignaciones.', position: 'left' } },
            { element: '#resultadoconsulta', popover: { title: 'Lista Agrupada', description: 'Haz clic en cualquier atleta para ver sus detalles.', position: 'top' } }
        ];
        if (typeof iniciarTourConPasos === 'function') iniciarTourConPasos(pasos).start();
    });
});
function validarEnvio(accion) {
    if (accion === "incluir" || accion === "modificar") {
        if ($('#id_atleta').val() == "" || $('#id_atleta').val() == null) {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar un atleta."); return false;
        }
        if ($('#id_equipamiento').val() == "" || $('#id_equipamiento').val() == null) {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar una pieza de equipamiento."); return false;
        }
        if ($('#fecha_inicio').val() == "") {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar la fecha de asignación."); return false;
        }
    }
    return true;
}

function editar(id_asignacion, id_atleta, id_equipamiento, fecha) {
    limpia();
    $("#btn_guardar").data("accion", "modificar");
    $("#btn_guardar").text("Guardar Cambios");
    $("#titulo_modal").text("Modificar Asignación");
    $("#lbl_fecha").text("Fecha de Asignación");

    // Mostramos configuración de edición
    $('#row_atleta').show();
    $('#col_equipamiento').show();
    $('#col_fecha_fin').hide();
    $('#row_anulados').hide();
    
    $("#id_asignacion").val(id_asignacion);
    $("#fecha_inicio").val(fecha);
    $("#id_atleta").val(id_atleta).trigger('change');
    
    if ($(`#id_equipamiento option[value='${id_equipamiento}']`).length === 0) {
        $("#id_equipamiento").append(new Option("Equipo Actual (Mantenido)", id_equipamiento, true, true));
    }
    $("#id_equipamiento").val(id_equipamiento).trigger('change');
    
    abrirModal();
}

function anular(id_asignacion, id_equipamiento) {
    confirmarAnulacion('¿Está seguro que quiere anular esta asignación?', function (motivo) {
        if (motivo !== false) { 
            if (motivo.trim().length < 5) {
                muestraMensaje("error", 3000, "Validación", "El motivo debe tener al menos 5 letras."); return;
            }
            var datos = new FormData();
            datos.append('accion', 'anular');
            datos.append('id_asignacion', id_asignacion);
            datos.append('id_equipamiento', id_equipamiento);
            datos.append('motivo_anulacion', motivo); 
            enviaAjax(datos);
        }
    });
}

function poblarCombos(atletas, equipos) {
    let comboAtleta = $("#id_atleta");
    let comboEquipo = $("#id_equipamiento");
    
    comboAtleta.find('option:not(:first)').remove();
    comboEquipo.find('option:not(:first)').remove();

    if (atletas && atletas.length > 0) {
        atletas.forEach(a => {
            if(a.estatus == 1) comboAtleta.append(`<option value="${a.id_atleta}">${a.nombres} ${a.apellidos} (CI: ${a.doc_identidad})</option>`);
        });
    }

    if (equipos && equipos.length > 0) {
        equipos.forEach(e => {
            comboEquipo.append(`<option value="${e.id_equipamiento}">${e.articulo}</option>`);
        });
    }

    comboAtleta.trigger('change');
    comboEquipo.trigger('change');
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
        timeout: 120000,
        success: function (respuesta) {
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta); return;
            }
            
            try {
                var lee = JSON.parse(respuesta); 

                if (lee.accion === "reporte") {
                    if (typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
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
                else if (lee.accion == "MultiConsulta") {
                    poblarCombos(lee.atletas, lee.equipos);
                } else if (lee.accion == "incluir" || lee.accion == "modificar" || lee.accion == "anular" || lee.accion == "exito") {
                    consultar();
                    MultiConsulta();
                    cerrarModal(); 
                    muestraMensaje("success", 2000, "Operación Exitosa", lee.mensaje);
                } else if (lee.accion == "error") {
                    if (typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
                    muestraMensaje("error", 3000, "Error", lee.mensaje || lee.codigo);
                }
            } catch (e) {
                console.error("Error procesando JSON", e, respuesta);
                if (typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
            }
        },
        error: function (request, status, err) {
            if (typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
            muestraMensaje("error", 2000, "Error", "ERROR: " + err);
        }
    });
}

function toggleDetalles(elemento) {
    $(elemento).next('.listado_detalle_oculto').slideToggle();
    $(elemento).find('.icono_flecha_detalle').toggleClass('rotar_flecha');
}

function limpia() {
    if($('#f')[0]) $('#f')[0].reset();
    $('.select2').val(null).trigger('change');
}