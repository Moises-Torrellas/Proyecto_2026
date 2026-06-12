<?php include_once(__DIR__ . '/cookie.php'); ?>
<div class="nav_superior">
    <div class="contenedor_superior">
        <div class="contenedor_logo">
            <button class="btn_hamburguesa ocultar_en_escritorio" id="btn_hamburguesa">
                <i data-lucide="menu"></i>
            </button>
            <img src="img/logo.png" class="logo">
            <img src="img/logo_2.png" class="nombre_negocio">
        </div>

        <div class="contenedor_usuario">
            <div class="botones_usuario">
                <a type="button" class="boton_usuario asistente" id="asistente" data-tippy-content="Cani"><i class="icon_boton_usuario" data-lucide="bot-message-square"></i></a>
                <a type="button" class="boton_usuario" id="ayuda" data-tippy-content="Ayuda"><i class="icon_boton_usuario" data-lucide="circle-question-mark"></i></a>
                <a type="button" class="boton_usuario" id="noti" data-tippy-content="Notificaciones"><i class="icon_boton_usuario" data-lucide="bell"></i> <span id="campana-notificaciones-badge" class="badge_conteo ocultar">0</span></a>

                <div class="contenedor_notificaciones ocultar" id="contenedor_notificaciones">
                    <div class="encabezado_notificaciones">
                        <h1>Notificaciones</h1>
                        <i data-lucide="bell" class="icon_noti_titulo"></i>
                    </div>

                    <ul class="lista_noti">
                        
                    </ul>

                    <div class="ver_todas_notificaciones">
                        <a href="#">Ver todas las notificaciones</a>
                    </div>
                </div>
            </div>
            <div class="info_usuario" id="info_usuario">
                <?php if (isset($_SESSION['foto']) && $_SESSION['foto'] !== 'default.png' && $_SESSION['foto'] !== '') : ?>
                    <img src="img/usuarios/<?php echo $_SESSION['foto']; ?>" alt="Perfil" class="img_usuario foto_perfil_navbar">
                <?php else : ?>
                    <i data-lucide="circle-user" class="img_usuario"></i>
                <?php endif; ?>
                <div class="contenedor_nombre">
                    <h3 class="nombre_usuario"><?php echo $_SESSION['nombre'] . ' ' . $_SESSION['apellido']; ?></h3>
                    <h4 class="tipo_usuario"><?php echo $_SESSION['rol']; ?></h4>
                </div>
                <i data-lucide="chevron-down" class="flecha_usuario" id="flecha"></i>
            </div>
            <div class="menu_superior ocultar" id="menu_superior">
                <div class="info_usuario_responsive ocultar_en_escritorio">
                    <h3 class="nombre_usuario"><?php echo $_SESSION['nombre'] . ' ' . $_SESSION['apellido']; ?></h3>
                    <h4 class="tipo_usuario"><?php echo $_SESSION['rol']; ?></h4>
                    <hr class="division_menu">
                </div>
                <ul class="nav_contenedor_superior">
                    <li class="nav_opciones nav_opciones_superior">
                        <a type="button" href="#" class="opciones"><i class="opciones_i" data-lucide="user-cog"></i> Mi Perfil</a>
                        <a type="button" style="cursor: pointer;" id="modo_oscuro" class="opciones"><i class="opciones_i" data-lucide="<?php echo _TEMA_ === 'oscuro' ? 'sun' : 'moon' ?>"></i> Modo Oscuro</a>
                        <a type="button" style="cursor: pointer;" id="salir" class="opciones"><i class="opciones_i" data-lucide="log-out"></i> Cerrar Sesión</a>
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
                        ¡Hola! Soy Cani, tu asistente virtual. ¿En qué puedo ayudarte hoy?
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
