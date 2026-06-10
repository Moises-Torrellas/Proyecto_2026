$(document).ready(function () {

    // 1. Manejo del botón "Nuevo Palmarés"
    $("#incluir").on("click", function () {
        mostrarAlertaTipoPalmares(
            '¿Qué tipo de palmarés desea registrar?',
            'Seleccione si desea registrar un palmarés Individual o Grupal',
            function(tipo) {
                abrirModalPalmares(tipo);
            }
        );
    });

    // 2. Manejo del botón "Generar Reporte"
    $("#generar").on("click", function () {
        mostrarAlertaTipoPalmares(
            '¿Qué reporte desea generar?',
            'Seleccione si desea un reporte Individual o Grupal',
            function(tipo) {
                abrirModalReporte(tipo);
            }
        );
    });

    // 3. Manejo de botones Limpiar
    $("#limpiar").on("click", function () {
        limpia();
    });

    // 4. Manejo de Procesamiento
    $("#proceso").on("click", function () {
        var accion = $(this).data("accion");
        var datos = new FormData($("#f")[0]);
        datos.append('accion', accion);
        // enviaAjax(datos);
    });

    // 5. Buscadores Duales en Tiempo Real
    $('#busqueda_individual').on('keyup', function () {
        var valor = $(this).val().toLowerCase();
        var hayResultados = false;
        $('#resultado_individual .listado_contenedor_grupal').each(function () {
            var texto = $(this).text().toLowerCase();
            if (texto.indexOf(valor) > -1) {
                $(this).show();
                hayResultados = true;
            } else {
                $(this).hide();
            }
        });

        if (!hayResultados && valor !== '') {
            if ($('#resultado_individual .listado_vacio').length === 0) {
                $('#resultado_individual').append('<div class="listado_vacio"><p>No se encontraron coincidencias</p></div>');
            } else {
                $('#resultado_individual .listado_vacio').show();
            }
        } else {
            $('#resultado_individual .listado_vacio').hide();
        }
        
        if (valor === '') {
            inicializarPaginadorDual('individual', '#resultado_individual', '#botonera_individual', '#rowsPerPage_individual', '#cantidadRegistros_individual');
        }
    });

    $('#busqueda_grupal').on('keyup', function () {
        var valor = $(this).val().toLowerCase();
        var hayResultados = false;
        $('#resultado_grupal .listado_contenedor_grupal').each(function () {
            var texto = $(this).text().toLowerCase();
            if (texto.indexOf(valor) > -1) {
                $(this).show();
                hayResultados = true;
            } else {
                $(this).hide();
            }
        });

        if (!hayResultados && valor !== '') {
            if ($('#resultado_grupal .listado_vacio').length === 0) {
                $('#resultado_grupal').append('<div class="listado_vacio"><p>No se encontraron coincidencias</p></div>');
            } else {
                $('#resultado_grupal .listado_vacio').show();
            }
        } else {
            $('#resultado_grupal .listado_vacio').hide();
        }
        
        if (valor === '') {
            inicializarPaginadorDual('grupal', '#resultado_grupal', '#botonera_grupal', '#rowsPerPage_grupal', '#cantidadRegistros_grupal');
        }
    });

    // 6. Lógica de Pestañas
    $(".pestana-btn").on("click", function () {
        // Quitar clase activa a todas las pestañas y ocultar todos los contenidos
        $(".pestana-btn").removeClass("activa");
        $(".pestana-content").removeClass("activa");
        
        // Agregar clase activa a la pestaña clickeada y mostrar su contenido
        $(this).addClass("activa");
        var target = $(this).data("target");
        $(target).addClass("activa");
    });

});

// ==========================
// FUNCIONES DE MODAL UNIFICADO
// ==========================

function abrirModalPalmares(tipo) {
    limpia(); // Limpia todos los campos del formulario
    
    $("#proceso").data("accion", "incluir");
    $("#proceso").text("Registrar").removeClass("btn_verde").addClass("btn_azul");
    $("#tipo_palmares").val(tipo);
    $("#seccion_reportes").hide();

    if (tipo === 'grupal') {
        $("#titulo_modal").text("Registrar Palmarés Grupal");
        $("#seccion_grupal").show();
        $("#seccion_individual").hide();
    } else {
        $("#titulo_modal").text("Registrar Palmarés Individual");
        $("#seccion_individual").show();
        $("#seccion_grupal").hide();
    }

    abrirModal();
}

function abrirModalReporte(tipo) {
    limpia();
    
    $("#proceso").data("accion", "generar_reporte");
    $("#proceso").text("Generar PDF").removeClass("btn_azul").addClass("btn_verde");
    $("#tipo_palmares").val(tipo); // Reutilizamos este campo para saber qué tipo de reporte es
    
    // Ocultar campos de registro
    $("#seccion_grupal").hide();
    $("#seccion_individual").hide();
    
    // Mostrar solo filtros
    $("#seccion_reportes").show();

    if (tipo === 'grupal') {
        $("#titulo_modal").text("Generar Reporte Grupal");
        $("#col_filtro_equipo").show();
        $("#col_filtro_atleta").hide();
    } else {
        $("#titulo_modal").text("Generar Reporte Individual");
        $("#col_filtro_atleta").show();
        $("#col_filtro_equipo").hide();
    }

    abrirModal();
}


// ==========================
// FUNCIÓN DE PAGINADOR DUAL
// ==========================

function inicializarPaginadorDual(tipo, contResultadosID, contBotoneraID, selectRowsID, labelCantidadID) {
    const $contenedorListado = $(contResultadosID);
    const $items = $contenedorListado.find('.listado_contenedor_grupal');
    const $registros = $items.length > 0 ? $items : $contenedorListado.find('.listado_item');
    
    const $rowsPerPageSelect = $(selectRowsID);
    const $paginationContainer = $(contBotoneraID);

    let currentPage = 1;
    let itemsPerPage = parseInt($rowsPerPageSelect.val()) || 5;

    function showPage(page) {
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;

        $registros.hide();

        $registros.slice(start, end).each(function () {
            if ($(this).hasClass('listado_contenedor_grupal')) {
                $(this).css('display', 'block');
            } else {
                $(this).css('display', 'flex');
            }
        });
    }

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

    $(labelCantidadID).text($registros.length);
}

// ==========================
// LÓGICA AJAX BASE
// ==========================

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
                if (lee.accion === "incluir") {
                    cerrarModal();
                    muestraMensaje("success", 2000, "Éxito", "Operación realizada correctamente");
                } else if (lee.accion === "generar_reporte") {
                    cerrarModal();
                    muestraMensaje("success", 2000, "Éxito", "Reporte generado");
                } else if (lee.accion === "error") {
                    muestraMensaje("error", 2000, "Error", lee.mensaje);
                }
            } catch (e) {
                console.error("Error parseando JSON: ", e);
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + request + status + err);
            }
        }
    });
}
