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
                            <h2 class="titulo_pagina" id="titulo">Centro de Ayudas</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar pregunta..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <button class="btn btn_azul" id="incluir">Abrir Manual de Usuario</button>
                        </div>
                    </div>

                    <!-- Contenedor del acordeón -->
                    <div class="contenedor_panelfaq">

                        <!-- ═══════════════════════════════════════════ -->
                        <!-- SECCIÓN: CUENTA Y ACCESO                  -->
                        <!-- ═══════════════════════════════════════════ -->
                        <div class="faq_section">
                            <h3 class="faq_category">Cuenta y Acceso</h3>
                            <div class="faq_accordion">

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo recuperar mi contraseña si la olvidé?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Si olvidaste tu contraseña, puedes restablecerla fácilmente desde la pantalla de inicio de sesión. Sigue estos pasos:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>En la pantalla de <strong>Inicio de Sesión</strong>, haz clic en el enlace <strong>"¿Has olvidado tu contraseña?"</strong> ubicado debajo del botón de ingresar.</li>
                                            <li>Se abrirá la página de <strong>Recuperar Contraseña</strong>. Ingresa tu número de <strong>Cédula</strong> en el campo correspondiente.</li>
                                            <li>Presiona el botón <strong>"Enviar Código"</strong>. El sistema enviará un código de verificación de 6 dígitos a tu correo electrónico registrado.</li>
                                            <li>Ingresa el <strong>código de 6 dígitos</strong> que recibiste en tu correo y presiona <strong>"Comprobar Código"</strong>. Si no lo recibiste, puedes hacer clic en <strong>"Reenviar Código"</strong> una vez transcurrido el tiempo de espera (30 segundos).</li>
                                            <li>Una vez verificado el código, se mostrará el formulario para crear una nueva contraseña. Ingresa tu <strong>nueva contraseña</strong> y confírmala en el campo <strong>"Repetir Contraseña"</strong>.</li>
                                            <li>Presiona el botón <strong>"Cambiar Contraseña"</strong> para completar el proceso.</li>
                                        </ol>
                                        <p><strong>Requisitos de la contraseña:</strong> debe tener entre 8 y 20 caracteres, incluir al menos una mayúscula, una minúscula, un número y un símbolo especial (ej. !@#$%^&*).</p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo editar mi información personal?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para actualizar tus datos personales, dirígete al módulo de <strong>Editar Perfil</strong> desde el menú del sistema. La página se divide en tres secciones independientes:</p>
                                        <p style="margin-top: 12px;"><strong>Información Personal:</strong></p>
                                        <ol style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Puedes cambiar tu <strong>foto de perfil</strong> haciendo clic en el botón <strong>"Seleccionar Foto"</strong> y eligiendo una imagen desde tu dispositivo.</li>
                                            <li>Modifica los campos de <strong>Cédula</strong>, <strong>Nombre</strong> y <strong>Apellido</strong> según necesites.</li>
                                            <li>Presiona el botón <strong>"Guardar Personal"</strong> para aplicar los cambios. Tu sesión se actualizará inmediatamente.</li>
                                        </ol>
                                        <p style="margin-top: 12px;"><strong>Información de Contacto:</strong></p>
                                        <ol style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Actualiza tu número de <strong>Teléfono</strong> y tu dirección de <strong>Correo</strong> electrónico.</li>
                                            <li>Presiona el botón <strong>"Guardar Contacto"</strong> para confirmar los cambios.</li>
                                        </ol>
                                        <p style="margin-top: 12px;"><strong>Seguridad (Cambiar Contraseña):</strong></p>
                                        <ol style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Ingresa tu <strong>Nueva Contraseña</strong> en el primer campo.</li>
                                            <li>Confírmala escribiéndola nuevamente en el campo <strong>"Confirmar Contraseña"</strong>. Ambas deben coincidir.</li>
                                            <li>Presiona el botón <strong>"Actualizar Clave"</strong> para guardar tu nueva contraseña.</li>
                                        </ol>
                                        <p><em>Nota: Puedes hacer clic en el ícono del ojo junto a los campos de contraseña para mostrar u ocultar el texto.</em></p>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════ -->
                        <!-- SECCIÓN: ADMINISTRACIÓN                   -->
                        <!-- ═══════════════════════════════════════════ -->
                        <div class="faq_section">
                            <h3 class="faq_category">Administración</h3>
                            <div class="faq_accordion">
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un atleta?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para registrar un nuevo atleta en el sistema, sigue estos pasos:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>En el menú lateral, dirígete a <strong>Administración > Atletas</strong>.</li>
                                            <li>Haz clic en el botón <strong>"Nuevo Atleta"</strong> ubicado en la parte superior.</li>
                                            <li>Se abrirá un formulario donde deberás completar los datos del atleta: <strong>Cédula</strong>, <strong>Nombre</strong>, <strong>Apellido</strong>, <strong>Fecha de Nacimiento</strong>, <strong>Género</strong>, <strong>Posición</strong>, <strong>Categoría</strong>, entre otros.</li>
                                            <li>Opcionalmente, puedes subir una <strong>foto del atleta</strong>.</li>
                                            <li>Al finalizar, presiona el botón <strong>"Registrar Atleta"</strong> para guardar el registro.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo agregar un representante legal?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para vincular un representante legal a uno o más atletas:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>En el menú lateral, dirígete a <strong>Administración > Representantes</strong>.</li>
                                            <li>Haz clic en el botón <strong>"Nuevo Representante"</strong>.</li>
                                            <li>Completa los datos del representante: <strong>Cédula</strong>, <strong>Nombre</strong>, <strong>Apellido</strong>, <strong>Teléfono</strong>, <strong>Correo</strong> y el <strong>Parentesco</strong> con el atleta.</li>
                                            <li>Selecciona el o los <strong>Atletas</strong> que estará representando.</li>
                                            <li>Presiona <strong>"Registrar Representante"</strong> para guardar el vínculo.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva posición de juego?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para catalogar un nuevo rol en el campo:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Posiciones</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nueva Posición"</strong>.</li>
                                            <li>En la ventana emergente, ingresa el <strong>Nombre</strong> (ej. Delantero), su <strong>Abreviatura</strong> (ej. DC) y una breve <strong>Descripción</strong> opcional.</li>
                                            <li>Presiona el botón verde <strong>"Registrar Posición"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva categoría de edad?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir una nueva clasificación por edades:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Categorías</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nueva Categoría"</strong>.</li>
                                            <li>En el formulario emergente, ingresa el <strong>Nombre</strong> de la categoría (ej. U-12) y establece la <strong>Edad Mínima</strong> y la <strong>Edad Máxima</strong> correspondientes.</li>
                                            <li>Presiona el botón verde <strong>"Registrar Categoría"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════ -->
                        <!-- SECCIÓN: COMPETENCIAS E HISTORIAL          -->
                        <!-- ═══════════════════════════════════════════ -->
                        <div class="faq_section">
                            <h3 class="faq_category">Competencias e Historial Deportivo</h3>
                            <div class="faq_accordion">
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo torneo?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir una nueva competición al sistema:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Torneos</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nuevo Torneo"</strong>.</li>
                                            <li>En la ventana emergente, ingresa el <strong>Nombre del Torneo</strong>, selecciona su <strong>Estatus</strong> (ej. En Curso, Finalizado), define las <strong>Fechas de Inicio y Fin</strong>, y especifica la <strong>Ubicación</strong>.</li>
                                            <li>Presiona el botón verde <strong>"Registrar Torneo"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo buscar y ver el estado de los torneos?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para consultar la información de los torneos registrados:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Torneos</strong> en el menú lateral.</li>
                                            <li>En la pantalla principal verás una <strong>lista con todas las competiciones</strong>. Cada tarjeta muestra las fechas, ubicación y el estatus actual (ej. "Finalizado", "En Curso").</li>
                                            <li>Si necesitas encontrar uno en particular, utiliza la <strong>barra de búsqueda</strong> ubicada en la parte superior e ingresa el nombre del torneo.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo equipo?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para crear una nueva agrupación de atletas:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Equipos</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nuevo Equipo"</strong>.</li>
                                            <li>Ingresa el <strong>Nombre del Equipo</strong>.</li>
                                            <li>Presiona el botón <strong>"Seleccionar Atletas"</strong> para ir añadiendo integrantes al equipo. Verás que cada atleta seleccionado se agrega a la tabla inferior.</li>
                                            <li>Una vez completado el roster, haz clic en el botón verde <strong>"Registrar Equipo"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva participación?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para inscribir un equipo en un torneo:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Participaciones</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nueva Participación"</strong>.</li>
                                            <li>En la ventana emergente, selecciona el <strong>Torneo</strong> correspondiente en la lista desplegable.</li>
                                            <li>Selecciona el <strong>Equipo</strong> que deseas inscribir.</li>
                                            <li>Haz clic en el botón verde <strong>"Registrar Participación"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo ver el historial de participaciones de un atleta?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para consultar las competiciones en las que un atleta ha participado:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Participaciones</strong> en el menú lateral.</li>
                                            <li>Utiliza la <strong>barra de búsqueda</strong> para encontrar al atleta por su nombre o cédula.</li>
                                            <li>Podrás ver la lista de torneos en los que ha participado, junto con los equipos y resultados asociados.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo premio?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir un nuevo tipo de galardón al sistema:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Premios</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nuevo Premio"</strong>.</li>
                                            <li>En la ventana emergente, ingresa el <strong>Nombre</strong> del premio (ej. Primer Lugar, Goleador) y selecciona el <strong>Tipo</strong> (Individual o Grupal) en la lista desplegable.</li>
                                            <li>Presiona el botón verde <strong>"Registrar Premio"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo palmarés?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para registrar un logro obtenido en una competición:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Palmarés</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nuevo Palmarés"</strong>.</li>
                                            <li>El sistema te mostrará una ventana preguntando qué tipo de logro deseas registrar:</li>
                                        </ol>
                                        <ul style="margin-top: 5px; margin-bottom: 10px; padding-left: 35px; color: #555;">
                                            <li><strong>Individual:</strong> Selecciona el Torneo, el Premio y el Atleta correspondiente.</li>
                                            <li><strong>Grupal:</strong> Selecciona el Torneo, el Premio y el Equipo ganador.</li>
                                        </ul>
                                        <ol start="4" style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Al completar los datos, presiona el botón verde <strong>"Registrar Palmarés"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar las estadísticas de un atleta?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para registrar el rendimiento deportivo de un atleta en un torneo:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Estadísticas</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nuevas Estadísticas"</strong>.</li>
                                            <li>En el formulario emergente, selecciona la <strong>Participación/Torneo</strong> y el <strong>Atleta</strong>.</li>
                                            <li>Ingresa los valores de rendimiento: <strong>Goles</strong>, <strong>Asistencias</strong>, <strong>Penalizaciones</strong>, <strong>Goles en Contra</strong>, <strong>Partidos Jugados</strong> y <strong>Average</strong>.</li>
                                            <li>Haz clic en el botón verde <strong>"Registrar Estadísticas"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════ -->
                        <!-- SECCIÓN: COBRANZAS                        -->
                        <!-- ═══════════════════════════════════════════ -->
                        <div class="faq_section">
                            <h3 class="faq_category">Cobranzas</h3>
                            <div class="faq_accordion">
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo cargo o deuda a un atleta?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para registrar una nueva cuenta por cobrar:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Cargos</strong> (Cuentas por Cobrar) en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nuevo Cargo"</strong>.</li>
                                            <li>En la ventana emergente, selecciona el o los <strong>Atletas</strong> correspondientes.</li>
                                            <li>Escoge el <strong>Concepto de Cobro</strong> (ej. Mensualidad, Inscripción).</li>
                                            <li>Ingresa el <strong>Monto Total</strong> y la <strong>Fecha de Emisión</strong>. Por defecto, el estatus estará en "Pendiente".</li>
                                            <li>Presiona el botón verde <strong>"Registrar Cargo"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un pago?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para registrar el pago de uno o varios cargos pendientes:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Pagos</strong> en el menú lateral (sección Cobranzas).</li>
                                            <li>Haz clic en el botón <strong>"Nuevo Pago"</strong>.</li>
                                            <li>En la ventana emergente, selecciona los <strong>Cargos a Pagar</strong> de la lista (puedes seleccionar múltiples cargos).</li>
                                            <li>Selecciona el <strong>Método de Pago</strong> que el atleta o representante utilizó (ej. Transferencia, Efectivo, Pago Móvil).</li>
                                            <li>Escoge la <strong>Moneda</strong> en la que se realiza el pago y la <strong>Tasa de Cambio</strong> si aplica.</li>
                                            <li>Ingresa el <strong>Monto del Pago</strong>. El sistema calculará automáticamente el monto al cambio.</li>
                                            <li>Establece la <strong>Fecha del Pago</strong> y, si el método lo requiere, ingresa el número de <strong>Referencia</strong>.</li>
                                            <li>Presiona el botón <strong>"Registrar Pago"</strong> para confirmar la transacción.</li>
                                        </ol>
                                        <p><em>Nota: Si el pago genera un vuelto pendiente, podrás registrarlo posteriormente usando el botón verde de "Registrar Vuelto" que aparecerá junto al pago.</em></p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo método de pago?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir una nueva forma de pago al sistema:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Métodos de Pago</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nuevo Método de Pago"</strong>.</li>
                                            <li>En la ventana emergente, ingresa el <strong>Nombre</strong> (ej. Transferencia, Efectivo, Pago Móvil).</li>
                                            <li>Selecciona en la lista desplegable si este método <strong>exige un número de Referencia</strong> al momento de cobrar ("Sí" o "No").</li>
                                            <li>Presiona el botón verde <strong>"Registrar Método"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo concepto de cobro?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir un nuevo tipo de cobro al sistema:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Conceptos</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nuevo Concepto"</strong>.</li>
                                            <li>En la ventana emergente, ingresa el <strong>Nombre</strong> (ej. Mensualidad), el <strong>Monto</strong> estándar, la <strong>Frecuencia</strong> (Mensual, Anual, Libre, etc.) y los <strong>Días Límite de Pago</strong> permitidos.</li>
                                            <li>Haz clic en el botón verde <strong>"Registrar Concepto"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva moneda en el sistema?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir una nueva divisa:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Monedas</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nueva Moneda"</strong>.</li>
                                            <li>En la ventana emergente, ingresa el <strong>Nombre</strong> (ej. Dólar), su <strong>Abreviatura</strong> (ej. USD) y el <strong>Símbolo</strong> correspondiente (ej. $).</li>
                                            <li>Presiona el botón verde <strong>"Registrar Moneda"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo sincronizar la tasa de cambio automáticamente?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para obtener la tasa del día de forma automática:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Tasas de Cambio</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón oscuro <strong>"Sincronizar Monto"</strong>.</li>
                                            <li>En la ventana emergente, selecciona la <strong>Moneda a Convertir</strong> (ej. USD).</li>
                                            <li>Presiona el botón verde <strong>"Sincronizar"</strong>. El sistema consultará la tasa actualizada y la registrará con el tipo "Automática".</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo actualizar la tasa de cambio de forma manual?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Si necesitas establecer una tasa personalizada o el sistema automático no está disponible:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Tasas de Cambio</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Actualizar Monto Manual"</strong>.</li>
                                            <li>Selecciona la <strong>Moneda a Convertir</strong>.</li>
                                            <li>Ingresa el valor exacto en el campo <strong>"Tasa en Bolívares"</strong>.</li>
                                            <li>Haz clic en <strong>"Guardar Tasa"</strong>. El registro quedará marcado con el tipo "Manual".</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════ -->
                        <!-- SECCIÓN: INVENTARIO                       -->
                        <!-- ═══════════════════════════════════════════ -->
                        <div class="faq_section">
                            <h3 class="faq_category">Inventario</h3>
                            <div class="faq_accordion">
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo artículo en el inventario?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir un artículo físico al inventario:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Inventario de Artículos</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nuevo Artículo"</strong>.</li>
                                            <li>En la ventana emergente, selecciona el <strong>tipo de artículo</strong> desde el catálogo.</li>
                                            <li>Especifica su <strong>condición</strong> o estado físico actual.</li>
                                            <li>Presiona <strong>"Guardar"</strong> para registrar el artículo.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo generar un reporte del inventario?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para generar un reporte del estado actual del inventario:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Inventario de Artículos</strong>.</li>
                                            <li>Haz clic en el botón oscuro <strong>"Generar Reporte"</strong>.</li>
                                            <li>En la ventana emergente, puedes filtrar los resultados seleccionando un <strong>artículo específico</strong> del catálogo y su <strong>estado físico</strong>.</li>
                                            <li>Haz clic en el botón verde <strong>"Generar Reporte"</strong> para visualizar el documento en formato PDF.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo artículo en el catálogo?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir un nuevo tipo de artículo al catálogo de referencia:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Catálogo</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nuevo Artículo"</strong>.</li>
                                            <li>En el formulario, ingresa el <strong>Nombre del Artículo</strong>, selecciona su <strong>Categoría</strong>, define el <strong>Stock Mínimo</strong> y, opcionalmente, indica la <strong>Talla</strong>.</li>
                                            <li>Presiona el botón verde <strong>"Registrar Artículo"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una categoría del catálogo?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir una nueva categoría de artículos al catálogo:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Categorías de Catálogo</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nueva Categoría"</strong>.</li>
                                            <li>En la ventana emergente, ingresa el <strong>Nombre</strong> de la categoría y una breve <strong>Descripción</strong>.</li>
                                            <li>Presiona el botón verde <strong>"Registrar Categoría"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo estado físico?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para añadir un nuevo estado de condición para los artículos:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Estado Físico</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nuevo Estado Físico"</strong>.</li>
                                            <li>En el formulario, ingresa el <strong>Nombre</strong> del estado y selecciona su <strong>Nivel de Condición</strong> en la lista desplegable.</li>
                                            <li>Presiona el botón verde <strong>"Registrar Estado"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva asignación de equipo?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para prestar o entregar un implemento a un atleta:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Asignaciones</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nueva Asignación"</strong>.</li>
                                            <li>En la ventana emergente, selecciona el <strong>Atleta</strong> que recibirá el artículo.</li>
                                            <li>Escoge el <strong>Artículo del Inventario</strong> que se le entregará.</li>
                                            <li>Define la <strong>Fecha de Asignación</strong>.</li>
                                            <li>Presiona el botón verde <strong>"Registrar Asignación"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar una nueva devolución de equipo?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para registrar el retorno de un implemento prestado:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Devoluciones</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón verde <strong>"Nueva Devolución"</strong>.</li>
                                            <li>Selecciona la <strong>Asignación</strong> correspondiente al préstamo que se está devolviendo.</li>
                                            <li>Indica el <strong>Estado Físico</strong> en el que se devuelve el artículo (ej. Buen Estado, Desgaste Medio).</li>
                                            <li>Establece la <strong>Fecha de Devolución</strong>.</li>
                                            <li>Opcionalmente, añade una <strong>Observación</strong> (ej. "El equipo tiene un raspón").</li>
                                            <li>Presiona el botón verde <strong>"Confirmar"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════ -->
                        <!-- SECCIÓN: SEGURIDAD Y CONTROL              -->
                        <!-- ═══════════════════════════════════════════ -->
                        <div class="faq_section">
                            <h3 class="faq_category">Seguridad y Control</h3>
                            <div class="faq_accordion">

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo usuario en el sistema?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para crear una nueva cuenta de usuario:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Usuarios</strong> en el menú lateral (sección Seguridad).</li>
                                            <li>Haz clic en el botón <strong>"Nuevo Usuario"</strong>.</li>
                                            <li>En el formulario emergente, completa los campos: <strong>Cédula</strong>, <strong>Nombre</strong>, <strong>Apellido</strong>, <strong>Teléfono</strong>, <strong>Correo</strong> y <strong>Contraseña</strong>.</li>
                                            <li>Selecciona el <strong>Rol</strong> que se le asignará al usuario (ej. Administrador, Entrenador).</li>
                                            <li>Opcionalmente, sube una <strong>foto de perfil</strong> haciendo clic en "Seleccionar Foto".</li>
                                            <li>Presiona el botón <strong>"Registrar Usuario"</strong>.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo modificar o eliminar un usuario?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para gestionar los usuarios existentes:</p>
                                        <p style="margin-top: 12px;"><strong>Modificar un usuario:</strong></p>
                                        <ol style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>En el módulo de <strong>Usuarios</strong>, localiza al usuario que deseas editar en la lista.</li>
                                            <li>Haz clic en el botón verde con el ícono de <strong>lápiz</strong>.</li>
                                            <li>Se abrirá el formulario con los datos actuales del usuario. Realiza los cambios necesarios.</li>
                                            <li>Presiona <strong>"Modificar Usuario"</strong> para guardar.</li>
                                        </ol>
                                        <p style="margin-top: 12px;"><strong>Eliminar un usuario:</strong></p>
                                        <ol style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>En el módulo de <strong>Usuarios</strong>, localiza al usuario en la lista.</li>
                                            <li>Haz clic en el botón rojo con el ícono de <strong>papelera</strong>.</li>
                                            <li>Confirma la acción en la ventana de confirmación que aparecerá.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo bloquear o desbloquear un usuario?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para cambiar el estado de acceso de un usuario:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>En el módulo de <strong>Usuarios</strong>, localiza al usuario en la lista.</li>
                                            <li>Observa el botón con ícono de <strong>candado</strong> junto al usuario:</li>
                                        </ol>
                                        <ul style="margin-top: 5px; margin-bottom: 10px; padding-left: 35px; color: #555;">
                                            <li><strong>Candado abierto (verde):</strong> El usuario está activo. Al hacer clic se bloqueará su acceso.</li>
                                            <li><strong>Candado cerrado (amarillo):</strong> El usuario está bloqueado. Al hacer clic se desbloqueará.</li>
                                        </ul>
                                        <ol start="3" style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Haz clic en el botón del candado y confirma la acción. Un usuario bloqueado no podrá iniciar sesión.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo asignar permisos a un usuario específico?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para personalizar los permisos individuales de un usuario:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>En el módulo de <strong>Usuarios</strong>, localiza al usuario en la lista.</li>
                                            <li>Haz clic en el botón morado con el ícono de <strong>permisos de usuario</strong>.</li>
                                            <li>Se abrirá un panel con dos pestañas: <strong>"Asignados"</strong> (permisos que ya tiene) y <strong>"No Asignados"</strong> (permisos disponibles).</li>
                                            <li>Utiliza el <strong>buscador</strong> para filtrar permisos por nombre.</li>
                                            <li>Agrega o quita permisos según las necesidades del usuario.</li>
                                            <li>Los cambios se guardan al confirmar la acción.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo registrar un nuevo rol?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para crear un nuevo rol con permisos predefinidos:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Roles</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón <strong>"Nuevo Rol"</strong>.</li>
                                            <li>En el formulario emergente, ingresa el <strong>Nombre del Rol</strong> (ej. Entrenador, Secretario) y una <strong>Descripción</strong>.</li>
                                            <li>Presiona el botón <strong>"Registrar Rol"</strong>.</li>
                                        </ol>
                                        <p><em>Nota: Una vez creado el rol, puedes asignarle permisos haciendo clic en el botón morado de "Permisos" junto al rol registrado.</em></p>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo asignar permisos a un rol?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para definir qué acciones puede realizar un rol en el sistema:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>En el módulo de <strong>Roles</strong>, localiza el rol al que deseas asignar permisos.</li>
                                            <li>Haz clic en el botón morado con el ícono de <strong>permisos</strong>.</li>
                                            <li>Se abrirá un panel con dos pestañas:</li>
                                        </ol>
                                        <ul style="margin-top: 5px; margin-bottom: 10px; padding-left: 35px; color: #555;">
                                            <li><strong>Asignados:</strong> Muestra los permisos que ya tiene el rol. Puedes quitarlos si es necesario.</li>
                                            <li><strong>No Asignados:</strong> Muestra los permisos disponibles para agregar.</li>
                                        </ul>
                                        <ol start="4" style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Utiliza el <strong>buscador</strong> para filtrar permisos por nombre.</li>
                                            <li>Agrega o quita permisos según las necesidades del rol. Los usuarios con este rol heredarán estos permisos automáticamente.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo consultar y gestionar los permisos del sistema?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>El módulo de <strong>Permisos</strong> permite ver y administrar todas las acciones disponibles en el sistema:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Permisos</strong> en el menú lateral.</li>
                                            <li>Verás los permisos organizados por <strong>módulo</strong>. Haz clic en cualquier módulo para expandir y ver sus permisos.</li>
                                            <li>Cada permiso muestra su <strong>nombre</strong>, <strong>descripción</strong> y <strong>estado</strong> (Activo o Bloqueado).</li>
                                            <li>Para <strong>modificar</strong> un permiso, haz clic en el botón verde de lápiz.</li>
                                            <li>Para <strong>bloquear/desbloquear</strong> un permiso, haz clic en el botón del candado. Un permiso bloqueado no estará disponible para ningún usuario o rol.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo gestionar los módulos del sistema?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>El módulo de <strong>Módulos</strong> permite ver y editar la configuración de las secciones del sistema:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Módulos</strong> en el menú lateral.</li>
                                            <li>Verás la lista de todos los módulos registrados con su <strong>nombre</strong>, <strong>ícono</strong> y <strong>descripción</strong>.</li>
                                            <li>Para <strong>modificar</strong> un módulo, haz clic en el botón verde de lápiz. Podrás editar el <strong>Nombre</strong> y la <strong>Descripción</strong> del módulo.</li>
                                            <li>Presiona el botón para guardar los cambios.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo consultar la bitácora de actividades?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>La <strong>Bitácora</strong> registra automáticamente todas las acciones que los usuarios realizan en el sistema. Para consultarla:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Bitácora</strong> en el menú lateral.</li>
                                            <li>Verás un listado cronológico de las acciones realizadas, mostrando el <strong>módulo</strong>, el <strong>usuario</strong>, la <strong>acción</strong> realizada y la <strong>fecha/hora</strong>.</li>
                                            <li>Haz clic en cualquier registro para <strong>expandir los detalles</strong>, donde podrás ver la cédula del usuario, el entorno, y los <strong>datos previos vs. datos nuevos</strong> de cada cambio.</li>
                                            <li>Utiliza la <strong>barra de búsqueda</strong> para filtrar por usuario, módulo o acción.</li>
                                            <li>Si necesitas ver más registros, presiona el botón <strong>"Cargar +100"</strong> para cargar los siguientes 100 registros.</li>
                                        </ol>
                                        <p style="margin-top: 12px;"><strong>Generar Reporte de Bitácora:</strong></p>
                                        <ol style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Haz clic en el botón verde <strong>"Generar Reporte"</strong>.</li>
                                            <li>En la ventana emergente, puedes filtrar por <strong>Módulo</strong>, <strong>Usuario</strong>, <strong>Fecha Inicio</strong> y <strong>Fecha Fin</strong>.</li>
                                            <li>Presiona el botón para generar el reporte en formato PDF.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo crear y restaurar respaldos de la base de datos?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>El módulo de <strong>Mantenimiento BD</strong> (Respaldo) permite crear puntos de restauración de la base de datos:</p>
                                        <p style="margin-top: 12px;"><strong>Crear un punto de restauración:</strong></p>
                                        <ol style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Mantenimiento BD</strong> en el menú lateral.</li>
                                            <li>Haz clic en el botón <strong>"Crear Punto de Restauración"</strong>.</li>
                                            <li>El sistema generará automáticamente un respaldo completo de la base de datos. Verás el nuevo registro en la lista con su <strong>nombre</strong>, <strong>fecha de creación</strong>, <strong>creador</strong> y <strong>peso</strong> del archivo.</li>
                                        </ol>
                                        <p style="margin-top: 12px;"><strong>Restaurar un respaldo:</strong></p>
                                        <ol style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Localiza el respaldo que deseas restaurar en la lista.</li>
                                            <li>Haz clic en el botón verde con el ícono de <strong>reloj</strong> para restaurar esa versión.</li>
                                            <li>Confirma la acción. <strong>Advertencia:</strong> Esta operación reemplazará los datos actuales con los del respaldo seleccionado.</li>
                                        </ol>
                                        <p style="margin-top: 12px;"><strong>Eliminar un respaldo:</strong></p>
                                        <ol style="margin-top: 5px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Haz clic en el botón rojo con el ícono de <strong>papelera</strong> junto al respaldo que deseas eliminar.</li>
                                            <li>Confirma la eliminación del archivo de respaldo.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════ -->
                        <!-- SECCIÓN: REPORTES ESTADÍSTICOS            -->
                        <!-- ═══════════════════════════════════════════ -->
                        <div class="faq_section">
                            <h3 class="faq_category">Reportes Estadísticos</h3>
                            <div class="faq_accordion">
                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo generar un reporte de atletas por categoría?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para visualizar gráficamente la distribución de atletas:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>Dirígete al módulo de <strong>Reportes Estadísticos</strong> en el menú lateral.</li>
                                            <li>Localiza la tarjeta <strong>"Atletas por Categorías"</strong>.</li>
                                            <li>Selecciona el tipo de gráfico que prefieras: <strong>Barras</strong> o <strong>Dona</strong>.</li>
                                            <li>Filtra los resultados por <strong>Categoría</strong> y/o <strong>Género</strong> (Masculino, Femenino o Todos).</li>
                                            <li>Marca o desmarca la opción <strong>"Incluir Atletas Retirados"</strong> según necesites.</li>
                                            <li>Presiona <strong>"Generar Reporte"</strong> para visualizar el gráfico con los datos.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo generar un reporte de efectividad de recaudación?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para analizar la recaudación financiera del sistema:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>En el módulo de <strong>Reportes Estadísticos</strong>, localiza la tarjeta <strong>"Efectividad de Recaudación"</strong>.</li>
                                            <li>Selecciona el tipo de gráfico: <strong>Barras</strong> o <strong>Líneas</strong>.</li>
                                            <li>Filtra por <strong>Moneda</strong> y/o <strong>Concepto de Cargo</strong>.</li>
                                            <li>Define el rango de fechas con los campos <strong>"Desde"</strong> y <strong>"Hasta"</strong>.</li>
                                            <li>Presiona <strong>"Generar Reporte"</strong> para ver el gráfico de recaudación.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo generar un reporte de flujo de implementos asignados?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para analizar el estado y flujo de los implementos del inventario:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>En el módulo de <strong>Reportes Estadísticos</strong>, localiza la tarjeta <strong>"Flujo y Estado de Implementos Asignados"</strong>.</li>
                                            <li>Selecciona el tipo de gráfico: <strong>Barras Horizontal</strong> o <strong>Pastel</strong>.</li>
                                            <li>Filtra por <strong>Categoría del Catálogo</strong> y/o <strong>Estado Físico</strong> de devolución.</li>
                                            <li>Define el rango de fechas de asignación con los campos <strong>"Asignado Desde"</strong> y <strong>"Asignado Hasta"</strong>.</li>
                                            <li>Presiona <strong>"Generar Reporte"</strong> para visualizar los datos.</li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="faq_item">
                                    <button class="faq_question">
                                        ¿Cómo generar un reporte de rendimiento ofensivo por atleta?
                                        <i class="fi fi-rr-angle-down"></i>
                                    </button>
                                    <div class="faq_answer">
                                        <p>Para analizar el rendimiento deportivo de los atletas:</p>
                                        <ol style="margin-top: 10px; margin-bottom: 10px; padding-left: 20px; color: #555;">
                                            <li>En el módulo de <strong>Reportes Estadísticos</strong>, localiza la tarjeta <strong>"Rendimiento Ofensivo por Atletas"</strong>.</li>
                                            <li>Selecciona el tipo de gráfico: <strong>Barras</strong> o <strong>Líneas</strong>.</li>
                                            <li>Filtra por <strong>Atleta</strong> específico y/o <strong>Torneo</strong> (puedes seleccionar "Todos los Torneos").</li>
                                            <li>Presiona <strong>"Generar Reporte"</strong> para ver el gráfico comparativo de goles, asistencias y demás métricas.</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mensaje cuando no hay resultados de búsqueda -->
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