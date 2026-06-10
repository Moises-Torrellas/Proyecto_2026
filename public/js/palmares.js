$(document).ready(function () {
    // 1. Cargar datos en los selects (MultiConsulta)
    MultiConsulta();

    // 2. Manejo de Pestañas
    $('.pestana-btn').on('click', function () {
        $('.pestana-btn').removeClass('activa');
        $('.pestana-content').removeClass('activa');

        $(this).addClass('activa');
        let target = $(this).data('target');
        $(target).addClass('activa');

        // Cargar datos de la pestaña (opcional si se recarga cada vez, o usar lo precargado)
        let tipo = target === '#tab-individual' ? 'individual' : 'grupal';
        // Paginador
        inicializarPaginadorPalmares(tipo);
    });

    // 3. Inicializar Paginadores para ambas tablas
    inicializarPaginadorPalmares('individual');
    inicializarPaginadorPalmares('grupal');

    // 4. Manejo del botón Limpiar
    $('#limpiar').on('click', function (e) {
        // Evita que main.js ejecute su limpia() primero y muestre todos los contenedores
        e.stopImmediatePropagation();

        // Llamamos al limpia global
        limpia();

        // Y ahora ajustamos la visibilidad según el contexto del modal actual
        let esReporte = $('#seccion_reportes').is(':visible');
        let tipoPalmares = $('#tipo_palmares').val();

        // Ocultar todas las secciones
        $('#seccion_individual').hide();
        $('#seccion_grupal').hide();
        $('#seccion_reportes').hide();

        if (esReporte) {
            $('#seccion_reportes').show();
        } else {
            if (tipoPalmares === 'individual') {
                $('#seccion_individual').show();
            } else if (tipoPalmares === 'grupal') {
                $('#seccion_grupal').show();
            }
        }
    });

    // 5. Enviar formulario (Proceso) adaptado a Pagos
    $('#proceso').on('click', function () {
        let accion = $(this).data('accion');

        if (accion === 'incluir') {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este palmarés?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'incluir');
                        enviaAjax(datos);
                    }
                });
            }
        } else if (accion === 'modificar') {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere modificar este palmarés?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'modificar');
                        enviaAjax(datos);
                    }
                });
            }
        } else if (accion === 'generar') {
            confirmar('¿Está seguro que quiere generar un reporte?', function (confirmado) {
                if (confirmado) {
                    abrirAlertaEspara('Se esta generando el reporte', 'Espere un momento');
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    enviaAjax(datos);
                }
            });
        }
    });

    // Evento manual para el botón #incluir (Nuevo)
    // Evento manual para el botón #incluir (Nuevo)
    $('#incluir').off('click').on('click', function (e) {
        ;
        mostrarAlertaTipoPalmares(
            '¿Qué tipo de Palmarés desea registrar?',
            'Seleccione una opción para continuar',
            function (tipo) {
                abrirModalPalmares(tipo);
            }
        );
    });

    // 6. Botón de Reportes (Abrir modal)
    $('#generar').off('click').on('click', function () {
        mostrarAlertaTipoPalmares(
            '¿Para qué tipo de Palmarés desea el reporte?',
            'Seleccione una opción para continuar',
            function (tipo) {
                abrirModalReporte(tipo);
            }
        );
    });

    // Inicializar Select2 en los selects principales
    $('#torneo_ind, #torneo_grp, #premio_ind, #premio_grp, #atleta, #equipo, #palmares_atleta, #palmares_equipo').select2({
        placeholder: "Seleccione una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal')
    });

    // 7. Tour Guiado
    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Búsqueda', description: 'Aquí puedes buscar atletas, equipos, torneos o premios rápidamente.', position: 'bottom' }
            },
            {
                element: '.pestana-btn.activa',
                popover: { title: 'Pestañas de Palmarés', description: 'Navega entre palmarés individuales y grupales.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Registrar Palmarés', description: 'Presiona aquí para registrar un nuevo premio para un atleta o equipo.', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Permite generar reportes estadísticos o tabulares en formato PDF.', position: 'left' }
            },
            {
                element: '.resultadoconsulta',
                popover: { title: 'Historial de Palmarés', description: 'Aquí se muestran agrupados los premios.', position: 'top' }
            }
        ];

        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });
});

