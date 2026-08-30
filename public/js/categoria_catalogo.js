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
            opcionesReporte(function(formato) {
                if (typeof abrirAlertaEspara === 'function') {
                    abrirAlertaEspara('Se está generando el reporte', 'Espere un momento');
                }
                var datos = new FormData($('#f')[0]);
                datos.append('accion', 'generar');
                datos.append('formato', formato);
                enviaAjax(datos);
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

    $('#ayuda').on('click', function () {
        const pasos = [
            { 
                element: '#busqueda', 
                popover: { 
                    title: 'Búsqueda Rápida', 
                    description: 'Filtra las categorías escribiendo su nombre o descripción.', 
                    position: 'bottom' 
                } 
            },
            { 
                element: '#incluir', 
                popover: { 
                    title: 'Nueva Categoría', 
                    description: 'Haz clic aquí para abrir el formulario y registrar una nueva categoría.', 
                    position: 'bottom' 
                } 
            },
            { 
                element: '#generar', 
                popover: { 
                    title: 'Exportar Reportes', 
                    description: 'Genera y descarga un reporte en PDF o Excel de las categorías.', 
                    position: 'left' 
                } 
            },
            { 
                element: '#resultadoconsulta', 
                popover: { 
                    title: 'Lista de Categorías', 
                    description: 'Aquí verás todas las categorías registradas. Puedes editarlas o eliminarlas desde las acciones.', 
                    position: 'top' 
                } 
            },
            {
                element: '.listado_item',
                popover: { title: 'Registro', description: 'Aquí se muestran los datos de la categoría.', position: 'bottom' }
            },
            {
                element: '.listado_col_acciones',
                popover: { title: 'Acciones', description: 'Usa estos botones para modificar los datos de la categoría o eliminarla del sistema.', position: 'left' }
            },
            {
                element: '#rowsPerPage',
                popover: { title: 'Registros Deseados', description: 'Aquí podrá seleccionar la cantidad de registros que quiere que se muestren.', position: 'top' }
            },
            {
                element: '#botonera',
                popover: { title: 'Cambiar de Página', description: 'Botones para cambiar de página.', position: 'top' }
            },
            {
                element: '#cantidad',
                popover: { title: 'Cantidad', description: 'Aquí puedes ver la cantidad de registros mostrados actualmente.', position: 'top' }
            }
        ];
        if (typeof iniciarTourConPasos === 'function') {
            iniciarTourConPasos(pasos).start();
        }
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



function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    contenedor.html(htmlRecibido);

    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
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
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta);
                return;
            }
            try {
                var lee = JSON.parse(respuesta);
                
                if (lee.accion === "reporte") {
                    if (typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
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
                    if (typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
                    muestraMensaje("error", 3000, "Error", lee.mensaje);
                }
            } catch (e) {
                if (typeof cerrarAlertaEspara === 'function') cerrarAlertaEspara();
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