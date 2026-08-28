$('#busqueda').off('keyup').on('keyup', busqueda);
let timerBusqueda;
let isSuperAdminEdit = false;
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
function consultarRoles() {
    var datos = new FormData();
    datos.append('accion', 'consultarRoles');
    enviaAjax(datos);
}

$(document).ready(function () {
    inicializarPaginador();
    consultarRoles();

    $("#cedula").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        if (input.length > 4) {
            input = input.substring(0, 8);
        }
        $(this).val(input);
    });

    $("#telefono").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        if (input.length > 4) {
            input = input.substring(0, 4) + '-' + input.substring(4, 11);
        }
        $(this).val(input);
    });

    // Validación de Cédula
    Validacion("cedula", /^[0-9\b]*$/, /^[0-9]{7,8}$/, "Minimo 7 maximo 8 digitos, solo numeros", "proceso");

    // Validación de Nombre
    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");

    // Validación de Apellido
    Validacion("apellido", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");

    // Validación de Teléfono
    Validacion("telefono", /^[0-9\-\b]*$/, /^[0-9]{4}[-]{1}[0-9]{7}$/, "El formato es 0400-0000000");

    // Validación de Correo
    Validacion("correo", /^[a-zA-Z0-9@._\-]*$/, /^(?=.{3,60}$)[^\s@]+@[^\s@]+\.(com|org|net|edu|gov|mil|info|io|co|es|mx|ar|cl|pe|br)$/i, "Ejemplo: usuario@dominio.com");

    // Validación de Contraseña
    Validacion("contraseña", /^[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]*$/, /^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]{8,20}$/, "8-20 caracteres, incluye Mayúscula, Minúscula, Número y Carácter Especial");


    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este usuario?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        if (window.croppedImageBlob) {
                            datos.set('foto', window.croppedImageBlob, 'foto_recortada.jpg');
                        }
                        datos.append('accion', 'incluir');
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "modificar") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere modificar este usuario?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        if (window.croppedImageBlob) {
                            datos.set('foto', window.croppedImageBlob, 'foto_recortada.jpg');
                        }
                        var fotoActual = $("#proceso").data("foto_actual");
                        datos.append('foto_actual', fotoActual);
                        datos.append('accion', 'modificar');
                        enviaAjax(datos);
                    }
                });
            }
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
        else if (accion == "permisos_usuario") {
            confirmar('¿Está seguro que quiere guardar los permisos?', function (confirmado) {
                if (confirmado) {
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'guardar_permisos_usuario');
                    enviaAjax(datos);
                }
            });
        }
    });

    $('#roles').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('#contenedor_modal'),
    });
    $("#incluir").on("click", function () {
        limpia();
        limpia_Tablas();

        isSuperAdminEdit = false;

        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Usuario");
        $("#titulo_modal").text("Registrar Usuario");
        $('#contraseña').closest('.colum').show();
        $('#telefono').closest('.colum').show();
        $('#correo').closest('.colum').show();
        $('#foto').closest('.colum').show();
        $('#nombre').closest('.colum').show();
        $('#apellido').closest('.colum').show();
        $('#cedula').closest('.colum').show();
        $('#roles').closest('.colum').show();
        $('#limpiar').show();
        $('#row_permisos').hide();
        $('#roles').val(null).trigger('change');
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();
        limpia_Tablas();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        $('#contraseña').closest('.colum').hide();
        $('#telefono').closest('.colum').hide();
        $('#correo').closest('.colum').hide();
        $('#foto').closest('.colum').hide();
        $('#nombre').closest('.colum').show();
        $('#apellido').closest('.colum').show();
        $('#cedula').closest('.colum').show();
        $('#roles').closest('.colum').show();
        $('#row_permisos').hide();
        $('#limpiar').show();
        $('#roles').val(null).trigger('change');
        abrirModal();
    });

    // Auto-check logic (disabled for dynamic permissions)
    /*
    $('#tabla_permisos').on('change', '.checkbox', function () {
        // ... old logic
    });
    */

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar al usuario que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevo Usuario', description: 'Si pulsa aqui se abrira un modal para ingresar un nuevo usuario', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child',
                popover: { title: 'Usuarios Registrados', description: 'Aqui se mostraran todos los usuarios registrados. Este es un registro individual.', position: 'top' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .cbt_m',
                popover: { title: 'Permisos Específicos', description: 'Permite gestionar los permisos particulares de este usuario.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .cbt_v',
                popover: { title: 'Modificar Usuario', description: 'Abre un modal para modificar los datos de este usuario.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .cbt_r',
                popover: { title: 'Eliminar Usuario', description: 'Permite eliminar permanentemente a este usuario.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .btn_t:last-of-type',
                popover: { title: 'Bloquear/Desbloquear', description: 'Permite bloquear o desbloquear el acceso de este usuario al sistema.', position: 'left' }
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
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de usuarios registrados.', position: 'top' }
            },
        ];

        // Iniciar tour
        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });

});

