$(document).ready(function () {

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
    $("#contraseña").on("keypress", function (e) {
        validarkeypress(/^[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]*$/, e);
    });

    $("#contraseña").on("keyup", function () {
        validarkeyup(/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]{8,20}$/,
            $(this), $("#contraseña_spam"), "Entre 8 y 20 caracteres, un número, una letra mayúscula, una letra minúscula y un carácter especial.");
    });

    $('#ingreso').on('click', function () {
        if (validarEnvio()) {
            const datos = new FormData($('#f')[0]);
            datos.append('accion', 'inicio');
            enviaAjax(datos);
        }
    });
});

function validarEnvio() {
    if (validarkeyup(/^[0-9]{7,8}$/, $('#cedula'),
        $("#cedula_spam"), "Minimo 7 maximo 8 digitos, solo numeros")) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar una cedula valida");
        return false;
    }
    else if (validarkeyup(/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]{8,20}$/,
        $('#contraseña'), $("#contraseña_spam"), "Entre 8 y 20 caracteres, un número, una letra mayúscula, una letra minúscula y un carácter especial.")) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar una contraseña valido");
        return false;
    }
    return true;
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
                if (lee.accion == "inicio") {
                    if (lee.resultado == 1) {
                        muestraMensaje("success", 2000, "Inicio Validado", lee.mensaje);
                        limpia();
                        setTimeout(function () {
                            window.location.href = lee.url;
                        }, 2000)
                    } else if (lee.resultado == 2) {
                        muestraMensaje("error", 2000, "Error", lee.mensaje);
                        $("#cedula").addClass("denegado");
                    } else {
                        muestraMensaje("error", 2000, "Error", lee.mensaje);
                        $("#contraseña").addClass("denegado");
                    }
                } else if (lee.accion == "error") {
                    muestraMensaje("error", 20000, "Error", lee.mensaje);
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