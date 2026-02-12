
window.onload = function () {
    setTimeout(() => {
        $('#loader').fadeOut();
    }, 500);

};

/* document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
}); */


$(".ojo").click(function () {
    var $this = $(this); // el botón clickeado
    var $pass = $this.siblings("input[type='password'], input[type='text']"); // el input cercano

    if ($pass.attr("type") === "password") {
        $pass.attr("type", "text");
        $this.removeClass("fi-sr-eye").addClass("fi-sr-eye-crossed");
    } else {
        $pass.attr("type", "password");
        $this.removeClass("fi-sr-eye-crossed").addClass("fi-sr-eye");
    }
});

$('#cerrar_modal').on("click", function () {
    cerrarModal();
});

function cerrarModal() {
    $("#modal").removeClass("expandir")
    $("#contenedor_modal").css('opacity', '0')
    $("#contenedor_modal").css('visibility', 'hidden')
}


function abrirModal() {
    $("#contenedor_modal").css('opacity', '1')
    $("#contenedor_modal").css('visibility', 'visible')
    $("#modal").addClass("expandir")
}

$(document).ready(function () {

    if (window.mensajeError) {
        muestraMensaje("error", 3000, "Acceso Denegado", window.mensajeError.mensaje);
    }

    var titulo = $("#titulo").text().trim();

    $(".opciones").each(function () {
        if ($(this).text().trim() === titulo) {
            $(this).addClass("selecto");
        }
    });

    let icono = $('#modo_oscuro i');
    let body = $('body');
    let circle = $('#circle-transition');

    // Inicializar tema según icono
    

    $('#modo_oscuro').on('click', function () {
        if (icono.hasClass('fi-sr-moon')) {
            // Expandir círculo con color oscuro
            circle.css('background-color', '#1f2a36'); // fondo oscuro de tu tema
            circle.css('clip-path', 'circle(150% at 50% 50%)');

            setTimeout(() => {
                // Cambiar tema a oscuro
                body.attr('data-tema', 'oscuro');
                icono.removeClass('fi-sr-moon').addClass('fi-sr-sun');
                // Contraer círculo
                circle.css('clip-path', 'circle(0% at 50% 50%)');
                const tema = 'oscuro';
                let fecha = new Date();
                fecha.setTime(fecha.getTime() + (30 * 24 * 60 * 60 * 1000));
                document.cookie = "tema_preferido=" + tema + ";expires=" + fecha.toUTCString() + ";path=/";
            }, 400); // espera transición clip-path
        } else {
            // Expandir círculo con color claro
            circle.css('background-color', '#f2f3f5'); // fondo claro de tu tema
            circle.css('clip-path', 'circle(150% at 50% 50%)');

            setTimeout(() => {
                // Cambiar tema a claro
                body.attr('data-tema', 'claro');
                icono.removeClass('fi-sr-sun').addClass('fi-sr-moon');
                // Contraer círculo
                circle.css('clip-path', 'circle(0% at 50% 50%)');

                const tema = 'claro';
                let fecha = new Date();
                fecha.setTime(fecha.getTime() + (30 * 24 * 60 * 60 * 1000));
                document.cookie = "tema_preferido=" + tema + ";expires=" + fecha.toUTCString() + ";path=/";
            }, 400);
        }
    });





    $('#info_usuario').on('click', function () {
        $('#menu_superior').toggleClass('expandir');
        $('#flecha').toggleClass('rotar');
    });
    $('#noti').on('click', function () {
        $('#contenedor_notificaciones').toggleClass('expandir');
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#info_usuario, #menu_superior').length) {
            $('#menu_superior').removeClass('expandir');
            $('#flecha').removeClass('rotar');
        }
        if (!$(e.target).closest('#noti, #contenedor_notificaciones').length) {
            $('#contenedor_notificaciones').removeClass('expandir');
        }
    });

    $('#contenedor_modal').on('click', function (e) {
        if ($(e.target).is('#contenedor_modal')) {
            cerrarModal();
        }
    });

    $("#salir").on("click", function () {
        confirmar('¿Está seguro de que quieres salir?', function (confirmado) {
            if (confirmado) {
                muestraMensaje("success", 1500, "Cerrando sesión");
                setTimeout(function () {
                    location.href = "CerrarSesion";
                }, 1500)
            }
        });
    });
});

