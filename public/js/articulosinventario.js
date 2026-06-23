let timerBusqueda;

$(document).ready(function () {
    cargarCombos();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    
    $('#busqueda').off('keyup').on('keyup', function () {
        clearTimeout(timerBusqueda);
        let val = $(this).val();
        timerBusqueda = setTimeout(() => {
            consultar();
        }, 500);
    });

    $("#btn_nuevo").on("click", function () {
        limpia();
        $("#f")[0].reset();
        $("#codigo_articulo").val('');
        $("#titulo_modal").text("Registrar Nuevo Artículo");
        $("#btn_guardar").text("Guardar").attr("data-accion", "incluir");
        abrirModal(); 
    });

    $('#btn_guardar').on('click', function () {
        if ($('#id_catalogo').val() === null || $('#id_estado').val() === null) {
            muestraMensaje("error", 2000, "Validación", "Seleccione el artículo y su estado físico.");
            return false;
        }

        let datos = new FormData($('#f')[0]);
        let accion = $(this).attr('data-accion');
        datos.append('accion', accion);
        
        let textoConfirmacion = accion === "incluir" ? '¿Registrar este artículo en el inventario?' : '¿Guardar los cambios?';
        confirmar(textoConfirmacion, function(confirma) {
            if (confirma) enviaAjax(datos);
        });
    });

    $('#generar').on('click', function () {
        // Ajusta la ruta a como la manejes en tu enrutador
        window.open('?url=Reportes/ArticulosInventario', '_blank'); 
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            { element: '#busqueda', popover: { title: 'Búsqueda', description: 'Filtra el inventario.' } },
            { element: '#btn_nuevo', popover: { title: 'Registrar', description: 'Añade un nuevo artículo físico al inventario.' } },
            { element: '#generar', popover: { title: 'Reporte', description: 'Descarga un PDF con el estado del inventario.' } },
            { element: '#resultadoconsulta', popover: { title: 'Inventario', description: 'Haz clic en un artículo para ver sus unidades individuales.' } }
        ];
        if (typeof iniciarTourConPasos === 'function') iniciarTourConPasos(pasos).start();
    });
});

function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}

function cargarCombos() {
    let datos = new FormData();
    datos.append('accion', 'cargar_combos');
    enviaAjax(datos);
}

function editar(id, catalogo, estado) {
    limpia();
    $("#f")[0].reset();
    $("#titulo_modal").text("Modificar Artículo");
    $("#btn_guardar").text("Modificar").attr("data-accion", "modificar");
    
    $("#codigo_articulo").val(id);
    $("#id_catalogo").val(catalogo).trigger('change');
    $("#id_estado").val(estado).trigger('change');
    
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

                if (lee.accion == "cargar_combos") poblarCombos(lee.catalogos, lee.estados);
                else if (lee.accion == "exito" || lee.accion == "eliminar") {
                    cerrarModal(); 
                    consultar();
                    muestraMensaje("success", 3000, "Éxito", lee.mensaje);
                } 
                else if (lee.accion == "error") muestraMensaje("error", 4000, "Error", lee.mensaje);
            } catch (e) {
                console.error(e);
            }
        }
    });
}