// 1. Declarar la variable de manera global para que funcione en cualquier parte del archivo
let miGrafico = null;

$(document).ready(function () {

    $('#proceso').on('click', function () {
        // Corrección menor: agregamos 'const' para declarar la variable local correctamente
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
        abrirModal();
        setTimeout(function () {
            cargarGraficoReporte();
        }, 150);
    });

    $('#cerrar_modal').on('click', function () {
        destruirGrafico();
    });

    /* $('#ayuda').on('click', function () {
        const pasos = [
            { element: '#busqueda', popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar al Atleta que necesites.', position: 'bottom' } },
            { element: '#incluir', popover: { title: 'Nuevo Atleta', description: 'Si pulsa aqui se abrira un modal para ingresar un nuevo Atleta', position: 'bottom' } },
            { element: '#generar', popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' } },
            { element: '#resultadoconsulta', popover: { title: 'Atletas Registrados', description: 'Aqui se mostraran todos los Atletas registrados.', position: 'top' } },
            { element: '#registro', popover: { title: 'Registro de un Atleta', description: 'Aqui se mostrara la informacion de un Atleta si pulsa el registro se desplegara mas informacion.', position: 'bottom' } },
            { element: '#cbt_v', popover: { title: 'Modificar Atletas', description: 'Si pulsa aqui se abrira un modal para modificar el Atleta seleccionado.', position: 'left' } },
            { element: '#cbt_r', popover: { title: 'Eliminar Atleta', description: 'Si pulsa aqui eliminara el Atleta seleccionado.', position: 'left' } },
            { element: '#cbt_sec', popover: { title: 'Generar Curriculum', description: 'Si pulsa aqui generara un curriculum del Atleta seleccionado.', position: 'left' } },
            { element: '#rowsPerPage', popover: { title: 'Registros Deseados', description: 'Aqui podra seleccionar la cantidad de registros que quiere que se muestren.', position: 'top' } },
            { element: '#botonera', popover: { title: 'Cambiar de Pagina', description: 'Botones para cambiar de página.', position: 'top' } },
            { element: '#cantidad', popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de representantes cargados.', position: 'top' } },
        ];

        const driver = iniciarTourConPasos(pasos);
        driver.start();
    }); */

});

// Esta función ahora sí puede leer y modificar la variable global 'miGrafico'
function cargarGraficoReporte() {
    const canvas = document.getElementById('barChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');

    // Ahora funciona perfectamente sin lanzar el error de "not defined"
    if (miGrafico !== null) {
        miGrafico.destroy();
    }

    const data = {
        labels: ['U-12', 'U-14', 'SENIOR'],
        datasets: [
            {
                label: 'Atletas Masc. (Activos)',
                data: [12, 10, 10],
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                stack: 'Masculino',
            },
            {
                label: 'Atletas Masc. (Retirados)',
                data: [2, 5, 4],
                backgroundColor: 'rgba(0, 123, 255, 0.4)',
                stack: 'Masculino',
            },
            {
                label: 'Atletas Fem. (Activos)',
                data: [8, 5, 15],
                backgroundColor: 'rgb(40, 167, 69, 0.8)',
                stack: 'Femenino',
            },
            {
                label: 'Atletas Fem. (Retirados)',
                data: [8, 3, 4],
                backgroundColor: 'rgb(40, 167, 69, 0.4)',
                stack: 'Femenino',
            },

        ]


    };

    const config = {
        type: 'bar',
        data: data,
        // NUEVO: Registramos el plugin de datalabels en esta instancia
        plugins: [ChartDataLabels],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },

                // NUEVO: Configuración de las etiquetas numéricas
                datalabels: {
                    anchor: 'end',       // Alinea la etiqueta respecto al final de la barra
                    align: 'top',        // Posiciona el número justo arriba de la barra
                    offset: 4,           // Separación en píxeles desde el borde de la barra
                    color: '#333333',    // Color del texto (puedes usar oscuro o claro según tu tema)
                    font: {
                        weight: 'bold',  // Texto en negrita como en tu imagen
                        size: 12         // Tamaño de la fuente
                    },
                    formatter: function (value) {
                        // Opcional: Si el valor es cero, no lo muestra para no amontonar texto
                        return value > 0 ? value : '';
                    }
                }
            },
            scales: {
                x: { stacked: false },
                y: {
                    stacked: true,
                    // Opcional: Le da un margen extra al tope del eje Y 
                    // para que los números de las barras más altas no se corten.
                    grace: '10%'
                }
            }
        }
    };

    miGrafico = new Chart(ctx, config);
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
            try {
                var lee = JSON.parse(respuesta);
                if (lee.accion == "reporte") {
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