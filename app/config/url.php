<?php
// Asset URL builder based on the project root derived from SCRIPT_NAME
function asset($path){
  // SCRIPT_NAME example: /Proyecto_2026/app/vista/Inicio.php
  $script = $_SERVER['SCRIPT_NAME'];
  // Move up 3 levels to reach project root: /Proyecto_2026
  $root = dirname($script, 3);
  return rtrim($root, '/') . '/' . ltrim($path, '/');
}
?>
