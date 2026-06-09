let timerBusqueda;

// Escuchador para la barra de búsqueda en tiempo real
$('#busqueda').off('keyup').on('keyup', busqueda);

function MultiConsulta() {
    let datos = new FormData();
    datos.append('accion', 'MultiConsulta');
    enviaAjax(datos);
}

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
    MultiConsulta();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();

    // Validaciones de formato (Usando la función global de tu framework)
    if (typeof Validacion === 'function') {
        Validacion("fecha_asignacion", /^[0-9\b-]*$/, /^\d{4}-\d{2}-\d{2}$/, "Seleccione una fecha válida", "btn_guardar");
    }

    // Configuración de Select2 para que funcione perfectamente dentro del Modal
    $('#id_atleta').select2({
        placeholder: "Seleccione un atleta...",
        allowClear: true,
        dropdownParent: $('.contenedor_modal')
    });
    
    $('#id_equipamiento').select2({
        placeholder: "Seleccione una pieza...",
        allowClear: true,
        dropdownParent: $('.contenedor_modal')
    });

    // Acción principal del botón de guardado (Equivalente al #proceso en pagos)
    $('#btn_guardar').on('click', function () {
        let accion = $(this).data("accion");
        
        if (validarEnvio(accion)) {
            let textoConfirmacion = accion === "incluir" 
                ? '¿Está seguro que quiere registrar esta asignación?' 
                : '¿Está seguro que quiere modificar esta asignación?';

            // Usamos tu función global confirmar()
            confirmar(textoConfirmacion, function (confirmado) {
                if (confirmado) {
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', accion);
                    enviaAjax(datos);
                }
            });
        }
    });

    // Botón para abrir el modal de nueva asignación (Equivalente a #incluir)
    $("#btn_nuevo").on("click", function () {
        limpia(); // Tu función global para limpiar inputs
        $("#f")[0].reset();
        $("#id_asignacion").val('');
        $("#titulo_modal").text("Registrar Asignación");
        $("#btn_guardar").text("Confirmar Préstamo").data("accion", "incluir");
        
        // Forzar fecha del sistema
        let hoy = new Date().toISOString().split('T')[0];
        $('#fecha_asignacion').val(hoy);
        
        $('#id_atleta').val(null).trigger('change');
        $('#id_equipamiento').val(null).trigger('change');
        
        abrirModal(); 
    });

    // Tour de Ayuda (Estilo Pagos/Atletas)
    $('#ayuda').on('click', function () {
        const pasos = [
            {
                element: '#btn_nuevo',
                popover: { title: 'Nueva Asignación', description: 'Pulsa aquí para prestar un equipo a un atleta.', position: 'bottom' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Equipos Asignados', description: 'Aquí verás todo el inventario que actualmente está en uso.', position: 'top' }
            }
        ];
        if (typeof iniciarTourConPasos === 'function') {
            const driver = iniciarTourConPasos(pasos);
            driver.start();
        }
    });
});

// Función de validación centralizada (Estilo Pagos)
function validarEnvio(accion) {
    if (accion === "incluir" || accion === "modificar") {
        if ($('#id_atleta').val() == "" || $('#id_atleta').val() == null) {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar un atleta.");
            return false;
        }
        if ($('#id_equipamiento').val() == "" || $('#id_equipamiento').val() == null) {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar una pieza de equipamiento.");
            return false;
        }
        if ($('#fecha_asignacion').val() == "") {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar la fecha de asignación.");
            return false;
        } else {
            // Prevenir fechas futuras
            let fechaIngresada = $('#fecha_asignacion').val();
            let hoy = new Date();
            let mes = (hoy.getMonth() + 1).toString().padStart(2, '0');
            let dia = hoy.getDate().toString().padStart(2, '0');
            let fechaActualStr = hoy.getFullYear() + '-' + mes + '-' + dia;
            
            if (fechaIngresada > fechaActualStr) {
                muestraMensaje("error", 2000, "Error", "La fecha de asignación no puede ser futura.");
                return false;
            }
        }
    }
    return true;
}