function validarEnvio(proceso) {
    if (validarkeyup(/^[0-9]{7,8}$/, $('#cedula'),
        $("#cedula_spam"), "Minimo 7 maximo 8 digitos, solo numeros", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar una cedula valida");
        return false;
    }
    else if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
        $("#nombre"), $("#nombre_spam"), "Solo letras  entre 3 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un nombre valido");
        return false;
    }
    else if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
        $('#apellido'), $("#apellido_spam"), "Solo letras entre 3 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un apellido valido");
        return false;
    }
    else if (validarkeyup(/^[0-9]{4}[-]{1}[0-9]{7}$/,
        $('#telefono'), $("#telefono_spam"), "El formato es 0400-000000", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un telefono valido");
        return false;
    }
    else if (proceso == "incluir") {
        if (validarkeyup(/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]{8,20}$/,
            $('#contraseña'), $("#contraseña_spam"), "Entre 8 y 20 caracteres, un número, una letra mayúscula, una letra minúscula y un carácter especial.", true)) {
            muestraMensaje("error", 2000, "Error", "Tiene que ingresar una contraseña valido");
            return false;
        }
    }
    else if (validarkeyup(/^(?=.{3,60}$)[^\s@]+@[^\s@]+\.(com|org|net|edu|gov|mil|info|io|co|es|mx|ar|cl|pe|br)$/i,
        $('#correo'), $("#correo_spam"), "Correo no válido. Ejemplo: usuario@dominio.com", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar una correo valido");
        return false;
    }
    else if (!isSuperAdminEdit && $('#roles option:selected').val() == null) {
        muestraMensaje("error", 2000, "Error", "Tiene que elegir un rol");
        return false;
    }
    return true;
}

// generarFilaPermisos eliminada porque usamos el nuevo renderizado dinámico

function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}

function modificar(datos) {
    limpia();

    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Usuario");
    $("#titulo_modal").text("Modificar Usuario");
    $('#contraseña').closest('.colum').show();
    $('#telefono').closest('.colum').show();
    $('#correo').closest('.colum').show();
    $('#foto').closest('.colum').show();
    $('#nombre').closest('.colum').show();
    $('#apellido').closest('.colum').show();
    $('#cedula').closest('.colum').show();
    
    // Aquí validamos si el usuario a modificar es de nivel 1 o 2
    isSuperAdminEdit = (datos[0].nivel_rol == 1 || datos[0].nivel_rol == 2);
    
    if (isSuperAdminEdit) {
        $('#roles').closest('.colum').hide();
    } else {
        $('#roles').closest('.colum').show();
    }
    
    $('#row_permisos').hide();
    $('#limpiar').show();
    $('#roles').val(null).trigger('change');
    $('#id').val(datos[0].idUsuario);
    $('#cedula').val(datos[0].cedulaUsuario);
    $('#nombre').val(datos[0].nombreUsuario);
    $('#apellido').val(datos[0].apellidoUsuario);
    $('#telefono').val(datos[0].telefonoUsuario);
    $('#correo').val(datos[0].correo);
    $('#roles').val(datos[0].id_rol).trigger('change');
    $("#proceso").data("foto_actual", datos[0].foto);

    if (datos[0].foto && datos[0].foto !== 'default.png') {
        $('#foto_previa').attr('src', 'img/usuarios/' + datos[0].foto).show();
        $('#icono_default').hide();
    } else {
        $('#foto_previa').hide().attr('src', '');
        $('#icono_default').show();
    }

    abrirModal();
}

function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar este Usuario?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}
let botonPresionado = null
function bloquear(id, b, elemento) {
    let texto = (b == 1) ? 'bloquear' : 'desbloquear';
    confirmar(`¿Está seguro que quiere ${texto} este Usuario?`, function (confirmado) {
        if (confirmado) {
            botonPresionado = elemento;
            var datos = new FormData();
            datos.append('accion', 'bloquear');
            datos.append('id', id);
            datos.append('bloqueo', b);
            enviaAjax(datos);

        }
    });
}

// Variable global para almacenar los datos de permisos del usuario
var datosPermisosUsuarioGlobal = [];

function mostrarPermisosUsuario(datos) {
    limpia();
    limpia_Tablas();
    $("#proceso").data("accion", "permisos_usuario");
    $("#proceso").text("Guardar Permisos");
    $("#titulo_modal").text("Permisos del Usuario");

    $('#contraseña').closest('.colum').hide();
    $('#telefono').closest('.colum').hide();
    $('#correo').closest('.colum').hide();
    $('#foto').closest('.colum').hide();
    $('#nombre').closest('.colum').hide();
    $('#apellido').closest('.colum').hide();
    $('#cedula').closest('.colum').hide();
    $('#roles').closest('.colum').hide();
    $('#row_permisos').show();
    $('#limpiar').hide();

    $('#id').val(datos[0].idUsuario);
    $('#roles').val(datos[0].id_rol).trigger('change');

    // Guardar datos globalmente para el buscador
    datosPermisosUsuarioGlobal = datos;

    // Renderizar en pestañas
    renderizarPermisosUsuarioEnTabs(datos);

    // Limpiar el buscador
    $('#buscador_permisos').val('');

    // Activar la primera pestaña
    $('.permisos_tab').removeClass('activa');
    $('.permisos_tab[data-tab="asignados"]').addClass('activa');
    $('.permisos_tab_contenido').removeClass('activo');
    $('#tab_asignados').addClass('activo');

    if (typeof lucide !== 'undefined') lucide.createIcons();

    abrirModal();
}

