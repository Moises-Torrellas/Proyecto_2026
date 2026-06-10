$(document).ready(function () {
    consultar();
    cargarCombos();

    // Abrir modal para nuevo
    $("#btn_nuevo").on("click", function () {
        $("#f")[0].reset();
        $("#id_equipamiento").val('');
        $("#titulo_modal").text("Registrar Nueva Pieza");
        $("#btn_guardar").attr("data-accion", "incluir");
        abrirModal(); 
    });

    // Guardar o Modificar
    $('#btn_guardar').on('click', function () {
        if ($('#id_catalogo').val() === "" || $('#id_estado').val() === "") {
            muestraMensaje("error", 2000, "Campos Vacíos", "Debe seleccionar un artículo y su estado.");
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

function cargarCombos() {
    let datos = new FormData();
    datos.append('accion', 'cargar_combos');
    enviaAjax(datos);
}

function editar(id, catalogo, estado) {
    $("#f")[0].reset();
    $("#titulo_modal").text("Modificar Inventario");
    $("#btn_guardar").attr("data-accion", "modificar");
    
    $("#id_equipamiento").val(id);
    $("#id_catalogo").val(catalogo);
    $("#id_estado").val(estado);
    
    abrirModal();
}

function eliminar(id) {
    confirmar(`¿Eliminar la pieza ID ${id} del inventario?`, function (confirmado) {
        if (confirmado) {
            let datos = new FormData();
            datos.append('accion', 'eliminar');
            datos.append('id_equipamiento', id);
            enviaAjax(datos);
        }
    });
}

function crearConsulta(datos) {
    const contenedor = $('#resultadoconsulta');
    contenedor.empty();

    if (datos.length === 0) {
        contenedor.append('<div class="listado_vacio"><p>No hay equipamientos en el inventario.</p></div>');
    } else {
        datos.forEach(dato => {
            // Color según el estado (Excelente verde, Malo rojo, etc)
            let colorEstado = dato.nivel_estado == 1 ? '#28a745' : (dato.nivel_estado == 2 ? '#ffc107' : '#dc3545');
            let colorEstatus = dato.estatus == 1 ? 'estatus_v' : 'estatus_a' ;
            let estatus = dato.estatus == 1 ? 'Disponible' : 'En Uso' ;
            let talla = dato.talla ? ` - Talla: ${dato.talla}` : '';

            let registro = `
                <div class="listado_contenedor_grupal">
                    <div class="listado_item">
                        <div class="listado_col_datos">
                            <div class="listado_dato_grupo" style="width: 10%;">
                                <small>ID</small>
                                <span style="font-weight: bold; color: var(--texto-principal);">#${dato.id_equipamiento}</span>
                            </div>
                            <div class="listado_dato_grupo" style="width: 40%;">
                                <small>Artículo (Catálogo)</small>
                                <span style="font-weight: bold;">${dato.articulo}${talla}</span>
                            </div>
                            <div class="listado_dato_grupo" style="width: 25%;">
                                <small>Categoría</small>
                                <span>${dato.categoria}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Condición Físico</small>
                                <span style="color: ${colorEstado}; font-weight:bold;">${dato.estado}</span>
                            </div>
                            <div class="listado_dato_grupo">
                                <small>Estatus</small>
                                <span class="${colorEstatus}">${estatus}</span>
                            </div>
                        </div>

                        <div class="listado_col_acciones">
                            <div style="display:flex; gap:5px;">
                                <button class="btn_t cbt_v" onclick="editar(${dato.id_equipamiento}, ${dato.id_catalogo || 0}, ${dato.id_estado || 0})" title="Modificar"><i class="fi fi-sr-edit"></i></button>
                                <button class="btn_t cbt_r" onclick="eliminar(${dato.id_equipamiento})" title="Eliminar"><i class="fi fi-sr-trash-xmark"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            contenedor.append(registro);
        });
    }
}

function poblarCombos(catalogos, estados) {
    let comboCat = $("#id_catalogo");
    let comboEst = $("#id_estado");
    
    // Limpiamos (dejando la opción por defecto)
    comboCat.find('option:not(:first)').remove();
    comboEst.find('option:not(:first)').remove();

    catalogos.forEach(c => {
        let txt = c.talla ? `${c.nombre} (Talla: ${c.talla})` : c.nombre;
        comboCat.append(`<option value="${c.id_catalogo}">${txt}</option>`);
    });

    estados.forEach(e => {
        // AQUÍ ESTABA EL ERROR: Decía c.id_estado en lugar de e.id_estado
        comboEst.append(`<option value="${e.id_estado}">${e.nombre}</option>`);
    });
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
        beforeSend: function (request) {
            request.setRequestHeader("X-CSRF-TOKEN", token);
        },
        success: function (respuesta) {
            try {
                var lee = JSON.parse(respuesta);
                
                if (lee.accion == "consultar") {
                    crearConsulta(lee.datos);
                } 
                else if (lee.accion == "cargar_combos") {
                    poblarCombos(lee.catalogos, lee.estados);
                }
                else if (lee.accion == "exito") {
                    cerrarModal(); 
                    consultar();
                    muestraMensaje("success", 3000, "Operación Exitosa", lee.mensaje);
                } 
                else if (lee.accion == "error") {
                    muestraMensaje("error", 4000, "Alerta del Sistema", lee.mensaje);
                }
            } catch (e) {
                console.error("Error JSON:", respuesta);
            }
        }
    });
}