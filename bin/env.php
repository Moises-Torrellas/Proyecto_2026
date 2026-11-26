<?php

declare(strict_types=1);

$accion = strtolower($argv[1] ?? 'status');
$raiz = dirname(__DIR__);

chdir($raiz);

switch ($accion) {
    case 'testing':
    case 'test':
    case 'prueba':
    case 'pruebas':
        activarTesting();
        break;

    case 'local':
    case 'dev':
    case 'desarrollo':
        activarLocal();
        break;

    case 'status':
    case 'estado':
    default:
        mostrarEstado();
        break;
}

function activarTesting(): void
{
    if (!file_exists('.env.testing')) {
        echo "[ERROR] No se encontro el archivo .env.testing en la raiz del proyecto.\n";
        exit(1);
    }

    // Si no existe respaldo de local, respaldar el .env actual
    if (!file_exists('.env.local') && file_exists('.env')) {
        copy('.env', '.env.local');
    }

    copy('.env.testing', '.env');

    $env = @parse_ini_file('.env') ?: [];

    $recaptchaActivo = !empty($env['RECAPTCHA_ACTIVO']) && $env['RECAPTCHA_ACTIVO'] !== 'false';

    echo "============================================================\n";
    echo "  [OK] ENTORNO CAMBIADO A: TESTING (PRUEBAS)\n";
    echo "============================================================\n";
    echo "  - Archivo activo : .env (copiado desde .env.testing)\n";
    echo "  - BD Principal   : " . ($env['DB_NAME'] ?? 'No definida') . "\n";
    echo "  - BD Seguridad   : " . ($env['DB_NAME_SG'] ?? 'No definida') . "\n";
    echo "  - reCAPTCHA      : " . ($recaptchaActivo ? 'ACTIVADO' : 'DESACTIVADO') . "\n";
    echo "------------------------------------------------------------\n";
    echo "  Activo tanto para Apache (XAMPP/Navegador) como para consola.\n";
    echo "  Para volver a desarrollo usa: composer env:local\n";
    echo "============================================================\n";
}

function activarLocal(): void
{
    if (file_exists('.env.local')) {
        copy('.env.local', '.env');
    }

    $env = @parse_ini_file('.env') ?: [];

    $recaptchaActivo = !empty($env['RECAPTCHA_ACTIVO']) && $env['RECAPTCHA_ACTIVO'] !== 'false';

    echo "============================================================\n";
    echo "  [OK] ENTORNO CAMBIADO A: LOCAL (DESARROLLO)\n";
    echo "============================================================\n";
    echo "  - Archivo activo : .env (restaurado para desarrollo)\n";
    echo "  - BD Principal   : " . ($env['DB_NAME'] ?? 'No definida') . "\n";
    echo "  - BD Seguridad   : " . ($env['DB_NAME_SG'] ?? 'No definida') . "\n";
    echo "  - reCAPTCHA      : " . ($recaptchaActivo ? 'ACTIVADO' : 'DESACTIVADO') . "\n";
    echo "------------------------------------------------------------\n";
    echo "  Para cambiar a pruebas usa: composer env:testing\n";
    echo "============================================================\n";
}

function mostrarEstado(): void
{
    if (!file_exists('.env')) {
        echo "[AVISO] No existe el archivo .env actualmente.\n";
        return;
    }

    $env = @parse_ini_file('.env') ?: [];
    $modo = strtoupper($env['APP_ENV'] ?? 'LOCAL (DESARROLLO)');

    $recaptchaActivo = !empty($env['RECAPTCHA_ACTIVO']) && $env['RECAPTCHA_ACTIVO'] !== 'false';

    echo "============================================================\n";
    echo "                ESTADO DEL ENTORNO ACTUAL\n";
    echo "============================================================\n";
    echo "  - Entorno activo : $modo\n";
    echo "  - BD Principal   : " . ($env['DB_NAME'] ?? 'No definida') . "\n";
    echo "  - BD Seguridad   : " . ($env['DB_NAME_SG'] ?? 'No definida') . "\n";
    echo "  - reCAPTCHA      : " . ($recaptchaActivo ? 'ACTIVADO' : 'DESACTIVADO') . "\n";
    echo "============================================================\n";
    echo "  Comandos para alternar entorno:\n";
    echo "    composer env:testing   -> Cambia al entorno de pruebas\n";
    echo "    composer env:local     -> Vuelve al entorno de desarrollo\n";
    echo "============================================================\n";
}
