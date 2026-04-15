
window.onload = function () {
    setTimeout(() => {
        $('#loader').fadeOut();
    }, 500);

};




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

$('#cerrar_modal_asistente').on("click", function () {
    cerrarModalAsistente();
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

function abrirModalAsistente() {
    $("#asistente_modal_contenedor").css('opacity', '1')
    $("#asistente_modal_contenedor").css('visibility', 'visible')
    $("#asistente_modal").addClass("expandir")
}

function cerrarModalAsistente() {
    $("#asistente_modal").removeClass("expandir")
    $("#asistente_modal_contenedor").css('opacity', '0')
    $("#asistente_modal_contenedor").css('visibility', 'hidden')
}

$(document).ready(function () {

    lucide.createIcons();

    tippy('[data-tippy-content]', { theme: 'light' });

    let scrollTimers = {}; // Objeto para guardar los timers de cada contenedor

    $('.contenido_modulo, .navegacion').on('scroll', function () {
        let $this = $(this);
        let id = $this.attr('class'); // Usamos la clase como identificador simple

        // Agregamos la clase al contenedor que se está moviendo
        $this.addClass('is-scrolling');

        // Limpiamos el timer específico de este contenedor
        if (scrollTimers[id]) {
            clearTimeout(scrollTimers[id]);
        }

        // Ocultamos el scroll después de 1.2 segundos de inactividad
        scrollTimers[id] = setTimeout(function () {
            $this.removeClass('is-scrolling');
            delete scrollTimers[id];
        }, 1200);
    });

    if (window.mensajeError) {
        muestraMensaje("error", 3000, "Acceso Denegado", window.mensajeError.mensaje);
    }
    $('#limpiar').on('click', function () {
        limpia();
    });
    var titulo = $("#titulo").text().trim();

    $(".opciones").each(function () {
        if ($(this).text().trim() === titulo) {
            $(this).addClass("selecto");
        }
    });


    let body = $('body');
    let circle = $('#circle-transition');
    $('#modo_oscuro').on('click', function () {

        let esOscuro = body.attr('data-tema') === 'oscuro';

        if (!esOscuro) {
            // Expandir círculo con color oscuro
            //circle.css('background-color', '#1f2a36'); // fondo oscuro de tu tema
            //circle.css('clip-path', 'circle(150% at 50% 50%)');

            setTimeout(() => {
                // Cambiar tema a oscuro
                body.attr('data-tema', 'oscuro');
                $('#modo_oscuro').find('svg').remove();
                // 2. Metemos la etiqueta <i> de Lucide al principio del botón
                $('#modo_oscuro').prepend('<i class="opciones_i" data-lucide="sun"></i> ');
                // 3. Le decimos a Lucide que dibuje el icono
                lucide.createIcons({
                    root: document.getElementById('modo_oscuro')
                });
                // Contraer círculo
                //circle.css('clip-path', 'circle(0% at 50% 50%)');
                const tema = 'oscuro';
                let fecha = new Date();
                fecha.setTime(fecha.getTime() + (30 * 24 * 60 * 60 * 1000));
                document.cookie = "tema_preferido=" + tema + ";expires=" + fecha.toUTCString() + ";path=/";
            }, 400); // espera transición clip-path
        } else {
            // Expandir círculo con color claro
            //circle.css('background-color', '#f2f3f5'); // fondo claro de tu tema
            //circle.css('clip-path', 'circle(150% at 50% 50%)');

            setTimeout(() => {
                // Cambiar tema a claro
                body.attr('data-tema', 'claro');
                $('#modo_oscuro').find('svg').remove();
                $('#modo_oscuro').prepend('<i class="opciones_i" data-lucide="moon"></i> ');
                lucide.createIcons({
                    root: document.getElementById('modo_oscuro')
                });
                // Contraer círculo
                //circle.css('clip-path', 'circle(0% at 50% 50%)');

                const tema = 'claro';
                let fecha = new Date();
                fecha.setTime(fecha.getTime() + (30 * 24 * 60 * 60 * 1000));
                document.cookie = "tema_preferido=" + tema + ";expires=" + fecha.toUTCString() + ";path=/";
            }, 400);
        }
    });

    $('#info_usuario').on('click', function (e) {
        e.stopPropagation();
        // Cerramos el de notificaciones por si estaba abierto
        $('#contenedor_notificaciones').removeClass('expandir');

        // Toggle al de usuario
        $('#menu_superior').toggleClass('expandir');
        $('#flecha').toggleClass('rotar');
    });

    $('#noti').on('click', function (e) {
        e.stopPropagation();
        // Cerramos el menú de usuario y reseteamos la flecha
        $('#menu_superior').removeClass('expandir');
        $('#flecha').removeClass('rotar');

        // Toggle al de notificaciones
        $('#contenedor_notificaciones').toggleClass('expandir');
    });

    $('#asistente').on('click', function (e) {
        abrirModalAsistente();
    });

    $(document).on('click', function (e) {
        // Si el clic no es en el área de usuario, cerrar menú usuario
        if (!$(e.target).closest('#info_usuario, #menu_superior').length) {
            $('#menu_superior').removeClass('expandir');
            $('#flecha').removeClass('rotar');
        }
        // Si el clic no es en notificaciones, cerrar notificaciones
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
                muestraMensaje("success", 2000, "Cerrando sesión");
                setTimeout(function () {
                    location.href = "CerrarSesion";
                }, 2000)
            }
        });
    });
});

