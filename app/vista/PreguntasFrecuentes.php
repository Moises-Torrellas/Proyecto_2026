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
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva posición de juego?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para catalogar un nuevo rol en el campo, dirígete al módulo de <strong>Posiciones</strong> y haz clic en el botón verde <strong>"Nueva Posicion"</strong>. En la ventana emergente, ingresa el Nombre (ej. Delantero), su Abreviatura (ej. DC) y una breve Descripción opcional. Finalmente, presiona el botón verde <strong>"Registrar Posición"</strong>.</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva categoría?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir una nueva clasificación por edades, dirígete al módulo de <strong>Categorías</strong> y haz clic en el botón verde <strong>"Nueva Categoría"</strong>. En el formulario emergente, ingresa el Nombre de la categoría (ej. U-12) y establece la Edad Mínima y la Edad Máxima correspondientes. Finalmente, presiona el botón verde <strong>"Registrar Categoría"</strong>.</p>
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
                                        ¿Cómo registrar un nuevo torneo?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir un evento, dirígete al módulo de <strong>Torneos</strong> y haz clic en el botón verde <strong>"Nuevo Torneo"</strong>. Se abrirá una ventana donde deberás ingresar el Nombre del Torneo, seleccionar su Estatus, definir las Fechas de Inicio y Fin, y especificar la Ubicación. Finalmente, presiona el botón verde <strong>"Registrar Torneo"</strong>.</p>
                                    </div>
                                </div>
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo buscar y ver el estado de los torneos?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>En la pantalla principal de <strong>Torneos</strong>, encontrarás una lista con todas las competiciones. Cada tarjeta te muestra información rápida como las fechas, ubicación y el estatus actual (por ejemplo, "Finalizado"). Si necesitas encontrar uno en particular, puedes utilizar la barra de <strong>"Buscar torneo por nombre..."</strong> ubicada en la parte superior.</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo equipo?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para crear una nueva agrupación, dirígete al módulo de <strong>Equipos</strong> y haz clic en el botón verde <strong>"Nuevo Equipo"</strong>. En el formulario, ingresa el Nombre de Equipo y presiona el botón <strong>"Seleccionar Atletas"</strong> para ir añadiendo a los integrantes. Verás que se agregan a la tabla inferior. Una vez completado el roster, haz clic en el botón verde <strong>"Registrar Equipo"</strong>.</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva participación?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para registrar la participación de un equipo, dirígete al módulo de <strong>Participaciones</strong> y haz clic en el botón verde <strong>"Nueva Participacion"</strong>. En la ventana emergente, simplemente selecciona el Torneo correspondiente y el Equipo que deseas inscribir utilizando las listas desplegables. Finalmente, haz clic en el botón verde <strong>"Registrar Participacion"</strong>.</p>
                                    </div>
                                </div>
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo premio en el sistema?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir un galardón, dirígete al módulo de <strong>Premios</strong> y haz clic en el botón verde <strong>"Nuevo Premio"</strong>. En la ventana emergente, ingresa el Nombre del premio (ej. Primer Lugar) y selecciona el Tipo (ej. Grupal o Individual) en la lista desplegable. Para finalizar, haz clic en el botón verde <strong>"Registrar Premio"</strong>.</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo palmarés?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Ve al módulo de <strong>Palmarés</strong> y haz clic en el botón verde <strong>"Nuevo Palmarés"</strong>. El sistema te mostrará una ventana preguntando qué tipo de logro deseas registrar:</p>
                                        <ul style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li><strong>Individual:</strong> Selecciona el Torneo, el Premio y el Atleta correspondiente.</li>
                                            <li><strong>Grupal:</strong> Selecciona el Torneo, el Premio y el Equipo ganador.</li>
                                        </ul>
                                        <p>Al completar los datos, presiona el botón verde <strong>"Registrar Palmarés"</strong>.</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar las estadísticas de un atleta?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Dirígete al módulo de <strong>Estadísticas</strong> y haz clic en el botón verde <strong>"Nuevas Estadísticas"</strong>. En el formulario emergente, selecciona la Participación/Torneo y el Atleta. Luego, ingresa los valores correspondientes a su rendimiento: Goles, Asistencias, Penalizaciones, Goles en Contra, Partidos Jugados y Average. Para guardar los datos, haz clic en el botón verde <strong>"Registrar Estadísticas"</strong>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="faq_section">
                            <h3 class="faq_category">Cobranzas</h3>
                            <div class="faq_accordion">
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo cargo o deuda a un atleta?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para registrar una nueva cuenta por cobrar, dirígete al módulo de <strong>Cargos</strong> y haz clic en el botón verde <strong>"Nuevo Cargo"</strong>. En la ventana emergente, selecciona el o los Atletas correspondientes, escoge el Concepto de Cobro, ingresa el Monto Total y la Fecha de Emisión. Por defecto, el estatus estará en "Pendiente". Finalmente, presiona el botón verde <strong>"Registrar Cargo"</strong>.</p>
                                    </div>
                                </div>
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un pago?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Dirígete a <strong>Cobranzas > Pagos</strong>. Busca los cargos pendientes del atleta e ingresa el monto, la fecha y el método de pago utilizado. Asegúrate de guardar el recibo emitido.</p>
                                    </div>
                                </div>
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo método de pago?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir una nueva forma de pago, dirígete al módulo de <strong>Métodos de Pago</strong> y haz clic en el botón verde <strong>"Nuevo Método de Pago"</strong>. En la ventana emergente, ingresa el Nombre (ej. Transferencia, Efectivo, Pago Móvil) y selecciona en la lista desplegable si este método exige un número de Referencia al momento de cobrar ("Sí" o "No"). Por último, presiona el botón verde <strong>"Registrar Método"</strong>.</p>
                                    </div>
                                </div>
                                
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo concepto de cobro?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir un nuevo tipo de cobro, dirígete al módulo de <strong>Conceptos</strong> y haz clic en el botón verde <strong>"Nuevo Concepto"</strong>. En la ventana emergente, ingresa el Nombre (ej. Mensualidad), el Monto estándar, la Frecuencia (Mensual, Anual, Libre, etc.) y los Días Límite de Pago permitidos. Finalmente, haz clic en el botón verde <strong>"Registrar Concepto"</strong>.</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva moneda en el sistema?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir una nueva divisa, dirígete al módulo de <strong>Monedas</strong> y haz clic en el botón verde <strong>"Nueva Moneda"</strong>. En la ventana emergente, ingresa el Nombre (ej. Dólar), su Abreviatura (ej. USD) y el Símbolo correspondiente (ej. $). Finalmente, presiona el botón verde <strong>"Registrar Moneda"</strong>.</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo sincronizar la tasa de cambio automáticamente?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para obtener la tasa del día de forma automática, dirígete al módulo de <strong>Tasas de Cambio</strong> y haz clic en el botón oscuro <strong>"Sincronizar Monto"</strong>. En la ventana emergente, selecciona la Moneda a Convertir (ej. USD) y presiona el botón verde <strong>"Sincronizar"</strong>. El sistema registrará el valor con el tipo "Automática".</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo actualizar la tasa de cambio de forma manual?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Si necesitas establecer una tasa personalizada o el sistema automático no está disponible, haz clic en el botón verde <strong>"Actualizar Monto Manual"</strong>. Selecciona la Moneda a Convertir, ingresa el valor exacto en el campo "Tasa en Bolívares" y haz clic en <strong>"Guardar Tasa"</strong>. Este registro quedará marcado con el tipo "Manual".</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="faq_section">
                            <h3 class="faq_category">Inventario</h3>
                            <div class="faq_accordion">
                                
                                <!-- Preguntas de Inventario -->
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo artículo en el inventario?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir un artículo, dirígete al módulo de <strong>Inventario de Artículos</strong> y haz clic en el botón verde <strong>"Nuevo Artículo"</strong>. Se abrirá una ventana donde deberás seleccionar el tipo de artículo desde el catálogo y especificar su condición o estado físico actual. Finalmente, presiona <strong>"Guardar"</strong>.</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo generar un reporte del inventario?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Ingresa a <strong>Inventario de Artículos</strong> y haz clic en el botón oscuro <strong>"Generar Reporte"</strong>. En la ventana emergente, puedes filtrar los resultados seleccionando un artículo específico del catálogo y su estado físico. Al terminar, haz clic en el botón verde <strong>"Generar Reporte"</strong> para visualizar el documento.</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo artículo en el catálogo?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir un nuevo tipo de artículo, dirígete al módulo de <strong>Catálogo</strong> y haz clic en el botón verde <strong>"Nuevo Artículo"</strong>. Se abrirá un formulario donde debes ingresar el Nombre del Artículo, seleccionar su Categoría, definir el Stock Mínimo y, opcionalmente, indicar la Talla. Al finalizar, presiona el botón verde <strong>"Registrar Artículo"</strong>.</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva categoría?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir una nueva categoría, dirígete al módulo de <strong>Categorías de Catálogo</strong> y haz clic en el botón verde <strong>"Nueva Categoría"</strong>. Aparecerá una ventana donde debes ingresar el Nombre de la categoría y una breve Descripción. Para finalizar, presiona el botón verde <strong>"Registrar Categoría"</strong>.</p>
                                    </div>
                                </div>
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo estado físico?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir un nuevo estado físico, dirígete al módulo de <strong>Estado Físico</strong> y haz clic en el botón verde <strong>"Nuevo Estado Físico"</strong>. Aparecerá un formulario donde debes ingresar el Nombre del estado y seleccionar su Nivel de Condición en la lista desplegable. Para finalizar, presiona el botón verde <strong>"Registrar Estado"</strong>.</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva asignación de equipo?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para prestar o asignar un equipo, dirígete al módulo de <strong>Asignaciones</strong> y haz clic en el botón verde <strong>"Nueva Asignación"</strong>. En la ventana emergente, selecciona el Atleta, escoge el Artículo del Inventario que se le entregará y define la Fecha de Asignación. Finalmente, presiona el botón verde <strong>"Registrar Asignación"</strong>.</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva devolución de equipo?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para registrar el retorno de un equipo prestado, ve al módulo de <strong>Devoluciones</strong> y haz clic en el botón verde <strong>"Nueva Devolución"</strong>. En el formulario, selecciona la Asignación correspondiente al préstamo, indica el Estado Físico en el que se devuelve el artículo y la Fecha de Devolución. También puedes añadir una Observación opcional (por ejemplo, "El equipo tiene un raspón"). Al terminar, presiona el botón verde <strong>"Confirmar"</strong>.</p>
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