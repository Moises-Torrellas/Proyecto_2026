$(document).ready(function () {
    if (typeof inicializarPaginador === 'function') inicializarPaginador();
    MultiConsulta();

    $("#btn_nuevo").on("click", function () {
        $("#f")[0].reset();
        $("#id_asignacion").val('');
        $("#titulo_modal").text("Registrar Asignación");
        $("#btn_guardar").text("Registrar Asignación").data("accion", "incluir");
        $('#fecha_asignacion').val(new Date().toISOString().split('T')[0]);
        abrirModal(); 
    });

    $('#btn_guardar').on('click', function () {
        if ($('#id_atleta').val() === "" || $('#id_equipamiento').val() === "") {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar un atleta y una pieza.");
            return false;
        }
        let accion = $(this).data("accion");
        let datos = new FormData($('#f')[0]);
        datos.append('accion', accion);
        enviaAjax(datos);
    });
});

function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}

function MultiConsulta() {
    let datos = new FormData();
    datos.append('accion', 'MultiConsulta');
    enviaAjax(datos);
}

function editar(id_asignacion, id_atleta, id_equipamiento, fecha) {
    $("#f")[0].reset();
    $("#titulo_modal").text("Modificar Asignación");
    $("#btn_guardar").text("Guardar Cambios").data("accion", "modificar");
    $("#id_asignacion").val(id_asignacion);
    $("#fecha_asignacion").val(fecha);
    $("#id_atleta").val(id_atleta).trigger('change');
    
    if ($(`#id_equipamiento option[value='${id_equipamiento}']`).length === 0) {
        $("#id_equipamiento").append(new Option("Equipo Actual (Mantenido)", id_equipamiento, true, true));
    }
    $("#id_equipamiento").val(id_equipamiento).trigger('change');
    abrirModal();
}

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
        cancelButtonText: 'Cancelar',
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
        beforeSend: function (request) { request.setRequestHeader("X-CSRF-TOKEN", token); },
        timeout: 10000,
        success: function (respuesta) {
            if (typeof respuesta === 'string' && respuesta.trim().startsWith('<')) {
                $('#resultadoconsulta').html(respuesta);
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
                Swal.fire({ icon: 'error', title: 'Error de Comunicación', text: 'El servidor falló.' });
            }
        },
        error: function (request, status, err) {
            muestraMensaje("error", 2000, "Error", "ERROR: " + err);
        }
    });
}