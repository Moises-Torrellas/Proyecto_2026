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
    MultiConsulta();

    $("#doc_i").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        if (input.length > 4) {
            input = input.substring(0, 8);
        }
        $(this).val(input);
    });

    $("#telefono").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        if (input.length > 4) {
            input = input.substring(0, 4) + '-' + input.substring(4, 11);
        }
        $(this).val(input);
    });

    Validacion("fecha_nac", /^[0-9\b-]*$/, /^\d{4}-\d{2}-\d{2}$/, "Seleccione una fecha válida", "proceso");
    Validacion("nombre", /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\b]*$/, /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,60}$/, "Solo letras, mínimo 3 caracteres", "proceso");
    Validacion("apellido", /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\b]*$/, /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,60}$/, "Solo letras, mínimo 3 caracteres", "proceso");
    Validacion("doc_i", /^[0-9\b]*$/, /^[0-9]{7,8}$/, "Mínimo 7 máximo 8 dígitos, solo números", "proceso");
    Validacion("telefono", /^[0-9\b-]*$/, /^[0-9]{4}-[0-9]{7}$/, "Formato inválido (XXXX-XXXXXXX)", "proceso");
    Validacion("direccion", /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\b,.-]*$/, /^.{5,150}$/, "Dirección muy corta o inválida", "proceso");
    Validacion("edad", /^[0-9\b]*$/, /^[0-9]{0,10}$/, "Solo numeros", "proceso");

    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este atleta?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar este atleta?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        var fotoActual = $("#proceso").data("foto_actual")
                        datos.append('foto_actual', fotoActual);
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


    $('#representante').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });

    $('#posicion').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });

    $('#categoria').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });

    $("#incluir").on("click", function () {
        limpia();

        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Atleta");
        $("#titulo_modal").text("Registrar Atleta");
        $('#fecha_nac').closest('.colum').show();
        $('#representante').closest('.colum').show();
        $('#telefono').closest('.colum').show();
        $('#direccion').closest('.colum').show();
        $('#foto').closest('.colum').show();
        $('#estatus').closest('.colum').hide().prop('disabled', true);
        $("#todos").prop('disabled', true); 
        $("#genero").val("H").trigger('change');

        $('#edad').prop('readonly', true);
        $

        $('#representante').val(null).trigger('change');
        $('#posicion').val(null).trigger('change');
        $('#categoria').val(null).trigger('change');
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();

        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        $('#fecha_nac').closest('.colum').hide();
        $('#representante').closest('.colum').hide();
        $('#telefono').closest('.colum').hide();
        $('#direccion').closest('.colum').hide();
        $('#foto').closest('.colum').hide();
        $('#estatus').closest('.colum').show().prop('disabled', false);
        $('#edad').prop('readonly', false);
        $("#todos").prop('disabled', false); 
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar al Atleta que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevo Atleta', description: 'Si pulsa aqui se abrira un modal para ingresar un nuevo Atleta', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Atletas Registrados', description: 'Aqui se mostraran todos los Atletas registrados.', position: 'top' }
            },
            {
                element: '#registro',
                popover: { title: 'Registro de un Atleta', description: 'Aqui se mostrara la informacion de un Atleta si pulsa el registro se desplegara mas informacion.', position: 'bottom' }
            },
            {
                element: '#cbt_v',
                popover: { title: 'Modificar Atletas', description: 'Si pulsa aqui se abrira un modal para modificar el Atleta seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_r',
                popover: { title: 'Eliminar Atleta', description: 'Si pulsa aqui eliminara el Atleta seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_sec',
                popover: { title: 'Generar Curriculum', description: 'Si pulsa aqui generara un curriculum del Atleta seleccionado.', position: 'left' }
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
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de representantes cargados.', position: 'top' }
            },
        ];

        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });


    // --- Lógica de validación de edad y activación de campos ---
    $("#fecha_nac").on("change", function () {
        validarEdadAtleta($(this).val());
        cargarEdad($(this).val());
    });

    // Se ejecuta cada vez que cambian estos campos
    $('#fecha_nac, #categoria').on('change', function () {
        validarCategoria();
    });

});

