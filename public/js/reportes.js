const graficos = {};
const Datos = {};
let cargandoFiltros = false;

// Variables para abortar peticiones previas de cada gráfico si se hacen múltiples clics rápidos
const peticionesActivas = {
    atletas: null,
    recaudacion: null,
    inventario: null,
    rendimiento: null
};
Validacion('filtro_desde', /^[0-9\/]*$/, /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/, 'Formato: dd/mm/yyyy');
Validacion('filtro_hasta', /^[0-9\/]*$/, /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/, 'Formato: dd/mm/yyyy');
Validacion('filtro_inv_desde', /^[0-9\/]*$/, /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/, 'Formato: dd/mm/yyyy');
Validacion('filtro_inv_hasta', /^[0-9\/]*$/, /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/, 'Formato: dd/mm/yyyy');

$(document).ready(function () {
    // 0. Inicializar select2 en todos los selectores
    $('.select').select2({
        width: '100%',
        placeholder: "Seleccione una opción"
    });

    // Validaciones para inputs de fecha (ahora usando flatpickr y la función Validacion)


    // 1. Cargar filtros globales (MultiConsulta) al iniciar
    cargarFiltrosIniciales();

    // 2. Evento para generar PDF
    $('.btn-generar').on('click', function () {
        const tipo = $(this).data("tipo");
        confirmar('¿Está seguro que quiere generar este reporte PDF?', function (confirmado) {
            if (confirmado) {
                abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                var datos = new FormData();
                datos.append('accion', 'generar');
                datos.append('tipo_reporte', tipo);

                if (graficos[tipo] !== undefined && graficos[tipo] !== null) {
                    datos.append('grafico_img', graficos[tipo].toBase64Image());
                }

                datos.append('datos_json', JSON.stringify(Datos[tipo] || []));
                enviaAjax(datos);
            }
        });
    });

    // 3. Eventos 'change' para recalcular gráficos cuando se modifiquen los filtros
    $(document).on('change', '#filtro_categoria, #filtro_genero, #filtro_retirados', function () {
        if (!cargandoFiltros) filtrarReporte('atletas');
    });

    $(document).on('change', '#filtro_moneda, #filtro_concepto, #filtro_desde, #filtro_hasta', function () {
        if (!cargandoFiltros) filtrarReporte('recaudacion');
    });

    $(document).on('change', '#filtro_cat_inventario, #filtro_estado_fisico, #filtro_inv_desde, #filtro_inv_hasta', function () {
        if (!cargandoFiltros) filtrarReporte('inventario');
    });

    $(document).on('change', '#filtro_atleta, #filtro_temporada', function () {
        if (!cargandoFiltros) filtrarReporte('rendimiento');
    });
});

function cargarFiltrosIniciales() {
    let datosFiltros = new FormData();
    datosFiltros.append('accion', 'MultiConsulta');
    enviaAjax(datosFiltros);
}

function filtrarReporte(tipoReporte) {
    if (peticionesActivas[tipoReporte] !== null) {
        peticionesActivas[tipoReporte].abort();
        peticionesActivas[tipoReporte] = null;
    }

    var datos = new FormData();
    datos.append('accion', 'consultar');
    datos.append('tipo_reporte', tipoReporte);

    if (tipoReporte === 'recaudacion') {
        datos.append('moneda', $('#filtro_moneda').val() || 'todos');
        datos.append('concepto', $('#filtro_concepto').val() || 'todos');
        datos.append('fecha_desde', $('#filtro_desde').val() || '');
        datos.append('fecha_hasta', $('#filtro_hasta').val() || '');
    } else if (tipoReporte === 'inventario') {
        datos.append('categoria_inventario', $('#filtro_cat_inventario').val() || 'todos');
        datos.append('estado_fisico', $('#filtro_estado_fisico').val() || 'todos');
        datos.append('fecha_desde', $('#filtro_inv_desde').val() || '');
        datos.append('fecha_hasta', $('#filtro_inv_hasta').val() || '');
    } else if (tipoReporte === 'rendimiento') {
        datos.append('atleta', $('#filtro_atleta').val() || 'todos');
        datos.append('torneo', $('#filtro_temporada').val() || 'todos');
    } else if (tipoReporte === 'atletas') {
        datos.append('categoria', $('#filtro_categoria').val() || 'todos');
        datos.append('genero', $('#filtro_genero').val() || 'todos');
        datos.append('incluir_retirados', $('#filtro_retirados').is(':checked') ? '1' : '0');
    }

    let xhr = enviaAjax(datos);
    peticionesActivas[tipoReporte] = xhr;

    xhr.always(function () {
        if (peticionesActivas[tipoReporte] === xhr) {
            peticionesActivas[tipoReporte] = null;
        }
    });
}

