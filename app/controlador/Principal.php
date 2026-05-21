<?php

// 1. Cargamos las funciones base
require_once(__DIR__."/Base.php");

    if (comprobarAjax() && !empty($_POST)) {
        
} else {
    cargarVista($pagina);
}