function inicializarPaginador() {
    const $table = $('#tablageneral');
    const $rows = $table.find('tbody tr');
    const $rowsPerPageSelect = $('#rowsPerPage');
    const $paginationContainer = $('#botonera');

    let currentPage = 1;
    let rowsPerPage = parseInt($rowsPerPageSelect.val());

    function renderPagination(filteredRows) {
        const totalRows = filteredRows.length;
        const pageCount = Math.ceil(totalRows / rowsPerPage);
        $paginationContainer.empty();

        if (pageCount === 1) {
            // Solo una página: mostrar botón 1 activo
            const $btn = $('<button class="boton active">').text(1);
            $paginationContainer.append($btn);
            return;
        }
        if (pageCount < 1) {
            // No hay páginas, no mostrar nada
            return;
        }

        const renderedPages = new Set();
        const $addButton = (num) => {
            if (renderedPages.has(num)) return;
            renderedPages.add(num);
            const $btn = $('<button class="boton">').text(num);
            if (num === currentPage) $btn.addClass('active');
            $btn.on('click', function () {
                currentPage = num;
                showPage(filteredRows);
                renderPagination(filteredRows);
            });
            $paginationContainer.append($btn);
        };
        const $addDots = () => $paginationContainer.append('<span class="puntos">...</span>');

        $addButton(1);
        if (currentPage > 4) $addDots();

        let start = Math.max(2, currentPage - 1);
        let end = Math.min(pageCount - 1, currentPage + 1);
        if (currentPage <= 3) { start = 2; end = Math.min(4, pageCount - 1); }
        if (currentPage >= pageCount - 2) { start = Math.max(2, pageCount - 3); end = pageCount - 1; }

        for (let i = start; i <= end; i++) $addButton(i);
        if (currentPage < pageCount - 3) $addDots();
        if (pageCount > 1) $addButton(pageCount);
    }

    function showPage(filteredRows) {
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        $rows.hide();
        filteredRows.slice(start, end).show();
    }

    function updateTable() {
        const searchTerm = $('#busqueda').val().toLowerCase().trim();
        const keywords = searchTerm.split(/\s+/);

        const filteredRows = $rows.filter(function () {
            const rowText = $(this).text().toLowerCase();
            return keywords.every(kw => rowText.includes(kw));
        });

        currentPage = 1;
        const thCount = $('#tablageneral thead th').length;
        if (filteredRows.length === 0) {
            $rows.hide();
            $('#tablageneral tbody').html(`<tr><td colspan="${thCount}" style="text-align:center;">No se consiguió ningún registro</td></tr>`);
            $paginationContainer.empty();
        } else {
            $('#tablageneral tbody').empty().append($rows);
            renderPagination(filteredRows);
            showPage(filteredRows);
        }
    }

    function countRecords() {
        const searchTerm = $('#busqueda').val().toLowerCase().trim();
        const keywords = searchTerm.split(/\s+/);

        const filteredRows = $rows.filter(function () {
            const rowText = $(this).text().toLowerCase();
            return keywords.every(kw => rowText.includes(kw));
        });

        $('#cantidadRegistros').text(filteredRows.length);
    }

    $('#busqueda').off('keyup').on('keyup', function () {
        updateTable();
        countRecords();
    });

    $rowsPerPageSelect.off('change').on('change', function () {
        rowsPerPage = parseInt($(this).val());
        updateTable();
        countRecords();
    });

    updateTable();
    countRecords();
}

function inicializarPaginadorCartas() {
    const $contenedor = $('.contenedor_cartas');
    const $cartas = $contenedor.find('.carta');
    const $rowsPerPageSelect = $('#rowsPerPage');
    const $paginationContainer = $('#botonera');

    let currentPage = 1;
    let rowsPerPage = parseInt($rowsPerPageSelect.val());

    function renderPagination(filteredCards) {
        const totalCards = filteredCards.length;
        const pageCount = Math.ceil(totalCards / rowsPerPage);
        $paginationContainer.empty();

        if (pageCount <= 1) {
            if (pageCount === 1) {
                const $btn = $('<button class="boton active">').text(1);
                $paginationContainer.append($btn);
            }
            return;
        }

        const renderedPages = new Set();
        const $addButton = (num) => {
            if (renderedPages.has(num)) return;
            renderedPages.add(num);
            const $btn = $('<button class="boton">').text(num);
            if (num === currentPage) $btn.addClass('active');
            $btn.on('click', function () {
                currentPage = num;
                showPage(filteredCards);
                renderPagination(filteredCards);
            });
            $paginationContainer.append($btn);
        };
        const $addDots = () => $paginationContainer.append('<span class="puntos">...</span>');

        $addButton(1);
        if (currentPage > 4) $addDots();

        let start = Math.max(2, currentPage - 1);
        let end = Math.min(pageCount - 1, currentPage + 1);
        if (currentPage <= 3) { start = 2; end = Math.min(4, pageCount - 1); }
        if (currentPage >= pageCount - 2) { start = Math.max(2, pageCount - 3); end = pageCount - 1; }

        for (let i = start; i <= end; i++) $addButton(i);
        if (currentPage < pageCount - 3) $addDots();
        $addButton(pageCount);
    }

    function showPage(filteredCards) {
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        $cartas.hide();
        filteredCards.slice(start, end).show();
    }

    function updateCards() {
        const searchTerm = $('#busqueda').val().toLowerCase().trim();
        const keywords = searchTerm.split(/\s+/);

        const filteredCards = $cartas.filter(function () {
            const cardText = $(this).text().toLowerCase();
            return keywords.every(kw => cardText.includes(kw));
        });

        currentPage = 1;
        if (filteredCards.length === 0) {
            $cartas.hide();
            $contenedor.html(`<p style="text-align:center; width:100%;">No se consiguió ningún registro</p>`);
            $paginationContainer.empty();
        } else {
            $('.contenedor_cartas').html(filteredCards);
            renderPagination(filteredCards);
            showPage(filteredCards);
        }
    }

    function countRecords() {
        const searchTerm = $('#busqueda').val().toLowerCase().trim();
        const keywords = searchTerm.split(/\s+/);

        const filteredCards = $cartas.filter(function () {
            const cardText = $(this).text().toLowerCase();
            return keywords.every(kw => cardText.includes(kw));
        });

        $('#cantidadRegistros').text(filteredCards.length);
    }

    $('#busqueda').off('keyup').on('keyup', function () {
        updateCards();
        countRecords();
    });

    $rowsPerPageSelect.off('change').on('change', function () {
        rowsPerPage = parseInt($(this).val());
        updateCards();
        countRecords();
    });

    updateCards();
    countRecords();
}