function inicializarPaginador() {
    const $table = $('#tablageneral');
    const $rows = $table.find('tbody tr').not(':has(td[colspan])'); // Excluimos la fila de "Cargar más"
    const $rowsPerPageSelect = $('#rowsPerPage');
    const $paginationContainer = $('#botonera');

    let currentPage = 1;
    let rowsPerPage = parseInt($rowsPerPageSelect.val()) || 10;

    // Función para cambiar de página
    function showPage(page) {
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        $rows.hide();
        $rows.slice(start, end).show();
    }

    // Función para dibujar los botones
    function renderPagination() {
        const totalRows = $rows.length;
        const pageCount = Math.ceil(totalRows / rowsPerPage);
        $paginationContainer.empty();

        if (pageCount <= 1) {
            if (pageCount === 1) {
                const $btn = $('<button class="boton active">').text(1);
                $paginationContainer.append($btn);
            }
            return;
        }

        const $addButton = (num) => {
            const $btn = $('<button class="boton">').text(num);
            if (num === currentPage) $btn.addClass('active');
            $btn.on('click', function () {
                currentPage = num;
                showPage(currentPage);
                renderPagination();
            });
            $paginationContainer.append($btn);
        };

        const $addDots = () => $paginationContainer.append('<span class="puntos">...</span>');

        // Lógica de botones (1 ... actual ... último)
        $addButton(1);
        if (currentPage > 3) $addDots();

        let start = Math.max(2, currentPage - 1);
        let end = Math.min(pageCount - 1, currentPage + 1);

        if (currentPage <= 2) end = Math.min(4, pageCount - 1);
        if (currentPage >= pageCount - 1) start = Math.max(2, pageCount - 3);

        for (let i = start; i <= end; i++) {
            $addButton(i);
        }

        if (currentPage < pageCount - 2) $addDots();
        $addButton(pageCount);
    }

    // Evento para cambiar cantidad de filas a ver
    $rowsPerPageSelect.off('change').on('change', function () {
        rowsPerPage = parseInt($(this).val());
        currentPage = 1;
        showPage(currentPage);
        renderPagination();
    });

    // Ejecución inicial
    showPage(currentPage);
    renderPagination();

    // Actualizar contador visual de registros cargados actualmente
    $('#cantidadRegistros').text($rows.length);
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



/* function quitarClase($etiqueta) {

    $etiqueta.on('blur', function () {
        $(this).removeClass('denegado');
    });
} */

// Se mantiene igual, sirve para RESTRINGIR caracteres mientras se presiona la tecla
function validarkeypress(er, e) {
    let key = e.keyCode || e.which;
    let tecla = String.fromCharCode(key);
    let a = er.test(tecla);
    if (!a) {
        e.preventDefault();
        muestraMensajeMini('error', 2000, 'Carácter no permitido');
    }
}

// CAMBIO: Ahora esta función tiene una lógica inteligente
function validarkeyup(er, etiqueta, etiquetamensaje, mensaje, mostrarError = false) {
    let a = er.test(etiqueta.val());

    if (a) {
        // Si es válido: Limpiamos todo
        etiquetamensaje.text("");
        etiqueta.removeClass("denegado");
        return false;
    } else {
        // Si es inválido:
        if (mostrarError) {
            // Solo ponemos el rojo si salimos del foco (blur) o damos clic en enviar
            etiquetamensaje.text(mensaje);
            etiqueta.addClass("denegado");
        }
        return true;
    }
}

function Validacion(idInput, erKeyPress, erKeyUp, mensajeAyuda, boton = null) {
    const $input = $(`#${idInput}`);
    const $spam = $(`#${idInput}_spam`);

    $input.on("keypress", function (e) {
        validarkeypress(erKeyPress, e);
    });

    $input.on("keyup", function () {
        validarkeyup(erKeyUp, $(this), $spam, "");
    });

    $input.on("focus", function () {
        $spam.text(mensajeAyuda);
        $(this).removeClass("denegado");
    });

    $input.on("blur", function () {
        let accion = boton ? $(`#${boton}`).data("accion") : null;
        let forzarError = (accion === "generar") ? false : true;
        validarkeyup(erKeyUp, $(this), $spam, mensajeAyuda, forzarError);
    });
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
        customClass: {
            popup: "mi-popup",
            title: "mi-titulo",
            content: "mi-contenido"
        },
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




