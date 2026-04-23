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
function consultarRoles() {
    var datos = new FormData();
    datos.append('accion', 'consultarRoles');
    enviaAjax(datos);
}

$(document).ready(function () {
    consultar();
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
                        datos.append('accion', 'modificar');
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "generar") {
            confirmar('¿Está seguro que quiere generar un reporte?', function (confirmado) {
                if (confirmado) {
                    abrirAlertaEspara('Se esta generando el reporte', 'Espere un momento')
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    enviaAjax(datos);
                }
            });
        }
    });

    $('#roles').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
    });
    $("#incluir").on("click", function () {
        limpia();
        limpia_Tablas();

        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Usuario");
        $("#titulo_modal").text("Registrar Usuario");
        $('#contraseña').closest('.colum').show();
        $('#telefono').closest('.colum').show();
        $('#correo').closest('.colum').show();
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
        $('#roles').val(null).trigger('change');
        abrirModal();
    });

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
                element: '#tabla',
                popover: { title: 'Usuarios Registrados', description: 'Aqui se mostraran todos los usuarios registrados.', position: 'top' }
            },
            {
                element: '#cbt_v',
                popover: { title: 'Modificar Usuario', description: 'Si pulsa aqui se abrira un modal para modificar el usuario seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_r',
                popover: { title: 'Eliminar Usuario', description: 'Si pulsa aqui eliminara el usuario seleccionado.', position: 'left' }
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
    else if ($('#roles option:selected').val() == null) {
        muestraMensaje("error", 2000, "Error", "Tiene que elegir un rol");
        return false;
    }
    return true;
}

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
    $('#roles').val(null).trigger('change');
    $('#id').val(datos[0].idUsuario);
    $('#cedula').val(datos[0].cedulaUsuario);
    $('#nombre').val(datos[0].nombreUsuario);
    $('#apellido').val(datos[0].apellidoUsuario);
    $('#telefono').val(datos[0].telefonoUsuario);
    $('#correo').val(datos[0].correo);
    $('#roles').val(datos[0].id_rol).trigger('change');

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

const icon = 'fi-sr-lock';

/* function crearConsulta(datos) {
    var tablaBody = $('#resultadoconsulta');
    tablaBody.empty();
    var cantidadRegistros = datos.length;
    var colspan = 5;
    datos.forEach(dato => {

        let botones = '';

        let icon = dato.bloqueo == 1 ? 'fi-sr-unlock' : 'fi-sr-lock';
        let color = dato.bloqueo == 1 ? 'cbt_g' : 'cbt_a';


        botones += `<td>`;

        botones += `<button class="btn_t cbt_v" onclick="buscar(${dato.idUsuario})"><i class="fi fi-sr-pencil"></i></button>`;
        botones += `<button class="btn_t cbt_r" onclick="eliminar(${dato.idUsuario})"><i class="fi fi-sr-trash-xmark"></i></button>`;
        botones += `<button class="btn_t ${color}" onclick="bloquear(${dato.idUsuario}, ${dato.bloqueo}, this)"><i class="fi ${icon}"></i>
            </button>`;

        botones += `</td>`;
        colspan = 6;


        var linea = `<tr>
                        <td>${dato.cedulaUsuario}</td>
                        <td>${escapeHTML(dato.nombreUsuario)}  ${escapeHTML(dato.apellidoUsuario)}</td>
                        <td>${dato.telefonoUsuario}</td>
                        <td>${dato.correo}</td>
                        <td>${escapeHTML(dato.nombre_rol)}</td>
                        ${botones}
                    </tr>`;

        tablaBody.append(linea);
    });

    if (cantidadRegistros == 0) {
        colspan = 6;
        var linea = `<tr>
                        <td colspan='${colspan}'>No se encontraron registros</td>
                    </tr>`;
        tablaBody.append(linea);
    }

    if (cantidadRegistros >= 100) {
        linea = ``
        linea = `<tr>
                    <td colspan='${colspan}'>
                        <button class="btn btn_azul" onclick="CargarRegistros()">Cargar Mas Registros</button>
                    </td>
                </tr>`;
        tablaBody.append(linea);
    }
    inicializarPaginador();
} */

function crearConsulta(datos) {
    const contenedor = $('#resultadoconsulta');
    contenedor.empty();

    if (datos.length === 0) {
        contenedor.append('<div class="listado_vacio"><p>No se encontraron registros</p></div>');
    } else {
        datos.forEach(dato => {
            let icon = dato.bloqueo == 1 ? 'fi-sr-unlock' : 'fi-sr-lock';
            let color = dato.bloqueo == 1 ? 'cbt_g' : 'cbt_a';

            let fotoHTML = dato.foto
                ? `<img src="${dato.foto}" class="listado_avatar" alt="Perfil">`
                : `<div class="listado_avatar_null"><i data-lucide="circle-user"></i></div>`;

            let registro = `
                <div class="listado_contenedor_grupal">
                    <div class="listado_item" onclick="toggleDetalles(this)">
                        <div class="listado_col_principal">
                            ${fotoHTML}
                            <div class="listado_info_base">
                                <span class="listado_titulo">${escapeHTML(dato.nombreUsuario)} ${escapeHTML(dato.apellidoUsuario)}</span>
                                <span class="listado_subtitulo">${dato.correo}</span>
                            </div>
                        </div>

                        <div class="listado_col_datos">
                            <div class="listado_dato_grupo">
                                <small>Cédula</small>
                                <span>${dato.cedulaUsuario}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Telefono</small>
                                <span>${escapeHTML(dato.telefonoUsuario)}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Rol</small>
                                <span class="listado_resaltado">${escapeHTML(dato.nombre_rol)}</span>
                            </div>
                        </div>

                        <div class="listado_col_acciones">
                            <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                <button class="btn_t cbt_v" onclick="buscar(${dato.idUsuario})"><i class="fi fi-sr-pencil"></i></button>
                                <button class="btn_t cbt_r" onclick="eliminar(${dato.idUsuario})"><i class="fi fi-sr-trash-xmark"></i></button>
                                <button class="btn_t ${color}" onclick="bloquear(${dato.idUsuario}, ${dato.bloqueo}, this)"><i class="fi ${icon}"></i></button>
                            </div>
                            <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                        </div>
                    </div>

                    <div class="listado_detalle_oculto">
                        <div class="listado_detalle_contenido">
                            <div class="detalle_grid">
                                <div class="detalle_info">
                                    <strong><i data-lucide="calendar"></i> Registro</strong>
                                    <span>${dato.fecha_registro || 'N/A'}</span>
                                </div>
                                <div class="detalle_info">
                                    <strong><i data-lucide="info"></i> Información Extra</strong>
                                    <span>Detalles adicionales del usuario aquí...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            contenedor.append(registro);
        });
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
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
        timeout: 10000,
        success: function (respuesta) {
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
                else if (lee.accion == "incluir") {
                    muestraMensaje("success", 2000, "Correcto", lee.mensaje);
                    consultar();
                    limpia();
                } else if (lee.accion == "modificar") {
                    muestraMensaje("success", 2000, "Correcto", lee.mensaje);
                    consultar();
                    limpia();
                    cerrarModal();
                } else if (lee.accion == "eliminar") {
                    muestraMensaje("success", 2000, "Correcto", lee.mensaje);
                    consultar();

                }
                else if (lee.accion == "reporte") {
                    cerrarAlertaEspara();
                    muestraMensaje("success", 2000, "Correcto", 'Se ha generado el reporte');
                    setTimeout(function () {
                        window.open(lee.archivo, '_blank');
                    }, 2000);
                    limpia();
                } else if (lee.accion == "bloquear") {
                    muestraMensaje("success", 2000, "Correcto", lee.mensaje);

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

