let miGrafico = null;
let Datos = null;
let reporteActual = '';
let cargandoFiltros = false; // BANDERA: Evita peticiones duplicadas al rellenar selects
let ajxConsultar = null;      // BANDERA: Guarda la petición activa para poder abortarla

$(document).ready(function () {

    // Evento unificado para procesar/generar PDF
    $('#proceso').on('click', function () {
        const accion = $(this).attr("data-accion") || $(this).data("accion");
        if (accion === "generar") {
            confirmar('¿Está seguro que quiere generar este reporte PDF?', function (confirmado) {
                if (confirmado) {
                    abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                    var datos = new FormData();
                    datos.append('accion', 'generar');
                    datos.append('tipo_reporte', reporteActual);

                    if (miGrafico !== null) {
                        datos.append('grafico_img', miGrafico.toBase64Image());
                    }

                    datos.append('datos_json', JSON.stringify(Datos));
                    enviaAjax(datos);
                }
            });
        }
    });

    // Evento delegado al presionar cualquier tarjeta de reporte
    $(".btn-abrir-reporte").on("click", function () {
        limpia();
        reporteActual = $(this).data("tipo"); // 'atletas', 'recaudacion', 'inventario' o 'rendimiento'

        // LIMPIEZA INMEDIATA: Desintegra el canvas viejo para que no muestre datos anteriores
        if (miGrafico !== null) {
            miGrafico.destroy();
            miGrafico = null;
        }
        $('#barChart').remove();
        $('#contenedor_canvas').append('<canvas id="barChart"></canvas>');

        // Sincroniza tanto el data-cache de jQuery como el atributo del DOM
        $("#proceso").attr("data-accion", "generar").data("accion", "generar");
        $("#proceso").text("Generar Reporte");

        // Alternar contenedores visuales de filtros y títulos según el reporte
        if (reporteActual === 'recaudacion') {
            $("#titulo_modal").text("Reporte: Efectividad de Recaudación y Morosidad");
            $("#grupo_filtros_atletas, #grupo_filtros_inventario, #grupo_filtros_rendimiento").hide();
            $("#grupo_filtros_recaudacion").show();
        } else if (reporteActual === 'inventario') {
            $("#titulo_modal").text("Reporte: Flujo y Estado de Implementos Asignados");
            $("#grupo_filtros_atletas, #grupo_filtros_recaudacion, #grupo_filtros_rendimiento").hide();
            $("#grupo_filtros_inventario").show();
        } else if (reporteActual === 'rendimiento') {
            $("#titulo_modal").text("Reporte: Rendimiento Ofensivo");
            $("#grupo_filtros_atletas, #grupo_filtros_recaudacion, #grupo_filtros_inventario").hide();
            $("#grupo_filtros_rendimiento").show();
        } else {
            $("#titulo_modal").text("Reporte: Atletas por Categorías");
            $("#grupo_filtros_recaudacion, #grupo_filtros_inventario, #grupo_filtros_rendimiento").hide();
            $("#grupo_filtros_atletas").show();
        }

        // Carga inicial de datos para poblar selects (MultiConsulta)
        if ($('#filtro_categoria').children('option').length <= 1) {
            let datosFiltros = new FormData();
            datosFiltros.append('accion', 'MultiConsulta');
            enviaAjax(datosFiltros);
        } else {
            filtrarReporte();
        }
    });

    // Detectar cambios en filtros de atletas (Solo si no está cargando la data base)
    $(document).on('change', '#filtro_categoria, #filtro_genero, #filtro_retirados', function () {
        if (reporteActual === 'atletas' && !cargandoFiltros) filtrarReporte();
    });

    // Detectar cambios en filtros de recaudación
    $(document).on('change', '#filtro_moneda, #filtro_concepto, #filtro_desde, #filtro_hasta', function () {
        if (reporteActual === 'recaudacion' && !cargandoFiltros) filtrarReporte();
    });

    // Detectar cambios en filtros de inventario
    $(document).on('change', '#filtro_cat_inventario, #filtro_estado_fisico, #filtro_inv_desde, #filtro_inv_hasta', function () {
        if (reporteActual === 'inventario' && !cargandoFiltros) filtrarReporte();
    });

    $(document).on('change', '#filtro_atleta, #filtro_temporada', function () {
        if (reporteActual === 'rendimiento' && !cargandoFiltros) filtrarReporte();
    });

    $('#cerrar_modal').on('click', function () {
        if (miGrafico !== null) {
            miGrafico.destroy();
            miGrafico = null;
        }
    });
});

