$(document).ready(function () {
    consultar();

    // Botón para generar un nuevo respaldo
    $("#btn_generar").on("click", function () {
        confirmar('¿Desea crear un nuevo respaldo de la base de datos en este momento?', function (confirmado) {
            if (confirmado) {
                abrirAlertaEspara('Creando Respaldo', 'Conectando con la base de datos, por favor espere...');
                let datos = new FormData();
                datos.append('accion', 'generar');
                enviaAjaxRespaldo(datos);
            }
        });
    });
});

function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjaxRespaldo(datos);
}

function restaurar(archivo) {
    confirmar(`¡ADVERTENCIA CRÍTICA! ¿Está seguro que desea restaurar el sistema a la versión: ${archivo}? Se sobreescribirán todos los datos actuales.`, function (confirmado) {
        if (confirmado) {
            abrirAlertaEspara('Restaurando Sistema', 'Inyectando sentencias SQL, el sistema no responderá por unos segundos...');
            let datos = new FormData();
            datos.append('accion', 'restaurar');
            datos.append('archivo', archivo);
            enviaAjaxRespaldo(datos);
        }
    });
}

function eliminar(archivo) {
    confirmar(`¿Eliminar permanentemente el respaldo ${archivo} del servidor?`, function (confirmado) {
        if (confirmado) {
            let datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('archivo', archivo);
            enviaAjaxRespaldo(datos);
        }
    });
}

function crearConsulta(datos) {
    const contenedor = $('#resultadoconsulta');

    contenedor.empty();

    if (datos.length === 0) {
        contenedor.append('<div class="listado_vacio"><p>No hay respaldos almacenados.</p></div>');
    } else {
        datos.forEach(dato => {

            // --- INICIO DE LA VALIDACIÓN DE ESTATUS ---
            let accionesHtml = '';
            let estatus = "";
            let clase = "";

            if (dato.estatus == 1) {
                estatus = "Guardado";
                clase = "estatus_v";
                accionesHtml = `
                    <div style="display:flex; gap:5px;">
                        <button class="btn_t cbt_v" onclick="restaurar('${dato.nombre}')" title="Restaurar esta versión"><i class="fi fi-sr-time-past"></i></button>
                        <button class="btn_t cbt_r" onclick="eliminar('${dato.nombre}')" title="Eliminar respaldo"><i class="fi fi-sr-trash-xmark"></i></button>
                    </div>
                `;
            } else if (dato.estatus == 2) {
                estatus = "Eliminado";
                clase = "estatus_r";
                accionesHtml = ``;
            }
            // --- FIN DE LA VALIDACIÓN ---

            let registro = `
                <div class="listado_contenedor_grupal">
                    <div class="listado_item">
                        <div class="listado_col_datos">
                            <div class="listado_dato_grupo" style="width: 40%;">
                                <small>Archivo</small>
                                <span style="font-weight: bold; color: var(--texto-principal);">${dato.nombre}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Fecha de Creación</small>
                                <span>${dato.fecha}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Creado por</small>
                                <span>${dato.creador}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Peso</small>
                                <span>${dato.tamano}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Estatus</small>
                                <span class="${clase}">${estatus}</span>
                            </div>
                        </div>

                        <div class="listado_col_acciones">
                            ${accionesHtml}
                        </div>
                    </div>
                </div>
            `;
            contenedor.append(registro);
        });
    }
}

function enviaAjaxRespaldo(datos) {
    var token = $('meta[name="csrf-token"]').attr('content');

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
        timeout: 30000,
        success: function (respuesta) {
            try {
                var lee = JSON.parse(respuesta);

                if (lee.accion == "consultar") {
                    crearConsulta(lee.datos);
                }
                else if (lee.accion == "generar") {
                    cerrarModal();
                    consultar();
                    muestraMensaje("success", 3000, "Éxito", lee.mensaje);
                }
                else if (lee.accion == "restaurar" || lee.accion == "eliminar") {
                    cerrarModal();
                    consultar();
                    muestraMensaje("success", 3000, "Proceso Completado", lee.mensaje);
                }
                else if (lee.accion == "error") {
                    cerrarModal();
                    muestraMensaje("error", 4000, "Error del Sistema", lee.mensaje);
                }
            } catch (e) {
                alert("Error procesando respuesta: " + e.message);
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 3000, "Tiempo de espera agotado", "El proceso está tardando demasiado.");
            } else {
                muestraMensaje("error", 3000, "Error", "Fallo de conexión con el servidor.");
            }
        }
    });
}