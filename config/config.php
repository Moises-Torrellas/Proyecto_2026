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

//define('_URL_', '/Proyecto_2026/public/');

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
const _MD_IA_ = 99;
const _MD_ATLETAS_ = 100;
const _MD_CONCEPTOS_ = 101;
const _MD_MONEDAS_ = 102;
const _MD_CATEGORIA_EQUI_ = 103;
const _MD_CALIDAD_ = 104;
const _MD_PARTICIPACIONES_ = 105;
const _MD_RESPALDO_ = 106;
const _MD_REPORTES_ = 107;

/* CODIGOS DE ERROR */
const DUPLICATE_CEDULA = "001";
const DUPLICATE_EMAIL  = "002";
const INVALID_ID     = "003";
const DUPLICATE_NAME  = "004";
const DUPLICATE_PHONE  = "005";
const ASSOCIATES  = "006";
const VALIDATION = "007";
const ALREADY_ANNULLED = "008";
const EMPTY_SELECTION  = "009";
const DB_CONNECTION    = "500";


const EXCHANGE_RATE_API_KEY = 'eb4ded73b72a7239fcce3154' ;
if (session_status() === PHP_SESSION_NONE) {
    // Configuramos la sesión antes de iniciarla
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_name('SISTEMA_CBS_SESSION');
    session_start();
}
