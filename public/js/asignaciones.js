let timerBusqueda;
let equiposDisponibles = [];

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
    if (typeof inicializarPaginador === 'function') inicializarPaginador(); 
    consultar();
    MultiConsulta();
    
    if (typeof Validacion === 'function') {
        Validacion("fecha_asignacion", /^[0-9\b-]*$/, /^\d{4}-\d{2}-\d{2}$/, "Seleccione una fecha válida", "btn_guardar");
    }

    $('#codigo_atleta').select2({
        placeholder: "Seleccione un atleta...",
        allowClear: true,
        dropdownParent: $('#contenedor_modal')
    });
    
    $('#codigo_articulo').select2({
        placeholder: "Seleccione un artículo...",
        allowClear: true,
        dropdownParent: $('#contenedor_modal')
    });

    $('#lote_articulo').select2({
        placeholder: "Seleccione un artículo disponible...",
        width: '100%',
        dropdownParent: $('#contenedor_modal')
    });

    // --- Manejo del lote de artículos en Asignaciones ---
    $('#btn_agregar_lote_asig').on('click', agregarArticuloLoteAsig);

    $('#btn_vaciar_lote_asig').on('click', function () {
        vaciarLoteAsig();
    });

    $('#cuerpo_lote_asig').on('click', '.btn_quitar_lote_asig', function () {
        let codigo = $(this).data('codigo');
        $(this).closest('.item_lote').remove();
        habilitarOpcionSelect(codigo, true);
        actualizarListaLoteAsig();
    });

    $('#btn_guardar').on('click', function () {
        let accion = $(this).data("accion");
        
        if (accion === "incluir") {
            if (validarEnvio(accion)) {
                let articulos = obtenerArticulosLoteAsig();
                let total = articulos.length;
                let textoConfirmacion = total === 1 
                    ? '¿Está seguro que quiere registrar esta asignación?' 
                    : `¿Está seguro que quiere registrar la asignación de ${total} artículos a este atleta?`;

                confirmar(textoConfirmacion, function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData();
                        datos.append('accion', 'incluir');
                        datos.append('codigo_atleta', $('#codigo_atleta').val());
                        datos.append('fecha_asignacion', $('#fecha_asignacion').val());
                        datos.append('articulos', JSON.stringify(articulos));
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion === "modificar") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere modificar esta asignación?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', accion);
                        enviaAjax(datos);
                    }
                });
            }
        }
        // Procesar la acción de generar reporte desde el modal
        else if (accion === "generar") {
            opcionesReporte(function(formato) {
                if (typeof abrirAlertaEspara === 'function') {
                    abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                }
                var datos = new FormData($('#f')[0]);
                datos.append('accion', 'generar');
                datos.append('filtro', $('#busqueda').val());
                datos.append('formato', formato);
                enviaAjax(datos);
            });
        }
    });

    $("#btn_nuevo").on("click", function () {
        limpia(); 
        $("#f")[0].reset();
        $("#id_asignacion").val('');
        $("#titulo_modal").text("Registrar Asignación");
        $("#btn_guardar").text("Confirmar Préstamo").data("accion", "incluir");
        
        let localDate = new Date();
        localDate.setMinutes(localDate.getMinutes() - localDate.getTimezoneOffset());
        let hoy = localDate.toISOString().split('T')[0];
        
        let fp = document.querySelector("#fecha_asignacion")._flatpickr;
        if (fp) {
            fp.setDate(hoy);
        } else {
            $('#fecha_asignacion').val(hoy);
        }
        
        $('#codigo_atleta').val(null).trigger('change');
        $('#lote_articulo').val(null).trigger('change');
        vaciarLoteAsig();

        // Configuración visual para inclusión en lote
        $('#col_atleta').show();
        $('#col_fecha_asignacion').show();
        $('#lbl_fecha').text('Fecha de Asignación');
        $('#col_fecha_fin').hide();
        $('#row_anulados').hide();
        $('#seccion_lote_asig').show();
        $('#row_articulo_modificar').hide();
        
        abrirModal(); 
    });

    // Configuración del botón para abrir criterios de Reporte
    $("#generar").on("click", function () {
        limpia();
        $("#btn_guardar").data("accion", "generar");
        $("#btn_guardar").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");

        // Ocultamos elementos de registro, mostramos selectores de rango y filtros del reporte
        $('#col_atleta').hide();
        $('#col_fecha_asignacion').show();
        $('#lbl_fecha').text('Fecha Inicio');
        $('#col_fecha_fin').show();
        $('#row_anulados').show();
        $('#seccion_lote_asig').hide();
        $('#row_articulo_modificar').hide();

        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Búsqueda', description: 'Aquí puedes buscar al registro que necesites.', position: 'bottom' }
            },
            {
                element: '#btn_nuevo',
                popover: { title: 'Nuevo Registro', description: 'Si pulsas aquí se abrirá un modal para registrar la asignación de uno o varios artículos a un atleta en un solo registro.', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsas aquí se abrirá un modal para generar un reporte en PDF o Excel.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Registros', description: 'Aquí se mostrarán todos los registros agrupados por atleta.', position: 'top' }
            },
            {
                element: '.listado_item',
                popover: { title: 'Detalles', description: 'Haz clic en el registro para desplegar y ver las asignaciones individuales.', position: 'bottom' },
                onNext: function() {
                    const primerRegistro = document.querySelector('.listado_item');
                    if (primerRegistro) {
                        const contenedor = $(primerRegistro).closest('.listado_contenedor_grupal');
                        if (!contenedor.hasClass('expandido')) {
                            contenedor.addClass('expandido');
                            contenedor.find('.listado_detalle_oculto').show();
                        }
                    }
                }
            },
            {
                element: '.sub_item_acciones',
                popover: { title: 'Acciones de Asignación', description: 'Aquí podrás modificar o anular (liberar) el artículo prestado.', position: 'left' }
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
});

function escaparHtml(texto) {
    return String(texto ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function agregarArticuloLoteAsig() {
    let codigo = $('#lote_articulo').val();
    if (!codigo) {
        muestraMensaje("error", 2000, "Validación", "Seleccione un artículo de la lista antes de agregarlo.");
        return;
    }

    if ($(`#cuerpo_lote_asig .item_lote[data-codigo="${codigo}"]`).length > 0) {
        muestraMensaje("warning", 2000, "Atención", "Este artículo ya fue agregado a la lista.");
        return;
    }

    let equipo = equiposDisponibles.find(e => String(e.codigo_articulo) === String(codigo));
    let nombre = equipo ? equipo.articulo : ("Artículo #" + codigo);
    if (equipo && equipo.talla && equipo.talla.trim() !== '') {
        nombre += ` (Talla: ${equipo.talla})`;
    }
    let codigoClub = equipo && equipo.codigo_club ? equipo.codigo_club : ('ID: ' + codigo);
    let estadoFisico = equipo && equipo.estado ? equipo.estado : 'Excelente';
    let nivelEstado = equipo ? parseInt(equipo.nivel_estado, 10) : 1;

    let claseCondicion = 'estatus_v';
    if (nivelEstado === 2) claseCondicion = 'estatus_a';
    else if (nivelEstado >= 3) claseCondicion = 'estatus_r';

    let html = `
        <div class="item_lote" data-codigo="${escaparHtml(codigo)}">
            <div class="item_lote_icono"><i data-lucide="package"></i></div>
            <div class="item_lote_info">
                <span class="item_lote_titulo" title="${escaparHtml(nombre)}">${escaparHtml(nombre)}</span>
                <span class="item_lote_subtitulo">Código: ${escaparHtml(codigoClub)}</span>
            </div>
            <div class="item_lote_etiqueta">
                Condición: <strong class="${claseCondicion}">${escaparHtml(estadoFisico)}</strong>
            </div>
            <button type="button" class="btn_t cbt_r btn_quitar_lote_asig" data-codigo="${escaparHtml(codigo)}" title="Quitar de la lista">
                <i class="fi fi-sr-trash"></i>
            </button>
        </div>`;

    $('#cuerpo_lote_asig .lista_lote_vacio').remove();
    $('#cuerpo_lote_asig').append(html);

    habilitarOpcionSelect(codigo, false);
    $('#lote_articulo').val(null).trigger('change');

    actualizarListaLoteAsig();

    let cuerpo = document.getElementById('cuerpo_lote_asig');
    if (cuerpo) cuerpo.scrollTop = cuerpo.scrollHeight;
}

function vaciarLoteAsig() {
    $('#cuerpo_lote_asig').empty();
    $('#lote_articulo option').prop('disabled', false);
    $('#lote_articulo').val(null).trigger('change');
    actualizarListaLoteAsig();
}

function habilitarOpcionSelect(codigo, habilitar) {
    let opt = $(`#lote_articulo option[value="${codigo}"]`);
    if (opt.length) {
        opt.prop('disabled', !habilitar);
        $('#lote_articulo').trigger('change');
    }
}

function actualizarListaLoteAsig() {
    let items = $('#cuerpo_lote_asig .item_lote');
    $('#contador_lote_asig').text(items.length);
    $('#btn_vaciar_lote_asig').prop('disabled', items.length === 0);

    if (items.length === 0) {
        $('#cuerpo_lote_asig').html(`
            <div class="lista_lote_vacio">
                <i data-lucide="package-open"></i>
                Aún no hay artículos seleccionados. Elija un artículo disponible y presione «+».
            </div>`);
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function obtenerArticulosLoteAsig() {
    let articulos = [];
    $('#cuerpo_lote_asig .item_lote').each(function () {
        let codigo = $(this).data('codigo');
        if (codigo) articulos.push(String(codigo));
    });
    return articulos;
}

function validarEnvio(accion) {
    let fecha = $('#fecha_asignacion').val();
    let regexFecha = /^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/;

    if (!fecha || !regexFecha.test(fecha)) {
        muestraMensaje("error", 2000, "Validación", "La fecha de asignación es obligatoria y debe ser válida (AAAA-MM-DD).");
        return false;
    }

    if (accion === "incluir") {
        let atleta = $('#codigo_atleta').val();
        if (!atleta) {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar un atleta de la lista.");
            return false;
        }
        let articulos = obtenerArticulosLoteAsig();
        if (articulos.length === 0) {
            muestraMensaje("error", 2000, "Validación", "Debe agregar al menos un artículo a la lista de asignación.");
            return false;
        }
    } else if (accion === "modificar") {
        let atleta = $('#codigo_atleta').val();
        let articulo = $('#codigo_articulo').val();
        if (!atleta) {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar un atleta válido.");
            return false;
        }
        if (!articulo) {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar un artículo de inventario válido.");
            return false;
        }
    }
    return true;
}

function editar(id_asignacion, codigo_atleta, codigo_articulo, fecha) {
    limpia();
    $("#f")[0].reset();
    $("#titulo_modal").text("Modificar Asignación");
    $("#btn_guardar").text("Guardar Cambios").data("accion", "modificar");
    
    let fechaLimpia = fecha ? fecha.split(' ')[0] : '';

    $("#id_asignacion").val(id_asignacion);
    let fp = document.querySelector("#fecha_asignacion")._flatpickr;
    if (fp) {
        fp.setDate(fechaLimpia);
    } else {
        $("#fecha_asignacion").val(fechaLimpia);
    }
    $("#codigo_atleta").val(codigo_atleta).trigger('change');
    
    if ($(`#codigo_articulo option[value='${codigo_articulo}']`).length === 0) {
        $("#codigo_articulo").append(new Option("Artículo Actual (Mantenido)", codigo_articulo, true, true));
    }
    $("#codigo_articulo").val(codigo_articulo).trigger('change');
    
    // Configuración visual de edición individual
    $('#col_atleta').show();
    $('#col_fecha_asignacion').show();
    $('#lbl_fecha').text('Fecha de Asignación');
    $('#col_fecha_fin').hide();
    $('#row_anulados').hide();
    $('#seccion_lote_asig').hide();
    $('#row_articulo_modificar').show();

    abrirModal();
}

function anular(id_asignacion, codigo_articulo) {
    confirmar('¿Está seguro que quiere anular esta asignación y liberar el artículo?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'anular');
            datos.append('id_asignacion', id_asignacion);
            datos.append('codigo_articulo', codigo_articulo);
            enviaAjax(datos);
        }
    });
}

function poblarCombos(atletas, equipos) {
    let comboAtleta = $("#codigo_atleta");
    let comboEquipo = $("#codigo_articulo");
    let comboLoteArticulo = $("#lote_articulo");
    
    comboAtleta.find('option:not(:first)').remove();
    comboEquipo.find('option:not(:first)').remove();
    comboLoteArticulo.find('option:not(:first)').remove();

    equiposDisponibles = equipos || [];

    if (atletas && atletas.length > 0) {
        atletas.forEach(a => {
            let primerNombre = a.p_nombre || '';
            let primerApellido = a.p_apellidos || '';
            let cedula = a.documento_identidad || 'N/A'; 
            let categoria = a.categoria || 'Sin Categoría'; 
            
            let textoMostrar = `${primerNombre} ${primerApellido} - CI: ${cedula} - ${categoria}`;
            let idValue = a.codigo_atleta || a.id_atleta;
            comboAtleta.append(`<option value="${idValue}">${escaparHtml(textoMostrar)}</option>`);
        });
    }

    if (equiposDisponibles.length > 0) {
        equiposDisponibles.forEach(e => {
            let nombreMostrar = e.articulo || ('Artículo #' + e.codigo_articulo);
            if (e.talla && e.talla.trim() !== '') {
                nombreMostrar += ` (Talla: ${e.talla})`;
            }
            if (e.codigo_club) {
                nombreMostrar += ` · ${e.codigo_club}`;
            }
            let cond = e.estado ? ` - Condición: ${e.estado}` : '';
            let textoOpcion = `${nombreMostrar}${cond}`;

            comboEquipo.append(`<option value="${e.codigo_articulo}">${escaparHtml(textoOpcion)}</option>`);
            comboLoteArticulo.append(`<option value="${e.codigo_articulo}">${escaparHtml(textoOpcion)}</option>`);
        });
    }

    comboAtleta.trigger('change');
    comboEquipo.trigger('change');
    comboLoteArticulo.trigger('change');
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
                crearConsulta(respuesta);
                return;
            }
            
            try {
                var lee = typeof respuesta === 'object' ? respuesta : JSON.parse(respuesta.substring(respuesta.indexOf('{')));

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
                } else if (lee.accion == "incluir" || lee.accion == "modificar" || lee.accion == "exito" || lee.accion == "anular") {
                    vaciarLoteAsig();
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

function limpia() {
    if($('#f')[0]) $('#f')[0].reset();
    $('.select2').val(null).trigger('change');
    $("#btn_guardar").data("accion", "incluir");
    $("#btn_guardar").text("Confirmar Préstamo");
    vaciarLoteAsig();
}
