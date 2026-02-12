function consultar() {
    var datos = new FormData();
    datos.append('accion', 'consultar');
    datos.append('token', $("#token").val());
    enviaAjax(datos);
}
function consultarRoles() {
    var datos = new FormData();
    datos.append('accion', 'consultarRoles');
    datos.append('token', $("#token").val());
    enviaAjax(datos);
}

$(document).ready(function () {
    consultar();
    consultarRoles();

    $("#cedula").on("keypress", function (e) {
        validarkeypress(/^[0-9\b]*$/, e);
    });

    $("#cedula").on("keyup", function () {
        validarkeyup(/^[0-9]{7,8}$/, $(this),
            $("#cedula_spam"), "Minimo 7 maximo 8 digitos, solo numeros");
    });

    $("#cedula").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        if (input.length > 4) {
            input = input.substring(0, 8);
        }
        $(this).val(input);
    });

    $("#nombre").on("keypress", function (e) {
        validarkeypress(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, e);
    });

    $("#nombre").on("keyup", function () {
        validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
            $(this), $("#nombre_spam"), "Solo letras entre 3 y 30 caracteres");
    });
    $("#apellido").on("keypress", function (e) {
        validarkeypress(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, e);
    });

    $("#apellido").on("keyup", function () {
        validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
            $(this), $("#apellido_spam"), "Solo letras entre 3 y 30 caracteres");
    });
    $("#telefono").on("keypress", function (e) {
        validarkeypress(/^[0-9\-\b]*$/, e);
    });

    $("#telefono").on("keyup", function () {
        validarkeyup(/^[0-9]{4}[-]{1}[0-9]{7}$/,
            $(this), $("#telefono_spam"), "El formato es 0400-000000");
    });
    $("#telefono").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        if (input.length > 4) {
            input = input.substring(0, 4) + '-' + input.substring(4, 11);
        }
        $(this).val(input);
    });

    $("#contraseña").on("keypress", function (e) {
        validarkeypress(/^[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]*$/, e);
    });

    $("#contraseña").on("keyup", function () {
        validarkeyup(/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]{8,20}$/,
            $(this), $("#contraseña_spam"), "Entre 8 y 20 caracteres, un número, una letra mayúscula, una letra minúscula y un carácter especial.");
    });

    // Solo permite teclas válidas (opcional)
    $("#correo").on("keypress", function (e) {
        validarkeypress(/^[a-zA-Z0-9@._\-]*$/, e);
    });

    // Valida el formato del correo en tiempo real
    $("#correo").on("keyup", function () {
        validarkeyup(/^(?=.{3,60}$)[^\s@]+@[^\s@]+\.(com|org|net|edu|gov|mil|info|io|co|es|mx|ar|cl|pe|br)$/i, $(this), $("#correo_spam"), "Correo no válido. Ejemplo: usuario@dominio.com");
    });


    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este usuario?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'incluir');
                        enviaAjax(datos);
                        for (var pair of datos.entries()) {
                            console.log(pair[0] + ': ' + pair[1]);
                        }
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
        /* if (window.permisos.incluir) { */
        limpia();
        limpia_Tablas();
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Usuario");
        $("#titulo_modal").text("Registrar Usuario");
        $('#roles').val(null).trigger('change');
        abrirModal();
        /* } else {
            muestraMensaje("error", 3000, "Error", 'No tienes los permisos para registrar un usuario.');
        } */
    });

    $("#generar").on("click", function () {
        limpia();
        limpia_Tablas();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
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
        $("#cedula_spam"), "Minimo 7 maximo 8 digitos, solo numeros")) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar una cedula valida");
        return false;
    }
    else if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
        $("#nombre"), $("#nombre_spam"), "Solo letras  entre 3 y 30 caracteres")) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un nombre valido");
        return false;
    }
    else if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
        $('#apellido'), $("#apellido_spam"), "Solo letras entre 3 y 30 caracteres")) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un apellido valido");
        return false;
    }
    else if (validarkeyup(/^[0-9]{4}[-]{1}[0-9]{7}$/,
        $('#telefono'), $("#telefono_spam"), "El formato es 0400-000000")) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un telefono valido");
        return false;
    }
    else if (proceso == "incluir") {
        if (validarkeyup(/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]{8,20}$/,
            $('#contraseña'), $("#contraseña_spam"), "Entre 8 y 20 caracteres, un número, una letra mayúscula, una letra minúscula y un carácter especial.")) {
            muestraMensaje("error", 2000, "Error", "Tiene que ingresar una contraseña valido");
            return false;
        }
    }
    else if (validarkeyup(/^(?=.{3,60}$)[^\s@]+@[^\s@]+\.(com|org|net|edu|gov|mil|info|io|co|es|mx|ar|cl|pe|br)$/i,
        $('#correo'), $("#correo_spam"), "Correo no válido. Ejemplo: usuario@dominio.com")) {
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
    if (window.permisos.modificar) {
        var datos = new FormData();
        datos.append('accion', 'buscar');
        datos.append('token', $("#token").val());
        datos.append('id', id);
        enviaAjax(datos);
    } else {
        muestraMensaje("error", 3000, "Error", 'No tienes los permisos para modificar un usuario.');
    }
}