// VARIABLES GLOBALES PARA LISTAS
let listaTorneos = [];
let listaPremios = [];
let listaAtletas = [];
let listaEquipos = [];
let tipoBuscadoActual = '';

function MultiConsulta() {
    let datos = new FormData();
    datos.append('accion', 'MultiConsulta');
    enviaAjax(datos);
}

// Para usar la misma estructura de Pagos (selects)
function construirSelect(idSelect, datos, campoId, campo1, campo2 = null, campo3 = null, campo4 = null, campo5 = null) {
    var select = $('#' + idSelect);
    select.empty();

    if (!select.prop('multiple')) {
        select.append('<option value="" selected disabled>Seleccione una opción</option>');
    }

    datos.forEach(dato => {
        let textoMostrar = "";

        // Torneos
        if (idSelect === 'torneo_ind' || idSelect === 'torneo_grp') {
            textoMostrar = `${dato[campo1]} (${dato[campo2]})`;
        }
        // Atletas
        else if (idSelect === 'atleta' || idSelect === 'palmares_atleta') {
            textoMostrar = `${dato[campo1]} ${dato[campo2]} - ${dato[campo3]}`;
        }
        // Equipos
        else if (idSelect === 'equipo' || idSelect === 'palmares_equipo') {
            textoMostrar = `${dato[campo1]} - ${dato[campo2]}`;
        }
        // Premios
        else if (idSelect === 'premio_ind' || idSelect === 'premio_grp') {
            textoMostrar = escapeHTML(String(dato[campo1]));
        }
        else {
            textoMostrar = escapeHTML(String(dato[campo1]));
        }

        var linea = `<option value="${dato[campoId]}">${textoMostrar}</option>`;
        select.append(linea);
    });
}