function enviaAjax(datos) {
    var token = $('meta[name="csrf-token"]').attr('content');

    return $.ajax({
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
            try {
                var lee = (typeof respuesta === 'string') ? JSON.parse(respuesta) : respuesta;

                if (lee.accion == "MultiConsulta") {
                    cargandoFiltros = true;

                    construirSelect('filtro_categoria', lee.categorias, 'codigo_categoria', 'nombre', 'Todas las Categorías');
                    construirSelect('filtro_moneda', lee.monedas, 'codigo_moneda', 'abreviatura', 'Todas las Monedas');
                    construirSelect('filtro_concepto', lee.conceptos, 'codigo_concepto', 'nombre', 'Todos los Conceptos');

                    if (lee.categorias_catalogo) {
                        construirSelect('filtro_cat_inventario', lee.categorias_catalogo, 'id_categoria', 'nombre', 'Todas las Categorías');
                    }

                    if (lee.atletas) { 
                        construirSelect('filtro_atleta', lee.atletas, 'codigo_atleta', 'nombre', 'Todos los Atletas');
                    }
                    if (lee.torneos) { 
                        construirSelect('filtro_temporada', lee.torneos, 'codigo_torneo', 'nombre', 'Todos los Torneos');
                    }

                    cargandoFiltros = false; 

                    // Iniciar la carga de todos los gráficos por primera vez
                    filtrarReporte('atletas');
                    filtrarReporte('recaudacion');
                    filtrarReporte('inventario');
                    filtrarReporte('rendimiento');
                }
                else if (lee.accion == "consultar") {
                    Datos[lee.tipo_reporte] = lee.datos;
                    cargarGraficoReporte(lee.datos, lee.tipo_reporte);
                }
                else if (lee.accion == "reporte") {
                    cerrarAlertaEspara();
                    muestraMensaje("success", 1000, "Creado Exitosamente", 'Se ha generado el reporte');
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
                    cerrarAlertaEspara();
                    muestraMensaje("error", 2000, "Error", lee.mensaje);
                }
            } catch (e) {
                console.error("Error en parsing JSON:", e);
                alert("Error en procesamiento de datos " + e.name);
            }
        },
        error: function (request, status, err) {
            if (request.statusText === 'abort') return;
            cerrarAlertaEspara();
            muestraMensaje("error", 2000, "Error", "ERROR: " + request.statusText);
        }
    });
}

function construirSelect(idSelect, datos, campoId, campo1, textoPorDefecto) {
    var select = $('#' + idSelect);
    
    if (select.hasClass("select2-hidden-accessible")) {
        select.select2('destroy');
    }
    
    select.empty();

    if (textoPorDefecto) {
        select.append(`<option value="todos" selected>${textoPorDefecto}</option>`);
    } else {
        select.append('<option value="" disabled selected>Seleccione una opción</option>');
    }

    if (datos && Array.isArray(datos)) {
        datos.forEach(dato => {
            var linea = `<option value="${dato[campoId]}">${escapeHTML(String(dato[campo1]))}</option>`;
            select.append(linea);
        });
    }
    
    select.select2({
        width: '100%',
        placeholder: "Seleccione una opción"
    });
}

