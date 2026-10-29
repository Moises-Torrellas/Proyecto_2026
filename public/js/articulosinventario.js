let timerBusqueda;

// Datos auxiliares para el registro por lote
let estadosFisicos = [];
const MAX_ARTICULOS_LOTE = 50;

$(document).ready(function () {
    cargarCombos();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    
    // Inicializar Select2
    if ($.fn.select2) {
        $('#id_catalogo').select2({
            placeholder: "Seleccione un artículo...",
            allowClear: true,
            dropdownParent: $('.contenedor_modal')
        });
        
        $('#id_estado').select2({
            placeholder: "Seleccione un estado...",
            allowClear: true,
            dropdownParent: $('.contenedor_modal')
        });

        $('#lote_catalogo').select2({
            placeholder: "Seleccione un artículo...",
            width: '100%',
            dropdownParent: $('.contenedor_modal')
        });

        $('#lote_estado').select2({
            placeholder: "Estado...",
            width: '100%',
            dropdownParent: $('.contenedor_modal')
        });
    }

    // Búsqueda con retraso (debounce)
    $('#busqueda').off('keyup').on('keyup', function () {
        clearTimeout(timerBusqueda);
        let val = $(this).val();
        timerBusqueda = setTimeout(() => {
            consultar();
        }, 500);
    });

    // Botón para abrir modal de INCLUIR
    $("#btn_nuevo").on("click", function () {
        limpia();
        $("#codigo_articulo").val('');
        $("#titulo_modal").text("Registrar Artículos en Inventario");
        $("#btn_guardar").text("Registrar").attr("data-accion", "incluir");
        mostrarModo('incluir');
        abrirModal(); 
    });

    // Botón para abrir modal de GENERAR REPORTE
    $('#generar').off('click').on('click', function (e) {
        e.preventDefault();
        limpia();
        $("#titulo_modal").text("Generar Reporte de Inventario");
        $("#btn_guardar").text("Generar Reporte").attr("data-accion", "generar");
        mostrarModo('generar');
        abrirModal(); 
    });

    // --- Registro por lote ---
    $('#btn_agregar_lote').on('click', agregarArticulosLote);

    $('#btn_vaciar_lote').on('click', function () {
        vaciarLote();
    });

    // Quitar un artículo de la lista
    $('#cuerpo_lote').on('click', '.btn_quitar_lote', function () {
        $(this).closest('.item_lote').remove();
        actualizarListaLote();
    });

    // Botón central de proceso dentro del modal
    $('#btn_guardar').on('click', function () {
        let accion = $(this).attr('data-accion');

        if (accion === "incluir") {
            let articulos = obtenerArticulosLote();
            if (articulos === null) return false;

            let total = articulos.length;
            let texto = total === 1
                ? '¿Registrar este artículo en el inventario?'
                : `¿Registrar ${total} artículos en el inventario?`;

            confirmar(texto, function (confirma) {
                if (confirma) {
                    let datos = new FormData();
                    datos.append('accion', 'incluir');
                    datos.append('articulos', JSON.stringify(articulos));
                    enviaAjax(datos);
                }
            });
            return;
        }

        // Validación exclusiva para modificar (solo el estado físico es editable)
        if (accion === "modificar") {
            if (!$('#id_estado').val()) {
                muestraMensaje("error", 2000, "Validación", "Seleccione el estado físico del artículo.");
                return false;
            }
        }

        if (accion === "generar") {
            opcionesReporte(function(formato) {
                if (typeof abrirAlertaEspara === 'function') {
                    abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                }
                let datos = new FormData($('#f')[0]);
                datos.append('accion', accion);
                datos.append('formato', formato);
                enviaAjax(datos);
            });
        } else {
            confirmar('¿Guardar los cambios?', function(confirma) {
                if (confirma) {
                    let datos = new FormData();
                    datos.append('accion', accion);
                    datos.append('codigo_articulo', $('#codigo_articulo').val());
                    datos.append('id_estado', $('#id_estado').val());
                    enviaAjax(datos);
                }
            });
        }
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Búsqueda', description: 'Filtra el inventario por nombre o código.', position: 'bottom' }
            },
            {
                element: '#btn_nuevo',
                popover: { title: 'Registrar', description: 'Añade uno o varios artículos físicos al inventario en un solo registro, eligiendo el estado físico de cada uno.', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Reporte', description: 'Abre el panel para filtrar y descargar un PDF o Excel.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Inventario', description: 'Aquí se mostrarán todos los artículos agrupados por catálogo.', position: 'top' }
            },
            {
                element: '.listado_item',
                popover: { title: 'Registro de Catálogo', description: 'Haz clic en el registro para desplegar y ver las unidades individuales (físicas) de este artículo.', position: 'bottom' },
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
                popover: { title: 'Acciones de Unidades', description: 'Aquí podrás modificar el estado físico de la unidad, o retirarla/reincorporarla del inventario.', position: 'left' }
            },
            {
                element: '#rowsPerPage',
                popover: { title: 'Registros Deseados', description: 'Aquí podrá seleccionar la cantidad de registros que quiere que se muestren.', position: 'top' }
            },
            {
                element: '#botonera',
                popover: { title: 'Cambiar de Página', description: 'Botones para cambiar de página.', position: 'top' }
            },
            {
                element: '#cantidad',
                popover: { title: 'Cantidad', description: 'Aquí puedes ver la cantidad de artículos mostrados actualmente.', position: 'top' }
            }
        ];
        if (typeof iniciarTourConPasos === 'function') {
            const driver = iniciarTourConPasos(pasos);
            driver.start();
        }
    });
});