function muestraMensaje(icono, tiempo, titulo, mensaje) {
    Swal.fire({
        icon: icono,
        timer: tiempo,
        title: titulo,
        html: mensaje,
        showConfirmButton: false,
        customClass: {
            popup: "mi-popup",
            title: "mi-titulo",
            content: "mi-contenido"
        }
    });
}

function muestraMensajeMini(icono, tiempo, titulo) {
    const Toast = Swal.mixin({
        toast: true,
        position: "top",
        showConfirmButton: false,
        timer: tiempo,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        },
        customClass: {
            popup: "mi-popup",
            title: "mi-titulo"
        }
    });
    Toast.fire({
        icon: icono,
        title: titulo
    });
}

function validarkeypress(er, e) {
    key = e.keyCode;
    tecla = String.fromCharCode(key);
    a = er.test(tecla);
    if (!a) {
        e.preventDefault();
        muestraMensajeMini('error', 2000, 'Carácter no permitido')
    }
}

function quitarClase($etiqueta) {

    $etiqueta.on('blur', function () {
        $(this).removeClass('denegado');
    });
}

function validarkeyup(er, etiqueta, etiquetamensaje, mensaje) {
    a = er.test(etiqueta.val());
    if (a) {
        etiquetamensaje.text(""); // Borra el mensaje solo si el contenido es válido
        etiqueta.removeClass("denegado"); // Quita la clase del input
        return false;
    } else {
        etiquetamensaje.text(mensaje);
        etiqueta.addClass("denegado");
        return true;
    }
}


function confirmar(titulo, callback) {
    Swal.fire({
        icon: "question",
        title: titulo,
        showCancelButton: true,
        confirmButtonText: "SI",
        confirmButtonColor: "#00a200",
        cancelButtonText: "NO",
        cancelButtonColor: "#d30000",
        customClass: {
            popup: "mi-popup",
            title: "mi-titulo",
            content: "mi-contenido"
        }
    }).then((result) => {
        if (result.isConfirmed) {
            callback(true);
        } else {
            callback(false);
        }
    }).catch((e) => {
        alert("Error en JSON " + e.name);
        callback(false);
    });
}

function abrirAlertaEspara(titulo, texto) {
    Swal.fire({
        title: titulo,
        text: texto,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}
function cerrarAlertaEspara() {
    Swal.close();
}

function limpia() {
    $('#f input').not(':checkbox, #token').val('');
    $('input').removeClass('denegado');
    $('.select').val(null).trigger('change');
    $('.mensaje').text('');
}

function limpia_Tablas() {
    $('.caja_tabla tbody').find('tr').remove();
}

function eliminaLinea(boton) {
    $(boton).closest('tr').remove();
}

function iniciarTourConPasos(pasos) {
    const driver = new Driver({
        animate: true,
        opacity: 0.75,
        padding: 10,
        allowClose: false,
        doneBtnText: 'Finalizar',
        closeBtnText: 'Cerrar',
        nextBtnText: 'Siguiente',
        prevBtnText: 'Anterior',
    });

    driver.defineSteps(pasos);

    return driver;
}




