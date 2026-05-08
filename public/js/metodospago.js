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

    // 2. Filtros de escritura (evitar letras en las edades)
    $("#edad_min, #edad_max").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, ''); // Solo números
        if (input.length > 2) {
            input = input.substring(0, 2); // Máximo 2 dígitos
        }
        $(this).val(input);
    });

    // 3. Validaciones en tiempo real para Categorías
    // Nombre: Letras, números, espacios y guiones (Ej: "U-12", "Sub 20")
    Validacion("nombre", /^[A-Za-z0-9\-\b\s]*$/, /^[A-Za-z0-9\-\b\s]{2,30}$/, "Permitido entre 2 y 30 caracteres (letras, números y guiones)", "proceso");
    Validacion("edad_min", /^[0-9\b]*$/, /^[0-9]{1,2}$/, "Edad mínima requerida, solo 1 o 2 dígitos", "proceso");
    Validacion("edad_max", /^[0-9\b]*$/, /^[0-9]{1,2}$/, "Edad máxima requerida, solo 1 o 2 dígitos", "proceso");

    // 4. Lógica de los Botones Guardar/Modificar
    $('#proceso').on('click', function () {
        let accion = $(this).data("accion");
        
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar esta categoría?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar esta categoría?', function (confirmado) {
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

    // 5. Botones de la vista
    $("#incluir").on("click", function () {
        limpia(); // Limpia el formulario
        $("#id").val("");
        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Categoría");
        $("#titulo_modal").text("Registrar Nueva Categoría");
        abrirModal(); // Esta función debe estar definida en tu main.js o base.js
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
    confirmar('¿Está seguro que quiere eliminar esta categoría?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id', id);
            enviaAjax(datos);
        }
    });
}

function validarEnvio(proceso) {
    if (validarkeyup(/^[A-Za-z0-9\-\b\s]{2,30}$/, $("#nombre"), $("#nombre_spam"), "Permitido entre 2 y 30 caracteres (letras, números y guiones)", true)) {
        muestraMensaje("error", 2000, "Error", "Debe ingresar un nombre de categoría válido");
        return false;
    }
    else if (validarkeyup(/^[0-9]{1,2}$/, $("#edad_min"), $("#edad_min_spam"), "Debe ser un número válido", true)) {
        muestraMensaje("error", 2000, "Error", "Debe ingresar una edad mínima válida");
        return false;
    }
    else if (validarkeyup(/^[0-9]{1,2}$/, $("#edad_max"), $("#edad_max_spam"), "Debe ser un número válido", true)) {
        muestraMensaje("error", 2000, "Error", "Debe ingresar una edad máxima válida");
        return false;
    }

    // Validación lógica: Edad mínima no puede ser mayor a la máxima
    let min = parseInt($("#edad_min").val(), 10);
    let max = parseInt($("#edad_max").val(), 10);
    if (min > max) {
        muestraMensaje("error", 3000, "Error", "La edad mínima no puede ser mayor a la edad máxima");
        return false;
    }

    return true;
}

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Categoría");
    $("#titulo_modal").text("Modificar Categoría");
    
    // Llenamos el formulario con los datos recibidos de la BD
    $('#id').val(datos[0].id_categorias);
    $('#nombre').val(datos[0].nombre);
    $('#edad_min').val(datos[0].edad_min);
    $('#edad_max').val(datos[0].edad_max);

    abrirModal();
}

function crearConsulta(datos) {
    const contenedor = $('#resultadoconsulta');
    contenedor.empty();

    if (datos.length === 0) {
        contenedor.append('<div class="listado_vacio"><p>No se encontraron categorías registradas</p></div>');
    } else {
        datos.forEach(dato => {
            let registro = `
                <div class="listado_contenedor_grupal">
                    <div class="listado_item" onclick="toggleDetalles(this)">
                        <div class="listado_col_datos">
                            <div class="listado_dato_grupo">
                                <small>Categoría</small>
                                <span style="font-weight: bold; color: #2ec135;">${escapeHTML(dato.nombre)}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Edad Mínima</small>
                                <span>${dato.edad_min} años</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Edad Máxima</small>
                                <span>${dato.edad_max} años</span>
                            </div>
                        </div>

                        <div class="listado_col_acciones">
                            <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(${dato.id_categorias})" title="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(${dato.id_categorias})" title="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
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
        url: "", // Se envía al controlador actual de la ruta (/Categorias)
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
                    cerrarModal(); // Agregado para que se cierre al guardar
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
                console.error(respuesta); // Útil para ver en la consola si el PHP imprimió un error visible
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