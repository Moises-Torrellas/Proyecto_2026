<?php
$file = 'c:/xampp/htdocs/Proyecto_2026/public/css/main.css';
$content = file_get_contents($file);
$content = preg_replace('/\.reportes_grid\s*\{.*?\.canvas_reporte\s*\{.*?\}/s', '.reportes_grid { display: grid; grid-template-columns: 1fr; gap: 30px; width: 100%; } .canvas_reporte { position: relative; width: 100%; height: 350px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed var(--borde-color); } .filtros_reporte { margin-top: 10px; }', $content);
file_put_contents($file, $content);
?>
