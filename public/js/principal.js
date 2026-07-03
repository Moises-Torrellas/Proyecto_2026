$(document).ready(function () {

    // 1. Inicialización de Alertas (Backend)
    const alerta = $('#alerta-backend');
    if (alerta.length > 0) {
        muestraMensaje(alerta.data('icono'), 2000, alerta.data('titulo'), alerta.data('mensaje'));
    }

    // 2. Configuración del Driver.js (Tour)
    const pasos = [
        { element: '#navegacion', popover: { title: 'Menu del Sistema', description: 'Aquí consigues todas las opciones.', position: 'right' } },
        { element: '#asistente', popover: { title: 'Sydney', description: 'Interactúa con tu asistente.', position: 'bottom' } },
        { element: '#noti', popover: { title: 'Notificaciones', description: 'Panel de avisos.', position: 'bottom' } },
        { element: '#info_usuario', popover: { title: 'Usuario', description: 'Menu de configuración personal.', position: 'left' } }
    ];
    const driver = iniciarTourConPasos(pasos);
    $('#ayuda').on('click', function () { driver.start(); });

    // 3. Carga Asíncrona: Gráficos
    $.ajax({
        url: '', // Se mantiene en la misma ruta del controlador
        type: 'POST',
        data: { accion: 'cargar_graficos' },
        dataType: 'json',
        success: function(res) {
            if (res.accion === 'exito') {
                const data = res.datos;
                
                // Gauge: Atletas Solventes
                document.querySelector('#gaugeChart').nextElementSibling.querySelector('h1').textContent = data.solvencia.porcentaje + "%";
                
                new Chart(document.getElementById('gaugeChart').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        datasets: [{
                            data: [data.solvencia.solventes, data.solvencia.deudores],
                            backgroundColor: ['#32AF0A', '#E1E6EB'],
                            borderWidth: 0,
                            circumference: 180,
                            rotation: 270,
                            cutout: '85%',
                            borderRadius: 10
                        }]
                    },
                    options: { aspectRatio: 1.5, plugins: { legend: { display: false }, tooltip: { enabled: true } } }
                });

                // Doughnut: Categorías
                new Chart(document.getElementById('doughnutChart').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: data.categorias.map(c => c.nombre),
                        datasets: [{
                            data: data.categorias.map(c => c.cantidad),
                            backgroundColor: ['#32AF0A', '#32AF0A99', '#32AF0A55', '#32AF0A25'],
                            hoverOffset: 4,
                            borderWidth: 0,
                            cutout: '70%'
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true, position: 'bottom', labels: { usePointStyle: true, padding: 20 } } }
                    }
                });
            }
        }
    });

    // 4. Carga Asíncrona: Calendario
    $.ajax({
        url: '',
        type: 'POST',
        data: { accion: 'cargar_calendario' },
        dataType: 'json',
        success: function(res) {
            if (res.accion === 'exito') {
                const calendar = new FullCalendar.Calendar(document.getElementById('calendario_eventos'), {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
                    events: res.datos,
                    eventClick: function (info) {
                        const inicio = info.event.start.toLocaleDateString();
                        const esTorneo = info.event.extendedProps.tipo === 'torneo';
                        
                        Swal.fire({
                            title: esTorneo ? 'Información del Torneo' : 'Cumpleaños',
                            html: `<div style="text-align: left;">
                                    <p><strong>Evento:</strong> ${info.event.title}</p>
                                    <p><strong>Fecha:</strong> ${inicio}</p>
                                   </div>`,
                            icon: 'info',
                            showConfirmButton: false,
                            showCancelButton: true,
                            cancelButtonText: 'Cerrar',
                            customClass: { popup: 'mi-popup', title: 'mi-titulo', cancelButton: 'btn_a' }
                        });
                    }
                });
                calendar.render();
            }
        }
    });
});