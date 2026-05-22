$('#busqueda').off('keyup').on('keyup', busqueda);
let timerBusqueda;
function consultar() {
    let datos = new FormData();
    datos.append('accion', 'consultar');
    enviaAjax(datos);
}
function consultarM() {
    let datos = new FormData();
    datos.append('accion', 'consultarM');
    enviaAjax(datos);
}
function consultarMP() {
    let datos = new FormData();
    datos.append('accion', 'consultarMP');
    enviaAjax(datos);
}
function consultarC() {
    let datos = new FormData();
    datos.append('accion', 'consultarC');
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
    consultarM();
    consultarMP();
    consultarC();

    /* $("#doc_i").on("input", function () {
        var input = $(this).val().replace(/[^0-9]/g, '');
        if (input.length > 4) {
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
    }); */

    /* Validacion("fecha_nac", /^[0-9\b-]*$/, /^\d{4}-\d{2}-\d{2}$/, "Seleccione una fecha válida", "proceso");
    Validacion("nombre", /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\b]*$/, /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,60}$/, "Solo letras, mínimo 3 caracteres", "proceso");
    Validacion("apellido", /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\b]*$/, /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,60}$/, "Solo letras, mínimo 3 caracteres", "proceso");
    Validacion("doc_i", /^[0-9\b]*$/, /^[0-9]{7,8}$/, "Mínimo 7 máximo 8 dígitos, solo números", "proceso");
    Validacion("telefono", /^[0-9\b-]*$/, /^[0-9]{4}-[0-9]{7}$/, "Formato inválido (XXXX-XXXXXXX)", "proceso");
    Validacion("direccion", /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\b,.-]*$/, /^.{5,150}$/, "Dirección muy corta o inválida", "proceso");
    Validacion("edad", /^[0-9\b]*$/, /^[0-9]{0,10}$/, "Solo numeros", "proceso"); */

    $('#proceso').on('click', function () {
        accion = $(this).data("accion");
        if (accion == "incluir") {
            if (validarEnvio(accion)) {
                confirmar('¿Está seguro que quiere registrar este pago?', function (confirmado) {
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
                confirmar('¿Está seguro que quiere modificar este pago?', function (confirmado) {
                    if (confirmado) {
                        var datos = new FormData($('#f')[0]);
                        var fotoActual = $("#proceso").data("foto_actual")
                        datos.append('foto_actual', fotoActual);
                        datos.append('accion', 'modificar');
                        enviaAjax(datos);
                    }
                });
            }
        }
        else if (accion == "generar") {
            confirmar('¿Está seguro que quiere generar un reporte?', function (confirmado) {
                if (confirmado) {
                    abrirAlertaEspara('Se esta generando el reporte', 'Espere un momento')
                    var datos = new FormData($('#f')[0]);
                    datos.append('accion', 'generar');
                    enviaAjax(datos);
                }
            });
        }
    });


    $('#cuenta').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });
    $('#metodo').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });
    $('#moneda').select2({
        placeholder: "Selecciona una opción",
        allowClear: true,
        dropdownParent: $('.contenedor_modal'),
    });

    $("#incluir").on("click", function () {
        limpia();

        $("#proceso").data("accion", "incluir");
        $("#proceso").text("Registrar Pago");
        $("#titulo_modal").text("Registrar Pago");

        $('#cuenta').val(null).trigger('change');
        $('#metodo').val(null).trigger('change');
        $('#moneda').val(null).trigger('change');
        abrirModal();
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
                popover: { title: 'Barra de Busqueda', description: 'Aqui puedes buscar al Atleta que necesites.', position: 'bottom' }
            },
            {
                element: '#incluir',
                popover: { title: 'Nuevo Atleta', description: 'Si pulsa aqui se abrira un modal para ingresar un nuevo Atleta', position: 'bottom' }
            },
            {
                element: '#generar',
                popover: { title: 'Generar Reportes', description: 'Si pulsa aqui se abrira un modal para generar un reporte en PDF.', position: 'left' }
            },
            {
                element: '#resultadoconsulta',
                popover: { title: 'Atletas Registrados', description: 'Aqui se mostraran todos los Atletas registrados.', position: 'top' }
            },
            {
                element: '#registro',
                popover: { title: 'Registro de un Atleta', description: 'Aqui se mostrara la informacion de un Atleta si pulsa el registro se desplegara mas informacion.', position: 'bottom' }
            },
            {
                element: '#cbt_v',
                popover: { title: 'Modificar Atletas', description: 'Si pulsa aqui se abrira un modal para modificar el Atleta seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_r',
                popover: { title: 'Eliminar Atleta', description: 'Si pulsa aqui eliminara el Atleta seleccionado.', position: 'left' }
            },
            {
                element: '#cbt_sec',
                popover: { title: 'Generar Curriculum', description: 'Si pulsa aqui generara un curriculum del Atleta seleccionado.', position: 'left' }
            },
            {
                element: '#rowsPerPage',
                popover: { title: 'Registros Deseados', description: 'Aqui podra seleccionar la cantidad de registros que quiere que se muestren.', position: 'top' }
            },
            {
                element: '#botonera',
                popover: { title: 'Cambiar de Pagina', description: 'Botones para cambiar de página.', position: 'top' }
            },
            {
                element: '#cantidad',
                popover: { title: 'Cantidad', description: 'Aqui puedes ver la cantidad de representantes cargados.', position: 'top' }
            },
        ];

        const driver = iniciarTourConPasos(pasos);
        driver.start();
    });

});

