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
    consultar();
    
    if (typeof Validacion === 'function') {
        Validacion("fecha_asignacion", /^[0-9\b-]*$/, /^\d{4}-\d{2}-\d{2}$/, "Seleccione una fecha válida", "btn_guardar");
    }

    $('#codigo_atleta').select2({
        placeholder: "Seleccione un atleta...",
        allowClear: true,
        dropdownParent: $('.contenedor_modal')
    });
    
    $('#codigo_articulo').select2({
        placeholder: "Seleccione un artículo...",
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

    $("#btn_nuevo").on("click", function () {
        limpia(); 
        $("#f")[0].reset();
        $("#id_asignacion").val('');
        $("#titulo_modal").text("Registrar Asignación");
        $("#btn_guardar").text("Registrar Asignación").data("accion", "incluir");
        
        let hoy = new Date().toISOString().split('T')[0];
        $('#fecha_asignacion').val(hoy);
        
        $('#codigo_atleta').val(null).trigger('change');
        $('#codigo_articulo').val(null).trigger('change');
        
        abrirModal(); 
    });

    $('#generar').on('click', function () {
        window.open('?url=Reportes/Asignaciones', '_blank'); 
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            { element: '#busqueda', popover: { title: 'Búsqueda', description: 'Aquí puedes buscar por nombre de atleta.', position: 'bottom' } },
            { element: '#btn_nuevo', popover: { title: 'Nueva Asignación', description: 'Registra un préstamo de equipo.', position: 'bottom' } },
            { element: '#generar', popover: { title: 'Generar Reporte', description: 'Descarga un archivo PDF de las asignaciones.', position: 'left' } },
            { element: '#resultadoconsulta', popover: { title: 'Lista Agrupada', description: 'Haz clic en cualquier atleta para ver sus detalles.', position: 'top' } }
        ];
        if (typeof iniciarTourConPasos === 'function') {
            iniciarTourConPasos(pasos).start();
        }
    });
});

function validarEnvio(accion) {
    if (accion === "incluir" || accion === "modificar") {
        if ($('#codigo_atleta').val() == "" || $('#codigo_atleta').val() == null) {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar un atleta.");
            return false;
        }
        if ($('#codigo_articulo').val() == "" || $('#codigo_articulo').val() == null) {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar un artículo del inventario.");
            return false;
        }
        if ($('#fecha_asignacion').val() == "") {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar la fecha de asignación.");
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
    
    let fechaLimpia = fecha.split(' ')[0];

    $("#id_asignacion").val(id_asignacion);
    $("#fecha_asignacion").val(fechaLimpia);
    $("#codigo_atleta").val(codigo_atleta).trigger('change');
    
    if ($(`#codigo_articulo option[value='${codigo_articulo}']`).length === 0) {
        $("#codigo_articulo").append(new Option("Artículo Actual (Mantenido)", codigo_articulo, true, true));
    }
    $("#codigo_articulo").val(codigo_articulo).trigger('change');
    
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
    
    comboAtleta.find('option:not(:first)').remove();
    comboEquipo.find('option:not(:first)').remove();

    if (atletas && atletas.length > 0) {
        atletas.forEach(a => {
            // Ajustado para leer correctamente las propiedades físicas de tu tabla Atletas
            let idAtletaVal = a.codigo_atleta || a.id_atleta;
            let pNombre = a.p_nombre || a.nombres || '';
            let pApellido = a.p_apellidos || a.apellidos || '';
            
            if(a.estatus == 1) {
                comboAtleta.append(`<option value="${idAtletaVal}">${pNombre} ${pApellido} (Código: ${idAtletaVal})</option>`);
            }
        });
    }

    if (equipos && equipos.length > 0) {
        equipos.forEach(e => {
            let nombreMostrar = e.nombre_catalogo || e.articulo || e.nombre || ("Artículo " + e.codigo_articulo);
            let codigoClub = e.codigo_club ? ` | Cód: ${e.codigo_club}` : "";
            comboEquipo.append(`<option value="${e.codigo_articulo}">${nombreMostrar}${codigoClub}</option>`);
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
                crearConsulta(respuesta);
                return;
            }
            
            try {
                var lee = typeof respuesta === 'object' ? respuesta : JSON.parse(respuesta.substring(respuesta.indexOf('{')));

                if (lee.accion == "MultiConsulta") {
                    poblarCombos(lee.atletas, lee.equipos);
                } else if (lee.accion == "incluir" || lee.accion == "modificar" || lee.accion == "exito") {
                    consultar();
                    MultiConsulta();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Operación Exitosa", lee.mensaje);
                } else if (lee.accion == "error") {
                    muestraMensaje("error", 3000, "Error", lee.mensaje || lee.codigo);
                }
            } catch (e) {
                console.error("Error procesando JSON", e, respuesta);
            }
        },
        error: function (request, status, err) {
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
    $("#btn_guardar").data("accion", "incluir");
    $("#btn_guardar").text("Confirmar Préstamo");
}