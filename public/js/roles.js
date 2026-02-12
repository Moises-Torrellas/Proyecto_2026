function consultar() {
    var datos = new FormData();
    datos.append('accion', 'consultar');
    datos.append('token', $("#token").val());
    enviaAjax(datos);
}
function consultarModulo() {
    var datos = new FormData();
    datos.append('accion', 'consultarModulo');
    datos.append('token', $("#token").val());
    enviaAjax(datos);
}

$(document).ready(function () {
    consultar();
    consultarModulo();

    $("#nombre").on("keypress", function (e) {
        validarkeypress(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, e);
    });

    $("#nombre").on("keyup", function () {
        validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
            $(this), $("#nombre_spam"), "Solo letras entre 3 y 30 caracteres");
    });

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

    $('#modulo').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
    });
    $("#incluir").on("click", function () {
        if (window.permisos.incluir) {
            limpia();
            limpia_Tablas();
            $("#proceso").data("accion", "incluir");
            $("#proceso").text("Registrar Rol");
            $("#titulo_modal").text("Registrar Rol");
            $('#modulo').val(null).trigger('change');
            abrirModal();
        } else {
            muestraMensaje("error", 3000, "Error", 'No tienes los permisos para registrar un rol.');
        }
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
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar al registro que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevo Registro', description: 'Si pulsa aqui se abrira un modal para registrar un nuevo rol', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#tabla',
                popover: { title: 'Registros', description: 'Aqui se mostraran todos los registros.', position: 'top' }
            },
            {
                element: '#cbt_v',
                popover: { title: 'Modificar Registro', description: 'Si pulsa aqui se abrira un modal para modificar el registro seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_r',
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
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de usuarios registrados.', position: 'top' }
            },
        ];

        // Iniciar tour
        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });
});

function validarEnvio() {
    if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
        $('#nombre'), $("#nombre_spam"), "Solo letras entre 3 y 30 caracteres")) {
        muestraMensaje("error", 2000, "Error", "Solo puede ingresar letra, Maximo 30 caracteres");
        return false;
    }
    else if ($('#tabla_permisos tr').length == 0) {
        muestraMensaje("error", 2000, "Error", "Nesecitas seleccionar al menos un modulo");
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
        muestraMensaje("error", 3000, "Error", 'No tienes los permisos para modificar un rol.');
    }
}

function modificar(datos) {
    if (window.permisos.modificar) {
        limpia();
        limpia_Tablas();
        $("#proceso").data("accion", "modificar");
        $("#proceso").text("Modificar Rol");
        $("#titulo_modal").text("Modificar Rol");
        $('#id').val(datos[0].id_rol);
        $('#nombre').val(datos[0].nombre_rol);
        $('#modulo').val(null).trigger('change');

        datos.forEach(dato => {
            var incluirChecked = dato.incluir == 1 ? 'checked' : '';
            var modificarChecked = dato.modificar == 1 ? 'checked' : '';
            var eliminarChecked = dato.eliminar == 1 ? 'checked' : '';
            var reporteChecked = dato.reporte == 1 ? 'checked' : '';
            var otrosChecked = dato.otros == 1 ? 'checked' : '';

            var linea = `<tr>
                            <td style="display: none;">
                                <input type="hidden" name="modulo_id[]" value="${dato.id_modulo}">
                            </td>
                            <td>${escapeHTML(dato.nombre_modulo)}</td>
                                
                            <td>
                                <label class="checkbox-container">
                                    <input class="checkbox" type="checkbox" id="check_incluir_${dato.id_modulo}" name="check_incluir[${dato.id_modulo}]" value="1" ${incluirChecked}>
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
                                
                            <td>
                                <button class="btn_t cbt_r btn_t_m" onclick="eliminaLinea(this);">
                                    <i class="fi fi-sr-trash-xmark"></i>
                                </button>
                            </td>
                        </tr>`;


            $("#tabla_permisos").append(linea);
        });
        abrirModal();
    } else {
        muestraMensaje("error", 3000, "Error", 'No tienes los permisos para modificar un rol.');
    }
}