function filtrarReporte() {
    // Si ya existe una consulta ejecutándose en segundo plano, la abortamos inmediatamente
    if (ajxConsultar !== null) {
        ajxConsultar.abort();
        ajxConsultar = null;
    }

    var datos = new FormData();
    datos.append('accion', 'consultar');
    datos.append('tipo_reporte', reporteActual);

    if (reporteActual === 'recaudacion') {
        datos.append('moneda', $('#filtro_moneda').val() || 'todos');
        datos.append('concepto', $('#filtro_concepto').val() || 'todos');
        datos.append('fecha_desde', $('#filtro_desde').val() || '');
        datos.append('fecha_hasta', $('#filtro_hasta').val() || '');
    } else if (reporteActual === 'inventario') {
        datos.append('categoria_inventario', $('#filtro_cat_inventario').val() || 'todos');
        datos.append('estado_fisico', $('#filtro_estado_fisico').val() || 'todos');
        datos.append('fecha_desde', $('#filtro_inv_desde').val() || '');
        datos.append('fecha_hasta', $('#filtro_inv_hasta').val() || '');
    } else if (reporteActual === 'rendimiento') {
        datos.append('atleta', $('#filtro_atleta').val() || 'todos');
        datos.append('torneo', $('#filtro_temporada').val() || 'todos');
    } else {
        datos.append('categoria', $('#filtro_categoria').val() || 'todos');
        datos.append('genero', $('#filtro_genero').val() || 'todos');
        datos.append('incluir_retirados', $('#filtro_retirados').is(':checked') ? '1' : '0');
    }

    // Almacenamos el hilo AJAX activo
    let xhr = enviaAjax(datos);
    ajxConsultar = xhr;

    // Control de seguridad: Resetea la variable global solo si sigue siendo la misma petición
    xhr.always(function () {
        if (ajxConsultar === xhr) {
            ajxConsultar = null;
        }
    });
}

function enviaAjax(datos) {
    var token = $('meta[name="csrf-token"]').attr('content');

    // Retornamos el objeto $.ajax para poder controlarlo con .abort()
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
                // SEGURIDAD: Evita romper el flujo si el servidor ya devuelve un objeto JSON parseado automáticamente
                var lee = (typeof respuesta === 'string') ? JSON.parse(respuesta) : respuesta;

                if (lee.accion == "MultiConsulta") {
                    cargandoFiltros = true; // Bloqueamos temporalmente los disparadores 'change'

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

                    cargandoFiltros = false; // Desbloqueamos los cambios
                    filtrarReporte();
                }
                else if (lee.accion == "consultar") {
                    Datos = lee.datos;
                    if ($("#contenedor_modal").hasClass('ocultar') || !$("#modal").hasClass('mostrar')) {
                        abrirModal();
                        setTimeout(function () {
                            cargarGraficoReporte(lee.datos, lee.tipo_reporte);
                        }, 250);
                    } else {
                        cargarGraficoReporte(lee.datos, lee.tipo_reporte);
                    }
                }
                else if (lee.accion == "reporte") {
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
            // Si el error fue causado por nuestro propio .abort(), no mostramos alerta molesta
            if (request.statusText === 'abort') return;

            cerrarAlertaEspara();
            muestraMensaje("error", 2000, "Error", "ERROR: " + request.statusText);
        }
    });
}

function construirSelect(idSelect, datos, campoId, campo1, textoPorDefecto) {
    var select = $('#' + idSelect);
    select.empty();

    // Si pasamos un texto global (ej: "Todas las Categorías"), lo añadimos como seleccionado por defecto
    if (textoPorDefecto) {
        select.append(`<option value="todos" selected>${textoPorDefecto}</option>`);
    } else {
        select.append('<option value="" disabled selected>Seleccione una opción</option>');
    }

    datos.forEach(dato => {
        var linea = `<option value="${dato[campoId]}">${escapeHTML(String(dato[campo1]))}</option>`;
        select.append(linea);
    });
}

function escapeHTML(texto) {
    var caracteres = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(texto).replace(/[&<>"']/g, m => caracteres[m]);
}

function cargarGraficoReporte(datosServidor, tipoReporte) {
    if (miGrafico !== null) {
        miGrafico.destroy();
        miGrafico = null;
    }
    $('#barChart').remove();
    $('#contenedor_canvas').append('<canvas id="barChart"></canvas>');

    const canvas = document.getElementById('barChart');
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
                    backgroundColor: 'rgba(255, 159, 64, 0.85)', // Naranja opaco y visible
                    borderColor: 'rgba(255, 159, 64, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Asistencias',
                    data: datosServidor.map(item => parseInt(item.total_asistencias) || 0),
                    backgroundColor: 'rgba(153, 102, 255, 0.85)', // Morado opaco y visible
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
                    color: '#ffffff', // Ahora resalta perfectamente sobre el fondo sólido
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

    miGrafico = new Chart(ctx, config);
}