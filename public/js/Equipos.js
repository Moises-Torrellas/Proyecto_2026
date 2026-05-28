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
        dropdownParent: $('.modal_contenedor'),
    });

    $("#incluir").on("click", function () {
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
    $("#asignar").on("click", function () {
        $("#buscar_atleta_modal").val("");
        renderizarModalSecundario(poolAtletas);
        abrirModalSecundario();
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
            // Validar respuestas HTML inesperadas en otras acciones
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta);
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

//DATOS PROEBA DE SELECCION DE ATLETAS
const poolAtletas = [
    { id: 1, doc_i: "V-25111222", nombre: "Moises Torrellas", categoria: "Sub-18", posicion: "Delantero Centro" },
    { id: 2, doc_i: "V-28456123", nombre: "Carlos Mendoza", categoria: "Sub-14", posicion: "Portero" },
    { id: 3, doc_i: "V-29123456", nombre: "Luis Rodríguez", categoria: "Sub-16", posicion: "Defensa Izquierdo" },
    { id: 4, doc_i: "V-30111222", nombre: "Andrés Pérez", categoria: "Sub-16", posicion: "Medio Centro" },
    { id: 5, doc_i: "V-31444555", nombre: "Gabriel Gómez", categoria: "Sub-18", posicion: "Extremo Derecho" }
];

let atletasSeleccionados = [];

// --- 1. RENDERIZAR ATLETAS EN EL MODAL SECUNDARIO ---
function renderizarModalSecundario(lista) {
    const $tbody = $("#tabla_Atletas");
    $tbody.empty();

    lista.forEach(atleta => {
        const yaSeleccionado = atletasSeleccionados.some(a => a.id === atleta.id);
        const checked = yaSeleccionado ? "checked" : "";

        // Adaptado rigurosamente a tu estructura de contenedor personalizado
        const fila = `
                <tr class="fila_seleccionar_atleta" data-id="${atleta.id}" style="cursor: pointer;">
                    <td style="text-align: center; vertical-align: middle;" class="prevent-click">
                        <label class="checkbox-container">
                            <input class="checkbox check_atleta_modal" type="checkbox" 
                                   id="check_registrar_${atleta.id}" 
                                   name="check_registrar[${atleta.id}]" 
                                   value="${atleta.id}" ${checked}>
                            <span class="custom-checkbox"></span>
                        </label>
                    </td>
                    <td>${atleta.doc_i}</td>
                    <td style="font-weight: 500;">${atleta.nombre}</td>
                    <td>${atleta.categoria}</td>
                    <td>${atleta.posicion}</td>
                </tr>
            `;
        $tbody.append(fila);
    });
    actualizarCheckboxMaestro();
}

// --- 2. CONTROL DE CLICS E INTERFACES ---

// Hacer clic en cualquier parte de la fila activa el checkbox
$(document).on("click", ".fila_seleccionar_atleta", function (e) {
    // Si hacen clic en el input o en el span visual del check, dejamos que el navegador actúe nativamente
    if ($(e.target).hasClass('check_atleta_modal') || $(e.target).hasClass('custom-checkbox')) return;

    const $checkbox = $(this).find(".check_atleta_modal");
    $checkbox.prop("checked", !$checkbox.is(":checked")).trigger("change");
});

// Sincronizar el estado del checkbox maestro individualmente
$(document).on("change", ".check_atleta_modal", function () {
    actualizarCheckboxMaestro();
});

function actualizarCheckboxMaestro() {
    const todos = $(".check_atleta_modal").length;
    const marcados = $(".check_atleta_modal:checked").length;
    $("#check_todos_atletas").prop("checked", todos > 0 && todos === marcados);
}

// Checkbox Maestro: Marcar o desmarcar todos los visibles
$("#check_todos_atletas").on("change", function () {
    const estado = $(this).is(":checked");
    $(".check_atleta_modal").prop("checked", estado);
});

// --- 3. BUSCADOR EN TIEMPO REAL ---
$("#buscar_atleta_modal").on("keyup", function () {
    const termino = $(this).val().toLowerCase().trim();

    const filtrados = poolAtletas.filter(atleta =>
        atleta.nombre.toLowerCase().includes(termino) ||
        atleta.doc_i.toLowerCase().includes(termino)
    );

    renderizarModalSecundario(filtrados);
});

// --- 4. BOTÓN LISTO (PERSISTENCIA ARREGLADA) ---
$("#listo").on("click", function () {

    // Recorremos las filas que se encuentran en el DOM actual (evita borrar los ocultos por filtro)
    $("#tabla_Atletas tr").each(function () {
        const $fila = $(this);
        const iden = parseInt($fila.data("id"));
        const $checkbox = $fila.find(".check_atleta_modal");

        if ($checkbox.is(":checked")) {
            if (!atletasSeleccionados.some(a => a.id === iden)) {
                const objetoAtleta = poolAtletas.find(a => a.id === iden);
                if (objetoAtleta) atletasSeleccionados.push(objetoAtleta);
            }
        } else {
            atletasSeleccionados = atletasSeleccionados.filter(a => a.id !== iden);
        }
    });

    renderizarModalPrincipal();

    if (typeof cerrarModalSecundario === "function") {
        cerrarModalSecundario();
    } else {
        $("#secundario_modal_contenedor").addClass("ocultar");
    }
});

// --- 5. RENDERIZAR TABLA EN EL MODAL PRINCIPAL ---
function renderizarModalPrincipal() {
    const $tbodyPrincipal = $("#tabla_Atletas_Seleccionados");
    $tbodyPrincipal.empty();

    if (atletasSeleccionados.length === 0) {
        $tbodyPrincipal.append('<tr><td colspan="5" style="text-align:center; color:#888;">Ningún atleta seleccionado</td></tr>');
        return;
    }

    atletasSeleccionados.forEach(atleta => {
        const fila = `
                <tr id="fila_seleccionado_${atleta.id}">
                    <td>${atleta.doc_i}</td>
                    <td style="font-weight: 500;">${atleta.nombre}</td>
                    <td>${atleta.categoria}</td>
                    <td>${atleta.posicion}</td>
                    <td style="text-align: center; vertical-align: middle; padding: 0;">
                        <div style="display: flex; justify-content: center; align-items: center; min-height: 45px;">
                            <button type="button" class="btn_t cbt_r btn_eliminar_atleta" data-id="${atleta.id}" data-tippy-content="Quitar Selección">
                                <i class="fi fi-sr-trash-xmark"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        $tbodyPrincipal.append(fila);
    });

    if (typeof tippy === "function") tippy('[data-tippy-content]');
}

// --- 6. ELIMINAR ATLETA DE LA SELECCIÓN ---
$(document).on("click", ".btn_eliminar_atleta", function () {
    const idEliminar = parseInt($(this).data("id"));

    atletasSeleccionados = atletasSeleccionados.filter(a => a.id !== idEliminar);
    $(`#fila_seleccionado_${idEliminar}`).remove();

    if (atletasSeleccionados.length === 0) {
        $("#tabla_Atletas_Seleccionados").append('<tr><td colspan="5" style="text-align:center; color:#888;">Ningún atleta seleccionado</td></tr>');
    }
});

// Limpieza general
$("#limpiar").on("click", function () {
    atletasSeleccionados = [];
    $("#tabla_Atletas_Seleccionados").empty().append('<tr><td colspan="5" style="text-align:center; color:#888;">Ningún atleta seleccionado</td></tr>');
});