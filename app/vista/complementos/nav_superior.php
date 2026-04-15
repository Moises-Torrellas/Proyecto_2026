<?php include_once(__DIR__ . '/cookie.php'); ?>
<div class="nav_superior">
    <div class="contenedor_superior">
        <div class="contenedor_logo">
            <img src="img/logo.png" class="logo">
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
<section class="contenedor_modal" id="asistente_modal_contenedor">
    <div class="modal modal_pequeño ocultar" id="asistente_modal">
        <div class="cabecera_modal">
            <h2 class="titulo_modal" id="titulo_modal"></h2>
            <a type="button" class="cerrar_modal" id="cerrar_modal_asistente">&times;</a>
        </div>
        <div class="contenido_modal">

        </div>
    </div>
</section>