function escapeHTML(texto) {
    var caracteres = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(texto).replace(/[&<>"']/g, m => caracteres[m]);
}

// ==========================================
// MÉTODOS CRUD
// ==========================================

function abrirModalPalmares(tipo) {
    limpia(); // Limpia campos
    $('#tipo_palmares').val(tipo);
    $('#id').val('');

    // Resetear select2
    $('#torneo_ind, #torneo_grp, #premio_ind, #premio_grp, #atleta, #equipo').val(null).trigger('change');

    // Ajustar visibilidad
    $('#seccion_reportes').hide();

    if (tipo === 'individual') {
        $('#titulo_modal').text('Registrar Palmarés Individual');
        $('#seccion_individual').show();
        $('#seccion_grupal').hide();
    } else {
        $('#titulo_modal').text('Registrar Palmarés Grupal');
        $('#seccion_individual').hide();
        $('#seccion_grupal').show();
    }

    // El torneo se puede elegir al incluir
    $('#torneo_ind, #torneo_grp').prop('disabled', false);

    $('#proceso').text('Registrar Palmarés').data('accion', 'incluir');
    abrirModal();
}

function abrirModalReporte(tipo) {
    limpia(); // Limpia campos
    $('#tipo_palmares').val(tipo); // Guardamos el tipo para saber qué filtrar
    
    // Ocultamos reportes (ya no existe, pero por seguridad) y mostramos la sección correspondiente
    $('#seccion_reportes').hide();
    
    if (tipo === 'individual') {
        $('#titulo_modal').text('Reporte Palmarés Individual');
        $('#seccion_individual').show();
        $('#seccion_grupal').hide();
    } else {
        $('#titulo_modal').text('Reporte Palmarés Grupal');
        $('#seccion_individual').hide();
        $('#seccion_grupal').show();
    }
    
    // Cambiamos el comportamiento del botón de proceso
    $('#proceso').text('Generar Reporte').data('accion', 'generar');
    
    // Deshabilitamos validaciones de registro si es necesario o simplemente lo abrimos
    abrirModal();
}

function buscar(id, tipo) {
    tipoBuscadoActual = tipo;
    let datos = new FormData();
    datos.append('accion', (tipo === 'individual') ? 'buscarIndividual' : 'buscarGrupal');
    datos.append('id', id);
    enviaAjax(datos);
}

function llenarModal(data, tipo) {
    limpia();
    $('#tipo_palmares').val(tipo);
    $('#id').val(data.id_individual || data.id_grupal);
    $('#seccion_reportes').hide();

    if (tipo === 'individual') {
        $('#titulo_modal').text('Modificar Palmarés Individual');
        $('#seccion_individual').show();
        $('#seccion_grupal').hide();

        $('#torneo_ind').val(data.id_torneo).prop('disabled', true).trigger('change');
        $('#premio_ind').val(data.id_premio).trigger('change');
        $('#atleta').val(data.id_atleta).trigger('change');
    } else {
        $('#titulo_modal').text('Modificar Palmarés Grupal');
        $('#seccion_individual').hide();
        $('#seccion_grupal').show();

        $('#torneo_grp').val(data.id_torneo).prop('disabled', true).trigger('change');
        $('#premio_grp').val(data.id_premio).trigger('change');
        $('#equipo').val(data.id_equipo).trigger('change');
    }

    $('#proceso').text('Modificar Palmarés').data('accion', 'modificar');
    abrirModal();
}

function eliminar(id, tipo) {
    confirmar(`¿Estás seguro de que quieres eliminar este palmarés ${tipo}?`, function (result) {
        if (result) {
            let datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            datos.append('tipo_palmares', tipo);
            enviaAjax(datos);
        }
    });
}

// Validaciones Frontend (Igual que en Pagos)
function validarEnvio(accion) {
    let tipo = $('#tipo_palmares').val();

    if (accion === "incluir" || accion === "modificar") {
        if (tipo === 'individual') {
            if ($('#torneo_ind').val() === "" || $('#torneo_ind').val() === null) {
                muestraMensaje("error", 2000, "Error", "Debe seleccionar un torneo");
                return false;
            }
            if ($('#premio_ind').val() === "" || $('#premio_ind').val() === null) {
                muestraMensaje("error", 2000, "Error", "Debe seleccionar un premio individual");
                return false;
            }
            if ($('#atleta').val() === "" || $('#atleta').val() === null) {
                muestraMensaje("error", 2000, "Error", "Debe seleccionar un atleta");
                return false;
            }
        } else if (tipo === 'grupal') {
            if ($('#torneo_grp').val() === "" || $('#torneo_grp').val() === null) {
                muestraMensaje("error", 2000, "Error", "Debe seleccionar un torneo");
                return false;
            }
            if ($('#premio_grp').val() === "" || $('#premio_grp').val() === null) {
                muestraMensaje("error", 2000, "Error", "Debe seleccionar un premio grupal");
                return false;
            }
            if ($('#equipo').val() === "" || $('#equipo').val() === null) {
                muestraMensaje("error", 2000, "Error", "Debe seleccionar un equipo");
                return false;
            }
        }
    }
    return true;
}

// ==========================================
// CENTRALIZADOR AJAX (Como en Pagos)
// ==========================================
var token = $('meta[name="csrf-token"]').attr('content');

function enviaAjax(datos) {
    $.ajax({
        async: true,
        url: "", // Va a la misma página del router actual (Palmares)
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
            cerrarAlertaEspara();

            // Si devuelve HTML (Recarga de listas)
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                // Hay dos contenedores dependiendo si consultamos ind o grp
                let tipoReq = datos.get('accion') === 'consultarIndividual' ? 'individual' : 'grupal';
                let containerId = (tipoReq === 'individual') ? '#resultadoconsulta-ind' : '#resultadoconsulta-grp';

                $(containerId).html(respuesta);
                if (typeof lucide !== 'undefined') lucide.createIcons();
                if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
                inicializarPaginadorPalmares(tipoReq);
                return;
            }

            try {
                var lee = JSON.parse(respuesta);

                if (lee.accion === "MultiConsulta") {
                    listaTorneos = lee.torneos;
                    listaPremios = lee.premios;
                    listaAtletas = lee.atletas;
                    listaEquipos = lee.equipos;

                    construirSelect('torneo_ind', lee.torneos, 'id_torneo', 'nombre', 'fecha_inicio');
                    construirSelect('torneo_grp', lee.torneos, 'id_torneo', 'nombre', 'fecha_inicio');

                    construirSelect('atleta', lee.atletas, 'id_atleta', 'nombres', 'apellidos', 'doc_identidad');
                    construirSelect('palmares_atleta', lee.atletas, 'id_atleta', 'nombres', 'apellidos', 'doc_identidad');

                    construirSelect('equipo', lee.equipos, 'id_equipos', 'nombre', 'categoria');
                    construirSelect('palmares_equipo', lee.equipos, 'id_equipos', 'nombre', 'categoria');

                    // Filtrar premios por tipo I o G
                    let premiosInd = lee.premios.filter(p => p.tipo === 'I');
                    let premiosGrp = lee.premios.filter(p => p.tipo === 'G');
                    construirSelect('premio_ind', premiosInd, 'id_premio', 'nombre');
                    construirSelect('premio_grp', premiosGrp, 'id_premio', 'nombre');
                }
                else if (lee.accion === "incluir") {
                    let tipoListado = lee.tipo_palmares === 'individual' ? 'consultarIndividual' : 'consultarGrupal';
                    let d = new FormData(); d.append('accion', tipoListado);
                    enviaAjax(d); // Recargar lista respectiva

                    limpia();
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                    cerrarModal();
                }
                else if (lee.accion === "eliminar") {
                    let tipoListado = lee.tipo_palmares === 'individual' ? 'consultarIndividual' : 'consultarGrupal';
                    let d = new FormData(); d.append('accion', tipoListado);
                    enviaAjax(d); // Recargar lista respectiva

                    muestraMensaje("success", 2000, "Retiro Exitoso", lee.mensaje);
                }
                else if (lee.accion === "modificar") {
                    let tipoListado = lee.tipo_palmares === 'individual' ? 'consultarIndividual' : 'consultarGrupal';
                    let d = new FormData(); d.append('accion', tipoListado);
                    enviaAjax(d); // Recargar lista respectiva

                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Modificación Exitosa", lee.mensaje);
                }
                else if (lee.accion === "buscar") {
                    if (lee.datos.length > 0) {
                        llenarModal(lee.datos[0], tipoBuscadoActual);
                    } else {
                        muestraMensaje("error", 2000, "Error", "Registro no encontrado");
                    }
                }
                else if (lee.accion === "reporte") {
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
                else if (lee.accion === "error") {
                    muestraMensaje("error", 3000, "Error", lee.mensaje);
                }
            } catch (e) {
                console.error("Error en JSON", e);
            }
        },
        error: function (request, status, err) {
            cerrarAlertaEspara();
            if (status === "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + status + err);
            }
        }
    });
}

// ==========================================
// PAGINADOR ESPECÍFICO PARA TABS
// ==========================================

function inicializarPaginadorPalmares(tipo) {
    const selectorResultados = (tipo === 'individual') ? '#resultadoconsulta-ind' : '#resultadoconsulta-grp';
    const selectorRowsPerPage = (tipo === 'individual') ? '#rowsPerPage-ind' : '#rowsPerPage-grp';
    const selectorBotonera = (tipo === 'individual') ? '#botonera-ind' : '#botonera-grp';
    const selectorCantidad = (tipo === 'individual') ? '#cantidadRegistros-ind' : '#cantidadRegistros-grp';

    const $contenedorListado = $(selectorResultados);
    const $items = $contenedorListado.find('.listado_contenedor_grupal');
    const $registros = $items.length > 0 ? $items : $contenedorListado.find('.listado_item');
    const $rowsPerPageSelect = $(selectorRowsPerPage);
    const $paginationContainer = $(selectorBotonera);

    let currentPage = 1;
    let itemsPerPage = parseInt($rowsPerPageSelect.val()) || 10;

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

    $(selectorCantidad).text($registros.length);
}
