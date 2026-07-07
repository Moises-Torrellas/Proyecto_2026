let timerBusqueda;

// --- LÓGICA DE BÚSQUEDA AJAX ---
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

// --- RENDERIZADO DEL DOM ---
function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    contenedor.html(htmlRecibido);

    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
    
    if (typeof inicializarPaginador === 'function') {
        inicializarPaginador();
    }
}

$(document).ready(function () {
    if (typeof inicializarPaginador === 'function') {
        inicializarPaginador();
    }
    MultiConsulta();

    $('#busqueda').off('keyup').on('keyup', busqueda);

    $("#ayuda").on("click", function() {
        if(typeof iniciarAyuda === 'function') {
            iniciarAyuda('devoluciones'); 
        }
    });

    // --- ACCIONES DE MODALES ---
    $("#btn_nuevo").on("click", function () {
        $("#f")[0].reset();
        $("#id_devolucion").val('');
        $("#titulo_modal").text("Registrar Devolución");
        $("#btn_guardar").text("Confirmar").attr("data-accion", "incluir");
        $('#fecha_devolucion').val(new Date().toISOString().split('T')[0]);
        
        $('#id_asignacion').closest('.colum').show();
        $('#id_estado').closest('.colum').show();
        $('#observacion').closest('.colum').show();
        $('#fecha_devolucion').closest('.colum').show();

        // Ocultar asignaciones que ya fueron devueltas o anuladas (estatus diferente de 1)
        $("#id_asignacion option").each(function() {
            let estatus = $(this).attr("data-estatus");
            let valor = $(this).val();

            if (valor === "") {
                $(this).prop("disabled", false).show();
            } else if (estatus != "1") {
                $(this).prop("disabled", true).hide();
            } else {
                $(this).prop("disabled", false).show();
            }
        });

        $('#id_asignacion').val("").trigger('change');
        $('#id_estado').val("").trigger('change');

        abrirModal(); 
    });

    $("#generar").on("click", function () {
        $("#f")[0].reset();
        $("#id_devolucion").val('');
        $("#titulo_modal").text("Filtros del Reporte");
        $("#btn_guardar").text("Generar PDF").attr("data-accion", "generar");

        $('#id_asignacion').closest('.colum').show();
        $('#id_estado').closest('.colum').show();
        $('#fecha_devolucion').closest('.colum').show();
        $('#observacion').closest('.colum').hide();
        
        // Habilitar y mostrar todo el universo de asignaciones para permitir la selección de devoluciones históricas
        $("#id_asignacion option").each(function() {
            $(this).prop("disabled", false).show();
        });

        $('#id_asignacion').val("").trigger('change');
        $('#id_estado').val("").trigger('change');
        $('#fecha_devolucion').val('');

        abrirModal();
    });

    $('#btn_guardar').on('click', function () {
        let accion = $(this).attr("data-accion");
        
        if (accion === "incluir" || accion === "modificar") {
            if ($('#id_asignacion').val() === "" || $('#id_estado').val() === "" || $('#fecha_devolucion').val() === "") {
                muestraMensaje("error", 2000, "Validación", "Complete los campos obligatorios.");
                return false;
            }
        }
        
        let datos = new FormData($('#f')[0]);
        datos.append('accion', accion);
        
        enviaAjax(datos);
    });
});

// --- FUNCIONES CORE ---
function enviaAjax(datos) {
    $.ajax({
        async: true,
        url: "",
        type: "POST",
        contentType: false,
        data: datos,
        processData: false,
        cache: false,
        beforeSend: function (request) { request.setRequestHeader("X-CSRF-TOKEN", $('meta[name="csrf-token"]').attr('content')); },
        timeout: 10000,
        success: function (respuesta) {
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta);
                return;
            }
            try {
                var lee = JSON.parse(respuesta);
                if (lee.accion == "MultiConsulta") {
                    poblarCombos(lee.asignaciones, lee.estados);
                } else if (lee.accion == "exito") {
                    consultar();
                    MultiConsulta();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Operación Exitosa", "Procesado correctamente.");
                } else if (lee.accion == "reporte") {
                    cerrarModal();
                    muestraMensaje("success", 1000, "Éxito", 'Se ha generado el reporte');
                    setTimeout(function () {
                        window.open(lee.archivo, '_blank');
                    }, 1000);
                } else if (lee.accion == "error") {
                    muestraMensaje("error", 2500, "Alerta", lee.mensaje || "Código de error: " + lee.codigo);
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Respuesta inválida del servidor.' });
            }
        },
        error: function (request, status, err) {
            muestraMensaje("error", 2000, "Error de Red", "Falló la comunicación con el servidor.");
        }
    });
}

function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}

// Re-ejecuta de manera asíncrona la carga de catálogos y estados
function MultiConsulta() {
    let datos = new FormData();
    datos.append('accion', 'MultiConsulta');
    enviaAjax(datos);
}

function editar(id_devolucion, id_asignacion, id_estado, fecha, observacion) {
    $("#f")[0].reset();
    $("#titulo_modal").text("Modificar Devolución");
    $("#btn_guardar").text("Guardar Cambios").attr("data-accion", "modificar");

    $('#id_asignacion').closest('.colum').show();
    $('#id_estado').closest('.colum').show();
    $('#observacion').closest('.colum').show();

    $("#id_devolucion").val(id_devolucion);
    $("#fecha_devolucion").val(fecha);
    $("#observacion").val(observacion);
    
    $("#id_asignacion option").each(function() {
        $(this).prop("disabled", false).show();
    });
    
    $("#id_asignacion").val(id_asignacion).trigger('change');
    $("#id_estado").val(id_estado).trigger('change');
    
    abrirModal();
}

function anular(id_devolucion) {
    if(typeof confirmarAnulacion === 'function') {
        confirmarAnulacion('¿Está seguro que quiere anular esta devolución?', function (motivo) {
            if (motivo !== false) {
                let datos = new FormData();
                datos.append('accion', 'anular');
                datos.append('id_devolucion', id_devolucion);
                datos.append('motivo_anulacion', motivo); 
                enviaAjax(datos);
            }
        });
    }
}

function poblarCombos(asignaciones, estados) {
    let comboAsignacion = $("#id_asignacion");
    let comboEstado = $("#id_estado");
    
    comboAsignacion.find('option:not(:first)').remove();
    comboEstado.find('option:not(:first)').remove();

    if (asignaciones && asignaciones.length > 0) {
        asignaciones.forEach(a => {
            let nomAtleta = a.atleta || '';
            let nomArticulo = a.articulo || '';
            let textoVisible = `${nomArticulo} (${nomAtleta})`;
            
            comboAsignacion.append(`<option value="${a.id_asignacion}" data-estatus="${a.estatus_asignacion}">${textoVisible}</option>`);
        });
    }

    if (estados && estados.length > 0) {
        estados.forEach(e => {
            comboEstado.append(`<option value="${e.id_estado}">${e.nombre}</option>`);
        });
    }

    comboAsignacion.trigger('change');
    comboEstado.trigger('change');
}