let timerBusqueda;

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
        $("#titulo_modal").text("Registrar Nuevo Artículo");
        $("#btn_guardar").text("Guardar").attr("data-accion", "incluir");
        $('#id_catalogo').closest('.colum').show();
        abrirModal(); 
    });

    // Botón para abrir modal de GENERAR REPORTE
    $('#generar').off('click').on('click', function (e) {
        e.preventDefault();
        limpia();
        $("#titulo_modal").text("Generar Reporte de Inventario");
        $("#btn_guardar").text("Generar Reporte").attr("data-accion", "generar");
        abrirModal(); 
    });

    // Botón central de proceso dentro del modal
    $('#btn_guardar').on('click', function () {
        let accion = $(this).attr('data-accion');

        // Validación exclusiva para incluir o modificar
        if (accion === "incluir" || accion === "modificar") {
            if ($('#id_catalogo').val() === null || $('#id_estado').val() === null) {
                muestraMensaje("error", 2000, "Validación", "Seleccione el artículo y su estado físico.");
                return false;
            }
        }

        // Definir el texto de confirmación según la acción
        let textoConfirmacion = '';
        if (accion === "incluir") textoConfirmacion = '¿Registrar este artículo en el inventario?';
        else if (accion === "modificar") textoConfirmacion = '¿Guardar los cambios?';
        else if (accion === "generar") textoConfirmacion = '¿Desea generar el reporte con los filtros seleccionados?';

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
            confirmar(textoConfirmacion, function(confirma) {
                if (confirma) {
                    let datos = new FormData($('#f')[0]);
                    datos.append('accion', accion);
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
                popover: { title: 'Registrar', description: 'Añade un nuevo artículo físico al inventario.', position: 'bottom' }
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
    $("#titulo_modal").text("Modificar Artículo");
    $("#btn_guardar").text("Modificar").attr("data-accion", "modificar");
    
    $("#codigo_articulo").val(id);
    
    if ($.fn.select2) {
        $("#id_catalogo").val(catalogo).trigger('change');
        $("#id_estado").val(estado).trigger('change');
    }
    
    $('#id_catalogo').closest('.colum').hide();
    
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
    
    comboCat.find('option:not(:first)').remove();
    comboEst.find('option:not(:first)').remove();

    catalogos.forEach(c => {
        let txt = c.talla ? `${c.nombre} (Talla: ${c.talla})` : c.nombre;
        comboCat.append(`<option value="${c.id_catalogo}">${txt}</option>`);
    });

    estados.forEach(e => {
        comboEst.append(`<option value="${e.id_estado}">${e.nombre}</option>`);
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