/**
 * Muestra u oculta las secciones del modal según la acción:
 *  - incluir:   lista de artículos por lote
 *  - modificar: solo el estado físico
 *  - generar:   filtros de catálogo y estado
 */
function mostrarModo(modo) {
    if (modo === 'incluir') {
        $('#seccion_lote').show();
        $('#seccion_individual').hide();
        vaciarLote();
        $('#lote_cantidad').val(1);
        if (estadosFisicos.length > 0) {
            $('#lote_estado').val(estadosFisicos[0].id_estado).trigger('change');
        }
    } else {
        $('#seccion_lote').hide();
        $('#seccion_individual').show();
        $('#col_catalogo').toggle(modo === 'generar');
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function escaparHtml(texto) {
    return String(texto ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function opcionesEstados(seleccionado) {
    return estadosFisicos.map(e =>
        `<option value="${e.id_estado}" ${String(e.id_estado) === String(seleccionado) ? 'selected' : ''}>${escaparHtml(e.nombre)}</option>`
    ).join('');
}

function agregarArticulosLote() {
    let idCatalogo = $('#lote_catalogo').val();
    let idEstado = $('#lote_estado').val();
    let cantidad = parseInt($('#lote_cantidad').val(), 10);

    if (!idCatalogo) {
        muestraMensaje("error", 2000, "Validación", "Seleccione el artículo del catálogo que desea agregar.");
        return;
    }
    if (!idEstado) {
        muestraMensaje("error", 2000, "Validación", "Seleccione el estado físico inicial.");
        return;
    }
    if (isNaN(cantidad) || cantidad < 1) {
        muestraMensaje("error", 2000, "Validación", "La cantidad debe ser un número mayor a cero.");
        return;
    }

    let actuales = $('#cuerpo_lote .item_lote').length;
    if (actuales + cantidad > MAX_ARTICULOS_LOTE) {
        muestraMensaje("error", 3000, "Validación", `Solo puede registrar hasta ${MAX_ARTICULOS_LOTE} artículos por registro.`);
        return;
    }

    let nombre = $('#lote_catalogo option:selected').text();
    let html = '';
    for (let i = 0; i < cantidad; i++) {
        html += `
            <div class="item_lote" data-catalogo="${escaparHtml(idCatalogo)}">
                <div class="item_lote_icono"><i data-lucide="package"></i></div>
                <div class="item_lote_info">
                    <span class="item_lote_titulo" title="${escaparHtml(nombre)}">${escaparHtml(nombre)}</span>
                    <span class="item_lote_subtitulo"></span>
                </div>
                <div class="item_lote_estado">
                    <select class="formulario select_estado_lote" aria-label="Estado físico">
                        ${opcionesEstados(idEstado)}
                    </select>
                </div>
                <button type="button" class="btn_t cbt_r btn_quitar_lote" title="Quitar de la lista">
                    <i class="fi fi-sr-trash"></i>
                </button>
            </div>`;
    }

    $('#cuerpo_lote .lista_lote_vacio').remove();
    $('#cuerpo_lote').append(html);
    $('#lote_cantidad').val(1);
    actualizarListaLote();

    // Desplazar hasta el último artículo agregado
    let cuerpo = document.getElementById('cuerpo_lote');
    if (cuerpo) cuerpo.scrollTop = cuerpo.scrollHeight;
}

function vaciarLote() {
    $('#cuerpo_lote').empty();
    actualizarListaLote();
}

function actualizarListaLote() {
    let items = $('#cuerpo_lote .item_lote');
    $('#contador_lote').text(items.length);
    $('#btn_vaciar_lote').prop('disabled', items.length === 0);

    if (items.length === 0) {
        $('#cuerpo_lote').html(`
            <div class="lista_lote_vacio">
                <i data-lucide="package-open"></i>
                Aún no hay artículos en la lista. Seleccione un artículo, la cantidad y presione «+».
            </div>`);
    } else {
        // Numerar las unidades de cada artículo del catálogo
        let conteo = {};
        items.each(function () {
            let cat = $(this).data('catalogo');
            conteo[cat] = (conteo[cat] || 0) + 1;
            $(this).find('.item_lote_subtitulo').text(`Unidad nueva #${conteo[cat]} · Código automático`);
        });
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/**
 * Devuelve el arreglo [{id_catalogo, id_estado}] de la lista, o null si no es válido.
 */
function obtenerArticulosLote() {
    let items = $('#cuerpo_lote .item_lote');
    if (items.length === 0) {
        muestraMensaje("error", 2500, "Validación", "Agregue al menos un artículo a la lista antes de registrar.");
        return null;
    }

    let articulos = [];
    let valido = true;
    items.each(function () {
        let estado = $(this).find('.select_estado_lote').val();
        if (!estado) {
            valido = false;
            return false;
        }
        articulos.push({ id_catalogo: String($(this).data('catalogo')), id_estado: String(estado) });
    });

    if (!valido) {
        muestraMensaje("error", 2500, "Validación", "Todos los artículos deben tener un estado físico seleccionado.");
        return null;
    }
    return articulos;
}

function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    datos.append('filtro', $('#busqueda').val() || '');
    enviaAjax(datos);
}

function cargarCombos() {
    let datos = new FormData();
    datos.append('accion', 'cargar_combos');
    enviaAjax(datos);
}

function editar(id, catalogo, estado) {
    limpia();
    $("#titulo_modal").text("Modificar Estado Físico");
    $("#btn_guardar").text("Modificar").attr("data-accion", "modificar");
    
    $("#codigo_articulo").val(id);
    
    if ($.fn.select2) {
        $("#id_catalogo").val(catalogo).trigger('change');
        $("#id_estado").val(estado).trigger('change');
    }
    
    mostrarModo('modificar');
    
    abrirModal();
}

function eliminar(id) {
    confirmar(`¿Desea retirar este artículo del inventario?`, function (confirmado) {
        if (confirmado) {
            let datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('codigo_articulo', id);
            enviaAjax(datos);
        }
    });
}

function reincorporar(id) {
    confirmar(`¿Desea reincorporar este artículo al inventario disponible?`, function (confirmado) {
        if (confirmado) {
            let datos = new FormData();
            datos.append('accion', 'reincorporar');
            datos.append('codigo_articulo', id);
            enviaAjax(datos);
        }
    });
}

function poblarCombos(catalogos, estados) {
    let comboCat = $("#id_catalogo");
    let comboEst = $("#id_estado");
    let comboLoteCat = $("#lote_catalogo");
    let comboLoteEst = $("#lote_estado");
    
    comboCat.find('option:not(:first)').remove();
    comboEst.find('option:not(:first)').remove();
    comboLoteCat.find('option:not(:first)').remove();
    comboLoteEst.find('option:not(:first)').remove();

    estadosFisicos = estados || [];

    (catalogos || []).forEach(c => {
        let txt = c.talla ? `${c.nombre} (Talla: ${c.talla})` : c.nombre;
        let opcion = `<option value="${c.id_catalogo}">${escaparHtml(txt)}</option>`;
        comboCat.append(opcion);
        comboLoteCat.append(opcion);
    });

    estadosFisicos.forEach(e => {
        let opcion = `<option value="${e.id_estado}">${escaparHtml(e.nombre)}</option>`;
        comboEst.append(opcion);
        comboLoteEst.append(opcion);
    });
}

function crearConsulta(htmlRecibido) {
    $('#resultadoconsulta').html(htmlRecibido);
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}



function limpia() {
    if($('#f')[0]) $('#f')[0].reset();
    if ($.fn.select2) {
        $('.select2').val(null).trigger('change');
    }
}

// Función principal AJAX
function enviaAjax(datos) {
    var token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        async: true,
        url: "", 
        type: "POST",
        contentType: false,
        data: datos,
        processData: false,
        cache: false,
        timeout: 120000,
        beforeSend: function (req) { req.setRequestHeader("X-CSRF-TOKEN", token); },
        success: function (respuesta) {
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta);
                return;
            }
            try {
                var lee;
                if (typeof respuesta === 'object') lee = respuesta;
                else lee = JSON.parse(respuesta.substring(respuesta.indexOf('{')));

                if (lee.accion == "cargar_combos") {
                    poblarCombos(lee.catalogos, lee.estados);
                } 
                // Añadimos "reincorporar" a la lista de acciones que refrescan la tabla y muestran éxito
                else if (lee.accion == "exito" || lee.accion == "eliminar" || lee.accion == "modificar" || lee.accion == "incluir" || lee.accion == "reincorporar") {
                    if (typeof cerrarModal === 'function') cerrarModal(); 
                    vaciarLote();
                    consultar();
                    muestraMensaje("success", 3000, "Éxito", lee.mensaje);
                } 
                else if (lee.accion === "reporte") {
                    // Cerrar alertas y modales
                    if(typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
                    if(typeof cerrarModal === 'function') cerrarModal();
                    
                    muestraMensaje("success", 1000, "Reporte Generado", 'Se ha generado el reporte');
                    
                    // Técnica del enlace fantasma para abrir el PDF sin bloqueos
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
                    if(typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
                    muestraMensaje("error", 4000, "Error", lee.mensaje);
                }
            } catch (e) {
                console.error("Error parseando JSON:", e);
                if(typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
            }
        },
        error: function(request, status, err) {
            if(typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 4000, "Error", "ERROR: <br/>" + status + ": " + err);
            }
        }
    });
}
