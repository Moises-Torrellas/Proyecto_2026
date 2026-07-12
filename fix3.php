<?php
$file = 'c:/xampp/htdocs/Proyecto_2026/public/css/main.css';
$c = file_get_contents($file);
$c = preg_replace('/(\.perfil_grid\s*\{[^}]*?)width:\s*100%;/s', '$1width: 90%;', $c);
file_put_contents($file, $c);
?>
