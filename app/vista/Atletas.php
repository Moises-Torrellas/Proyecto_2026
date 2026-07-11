<?php
if (isset($solo_lista) && $solo_lista === true) :
    if (empty($registro)) : ?>
        <div class="listado_vacio">
            <p>No se encontraron registros</p>
        </div>
        <?php else :
        $anioActual = date('Y');
        foreach ($registro as $dato) :
            $anioNacimiento = date('Y', strtotime($dato['fecha_nac']));
            $edadCalendario = $anioActual - $anioNacimiento;
            $genero = ($dato['genero'] === 'H') ? 'Hombre' : 'Mujer';
            $edadMin = $dato['edad_min'] ?? 0;
            $edadMax = $dato['edad_max'] ?? 99;
            $fueraDeRango = ($edadCalendario < $edadMin || $edadCalendario > $edadMax);

            // Renderizado dinámico del Avatar/Foto
            $foto = $dato['foto'] ?? '';

            if ($fueraDeRango) {
                $fotoHTML = '<div class="listado_avatar_null" style="color: #eab308;" data-tippy-content="Edad fuera del rango de la categoría"><i class="icon_con" data-lucide="circle-alert"></i></div>';
            } else {
                // Comportamiento normal si está en rango
                $fotoHTML = ($foto === 'default.png' || empty($foto))
                    ? '<div class="listado_avatar_null"><i class="icon_con" data-lucide="circle-user"></i></div>'
                    : '<img src="img/atletas/' . htmlspecialchars($foto) . '" class="listado_avatar" alt="Perfil" onerror="manejarErrorCamara(this)">';
            }
        ?>
            <div id="registro" class="listado_contenedor_grupal">
                <div class="listado_item" onclick="toggleDetalles(this)">

                    <div class="listado_col_principal">
                        <?= $fotoHTML ?>
                        <div class="listado_info_base">
                            <span class="listado_titulo">
                                <?= htmlspecialchars($dato['nombres']) ?> <?= htmlspecialchars($dato['apellidos']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="listado_col_datos">
                        <div class="listado_dato_grupo">
                            <small>Doc de Identidad</small>
                            <span><?= htmlspecialchars(!empty($dato['doc_identidad']) ? $dato['doc_identidad'] : 'R-' . ($dato['cedula_rep'] ?? '')) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Edad (Año Cal.)</small>
                            <span class="listado_resaltado" <?= $fueraDeRango ? 'style="color: #eab308;"' : '' ?>>
                                <?= $edadCalendario ?> años
                            </span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Género</small>
                            <span><?= htmlspecialchars($genero) ?></span>
                        </div>
                        <div class="listado_dato_grupo">
                            <small>Estatus</small>
                            <?php if ($dato['estatus'] == 1) : ?>
                                <span class="estatus_v">Activo</span>
                            <?php else : ?>
                                <span class="estatus_r">Retirado</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="listado_col_acciones">
                        <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                            <?php if (!empty($permisos['modificar_atleta'])) : ?>
                                <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['id_atleta'] ?>)" data-tippy-content="Modificar">
                                    <i class="fi fi-sr-pencil"></i>
                                </button>
                            <?php endif; ?>
                            <?php if ($dato['estatus'] == 1 && !empty($permisos['retirar_atleta'])) : ?>
                                <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['id_atleta'] ?>)" data-tippy-content="Retirar">
                                    <i class="fi fi-sr-cross-circle"></i>
                                </button>
                            <?php endif; ?>
                            <?php if ($dato['estatus'] == 2 && !empty($permisos['reinscribir_atleta'])) : ?>
                                <button id="cbt_re" class="btn_t cbt_m" onclick="buscarReinscribir(<?= $dato['id_atleta'] ?>)" data-tippy-content="Re-Inscribir">
                                    <i class="fi fi-sr-rotate-right"></i>
                                </button>
                            <?php endif; ?>
                            <?php if (!empty($permisos['curriculum_atleta'])) : ?>
                                <button id="cbt_sec" class="btn_t cbt_sec" onclick="GenerarCurriculum(<?= $dato['id_atleta'] ?>)" data-tippy-content="Generar Currículum">
                                    <i class="fi fi-sr-clipboard-user"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                    </div>
                </div>

                <div class="listado_detalle_oculto">
                    <div class="detalle_expandido_container">

                        <div class="detalle_fila">
                            <div class="detalle_card">
                                <div class="detalle_card_icon"><i data-lucide="bring-to-front"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Categoría Deportiva</label>
                                    <span><?= htmlspecialchars($dato['nombre_categoria']) ?></span>
                                    <small <?= $fueraDeRango ? 'style="color: #eab308; font-weight: bold;"' : '' ?>>
                                        Rango: <?= $dato['edad_min'] ?>-<?= $dato['edad_max'] ?> años
                                    </small>
                                </div>
                            </div>

                            <?php if (!empty($dato['nombre_rep']) && trim($dato['nombre_rep']) !== "") : ?>
                                <div class="detalle_card">
                                    <div class="detalle_card_icon"><i data-lucide="user-star"></i></div>
                                    <div class="detalle_card_txt">
                                        <label>Representante</label>
                                        <span><?= htmlspecialchars($dato['nombre_rep']) ?> <?= htmlspecialchars($dato['apellido_rep'] ?? '') ?></span>
                                        <small><?= htmlspecialchars($dato['cedula_rep'] ?? '') ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="detalle_card">
                                <div class="detalle_card_icon"><i data-lucide="land-plot"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Posición Técnica</label>
                                    <span><?= htmlspecialchars($dato['nombre_posicion']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="detalle_fila">
                            <div class="detalle_card">
                                <div class="detalle_card_icon"><i data-lucide="map-pin"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Dirección</label>
                                    <span><?= htmlspecialchars(!empty($dato['direccion']) ? $dato['direccion'] : ($dato['direccion_rep'] ?? '')) ?></span>
                                </div>
                            </div>
                            <div class="detalle_card">
                                <div class="detalle_card_icon"><i data-lucide="phone"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Teléfono</label>
                                    <span><?= htmlspecialchars(!empty($dato['telefono']) ? $dato['telefono'] : ($dato['telefono_rep'] ?? '')) ?></span>
                                </div>
                            </div>
                            <div class="detalle_card">
                                <div class="detalle_card_icon"><i data-lucide="calendar-1"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Fecha de Nacimiento</label>
                                    <span><?= htmlspecialchars(date('d-m-Y', strtotime($dato['fecha_nac']))) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="detalle_fila">
                            <div class="detalle_card">
                                <div class="detalle_card_icon"><i data-lucide="ruler"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Físico</label>
                                    <span><?= htmlspecialchars($dato['peso_kg'] ?? '0') ?> kg / <?= htmlspecialchars($dato['estatura_cm'] ?? '0') ?> cm</span>
                                </div>
                            </div>
                            <div class="detalle_card">
                                <div class="detalle_card_icon"><i data-lucide="shirt"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Dorsal</label>
                                    <span><?= htmlspecialchars($dato['dorsal'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                            <div class="detalle_card">
                                <div class="detalle_card_icon"><i data-lucide="calendar-plus"></i></div>
                                <div class="detalle_card_txt">
                                    <label>Fecha de Ingreso</label>
                                    <span><?= htmlspecialchars(date('d-m-Y', strtotime($dato['fecha_ingreso']))) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="detalle_fila">
                            <?php if (!empty($dato['fecha_retiro'])) : ?>
                                <div class="detalle_card">
                                    <div class="detalle_card_icon" style="color:#ef4444;"><i data-lucide="calendar-minus"></i></div>
                                    <div class="detalle_card_txt">
                                        <label>Fecha de Retiro</label>
                                        <span><?= htmlspecialchars(date('d-m-Y', strtotime($dato['fecha_retiro']))) ?></span>
                                        <small style="color:#ef4444;">Motivo: <?= htmlspecialchars($dato['motivo_retiro'] ?? 'No especificado') ?></small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($dato['fecha_reingreso'])) : ?>
                                <div class="detalle_card">
                                    <div class="detalle_card_icon" style="color:#22c55e;"><i data-lucide="calendar-sync"></i></div>
                                    <div class="detalle_card_txt">
                                        <label>Fecha de Re-Ingreso</label>
                                        <span><?= htmlspecialchars(date('d-m-Y', strtotime($dato['fecha_reingreso']))) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
<?php
        endforeach;
    endif;
    exit();
endif;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php include('complementos/head.php'); ?>
    <title>Atletas</title>
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
                            <h2 class="titulo_pagina" id="titulo">Atletas</h2>
                        </div>
                        <div class="contenedor_busqueda">
                            <input type="text" placeholder="Buscar..." autocomplete="off" id="busqueda">
                            <i class="fi fi-br-search icon_input"></i>
                        </div>
                        <div class="botones">
                            <?php if (!empty($permisos['incluir_atleta'])) : ?>
                                <button class="btn btn_azul" id="incluir">Nuevo Atleta</button>
                            <?php endif; ?>
                            <?php if (!empty($permisos['generar_atletas'])) : ?>
                                <button class="btn btn_verde" id="generar">Generar Reporte</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="contenedor_resultados">
                        <div id="resultadoconsulta" class="resultadoconsulta">
                            <?php if (empty($registro)) : ?>
                                <div class="listado_vacio">
                                    <p>No se encontraron registros</p>
                                </div>
                                <?php else :
                                $anioActual = date('Y');
                                foreach ($registro as $dato) :
                                    $anioNacimiento = date('Y', strtotime($dato['fecha_nac']));
                                    $edadCalendario = $anioActual - $anioNacimiento;
                                    $genero = ($dato['genero'] === 'H') ? 'Hombre' : 'Mujer';
                                    $edadMin = $dato['edad_min'] ?? 0;
                                    $edadMax = $dato['edad_max'] ?? 99;
                                    $fueraDeRango = ($edadCalendario < $edadMin || $edadCalendario > $edadMax);

                                    // Renderizado dinámico del Avatar/Foto
                                    $foto = $dato['foto'] ?? '';

                                    if ($fueraDeRango) {
                                        $fotoHTML = '<div class="listado_avatar_null" style="color: #eab308;" data-tippy-content="Edad fuera del rango de la categoría"><i class="icon_con" data-lucide="circle-alert"></i></div>';
                                    } else {
                                        // Comportamiento normal si está en rango
                                        $fotoHTML = ($foto === 'default.png' || empty($foto))
                                            ? '<div class="listado_avatar_null"><i class="icon_con" data-lucide="circle-user"></i></div>'
                                            : '<img src="img/atletas/' . htmlspecialchars($foto) . '" class="listado_avatar" alt="Perfil" onerror="manejarErrorCamara(this)">';
                                    }
                                ?>
                                    <div id="registro" class="listado_contenedor_grupal">
                                        <div class="listado_item" onclick="toggleDetalles(this)">

                                            <div class="listado_col_principal">
                                                <?= $fotoHTML ?>
                                                <div class="listado_info_base">
                                                    <span class="listado_titulo">
                                                        <?= htmlspecialchars($dato['nombres']) ?> <?= htmlspecialchars($dato['apellidos']) ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="listado_col_datos">
                                                <div class="listado_dato_grupo">
                                                    <small>Doc de Identidad</small>
                                                    <span><?= htmlspecialchars(!empty($dato['doc_identidad']) ? $dato['doc_identidad'] : 'R-' . ($dato['cedula_rep'] ?? '')) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Edad (Año Cal.)</small>
                                                    <span class="listado_resaltado" <?= $fueraDeRango ? 'style="color: #eab308;"' : '' ?>>
                                                        <?= $edadCalendario ?> años
                                                    </span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Género</small>
                                                    <span><?= htmlspecialchars($genero) ?></span>
                                                </div>
                                                <div class="listado_dato_grupo">
                                                    <small>Estatus</small>
                                                    <?php if ($dato['estatus'] == 1) : ?>
                                                        <span class="estatus_v">Activo</span>
                                                    <?php else : ?>
                                                        <span class="estatus_r">Retirado</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="listado_col_acciones">
                                                <div onclick="event.stopPropagation();" style="display:flex; gap:5px;">
                                                    <?php if (!empty($permisos['modificar_atleta'])) : ?>
                                                        <button id="cbt_v" class="btn_t cbt_v" onclick="buscar(<?= $dato['id_atleta'] ?>)" data-tippy-content="Modificar">
                                                            <i class="fi fi-sr-pencil"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($dato['estatus'] == 1 && !empty($permisos['retirar_atleta'])) : ?>
                                                        <button id="cbt_r" class="btn_t cbt_r" onclick="eliminar(<?= $dato['id_atleta'] ?>)" data-tippy-content="Retirar">
                                                            <i class="fi fi-sr-cross-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($dato['estatus'] == 2 && !empty($permisos['reinscribir_atleta'])) : ?>
                                                        <button id="cbt_re" class="btn_t cbt_m" onclick="buscarReinscribir(<?= $dato['id_atleta'] ?>)" data-tippy-content="Re-Inscribir">
                                                            <i class="fi fi-sr-rotate-right"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if (!empty($permisos['curriculum_atleta'])) : ?>
                                                        <button id="cbt_sec" class="btn_t cbt_sec" onclick="GenerarCurriculum(<?= $dato['id_atleta'] ?>)" data-tippy-content="Generar Currículum">
                                                            <i class="fi fi-sr-clipboard-user"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <i data-lucide="chevron-down" class="icono_flecha_detalle"></i>
                                            </div>
                                        </div>

                                        <div class="listado_detalle_oculto">
                                            <div class="detalle_expandido_container">

                                                <div class="detalle_fila">
                                                    <div class="detalle_card">
                                                        <div class="detalle_card_icon"><i data-lucide="bring-to-front"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Categoría Deportiva</label>
                                                            <span><?= htmlspecialchars($dato['nombre_categoria']) ?></span>
                                                            <small <?= $fueraDeRango ? 'style="color: #eab308; font-weight: bold;"' : '' ?>>
                                                                Rango: <?= $dato['edad_min'] ?>-<?= $dato['edad_max'] ?> años
                                                            </small>
                                                        </div>
                                                    </div>

                                                    <?php if (!empty($dato['nombre_rep']) && trim($dato['nombre_rep']) !== "") : ?>
                                                        <div class="detalle_card">
                                                            <div class="detalle_card_icon"><i data-lucide="user-star"></i></div>
                                                            <div class="detalle_card_txt">
                                                                <label>Representante</label>
                                                                <span><?= htmlspecialchars($dato['nombre_rep']) ?> <?= htmlspecialchars($dato['apellido_rep'] ?? '') ?></span>
                                                                <small><?= htmlspecialchars($dato['cedula_rep'] ?? '') ?></small>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="detalle_card">
                                                        <div class="detalle_card_icon"><i data-lucide="land-plot"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Posición Técnica</label>
                                                            <span><?= htmlspecialchars($dato['nombre_posicion']) ?></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="detalle_fila">
                                                    <div class="detalle_card">
                                                        <div class="detalle_card_icon"><i data-lucide="map-pin"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Dirección</label>
                                                            <span><?= htmlspecialchars(!empty($dato['direccion']) ? $dato['direccion'] : ($dato['direccion_rep'] ?? '')) ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="detalle_card">
                                                        <div class="detalle_card_icon"><i data-lucide="phone"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Teléfono</label>
                                                            <span><?= htmlspecialchars(!empty($dato['telefono']) ? $dato['telefono'] : ($dato['telefono_rep'] ?? '')) ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="detalle_card">
                                                        <div class="detalle_card_icon"><i data-lucide="calendar-1"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Fecha de Nacimiento</label>
                                                            <span><?= htmlspecialchars(date('d-m-Y', strtotime($dato['fecha_nac']))) ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="detalle_fila">
                                                    <div class="detalle_card">
                                                        <div class="detalle_card_icon"><i data-lucide="ruler"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Físico</label>
                                                            <span><?= htmlspecialchars($dato['peso_kg'] ?? '0') ?> kg / <?= htmlspecialchars($dato['estatura_cm'] ?? '0') ?> cm</span>
                                                        </div>
                                                    </div>
                                                    <div class="detalle_card">
                                                        <div class="detalle_card_icon"><i data-lucide="shirt"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Dorsal</label>
                                                            <span><?= htmlspecialchars($dato['dorsal'] ?? 'N/A') ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="detalle_card">
                                                        <div class="detalle_card_icon"><i data-lucide="calendar-plus"></i></div>
                                                        <div class="detalle_card_txt">
                                                            <label>Fecha de Ingreso</label>
                                                            <span><?= htmlspecialchars(date('d-m-Y', strtotime($dato['fecha_ingreso']))) ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="detalle_fila">
                                                    <?php if (!empty($dato['fecha_retiro'])) : ?>
                                                        <div class="detalle_card">
                                                            <div class="detalle_card_icon" style="color:#ef4444;"><i data-lucide="calendar-minus"></i></div>
                                                            <div class="detalle_card_txt">
                                                                <label>Fecha de Retiro</label>
                                                                <span><?= htmlspecialchars(date('d-m-Y', strtotime($dato['fecha_retiro']))) ?></span>
                                                                <small style="color:#ef4444;">Motivo: <?= htmlspecialchars($dato['motivo_retiro'] ?? 'No especificado') ?></small>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($dato['fecha_reingreso'])) : ?>
                                                        <div class="detalle_card">
                                                            <div class="detalle_card_icon" style="color:#22c55e;"><i data-lucide="calendar-sync"></i></div>
                                                            <div class="detalle_card_txt">
                                                                <label>Fecha de Re-Ingreso</label>
                                                                <span><?= htmlspecialchars(date('d-m-Y', strtotime($dato['fecha_reingreso']))) ?></span>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                endforeach;
                            endif;
                            ?>
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
                                <input type="date" class="formulario" id="fecha_nac" name="fecha_nac">
                                <label for="fecha_nac" class="titulo_formulario">Fecha de Nacimiento</label>
                                <span class="mensaje" id="fecha_nac_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="edad" name="edad" readonly>
                                <label for="edad" class="titulo_formulario">Edad (Año Calendario)</label>
                                <span class="mensaje" id="edad_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="nombre" name="nombre">
                                <label for="nombre" class="titulo_formulario">Primer y Segundo Nombre</label>
                                <span class="mensaje" id="nombre_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="apellido" name="apellido">
                                <label for="apellido" class="titulo_formulario">Primer y Segundo Apellido</label>
                                <span class="mensaje" id="apellido_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="categoria" id="categoria" class="formulario select">

                                </select>
                                <label for="categoria" class="titulo_formulario">Categoria</label>
                                <span class="mensaje" id="categoria_span"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="posicion" id="posicion" class="formulario select">

                                </select>
                                <label for="posicion" class="titulo_formulario">Posición</label>
                                <span class="mensaje" id="posicion_span"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="genero" id="genero" class="formulario select">
                                    <option id="todos" value="T" selected>Todos</option>
                                    <option value="H">Hombre</option>
                                    <option value="M">Mujer</option>
                                </select>
                                <label for="genero" class="titulo_formulario">Genero</label>
                                <span class="mensaje" id="genero_span"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="estatus" id="estatus" class="formulario select" disabled>
                                    <option value="T" selected>Todos</option>
                                    <option value="1">Activos</option>
                                    <option value="2">Retirados</option>
                                </select>
                                <label for="estatus" class="titulo_formulario">Estatus</label>
                                <span class="mensaje" id="estatus_span"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <select name="representante" id="representante" class="formulario select">

                                </select>
                                <label for="representante" class="titulo_formulario">Representante</label>
                                <span class="mensaje" id="representante_span"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="doc_i" name="doc_i">
                                <label for="doc_i" class="titulo_formulario">Documento de Identidad</label>
                                <span class="mensaje" id="doc_i_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="telefono" name="telefono">
                                <label for="telefono" class="titulo_formulario">Telefono</label>
                                <span class="mensaje" id="telefono_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="text" class="formulario" id="direccion" name="direccion">
                                <label for="direccion" class="titulo_formulario">Direccion</label>
                                <span class="mensaje" id="direccion_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="number" class="formulario" id="dorsal" name="dorsal">
                                <label for="dorsal" class="titulo_formulario">Dorsal</label>
                                <span class="mensaje" id="dorsal_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="number" class="formulario" step="0.01" id="peso" name="peso">
                                <label for="peso" class="titulo_formulario">Peso (kg)</label>
                                <span class="mensaje" id="peso_spam"></span>
                            </div>
                        </div>
                        <div class="colum">
                            <div class="caja_formulario">
                                <input type="number" class="formulario" id="estatura" name="estatura">
                                <label for="estatura" class="titulo_formulario">Estatura (cm)</label>
                                <span class="mensaje" id="estatura_spam"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="colum">
                            <div class="caja_formulario subir_foto">

                                <div class="preview_contenedor">
                                    <img id="foto_previa" src="" style="display: none;">
                                    <i id="icono_default" data-lucide="circle-user" style="display: block; width: 80px; height: 80px; color: var(--color-primario);"></i>
                                </div>

                                <input type="file" class="input_foto_oculto" id="foto" name="foto" accept="image/*">

                                <label for="foto" class="btn_subir_foto">
                                    <i data-lucide="upload-cloud"></i> Seleccionar Foto
                                </label>

                                <span class="mensaje" id="foto_spam"></span>
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
    <?php include('complementos/MiniModal.php'); ?>
    <script src="js/main.js"></script>
    <script src="js/atletas.js"></script>
    <?php include('complementos/mensajeError.php'); ?>
</body>

</html>