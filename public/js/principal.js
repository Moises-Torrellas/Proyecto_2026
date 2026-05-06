$(document).ready(function () {

    const alerta = $('#alerta-backend');

    if (alerta.length > 0) {
        // Extraemos los datos del dataset del div
        const icono = alerta.data('icono');
        const titulo = alerta.data('titulo');
        const mensaje = alerta.data('mensaje');

        // Llamamos a tu función
        muestraMensaje(icono, 2000, titulo, mensaje);
    }

    const pasos = [
        {
            element: '#navegacion',
            popover: {
                title: 'Menu del Sistema',
                description: 'Aquí consigues todas las opciones del sistema.',
                position: 'right'
            }
        },
        {
            element: '#asistente',
            popover: {
                title: 'Sydney',
                description: 'Aqui puedes interactuar con Sydney su asistente virtual.',
                position: 'bottom'
            }
        },
        {
            element: '#noti',
            popover: {
                title: 'Panel de Notificaciones',
                description: 'Aquí puedes dar click para abrir o cerrar el panel de notificaciones.',
                position: 'bottom'
            }
        },
        {
            element: '#info_usuario',
            popover: {
                title: 'Panel de Usuario',
                description: 'Puedes dar click en tu nombre para abrir el menu del usuario.',
                position: 'left'
            }
        }
    ];

    const driver = iniciarTourConPasos(pasos);

    $('#ayuda').on('click', function () {
        driver.start();
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendario_eventos');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listWeek'
        },

        // DATOS DE PRUEBA: TORNEOS MULTIDÍA
        events: [
            {
                id: '101',
                title: '🏆 Torneo Regional Barquisimeto',
                start: '2026-05-10', // Inicia el 10
                end: '2026-05-13',   // Termina el 12 (en FullCalendar el 'end' es exclusivo, marca el día que ya no está)
                color: '#d35400'     // Color especial para torneos
            },
            {
                id: '102',
                title: 'Torneo Relámpago Hockey Sub-18',
                start: '2026-05-20T08:00:00',
                end: '2026-05-21T18:00:00', // Dura dos días con horas específicas
                color: '#27ae60'
            },
            {
                id: '103',
                title: 'Copa Inter-Academias 2026',
                start: '2026-05-28',
                end: '2026-05-31',
                color: '#2980b9'
            }, 
            {
                id: '104',
                title: 'Cumpleaños de Jose Perez',
                start: '2026-05-03',
                display: 'list-item',
                color: '#2980b9'
            }
        ],

        eventClick: function (info) {
            // Formateamos las fechas para que se vean bien en el SweetAlert
            const inicio = info.event.start.toLocaleDateString();
            const fin = info.event.end ? info.event.end.toLocaleDateString() : inicio;

            Swal.fire({
                title: 'Información del Torneo',
                html: `
        <div style="text-align: left;">
            <p><strong>Competencia:</strong> ${info.event.title}</p>
            <p><strong>Desde:</strong> ${inicio}</p>
            <p><strong>Hasta:</strong> ${fin}</p>
            <p style="font-size: 0.8em; opacity: 0.6;">ID: ${info.event.id}</p>
        </div>
    `,
                icon: 'info',
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cerrar',

                // Eliminamos el background estático para que use el de la clase CSS
                customClass: {
                    popup: 'mi-popup', // Usamos tu clase personalizada + el radio de borde
                    title: 'mi-titulo',                 // Tu clase de título personalizada
                    cancelButton: 'btn_a'               // Reutilizamos tu clase de botón pequeño si deseas
                },
            });
        }
    });

    calendar.render();
});

const ctx = document.getElementById('gaugeChart').getContext('2d');
const gaugeChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [75.55, 24.45], // El valor y el resto para completar el 100%
            backgroundColor: ['#32AF0A', '#E1E6EB'], // Azul y gris claro
            borderWidth: 0,
            circumference: 180, // Solo medio círculo
            rotation: 270,    // Lo orienta hacia arriba
            cutout: '85%',    // Hace que la línea sea delgada
            borderRadius: 10  // Bordes redondeados como en tu imagen
        }]
    },
    options: {
        aspectRatio: 1.5,
        plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
        }
    }
});

const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
new Chart(ctxDoughnut, {
    type: 'doughnut',
    data: {
        labels: ['U-12', 'U-14', 'U-17'], // Basado en tus categorías de hockey
        datasets: [{
            data: [10, 20, 20], // Porcentajes de ejemplo
            backgroundColor: [
                '#32AF0A', // Azul oscuro (Desktop)
                '#32AF0A44', // Azul medio (Mobile)
                '#32AF0A25'  // Azul claro (Tablet)
            ],
            hoverOffset: 4,
            borderWidth: 0, // Sin bordes para un look más limpio
            cutout: '70%'   // Grosor del círculo
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom', // Leyenda abajo como en la imagen
                labels: {
                    usePointStyle: true, // Puntos redondos en la leyenda
                    padding: 20,
                    font: {
                        size: 12
                    }
                }
            }
        }
    }
});