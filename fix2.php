<?php
$file = 'c:/xampp/htdocs/Proyecto_2026/public/css/main.css';
$c = file_get_contents($file);
$c = str_replace('.perfil_grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    width: 100%;', '.perfil_grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    width: 90%;', $c);
$c = str_replace('.reportes_grid { display: grid; grid-template-columns: 1fr; gap: 30px; width: 100%; }', '.reportes_grid { display: grid; grid-template-columns: 1fr; gap: 30px; width: 90%; }', $c);
file_put_contents($file, $c);
?>
