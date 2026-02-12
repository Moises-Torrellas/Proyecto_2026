document.addEventListener('keydown', function (event) {
    if (event.key === 'Tab') {
        event.preventDefault(); // Evita que cambie de campo
    }
});

$(document).ready(function () {
    // Inicial: solo mostrar sec_1
    sec_1();

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

    $("#codigo").on("keypress", function (e) {
        validarkeypress(/^[0-9\b]*$/, e);
    });

    $("#codigo").on("keyup", function () {
        validarkeyup(/^[0-9]{6}$/, $(this),
            $("#codigo_spam"), "solo 6 numeros");
    });

    $("#codigo").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        if (input.length > 4) {
            input = input.substring(0, 6);
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

    $("#contraseña_r").on("keypress", function (e) {
        validarkeypress(/^[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]*$/, e);
    });

    $("#contraseña_r").on("keyup", function () {
        validarkeyup(/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]{8,20}$/,
            $(this), $("#contraseña_r_spam"), "Entre 8 y 20 caracteres, un número, una letra mayúscula, una letra minúscula y un carácter especial.");
    });

    // Al hacer clic en Enviar Código (sec_1 → sec_2)
    $("#comprobar").click(function () {
        if (validarCedula()) {
            var datos = new FormData($('#c')[0]);
            datos.append('accion', 'comprobar');
            enviaAjax(datos);
            abrirAlertaEspara('Se le esta enviando un correo con el código de recuperacion.', 'Espere un momento.');
        }
    });

    // Al hacer clic en Enviar Código (sec_2 → sec_3)
    $("#comprobarCodigo").click(function () {
        if (validarCodigo()) {
            var datos = new FormData($('#r')[0]);
            datos.append('accion', 'comprobarCodigo');
            enviaAjax(datos);
        }
    });
    $("#reenviar").click(function () {
        var datos = new FormData();
        datos.append('accion', 'reenviar');
        enviaAjax(datos);
        abrirAlertaEspara('Se le esta reenviando un correo con el código de recuperacion.', 'Espere un momento.');
    });

    $("#cambiar").click(function () {
        if ($('#contraseña').val() === $('#contraseña_r').val()) {
            var datos = new FormData($('#f')[0]);
            datos.append('accion', 'cambiar');
            enviaAjax(datos);
        }
        else {
            muestraMensaje("success", 2000, "Error", 'Las contraseñas ingresadas no coinciden. Por favor, verifíquelas.');
        }
    });
});

let contadorInterval;

function iniciarContador() {
    let duracion = 30;
    const $contador = $("#contador");
    const $boton = $("#reenviar");

    $boton.prop("disabled", true).css("opacity", "0.6");

    clearInterval(contadorInterval); // Limpiar si ya hay uno
    contadorInterval = setInterval(() => {
        const minutos = Math.floor(duracion / 60);
        const segundos = duracion % 60;
        $contador.text(`Reenviar el codigo en (${minutos}:${segundos < 10 ? '0' : ''}${segundos})`);
        duracion--;

        if (duracion < 0) {
            clearInterval(contadorInterval);
            $boton.prop("disabled", false).css("opacity", "1");
            $contador.text("¡Puedes reenviar el código!");
        }
    }, 1000);
}

function validarCedula() {
    if (validarkeyup(/^[0-9]{7,8}$/, $('#cedula'),
        $("#cedula_spam"), "Minimo 7 maximo 8 digitos, solo numeros")) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar una cedula valida");
        return false;
    }
    return true;
}
function validarCodigo() {
    if (validarkeyup(/^[0-9]{6}$/, $('#codigo'),
        $("#codigo_spam"), "solo 6 numeros")) {
        muestraMensaje("error", 2000, "Error", "El codigo solo tiene 6 numero");
        return false;
    }
    return true;
}

function sec_1() {
    $("#sec_1").addClass("visible");
    $("#sec_2, #sec_3").addClass("slide-right-in");
}
function sec_2() {
    $("#sec_1").removeClass("visible").addClass("slide-left");
    $("#sec_2").removeClass("slide-right-in").addClass("visible");
    iniciarContador();
}
function sec_3() {
    $("#sec_2").removeClass("visible").addClass("slide-left");
    $("#sec_3").removeClass("slide-right-in").addClass("visible");
    iniciarContador();
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
        timeout: 20000,
        success: function (respuesta) {
            try {
                var lee = JSON.parse(respuesta);

                if (lee.accion == "comprobar") {
                    if (lee.resultado == 1) {
                        cerrarAlertaEspara();
                        muestraMensaje("success", 2000, "Enviado", lee.mensaje);
                        sec_2();
                    } else {
                        cerrarAlertaEspara();
                        muestraMensaje("error", 2000, "Error", lee.mensaje);
                    }

                } else if (lee.accion == "comprobarCodigo") {
                    if (lee.resultado == 1) {
                        muestraMensaje("success", 2000, "Validado", lee.mensaje);
                        sec_3();
                    } else {
                        muestraMensaje("error", 2000, "Error", lee.mensaje);
                    }

                }
                else if (lee.accion == "cambiar") {
                    if (lee.resultado == 1) {
                        muestraMensaje("success", 2000, "Validado", lee.mensaje);
                        setTimeout(function () {
                            window.location.href = "/Proyecto_Plantilla/public/";
                        }, 2000)
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