// Preparar el modal para edición
function editar(id_asignacion, id_atleta, id_equipamiento, fecha) {
    limpia();
    $("#f")[0].reset();
    $("#titulo_modal").text("Modificar Asignación");
    $("#btn_guardar").text("Guardar Cambios").data("accion", "modificar");
    
    $("#id_asignacion").val(id_asignacion);
    $("#fecha_asignacion").val(fecha);
    $("#id_atleta").val(id_atleta).trigger('change');
    
    // Mantenemos la opción del equipo actual por si no lo quiere cambiar
    if ($(`#id_equipamiento option[value='${id_equipamiento}']`).length === 0) {
        $("#id_equipamiento").append(new Option("Equipo Actual (Mantenido)", id_equipamiento, true, true));
    }
    $("#id_equipamiento").val(id_equipamiento).trigger('change');
    
    abrirModal();
}

// Mantenemos el SweetAlert personalizado con los colores que configuramos
function anular(id_asignacion, id_equipamiento) {
    Swal.fire({
        title: 'Anular Asignación',
        text: "Ingrese el motivo (Mínimo 5 caracteres):",
        input: 'text',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#39b015',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'No',
        inputValidator: (value) => {
            if (!value || value.trim().length < 5) return '¡El motivo no es válido!';
        }
    }).then((result) => {
        if (result.isConfirmed) {
            let datos = new FormData();
            datos.append('accion', 'anular');
            datos.append('id_asignacion', id_asignacion);
            datos.append('id_equipamiento', id_equipamiento);
            datos.append('motivo', result.value);
            enviaAjax(datos);
        }
    });
}

function poblarCombos(atletas, equipos) {
    let comboAtleta = $("#id_atleta");
    let comboEquipo = $("#id_equipamiento");
    
    comboAtleta.find('option:not(:first)').remove();
    comboEquipo.find('option:not(:first)').remove();

    if (atletas && atletas.length > 0) {
        atletas.forEach(a => {
            if(a.estatus == 1) comboAtleta.append(`<option value="${a.id_atleta}">${a.nombres} ${a.apellidos} (CI: ${a.doc_identidad})</option>`);
        });
    }

    if (equipos && equipos.length > 0) {
        equipos.forEach(e => {
            comboEquipo.append(`<option value="${e.id_equipamiento}">${e.articulo} (Pza #${e.id_equipamiento})</option>`);
        });
    }

    comboAtleta.trigger('change');
    comboEquipo.trigger('change');
}

// Construcción de la tabla (Estilo Pagos)
function crearConsulta(htmlRecibido) {
    const contenedor = $('#resultadoconsulta');
    contenedor.html(htmlRecibido);

    if (typeof lucide !== 'undefined') lucide.createIcons();
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    if (typeof tippy !== 'undefined') tippy('[data-tippy-content]', { theme: 'light' });
}

// AJAX (Estilo Pagos con control de errores)
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
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                crearConsulta(respuesta);
                return;
            }
            try {
                var lee = JSON.parse(respuesta);
                if (lee.accion == "MultiConsulta") {
                    poblarCombos(lee.atletas, lee.equipos);
                } else if (lee.accion == "incluir" || lee.accion == "modificar" || lee.accion == "exito") {
                    consultar();
                    MultiConsulta();
                    cerrarModal();
                    muestraMensaje("success", 2000, "Operación Exitosa", lee.mensaje);
                } else if (lee.accion == "error") {
                    muestraMensaje("error", 2000, "Error", lee.mensaje || lee.codigo);
                }
            } catch (e) {
                console.error("Error procesando JSON", e);
                Swal.fire({ icon: 'error', title: 'Error de Comunicación', text: 'El servidor falló.' });
            }
        },
        error: function (request, status, err) {
            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: " + err);
            }
        }
    });
}