// 1. Declarar la variable de manera global para que funcione en cualquier parte del archivo
let miGrafico = null;
let Datos = null;

$(document).ready(function () {

    $('#proceso').on('click', function () {
        const accion = $(this).data("accion");
        if (accion == "generar") {
            confirmar('¿Está seguro que quiere generar un reporte?', function (confirmado) {
                if (confirmado) {
                    abrirAlertaEspara('Se esta generando el reporte', 'Espere un momento');
                    var datos = new FormData();
                    datos.append('accion', 'generar');

                    if (miGrafico !== null) {
                        const graficoBase64 = miGrafico.toBase64Image();
                        datos.append('grafico_img', graficoBase64);
                    }

                    datos.append('datos_json', JSON.stringify(Datos));
                    enviaAjax(datos);
                }
            });
        }
    });

    $("#incluir").on("click", function () {
        limpia();

        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte Estadistico");
        
        var datos = new FormData();
        datos.append('accion', 'consultar');
        enviaAjax(datos);
    });

    // CORRECCIÓN ERROR 2: Destruir el gráfico de manera correcta usando la variable global
    $('#cerrar_modal').on('click', function () {
        if (miGrafico !== null) {
            miGrafico.destroy();
            miGrafico = null; // Reseteamos la variable
        }
    });

});

function cargarGraficoReporte(datosServidor) {
    const canvas = document.getElementById('barChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    if (miGrafico !== null) {
        miGrafico.destroy();
    }

    const etiquetas = datosServidor.map(item => item.categoria);
    const dataMascActivos = datosServidor.map(item => parseInt(item.masc_activos) || 0);
    const dataMascRetirados = datosServidor.map(item => parseInt(item.masc_retirados) || 0);
    const dataFemActivos = datosServidor.map(item => parseInt(item.fem_activos) || 0);
    const dataFemRetirados = datosServidor.map(item => parseInt(item.fem_retirados) || 0);

    const data = {
        labels: etiquetas,
        datasets: [
            {
                label: 'Atletas Masc. (Activos)',
                data: dataMascActivos,
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                stack: 'Masculino',
            },
            {
                label: 'Atletas Masc. (Retirados)',
                data: dataMascRetirados,
                backgroundColor: 'rgba(0, 123, 255, 0.4)',
                stack: 'Masculino',
            },
            {
                label: 'Atletas Fem. (Activos)',
                data: dataFemActivos,
                backgroundColor: 'rgba(40, 167, 69, 0.8)', 
                stack: 'Femenino',
            },
            {
                label: 'Atletas Fem. (Retirados)',
                data: dataFemRetirados,
                backgroundColor: 'rgba(40, 167, 69, 0.4)',
                stack: 'Femenino',
            }
        ]
    };

    const config = {
        type: 'bar',
        data: data,
        plugins: [ChartDataLabels],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    offset: 4,
                    color: '#333333',
                    font: { weight: 'bold', size: 12 },
                    formatter: function (value) {
                        return value > 0 ? value : ''; 
                    }
                }
            },
            scales: {
                x: { stacked: false },
                y: {
                    stacked: true,
                    grace: '10%' 
                }
            }
        }
    };

    miGrafico = new Chart(ctx, config);
}

// Nota: Es más seguro capturar el token justo antes de enviarlo por si el DOM no había cargado la meta etiqueta todavía.
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
        beforeSend: function (request) {
            request.setRequestHeader("X-CSRF-TOKEN", token);
        },
        timeout: 120000,
        success: function (respuesta) {
            try {
                var lee = JSON.parse(respuesta);
                if (lee.accion == "consultar") {
                    Datos = lee.datos;
                    abrirModal();
                    cargarGraficoReporte(lee.datos);
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
                    muestraMensaje("error", 2000, "Error", lee.mensaje);
                }
            } catch (e) {
                alert("Error en JSON " + e.name);
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + request + status + err);
            }
        },
        complete: function () { },
    });
}