<?php include_once(__DIR__ . '/cookie.php'); ?>
<div class="nav_superior">
    <div class="contenedor_superior">
        <div class="contenedor_logo">
            <img src="img/logo.png" class="logo">
            <h2 class="nombre_negocio">CANNIBALS LARA</h2>
        </div>

        <div class="contenedor_usuario">
            <div class="botones_usuario">
                <a type="button" class="boton_usuario asistente" id="asistente" data-tippy-content="Sydney"><i class="icon_boton_usuario" data-lucide="bot-message-square"></i></a>
                <a type="button" class="boton_usuario" id="ayuda" data-tippy-content="Ayuda"><i class="icon_boton_usuario" data-lucide="circle-question-mark"></i></a>
                <a type="button" class="boton_usuario" id="noti" data-tippy-content="Notificaciones"><i class="icon_boton_usuario" data-lucide="bell"></i></a>

                <div class="contenedor_notificaciones ocultar" id="contenedor_notificaciones">
                    <div class="encabezado_notificaciones">
                        <h1>Notificaciones</h1>
                        <i data-lucide="bell" class="icon_noti_titulo"></i>
                    </div>

                    <ul class="lista_noti">
                        <li class="item_noti">
                            <div class="noti_icono_estado">
                                <i data-lucide="info" class="icon_noti_info"></i>
                            </div>
                            <div class="noti_contenido">
                                <p class="noti_mensaje">Se ha asignado un nuevo equipo talla 42.</p>
                                <span class="noti_tiempo">Hace 5 min</span>
                            </div>
                        </li>

                        <li class="item_noti">
                            <div class="noti_icono_estado">
                                <i data-lucide="check-circle" class="icon_noti_success"></i>
                            </div>
                            <div class="noti_contenido">
                                <p class="noti_mensaje">Registro de atleta completado con éxito.</p>
                                <span class="noti_tiempo">Hace 1 hora</span>
                            </div>
                        </li>
                    </ul>

                    <div class="ver_todas_notificaciones">
                        <a href="#">Ver todas las notificaciones</a>
                    </div>
                </div>
            </div>
            <div class="info_usuario" id="info_usuario">
                <i data-lucide="circle-user" class="img_usuario"></i>
                <div class="contenedor_nombre">
                    <h3 class="nombre_usuario"><?php echo $_SESSION['nombre'] . ' ' . $_SESSION['apellido']; ?></h3>
                    <h4 class="tipo_usuario"><?php echo $_SESSION['rol']; ?></h4>
                </div>
                <i data-lucide="chevron-down" class="flecha_usuario" id="flecha"></i>
            </div>
            <div class="menu_superior ocultar" id="menu_superior">
                <ul class="nav_contenedor_superior">
                    <li class="nav_opciones nav_opciones_superior">
                        <a type="button" href="#" class="opciones"><i class="opciones_i" data-lucide="user-cog"></i> Mi Perfil</a>
                        <a type="button" href="#" class="opciones"><i class="opciones_i" data-lucide="settings"></i> Configuraciones</a>
                        <a type="button" href="#" id="modo_oscuro" class="opciones"><i class="opciones_i" data-lucide="<?php echo _TEMA_ === 'oscuro' ? 'sun' : 'moon' ?>"></i> Modo Oscuro</a>
                        <a type="button" href="#" id="salir" class="opciones"><i class="opciones_i" data-lucide="log-out"></i> Cerrar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</div>
<section class="asistente_overlay" id="asistente_modal_contenedor">
    <div class="asistente_ventana ocultar" id="asistente_modal">
        <div class="asistente_cabecera">
            <div class="asistente_info_perfil">
                <i data-lucide="bot-message-square" class="asistente_icon_bot"></i>
                <h2 class="asistente_nombre_titulo">Asistente Virtual</h2>
            </div>
            <a type="button" class="asistente_boton_cerrar" id="cerrar_modal_asistente">&times;</a>
        </div>

        <div class="asistente_cuerpo">
            <div class="asistente_historial" id="chat_historial">
                <div class="asistente_msg asistente_bot">
                    <div class="asistente_burbuja">
                        ¡Hola! Soy Sydney, tu asistente virtual. ¿En qué puedo ayudarte hoy?
                    </div>
                </div>
            </div>

            <div class="asistente_editor">
                <textarea id="chat_mensaje" class="asistente_input" placeholder="Escribe un mensaje..." rows="1"></textarea>
                <button id="enviar_mensaje" class="asistente_btn_enviar">
                    <i data-lucide="send-horizontal"></i>
                </button>
            </div>
        </div>
    </div>
</section>