function escapeHTML(texto) {
    var caracteres = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(texto).replace(/[&<>"']/g, m => caracteres[m]);
}

function cargarGraficoReporte(datosServidor, tipoReporte) {
    const canvasId = 'chart_' + tipoReporte;
    
    if (graficos[tipoReporte]) {
        graficos[tipoReporte].destroy();
        graficos[tipoReporte] = null;
    }
    
    $('#' + canvasId).remove();
    // Lo agregamos nuevamente al DOM para asegurarnos que ChartJS dibuje sobre un canvas limpio
    if (tipoReporte === 'atletas') {
        $('.card_perfil').eq(0).find('.canvas_reporte').append(`<canvas id="${canvasId}"></canvas>`);
    } else if (tipoReporte === 'recaudacion') {
        $('.card_perfil').eq(1).find('.canvas_reporte').append(`<canvas id="${canvasId}"></canvas>`);
    } else if (tipoReporte === 'inventario') {
        $('.card_perfil').eq(2).find('.canvas_reporte').append(`<canvas id="${canvasId}"></canvas>`);
    } else if (tipoReporte === 'rendimiento') {
        $('.card_perfil').eq(3).find('.canvas_reporte').append(`<canvas id="${canvasId}"></canvas>`);
    }

    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    let data = {};
    let opcionesScales = {};

    if (tipoReporte === 'recaudacion') {
        const etiquetas = datosServidor.map(item => `${item.concepto} (${item.moneda})`);
        const cargado = datosServidor.map(item => parseFloat(item.total_cargado) || 0);
        const recaudado = datosServidor.map(item => parseFloat(item.total_recaudado) || 0);

        data = {
            labels: etiquetas,
            datasets: [
                {
                    label: 'Total Cargado',
                    data: cargado,
                    backgroundColor: 'rgba(255, 64, 64, 0.85)',
                    borderColor: 'rgba(255, 64, 64, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Total Recaudado',
                    data: recaudado,
                    backgroundColor: 'rgba(40, 167, 69, 0.85)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                }
            ]
        };

        opcionesScales = {
            x: { stacked: false },
            y: { stacked: false, grace: '10%' }
        };

    } else if (tipoReporte === 'inventario') {
        const etiquetas = datosServidor.map(item => item.articulo);

        data = {
            labels: etiquetas,
            datasets: [
                {
                    label: 'Uso Activo',
                    data: datosServidor.map(item => parseInt(item.uso_activo) || 0),
                    backgroundColor: 'rgba(0, 123, 255, 0.85)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Devuelto (Buen Estado)',
                    data: datosServidor.map(item => parseInt(item.devuelto_bueno) || 0),
                    backgroundColor: 'rgba(40, 167, 69, 0.85)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Devuelto (Estado Medio)',
                    data: datosServidor.map(item => parseInt(item.devuelto_medio) || 0),
                    backgroundColor: 'rgba(255, 193, 7, 0.85)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Devuelto (Mal Estado)',
                    data: datosServidor.map(item => parseInt(item.devuelto_malo) || 0),
                    backgroundColor: 'rgba(255, 64, 64, 0.85)',
                    borderColor: 'rgba(255, 64, 64, 1)',
                    borderWidth: 1
                }
            ]
        };

        opcionesScales = {
            x: { stacked: true, grace: '10%' },
            y: { stacked: true }
        };

    } else if (tipoReporte === 'rendimiento') {
        const etiquetas = datosServidor.map(item => `${item.atleta} (${item.torneo})`);

        data = {
            labels: etiquetas,
            datasets: [
                {
                    label: 'Goles',
                    data: datosServidor.map(item => parseInt(item.total_goles) || 0),
                    backgroundColor: 'rgba(255, 159, 64, 0.85)',
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Asistencias',
                    data: datosServidor.map(item => parseInt(item.total_asistencias) || 0),
                    backgroundColor: 'rgba(153, 102, 255, 0.85)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }
            ]
        };

        opcionesScales = {
            x: { stacked: false },
            y: { stacked: false, grace: '10%' }
        };

    } else {
        const etiquetas = datosServidor.map(item => item.categoria);
        data = {
            labels: etiquetas,
            datasets: [
                { label: 'Atletas Masc. (Activos)', data: datosServidor.map(item => parseInt(item.masc_activos) || 0), backgroundColor: 'rgba(0, 123, 255, 0.8)', stack: 'Masculino' },
                { label: 'Atletas Masc. (Retirados)', data: datosServidor.map(item => parseInt(item.masc_retirados) || 0), backgroundColor: 'rgba(0, 123, 255, 0.4)', stack: 'Masculino' },
                { label: 'Atletas Fem. (Activos)', data: datosServidor.map(item => parseInt(item.fem_activos) || 0), backgroundColor: 'rgba(40, 167, 69, 0.8)', stack: 'Femenino' },
                { label: 'Atletas Fem. (Retirados)', data: datosServidor.map(item => parseInt(item.fem_retirados) || 0), backgroundColor: 'rgba(40, 167, 69, 0.4)', stack: 'Femenino' }
            ]
        };

        opcionesScales = {
            x: { stacked: false },
            y: { stacked: true, grace: '10%' }
        };
    }

    const config = {
        type: 'bar',
        data: data,
        plugins: [ChartDataLabels],
        options: {
            indexAxis: tipoReporte === 'inventario' ? 'y' : 'x', 
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                datalabels: {
                    anchor: 'center',
                    align: 'center',
                    color: '#ffffff',
                    font: { weight: 'bold', size: 11 },
                    formatter: function (value) {
                        if (tipoReporte === 'recaudacion') {
                            return value > 0 ? value.toLocaleString('es-VE', { minimumFractionDigits: 2 }) : '';
                        }
                        return value > 0 ? value : '';
                    }
                }
            }
        },
        scales: opcionesScales
    };

    graficos[tipoReporte] = new Chart(ctx, config);
}