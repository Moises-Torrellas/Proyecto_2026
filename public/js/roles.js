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
    consultar();

    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");

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
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
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

    // Auto-check logic
    $('#tabla_permisos').on('change', '.checkbox', function() {
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

function generarFilaPermisos(dato) {
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
}

function modificar(datos) {
    limpia();
    limpia_Tablas();
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Rol");
    $("#titulo_modal").text("Modificar Rol");
    $('#id').val(datos[0].id_rol);
    $('#nombre').val(datos[0].nombre_rol);
    $('#row_nombre').show();
    $('#row_modulo').hide();
    $('#row_modulo').hide();
    $('#row_permisos').hide();
    $('#proceso').show();
    abrirModal();
}

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

    datos.forEach(dato => {
        $("#tabla_permisos").append(generarFilaPermisos(dato));
    });
    abrirModal();
}

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

function CargarPermisos(id){
    var datos = new FormData();
    datos.append('accion', 'CargarPermisos');
    datos.append('id', id);
    enviaAjax(datos);
}

function crearConsulta(datos) {
    const contenedor = $('#resultadoconsulta');
    contenedor.empty();

    if (datos.length === 0) {
        contenedor.append('<div class="listado_vacio"><p>No se encontraron registros</p></div>');
    } else {
        datos.forEach(dato => {
            let registro = `
                <div class="listado_contenedor_grupal" style="cursor: auto;">
                    <div class="listado_item">
                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Nombre</small>
                            <span>${dato.nombre_rol}</span>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <button class="btn_t cbt_m" onclick="CargarPermisos(${dato.id_rol})" data-tippy-content="Permisos"><i class="fi fi-sr-user-permissions"></i></button>
                        <button class="btn_t cbt_v" onclick="buscar(${dato.id_rol})" data-tippy-content="Modificar"><i class="fi fi-sr-pencil"></i></button>
                        <button class="btn_t cbt_r" onclick="eliminar(${dato.id_rol})" data-tippy-content="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
                    </div>
                </div>
                </div>
            `;
            contenedor.append(registro);
        });
    }

    if (typeof lucide !== 'undefined') lucide.createIcons();
    inicializarPaginador();
    tippy('[data-tippy-content]', { theme: 'light' });
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
            try {
                var lee = JSON.parse(respuesta);
                if (lee.accion == "consultar") {
                    crearConsulta(lee.datos);
                }
                else if (lee.accion == "buscar") {
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