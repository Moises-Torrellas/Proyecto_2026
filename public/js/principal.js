$(document).ready(function () {
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
            element: '#casa',
            popover: {
                title: 'Pagina Principal',
                description: 'Aquí puedes dar click para ir a la pagina principal.',
                position: 'bottom'
            }
        },
        {
            element: '#modo_oscuro',
            popover: {
                title: 'Modo Oscuro',
                description: 'Aquí puedes dar click para activar o desactivar el modo oscuro.',
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

const lineCtx = document.getElementById('lineChart').getContext('2d');

const lineChart = new Chart(lineCtx, {
    type: 'line',
    data: {
        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
        datasets: [{
            label: 'Ganancias',
            data: [0, 10000, 5000, 15000, 10000, 20000, 15000, 25000, 20000, 30000, 25000, 40000],
            backgroundColor: 'rgba(59, 130, 246, 0.4)', // Área debajo
            borderColor: 'rgba(0, 123, 255, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                backgroundColor: '#ffffff',
                titleColor: '#000000',
                bodyColor: '#000000',
                borderColor: '#ffffff',
                borderWidth: 1,
                padding: 10,
                cornerRadius: 6
            },
            legend: {
                display: false
            }
        },
        scales: {
            x: {
                ticks: {
                    color: '#94a3b8', // Color etiquetas eje X
                    font: {
                        size: 12,
                        weight: 'bold'
                    }
                },
                grid: {
                    display: false
                }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#94a3b8', // Color valores eje Y
                    font: {
                        size: 12
                    }
                },
                grid: {
                    color: '#e5e7eb'
                }
            }
        }
    }
});