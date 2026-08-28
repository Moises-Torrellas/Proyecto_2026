$('#busqueda').off('keyup').on('keyup', busqueda);
let timerBusqueda;
function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}
function busqueda() {
    clearTimeout(timerBusqueda);
    timerBusqueda = setTimeout(function () {
        let valorBusqueda = $('#busqueda').val();
        let datos = new FormData();
        datos.append('accion', 'consultar');
        datos.append('filtro', valorBusqueda);

        enviaAjax(datos);
    }, 500);
}

$(document).ready(function () {
    inicializarPaginador();

    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");
    Validacion("descripcion", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,100}$/, "Solo letras entre 3 y 100 caracteres", "proceso");
    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio()) {
                confirmar('¿Está seguro que quiere registrar este rol?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'incluir');
                        enviaAjax(datos);
                        /* for (var pair of datos.entries()) {
                            console.log(pair[0] + ': ' + pair[1]);
                        }  */
                    }
                });
            }
        }
        else if (accion == "modificar") {
            if (validarEnvio()) {
                confirmar('¿Está seguro que quiere modificar este rol?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'modificar');
                        enviaAjax(datos);
                        /* for (var pair of datos.entries()) {
                            console.log(pair[0] + ': ' + pair[1]);
                        }  */
                    }
                });
            }
        }
        else if (accion == "permisos") {
            confirmar('¿Está seguro que quiere guardar los permisos?', function (confirmado) {
                if (confirmado) {
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'guardar_permisos');
                    enviaAjax(datos);
                }
            });
        }
        else if (accion == "generar") {
            opcionesReporte(function (formato) {
                abrirAlertaEspara('Se esta generando el reporte', 'Espere un momento');
                var datos = new FormData($('#f')[0]);
                datos.append('accion', 'generar');
                datos.append('formato', formato);
                enviaAjax(datos);
            });
        }
    });

    $("#incluir").on("click", function () {
        limpia();
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Rol");
        $("#titulo_modal").text("Registrar Rol");
        $('#row_nombre').show();
        $('#row_permisos').hide();
        $('#proceso').show();

        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        $('#row_nombre').show();
        $('#row_permisos').hide();
        $('#proceso').show();
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar al registro que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevo Registro', description: 'Si pulsa aqui se abrira un modal para registrar un nuevo rol', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF o Excel.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child',
                popover: { title: 'Registros', description: 'Aqui se mostraran todos los registros. Este es un registro individual.', position: 'top' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .cbt_m',
                popover: { title: 'Permisos del Rol', description: 'Si pulsa aqui se abrira un modal para administrar los permisos asociados a este rol.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .cbt_v',
                popover: { title: 'Modificar Registro', description: 'Si pulsa aqui se abrira un modal para modificar el registro seleccionado.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .cbt_r',
                popover: { title: 'Eliminar Registro', description: 'Si pulsa aqui eliminara el registro seleccionado.', position: 'left' }
            },
            {
                element: '#rowsPerPage',
                popover: { title: 'Registros Deseados', description: 'Aqui podra seleccionar la cantidad de registros que quiere que se muestren.', position: 'top' }
            },
            {
                element: '#botonera',
                popover: { title: 'Cambiar de Pagina', description: 'Botones para cambiar de página.', position: 'top' }
            },
            {
                element: '#cantidad',
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de roles registrados.', position: 'top' }
            },
        ];

        // Iniciar tour
        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });

    // Auto-check logic
    $('#tabla_permisos').on('change', '.checkbox', function () {
        var id = $(this).attr('id');
        var partes = id.split('_');
        if (partes.length >= 3) {
            var accion = partes[1];
            var idModulo = partes[2];

            if (accion !== 'ingresar' && $(this).is(':checked')) {
                $('#check_ingresar_' + idModulo).prop('checked', true);
            }

            if (accion === 'ingresar' && !$(this).is(':checked')) {
                $('#check_registrar_' + idModulo).prop('checked', false);
                $('#check_modificar_' + idModulo).prop('checked', false);
                $('#check_eliminar_' + idModulo).prop('checked', false);
                $('#check_reporte_' + idModulo).prop('checked', false);
                $('#check_otros_' + idModulo).prop('checked', false);
            }
        }
    });
});