function eliminar(id) {
    if (window.permisos.eliminar) {
        confirmar('¿Está seguro que quiere eliminar este rol?', function (confirmado) {
            if (confirmado) {
                var datos = new FormData();
                datos.append('accion', 'eliminar');
                datos.append('token', $("#token").val());
                datos.append('id', id);
                enviaAjax(datos);
            }
        });
    } else {
        muestraMensaje("error", 3000, "", 'No tienes los permisos para eliminar un rol.');
    }
}

function crearConsulta(datos) {
    var tablaBody = $('#resultadoconsulta');
    tablaBody.empty();
    datos.forEach(dato => {
        var linea = `<tr>
                        <td>${dato.id_rol}</td>
                        <td>${escapeHTML(dato.nombre_rol)}</td>
                        <td>
                            <button class="btn_t cbt_v" id="cbt_v" onclick="buscar(${dato.id_rol})"><i class="fi fi-sr-pencil"></i></button>
                            <button class="btn_t cbt_r" id="cbt_r" onclick="eliminar(${dato.id_rol})"><i class="fi fi-sr-trash-xmark"></i></button>
                        </td>
                    </tr>`;

        tablaBody.append(linea);
    });
    inicializarPaginador();
}

$('#add').on('click', function () {
    var nombre = $('#modulo option:selected').text();
    var id = $('#modulo option:selected').val();

    var modulo_existe = true;

    if (id != null) {
        $('#tabla_permisos tr').each(function () {
            if (nombre == $(this).find('td:eq(1)').text()) {
                muestraMensaje("error", 2000, "Error", "Ya agregaste este modulo.");
                modulo_existe = false;
            }
        })

        if (modulo_existe) {
            var lin = `<tr>
                            <td style="display: none;">
                                <input type="hidden" name="modulo_id[]" value="${id}">
                            </td>
                                
                            <td>${nombre}</td>
                                
                            <td>
                                <label class="checkbox-container">
                                    <input class="checkbox" type="checkbox" id="check_incluir" name="check_incluir[${id}]" value="1">
                                    <span class="custom-checkbox"></span>
                                </label>
                            </td>
                                
                            <td>
                                <label class="checkbox-container">
                                    <input class="checkbox" type="checkbox" id="check_modificar" name="check_modificar[${id}]" value="1">
                                    <span class="custom-checkbox"></span>
                                </label>
                            </td>
                                
                            <td>
                                <label class="checkbox-container">
                                    <input class="checkbox" type="checkbox" id="check_eliminar" name="check_eliminar[${id}]" value="1">
                                    <span class="custom-checkbox"></span>
                                </label>
                            </td>
                                
                            <td>
                                <label class="checkbox-container">
                                    <input class="checkbox" type="checkbox" id="check_reporte" name="check_reporte[${id}]" value="1">
                                    <span class="custom-checkbox"></span>
                                </label>
                            </td>
                                
                            <td>
                                <label class="checkbox-container">
                                    <input class="checkbox" type="checkbox" id="check_otros" name="check_otros[${id}]" value="1">
                                    <span class="custom-checkbox"></span>
                                </label>
                            </td>
                                
                            <td>
                                <button class="btn_t cbt_r btn_t_m" onclick="eliminaLinea(this);">
                                    <i class="fi fi-sr-trash-xmark"></i>
                                </button>
                            </td>
                        </tr>

                        `;
            $("#tabla_permisos").append(lin);
        }
    } else if (id == null) {
        muestraMensaje("error", 2000, "Error", "Tienes que seleccionar un modulo antes de agregarlo.");
    }
});

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
    var select = $('#modulo');
    select.empty();
    datos.forEach(dato => {
        var linea = `<option value="${dato.id_modulo}">${escapeHTML(dato.nombre_modulo)}</option>`;
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
                if (lee.accion == "consultar") {
                    crearConsulta(lee.datos);
                }
                else if (lee.accion == "consultarModulo") {
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
                        limpia_Tablas();
                        cerrarModal();
                    } else {
                        muestraMensaje("error", 2000, "Error", lee.mensaje);
                    }

                } else if (lee.accion == "modificar") {
                    if (lee.resultado == 1) {
                        muestraMensaje("success", 2000, "Correcto", lee.mensaje);
                        consultar();
                        limpia();
                        limpia_Tablas();
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