$(document).ready(function () {
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    MultiConsulta();

    $("#btn_nuevo").on("click", function () {
        $("#f")[0].reset();
        $("#id_devolucion").val('');
        $("#titulo_modal").text("Registrar Devolución");
        $("#btn_guardar").text("Confirmar Devolución").data("accion", "incluir");
        $('#fecha_devolucion').val(new Date().toISOString().split('T')[0]);
        
        $('#id_asignacion').closest('.colum').show();
        $('#id_estado').closest('.colum').show();
        $('#observacion').closest('.colum').show();
        $('#fecha_devolucion').closest('.colum').show();

        $('#id_asignacion').val("").trigger('change');
        $('#id_estado').val("").trigger('change');

        abrirModal(); 
    });

    // Evento para abrir el modal en modo reporte
    $("#generar").on("click", function () {
        $("#f")[0].reset();
        $("#titulo_modal").text("Generar Reporte");
        $("#btn_guardar").text("Generar Reporte").data("accion", "generar");
        
        // Mostramos todos los campos para que sirvan de filtros
        $('#id_asignacion').closest('.colum').show();
        $('#id_estado').closest('.colum').show();
        $('#fecha_devolucion').closest('.colum').show();
        
        // La observación normalmente no se usa para filtrar reportes, 
        // pero la mostramos para mantener el diseño del formulario completo
        $('#observacion').closest('.colum').show();

        // Reseteamos los selectores
        $('#id_asignacion').val("").trigger('change');
        $('#id_estado').val("").trigger('change');
        $('#fecha_devolucion').val('');

        abrirModal();
    });

    $('#btn_guardar').on('click', function () {
        let accion = $(this).data("accion");
        
        if (accion === "incluir" || accion === "modificar") {
            if ($('#id_asignacion').val() === "" || $('#id_estado').val() === "") {
                muestraMensaje("error", 2000, "Validación", "Debe seleccionar una asignación y un estado.");
                return false;
            }
            let datos = new FormData($('#f')[0]);
            datos.append('accion', accion);
            enviaAjax(datos);
            
        } else if (accion === "generar") {
            if (typeof confirmar === 'function') {
                confirmar('¿Está seguro que quiere generar un reporte?', function (confirmado) {
                    if (confirmado) {
                        if (typeof abrirAlertaEspara === 'function') abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'generar');
                        enviaAjax(datos);
                    }
                });
            } else {
                var datos = new FormData($('#f')[0]);
                datos.append('accion', 'generar');
                enviaAjax(datos);
            }
        }
    });
});

function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}

function MultiConsulta() {
    let datos = new FormData();
    datos.append('accion', 'MultiConsulta');
    enviaAjax(datos);
}

function editar(id_devolucion, id_asignacion, id_estado, fecha, observacion) {
    $("#f")[0].reset();
    $("#titulo_modal").text("Modificar Devolución");
    $("#btn_guardar").text("Guardar Cambios").data("accion", "modificar");
    
    $('#id_asignacion').closest('.colum').show();
    $('#id_estado').closest('.colum').show();
    $('#observacion').closest('.colum').show();
    $('#fecha_devolucion').closest('.colum').show();

    $("#id_devolucion").val(id_devolucion);
    $("#fecha_devolucion").val(fecha);
    $("#observacion").val(observacion);
    
    $("#id_asignacion").val(id_asignacion).trigger('change');
    $("#id_estado").val(id_estado).trigger('change');
    
    abrirModal();
}

function anular(id_devolucion) {
    Swal.fire({
        title: 'Anular Devolución',
        text: "Ingrese el motivo (Mínimo 5 caracteres):",
        input: 'text',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#39b015',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value || value.trim().length < 5) return '¡El motivo no es válido!';
        }
    }).then((result) => {
        if (result.isConfirmed) {
            let datos = new FormData();
            datos.append('accion', 'anular');
            datos.append('id_devolucion', id_devolucion);
            datos.append('motivo', result.value);
            enviaAjax(datos);
        }
    });
}

function poblarCombos(asignaciones, estados) {
    let comboAsignacion = $("#id_asignacion");
    let comboEstado = $("#id_estado");
    
    comboAsignacion.find('option:not(:first)').remove();
    comboEstado.find('option:not(:first)').remove();

    if (asignaciones && asignaciones.length > 0) {
        asignaciones.forEach(a => {
            comboAsignacion.append(`<option value="${a.id_asignacion}">${a.atleta} - ${a.articulo}</option>`);
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
        beforeSend: function (request) { request.setRequestHeader("X-CSRF-TOKEN", token); },
        timeout: 120000,
        success: function (respuesta) {
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                $('#resultadoconsulta').html(respuesta);
                if (typeof lucide !== 'undefined') lucide.createIcons();
                if (typeof inicializarPaginador === 'function') inicializarPaginador();
                return;
            }
            try {
                var lee = JSON.parse(respuesta);
                if (lee.accion == "MultiConsulta") {
                    poblarCombos(lee.asignaciones, lee.estados);
                } else if (lee.accion == "incluir" || lee.accion == "modificar" || lee.accion == "exito") {
                    consultar();
                    MultiConsulta();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Operación Exitosa", lee.mensaje);
                } else if (lee.accion == "reporte") {
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
                } else if (lee.accion == "error") {
                    if (typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
                    muestraMensaje("error", 2000, "Error", lee.mensaje || lee.codigo);
                }
            } catch (e) {
                if (typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
                Swal.fire({ icon: 'error', title: 'Error de Comunicación', text: 'El servidor falló.' });
            }
        },
        error: function (request, status, err) {
            if (typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
            muestraMensaje("error", 2000, "Error", "ERROR: " + err);
        }
    });
}