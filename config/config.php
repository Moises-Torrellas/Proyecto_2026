<?php
// =======================================================
// 1. CARGA DE VARIABLES DE ENTORNO (.env)
// =======================================================
// Ajusta esta ruta dependiendo de dónde esté este config.php
// Si config.php está dentro de una carpeta "config", esto retrocede un nivel a la raíz.
$rutaEnv = __DIR__ . '/../.env'; 

if (file_exists($rutaEnv)) {
    $variables = parse_ini_file($rutaEnv);
    foreach ($variables as $clave => $valor) {
        $_ENV[$clave] = $valor;
        putenv(sprintf('%s=%s', $clave, $valor));
    }
} else {
    die("Error crítico: No se encontró el archivo .env en la ruta: " . $rutaEnv);
}

// =======================================================
// 2. CONFIGURACIÓN DE BASES DE DATOS (Protegidas)
// =======================================================
/* Base de Datos SG */
define('_DB_NAME_SG_', 'bds2');
define('_DB_HOST_SG_', 'localhost');
define('_DB_USER_SG_', 'root');
define('_DB_PASS_SG_', '');

/* Base de Datos Principal */
define('_DB_NAME_', 'cannibalsbd2');
define('_DB_HOST_', 'localhost');
define('_DB_USER_', 'root');
define('_DB_PASS_', '');


// =======================================================
// 3. CONFIGURACIÓN REGIONAL Y ZONA HORARIA
// =======================================================
date_default_timezone_set('America/Caracas');
setlocale(LC_TIME, 'es_ES.UTF-8');
setlocale(LC_CTYPE, 'es_ES.UTF-8');


// =======================================================
// 4. CONFIGURACIÓN DEL TEMA
// =======================================================
$tema_recibido = $_COOKIE['tema_preferido'] ?? 'claro';
$tema_seguro = ($tema_recibido === 'oscuro') ? 'oscuro' : 'claro';
define('_TEMA_', $tema_seguro);


// =======================================================
// 5. CONSTANTES DE MÓDULOS
// =======================================================
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
const _MD_ARTICULOS_INVENTARIO_ = 15;
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
const _MD_CATEGORIA_CAT_ = 103;
const _MD_ESTADO_FISICO_ = 104;
const _MD_PARTICIPACIONES_ = 105;
const _MD_RESPALDO_ = 106;
const _MD_REPORTES_ = 107;
const _MD_HISTORIAL_ = 108;
const _MD_PERMISOS_ = 109;
const _MD_TASA_ = 110;
const _MD_MODULO_ = 112;



// =======================================================
// 6. CÓDIGOS DE ERROR
// =======================================================
const DUPLICATE_CEDULA = "001";
const DUPLICATE_EMAIL  = "002";
const INVALID_ID     = "003";
const DUPLICATE_NAME  = "004";
const DUPLICATE_PHONE  = "005";
const DUPLICATE  = "010";
const ASSOCIATES  = "006";
const VALIDATION = "007";
const ALREADY_ANNULLED = "008";
const EMPTY_SELECTION  = "009";
const DB_CONNECTION    = "500";