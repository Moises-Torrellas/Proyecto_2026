<?php
/* Base de Datos */
define('_DB_NAME_SG_', 'bds');
define('_DB_HOST_SG_', 'localhost');
define('_DB_USER_SG_', 'root');
define('_DB_PASS_SG_', '');

define('_DB_NAME_', 'cannibalsbd');
define('_DB_HOST_', 'localhost');
define('_DB_USER_', 'root');
define('_DB_PASS_', '');

date_default_timezone_set('America/Caracas');
setlocale(LC_TIME, 'es_ES.UTF-8');
setlocale(LC_CTYPE, 'es_ES.UTF-8');

define('_URL_', '/Proyecto_2026/public/');

define('_TEMA_', $_COOKIE['tema_preferido'] ?? 'claro');

/*Requisitos no Funcionales*/
const _MD_USUARIOS_ = 1;
const _MD_ROLES_    = 2;
const _MD_BITACORA_ = 3;
const _MD_INICIO_   = 4;
const _MD_CERRAR_   = 5;
const _MD_RECUPERACION_ = 8;
const _MD_REPRESENTANTES_ = 9;
const _MD_POSICIONES_ = 10;
const _MD_CATEGORIAS_ = 11;
const _MD_CUENTAS_ = 12;
const _MD_PAGOS_ = 13;
const _MD_METODOS_ = 14;
const _MD_EQUIPAMIENTO_ = 15;
const _MD_CATALOGO_ = 16;
const _MD_ASIGNACIONES_ = 17;
const _MD_DEVOLUCIONES_ = 18;
const _MD_TORNEOS_ = 19;
const _MD_EQUIPOS_ = 20;
const _MD_PREMIOS_ = 21;
const _MD_PALMARES_ = 22;
const _MD_ESTADISTICAS_ = 23;

if (session_status() === PHP_SESSION_NONE) {
    // Configuramos la sesión antes de iniciarla
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_name('SISTEMA_SG_SESSION');
    session_start();
}