function modificar(datos) {
    if (window.permisos.modificar) {
        limpia();
        $("#proceso").data("accion", "modificar");
        $("#proceso").text("Modificar Usuario");
        $("#titulo_modal").text("Modificar Usuario");
        $('#roles').val(null).trigger('change');
        $('#id').val(datos[0].idUsuario);
        $('#cedula').val(datos[0].cedulaUsuario);
        $('#nombre').val(datos[0].nombreUsuario);
        $('#apellido').val(datos[0].apellidoUsuario);
        $('#telefono').val(datos[0].telefonoUsuario);
        $('#correo').val(datos[0].correo);
        $('#roles').val(datos[0].id_rol);
        abrirModal();
    } else {
        muestraMensaje("error", 3000, "Error", 'No tienes los permisos para modificar un usuario.');
    }
}

function eliminar(id) {
    if (window.permisos.eliminar) {
        confirmar('¿Está seguro que quiere eliminar este Usuario?', function (confirmado) {
            if (confirmado) {
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('token', $("#token").val());
                datos.append('id', id);
                enviaAjax(datos);
            }
        });
    } else {
        muestraMensaje("error", 3000, "Error", 'No tienes los permisos para eliminar un usuario.');
    }

}

function crearConsulta(datos) {
    var tablaBody = $('#resultadoconsulta');
    tablaBody.empty();
    var cantidadRegistros = datos.length;
    var colspan = 5;
    datos.forEach(dato => {

        let botones = '';
        if (window.permisos.modificar || window.permisos.eliminar) {
            botones += `<td>`;
            if (window.permisos.modificar) {
                botones += `<button class="btn_t cbt_v" id="cbt_v" onclick="buscar(${dato.idUsuario})"><i class="fi fi-sr-pencil"></i></button>`;
            }
            if (window.permisos.eliminar) {
                botones += `<button class="btn_t cbt_r" id="cbt_r" onclick="eliminar(${dato.idUsuario})"><i class="fi fi-sr-trash-xmark"></i></button>`;
            }
            botones += `</td>`;
            colspan = 6;
        }

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

function enviaAjax(datos) {
    $.ajax({
        async: true,
        url: "",
        type: "POST",
        contentType: false,
        data: datos,
        processData: false,
        cache: false,
        beforeSend: function () { },
        timeout: 10000,
        success: function (respuesta) {
            try {
                var lee = JSON.parse(respuesta);
                console.log(lee);
                if (lee.accion == "consultar") {
                    crearConsulta(lee.datos);
                }
                else if (lee.accion == "consultarRoles") {
                    construirSelect(lee.datos);
                }
                else if (lee.accion == "buscar") {
                    if (lee.resultado == 1) {
                        modificar(lee.datos);
                    } else {
                        muestraMensaje("error", 2000, "Error", lee.mensaje);
                    }
                }
                else if (lee.accion == "incluir") {
                    if (lee.resultado == 1) {
                        muestraMensaje("success", 2000, "Correcto", lee.mensaje);
                        consultar();
                        limpia();
                        cerrarModal();
                    } else {
                        muestraMensaje("error", 2000, "Error", lee.mensaje);
                    }

                } else if (lee.accion == "modificar") {
                    if (lee.resultado == 1) {
                        muestraMensaje("success", 2000, "Correcto", lee.mensaje);
                        consultar();
                        limpia();
                        cerrarModal();
                    } else {
                        muestraMensaje("error", 2000, "Error", lee.mensaje);
                    }

                } else if (lee.accion == "eliminar") {
                    if (lee.resultado == 1) {
                        muestraMensaje("success", 2000, "Correcto", lee.mensaje);
                        consultar();
                    } else {
                        muestraMensaje("error", 2000, "Error", lee.mensaje);
                    }

                } else if (lee.accion == "error") {
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