function validarEnvio() {
    if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
        $('#nombre'), $("#nombre_spam"), "Solo letras entre 3 y 30 caracteres")) {
        muestraMensaje("error", 2000, "Error", "Solo puede ingresar letra, Maximo 30 caracteres");
        return false;
    }
    else if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,100}$/,
        $('#descripcion'), $("#descripcion_spam"), "Solo letras entre 3 y 100 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar una descripcion valida");
        return false;
    }
    else if (accion === "permisos") {
        return true;
    }
    return true;
}

function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}

/* function generarFilaPermisos(dato) {
    var ingresarChecked = dato.ingresar == 1 ? 'checked' : '';
    var registrarChecked = dato.registrar == 1 ? 'checked' : '';
    var modificarChecked = dato.modificar == 1 ? 'checked' : '';
    var eliminarChecked = dato.eliminar == 1 ? 'checked' : '';
    var reporteChecked = dato.reporte == 1 ? 'checked' : '';
    var otrosChecked = dato.otros == 1 ? 'checked' : '';

    return `<tr>
                        <td style="display: none;">
                            <input type="hidden" name="id_modulo[]" value="${dato.id_modulo}">
                        </td>
                        <td>${escapeHTML(dato.nombre_modulo)}</td>
                            
                        <td>
                            <label class="checkbox-container">
                                <input class="checkbox" type="checkbox" id="check_ingresar_${dato.id_modulo}" name="check_ingresar[${dato.id_modulo}]" value="1" ${ingresarChecked}>
                                <span class="custom-checkbox"></span>
                            </label>
                        </td>
                            
                        <td>
                            <label class="checkbox-container">
                                <input class="checkbox" type="checkbox" id="check_registrar_${dato.id_modulo}" name="check_registrar[${dato.id_modulo}]" value="1" ${registrarChecked}>
                                <span class="custom-checkbox"></span>
                            </label>
                        </td>
                            
                        <td>
                            <label class="checkbox-container">
                                <input class="checkbox" type="checkbox" id="check_modificar_${dato.id_modulo}" name="check_modificar[${dato.id_modulo}]" value="1" ${modificarChecked}>
                                <span class="custom-checkbox"></span>
                            </label>
                        </td>

                        <td>
                            <label class="checkbox-container">
                                <input class="checkbox" type="checkbox" id="check_eliminar_${dato.id_modulo}" name="check_eliminar[${dato.id_modulo}]" value="1" ${eliminarChecked}>
                                <span class="custom-checkbox"></span>
                            </label>
                        </td>

                        <td>
                            <label class="checkbox-container">
                                <input class="checkbox" type="checkbox" id="check_reporte_${dato.id_modulo}" name="check_reporte[${dato.id_modulo}]" value="1" ${reporteChecked}>
                                <span class="custom-checkbox"></span>
                            </label>
                        </td>

                        <td>
                            <label class="checkbox-container">
                                <input class="checkbox" type="checkbox" id="check_otros_${dato.id_modulo}" name="check_otros[${dato.id_modulo}]" value="1" ${otrosChecked}>
                                <span class="custom-checkbox"></span>
                            </label>
                        </td>
                    </tr>`;
} */

function modificar(datos) {
    limpia();
    limpia_Tablas();
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Rol");
    $("#titulo_modal").text("Modificar Rol");
    $('#id').val(datos[0].id_rol);
    $('#nombre').val(datos[0].nombre_rol);
    $('#descripcion').val(datos[0].descripcion);
    $('#row_nombre').show();
    $('#row_modulo').hide();
    $('#row_modulo').hide();
    $('#row_permisos').hide();
    $('#proceso').show();
    abrirModal();
}

// Variable global para almacenar los datos de permisos
var datosPermisosGlobal = [];