function construirSelect(idSelect, datos, campoId, campo1, campo2 = null, campo3 = null) {
    var select = $('#' + idSelect);
    select.empty();
    select.append('<option value="" selected disabled>Seleccione una opción</option>');

    datos.forEach(dato => {
        let textoMostrar = "";
        let atributosExtra = ""; // Variable para guardar los límites de edad

        if (idSelect === 'moneda' && campo1 && campo2) {
            textoMostrar = `${dato[campo1]} ${dato[campo2]}`;
        }
        else if (idSelect === 'metodo' && campo1) {
            textoMostrar = `${dato[campo1]}`;
        }
        else if (idSelect === 'cuenta' && campo1 && campo2 && campo3) {
            textoMostrar = `${dato[campo1]}/${dato[campo2]} ${dato[campo3]}`;
        }
        else {
            textoMostrar = escapeHTML(String(dato[campo1]));
        }

        // Se agregan los atributosExtra a la etiqueta <option>
        var linea = `<option value="${dato[campoId]}">${textoMostrar}</option>`;
        select.append(linea);
    });
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
        beforeSend: function (request) {
            request.setRequestHeader("X-CSRF-TOKEN", token);
        },
        timeout: 10000,
        success: function (respuesta) {
            try {
                var lee = JSON.parse(respuesta);
                if (lee.accion == "consultar") {
                    crearConsulta(lee.datos);
                } else if (lee.accion == "consultarM") {
                    //muestraMensaje("success", 2000, "Registro Exitoso", 'Hola1');
                    construirSelect('moneda', lee.datos, 'id_moneda', 'simbolo', 'nombre');
                } else if (lee.accion == "consultarMP") {
                    //muestraMensaje("success", 2000, "Registro Exitoso", 'Hola2');
                    construirSelect('metodo', lee.datos, 'id_metodos', 'nombre');
                } else if (lee.accion == "consultarC") {
                    //muestraMensaje("success", 2000, "Registro Exitoso", 'Hola3');
                    construirSelect('cuenta', lee.datos, 'id_cobrar', 'concepto_nombre', 'atleta_nombre','atleta_apellido');
                } else if (lee.accion == "incluir") {
                    consultar();
                    limpia();
                    muestraMensaje("success", 2000, "Registro Exitoso", lee.mensaje);
                } else if (lee.accion == "eliminar") {
                    consultar();
                    muestraMensaje("success", 2000, "Retiro Exitoso", lee.mensaje);
                } else if (lee.accion == "modificar") {
                    consultar();
                    limpia();
                    cerrarModal();

                    muestraMensaje("success", 2000, "Modificacion Exitosa", lee.mensaje);
                } else if (lee.accion == "buscar") {
                    modificar(lee.datos);
                }
                else if (lee.accion == "error") {
                    muestraMensaje("error", 2000, "Error", lee.mensaje);
                }
            } catch (e) {
                alert("Error en JSON " + e.name);
            }
        },


        error: function (request, status, err) {

            if (status == "timeout") {
                muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
            } else {
                muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + request + status + err);
            }
        },
        complete: function () { },
    });
}
