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

    $('#id_asignacion').select2({
        placeholder: "Seleccione una asignación...",
        allowClear: true,
        dropdownParent: $('#contenedor_modal')
    });
    
    $('#id_estado').select2({
        placeholder: "Seleccione un Estado Fisico...",
        allowClear: true,
        dropdownParent: $('#contenedor_modal')
    });

    $('#filtro_atleta').select2({
        placeholder: "Todos los atletas",
        allowClear: true,
        dropdownParent: $('#contenedor_modal')
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Búsqueda', description: 'Aquí puedes buscar al registro que necesites.', position: 'bottom' }
            },
            {
                element: '#btn_nuevo',
                popover: { title: 'Nueva Devolución', description: 'Si pulsas aquí se abrirá un modal para registrar una nueva devolución.', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsas aquí se abrirá un modal para generar un reporte en PDF o Excel.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Registros', description: 'Aquí se mostrarán todos los registros.', position: 'top' }
            },
            {
                element: '.listado_item',
                popover: { title: 'Detalles', description: 'Haz clic en el registro para desplegar y ver las devoluciones individuales.', position: 'bottom' },
                onNext: function() {
                    const primerRegistro = document.querySelector('.listado_item');
                    if (primerRegistro) {
                        const contenedor = $(primerRegistro).closest('.listado_contenedor_grupal');
                        if (contenedor.find('.listado_detalle_oculto').css('display') === 'none') {
                            contenedor.find('.listado_detalle_oculto').show();
                        }
                    }
                }
            },
            {
                element: '.sub_item_acciones',
                popover: { title: 'Acciones de Devolución', description: 'Aquí podrás modificar o anular la devolución.', position: 'left' }
            },
            {
                element: '#rowsPerPage',
                popover: { title: 'Registros Deseados', description: 'Aquí podrás seleccionar la cantidad de registros que quieres que se muestren.', position: 'top' }
            },
            {
                element: '#botonera',
                popover: { title: 'Cambiar de Página', description: 'Botones para cambiar de página.', position: 'top' }
            },
            {
                element: '#cantidad',
                popover: { title: 'Cantidad', description: 'Aquí puedes ver la cantidad de registros.', position: 'top' }
            }
        ];
        if (typeof iniciarTourConPasos === 'function') {
            iniciarTourConPasos(pasos).start();
        }
    });

    // --- ACCIONES DE MODALES ---
    $("#btn_nuevo").on("click", function () {
        $("#f")[0].reset();
        $("#id_devolucion").val('');
        $("#titulo_modal").text("Registrar Devolución");
        $("#btn_guardar").text("Confirmar").attr("data-accion", "incluir");
        let localDate = new Date();
        localDate.setMinutes(localDate.getMinutes() - localDate.getTimezoneOffset());
        let hoy = localDate.toISOString().split('T')[0];
        
        let fp = document.querySelector("#fecha_devolucion")._flatpickr;
        if (fp) {
            fp.setDate(hoy);
        } else {
            $('#fecha_devolucion').val(hoy);
        }
        
        // Mostrar campos de registro, ocultar campos de reporte
        $('#col_asignacion').show();
        $('#row_observacion').show();
        $('#row_atleta_reporte').hide();
        $('#col_fecha_hasta').hide();
        $('#lbl_fecha').text('Fecha de Devolución');

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

    // Configuración del botón para abrir criterios de Reporte (mismo modal, oculta/muestra campos)
    $("#generar").on("click", function () {
        $("#f")[0].reset();
        $("#id_devolucion").val('');
        $("#btn_guardar").text("Generar Reporte").attr("data-accion", "generar");
        $("#titulo_modal").text("Generar Reporte de Devoluciones");

        // Ocultar campos de registro, mostrar campos de reporte
        $('#col_asignacion').hide();
        $('#row_observacion').hide();
        $('#row_atleta_reporte').show();
        $('#col_fecha_hasta').show();
        $('#lbl_fecha').text('Fecha Desde');

        // Permitir "Todos los estados" en el select para reporte
        $('#id_estado').val("").trigger('change');

        abrirModal();
    });

    $('#btn_guardar').on('click', function () {
        let accion = $(this).attr("data-accion");
        
        if (accion === "incluir" || accion === "modificar") {
            if ($('#id_asignacion').val() === "" || $('#id_estado').val() === "" || $('#fecha_devolucion').val() === "") {
                muestraMensaje("error", 2000, "Validación", "Complete los campos obligatorios.");
                return false;
            }
            let datos = new FormData($('#f')[0]);
            datos.append('accion', accion);
            enviaAjax(datos);
        } else if (accion === "generar") {
            opcionesReporte(function(formato) {
                if (typeof abrirAlertaEspara === 'function') {
                    abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                }
                var datos = new FormData($('#f')[0]);
                datos.append('accion', 'generar');
                datos.append('formato', formato);
                datos.append('codigo_atleta', $('#filtro_atleta').val());
                datos.append('fecha_desde', $('#fecha_devolucion').val());
                enviaAjax(datos);
            });
        }
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
                    poblarCombos(lee.asignaciones, lee.estados, lee.atletas);
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

    // Mostrar campos de registro, ocultar campos de reporte
    $('#col_asignacion').show();
    $('#row_observacion').show();
    $('#row_atleta_reporte').hide();
    $('#col_fecha_hasta').hide();
    $('#lbl_fecha').text('Fecha de Devolución');

    let fechaLimpia = fecha ? fecha.split(' ')[0] : '';
    $("#id_devolucion").val(id_devolucion);
    let fp = document.querySelector("#fecha_devolucion")._flatpickr;
    if (fp) {
        fp.setDate(fechaLimpia);
    } else {
        $("#fecha_devolucion").val(fechaLimpia);
    }
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

function poblarCombos(asignaciones, estados, atletas) {
    let comboAsignacion = $("#id_asignacion");
    let comboEstado = $("#id_estado");
    let comboAtleta = $("#filtro_atleta");
    
    comboAsignacion.find('option:not(:first)').remove();
    comboEstado.find('option:not(:first)').remove();
    comboAtleta.find('option:not(:first)').remove();

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

    if (atletas && atletas.length > 0) {
        atletas.forEach(a => {
            let nombre_completo = `${a.p_nombre} ${a.p_apellidos}`;
            comboAtleta.append(`<option value="${a.codigo_atleta}">${nombre_completo} - CI: ${a.documento_identidad}</option>`);
        });
    }

    comboAsignacion.trigger('change');
    comboEstado.trigger('change');
    comboAtleta.trigger('change');
}