<?php
// app/controlador/PreguntasFrecuentes.php

// 1. Cargamos las funciones base
require_once(__DIR__ . "/Base.php");

// 2. Comprobar sesión
if (!isset($_SESSION['id'])) {
    header("Location: Inicio");
    exit();
}

// 3. Renderizar la vista
// El módulo no tiene un modelo asociado ni validación de ID de módulo ya que es genérico de soporte.
cargarVista($pagina);
