$('#busqueda').off('keyup').on('keyup', busqueda);
let timerBusqueda;

function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}

function MultiConsulta() {
    let datos = new FormData();
    datos.append('accion', 'MultiConsulta'); // Acción unificada
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
    
    // Llamada unificada al cargar la pantalla
    MultiConsulta();

    // --- Filtros visuales de inputs ---
    $("#stock_minimo").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        $(this).val(input);
    });

    $("#talla").on("input", function () {
        $(this).val($(this).val().toUpperCase());
    });

    // --- Validaciones en tiempo real ---
    Validacion("nombre", /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\.]*$/, /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-\.]{3,50}$/, "Entre 3 y 50 caracteres", "proceso");
    Validacion("stock_minimo", /^[0-9\b]*$/, /^[0-9]+$/, "Debe ingresar un número entero", "proceso");
    Validacion("talla", /^[A-Z0-9\s\-\/]*$/, /^[A-Z0-9\s\-\/]{0,10}$/, "Máximo 10 caracteres permitidos", "proceso");

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
            confirmar('¿Está seguro que quiere generar un reporte del catálogo?', function (confirmado) {
                if (confirmado) {
                    // Mantenemos la alerta de espera para el PDF
                    if(typeof abrirAlertaEspara === 'function') abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    enviaAjax(datos);
                }
            });
        }
    });

    // Configuración de Select2
    if ($.fn.select2) {
        $('#id_categoria').select2({ placeholder: "Selecciona una Categoría", allowClear: true, dropdownParent: $('.contenedor_modal') });
        $('#id_posicion').select2({ placeholder: "Selecciona una Posición (Opcional)", allowClear: true, dropdownParent: $('.contenedor_modal') });
    }

    $("#incluir").on("click", function () {
        limpia();
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Artículo");
        $("#titulo_modal").text("Nuevo Artículo");
        
        // Aseguramos que se muestren los campos
        $('#nombre').closest('.colum').show();
        $('#stock_minimo').closest('.colum').show();
        $('#talla').closest('.colum').show();
        $('#id_posicion').closest('.colum').show();
        
        if ($.fn.select2) {
            $('#id_categoria').val(null).trigger('change');
            $('#id_posicion').val(null).trigger('change');
        }
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Reporte de Catálogo");
        
        // Ocultamos los campos que no sirven para el filtro del PDF
        $('#nombre').closest('.colum').hide();
        $('#stock_minimo').closest('.colum').hide();
        $('#talla').closest('.colum').hide();
        $('#id_posicion').closest('.colum').hide();

        if ($.fn.select2) $('#id_categoria').val(null).trigger('change');
        
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            { element: '#busqueda', popover: { title: 'Barra de Búsqueda', description: 'Aquí puedes buscar artículos por su nombre, categoría o talla.', position: 'bottom' } },
            { element: '#incluir', popover: { title: 'Nuevo Artículo', description: 'Pulsa aquí para registrar un nuevo artículo en el catálogo.', position: 'bottom' } },
            { element: '#generar', popover: { title: 'Generar Reportes', description: 'Pulsa aquí para exportar la lista de artículos en PDF.', position: 'left' } },
            { element: '#resultadoconsulta', popover: { title: 'Catálogo', description: 'Aquí se mostrarán todos los artículos registrados.', position: 'top' } }
        ];
        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });
});

function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}

function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar este artículo del catálogo?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
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
        if ($('#talla').val().trim() !== "") {
            if (validarkeyup(/^[A-Z0-9\s\-\/]{1,10}$/, $('#talla'), $("#talla_spam"), "Máximo 10 caracteres", true)) {
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
    $('#id_posicion').closest('.colum').show();

    $('#id').val(datos[0].id_catalogo);
    $('#nombre').val(datos[0].nombre);
    $('#stock_minimo').val(datos[0].stock_minimo);
    $('#talla').val(datos[0].talla);
    
    if ($.fn.select2) {
        $('#id_categoria').val(datos[0].id_categoria).trigger('change');
        $('#id_posicion').val(datos[0].id_posicion).trigger('change');
    }

    abrirModal();
}

function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    contenedor.html(htmlRecibido);

    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

function construirSelect(idSelect, datos, campoId, campo1) {
    var select = $('#' + idSelect);
    select.empty();
    select.append('<option value="" selected disabled>Seleccione una opción</option>');

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
        timeout: 10000,
        success: function (respuesta) {
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta);
                return;
            }
            try {
                var lee = JSON.parse(respuesta);
                
                // Mapeo unificado MultiConsulta
                if (lee.accion === "MultiConsulta") {
                    construirSelect('id_categoria', lee.categorias, 'id_categoria', 'nombre');
                    construirSelect('id_posicion', lee.posiciones, 'id_posicion', 'nombre');
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
                    // Mantenemos la lógica intacta del Reporte PDF
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