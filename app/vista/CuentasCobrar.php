<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Cuentas por Cobrar</title>
</head>

<body data-tema="<?= _TEMA_ === 'oscuro' ? 'oscuro' : 'claro' ?>">
    <?php include('complementos/loader.php'); ?>
    <?php include('complementos/circle.php'); ?>
    <section class="contenedor">
        <?php include('complementos/nav_superior.php'); ?>
        <?php include('complementos/nav_lateral.php'); ?>
        <div class="contenido">
            <div class="contenido_modulo">
                <div class="contenedor_funciones">
                    <div class="contenedor_opciones">
                        <div class="contenedor_titulo">
                            <h2 class="titulo_pagina" id="titulo">Cuentas por Cobrar</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar cargo..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <button class="btn btn_azul" id="incluir">Nuevo Cargo</button>
                            <button class="btn btn_verde" id="generar">Generar Reporte</button>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                        </div>
                    </div>
                    <?php include('complementos/botonera.php'); ?>
                </div>
            </div>
        </div>
    </section>
    <section class="contenedor_modal" id="contenedor_modal">
        <div class="modal ocultar" id="modal">
            <div class="cabecera_modal">
                <h2 class="titulo_modal" id="titulo_modal"></h2>
                <a type="button" class="cerrar_modal" id="cerrar_modal">&times;</a>
            </div>
            <div class="contenido_modal">
                <form id="f" autocomplete="off">
                    <input type="hidden" id="id" name="id">
                    
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="id_atleta" id="id_atleta" class="formulario select">
                                    <!-- Options llenadas por AJAX -->
                                </select>
                                <label for="id_atleta" class="titulo_formulario">Atleta</label>
                                <span class="mensaje" id="id_atleta_span"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="id_concepto" id="id_concepto" class="formulario select">
                                    <!-- Options llenadas por AJAX -->
                                </select>
                                <label for="id_concepto" class="titulo_formulario">Concepto de Cobro</label>
                                <span class="mensaje" id="id_concepto_span"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="monto_total" name="monto_total" placeholder="Ej: 50.00">
                                <label for="monto_total" class="titulo_formulario">Monto Total</label>
                                <span class="mensaje" id="monto_total_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <!-- Este campo suele ser readonly porque se calcula automático al cobrar o pagar -->
                                <input type="text" class="formulario" id="monto_pendiente" name="monto_pendiente" readonly>
                                <label for="monto_pendiente" class="titulo_formulario">Monto Pendiente</label>
                                <span class="mensaje" id="monto_pendiente_spam"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="date" class="formulario" id="fecha_emision" name="fecha_emision" readonly>
                                <label for="fecha_emision" class="titulo_formulario">Fecha de Emisión</label>
                                <span class="mensaje" id="fecha_emision_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="estatus" id="estatus" class="formulario select" disabled>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Abonado">Abonado</option>
                                    <option value="Pagado">Pagado</option>
                                </select>
                                <label for="estatus" class="titulo_formulario">Estatus</label>
                                <span class="mensaje" id="estatus_span"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="colum">
                            <button type="button" class="btn btn_azul" id="proceso"></button>
                            <button type="button" class="btn btn_verde" id="limpiar">Limpiar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <script src="js/main.js"></script>
    <!-- Asegúrate de crear este archivo JS para manejar la lógica de esta vista específica -->
    <script src="js/cuentas_cobrar.js"></script>
</body>

</html>