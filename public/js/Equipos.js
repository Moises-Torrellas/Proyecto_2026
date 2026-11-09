'use strict';

// =====================================================================
// VARIABLES GLOBALES
// =====================================================================
let timerBusqueda;
let poolAtletas = [];          // Atletas disponibles (base) para el selector del lote
let atletasSeleccionados = []; // Atletas agregados a la lista del equipo
const token = $('meta[name="csrf-token"]').attr('content');

// =====================================================================
// INICIALIZACIÓN Y EVENTOS PRINCIPALES
// =====================================================================
$(document).ready(function () {
    inicializarPaginador();

    // Configuración inicial de componentes UI
    Validacion("nombre", /^[A-Za-z0-9\-\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z0-9\-\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras, números, guiones y espacios (3-30 caracteres)", "proceso");

    $('#lote_atleta').select2({
        placeholder: "Seleccione un atleta...",
        width: '100%',
        dropdownParent: $('#contenedor_modal')
    });

    $('#id_equipo').select2({
        placeholder: "Seleccione un equipo",
        width: '100%',
        dropdownParent: $('#contenedor_modal')
    });

    // Pool de atletas disponibles para el selector del lote
    cargarAtletasDisponibles();

    // -----------------------------------------------------------------
    // EVENTOS DE FORMULARIO (Procesar, Incluir, Generar)
    // -----------------------------------------------------------------
    $('#proceso').on('click', function () {
        const accion = $(this).data("accion");

        if (accion === "incluir" || accion === "modificar") {
            if (!validarEnvio(accion)) return;

            const total = atletasSeleccionados.length;
            const mensaje = accion === "incluir"
                ? `¿Está seguro que quiere registrar este equipo con ${total} atleta(s)?`
                : `¿Está seguro que quiere modificar este equipo con ${total} atleta(s)?`;

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
            if (!validarEnvio(accion)) return;
            opcionesReporte(function(formato) {
                abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                let datos = new FormData($('#f')[0]);
                datos.append('accion', 'generar');
                datos.append('formato', formato);
                enviaAjax(datos);
            });
        }
    });

    // -----------------------------------------------------------------
    // APERTURA DE MODALES
    // -----------------------------------------------------------------
    $("#incluir").on("click", function () {
        limpiarModalEquipo();
        poblarSelectAtletas();
        prepararModalPrincipal("incluir", "Registrar Equipo", true);
    });

    $("#generar").on("click", function () {
        limpiarModalEquipo();
        prepararModalPrincipal("generar", "Generar Reporte", false);
    });

    // -----------------------------------------------------------------
    // MANEJO DEL LOTE DE ATLETAS (misma mecánica que Asignaciones)
    // -----------------------------------------------------------------
    $('#btn_agregar_lote_equipo').on('click', agregarAtletaLote);

    $('#btn_vaciar_lote_equipo').on('click', function () {
        vaciarLoteEquipo();
    });

    $('#cuerpo_lote_equipo').on('click', '.btn_quitar_lote_equipo', function () {
        const id = parseInt($(this).data('id'), 10);
        atletasSeleccionados = atletasSeleccionados.filter(a => a.id !== id);
        $(this).closest('.item_lote').remove();
        habilitarOpcionAtleta(id, true);
        actualizarListaLoteEquipo();
    });

    // -----------------------------------------------------------------
    // TOUR DE AYUDA (Driver.js / similar)
    // -----------------------------------------------------------------
    $('#ayuda').on('click', function () {
        const pasos = [
            { element: '#busqueda', popover: { title: 'Barra de Búsqueda', description: 'Aquí puedes buscar al equipo que necesites.', position: 'bottom' } },
            { element: '#incluir', popover: { title: 'Nuevo Equipo', description: 'Abre un modal para ingresar un equipo nuevo y agregar sus atletas a la lista.', position: 'bottom' } },
            { element: '#generar', popover: { title: 'Generar Reportes', description: 'Abre un modal para generar un reporte en PDF.', position: 'left' } },
            { element: '#resultadoconsulta', popover: { title: 'Equipos Registrados', description: 'Muestra todos los equipos registrados.', position: 'top' } },
            { element: '#resultadoconsulta .listado_contenedor_grupal:first-child .cbt_v', popover: { title: 'Modificar Equipo', description: 'Abre un modal para modificar el equipo seleccionado.', position: 'left' } },
            { element: '#resultadoconsulta .listado_contenedor_grupal:first-child .cbt_r', popover: { title: 'Eliminar Equipo', description: 'Elimina el equipo seleccionado.', position: 'left' } },
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

function escaparHtml(texto) {
    return String(texto ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
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
        cargarAtletasDisponibles();
        if (lee.accion !== "eliminar") {
            limpiarModalEquipo();
            cerrarModal();
        }
        let titulo = lee.accion.charAt(0).toUpperCase() + lee.accion.slice(1) + " Exitosa";
        muestraMensaje("success", 2000, titulo, lee.mensaje);
        return;
    }

    if (acciones[lee.accion]) acciones[lee.accion]();
}

function validarEnvio(proceso) {
    if (proceso === "generar") {
        if (!$('#id_equipo').val()) {
            muestraMensaje("error", 2000, "Error de Validación", "Debe seleccionar un equipo.");
            return false;
        }
        return true;
    }
    if (validarkeyup(/^[A-Za-z0-9\-\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, $("#nombre"), $("#nombre_spam"), "Solo letras, números, guiones y espacios (3-30 caracteres)", true)) {
        muestraMensaje("error", 2000, "Error de Validación", "Tiene que ingresar un nombre válido.");
        return false;
    }
    if (atletasSeleccionados.length === 0) {
        muestraMensaje("error", 2000, "Error de Validación", "Debe agregar al menos un atleta a la lista del equipo.");
        return false;
    }
    return true;
}

function prepararModalPrincipal(accion, titulo, mostrarNombre) {
    $("#proceso").data("accion", accion).text(accion === "modificar" ? "Guardar Cambios" : titulo);
    $("#titulo_modal").text(titulo);
    $('#row_nombre').toggle(mostrarNombre);
    $('#seccion_lote_equipo').toggle(mostrarNombre);
    $('#row_equipo_reporte').toggle(!mostrarNombre);

    abrirModal();
}

function modificar(datos) {
    limpiarModalEquipo();

    const equipo = datos[0];
    const idEquipo = equipo.id_equipos;

    $('#id').val(idEquipo);
    $('#nombre').val(equipo.nombre);

    prepararModalPrincipal("modificar", "Modificar Equipo", true);

    cargarAtletasAsignadosEquipo(idEquipo, function () {
        // Los atletas actuales del equipo pueden no estar en el pool base;
        // se agregan como opciones para poder quitarlos y volver a agregarlos.
        poblarSelectAtletas(atletasSeleccionados);
        renderizarLoteEquipo();
    });
}

function crearConsulta(htmlRecibido) {
    $('#resultadoconsulta').html(htmlRecibido);
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

/**
 * Limpieza general del formulario (main.js) + reinicio de la lista de atletas.
 */
function limpiarModalEquipo() {
    limpia();
    vaciarLoteEquipo();
}

// =====================================================================
// MANEJO DE RENDERS (Cumpliendo reglas de estilo fuera del JS)
// =====================================================================
function cargarAtletasDisponibles() {
    let datos = crearFormData('consultarAtletasModal');
    datos.append('filtro', '');

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
                    poblarSelectAtletas(atletasSeleccionados);
                } else if (lee.accion === 'error') {
                    muestraMensaje("error", 3000, "Error", lee.mensaje || 'Error al cargar atletas');
                }
            } catch (e) {
                muestraMensaje("error", 3000, "Error", "Respuesta inesperada del servidor.");
            }
        }
    });
}

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

/**
 * Reconstruye las opciones del selector de atletas a partir del pool base
 * más los atletas adicionales indicados (ej. los ya asignados al equipo).
 */
function poblarSelectAtletas(extras = []) {
    const $select = $('#lote_atleta');
    $select.find('option:not(:first)').remove();

    const vistos = new Set();
    const lista = [...poolAtletas, ...extras].filter(a => {
        const id = parseInt(a.id, 10);
        if (!id || vistos.has(id)) return false;
        vistos.add(id);
        return true;
    });

    const opcionesHTML = lista.map(a => {
        const categoria = a.categoria || 'Sin Categoría';
        const texto = `${a.nombre} - CI: ${a.doc_i} - ${categoria}`;
        const deshabilitado = atletasSeleccionados.some(s => s.id === a.id) ? 'disabled' : '';
        return `<option value="${a.id}" ${deshabilitado}>${escaparHtml(texto)}</option>`;
    }).join('');

    $select.append(opcionesHTML).val(null).trigger('change');
}

function buscarAtletaPorId(id) {
    return poolAtletas.find(a => a.id === id) || atletasSeleccionados.find(a => a.id === id) || null;
}

function agregarAtletaLote() {
    const id = parseInt($('#lote_atleta').val(), 10);
    if (!id) {
        muestraMensaje("error", 2000, "Validación", "Seleccione un atleta de la lista antes de agregarlo.");
        return;
    }

    if (atletasSeleccionados.some(a => a.id === id)) {
        muestraMensaje("warning", 2000, "Atención", "Este atleta ya fue agregado a la lista.");
        return;
    }

    const atleta = buscarAtletaPorId(id);
    if (!atleta) {
        muestraMensaje("error", 2000, "Error", "No se encontró la información del atleta seleccionado.");
        return;
    }

    atletasSeleccionados.push(atleta);

    $('#cuerpo_lote_equipo .lista_lote_vacio').remove();
    $('#cuerpo_lote_equipo').append(crearItemLoteAtleta(atleta));

    habilitarOpcionAtleta(id, false);
    $('#lote_atleta').val(null).trigger('change');

    actualizarListaLoteEquipo();

    const cuerpo = document.getElementById('cuerpo_lote_equipo');
    if (cuerpo) cuerpo.scrollTop = cuerpo.scrollHeight;
}

function crearItemLoteAtleta(atleta) {
    const posicion = atleta.posicion || 'Sin Posición';
    const categoria = atleta.categoria || 'Sin Categoría';

    return `
        <div class="item_lote" data-id="${escaparHtml(atleta.id)}">
            <div class="item_lote_icono"><i data-lucide="user"></i></div>
            <div class="item_lote_info">
                <span class="item_lote_titulo" title="${escaparHtml(atleta.nombre)}">${escaparHtml(atleta.nombre)}</span>
                <span class="item_lote_subtitulo">C.I: ${escaparHtml(atleta.doc_i)} · Posición: ${escaparHtml(posicion)}</span>
            </div>
            <div class="item_lote_etiqueta">
                Categoría: <strong>${escaparHtml(categoria)}</strong>
            </div>
            <button type="button" class="btn_t cbt_r btn_quitar_lote_equipo" data-id="${escaparHtml(atleta.id)}" title="Quitar del equipo">
                <i class="fi fi-sr-trash"></i>
            </button>
        </div>`;
}

function renderizarLoteEquipo() {
    const cuerpo = $('#cuerpo_lote_equipo').empty();
    atletasSeleccionados.forEach(a => cuerpo.append(crearItemLoteAtleta(a)));
    actualizarListaLoteEquipo();
}

function vaciarLoteEquipo() {
    atletasSeleccionados = [];
    $('#cuerpo_lote_equipo').empty();
    $('#lote_atleta option').prop('disabled', false);
    $('#lote_atleta option:first').prop('disabled', true);
    $('#lote_atleta').val(null).trigger('change');
    actualizarListaLoteEquipo();
}

function habilitarOpcionAtleta(id, habilitar) {
    const opt = $(`#lote_atleta option[value="${id}"]`);
    if (opt.length) {
        opt.prop('disabled', !habilitar);
        $('#lote_atleta').trigger('change');
    }
}

function actualizarListaLoteEquipo() {
    const total = $('#cuerpo_lote_equipo .item_lote').length;
    $('#contador_lote_equipo').text(total);
    $('#btn_vaciar_lote_equipo').prop('disabled', total === 0);

    if (total === 0) {
        $('#cuerpo_lote_equipo').html(`
            <div class="lista_lote_vacio">
                <i data-lucide="users"></i>
                Aún no hay atletas en el equipo. Elija un atleta y presione «+».
            </div>`);
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
