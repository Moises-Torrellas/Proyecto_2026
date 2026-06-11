let timerBusqueda;

// Escuchador para la barra de búsqueda en tiempo real
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
    MultiConsulta();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();

    if (typeof Validacion === 'function') {
        Validacion("fecha_asignacion", /^[0-9\b-]*$/, /^\d{4}-\d{2}-\d{2}$/, "Seleccione una fecha válida", "btn_guardar");
    }

    $('#id_atleta').select2({
        placeholder: "Seleccione un atleta...",
        allowClear: true,
        dropdownParent: $('.contenedor_modal')
    });
    
    $('#id_equipamiento').select2({
        placeholder: "Seleccione una pieza...",
        allowClear: true,
        dropdownParent: $('.contenedor_modal')
    });

    $('#btn_guardar').on('click', function () {
        let accion = $(this).data("accion");
        
        if (validarEnvio(accion)) {
            let textoConfirmacion = accion === "incluir" 
                ? '¿Está seguro que quiere registrar esta asignación?' 
                : '¿Está seguro que quiere modificar esta asignación?';

            confirmar(textoConfirmacion, function (confirmado) {
                if (confirmado) {
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', accion);
                    enviaAjax(datos);
                }
            });
        }
    });

    // Abrir Modal de Nueva Asignación
    $("#btn_nuevo").on("click", function () {
        limpia(); 
        $("#f")[0].reset();
        $("#id_asignacion").val('');
        $("#titulo_modal").text("Registrar Asignación");
        $("#btn_guardar").text("Registrar Asignación").data("accion", "incluir");
        
        // Forzar fecha del sistema
        let hoy = new Date().toISOString().split('T')[0];
        $('#fecha_asignacion').val(hoy);
        
        $('#id_atleta').val(null).trigger('change');
        $('#id_equipamiento').val(null).trigger('change');
        
        abrirModal(); 
    });

    // Tour Guiado de Ayuda
    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Búsqueda', description: 'Aquí puedes buscar por nombre de atleta o CI.', position: 'bottom' }
            },
            {
                element: '#btn_nuevo',
                popover: { title: 'Nueva Asignación', description: 'Abre el formulario para registrar un nuevo préstamo de equipo.', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reporte', description: 'Descarga un archivo PDF con el historial de asignaciones.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Lista Agrupada', description: 'Haz clic en cualquier atleta para desplegar el detalle de sus equipos asignados.', position: 'top' }
            }
        ];
        if (typeof iniciarTourConPasos === 'function') {
            const driver = iniciarTourConPasos(pasos);
            driver.start();
        }
    });
});

function validarEnvio(accion) {
    if (accion === "incluir" || accion === "modificar") {
        if ($('#id_atleta').val() == "" || $('#id_atleta').val() == null) {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar un atleta.");
            return false;
        }
        if ($('#id_equipamiento').val() == "" || $('#id_equipamiento').val() == null) {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar una pieza de equipamiento.");
            return false;
        }
        if ($('#fecha_asignacion').val() == "") {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar la fecha de asignación.");
            return false;
        } else {
            let fechaIngresada = $('#fecha_asignacion').val();
            let hoy = new Date();
            let mes = (hoy.getMonth() + 1).toString().padStart(2, '0');
            let dia = hoy.getDate().toString().padStart(2, '0');
            let fechaActualStr = hoy.getFullYear() + '-' + mes + '-' + dia;
            
            if (fechaIngresada > fechaActualStr) {
                muestraMensaje("error", 2000, "Error", "La fecha de asignación no puede ser futura.");
                return false;
            }
        }
    }
    return true;
}

function editar(id_asignacion, id_atleta, id_equipamiento, fecha) {
    limpia();
    $("#f")[0].reset();
    $("#titulo_modal").text("Modificar Asignación");
    $("#btn_guardar").text("Guardar Cambios").data("accion", "modificar");
    
    $("#id_asignacion").val(id_asignacion);
    $("#fecha_asignacion").val(fecha);
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
            // 1. Verificamos si es un HTML puro para la tabla (sin intentar parsearlo como JSON)
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta);
                return;
            }
            
            try {
                var lee;
                if (typeof respuesta === 'object') {
                    lee = respuesta;
                } else {
                    let textoLimpio = respuesta.substring(respuesta.indexOf('{'));
                    lee = JSON.parse(textoLimpio); 
                }

                if (lee.accion == "MultiConsulta") {
                    poblarCombos(lee.atletas, lee.equipos);
                } else if (lee.accion == "incluir" || lee.accion == "modificar" || lee.accion == "exito") {
                    consultar();
                    MultiConsulta();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Operación Exitosa", lee.mensaje);
                } else if (lee.accion == "error") {
                    muestraMensaje("error", 2000, "Error", lee.mensaje || lee.codigo);
                }
            } catch (e) {
                console.error("Error procesando JSON", e, respuesta);
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Error de Comunicación', 
                    text: 'El servidor respondió de forma inesperada. Revisa la consola.' 
                });
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: " + err);
            }
        }
    });
}