function validarEdadAtleta(fechaValor) {
    if (!fechaValor) return;

    const hoy = new Date();
    const nacimiento = new Date(fechaValor);

    // Edad calendario: Solo la resta de los años
    let edad = hoy.getFullYear() - nacimiento.getFullYear();

    const $doc_i = $("#doc_i");
    const $representante = $("#representante");
    const $telefono = $("#telefono");
    const $direccion = $("#direccion");
    const $btnProceso = $("#proceso");

    // 1. Regla: Mínimo 4 años
    if (edad < 4) {
        muestraMensaje("error", 3000, "Edad insuficiente", "El atleta debe tener al menos 4 años.");
        $btnProceso.prop("disabled", true).addClass("btn_bloqueado");
        return;
    } else {
        $btnProceso.prop("disabled", false).removeClass("btn_bloqueado");
    }

    // 2. Regla: Placeholder de Cédula
    if (edad < 9) {
        $doc_i.prop("disabled", true).val("").parent().addClass("campo_deshabilitado");
    } else {
        $doc_i.attr("placeholder", "Cédula del atleta");
    }

    // 3. Regla: Representante (SOLO DESHABILITAR, NO OCULTAR)
    if (edad < 18) {
        // Es menor: Requiere representante
        $representante.prop("disabled", false).parent().removeClass("campo_deshabilitado");

        // Deshabilita tlf y dirección propios
        $telefono.prop("disabled", true).val("").parent().addClass("campo_deshabilitado");
        $direccion.prop("disabled", true).val("").parent().addClass("campo_deshabilitado");
    } else {
        // Es adulto: Se deshabilita representante pero permanece visible
        $representante.prop("disabled", true).val("").parent().addClass("campo_deshabilitado");

        // Habilita tlf y dirección propios
        $telefono.prop("disabled", false).parent().removeClass("campo_deshabilitado");
        $direccion.prop("disabled", false).parent().removeClass("campo_deshabilitado");
    }
}

function cargarEdad(fecha) {
    const hoy = new Date();
    const nacimiento = new Date(fecha);
    const edad = hoy.getFullYear() - nacimiento.getFullYear();
    $('#edad').val(edad);
}

function validarEnvio(proceso) {
    // 1. Fecha de Nacimiento (Primero en tu HTML)
    if ($('#fecha_nac').val() == "" || $('#fecha_nac').val() == null) {
        muestraMensaje("error", 2000, "Error", "La fecha de nacimiento es obligatoria");
        return false;
    }

    // 2. Nombre
    if (validarkeyup(/^[A-Za-záéíóúÁÉÍÓÚñÑ\s]{3,30}$/, $("#nombre"), $("#nombre_spam"), "Solo letras entre 3 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Debe ingresar un nombre válido");
        return false;
    }

    // 3. Apellido
    if (validarkeyup(/^[A-Za-záéíóúÁÉÍÓÚñÑ\s]{3,30}$/, $('#apellido'), $("#apellido_spam"), "Solo letras entre 3 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Debe ingresar un apellido válido");
        return false;
    }

    // 4. Categoría (Select)
    if ($('#categoria').val() == "" || $('#categoria').val() == null) {
        muestraMensaje("error", 2000, "Error", "Debe elegir una categoría");
        return false;
    }

    // 5. Posición (Select)
    if ($('#posicion').val() == "" || $('#posicion').val() == null) {
        muestraMensaje("error", 2000, "Error", "Debe elegir una posición");
        return false;
    }

    // 6. Género (Select)
    if ($('#genero').val() == "" || $('#genero').val() == null) {
        muestraMensaje("error", 2000, "Error", "Debe elegir el género");
        return false;
    }

    // 7. Representante (Solo si NO está deshabilitado)
    if (!$("#representante").prop("disabled")) {
        if ($('#representante').val() == "" || $('#representante').val() == null) {
            muestraMensaje("error", 2000, "Error", "Debe seleccionar un representante");
            return false;
        }
    }

    // 8. Documento de Identidad (Cédula)

    if (!$("#doc_i").prop("disabled")) {
        if (validarkeyup(/^[0-9]{7,8}$/, $('#doc_i'), $("#doc_i_spam"), "Mínimo 7 máximo 8 dígitos", true)) {
            muestraMensaje("error", 2000, "Error", "Debe ingresar una cédula válida");
            return false;
        }
    }

    // 9. Teléfono (Solo si NO está deshabilitado)
    if (!$("#telefono").prop("disabled")) {
        if (validarkeyup(/^[0-9]{4}[-]{1}[0-9]{7}$/, $('#telefono'), $("#telefono_spam"), "Formato: 0400-0000000", true)) {
            muestraMensaje("error", 2000, "Error", "Debe ingresar un teléfono válido");
            return false;
        }
    }

    // 10. Dirección (Solo si NO está deshabilitado)
    if (!$("#direccion").prop("disabled")) {
        if ($('#direccion').val().trim().length < 5) {
            muestraMensaje("error", 2000, "Error", "La dirección debe tener al menos 5 caracteres");
            return false;
        }
    }
    if (proceso == "incluir") {
        if ($('#foto').val() == "" || $('#foto')[0].files.length === 0) {
            muestraMensaje("error", 2000, "Error", "Debe seleccionar una foto para el atleta");
            return false;
        }
    }


    if (!validarCategoria()) {
        muestraMensaje("error", 2000, "Error", "La edad del atleta debe estar dentro de la categoría elegida");
        return false;
    }
    return true;
}

