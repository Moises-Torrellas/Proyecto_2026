'use strict';

// =====================================================================
// VARIABLES GLOBALES
// =====================================================================
let timerBusqueda;
let timerAtletas;
let poolAtletas = [];
let atletasSeleccionados = [];
const token = $('meta[name="csrf-token"]').attr('content');

// =====================================================================
// INICIALIZACIÓN Y EVENTOS PRINCIPALES
// =====================================================================
$(document).ready(function () {
    inicializarPaginador();

    // Configuración inicial de componentes UI
    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");

    $('#categoria').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.modal_contenedor'),
    });

    // -----------------------------------------------------------------
    // EVENTOS DE FORMULARIO (Procesar, Incluir, Generar, Asignar)
    // -----------------------------------------------------------------
    $('#proceso').on('click', function () {
        const accion = $(this).data("accion");

        if (accion === "incluir" || accion === "modificar") {
            if (!validarEnvio(accion)) return;

            const mensaje = accion === "incluir" ? '¿Está seguro que quiere registrar este equipo?' : '¿Está seguro que quiere modificar este equipo?';
            
            confirmar(mensaje, function (confirmado) {
                if (confirmado) {
                    let datos = new FormData($('#f')[0]);
                    datos.append('accion', accion);
                    
                    // Adjuntar payload de atletas seleccionados al FormData
                    atletasSeleccionados.forEach(a => datos.append('atletas[]', a.id));
                    enviaAjax(datos);
                }
            });
        } else if (accion === "generar") {
            confirmar('¿Está seguro que quiere generar un reporte?', function (confirmado) {
                if (confirmado) {
                    abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                    let datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    enviaAjax(datos);
                }
            });
        }
    });

    // -----------------------------------------------------------------
    // APERTURA DE MODALES
    // -----------------------------------------------------------------
    $("#incluir").on("click", function () {
        limpia();
        limpia_Tablas();
        prepararModalPrincipal("incluir", "Registrar Equipo", true);
        $('#categoria').val(null).trigger('change');
    });

    $("#generar").on("click", function () {
        limpia();
        prepararModalPrincipal("generar", "Generar Reporte", false);
    });

    $("#asignar").on("click", function () {
        $("#buscar_atleta_modal").val("");
        cargarAtletasModal();
        abrirModalSecundario();
    });

    // -----------------------------------------------------------------
    // TOUR DE AYUDA (Driver.js / similar)
    // -----------------------------------------------------------------
    $('#ayuda').on('click', function () {
        const pasos = [
            { element: '#busqueda', popover: { title: 'Barra de Búsqueda', description: 'Aquí puedes buscar al equipo que necesites.', position: 'bottom' } },
            { element: '#incluir', popover: { title: 'Nuevo Equipo', description: 'Abre un modal para ingresar un equipo nuevo.', position: 'bottom' } },
            { element: '#generar', popover: { title: 'Generar Reportes', description: 'Abre un modal para generar un reporte en PDF.', position: 'left' } },
            { element: '#resultadoconsulta', popover: { title: 'Equipos Registrados', description: 'Muestra todos los equipos registrados.', position: 'top' } },
            { element: '#cbt_v', popover: { title: 'Modificar Equipo', description: 'Abre un modal para modificar el equipo seleccionado.', position: 'left' } },
            { element: '#cbt_r', popover: { title: 'Eliminar Equipo', description: 'Elimina el equipo seleccionado.', position: 'left' } },
            { element: '#rowsPerPage', popover: { title: 'Registros Deseados', description: 'Selecciona la cantidad de registros a mostrar.', position: 'top' } },
            { element: '#botonera', popover: { title: 'Cambiar de Página', description: 'Botones para la paginación.', position: 'top' } },
            { element: '#cantidad', popover: { title: 'Cantidad', description: 'Muestra la cantidad total de equipos.', position: 'top' } }
        ];
        iniciarTourConPasos(pasos).start();
    });

    // -----------------------------------------------------------------
    // EVENTOS DE BÚSQUEDA (Debounce corregido)
    // -----------------------------------------------------------------
    $('#busqueda').on('keyup', function() {
        clearTimeout(timerBusqueda);
        const valor = $(this).val();
        timerBusqueda = setTimeout(function () {
            let datos = crearFormData('consultar');
            if (valor) datos.append('filtro', valor);
            enviaAjax(datos);
        }, 500);
    });
    
    $("#buscar_atleta_modal").on("keyup", function () {
        clearTimeout(timerAtletas);
        const valor = $(this).val() || '';
        timerAtletas = setTimeout(() => cargarAtletasModal(valor), 400);
    });

    // -----------------------------------------------------------------
    // INTERACCIÓN CON TABLAS (DELEGACIÓN DE EVENTOS)
    // -----------------------------------------------------------------
    $(document).on("click", ".fila_seleccionar_atleta", function (e) {
        if ($(e.target).hasClass('check_atleta_modal') || $(e.target).hasClass('custom-checkbox')) return;
        const $checkbox = $(this).find(".check_atleta_modal");
        $checkbox.prop("checked", !$checkbox.is(":checked")).trigger("change");
    });

    $(document).on("change", ".check_atleta_modal", actualizarCheckboxMaestro);

    $("#check_todos_atletas").on("change", function () {
        $(".check_atleta_modal").prop("checked", $(this).is(":checked"));
    });

    $(document).on("click", ".btn_eliminar_atleta", function () {
        const idEliminar = parseInt($(this).data("id"));
        atletasSeleccionados = atletasSeleccionados.filter(a => a.id !== idEliminar);
        $(`#fila_seleccionado_${idEliminar}`).remove();

        if (atletasSeleccionados.length === 0) renderizarFilaVaciaPrincipal();
    });

    // -----------------------------------------------------------------
    // BOTONES DE ACCIÓN SECUNDARIA (Manejo de Selección)
    // -----------------------------------------------------------------
    $("#listo").on("click", function () {
        $("#tabla_Atletas tr").each(function () {
            const iden = parseInt($(this).data("id"));
            const estaSeleccionado = $(this).find(".check_atleta_modal").is(":checked");

            if (estaSeleccionado) {
                if (!atletasSeleccionados.some(a => a.id === iden)) {
                    const atletaInfo = poolAtletas.find(a => a.id === iden);
                    if (atletaInfo) atletasSeleccionados.push(atletaInfo);
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

    $("#limpiar").on("click", function () {
        atletasSeleccionados = [];
        renderizarFilaVaciaPrincipal();
    });
});

// =====================================================================
// FUNCIONES DE CONSULTA Y ENVÍO (AJAX)
// =====================================================================
function consultar() {
    enviaAjax(crearFormData('consultar'));
}



function buscar(id) {
    let datos = crearFormData('buscar');
    datos.append('id', id);
    enviaAjax(datos);
}

function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar este registro?', function (confirmado) {
        if (confirmado) {
            let datos = crearFormData('eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}

function enviaAjax(datos) {
    $.ajax({
        async: true,
        url: "", 
        type: "POST",
        contentType: false,
        data: datos,
        processData: false,
        cache: false,
        timeout: 120000,
        beforeSend: function (request) {
            request.setRequestHeader("X-CSRF-TOKEN", token);
        },
        success: function (respuesta) {
            // Renderizado de vistas parciales en formato HTML
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta);
                return;
            }

            try {
                let lee = typeof respuesta === 'string' ? JSON.parse(respuesta) : respuesta;
                procesarAccionAjax(lee);
            } catch (e) {
                console.error('Error parseando JSON:', e, '\nRespuesta:', respuesta);
                muestraMensaje("error", 3000, "Error", "Respuesta inesperada del servidor.");
            }
        },
        error: function (request, status, err) {
            const msjError = status === "timeout" ? "Servidor ocupado, intente de nuevo" : `ERROR: <br/>${status} ${err}`;
            muestraMensaje("error", 2000, "Error de Conexión", msjError);
        }
    });
}

// =====================================================================
// FUNCIONES DE UTILIDAD Y RENDERIZADO
// =====================================================================
function crearFormData(accion) {
    let datos = new FormData();
    datos.append('accion', accion);
    return datos;
}

function procesarAccionAjax(lee) {
    const acciones = {
        buscar: () => modificar(lee.datos),
        reporte: () => {
            cerrarAlertaEspara();
            cerrarModal();
            muestraMensaje("success", 1000, "Creado Exitosamente", 'Se ha generado el reporte');
            setTimeout(() => window.open(lee.archivo, '_blank'), 1000);
        },
        error: () => muestraMensaje("error", 2000, "Error", lee.mensaje)
    };

    if (["incluir", "modificar", "eliminar"].includes(lee.accion)) {
        consultar();
        if (lee.accion !== "eliminar") {
            limpia();
            cerrarModal();
        }
        let titulo = lee.accion.charAt(0).toUpperCase() + lee.accion.slice(1) + " Exitosa";
        muestraMensaje("success", 2000, titulo, lee.mensaje);
        return;
    }

    if (acciones[lee.accion]) acciones[lee.accion]();
}

function validarEnvio(proceso) {
    if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, $("#nombre"), $("#nombre_spam"), "Solo letras entre 3 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error de Validación", "Tiene que ingresar un nombre válido.");
        return false;
    }
    return true;
}

function prepararModalPrincipal(accion, titulo, mostrarNombre) {
    $("#proceso").data("accion", accion).text(titulo);
    $("#titulo_modal").text(titulo);
    $('#nombre').closest('.colum').toggle(mostrarNombre);
    abrirModal();
}

function modificar(datos) {
    prepararModalPrincipal("modificar", "Modificar Equipo", true);

    const equipo = datos[0];
    const idEquipo = equipo.id_equipos;
    const idCategoria = (equipo.id_categorias ?? equipo.id_categoria ?? "");

    $('#id').val(idEquipo);
    $('#nombre').val(equipo.nombre);
    $('#categoria').val(idCategoria).trigger('change');

    cargarAtletasAsignadosEquipo(idEquipo, renderizarModalPrincipal);
}

function crearConsulta(htmlRecibido) {
    $('#resultadoconsulta').html(htmlRecibido);
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

function construirSelect(idSelect, datos, campoId, campo1, campo2 = null, campo3 = null) {
    const $select = $('#' + idSelect).empty().append('<option value="" selected disabled>Seleccione una opción</option>');
    const escapeHTML = (str) => String(str).replace(/[&<>'"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c] || c));

    if (Array.isArray(datos)) {
        const opcionesHTML = datos.map(dato => {
            let texto = escapeHTML(dato[campo1]);
            let attr = "";
            
            // Atributos de metadatos limpios, la validación de coherencia queda del lado del servidor
            if (idSelect === 'categoria' && campo2 && campo3) {
                texto += ` (${dato[campo2]} a ${dato[campo3]} años)`;
                attr = `data-min="${dato[campo2]}" data-max="${dato[campo3]}"`;
            }
            return `<option value="${dato[campoId]}" ${attr}>${texto}</option>`;
        }).join('');
        
        $select.append(opcionesHTML);
    }
    $select.trigger('change');
}

// =====================================================================
// MANEJO DE RENDERS (Cumpliendo reglas de estilo fuera del JS)
// =====================================================================
function cargarAtletasAsignadosEquipo(idEquipo, onDone) {
    let datos = crearFormData('consultarAtletasAsignadosEquipo');
    datos.append('id', idEquipo);

    $.ajax({
        url: "",
        type: "POST",
        data: datos,
        processData: false,
        contentType: false,
        headers: { "X-CSRF-TOKEN": token },
        success: function (respuesta) {
            try {
                let lee = typeof respuesta === 'string' ? JSON.parse(respuesta) : respuesta;
                atletasSeleccionados = (lee.accion === 'consultarAtletasAsignadosEquipo' && Array.isArray(lee.datos)) ? lee.datos : [];
            } catch (e) {
                atletasSeleccionados = [];
            }
            if (typeof onDone === 'function') onDone();
        },
        error: function () {
            atletasSeleccionados = [];
            if (typeof onDone === 'function') onDone();
        }
    });
}

function cargarAtletasModal(filtro = '') {
    let datos = crearFormData('consultarAtletasModal');
    datos.append('filtro', filtro);

    $.ajax({
        url: "",
        type: "POST",
        data: datos,
        processData: false,
        contentType: false,
        headers: { "X-CSRF-TOKEN": token },
        success: function (respuesta) {
            try {
                const lee = typeof respuesta === 'string' ? JSON.parse(respuesta) : respuesta;
                if (lee.accion === 'consultarAtletasModal' && Array.isArray(lee.datos)) {
                    poolAtletas = lee.datos;
                    renderizarModalSecundario(poolAtletas);
                } else if (lee.accion === 'error') {
                    muestraMensaje("error", 3000, "Error", lee.mensaje || 'Error al cargar atletas');
                }
            } catch (e) {
                muestraMensaje("error", 3000, "Error", "Respuesta inesperada del servidor.");
            }
        }
    });
}

function renderizarModalSecundario(lista) {
    const filasHTML = lista.map(atleta => {
        const checked = atletasSeleccionados.some(a => a.id === atleta.id) ? "checked" : "";
        return `
            <tr class="fila_seleccionar_atleta cursor-pointer" data-id="${atleta.id}">
                <td class="text-center align-middle prevent-click">
                    <label class="checkbox-container">
                        <input class="checkbox check_atleta_modal" type="checkbox" value="${atleta.id}" ${checked}>
                        <span class="custom-checkbox"></span>
                    </label>
                </td>
                <td>${atleta.doc_i}</td>
                <td class="font-medium">${atleta.nombre}</td>
                <td>${atleta.categoria}</td>
                <td>${atleta.posicion}</td>
            </tr>
        `;
    }).join('');

    $("#tabla_Atletas").html(filasHTML);
    actualizarCheckboxMaestro();
}

function actualizarCheckboxMaestro() {
    const todos = $(".check_atleta_modal").length;
    $("#check_todos_atletas").prop("checked", todos > 0 && todos === $(".check_atleta_modal:checked").length);
}

function renderizarFilaVaciaPrincipal() {
    $("#tabla_Atletas_Seleccionados").html('<tr><td colspan="5" class="text-center text-muted py-4">Ningún atleta seleccionado</td></tr>');
}

function renderizarModalPrincipal() {
    if (atletasSeleccionados.length === 0) {
        renderizarFilaVaciaPrincipal();
        return;
    }

    const filasHTML = atletasSeleccionados.map(atleta => `
        <tr id="fila_seleccionado_${atleta.id}">
            <td>${atleta.doc_i}</td>
            <td class="font-medium">${atleta.nombre}</td>
            <td>${atleta.categoria}</td>
            <td>${atleta.posicion}</td>
            <td class="text-center align-middle p-0">
                <div class="flex justify-center items-center min-h-11">
                    <button type="button" class="btn_t cbt_r btn_eliminar_atleta" data-id="${atleta.id}" data-tippy-content="Quitar Selección">
                        <i class="fi fi-sr-trash-xmark"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    $("#tabla_Atletas_Seleccionados").html(filasHTML);
    if (typeof tippy === "function") tippy('[data-tippy-content]');
}