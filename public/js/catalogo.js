$('#busqueda').off('keyup').on('keyup', busqueda);
let timerBusqueda;
let categoriasDatos = [];

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
    
    MultiConsulta();

    $("#stock_minimo").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(input);
    });

    Validacion("nombre", /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\.]*$/, /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\.]{3,50}$/, "Entre 3 y 50 caracteres", "proceso");
    Validacion("stock_minimo", /^[0-9\b]*$/, /^[0-9]+$/, "Debe ingresar un número entero", "proceso");

    $('#proceso').on('click', function () {
        let accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este artículo?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'incluir');
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "modificar") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere modificar este artículo?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'modificar');
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "generar") {
            opcionesReporte(function(formato) {
                if(typeof abrirAlertaEspara === 'function') abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                var datos = new FormData($('#f')[0]);
                datos.append('accion', 'generar');
                datos.append('formato', formato);
                enviaAjax(datos);
            });
        }
    });

    if ($.fn.select2) {
        $('#id_categoria').select2({ placeholder: "Selecciona una Categoría", allowClear: true, dropdownParent: $('.contenedor_modal') });
        $('#talla').select2({ placeholder: "Seleccione una talla (Opcional)", allowClear: true, dropdownParent: $('.contenedor_modal') });
        $('#codigo_posicion').select2({ dropdownParent: $('.contenedor_modal') });
    }

    $('#id_categoria').on('change', function() {
        let id_cat = $(this).val();
        let selectTalla = $('#talla');
        selectTalla.empty();
        selectTalla.append('<option value="">Ninguna</option>');
        
        if (id_cat) {
            let cat = categoriasDatos.find(c => c.id_categoria == id_cat);
            if (cat) {
                let opciones = [];
                if (cat.tipo_talla === 'Numerico') {
                    opciones = ['5', '6', '7', '8', '9', '10', '11', '12', '13', '14'];
                } else if (cat.tipo_talla === 'Letras') {
                    opciones = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
                } else if (cat.tipo_talla === 'Categorico') {
                    opciones = ['Infantil', 'Junior', 'Juvenil', 'Senior', 'Master'];
                }
                
                opciones.forEach(opt => {
                    selectTalla.append(`<option value="${opt.toUpperCase()}">${opt}</option>`);
                });
            }
        }
    });

    $("#incluir").on("click", function () {
        limpia();
        $('#id_catalogo').val(""); 
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Artículo");
        $("#titulo_modal").text("Nuevo Artículo");
        
        $('#nombre').closest('.colum').show();
        $('#stock_minimo').closest('.colum').show();
        $('#talla').closest('.colum').show();
        $('#codigo_posicion').closest('.colum').show();
        
        if ($.fn.select2) {
            $('#id_categoria').val(null).trigger('change');
            $('#talla').val(null).trigger('change');
            $('#codigo_posicion').val("").trigger('change');
        }
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Reporte de Catálogo");
        
        $('#nombre').closest('.colum').hide();
        $('#stock_minimo').closest('.colum').hide();
        $('#talla').closest('.colum').show();
        $('#codigo_posicion').closest('.colum').hide();

        if ($.fn.select2) {
            $('#id_categoria').val(null).trigger('change');
        }
        
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            { element: '#busqueda', popover: { title: 'Barra de Búsqueda', description: 'Aquí puedes buscar artículos por su nombre, categoría o talla.', position: 'bottom' } },
            { element: '#incluir', popover: { title: 'Nuevo Artículo', description: 'Pulsa aquí para registrar un nuevo artículo en el catálogo.', position: 'bottom' } },
            { element: '#generar', popover: { title: 'Generar Reportes', description: 'Pulsa aquí para exportar la lista de artículos en PDF o Excel.', position: 'left' } },
            { element: '#resultadoconsulta', popover: { title: 'Catálogo', description: 'Aquí se mostrarán todos los artículos registrados.', position: 'top' } },
            { element: '.sub_item_acciones', popover: { title: 'Acciones de Artículo', description: 'Aquí podrás modificar la información del artículo o eliminarlo.', position: 'left' } },
            { element: '#rowsPerPage', popover: { title: 'Registros Deseados', description: 'Aquí podrá seleccionar la cantidad de registros que quiere que se muestren.', position: 'top' } },
            { element: '#botonera', popover: { title: 'Cambiar de Página', description: 'Botones para cambiar de página.', position: 'top' } },
            { element: '#cantidad', popover: { title: 'Cantidad', description: 'Aquí puedes ver la cantidad de artículos mostrados actualmente.', position: 'top' } }
        ];
        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });
});

function buscar(id_catalogo) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id_catalogo', id_catalogo);
    enviaAjax(datos);
}

function eliminar(id_catalogo) {
    confirmar('¿Está seguro que quiere eliminar este artículo del catálogo?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id_catalogo', id_catalogo);
            enviaAjax(datos);
        }
    });
}