function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}
function eliminar(id) {
    confirmar('¿Está seguro que quiere retirar este atleta del club?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}

function modificar(datos) {
    limpia();
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Atleta");
    $("#titulo_modal").text("Modificar Atleta");
    $('#fecha_nac').closest('.colum').show();
    $('#representante').closest('.colum').show();
    $('#telefono').closest('.colum').show();
    $('#direccion').closest('.colum').show();
    $('#foto').closest('.colum').show();
    $('#estatus').closest('.colum').hide().prop('disabled', true);
    $('#edad').prop('readonly', true);
    $("#todos").prop('disabled', true); 

    $('#fecha_nac').val(datos[0].fecha_nac);
    $('#id').val(datos[0].id_atleta);
    $('#doc_i').val(datos[0].doc_identidad);
    $('#nombre').val(datos[0].nombres);
    $('#apellido').val(datos[0].apellidos);
    $('#genero').val(datos[0].genero);
    $('#telefono').val(datos[0].telefono);
    $('#direccion').val(datos[0].direccion);
    $('#representante').val(datos[0].id_representante).trigger('change');
    $('#posicion').val(datos[0].id_posicion).trigger('change');
    $('#categoria').val(datos[0].id_categoria).trigger('change');
    $("#proceso").data("foto_actual", datos[0].foto);
    const rutaCarpeta = "img/atletas/";
    const nombreFoto = datos[0].foto ? datos[0].foto : "default.png";
    $("#foto_previa").attr("src", rutaCarpeta + nombreFoto).trigger('change');
    validarEdadAtleta(datos[0].fecha_nac);
    cargarEdad(datos[0].fecha_nac);

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

    datos.forEach(dato => {
        let textoMostrar = "";
        let atributosExtra = ""; // Variable para guardar los límites de edad

        if (idSelect === 'representante' && campo1 && campo2 && campo3) {
            textoMostrar = `${escapeHTML(dato[campo1])} ${escapeHTML(dato[campo2])} — ${dato[campo3]}`;
        }
        else if (idSelect === 'posicion' && campo1 && campo2) {
            textoMostrar = `${escapeHTML(dato[campo1])} (${escapeHTML(dato[campo2])})`;
        }
        else if (idSelect === 'categoria' && campo1 && campo2 && campo3) {
            textoMostrar = `${escapeHTML(dato[campo1])} (${dato[campo2]} a ${dato[campo3]} años)`;
            // GUARDAR LÍMITES EN LA OPCIÓN
            atributosExtra = `data-min="${dato[campo2]}" data-max="${dato[campo3]}"`;
        }
        else {
            textoMostrar = escapeHTML(String(dato[campo1]));
        }

        // Se agregan los atributosExtra a la etiqueta <option>
        var linea = `<option value="${dato[campoId]}" ${atributosExtra}>${textoMostrar}</option>`;
        select.append(linea);
    });
}

function escapeHTML(texto) {
    var caracteres = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return texto.replace(/[&<>"']/g, m => caracteres[m]);
}

function validarCategoria() {
    const fechaNac = $('#fecha_nac').val();
    const categoria = $('#categoria option:selected');

    if (!fechaNac || !categoria.val()) return;

    // Calcular edad calendario
    const anioNac = new Date(fechaNac).getFullYear();
    const anioAct = new Date().getFullYear();
    const edadCalendario = anioAct - anioNac;
    // Obtener límites desde los atributos data que guardamos antes
    const min = parseInt(categoria.data('min'));
    const max = parseInt(categoria.data('max'));
    const $btnProceso = $("#proceso");

    if (edadCalendario < min || edadCalendario > max) {
        $btnProceso.prop("disabled", true).addClass("btn_bloqueado");
        muestraMensaje("error", 2000, "Error", `El atleta tiene ${edadCalendario} años, pero la categoría solo permite de ${min} a ${max} años.`);
        return false;
    }
    $btnProceso.prop("disabled", false).removeClass("btn_bloqueado");
    return true;
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

            // 2. Procesamos respuestas de datos y lógica del formulario (JSON)
            try {
                var lee = JSON.parse(respuesta);

                if (lee.accion == "MultiConsulta") {
                    construirSelect('representante', lee.representantes, 'id_representante', 'nombre', 'apellido', 'cedula');
                    construirSelect('posicion', lee.posiciones, 'id_posicion', 'nombre', 'abreviatura');
                    construirSelect('categoria', lee.categorias, 'id_categorias', 'nombre', 'edad_min', 'edad_max');
                } else if (lee.accion == "incluir") {
                    consultar();
                    limpia();
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                } else if (lee.accion == "eliminar") {
                    consultar();
                    muestraMensaje("success", 2000, "Retiro Exitoso", lee.mensaje);
                } else if (lee.accion == "modificar") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Modificación Exitosa", lee.mensaje);
                }
                else if (lee.accion == "reporte") {
                    // 1. Cerramos la alerta de espera de inmediato
                    cerrarAlertaEspara();

                    // 2. Cerramos el modal del formulario
                    cerrarModal();

                    // 3. Mostramos el mensaje de éxito (dura 2000ms en pantalla)
                    muestraMensaje("success", 1000, "Creado Exitosamente", 'Se ha generado el reporte');
                    setTimeout(function () {
                        const enlaceFantasma = document.createElement('a');
                        enlaceFantasma.href = lee.archivo;
                        enlaceFantasma.target = '_blank';
                        document.body.appendChild(enlaceFantasma);
                        enlaceFantasma.click();
                        document.body.removeChild(enlaceFantasma);
                    }, 1000);
                } else if (lee.accion == "buscar") {
                    modificar(lee.datos);
                } else if (lee.accion == "error") {
                    muestraMensaje("error", 2000, "Error", lee.mensaje);
                }
            } catch (e) {
                console.error("Error al parsear el JSON del servidor. Respuesta recibida:", respuesta);
                alert("Error en JSON: " + e.name);
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                // Se quita el objeto 'request' de la concatenación para evitar el '[object Object]'
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + status + ": " + err);
            }
        },
        complete: function () { },
    });
}

document.getElementById('foto').addEventListener('change', function (event) {
    const archivo = event.target.files[0];
    const vistaPrevia = document.getElementById('foto_previa');

    if (archivo) {
        const reader = new FileReader();
        reader.onload = function (e) {
            vistaPrevia.src = e.target.result;
        }
        reader.readAsDataURL(archivo);
    } else {
        // Si el usuario cancela la selección, volvemos a poner la cámara
        vistaPrevia.src = '';
    }
});