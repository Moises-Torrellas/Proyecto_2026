<div class="nav_lateral">
    <div class="navegacion" id="navegacion">
        <ul class="nav_contenedor">

            <li class="nav_identificador">Dashboard</li>
            <a type="button" href="Principal" class="opciones">
                <i class="opciones_i" data-lucide="layout-dashboard"></i> Panel de Control
            </a>

            <?php
            $nivelUsuario = $_SESSION['nivel_rol'] ?? 99;

            // Añadimos $clave_acceso como segundo parámetro
            $puedeVer = function (int $id_modulo, string $clave_acceso) use ($nivelUsuario): bool {
                // Si el nivel 1 es superadministrador y ve todo, lo dejamos pasar de inmediato
                if ($nivelUsuario === 1) return true;

                // Llamamos a la función maestra: modulo, clave, bitacora (null), soloValidar (true)
                $p = procesarPermisos($id_modulo, $clave_acceso, true);

                // Verificamos si la clave específica existe y es verdadera
                return !empty($p[$clave_acceso]);
            };
            ?>

            <?php if ($puedeVer(_MD_ATLETAS_, 'ingresar_atleta') || $puedeVer(_MD_REPRESENTANTES_, 'ingresar_representantes') || $puedeVer(_MD_POSICIONES_, 'ingresar_posicion') || $puedeVer(_MD_CATEGORIAS_,'')) : ?>
                <li class="nav_identificador">Administracion</li>
                <li class="nav_opciones">
                    <?php if ($puedeVer(_MD_ATLETAS_,'')) : ?>
                        <a type="button" href="Atletas" class="opciones"><i class="opciones_i" data-lucide="circle-star"></i> Atletas</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_REPRESENTANTES_,'ingresar_representantes')) : ?>
                        <a type="button" href="Representantes" class="opciones "><i class="opciones_i" data-lucide="user-star"></i> Representantes</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_POSICIONES_,'')) : ?>
                        <a type="button" href="Posiciones" class="opciones "><i class="opciones_i" data-lucide="land-plot"></i> Posiciones</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_CATEGORIAS_,'ingresar_categorias')) : ?>
                        <a type="button" href="Categorias" class="opciones "><i class="opciones_i" data-lucide="bring-to-front"></i> Categorías</a>
                    <?php endif; ?>
                </li>
            <?php endif; ?>

            <?php if ($puedeVer(_MD_CUENTAS_,'ingresar_cargo') || $puedeVer(_MD_PAGOS_,'ingresar_pago') || $puedeVer(_MD_METODOS_,'ingresar_metodop') || $puedeVer(_MD_CONCEPTOS_,'') || $puedeVer(_MD_MONEDAS_,'')) : ?>
                <li class="nav_identificador">Cobranzas</li>
                <li class="nav_opciones">
                    <?php if ($puedeVer(_MD_CUENTAS_,'ingresar_cargo')) : ?>
                        <a type="button" href="CuentasCobrar" class="opciones"><i class="opciones_i" data-lucide="hand-coins"></i> Cargos</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_PAGOS_,'ingresar_pago')) : ?>
                        <a type="button" href="Pagos" class="opciones"><i class="opciones_i" data-lucide="banknote"></i> Pagos</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_METODOS_,'ingresar_metodop')) : ?>
                        <a type="button" href="MetodosPago" class="opciones"><i class="opciones_i" data-lucide="wallet"></i> Metodos de Pago</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_CONCEPTOS_,'ingresar_conceptos')) : ?>
                        <a type="button" href="Conceptos" class="opciones"><i class="opciones_i" data-lucide="receipt"></i> Conceptos de Cargos</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_MONEDAS_,'')) : ?>
                        <a type="button" href="Monedas" class="opciones"><i class="opciones_i" data-lucide="coins"></i> Monedas</a>
                        <a type="button" href="TasaCambios" class="opciones"><i class="opciones_i" data-lucide="arrow-left-right"></i> Tasas de Cambio</a>
                    <?php endif; ?>
                </li>
            <?php endif; ?>

            <?php if ($puedeVer(_MD_ARTICULOS_INVENTARIO_,'') || $puedeVer(_MD_CATALOGO_,'') || $puedeVer(_MD_CATEGORIA_CAT_,'') || $puedeVer(_MD_ESTADO_FISICO_,'') || $puedeVer(_MD_ASIGNACIONES_,'') || $puedeVer(_MD_DEVOLUCIONES_,'')) : ?>
                <li class="nav_identificador">Inventario</li>
                <li class="nav_opciones">
                    <?php if ($puedeVer(_MD_ARTICULOS_INVENTARIO_,'')) : ?>
                        <a type="button" href="ArticulosInventario" class="opciones"><i class="opciones_i" data-lucide="boxes"></i> Inventario de Artículos</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_CATALOGO_,'')) : ?>
                        <a type="button" href="Catalogo" class="opciones"><i class="opciones_i" data-lucide="clipboard-pen-line"></i> Catálogo</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_CATEGORIA_CAT_,'')) : ?>
                        <a type="button" href="CategoriaCatalogo" class="opciones"><i class="opciones_i" data-lucide="layers-plus"></i> Categorías de Catálogo</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_ESTADO_FISICO_,'')) : ?>
                        <a type="button" href="EstadoFisico" class="opciones"><i class="opciones_i" data-lucide="badge-check"></i> Estado Físico</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_ASIGNACIONES_,'')) : ?>
                        <a type="button" href="Asignaciones" class="opciones"><i class="opciones_i" data-lucide="list-plus"></i> Asignaciones</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_DEVOLUCIONES_,'')) : ?>
                        <a type="button" href="Devoluciones" class="opciones"><i class="opciones_i" data-lucide="list-restart"></i> Devoluciones</a>
                    <?php endif; ?>
                </li>
            <?php endif; ?>

            <?php if ($puedeVer(_MD_TORNEOS_,'') || $puedeVer(_MD_EQUIPOS_,'') || $puedeVer(_MD_PARTICIPACIONES_,'')) : ?>
                <li class="nav_identificador">Competencias</li>
                <li class="nav_opciones">
                    <?php if ($puedeVer(_MD_TORNEOS_,'ingresar_torneos')) : ?>
                        <a type="button" href="Torneos" class="opciones"><i class="opciones_i" data-lucide="hand-coins"></i> Torneos</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_EQUIPOS_,'')) : ?>
                        <a type="button" href="Equipos" class="opciones"><i class="opciones_i" data-lucide="shield-half"></i> Equipos</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_PARTICIPACIONES_,'')) : ?>
                        <a type="button" href="Participaciones" class="opciones"><i class="opciones_i" data-lucide="shield-check"></i> Participaciones</a>
                    <?php endif; ?>
                </li>
            <?php endif; ?>
            <?php if ($puedeVer(_MD_PREMIOS_,'') || $puedeVer(_MD_PALMARES_,'') || $puedeVer(_MD_ESTADISTICAS_,'')) : ?>
                <li class="nav_identificador">Historial Deportivo</li>
                <li class="nav_opciones">
                    <?php if ($puedeVer(_MD_PREMIOS_,'')) : ?>
                        <a type="button" href="Premios" class="opciones"><i class="opciones_i" data-lucide="award"></i> Premios</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_PALMARES_,'')) : ?>
                        <a type="button" href="Palmares" class="opciones"><i class="opciones_i" data-lucide="trophy"></i> Palmarés</a>
                    <?php endif; ?>

                    <?php if ($puedeVer(_MD_ESTADISTICAS_,'')) : ?>
                        <a type="button" href="Estadisticas" class="opciones"><i class="opciones_i" data-lucide="chart-area"></i> Estadisticas</a>
                    <?php endif; ?>
                    <?php if ($puedeVer(_MD_HISTORIAL_,'')) : ?>
                        <a type="button" href="Historial" class="opciones"><i class="opciones_i" data-lucide="book-user"></i> Historial Deportivo</a>
                    <?php endif; ?>
                </li>
            <?php endif; ?>

            <li class="nav_identificador">Reportes</li>
            <li class="nav_opciones">
                <a type="button" href="Reportes" class="opciones"><i class="opciones_i" data-lucide="chart-column-stacked"></i> Reportes Estadisticos</a>
            </li>

            <?php if ($nivelUsuario === 1) : ?>
                <li class="nav_identificador">General</li>
                <li class="nav_opciones">
                    <a type="button" href="Usuarios" class="opciones"><i class="opciones_i" data-lucide="users"></i> Usuarios</a>
                    <a type="button" href="Roles" class="opciones"><i class="opciones_i" data-lucide="shield-user"></i> Roles</a>
                    <a type="button" href="Permisos" class="opciones"><i class="opciones_i" data-lucide="user-key"></i> Permisos</a>
                    <a type="button" href="Modulos" class="opciones"><i class="opciones_i" data-lucide="component"></i> Modulos</a>
                    <a type="button" href="Bitacora" class="opciones "><i class="opciones_i" data-lucide="notebook"></i> Bitacora</a>
                    <a type="button" href="Respaldo" class="opciones "><i class="opciones_i" data-lucide="server-cog"></i> Mantenimiento BD</a>
                </li>
            <?php endif; ?>

            <li class="nav_identificador">Soporte</li>
            <li class="nav_opciones">
                <a type="button" href="#" class="opciones"><i class="opciones_i" data-lucide="info"></i> Preguntas Frecuentes</a>
                <a type="button" href="#" class="opciones"><i class="opciones_i" data-lucide="book-open-text"></i> Manual De Usuario</a>
            </li>

        </ul>
    </div>
</div>