function mostrarPermisos(datos) {
    limpia();
    limpia_Tablas();
    $("#proceso").data("accion", "permisos");
    $("#proceso").text("Guardar Permisos");
    $("#titulo_modal").text("Permisos del Rol");
    $('#id').val(datos[0].id_rol);
    $('#nombre').val(datos[0].nombre_rol);

    $('#row_nombre').hide();
    $('#row_permisos').show();
    $('#proceso').show();

    // Guardar datos globalmente para el buscador
    datosPermisosGlobal = datos;

    // Renderizar en pestañas
    renderizarPermisosEnTabs(datos);

    // Limpiar el buscador
    $('#buscador_permisos').val('');

    // Activar la primera pestaña
    $('.permisos_tab').removeClass('activa');
    $('.permisos_tab[data-tab="asignados"]').addClass('activa');
    $('.permisos_tab_contenido').removeClass('activo');
    $('#tab_asignados').addClass('activo');

    abrirModal();
}

function renderizarPermisosEnTabs(datos, filtro) {
    filtro = (filtro || '').toLowerCase().trim();

    // Agrupar permisos por módulo
    let modulos = {};
    datos.forEach(dato => {
        if (!modulos[dato.id_modulo]) {
            modulos[dato.id_modulo] = {
                id_modulo: dato.id_modulo,
                nombre_modulo: dato.nombre_modulo,
                icono: dato.icono || 'folder',
                estatus_modulo: dato.estatus_modulo,
                permisos: []
            };
        }
        modulos[dato.id_modulo].permisos.push(dato);
    });

    let htmlAsignados = '';
    let htmlNoAsignados = '';
    let contAsignados = 0;
    let contNoAsignados = 0;

    Object.values(modulos).forEach(modulo => {
        // Filtrar por nombre del módulo
        if (filtro && !modulo.nombre_modulo.toLowerCase().includes(filtro)) return;

        let tieneAlgunAsignado = modulo.permisos.some(p => p.asignado == 1);

        if (tieneAlgunAsignado) {
            // El módulo tiene al menos 1 permiso asignado: va completo a "Asignados"
            contAsignados++;
            htmlAsignados += generarBloqueModulo(modulo, modulo.permisos, true);
        } else {
            // El módulo no tiene ningún permiso asignado: va completo a "No Asignados"
            contNoAsignados++;
            htmlNoAsignados += generarBloqueModulo(modulo, modulo.permisos, false);
        }
    });

    // Si no hay contenido, mostrar mensaje
    if (!htmlAsignados) {
        htmlAsignados = '<div class="permisos_vacio"><i class="fi fi-sr-check-circle"></i>No hay permisos asignados' + (filtro ? ' para esta búsqueda' : '') + '</div>';
    }
    if (!htmlNoAsignados) {
        htmlNoAsignados = '<div class="permisos_vacio"><i class="fi fi-sr-circle-xmark"></i>No hay permisos sin asignar' + (filtro ? ' para esta búsqueda' : '') + '</div>';
    }

    $("#tabla_permisos_asignados").html(htmlAsignados);
    $("#tabla_permisos_no_asignados").html(htmlNoAsignados);

    // Actualizar badges
    $('#badge_asignados').text(contAsignados);
    $('#badge_no_asignados').text(contNoAsignados);

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function generarBloqueModulo(modulo, permisos, esAsignado) {
    let cantidadPermisos = permisos.length;

    let html = `
    <div class="listado_contenedor_grupal">
        <div class="listado_item" onclick="toggleDetalles(this)">
            <div class="listado_col_principal">
                <div class="listado_avatar_null"><i class="icon_con" data-lucide="${modulo.icono}"></i></div>
                <div class="listado_info_base">
                    <span class="listado_titulo">${escapeHTML(modulo.nombre_modulo)}</span>
                </div>
            </div>

            <div class="listado_col_datos">
                <div class="listado_dato_grupo">
                    <small>${esAsignado ? 'Permisos Activos' : 'Permisos Disponibles'}</small>
                    <span>${cantidadPermisos} Opción(es)</span>
                </div>
            </div>

            <div class="listado_col_acciones">
                <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
            </div>
        </div>

        <div class="listado_detalle_oculto">
            <div class="detalle_expandido_container" style="padding: 15px;">
                <div class="lista_sub_items">`;

    permisos.forEach(dato => {
        let permisoChecked = (dato.asignado == 1) ? 'checked' : '';
        let nombreLower = dato.nombre_permiso.toLowerCase();
        let claseTipo = (nombreLower.includes('ingresar') || nombreLower.includes('consultar') || nombreLower.includes('acceder') || nombreLower.includes('listar'))
            ? 'permiso-acceso'
            : 'permiso-accion';

        html += `
        <div class="sub_item_fila">
            <div class="sub_item_info" style="flex: 2;">
                <span class="sub_item_titulo">${escapeHTML(dato.nombre_permiso)}</span>
                <small style="display: block; color: #666; font-size: 0.85em; margin-top: 2px;">Descripción: ${escapeHTML(dato.descripcion || '')}</small>
            </div>

            <div class="sub_item_acciones">
                <label class="checkbox-container">
                    <input class="checkbox ${claseTipo}" type="checkbox" id="check_permiso_${dato.id_permiso}" name="permisos[${dato.id_permiso}]" value="1" ${permisoChecked}>
                    <span class="custom-checkbox"></span>
                </label>
            </div>
        </div>`;
    });

    html += `
                </div>
            </div>
        </div>
    </div>`;

    return html;
}

// Eventos de pestañas y buscador (delegados al documento para que funcionen siempre)
$(document).off('click.permisos_tabs').on('click.permisos_tabs', '.permisos_tab', function () {
    let tab = $(this).data('tab');
    $(this).closest('.permisos_tabs').find('.permisos_tab').removeClass('activa');
    $(this).addClass('activa');
    $(this).closest('#tabla_permisos_container').find('.permisos_tab_contenido').removeClass('activo');
    $('#tab_' + tab).addClass('activo');
});

$(document).off('input.permisos_buscar').on('input.permisos_buscar', '#buscador_permisos', function () {
    let filtro = $(this).val();
    renderizarPermisosEnTabs(datosPermisosGlobal, filtro);
});

function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar este rol?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}

