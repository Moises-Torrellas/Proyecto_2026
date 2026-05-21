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
    // 1. Cargar la tabla al iniciar
    consultar();

    // 2. Validaciones en tiempo real para Métodos de Pago
    // Nombre: Letras, números, espacios y acentos (Ej: "Pago Móvil", "Zelle", "Transferencia")
    Validacion("nombre", /^[A-Za-z0-9\sñÑáéíóúÁÉÍÓÚ]*$/, /^[A-Za-z0-9\sñÑáéíóúÁÉÍÓÚ]{2,30}$/, "Permitido entre 2 y 30 caracteres", "proceso");
    
    // Nota: Si 'nec_referencia' y 'estatus' son campos <select>, la validación onkeyup 
    // no es estrictamente necesaria aquí, se controlan mejor en la función validarEnvio().
    // Si son inputs de texto, puedes descomentar y ajustar estas líneas:
    // Validacion("nec_referencia", /^[A-Za-z0-9]*$/, /^[A-Za-z0-9]{1,2}$/, "Especifique si requiere referencia", "proceso");
    // Validacion("estatus", /^[A-Za-z0-9]*$/, /^[A-Za-z0-9]{1,15}$/, "Especifique el estatus", "proceso");

    // 3. Lógica de los Botones Guardar/Modificar
    $('#proceso').on('click', function () {
        let accion = $(this).data("accion");
        
        if (accion == "incluir") {
            if (validarEnvio(accion)) { // Descomentado para que valide antes de incluir
                confirmar('¿Está seguro que quiere registrar este método de pago?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar este método de pago?', function (confirmado) {
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
                    abrirAlertaEspara('Se está generando el reporte', 'Espere un momento')
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    enviaAjax(datos);
                }
            });
        }
    });

    // 4. Botones de la vista
    $("#incluir").on("click", function () {
        limpia(); // Limpia el formulario
        $("#id").val(""); // Este es el input hidden en tu form (asegúrate de que en el backend reciba id_metodos)
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Método");
        $("#titulo_modal").text("Registrar Nuevo Método de Pago");
        abrirModal();
    });

    $("#generar").on("click", function () {
        limpia();
        $("#proceso").data("accion", "generar");
        $("#proceso").text("Generar Reporte");
        $("#titulo_modal").text("Generar Reporte");
        abrirModal();
    });
});

// --- FUNCIONES LÓGICAS GLOBALES ---

function buscar(id) {
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id', id);
    enviaAjax(datos);
}

function eliminar(id) {
    confirmar('¿Está seguro que quiere eliminar este método de pago?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}

function validarEnvio(proceso) {
    // Validar Nombre
    if (validarkeyup(/^[A-Za-z0-9\sñÑáéíóúÁÉÍÓÚ]{2,30}$/, $("#nombre"), $("#nombre_spam"), "Permitido entre 2 y 30 caracteres", true)) {
        muestraMensaje("error", 2000, "Error", "Debe ingresar un nombre de método válido");
        return false;
    }
    
    // Validar Necesita Referencia (Asumiendo que no puede estar vacío)
    if ($("#nec_referencia").val() === null || $("#nec_referencia").val() === "") {
        muestraMensaje("error", 2000, "Error", "Debe indicar si el método necesita referencia");
        // Opcional: mostrar un span de error si tienes uno configurado
        // $("#nec_referencia_spam").text("Campo requerido").show(); 
        return false;
    }

    // Validar Estatus (Asumiendo que no puede estar vacío)
    if ($("#estatus").val() === null || $("#estatus").val() === "") {
        muestraMensaje("error", 2000, "Error", "Debe seleccionar un estatus");
        return false;
    }

    return true;
}

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Método");
    $("#titulo_modal").text("Modificar Método de Pago");
    
    // Llenamos el formulario con los datos recibidos de la BD
    $('#id').val(datos[0].id_metodos); 
    $('#nombre').val(datos[0].nombre);
    $('#nec_referencia').val(datos[0].nec_referencia);
    $('#estatus').val(datos[0].estatus);

    abrirModal();
}

function crearConsulta(datos) {
    const contenedor = $('#resultadoconsulta');
    contenedor.empty();

    if (datos.length === 0) {
        contenedor.append('<div class="listado_vacio"><p>No se encontraron métodos de pago registrados</p></div>');
    } else {
        datos.forEach(dato => {
            // Evaluamos cómo mostrar la info de referencia y estatus de forma amigable
            let txtReferencia = (dato.nec_referencia == '1' || dato.nec_referencia == 'Si' || dato.nec_referencia == 'Sí') ? 'Sí' : 'No';
            let txtEstatus = (dato.estatus == '1' || dato.estatus == 'Activo') ? 'Activo' : 'Inactivo';
            let colorEstatus = (txtEstatus === 'Activo') ? '#2ec135' : '#e74c3c';

            let registro = `
                <div class="listado_contenedor_grupal">
                    <div class="listado_item" onclick="toggleDetalles(this)">
                        <div class="listado_col_datos">
                            <div class="listado_dato_grupo">
                                <small>Método de Pago</small>
                                <span style="font-weight: bold; color: #333;">${escapeHTML(dato.nombre)}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>¿Requiere Ref?</small>
                                <span>${txtReferencia}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Estatus</small>
                                <span style="color: ${colorEstatus}; font-weight: 500;">${txtEstatus}</span>
                            </div>
                        </div>

                        <div class="listado_col_acciones">
                            <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(${dato.id_metodos})" title="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(${dato.id_metodos})" title="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
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

function escapeHTML(texto) {
    if (!texto) return '';
    var caracteres = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(texto).replace(/[&<>"']/g, m => caracteres[m]);
}

var token = $('meta[name="csrf-token"]').attr('content');

function enviaAjax(datos) {
    $.ajax({
        async: true,
        url: "", // Recuerda que esto apunta al controlador actual (/MetodosPago)
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
                else if (lee.accion == "incluir") {
                    consultar();
                    limpia();
                    cerrarModal(); 
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                } 
                else if (lee.accion == "eliminar") {
                    consultar();
                    muestraMensaje("success", 2000, "Eliminación Exitosa", lee.mensaje);
                } 
                else if (lee.accion == "modificar") {
                    consultar();
                    limpia();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Modificación Exitosa", lee.mensaje);
                } 
                else if (lee.accion == "buscar") {
                    modificar(lee.datos);
                }
                else if (lee.accion == "error") {
                    muestraMensaje("error", 3000, "Error", lee.mensaje);
                }
            } catch (e) {
                alert("Error procesando los datos: " + e.message);
                console.error(respuesta); 
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 3000, "Error de Conexión", "Revisa la consola. Código: " + request.status);
                console.error("Detalle del error:", request.responseText);
            }
        }
    });
}