$('#busqueda').off('keyup').on('keyup', busqueda);
let timerBusqueda;
function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}
function MultiConsulta() {
    let datos = new FormData();
    datos.append('accion', 'MultiConsulta'); // Nueva acción unificada
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
    
    // Con MultiConsulta basta, ya que llena el select con todos los parámetros requeridos
    MultiConsulta(); 

    // Validación de Nombre
    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");

    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este equipo?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar este equipo?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'modificar');
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "generar") {
            confirmar('¿Está seguro que quiere generar un reporte?', function (confirmado) {
                if (confirmado) {
                    abrirAlertaEspara('Se esta generando el reporte', 'Espere un momento')
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    enviaAjax(datos);
                }
            });
        }
    });

    $('#categoria').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });

    $("#incluir").on("click", function () {
        // Asegurar que el modal exista y esté visible
        if ($('#contenedor_modal').length === 0) {
            console.error('No existe #contenedor_modal');
            return;
        }
        limpia();

        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Equipo");
        $("#titulo_modal").text("Registrar Equipo");
        $('#nombre').closest('.colum').show();
        $('#categoria').val(null).trigger('change');
        abrirModal();
    });


    $("#generar").on("click", function () {
        limpia();

        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $('#nombre').closest('.colum').hide();
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar al equipo que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevo Equipo', description: 'Si pulsa aqui se abrira un modal para ingresar un equipo', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Equipos Registrados', description: 'Aqui se mostraran todos los equipos registrados.', position: 'top' }
            },
            {
                element: '#cbt_v',
                popover: { title: 'Modificar Equipo', description: 'Si pulsa aqui se abrira un modal para modificar el Equipo seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_r',
                popover: { title: 'Eliminar Equipo', description: 'Si pulsa aqui eliminara el equipo seleccionado.', position: 'left' }
            },
            {
                element: '#rowsPerPage',
                popover: { title: 'Registros Deseados', description: 'Aqui podra seleccionar la cantidad de registros que quiere que se muestren.', position: 'top' }
            },
            {
                element: '#botonera',
                popover: { title: 'Cambiar de Pagina', description: 'Botones para cambiar de página.', position: 'top' }
            },
            {
                element: '#cantidad',
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de equipos cargados.', position: 'top' }
            },
        ];

        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });
    
    $('#fecha_nac, #categoria').on('change', function () {
        // validarCategoria no existe en este módulo; evitar ReferenceError
        if (typeof validarCategoria === 'function') {
            validarCategoria();
        }
    });



});

function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}


function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar este ?', function (confirmado) {

        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}

function validarEnvio(proceso) {
    if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
        $("#nombre"), $("#nombre_spam"), "Solo letras  entre 3 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un nombre valido");
        return false;
    }

    let cat = $('#categoria').val();
    if (cat === null || cat === "" || cat === undefined) {
        muestraMensaje("error", 2000, "Error", "Debe elegir una categoría");
        return false;
    }
    
    return true;
}

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Equipo");
    $("#titulo_modal").text("Modificar Equipo");
    $('#nombre').closest('.colum').show();

    $('#id').val(datos[0].id_equipos);
    $('#nombre').val(datos[0].nombre);

    // Compatibilidad: algunos endpoints pueden devolver id_categoria (equipos) o id_categorias (join)
    const idCategoria = (datos[0].id_categorias ?? datos[0].id_categoria ?? "");
    $('#categoria').val(idCategoria).trigger('change');

    abrirModal();
}


function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');

    // Inyectamos directamente el bloque HTML estructurado que procesó el servidor
    contenedor.html(htmlRecibido);

    // Reactivamos las librerías visuales y los comportamientos estéticos
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}
function construirSelect(idSelect, datos, campoId, campo1, campo2 = null, campo3 = null) {
    var select = $('#' + idSelect);
    select.empty();
    select.append('<option value="" selected disabled>Seleccione una opción</option>');

    // Función de escape interna y segura para evitar el ReferenceError de escapeHTML
    const filtrarHTML = (texto) => {
        if (!texto) return "";
        return String(texto).replace(/[&<>'"]/g, function (caracter) {
            const mapeo = { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' };
            return mapeo[caracter] || caracter;
        });
    };

    // Validar que 'datos' sea un arreglo manejable
    if (datos && Array.isArray(datos)) {
        datos.forEach(dato => {
            let textoMostrar = "";
            let atributosExtra = ""; 

            if (idSelect === 'categoria' && campo1 && campo2 && campo3) {
                textoMostrar = `${filtrarHTML(dato[campo1])} (${dato[campo2]} a ${dato[campo3]} años)`;
                // Guardar los límites de edad en el HTML de la opción
                atributosExtra = `data-min="${dato[campo2]}" data-max="${dato[campo3]}"`;
            } else {
                textoMostrar = filtrarHTML(String(dato[campo1]));
            }

            var linea = `<option value="${dato[campoId]}" ${atributosExtra}>${textoMostrar}</option>`;
            select.append(linea);
        });
    }

    // OBLIGATORIO PARA SELECT2: Notificar a la librería que el select cambió su contenido
    select.trigger('change');
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
            // 1. SI LA ACCIÓN ES CONSULTAR, EL SERVIDOR DEVUELVE HTML (La tabla de equipos)
            // Lo manejamos aquí directamente antes de que intente parsearlo como JSON
            if (datos.get('accion') === 'consultar') {
                crearConsulta(respuesta);
                return;
            }

            // Validar respuestas HTML inesperadas en otras acciones
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                console.error('Respuesta HTML inesperada en Equipos:', respuesta);
                muestraMensaje("error", 3000, "Error", "El servidor devolvió HTML en vez de JSON. Revisa consola.");
                return;
            }

            try {
                var lee = typeof respuesta === 'string' ? JSON.parse(respuesta) : respuesta;

                if (lee.accion == "MultiConsulta") {
                    construirSelect('categoria', lee.categoria, 'id_categorias', 'nombre', 'edad_min', 'edad_max');
                } else if (lee.accion == "incluir") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                } else if (lee.accion == "eliminar") {
                    consultar();
                    muestraMensaje("success", 2000, "Eliminacion Exitosa", lee.mensaje);
                } else if (lee.accion == "modificar") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Modificacion Exitosa", lee.mensaje);
                } else if (lee.accion == "buscar") {
                    modificar(lee.datos);
                } else if (lee.accion == "reporte") {
                    cerrarAlertaEspara();
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
                    muestraMensaje("error", 2000, "Error", lee.mensaje);
                }
            } catch (e) {
                // IMPRESCINDIBLE: Imprimir el error 'e' real en la consola para saber qué falló en el JS
                console.error('Error real en la ejecución del JS interno:', e);
                console.error('Respuesta que causó el conflicto:', respuesta);
                muestraMensaje("error", 3000, "Error", "Respuesta inesperada del servidor (no JSON). Revisa la consola F12.");
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + status + " " + err);
            }
        }
    });
}
