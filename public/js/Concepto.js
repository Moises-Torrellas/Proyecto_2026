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
    // Validación de Nombre
    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");

    // Validación de monto
    Validacion("monto", /^[0-9\b\,]*$/, /^[0-9]+(.[0-9]{1,2})?$/, "Solo números con hasta dos decimales (solo comas)", "proceso");
    
    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este concepto de pago?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar este concepto de pago?', function (confirmado) {
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

    $("#incluir").on("click", function () {
        limpia();

        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Concepto");
        $("#titulo_modal").text("Registrar Concepto de Pago");
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();

        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar el Concepto de Pago que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevo Concepto de pago', description: 'Si pulsa aqui se abrira un modal para ingresar un nuevo Concepto de Pago', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Conceptos de Pago Registrados', description: 'Aqui se mostraran todos los Conceptos de pago registrados.', position: 'top' }
            },
            {
                element: '#cbt_v',
                popover: { title: 'Modificar Concepto de Pago', description: 'Si pulsa aqui se abrira un modal para modificar el Conceptos de Pago seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_r',
                popover: { title: 'Eliminar Concepto de Pago', description: 'Si pulsa aqui eliminara el Concepto de Pago seleccionado.', position: 'left' }
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
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de Concepto de Pagos cargados.', position: 'top' }
            },
        ];

        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });

});

function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}
function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar este proceso de pago?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}
function cambiarEstatus(id, estadoActual) {
    let accionTexto = (estadoActual == 1) ? 'desactivar' : 'activar';
    confirmar(`¿Está seguro que desea ${accionTexto} este concepto de pago?`, function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'estatus');
            datos.append('id', id);
            datos.append('estatus', estadoActual);
            enviaAjax(datos);
        }
    });
}

function validarEnvio(proceso) {
        if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
            $("#nombre"), $("#nombre_spam"), "Solo letras  entre 3 y 30 caracteres", true)) {
            muestraMensaje("error", 2000, "Error", "Tiene que ingresar un nombre valido");
            return false;
    }
    else if (validarkeyup(/^[0-9]+(.[0-9]{1,2})?$/, $('#monto'),
    $("#monto_spam"), "Solo números con hasta dos decimales (solo comas)", true)) {
    muestraMensaje("error", 2000, "Error", "Tiene que ingresar un monto válido");
    return false;
}
    return true;
}

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar concepto de pago");
    $("#titulo_modal").text("Modificar concepto de pago");
    $('#id').val(datos[0].id_conceptos);
    $('#nombre').val(datos[0].nombre);
    $('#monto').val(datos[0].monto);

    abrirModal();
}
function crearConsulta(datos) {
    const contenedor = $('#resultadoconsulta');
    contenedor.empty();

    if (datos.length === 0) {
        contenedor.append('<div class="listado_vacio"><p>No se encontraron registros</p></div>');
    } else {
        datos.forEach(dato => {
    let registro = `
        <div class="listado_contenedor_grupal">
            <div class="listado_item" onclick="toggleDetalles(this)">
                <div class="listado_col_datos">
                    <div class="listado_dato_grupo">
                        <small>Nombre</small>
                        <span>${dato.nombre}</span>
                    </div>
                    <div class="listado_dato_grupo">
                        <small>Monto</small>
                        <span>${dato.monto}</span>
                    </div>
                </div>

                <div class="listado_col_acciones">
                    <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                        <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(${dato.id_conceptos})"><i class="fi fi-sr-pencil"></i></button>
                        <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(${dato.id_conceptos})"><i class="fi fi-sr-trash-xmark"></i></button>
                        <button id="cbt_b" class="btn_t cbt_b" onclick="cambiarEstatus(${dato.id_conceptos}, ${dato.estatus})">
                        <i class="${dato.estatus == 2 ? 'fi fi-sr-lock' : 'fi fi-sr-unlock'}"></i>
</button>
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
                } else if (lee.accion == "incluir") {
                    consultar();
                    limpia();
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                } else if (lee.accion == "estatus") {
                    consultar();
                    muestraMensaje("success", 2000, "Actualización Exitosa", lee.mensaje);
                } else if (lee.accion == "eliminar") {
                    consultar();
                    muestraMensaje("success", 2000, "Eliminacion Exitosa", lee.mensaje);
                } else if (lee.accion == "modificar") {
                    consultar();
                    limpia();
                    cerrarModal();

                    muestraMensaje("success", 2000, "Modificacion Exitosa", lee.mensaje);
                } else if (lee.accion == "buscar") {
                    modificar(lee.datos);
                }
                else if (lee.accion == "error") {
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
