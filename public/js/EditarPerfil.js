$(document).ready(function () {
    // ==========================================
    // 1. FORMATEO DE INPUTS EN TIEMPO REAL
    // ==========================================
    $("#cedula").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        if (input.length > 8) {
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

    // ==========================================
    // 2. VALIDACIONES EN TIEMPO REAL (Librería global)
    // ==========================================
    Validacion("cedula", /^[0-9\b]*$/, /^[0-9]{7,8}$/, "Mínimo 7, máximo 8 dígitos, solo números", "proceso");
    Validacion("nombre", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");
    Validacion("apellido", /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, /^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, "Solo letras entre 3 y 30 caracteres", "proceso");
    Validacion("telefono", /^[0-9\-\b]*$/, /^[0-9]{4}[-]{1}[0-9]{7}$/, "El formato es 0400-0000000");
    Validacion("correo", /^[a-zA-Z0-9@._\-]*$/, /^(?=.{3,60}$)[^\s@]+@[^\s@]+\.(com|org|net|edu|gov|mil|info|io|co|es|mx|ar|cl|pe|br)$/i, "Ejemplo: usuario@dominio.com");
    Validacion("contrasena", /^[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]*$/, /^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]{8,20}$/, "8-20 caracteres, incluye Mayúscula, Minúscula, Número y Carácter Especial");
    Validacion("confirmar_contrasena", /^[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]*$/, /^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]{8,20}$/, "Las contraseñas deben coincidir");

    // ==========================================
    // 3. EVENTOS DE ENVÍO DE FORMULARIOS
    // ==========================================

    // --- FORMULARIO: INFORMACIÓN PERSONAL ---
    $('#btn_editar_personal').on('click', function () {
        if (validarPersonal()) {
            confirmar("¿Está seguro de actualizar su Información Personal?", function (confirmado) {
                if (confirmado) {
                    var datos = new FormData($('#form_personal')[0]);
                    if (window.croppedImageBlob) {
                        datos.set('foto', window.croppedImageBlob, 'foto_recortada.jpg');
                    }
                    datos.append('accion', 'editar_personal');
                    enviaAjax(datos);
                }
            });
        }
    });

    // --- FORMULARIO: CONTACTO ---
    $('#btn_editar_contacto').on('click', function () {
        if (validarContacto()) {
            confirmar("¿Está seguro de actualizar su Información de Contacto?", function (confirmado) {
                if (confirmado) {
                    var datos = new FormData($('#form_contacto')[0]);
                    datos.append('accion', 'editar_contacto');
                    enviaAjax(datos);
                }
            });
        }
    });

    // --- FORMULARIO: SEGURIDAD ---
    $('#btn_editar_seguridad').on('click', function () {
        if (validarSeguridad()) {
            confirmar("¿Está seguro de actualizar su Contraseña?", function (confirmado) {
                if (confirmado) {
                    var datos = new FormData($('#form_seguridad')[0]);
                    datos.append('accion', 'editar_seguridad');
                    enviaAjax(datos);
                }
            });
        }
    });
});

// ==========================================
// 4. FUNCIONES DE VALIDACIÓN ESPECÍFICAS
// ==========================================

function validarPersonal() {
    if (validarkeyup(/^[0-9]{7,8}$/, $('#cedula'), $("#cedula_spam"), "Mínimo 7 máximo 8 dígitos, solo números", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar una cédula válida");
        return false;
    } else if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, $("#nombre"), $("#nombre_spam"), "Solo letras entre 3 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un nombre válido");
        return false;
    } else if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/, $('#apellido'), $("#apellido_spam"), "Solo letras entre 3 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un apellido válido");
        return false;
    }
    return true;
}

function validarContacto() {
    if (validarkeyup(/^[0-9]{4}[-]{1}[0-9]{7}$/, $('#telefono'), $("#telefono_spam"), "El formato es 0400-0000000", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un teléfono válido");
        return false;
    } else if (validarkeyup(/^(?=.{3,60}$)[^\s@]+@[^\s@]+\.(com|org|net|edu|gov|mil|info|io|co|es|mx|ar|cl|pe|br)$/i, $('#correo'), $("#correo_spam"), "Correo no válido. Ejemplo: usuario@dominio.com", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar un correo válido");
        return false;
    }
    return true;
}

function validarSeguridad() {
    if (validarkeyup(/^(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#\$%\^\&*\)\(+=._-])[0-9A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC!@#\$%\^\&*\)\(+=._-]{8,20}$/, $('#contrasena'), $("#contrasena_spam"), "Entre 8 y 20 caracteres, un número, una letra mayúscula, una letra minúscula y un carácter especial.", true)) {
        muestraMensaje("error", 2000, "Error", "Tiene que ingresar una contraseña válida según los parámetros");
        return false;
    }
    
    // Validación adicional para confirmar que sean iguales
    if ($('#contrasena').val() !== $('#confirmar_contrasena').val()) {
        $('#confirmar_contrasena_spam').text("Las contraseñas no coinciden").css("color", "red");
        muestraMensaje("error", 2000, "Error", "Las contraseñas no coinciden");
        return false;
    } else {
        $('#confirmar_contrasena_spam').text("");
    }
    
    return true;
}

// ==========================================
// 5. NÚCLEO DE COMUNICACIÓN AJAX
// ==========================================
var token = $('meta[name="csrf-token"]').attr('content');

function enviaAjax(datos) {
    $.ajax({
        async: true,
        url: "", // Ajustar a la ruta del controlador si es necesario
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
                
                if (lee.accion === "exito") {
                    muestraMensaje("success", 2000, "Actualización Exitosa", lee.mensaje).then(function() {
                        window.location.reload();
                    });
                } else if (lee.accion === "error") {
                    muestraMensaje("error", 2500, "Error de Actualización", lee.mensaje).then(function() {
                        window.location.reload();
                    });
                }
            } catch (e) {
                console.error("Error al procesar la respuesta JSON: " + e.name);
                muestraMensaje("error", 2000, "Error", "Respuesta inesperada del servidor").then(function() {
                    window.location.reload();
                });
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo").then(function() {
                    window.location.reload();
                });
            } else {
                muestraMensaje("error", 2000, "Error", "Problema de conexión con el servidor").then(function() {
                    window.location.reload();
                });
            }
        }
    });
}
