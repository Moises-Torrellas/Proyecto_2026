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

    // Diccionario de monedas permitidas
    const monedasOficiales = {
        "USD": { simbolo: "$", nombre: "Dólar" },
        "VES": { simbolo: "Bs", nombre: "Bolívar" },
        "EUR": { simbolo: "€", nombre: "Euro" }
    };

    // Autocompletar y bloquear campos al cambiar la abreviatura
    $('#abreviatura').on('change', function () {
        let iso = $(this).val();
        if (monedasOficiales[iso]) {
            $('#nombre').val(monedasOficiales[iso].nombre);
            $('#simbolo').val(monedasOficiales[iso].simbolo);
        }
    });

    $('#abreviatura').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('#contenedor_modal')
    });

    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar esta moneda?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar esta moneda?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        datos.append('accion', 'modificar');
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "generar") {
            opcionesReporte(function(formato) {
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
        $("#proceso").text("Registrar Moneda");
        $("#titulo_modal").text("Registrar Moneda");
        
        $('#nombre').closest('.colum').show();
        $('#simbolo').closest('.colum').show();
        $('#abreviatura option[value="Todas"]').remove();
        
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();

        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        
        if ($('#abreviatura option[value="Todas"]').length === 0) {
            $('#abreviatura').prepend('<option value="Todas" selected>Todas las Abreviaturas</option>');
        }
        $('#nombre').closest('.colum').hide();
        $('#simbolo').closest('.colum').hide();

        abrirModal();
    });

    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#busqueda',
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar la moneda que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nueva Moneda', description: 'Si pulsa aqui se abrira un modal para ingresar una nueva moneda', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira una alerta para generar un reporte en PDF o EXCEL.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Monedas Registradas', description: 'Aqui se mostraran todos las monedas registradas.', position: 'top' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child',
                popover: { title: 'Registro de Moneda', description: 'Este es un registro individual de moneda. Aqui puedes ver sus detalles y acciones.', position: 'top' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .listado_col_acciones button:nth-of-type(1)',
                popover: { title: 'Moneda Base', description: 'Si pulsa aqui seleccionará esta moneda como la moneda base del sistema.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .listado_col_acciones button:nth-of-type(2)',
                popover: { title: 'Modificar Moneda', description: 'Si pulsa aqui se abrira un modal para modificar esta moneda.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .listado_col_acciones button:nth-of-type(3)',
                popover: { title: 'Eliminar Moneda', description: 'Si pulsa aqui eliminara esta moneda.', position: 'left' }
            },
            {
                element: '#resultadoconsulta .listado_contenedor_grupal:first-child .listado_col_acciones button:nth-of-type(4)',
                popover: { title: 'Bloquear/Desbloquear Moneda', description: 'Si pulsa aqui bloqueará o desbloqueará esta moneda.', position: 'left' }
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
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de monedas cargadas.', position: 'top' }
            },
        ];

        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });

});

function validarEnvio(accion) {
    if (accion == "generar") return true;

    if (!$('#abreviatura').val()) {
        muestraMensaje("error", 2000, "Error", "Tiene que seleccionar una Moneda (ISO)");
        return false;
    }
    
    if ($('#nombre').val() === "") {
        muestraMensaje("error", 2000, "Error", "El nombre de la moneda no puede estar vacío");
        return false;
    }

    if ($('#simbolo').val() === "") {
        muestraMensaje("error", 2000, "Error", "El símbolo no puede estar vacío");
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
function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar esta moneda?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Moneda");
    $("#titulo_modal").text("Modificar Moneda");
    $('#id').val(datos[0].codigo_moneda);
    $('#nombre').val(datos[0].nombre);
    
    $('#nombre').closest('.colum').show();
    $('#simbolo').closest('.colum').show();
    $('#abreviatura option[value="Todas"]').remove();
    
    $('#abreviatura').val(datos[0].abreviatura).trigger('change');
    $('#simbolo').val(datos[0].simbolo);

    abrirModal();
}

let botonPresionado = null
function bloquear(id, b, elemento) {
    let texto = (b == 1) ? 'bloquear' : 'desbloquear';
    confirmar(`¿Está seguro que quiere ${texto} esta Moneda?`, function (confirmado) {
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

function seleccionar(id) {
    confirmar(`¿Está seguro que quiere seleccionar esta Moneda como la moneda base del sistema? información: Esto remplazara la moneda actual y todas los cargos se cargaran con esta moneda.`, function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'select');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}
function msj() {
    muestraMensaje("error", 2000, "Necesita seleccionar otra moneda", "No puede desactivar la moneda base, si desea cambiarla debe seleccionar otra moneda.");
}

function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');

    // Inyectamos directamente el string de tarjetas HTML que escupió el PHP
    contenedor.html(htmlRecibido);

    // Ejecutamos tus inicializadores estéticos y paginadores normales
    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
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
                
                if (lee.accion == "incluir") {
                    consultar();
                    limpia();
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
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
                } else if (lee.accion == "bloquear") {
                    muestraMensaje("success", 2000, "Bloqueo Exitosa", lee.mensaje);
                    consultar();
                } else if (lee.accion == "select") {
                    muestraMensaje("success", 2000, "Cambio Exitoso", lee.mensaje);
                    consultar();
                } else if (lee.accion == "reporte") {
                    cerrarAlertaEspara();
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
                } else if (lee.accion == "error") {
                    // Cerramos cualquier alerta de carga en caso de error
                    if (typeof Swal !== 'undefined') Swal.close(); 
                    muestraMensaje("error", 3000, "Error", lee.mensaje);
                }
            } catch (e) {
                if (typeof Swal !== 'undefined') Swal.close(); 
                console.error("Error al procesar JSON:", e, respuesta);
                alert("Error en JSON: " + e.message);
            }
        },
        error: function (request, status, err) {
            if (typeof Swal !== 'undefined') Swal.close(); 
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + status + " " + err);
            }
        },
        complete: function () { },
    });
}