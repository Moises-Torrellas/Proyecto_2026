$(document).ready(function () {
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    MultiConsulta();

    $("#ayuda").on("click", function() {
        if(typeof iniciarAyuda === 'function') {
            iniciarAyuda('devoluciones'); 
        }
    });

    // Acción para Nueva Devolución
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

        $('#id_asignacion').val("").trigger('change');
        $('#id_estado').val("").trigger('change');

        abrirModal(); 
    });

    // Acción para Generar Reporte (Abre el modal permitiendo usar filtros)
    $("#generar").on("click", function () {
        $("#f")[0].reset();
        $("#id_devolucion").val('');
        $("#titulo_modal").text("Filtros del Reporte");
        $("#btn_guardar").text("Generar PDF").attr("data-accion", "generar");

        $('#id_asignacion').closest('.colum').show();
        $('#id_estado').closest('.colum').show();
        $('#fecha_devolucion').closest('.colum').show();
        $('#observacion').closest('.colum').hide(); // Observación no filtra
        
        $('#id_asignacion').val("").trigger('change');
        $('#id_estado').val("").trigger('change');
        $('#fecha_devolucion').val('');

        abrirModal();
    });

    // Acción del botón Confirmar dentro del modal
    $('#btn_guardar').on('click', function () {
    let accion = $(this).attr("data-accion");
    
    // Validación estricta SOLO si vas a registrar o editar
    if (accion === "incluir" || accion === "modificar") {
        if ($('#id_asignacion').val() === "" || $('#id_estado').val() === "" || $('#fecha_devolucion').val() === "") {
            muestraMensaje("error", 2000, "Validación", "Complete los campos obligatorios.");
            return false;
        }
    }
    
    // --- AGREGA ESTA LÍNEA PARA VER SI LOS DATOS SALEN BIEN ---
    // Esto te dirá en la consola si el JS está capturando los datos correctamente
    console.log("Acción enviada:", accion);
    
    let datos = new FormData($('#f')[0]);
    datos.append('accion', accion);
    
    // --- OPCIONAL: DEPURACIÓN ---
    // Si la transacción falla, descomenta la siguiente línea para ver qué datos lleva el paquete
    // for (var pair of datos.entries()) { console.log(pair[0]+ ': ' + pair[1]); }
    
    enviaAjax(datos);
});
});

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
                $('#resultadoconsulta').html(respuesta);
                if (typeof lucide !== 'undefined') lucide.createIcons();
                if (typeof inicializarPaginador === 'function') inicializarPaginador();
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
    
    $("#id_asignacion").val(id_asignacion).trigger('change');
    $("#id_estado").val(id_estado).trigger('change');
    
    abrirModal();
}

function confirmarAnulacion(id_devolucion) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Se anulará la devolución y la asignacion volverá a estar asignado. Indique el motivo:",
        icon: 'warning',
        input: 'textarea',
        inputPlaceholder: 'Escriba el motivo...',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, Anular',
        cancelButtonText: 'Cancelar',
        preConfirm: (motivo) => {
            if (!motivo || motivo.trim() === '') {
                Swal.showValidationMessage('Debe ingresar un motivo para anular');
            }
            return motivo;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            let datos = new FormData();
            datos.append('accion', 'anular');
            datos.append('id_devolucion', id_devolucion);
            datos.append('motivo_anulacion', result.value); 
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
            let nomAtleta = '';
            if(a.nombres) nomAtleta = a.nombres + ' ' + (a.apellidos || '');
            else if(a.atleta) nomAtleta = a.atleta;
            
            let nomArticulo = a.articulo_nombre || a.catalogo || a.articulo || '';
            
            // Genera el texto limpio para el desplegable: Ej: "Casco (Jose Perez)"
            let textoVisible = 'Equipo sin especificar';
            if(nomArticulo !== '' || nomAtleta !== '') {
                textoVisible = `${nomArticulo} (${nomAtleta})`;
            }

            comboAsignacion.append(`<option value="${a.id_asignacion}">${textoVisible}</option>`);
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