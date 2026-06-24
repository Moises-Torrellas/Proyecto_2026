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

    // 2. Validaciones en tiempo real para Categorías
    // Nombre: Letras, números, espacios y guiones (Ej: "U-12", "Sub 20")
    Validacion("nombre", /^[A-Za-z0-9\-\b\s]*$/, /^[A-Za-z0-9\-\b\s]{2,30}$/, "Permitido entre 2 y 30 caracteres (letras, números y guiones)", "proceso");
    Validacion("descripcion", /^[A-Za-z0-9\-\b\s]*$/, /^[A-Za-z0-9\-\b\s]{2,30}$/, "Permitido entre 2 y 30 caracteres (letras, números y guiones)", "proceso");

    // 3. Lógica de los Botones Guardar/Modificar
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

    // 4. Botones de la vista
    $("#incluir").on("click", function () {
        limpia(); // Limpia el formulario
        $("#id_categoria").val(""); // Ajustado a id_categoria
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

function buscar(id_categoria) { // Parámetro ajustado
    var datos = new FormData();
    datos.append('accion', 'buscar');
    datos.append('id_categoria', id_categoria); // Ajustado a id_categoria
    enviaAjax(datos);
}

function eliminar(id_categoria) { // Parámetro ajustado
    confirmar('¿Está seguro que quiere eliminar esta categoría?', function (confirmado) {
        if (confirmado) {
            var datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id_categoria', id_categoria); // Ajustado a id_categoria
            enviaAjax(datos);
        }
    });
}

function validarEnvio(proceso) {
    if (validarkeyup(/^[A-Za-z0-9\-\b\s]{2,30}$/, $("#nombre"), $("#nombre_spam"), "Permitido entre 2 y 30 caracteres (letras, números y guiones)", true)) {
        muestraMensaje("error", 2000, "Error", "Debe ingresar un nombre de categoría válido");
        return false;
    }
    else if (validarkeyup(/^[A-Za-z0-9\-\b\s]{2,30}$/, $("#descripcion"), $("#descripcion_spam"), "Permitido entre 2 y 30 caracteres (letras, números y guiones)", true)) {
        muestraMensaje("error", 2000, "Error", "Debe ingresar una descripción válida");
        return false;
    }
    
    return true;
}

function modificar(datos) {
    $("#proceso").data("accion", "modificar");
    $("#proceso").text("Modificar Categoría");
    $("#titulo_modal").text("Modificar Categoría");
    
    // Llenamos el formulario con los datos recibidos de la BD (Ajustado id_categoria)
    $('#id_categoria').val(datos[0].id_categoria);
    $('#nombre').val(datos[0].nombre);
    $('#descripcion').val(datos[0].descripcion);

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
                                <small>Descripción</small>
                                <span>${escapeHTML(dato.descripcion)} </span>
                            </div>
                        </div>

                        <div class="listado_col_acciones">
                            <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(${dato.id_categoria})" title="Modificar"><i class="fi fi-sr-pencil"></i></button>
                                <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(${dato.id_categoria})" title="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
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
    if (!texto) return "";
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
        url: "", // Se envía al controlador actual de la ruta (/CategoriaCatalogo)
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