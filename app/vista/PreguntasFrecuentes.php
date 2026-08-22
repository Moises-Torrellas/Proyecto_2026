<!DOCTYPE html>
<html lang="es">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Preguntas Frecuentes</title>
    <!-- Agregamos los estilos específicos para Preguntas Frecuentes -->
    <link rel="stylesheet" href="css/preguntas.css">
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
                            <h2 class="titulo_pagina" id="titulo">Preguntas Frecuentes</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar pregunta..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                    </div>

                    <!-- Contenedor del acordeón -->
                    <div class="contenedor_panelfaq">
                        <div class="faq_section">
                            <h3 class="faq_category">Administración</h3>
                            <div class="faq_accordion">
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un atleta?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para registrar un atleta debes ir al módulo de <strong>Administración > Atletas</strong> en el menú lateral. Luego haz clic en el botón de agregar (usualmente un '+') y llena el formulario con los datos personales, de contacto y deportivos del atleta. Al finalizar, haz clic en "Guardar".</p>
                                    </div>
                                </div>
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo agregar un representante legal?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>En el menú lateral, dirígete a <strong>Administración > Representantes</strong>. Podrás añadir un nuevo representante vinculándolo a uno o varios atletas mediante su cédula.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="faq_section">
                            <h3 class="faq_category">Competencias e Historial Deportivo</h3>
                            <div class="faq_accordion">
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo ver el historial de inscripción de un atleta?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para ver el historial, ve a <strong>Competencias > Participaciones</strong> o al apartado <strong>Historial Deportivo</strong> y busca al atleta por su cédula o nombre. Podrás ver en qué torneos ha participado y sus resultados.</p>
                                    </div>
                                </div>
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un torneo?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Ingresa a <strong>Competencias > Torneos</strong>. Registra los detalles del torneo, fechas de inicio y fin, y categorías participantes.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="faq_section">
                            <h3 class="faq_category">Cobranzas</h3>
                            <div class="faq_accordion">
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un pago?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Dirígete a <strong>Cobranzas > Pagos</strong>. Busca los cargos pendientes del atleta e ingresa el monto, la fecha y el método de pago utilizado. Asegúrate de guardar el recibo emitido.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Agrega más secciones según sea necesario -->
                        <div class="faq_no_results" style="display: none;">
                            <p>No se encontraron resultados para tu búsqueda.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Scripts de la página -->
    <script src="js/main.js"></script>
    <script src="js/preguntasFrecuentes.js"></script>
</body>

</html>
