
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
$('#cerrar_modal_Secundario').on("click", function () {
    cerrarModalSecundario();
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
function abrirModalSecundario() {
    $("#secundario_modal_contenedor").css('opacity', '1')
    $("#secundario_modal_contenedor").css('visibility', 'visible')
    $("#secundario_modal").addClass("expandir")
}

function cerrarModalSecundario() {
    $("#secundario_modal").removeClass("expandir")
    $("#secundario_modal_contenedor").css('opacity', '0')
    $("#secundario_modal_contenedor").css('visibility', 'hidden')
}

$(document).ready(function () {

    lucide.createIcons();

    tippy('[data-tippy-content]', { theme: 'light' });

    $('#btn_hamburguesa').on('click', function(e) {
        e.stopPropagation();
        $('.nav_lateral').toggleClass('mostrar');
    });

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
            circle.css('background-color', '#1f2a36'); // fondo oscuro de tu tema
            circle.css('clip-path', 'circle(150% at 50% 50%)');

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
                $('#modo_oscuro').find('svg').remove();
                $('#modo_oscuro').prepend('<i class="opciones_i" data-lucide="moon"></i> ');
                lucide.createIcons({
                    root: document.getElementById('modo_oscuro')
                });
                // Contraer círculo
                circle.css('clip-path', 'circle(0% at 50% 50%)');

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
        // Si el clic no es en nav_lateral ni en btn_hamburguesa, cerrar menú lateral
        if (!$(e.target).closest('.nav_lateral, #btn_hamburguesa').length) {
            $('.nav_lateral').removeClass('mostrar');
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

    function enviarMensaje() {
        let texto = $('#chat_mensaje').val().trim();
        if (texto !== "") {
            // 1. Añadir mensaje del usuario con las NUEVAS clases
            $('#chat_historial').append(`
            <div class="asistente_msg asistente_usuario">
                <div class="asistente_burbuja">${texto}</div>
            </div>
        `);

            // Limpiar input
            $('#chat_mensaje').val('');

            // Scroll automático al fondo
            let historial = $('#chat_historial');
            historial.scrollTop(historial[0].scrollHeight);

            // 2. Simular respuesta del Bot con las NUEVAS clases
            setTimeout(() => {
                $('#chat_historial').append(`
                <div class="asistente_msg asistente_bot">
                    <div class="asistente_burbuja">Entendido, estoy procesando tu solicitud sobre: "${texto}"...</div>
                </div>
            `);
                historial.scrollTop(historial[0].scrollHeight);
            }, 1000);
        }
    }

    // Eventos (Se mantienen igual, solo asegúrate de que los IDs coincidan)
    $('#enviar_mensaje').on('click', enviarMensaje);

    $('#chat_mensaje').on('keydown', function (e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            enviarMensaje();
        }
    });
});

function inicializarPaginador() {
    const $contenedorListado = $('#resultadoconsulta');

    // CAMBIO CLAVE: Ahora buscamos el CONTENEDOR GRUPAL, no solo el item.
    // Esto asegura que se oculte el borde verde y el panel de detalle también.
    const $items = $contenedorListado.find('.listado_contenedor_grupal');

    // Si tienes tablas sin tree (donde usas listado_item directo), 
    // esta línea detectará ambos casos:
    const $registros = $items.length > 0 ? $items : $contenedorListado.find('.listado_item');

    const $rowsPerPageSelect = $('#rowsPerPage');
    const $paginationContainer = $('#botonera');

    let currentPage = 1;
    let itemsPerPage = parseInt($rowsPerPageSelect.val()) || 10;

    function showPage(page) {
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;

        // Ocultamos todos los contenedores completos
        $registros.hide();

        // Mostramos solo los de la página actual
        // Si es el tree, usamos block (porque el flex está dentro, en el listado_item)
        // Si es la tabla simple, usamos flex.
        $registros.slice(start, end).each(function () {
            if ($(this).hasClass('listado_contenedor_grupal')) {
                $(this).css('display', 'block');
            } else {
                $(this).css('display', 'flex');
            }
        });
    }

    // --- El resto de tu función renderPagination se mantiene igual ---
    function renderPagination() {
        const totalItems = $registros.length;
        const pageCount = Math.ceil(totalItems / itemsPerPage);
        $paginationContainer.empty();

        if (pageCount <= 1) {
            if (pageCount === 1 && totalItems > 0) {
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

    $rowsPerPageSelect.off('change').on('change', function () {
        itemsPerPage = parseInt($(this).val());
        currentPage = 1;
        showPage(currentPage);
        renderPagination();
    });

    showPage(currentPage);
    renderPagination();

    $('#cantidadRegistros').text($registros.length);
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
    const formulario = $('#f');

    // 1. Limpiar campos de texto, fecha y otros (Excepto token)
    formulario.find('input').not(':checkbox, :radio, :file, #token').val('');

    // 2. Limpiar input de ARCHIVO y resetear la PREVISUALIZACIÓN a la cámara
    formulario.find('input:file').val(''); 
    $('#foto_previa').attr('src', ''); // Restablece el icono original

    // 3. Desmarcar checkboxes y radios
    formulario.find('input:checkbox, input:radio').prop('checked', false);

    // 4. Limpiar textareas (Dirección)
    formulario.find('textarea').val('');

    // 5. Resetear Selects (Categoría, Posición, Representante)
    formulario.find('select').each(function () {
        $(this).val($(this).find('option:first').val()).trigger('change');
    });

    // 6. Resetear estados visuales y mensajes de error
    formulario.find('.denegado').removeClass('denegado');
    $('.mensaje').text(''); 

    // 7. Restablecer bloqueos de la lógica de edad (Atletas)
    formulario.find('input, select, textarea, button').prop('disabled', false);
    $('.campo_deshabilitado, .bloqueado, .btn_bloqueado').removeClass('campo_deshabilitado bloqueado btn_bloqueado');

    // 8. Ajustes de placeholders y visibilidad
    $("#doc_i").attr("placeholder", "Cédula");
    formulario.find('.row, .col, div').show();
}

function limpia_Tablas() {
    $('#tabla_permisos').find('tr').remove();
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

function toggleDetalles(elemento) {
    const contenedorActual = $(elemento).closest('.listado_contenedor_grupal');
    const panelActual = contenedorActual.find('.listado_detalle_oculto');

    // 1. Buscamos todos los demás contenedores que estén expandidos y los cerramos
    $('.listado_contenedor_grupal.expandido').not(contenedorActual).each(function () {
        $(this).removeClass('expandido');
        $(this).find('.listado_detalle_oculto').slideUp(300);
    });

    // 2. Toggle del registro actual (lo que ya tenías)
    contenedorActual.toggleClass('expandido');
    panelActual.slideToggle(300);
}

// public/js/notificaciones.js
document.addEventListener("DOMContentLoaded", function () {
    const btnNoti = document.getElementById("noti");
    const contenedorNoti = document.getElementById("contenedor_notificaciones");

    // ==========================================
    // 1. CONEXIÓN AL WEBSOCKET (EN TIEMPO REAL)
    // ==========================================
    const ws = new WebSocket("ws://localhost:8080");

    ws.onmessage = function (event) {
        const payload = JSON.parse(event.data);
        
        // Renderizar el mensaje mini superior nativo con Lucide Icons
        renderizarNotificacionSuperior(payload.titulo, payload.mensaje, payload.tipo);
        
        // Incrementar el indicador numérico sobre tu campana
        actualizarContadorBadge();
    };

    ws.onclose = function () {
        console.warn("Conexión de notificaciones caída. Operando en modo pasivo (BD).");
    };

    // ==========================================
    // 2. INTERACCIÓN DEL PANEL DESPLEGABLE FLOTANTE
    // ==========================================
    if (btnNoti && contenedorNoti) {
        btnNoti.addEventListener("click", function (e) {
            e.preventDefault();
            
            // Alternar visibilidad con tu clase existente
            contenedorNoti.classList.toggle("ocultar");

            // Si el panel se abre, extraemos la historia real de MariaDB
            if (!contenedorNoti.classList.contains("ocultar")) {
                cargarNotificacionesEnPanel();
            }
        });

        // Ocultar el panel si hacen clic en cualquier otra zona de la pantalla
        document.addEventListener("click", function (e) {
            if (!btnNoti.contains(e.target) && !contenedorNoti.contains(e.target)) {
                contenedorNoti.classList.add("ocultar");
            }
        });
    }
});

// ==========================================
// 3. CONSULTA AL CONTROLADOR (URL AMIGABLE .HTACCESS)
// ==========================================
function cargarNotificacionesEnPanel() {
    // Usamos el enrutamiento limpio de tu .htaccess
    fetch('Notificaciones')
        .then(response => response.json())
        .then(respuesta => {
            const listaUl = document.querySelector(".lista_noti");
            if (!listaUl) return;

            listaUl.innerHTML = ""; // Limpiar elementos estáticos viejos

            if (respuesta.accion === 'consultar' && respuesta.datos.length > 0) {
                
                respuesta.datos.forEach(noti => {
                    let iconName = "info";
                    let iconClass = "icon_noti_info";

                    if (noti.tipo === "cumpleaños") {
                        iconName = "cake";
                        iconClass = "icon_noti_info"; 
                    } else if (noti.tipo === "torneo") {
                        iconName = "trophy";
                        iconClass = "icon_noti_success";
                    } else if (noti.tipo === "cuenta_cobrar") {
                        iconName = "credit-card";
                        iconClass = "icon_noti_info"; 
                    }

                    const itemHTML = `
                        <li class="item_noti">
                            <div class="noti_icono_estado">
                                <i data-lucide="${iconName}" class="${iconClass}"></i>
                            </div>
                            <div class="noti_contenido">
                                <h3 class="noti_titulo">${noti.titulo}</h3>
                                <p class="noti_mensaje">${noti.mensaje}</p>
                                <span class="noti_tiempo">${noti.creado_en}</span>
                            </div>
                        </li>
                    `;
                    listaUl.insertAdjacentHTML("beforeend", itemHTML);
                });

                // Forzar re-escaneo de Lucide sobre las nuevas etiquetas li
                if (typeof lucide !== 'undefined') lucide.createIcons({ container: listaUl });

                // Reiniciar el badge numérico visual del botón
                const badge = document.getElementById("campana-notificaciones-badge");
                if (badge) {
                    badge.textContent = "0";
                    badge.classList.add("ocultar"); 
                }

            } else {
                listaUl.innerHTML = `<li class="item_noti"><p class="noti_mensaje" style="padding: 10px; text-align: center; width: 100%;">No tienes notificaciones por ahora.</p></li>`;
            }
        })
        .catch(err => console.error("Error al cargar historial desde el .htaccess:", err));
}

// ==========================================
// 4. FUNCIONES DE RENDERIZACIÓN PARA SWEETALERT Y BADGE
// ==========================================
function renderizarNotificacionSuperior(titulo, mensaje, tipo) {
    let nombreIcono = "bell";
    let colorIcono = "#3085d6";

    if (tipo === "cumpleaños") { nombreIcono = "cake"; colorIcono = "#ec4899"; }
    else if (tipo === "torneo") { nombreIcono = "trophy"; colorIcono = "#eab308"; }
    else if (tipo === "cuenta_cobrar") { nombreIcono = "credit-card"; colorIcono = "#ef4444"; }

    const contenidoHTML = `
        <div style="display: flex; align-items: center; gap: 10px; text-align: left;">
            <i data-lucide="${nombreIcono}" style="width: 24px; height: 24px; stroke-width: 2; color: ${colorIcono}; flex-shrink: 0;"></i>
            <div>
                <span style="font-weight: bold; display: block; font-size: 14px;">${titulo}</span>
                <span style="font-weight: normal; font-size: 12px; color: #555;">${mensaje}</span>
            </div>
        </div>
    `;
    
    muestraNoti(contenidoHTML, 10000);
}

function actualizarContadorBadge() {
    const badge = document.getElementById("campana-notificaciones-badge");
    if (badge) {
        let actual = parseInt(badge.textContent) || 0;
        badge.textContent = actual + 1;
        badge.classList.remove("ocultar");
    }
}

// Tu función original intacta adaptada para inicializar Lucide
function muestraNoti(titulo, tiempo) {
    const Toast = Swal.mixin({
        toast: true,
        position: "top",
        showConfirmButton: false,
        timer: tiempo,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
            if (typeof lucide !== 'undefined') {
                lucide.createIcons({ container: toast });
            }
        },
        customClass: {
            popup: "mi-popup",
            title: "mi-titulo"
        }
    });
    Toast.fire({ title: titulo });
}

// Función global para manejar imágenes rotas de atletas
function manejarErrorCamara(img) {
    // 1. Definimos el HTML exacto que quieres para el estado 'null'
    const htmlAvatarNull = '<div class="listado_avatar_null"><i class="icon_con" data-lucide="circle-user"></i></div>';

    // 2. Creamos un elemento temporal para convertir el string HTML en nodos DOM
    const placeholder = document.createElement('div');
    placeholder.innerHTML = htmlAvatarNull;
    const nuevoNodo = placeholder.firstChild; // Obtenemos el div.listado_avatar_null

    // 3. Reemplazamos la imagen rota por el nuevo contenedor del icono
    img.replaceWith(nuevoNodo);

    // 4. ¡IMPORTANTE! Forzamos a Lucide a renderizar el icono recién insertado
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}