function renderizarPermisosUsuarioEnTabs(datos, filtro) {
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
            htmlAsignados += generarBloqueModuloUsuario(modulo, modulo.permisos, true);
        } else {
            // El módulo no tiene ningún permiso asignado: va completo a "No Asignados"
            contNoAsignados++;
            htmlNoAsignados += generarBloqueModuloUsuario(modulo, modulo.permisos, false);
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

function generarBloqueModuloUsuario(modulo, permisos, esAsignado) {
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

// Eventos de pestañas y buscador para usuarios
$(document).off('click.permisos_tabs_u').on('click.permisos_tabs_u', '.permisos_tab', function () {
    let tab = $(this).data('tab');
    $(this).closest('.permisos_tabs').find('.permisos_tab').removeClass('activa');
    $(this).addClass('activa');
    $(this).closest('#tabla_permisos_container').find('.permisos_tab_contenido').removeClass('activo');
    $('#tab_' + tab).addClass('activo');
});

$(document).off('input.permisos_buscar_u').on('input.permisos_buscar_u', '#buscador_permisos', function () {
    let filtro = $(this).val();
    renderizarPermisosUsuarioEnTabs(datosPermisosUsuarioGlobal, filtro);
});

function CargarPermisos(idUsuario) {
    var datos = new FormData();
    datos.append('accion', 'CargarPermisosUsuario');
    datos.append('id', idUsuario);
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

function CargarRegistros() {
    muestraMensaje("question", 2000, "¿Seguro que quiere cargar mas registros?")
    confirmar('¿Seguro que quiere cargar mas registros?', function (confirmado) {
        if (confirmado) {
            muestraMensaje("success", 1500, "Cargo de forma exitosa");

        }
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
    return texto.replace(/[&<>"']/g, m => caracteres[m]);
}

function construirSelect(datos) {
    var select = $('#roles');
    select.empty();
    datos.forEach(dato => {
        var linea = `<option value="${dato.id_rol}">${escapeHTML(dato.nombre_rol)}</option>`;
        select.append(linea);
    });
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
                if (lee.accion == "consultar") {
                    crearConsulta(lee.datos);
                }
                else if (lee.accion == "consultarRoles") {
                    construirSelect(lee.datos);
                }
                else if (lee.accion == "buscar") {
                    modificar(lee.datos);
                }
                else if (lee.accion == "CargarPermisosUsuario") {
                    mostrarPermisosUsuario(lee.datos);
                }
                else if (lee.accion == "incluir") {
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                    consultar();
                    limpia();
                    cerrarModal(); // Agregado para cerrar el modal después de guardar exitosamente
                } else if (lee.accion == "modificar" || lee.accion == "guardar_permisos_usuario") {
                    var msjTitle = lee.accion == "modificar" ? "Modificacion Exitosa" : "Permisos Guardados";
                    muestraMensaje("success", 2000, msjTitle, lee.mensaje);
                    consultar();
                    limpia();
                    cerrarModal();
                } else if (lee.accion == "eliminar") {
                    muestraMensaje("success", 2000, "Eliminacion Exitosa", lee.mensaje);
                    consultar();

                }
                else if (lee.accion == "reporte") {
                    cerrarAlertaEspara();
                    muestraMensaje("success", 2000, "Creado Exitosamente", 'Se ha generado el reporte');
                    setTimeout(function () {
                        window.open(lee.archivo, '_blank');
                    }, 2000);
                    cerrarModal();
                    limpia();
                } else if (lee.accion == "bloquear") {
                    muestraMensaje("success", 2000, "Accion Exitosa", lee.mensaje);

                    if (botonPresionado) {
                        let btn = $(botonPresionado);
                        let icono = btn.find('i');

                        let estadoAnterior = btn.attr('onclick').match(/,\s*(\d+),/)[1];
                        let nuevoEstado = (estadoAnterior == 1) ? 2 : 1;

                        if (nuevoEstado == 1) {
                            btn.removeClass('cbt_a').addClass('cbt_g');
                            icono.removeClass('fi-sr-lock').addClass('fi-sr-unlock');
                        } else {

                            btn.removeClass('cbt_g').addClass('cbt_a');
                            icono.removeClass('fi-sr-unlock').addClass('fi-sr-lock');
                        }

                        // Actualizamos el onclick para el siguiente click
                        let idUsuario = btn.attr('onclick').match(/bloquear\((\d+),/)[1];
                        btn.attr('onclick', `bloquear(${idUsuario}, ${nuevoEstado}, this)`);

                        botonPresionado = null; // Limpiamos la variable
                    } else {
                        // Si por alguna razón no hay referencia, recargamos la tabla (respaldo)
                        consultar();
                    }

                }
                else if (lee.accion == "error") {
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



