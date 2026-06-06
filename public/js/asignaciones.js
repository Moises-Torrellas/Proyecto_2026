$(document).ready(function () {
    consultar();
    cargarCombos();

    $("#btn_nuevo").on("click", function () {
        $("#f")[0].reset();
        $("#id_asignacion").val('');
        $("#titulo_modal").text("Registrar Asignación");
        $("#btn_guardar").text("Confirmar Préstamo").attr("data-accion", "incluir");
        $('#fecha_asignacion').val(new Date().toISOString().split('T')[0]);
        
        abrirModal(); 
    });

    $('#btn_guardar').on('click', function () {
        if ($('#id_atleta').val() === "" || $('#id_equipamiento').val() === "") {
            muestraMensaje("error", 2000, "Validación", "Debe seleccionar un atleta y una pieza.");
            return false;
        }

        let datos = new FormData($('#f')[0]);
        datos.append('accion', $(this).attr('data-accion'));
        enviaAjax(datos);
    });
});

function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}

// Ahora llamamos a MultiConsulta
function cargarCombos() {
    let datos = new FormData();
    datos.append('accion', 'MultiConsulta');
    enviaAjax(datos);
}

function editar(id_asignacion, id_atleta, id_equipamiento, fecha) {
    $("#f")[0].reset();
    $("#titulo_modal").text("Modificar Asignación");
    $("#btn_guardar").text("Guardar Cambios").attr("data-accion", "modificar");
    
    $("#id_asignacion").val(id_asignacion);
    $("#fecha_asignacion").val(fecha);
    $("#id_atleta").val(id_atleta).trigger('change');
    
    if ($(`#id_equipamiento option[value='${id_equipamiento}']`).length === 0) {
        $("#id_equipamiento").append(new Option("Equipo Actual (Mantenido)", id_equipamiento, true, true));
    } else {
        $("#id_equipamiento").val(id_equipamiento).trigger('change');
    }
    
    abrirModal();
}

function anular(id_asignacion, id_equipamiento) {
    Swal.fire({
        title: 'Anular Asignación',
        text: "Ingrese el motivo de la anulación (Mínimo 5 caracteres):",
        input: 'text',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value || value.trim().length < 5) {
                return '¡El motivo no es válido!'
            }
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

function crearConsulta(datos) {
    const contenedor = $('#resultadoconsulta');
    contenedor.empty();

    if (datos.length === 0) {
        contenedor.append('<div class="listado_vacio"><p>No hay asignaciones activas.</p></div>');
    } else {
        datos.forEach(dato => {
            let registro = `
                <div class="listado_contenedor_grupal">
                    <div class="listado_item">
                        <div class="listado_col_datos">
                            <div class="listado_dato_grupo" style="width: 15%;">
                                <small>Fecha</small>
                                <span style="font-weight: bold;">${dato.fecha_vista}</span>
                            </div>
                            <div class="listado_dato_grupo" style="width: 35%;">
                                <small>Atleta</small>
                                <span style="font-weight: bold; color: var(--texto-principal);">${dato.atleta}</span>
                                <small>CI: ${dato.doc_identidad}</small>
                            </div>
                            <div class="listado_dato_grupo" style="width: 30%;">
                                <small>Equipo Asignado</small>
                                <span>${dato.articulo}</span>
                                <small style="color: #6c757d;">(Pza ID: #${dato.id_equipamiento})</small>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Estado</small>
                                <span style="color:#ffc107; font-weight:bold;">EN USO</span>
                            </div>
                        </div>
                        <div class="listado_col_acciones">
                            <div style="display:flex; gap:5px;">
                                <button class="btn_t cbt_v" onclick="editar(${dato.id_asignacion}, ${dato.id_atleta}, ${dato.id_equipamiento}, '${dato.fecha_real}')" title="Modificar"><i class="fi fi-sr-edit"></i></button>
                                <button class="btn_t cbt_r" onclick="anular(${dato.id_asignacion}, ${dato.id_equipamiento})" title="Anular Asignación"><i class="fi fi-sr-ban"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            contenedor.append(registro);
        });
    }
}

function poblarCombos(atletas, equipos) {
    let comboAtleta = $("#id_atleta");
    let comboEquipo = $("#id_equipamiento");
    
    comboAtleta.find('option:not(:first)').remove();
    comboEquipo.find('option:not(:first)').remove();

    if (atletas && atletas.length > 0) {
        atletas.forEach(a => {
            // Evaluamos que el atleta esté activo (estatus = 1) según el ModeloAtletas
            if(a.estatus == 1) {
                comboAtleta.append(`<option value="${a.id_atleta}">${a.nombres} ${a.apellidos} (CI: ${a.doc_identidad})</option>`);
            }
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

function enviaAjax(datos) {
    var token = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        async: true,
        url: "", 
        type: "POST",
        contentType: false,
        data: datos,
        processData: false,
        cache: false,
        beforeSend: function (request) { request.setRequestHeader("X-CSRF-TOKEN", token); },
        success: function (respuesta) {
            try {
                var lee = JSON.parse(respuesta);
                if (lee.accion == "consultar") {
                    crearConsulta(lee.datos);
                } else if (lee.accion == "MultiConsulta") {
                    // Lee las llaves que configuramos en PHP
                    poblarCombos(lee.atletas, lee.equipos);
                } else if (lee.accion == "exito") {
                    cerrarModal(); 
                    consultar();
                    cargarCombos(); 
                    muestraMensaje("success", 3000, "Operación Exitosa", lee.mensaje);
                } else if (lee.accion == "error") {
                    muestraMensaje("error", 4000, "Alerta", lee.mensaje || lee.codigo);
                }
            } catch (e) {
                console.error("Error del servidor:", respuesta);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Comunicación',
                    text: 'El servidor falló. Presiona F12 y revisa la pestaña Console o Network para ver el código del error.'
                });
            }
        }
    });
}