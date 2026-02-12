
let modoVista = "tabla"; // por defecto tabla

$(document).ready(function () {
    $("#c_rows").prepend(`<input type="checkbox" id="switchVista" hidden>
                        <label for="switchVista" class="switch">
                            <i class="fi fi-br-list"></i>
                            <span class="knob"></span>
                            <i class="fi fi-br-border-all"></i>
                        </label>`);

    $("#switchVista").on("change", function () {
        modoVista = this.checked ? "cartas" : "tabla";
        consultar();
    });

    consultar();
});

function consultar() {
    let $contenedor = $("#tabla");
    $contenedor.empty();

    if (modoVista === "cartas") {
        $("#rowsPerPage").text('');
        $("#rowsPerPage").append('<option value="4" selected>4</option>');
        $("#rowsPerPage").append('<option value="8">8</option>');
        $("#rowsPerPage").append('<option value="12">12</option>');
        $("#rowsPerPage").append('<option value="20">20</option>');
        let htmlCartas = `
        <div class="contenedor_cartas">
            ${generarCarta(1)}
            ${generarCarta(2)}
            ${generarCarta(3)}
            ${generarCarta(4)}
            ${generarCarta(5)}
            ${generarCarta(6)}
        </div>`;
        $contenedor.append(htmlCartas);
        inicializarPaginadorCartas();
    } else {
        $("#rowsPerPage").text('');
        $("#rowsPerPage").append('<option value="5" selected>5</option>');
        $("#rowsPerPage").append('<option value="10">10</option>');
        $("#rowsPerPage").append('<option value="20">20</option>');
        let htmlTabla = `
        <table id="tablageneral">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Categoria</th>
                    <th>Medida</th>
                    <th>Precio Unitario</th>
                    <th>Descripción</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody id="resultadoconsulta">
                ${generarFila(1)}
                ${generarFila(2)}
                ${generarFila(3)}
                ${generarFila(4)}
                ${generarFila(5)}
                ${generarFila(6)}
            </tbody>
        </table>`;
        $contenedor.append(htmlTabla);
        inicializarPaginador(); // paginador para tabla
    }
}

// Plantilla de carta
function generarCarta(codigo) {
    return `
    <div class="carta">
        <img src="img/p.jpg" alt="Coca-Cola">
        <div class="carta-body">
            <h3>Coca-Cola</h3>
            <p><strong>Codigo:</strong> ${codigo}</p>
            <p><strong>Categoría:</strong> Bebidas</p>
            <p><strong>Medida:</strong> 500Ml</p>
            <p><strong>Precio:</strong> 2$</p>
            <p><strong>Descripción:</strong> Lata</p>
        </div>
        <div class="carta-footer">
            <button class="btn_t cbt_v"><i class="fi fi-sr-pencil"></i></button>
            <button class="btn_t cbt_r"><i class="fi fi-sr-trash-xmark"></i></button>
        </div>
    </div>`;
}

// Plantilla de fila
function generarFila(codigo) {
    return `
    <tr>
        <td>${codigo}</td>
        <td><img src="img/p.jpg" alt=""></td>
        <td>Coca-Cola</td>
        <td>Bebidas</td>
        <td>500Ml</td>
        <td>2$</td>
        <td>Lata</td>
        <td>
            <button class="btn_t cbt_v"><i class="fi fi-sr-pencil"></i></button>
            <button class="btn_t cbt_r"><i class="fi fi-sr-trash-xmark"></i></button>
        </td>
    </tr>`;
}