function CargarPermisos(id) {
    var datos = new FormData();
    datos.append('accion', 'CargarPermisos');
    datos.append('id', id);
    enviaAjax(datos);
}

function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');

    // Inyectamos directamente el bloque HTML estructurado que procesó el servidor
    contenedor.html(htmlRecibido);

    // Reactivamos las librerías visuales y los comportamientos estéticos
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

function escapeHTML(texto) {
    var caracteres = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return texto.replace(/[&<>"']/g, m => caracteres[m]);
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
        timeout: 120000,
        success: function (respuesta) {
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta);
                return;
            }
            try {
                var lee = JSON.parse(respuesta);

                if (lee.accion == "buscar") {
                    modificar(lee.datos);
                }
                else if (lee.accion == "CargarPermisos") {
                    mostrarPermisos(lee.datos);
                }
                else if (lee.accion == "incluir") {
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                    consultar();
                    limpia();
                    cerrarModal();
                } else if (lee.accion == "modificar" || lee.accion == "guardar_permisos") {
                    muestraMensaje("success", 2000, "Modificacion Exitosa", lee.mensaje);
                    consultar();
                    limpia();
                    limpia_Tablas();
                    cerrarModal();
                } else if (lee.accion == "eliminar") {
                    muestraMensaje("success", 2000, "Eliminacion Exitosa", lee.mensaje);
                    consultar();
                } else if (lee.accion == "reporte") {
                    cerrarAlertaEspara();
                    muestraMensaje("success", 2000, "Creado Exitosamente", 'Se ha generado el reporte');
                    setTimeout(function () {
                        window.open(lee.archivo, '_blank');
                    }, 2000);
                    cerrarModal();
                    limpia();
                } else if (lee.accion == "error") {
                    cerrarAlertaEspara();
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