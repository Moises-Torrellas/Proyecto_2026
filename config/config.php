<?php
/* Base de Datos */
define('_DB_NAME_SG_', 'bds');
define('_DB_HOST_SG_', 'localhost');
define('_DB_USER_SG_', 'root');
define('_DB_PASS_SG_', 'admin123');

date_default_timezone_set('America/Caracas');
setlocale(LC_TIME, 'es_ES.UTF-8');

define('_URL_', '/Proyecto_2026/public/');

define('_TEMA_', $_COOKIE['tema_preferido'] ?? 'claro');

/*Requisitos no Funcionales*/
const _MD_USUARIOS_ = 1;
const _MD_ROLES_    = 2;
const _MD_BITACORA_ = 3;
const _MD_INICIO_   = 4;
const _MD_CERRAR_   = 5;
const _MD_PRODUCTOS_= 6;

if (session_status() === PHP_SESSION_NONE) {
    // Configuramos la sesión antes de iniciarla
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_name('SISTEMA_SG_SESSION');
    session_start();
}