function validarEnvio(proceso) {
    if (proceso !== "generar") {
        if ($('#id_categoria').val() == "" || $('#id_categoria').val() == null) {
            muestraMensaje("error", 2000, "Error", "Debe seleccionar una categoría.");
            return false;
        }
        if (validarkeyup(/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\.]{3,50}$/, $('#nombre'), $("#nombre_spam"), "Entre 3 y 50 caracteres", true)) {
            muestraMensaje("error", 2000, "Error", "Tiene que ingresar un nombre válido.");
            return false;
        }
        if (validarkeyup(/^[0-9]+$/, $('#stock_minimo'), $("#stock_minimo_spam"), "Debe ingresar un número", true)) {
            muestraMensaje("error", 2000, "Error", "Tiene que ingresar una cantidad válida para el stock mínimo.");
            return false;
        }
        if ($('#talla').val() && $('#talla').val().trim() !== "") {
            if (validarkeyup(/^[A-Za-z0-9\s\-\/]{1,20}$/, $('#talla'), $("#talla_spam"), "Máximo 20 caracteres", true)) {
                muestraMensaje("error", 2000, "Error", "El formato de la talla es inválido.");
                return false;
            }
        }
    }
    return true;
}

function modificar(datos) {
    limpia();
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Artículo");
    $("#titulo_modal").text("Modificar Catálogo");
    
    $('#nombre').closest('.colum').show();
    $('#stock_minimo').closest('.colum').show();
    $('#talla').closest('.colum').show();

    $('#id_catalogo').val(datos[0].id_catalogo || datos[0].Id_catalogo);
    $('#nombre').val(datos[0].nombre);
    $('#stock_minimo').val(datos[0].stock_minimo);
    
    let categoriaId = datos[0].id_categoria || datos[0].Id_categoria;
    $('#id_categoria').val(categoriaId);
    
    if ($.fn.select2) {
        $('#id_categoria').trigger('change');
        
        // Timeout para que id_categoria rellene el selectTalla antes de setearlo
        setTimeout(() => {
            $('#talla').val(datos[0].talla ? datos[0].talla.toUpperCase() : '').trigger('change');
            $('#codigo_posicion').val(datos[0].codigo_posicion || "").trigger('change');
        }, 100);
    } else {
        setTimeout(() => {
            $('#talla').val(datos[0].talla ? datos[0].talla.toUpperCase() : '');
            $('#codigo_posicion').val(datos[0].codigo_posicion || "");
        }, 100);
    }

    abrirModal();
}

function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    contenedor.html(htmlRecibido);

    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

function construirSelect(idSelect, datos, campoId, campo1, textoDefault = 'Seleccione una opción') {
    var select = $('#' + idSelect);
    select.empty();
    if (textoDefault !== null) {
        select.append('<option value="" selected disabled>' + textoDefault + '</option>');
    }

    datos.forEach(dato => {
        let textoMostrar = escapeHTML(String(dato[campo1]));
        var linea = `<option value="${dato[campoId]}">${textoMostrar}</option>`;
        select.append(linea);
    });
}

function escapeHTML(texto) {
    if (!texto) return '';
    var caracteres = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(texto).replace(/[&<>"']/g, m => caracteres[m]);
}

function limpia() {
    if($('#f')[0]) $('#f')[0].reset();
    if ($.fn.select2) {
        $('.select').val(null).trigger('change');
    }
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
                var lee = JSON.parse(respuesta);
                
                if (lee.accion === "MultiConsulta") {
                    categoriasDatos = lee.categorias;
                    construirSelect('id_categoria', lee.categorias, 'id_categoria', 'nombre');
                    construirSelect('codigo_posicion', lee.posiciones, 'codigo_posicion', 'nombre', null);
                    $('#codigo_posicion').prepend('<option value="" selected>Todas las posiciones</option>');
                } 
                else if (lee.accion === "incluir") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                } 
                else if (lee.accion === "eliminar") {
                    consultar();
                    muestraMensaje("success", 2000, "Eliminación Exitosa", lee.mensaje);
                } 
                else if (lee.accion === "modificar") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Modificación Exitosa", lee.mensaje);
                } 
                else if (lee.accion === "buscar") {
                    modificar(lee.datos);
                } 
                else if (lee.accion === "reporte") {
                    if(typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
                    cerrarModal();
                    muestraMensaje("success", 1000, "Reporte Generado", 'Se ha generado el reporte');
                    setTimeout(function () {
                        const enlaceFantasma = document.createElement('a');
                        enlaceFantasma.href = lee.archivo;
                        enlaceFantasma.target = '_blank';
                        document.body.appendChild(enlaceFantasma);
                        enlaceFantasma.click();
                        document.body.removeChild(enlaceFantasma);
                    }, 1000);
                } 
                else if (lee.accion === "error") {
                    if(typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
                    muestraMensaje("error", 3000, "Error", lee.mensaje);
                }
            } catch (e) {
                console.error("Respuesta del servidor:", respuesta);
                alert("Error procesando respuesta del servidor: " + e.message);
            }
        },
        error: function (request, status, err) {
            if(typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + status + ": " + err);
            }
        }
    });
}