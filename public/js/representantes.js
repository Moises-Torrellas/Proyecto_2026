$(document).ready(function () {
    
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

        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Representante");
        $("#titulo_modal").text("Registrar Representante");
        $('#contraseña').closest('.colum').show();
        $('#telefono').closest('.colum').show();
        $('#correo').closest('.colum').show();
        $('#roles').val(null).trigger('change');
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();
        
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

        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });

});

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
