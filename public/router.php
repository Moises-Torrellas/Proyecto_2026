<?php

declare(strict_types=1);

// Router para el servidor web incorporado de PHP (php -S)
// Permite emular las reglas de reescritura de Apache (.htaccess) en entornos CI/CD y desarrollo local.

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

// Servir archivos estáticos existentes directamente (CSS, JS, imágenes, etc.)
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Extraer el parámetro de ruta solicitado
$pagina = ltrim($uri, '/');
if (!empty($pagina)) {
    $_GET['pagina'] = $pagina;
}

require_once __DIR__ . '/index.php';
