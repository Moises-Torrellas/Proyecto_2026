<?php

declare(strict_types=1);

$tipo = strtolower($argv[1] ?? 'ayuda');
$grupo = $argv[2] ?? null;

$esWindows = (DIRECTORY_SEPARATOR === '\\');
$binPhpunit = $esWindows ? 'vendor\\bin\\phpunit --colors=always' : 'vendor/bin/phpunit --colors=always';
$binCodecept = $esWindows ? 'vendor\\bin\\codecept --colors' : 'vendor/bin/codecept --colors';

switch ($tipo) {
    case 'chrome':
    case 'chromedriver':
        iniciarChromeDriver($esWindows);
        break;

    case 'unit':
    case 'unitaria':
    case 'unitarias':
        ejecutarUnitarias($binPhpunit, $grupo);
        break;

    case 'integ':
    case 'integracion':
    case 'integraciones':
        ejecutarIntegracion($binPhpunit, $grupo);
        break;

    case 'humo':
    case 'smoke':
        ejecutarHumo($binPhpunit, $grupo);
        break;

    case 'interfaz':
    case 'acep':
    case 'aceptacion':
    case 'ui':
        ejecutarInterfaz($binCodecept, $grupo);
        break;

    default:
        mostrarAyuda();
        break;
}

function iniciarChromeDriver(bool $esWindows): void
{
    $driverRelativo = 'tests/Acceptance/chromedriver-win64/chromedriver.exe';
    if (!file_exists($driverRelativo)) {
        echo "[ERROR] No se encontro el ejecutable en: $driverRelativo\n";
        exit(1);
    }

    echo "============================================================\n";
    echo "       INICIANDO CHROMEDRIVER EN PUERTO 9515...\n";
    echo "============================================================\n";

    if ($esWindows) {
        @exec('taskkill /F /IM chromedriver.exe 2>nul');
        $driverAbsoluto = str_replace('/', '\\', realpath($driverRelativo));
        pclose(popen('start "ChromeDriver Server (9515)" "' . $driverAbsoluto . '" --port=9515', 'r'));
    } else {
        @exec('pkill -f chromedriver 2>/dev/null');
        exec($driverRelativo . ' --port=9515 > /dev/null 2>&1 &');
    }

    echo "[OK] ChromeDriver iniciado en segundo plano en http://127.0.0.1:9515\n";
    echo "Ya puedes ejecutar tus pruebas de interfaz con: composer test:interfaz [grupo]\n";
    echo "============================================================\n";
}

function ejecutarUnitarias(string $bin, ?string $grupo): void
{
    echo "============================================================\n";
    echo "                EJECUTANDO PRUEBAS UNITARIAS\n";
    echo "============================================================\n";

    if (empty($grupo)) {
        echo "[INFO] Ejecutando toda la suite Unitaria...\n";
        passthru("$bin --testsuite Unit", $status);
    } else {
        $dir = resolverRuta('tests/Unit', $grupo);
        if ($dir !== null) {
            echo "[INFO] Ejecutando modulo: $dir\n";
            passthru("$bin $dir", $status);
        } else {
            echo "[INFO] Buscando por grupo: $grupo...\n";
            passthru("$bin --group $grupo", $status);
        }
    }
    exit($status ?? 0);
}

function ejecutarIntegracion(string $bin, ?string $grupo): void
{
    echo "============================================================\n";
    echo "              EJECUTANDO PRUEBAS DE INTEGRACION\n";
    echo "============================================================\n";

    if (empty($grupo)) {
        echo "[INFO] Ejecutando toda la suite de Integracion...\n";
        passthru("$bin --testsuite Integration", $status);
    } else {
        $dir = resolverRuta('tests/Integration', $grupo);
        if ($dir !== null) {
            echo "[INFO] Ejecutando modulo: $dir\n";
            passthru("$bin $dir", $status);
        } else {
            echo "[INFO] Buscando por grupo: $grupo...\n";
            passthru("$bin --group $grupo", $status);
        }
    }
    exit($status ?? 0);
}

function ejecutarHumo(string $bin, ?string $grupo): void
{
    echo "============================================================\n";
    echo "                 EJECUTANDO PRUEBAS DE HUMO\n";
    echo "============================================================\n";

    if (empty($grupo)) {
        echo "[INFO] Ejecutando suite de Humo (Smoke)...\n";
        passthru("$bin --testsuite Smoke", $status);
    } else {
        echo "[INFO] Ejecutando grupo de Humo: $grupo...\n";
        passthru("$bin --testsuite Smoke --group $grupo", $status);
    }
    exit($status ?? 0);
}

function ejecutarInterfaz(string $bin, ?string $grupo): void
{
    echo "============================================================\n";
    echo "                EJECUTANDO PRUEBAS DE INTERFAZ\n";
    echo "============================================================\n";

    if (empty($grupo)) {
        echo "[INFO] Ejecutando todas las pruebas de Aceptacion...\n";
        passthru("$bin run Acceptance", $status);
    } else {
        echo "[INFO] Ejecutando grupo de Interfaz: $grupo...\n";
        passthru("$bin run Acceptance --group $grupo", $status);
    }
    exit($status ?? 0);
}

function resolverRuta(string $baseDir, string $nombre): ?string
{
    $candidatos = [
        $baseDir . '/' . ucfirst($nombre),
        $baseDir . '/' . $nombre,
        $baseDir . '/' . strtolower($nombre),
        $baseDir . '/' . strtoupper($nombre)
    ];

    foreach ($candidatos as $c) {
        if (is_dir($c)) {
            return str_replace('/', DIRECTORY_SEPARATOR, $c);
        }
    }

    return null;
}

function mostrarAyuda(): void
{
    echo "============================================================\n";
    echo "        COMANDOS DISPONIBLES EN COMPOSER - PROYECTO 2026\n";
    echo "============================================================\n";
    echo "  composer chrome                -> Inicia ChromeDriver en puerto 9515\n";
    echo "  composer test:unit [modulo]    -> Ejecuta pruebas unitarias (ej: recuperacion, atletas)\n";
    echo "  composer test:integ [modulo]   -> Ejecuta pruebas de integracion (ej: atletas, cargos)\n";
    echo "  composer test:humo [grupo]     -> Ejecuta pruebas de humo\n";
    echo "  composer test:interfaz [grupo] -> Ejecuta pruebas de interfaz (ej: recuperacion, atletas)\n";
    echo "============================================================\n";
}

