<?php

declare(strict_types=1);

$tipo = strtolower($argv[1] ?? 'ayuda');
$grupo = $argv[2] ?? null;
$extra = $argv[3] ?? null;

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

    case 'backend':
    case 'postman':
    case 'api':
        ejecutarBackendPostman($grupo);
        break;

    case 'rendimiento':
    case 'perf':
    case 'performance':
    case 'jmeter':
        ejecutarRendimientoJMeter($grupo, $extra ?: 'rendimiento', $esWindows);
        break;

    case 'carga':
    case 'load':
        ejecutarRendimientoJMeter($grupo, 'carga', $esWindows);
        break;

    case 'estres':
    case 'stress':
        ejecutarRendimientoJMeter($grupo, 'estres', $esWindows);
        break;

    case 'jmeter-gui':
    case 'jmeter:gui':
        abrirJMeterGui($grupo, $esWindows);
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

function comandoExiste(string $cmd): bool
{
    $test = (DIRECTORY_SEPARATOR === '\\') ? "where.exe $cmd 2>nul" : "command -v $cmd 2>/dev/null";
    exec($test, $output, $returnCode);
    return $returnCode === 0;
}

function ejecutarBackendPostman(?string $modulo): void
{
    echo "============================================================\n";
    echo "         EJECUTANDO PRUEBAS DE BACKEND (POSTMAN / NEWMAN)\n";
    echo "============================================================\n";

    $comandoNewman = null;
    $winNpmNewman = (DIRECTORY_SEPARATOR === '\\' && getenv('APPDATA'))
        ? getenv('APPDATA') . DIRECTORY_SEPARATOR . 'npm' . DIRECTORY_SEPARATOR . 'newman.cmd'
        : null;

    if (comandoExiste('newman')) {
        $comandoNewman = 'newman';
    } elseif ($winNpmNewman && file_exists($winNpmNewman)) {
        $comandoNewman = '"' . $winNpmNewman . '"';
    } elseif (comandoExiste('npx')) {
        // Verificar si npx tiene newman instalado localmente
        $testLocal = (DIRECTORY_SEPARATOR === '\\') ? 'where.exe .\\node_modules\\.bin\\newman.cmd 2>nul' : 'test -f ./node_modules/.bin/newman';
        exec($testLocal, $out, $ret);
        if ($ret === 0) {
            $comandoNewman = 'npx newman';
        }
    }

    if ($comandoNewman === null) {
        echo "[ADVERTENCIA] Newman no esta instalado en el sistema.\n";
        echo "Para ejecutar colecciones de Postman desde consola necesitas Newman.\n";
        echo "Instalalo con Node.js ejecutando en tu terminal:\n\n";
        echo "    npm install -g newman\n\n";
        echo "O abre la aplicacion de escritorio de Postman e importa tests/Backend/\n";
        echo "============================================================\n";
        exit(1);
    }

    $envFile = 'tests/Backend/Hockey-Cannibals Lara - Local Environment.postman_environment.json';
    if (!file_exists($envFile)) {
        echo "[ERROR] No se encontro el archivo de entorno: $envFile\n";
        exit(1);
    }

    $archivos = glob('tests/Backend/*.postman_collection.json');
    if (empty($archivos)) {
        echo "[ERROR] No se encontraron colecciones en tests/Backend/\n";
        exit(1);
    }

    if (!empty($modulo)) {
        $coincidencias = array_filter($archivos, function ($archivo) use ($modulo) {
            return stripos(basename($archivo), $modulo) !== false;
        });

        if (empty($coincidencias)) {
            echo "[ERROR] No se encontro ninguna coleccion de Postman para el modulo: $modulo\n";
            echo "Colecciones disponibles en tests/Backend/:\n";
            foreach ($archivos as $a) {
                echo "  - " . basename($a) . "\n";
            }
            exit(1);
        }
        $archivos = array_values($coincidencias);
    }

    $total = count($archivos);
    echo "[INFO] Se ejecutaran $total coleccion(es) de Postman...\n\n";

    $errores = 0;
    foreach ($archivos as $i => $col) {
        $nombre = basename($col, '.postman_collection.json');
        echo "------------------------------------------------------------\n";
        echo " [" . ($i + 1) . "/$total] Ejecutando: $nombre\n";
        echo "------------------------------------------------------------\n";
        $cmd = sprintf('%s run "%s" -e "%s" --reporters cli', $comandoNewman, $col, $envFile);
        passthru($cmd, $status);
        if ($status !== 0) {
            $errores++;
        }
    }

    echo "============================================================\n";
    if ($errores > 0) {
        echo "[AVISO] Finalizado con $errores coleccion(es) con incidencias o fallos.\n";
    } else {
        echo "[OK] Todas las colecciones de Postman ejecutadas exitosamente.\n";
    }
    echo "============================================================\n";
    exit($errores > 0 ? 1 : 0);
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
    echo "  composer chrome                     -> Inicia ChromeDriver en puerto 9515\n";
    echo "  composer test:unit [modulo]         -> Ejecuta pruebas unitarias (ej: recuperacion, atletas)\n";
    echo "  composer test:integ [modulo]        -> Ejecuta pruebas de integracion (ej: atletas, cargos)\n";
    echo "  composer test:humo [grupo]          -> Ejecuta pruebas de humo\n";
    echo "  composer test:interfaz [grupo]      -> Ejecuta pruebas de interfaz (ej: recuperacion, atletas)\n";
    echo "  composer test:backend [modulo]      -> Ejecuta colecciones de Postman (Newman)\n";
    echo "  composer test:rendimiento [modulo]  -> Ejecuta pruebas de rendimiento base (JMeter)\n";
    echo "  composer test:carga [modulo]        -> Ejecuta pruebas de carga (JMeter)\n";
    echo "  composer test:estres [modulo]       -> Ejecuta pruebas de estres (JMeter)\n";
    echo "  composer jmeter:gui [modulo]        -> Abre el plan .jmx en JMeter GUI para editarlo\n";
    echo "============================================================\n";
}

function habilitarColoresConsola(): void
{
    if (function_exists('sapi_windows_vt100_support') && defined('STDOUT')) {
        @sapi_windows_vt100_support(STDOUT, true);
    }
}

function ansi(string $texto, string $codigo): string
{
    return "\033[{$codigo}m{$texto}\033[0m";
}

function localizarJMeter(bool $esWindows): ?string
{
    $candidatosCmd = $esWindows ? ['jmeter.bat', 'jmeter'] : ['jmeter'];
    foreach ($candidatosCmd as $cmd) {
        if (comandoExiste($cmd)) {
            return $cmd;
        }
    }

    $rutas = [];
    $jmeterHome = getenv('JMETER_HOME');
    if (!empty($jmeterHome)) {
        $rutas[] = rtrim($jmeterHome, '\\/') . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . ($esWindows ? 'jmeter.bat' : 'jmeter');
    }

    if ($esWindows) {
        $patrones = [
            'bin/jmeter/bin/jmeter.bat',
            'C:/apache-jmeter*/bin/jmeter.bat',
            'C:/tools/apache-jmeter*/bin/jmeter.bat',
            'C:/Program Files/apache-jmeter*/bin/jmeter.bat',
        ];
        $home = getenv('USERPROFILE');
        if (!empty($home)) {
            $patrones[] = str_replace('\\', '/', $home) . '/apache-jmeter*/bin/jmeter.bat';
            $patrones[] = str_replace('\\', '/', $home) . '/Downloads/apache-jmeter*/bin/jmeter.bat';
        }
        foreach ($patrones as $patron) {
            $encontrados = glob($patron);
            if (!empty($encontrados)) {
                rsort($encontrados);
                $rutas[] = $encontrados[0];
            }
        }
    }

    foreach ($rutas as $ruta) {
        if (file_exists($ruta)) {
            return '"' . str_replace('/', DIRECTORY_SEPARATOR, realpath($ruta) ?: $ruta) . '"';
        }
    }

    return null;
}

function normalizarClaveModulo(string $texto): string
{
    $texto = strtolower(trim($texto));
    $reemplazos = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        '_' => '', '-' => '', ' ' => '',
    ];
    return strtr($texto, $reemplazos);
}

function buscarPlanesJMeter(?string $modulo): array
{
    $planes = glob('tests/Performance/*/*.jmx') ?: [];
    sort($planes);

    if (empty($planes)) {
        return [];
    }

    if (empty($modulo)) {
        return $planes;
    }

    // Alias amigables para modulos comunes (ej: login / sesion -> Inicio)
    $alias = [
        'login'        => 'inicio',
        'sesion'       => 'inicio',
        'iniciodesesion' => 'inicio',
        'iniciosesion' => 'inicio',
    ];
    $buscado = normalizarClaveModulo($modulo);
    if (isset($alias[$buscado])) {
        $buscado = $alias[$buscado];
    }

    $coincidencias = array_filter($planes, function (string $archivo) use ($buscado): bool {
        $carpeta = normalizarClaveModulo(basename(dirname($archivo)));
        $nombre = normalizarClaveModulo(basename($archivo, '.jmx'));
        return $carpeta === $buscado
            || $nombre === $buscado
            || str_contains($carpeta, $buscado)
            || str_contains($nombre, $buscado);
    });

    return array_values($coincidencias);
}

function obtenerPerfilJMeter(string $perfilRaw): array
{
    $perfil = strtolower($perfilRaw);
    $mapa = [
        'rendimiento' => [
            'nombre'        => 'RENDIMIENTO (Baseline)',
            'clave'         => 'rendimiento',
            'usuarios'      => 10,
            'rampup'        => 10,
            'duracion'      => 30,
            'think_time'    => 200,
            'think_jitter'  => 100,
            'sla_error_pct' => 1.0,
            'sla_p95_ms'    => 1500,
        ],
        'carga' => [
            'nombre'        => 'CARGA (Load Test)',
            'clave'         => 'carga',
            'usuarios'      => 35,
            'rampup'        => 20,
            'duracion'      => 60,
            'think_time'    => 150,
            'think_jitter'  => 100,
            'sla_error_pct' => 2.0,
            'sla_p95_ms'    => 2500,
        ],
        'estres' => [
            'nombre'        => 'ESTRES (Stress Test)',
            'clave'         => 'estres',
            'usuarios'      => 100,
            'rampup'        => 30,
            'duracion'      => 60,
            'think_time'    => 0,
            'think_jitter'  => 50,
            'sla_error_pct' => 10.0,
            'sla_p95_ms'    => 5000,
        ],
    ];

    if ($perfil === 'load') {
        return $mapa['carga'];
    }
    if ($perfil === 'stress') {
        return $mapa['estres'];
    }

    return $mapa[$perfil] ?? $mapa['rendimiento'];
}

function localizarJava(bool $esWindows): ?string
{
    $bin = $esWindows ? 'java.exe' : 'java';
    if (comandoExiste('java')) {
        return 'java';
    }

    $rutas = [];
    $javaHome = getenv('JAVA_HOME');
    if (!empty($javaHome)) {
        $rutas[] = rtrim($javaHome, '\\/') . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $bin;
    }

    if ($esWindows) {
        $patrones = [
            'C:/tools/jdk*/bin/java.exe',
            'C:/Program Files/Eclipse Adoptium/jdk*/bin/java.exe',
            'C:/Program Files/Java/jdk*/bin/java.exe',
        ];
        $home = getenv('USERPROFILE');
        if (!empty($home)) {
            $patrones[] = str_replace('\\', '/', $home) . '/tools/jdk*/bin/java.exe';
        }
        foreach ($patrones as $patron) {
            $encontrados = glob($patron);
            if (!empty($encontrados)) {
                rsort($encontrados);
                $rutas[] = $encontrados[0];
            }
        }
    }

    foreach ($rutas as $ruta) {
        if (file_exists($ruta)) {
            $rutaAbs = realpath($ruta) ?: $ruta;
            $binDir = dirname($rutaAbs);
            $homeDir = dirname($binDir);
            putenv('JAVA_HOME=' . $homeDir);
            putenv('PATH=' . $binDir . PATH_SEPARATOR . (getenv('PATH') ?: ''));
            return $rutaAbs;
        }
    }

    return null;
}

function verificarRequisitosJMeter(bool $esWindows): string
{
    if (localizarJava($esWindows) === null) {
        echo ansi("[ERROR] Java (JDK 8/11/17/21) no esta instalado o no figura en el PATH.\n", '1;31');
        echo "Apache JMeter requiere Java para ejecutarse.\n";
        echo "Instalalo (por ejemplo Eclipse Temurin JDK 17/21) y verifica con: " . ansi("java -version\n", '1;36');
        exit(1);
    }

    $binJMeter = localizarJMeter($esWindows);
    if ($binJMeter === null) {
        echo ansi("[ADVERTENCIA] Apache JMeter no esta instalado o no se encontro en el sistema.\n", '1;33');
        echo "Para ejecutar pruebas de rendimiento desde consola:\n\n";
        echo "  1. Descarga Apache JMeter (Binaries .zip) desde: https://jmeter.apache.org/download_jmeter.cgi\n";
        echo "  2. Descomprimelo en: " . ansi("C:\\apache-jmeter", '1;36') . " (o define la variable " . ansi("JMETER_HOME", '1;36') . ")\n";
        echo "  3. Agrega " . ansi("C:\\apache-jmeter\\bin", '1;36') . " al PATH del sistema (opcional, se autodetecta).\n\n";
        echo "Luego vuelve a ejecutar: " . ansi("composer test:rendimiento equipos\n", '1;32');
        exit(1);
    }

    return $binJMeter;
}

function abrirJMeterGui(?string $modulo, bool $esWindows): void
{
    habilitarColoresConsola();
    $binJMeter = verificarRequisitosJMeter($esWindows);
    $planes = buscarPlanesJMeter($modulo);

    if (empty($planes)) {
        echo ansi("[ERROR] No se encontro ningun plan .jmx" . ($modulo ? " para el modulo: $modulo" : '') . "\n", '1;31');
        exit(1);
    }

    $plan = realpath($planes[0]) ?: $planes[0];
    echo ansi("====================================================================\n", '1;36');
    echo ansi("               ABRIENDO PLAN EN APACHE JMETER (GUI)\n", '1;36');
    echo ansi("====================================================================\n", '1;36');
    echo "  Plan: " . ansi($plan, '1;37') . "\n";
    echo "  Nota: Usa la GUI solo para disenar/editar el plan. Ejecuta las\n";
    echo "        pruebas reales con composer test:rendimiento | carga | estres\n";

    $props = realpath('tests/Performance/config/local.properties');
    $argProps = $props ? ' -q "' . $props . '"' : '';

    if ($esWindows) {
        pclose(popen('start "Apache JMeter GUI" ' . $binJMeter . ' -t "' . $plan . '"' . $argProps, 'r'));
    } else {
        exec($binJMeter . ' -t "' . $plan . '"' . $argProps . ' > /dev/null 2>&1 &');
    }

    echo ansi("[OK] JMeter GUI lanzado.\n", '1;32');
    exit(0);
}

function ejecutarRendimientoJMeter(?string $moduloArg, string $perfilArg, bool $esWindows): void
{
    habilitarColoresConsola();

    // Permite: composer test:rendimiento carga  (sin modulo, ejecuta todos con perfil carga)
    $perfilesValidos = ['rendimiento', 'carga', 'estres', 'load', 'stress'];
    if ($moduloArg !== null && in_array(strtolower($moduloArg), $perfilesValidos, true) && $perfilArg === 'rendimiento') {
        $perfilArg = $moduloArg;
        $moduloArg = null;
    }

    $perfil = obtenerPerfilJMeter($perfilArg);

    if (getenv('JM_USUARIOS')) {
        $perfil['usuarios'] = max(1, (int)getenv('JM_USUARIOS'));
    }
    if (getenv('JM_RAMPUP')) {
        $perfil['rampup'] = max(1, (int)getenv('JM_RAMPUP'));
    }
    if (getenv('JM_DURACION')) {
        $perfil['duracion'] = max(5, (int)getenv('JM_DURACION'));
    }

    echo ansi("================================================================================\n", '1;36');
    echo ansi("     PRUEBAS DE RENDIMIENTO CON APACHE JMETER - " . $perfil['nombre'] . "\n", '1;36');
    echo ansi("================================================================================\n", '1;36');

    $binJMeter = verificarRequisitosJMeter($esWindows);
    $planes = buscarPlanesJMeter($moduloArg);

    if (empty($planes)) {
        echo ansi("[ERROR] No se encontro ningun plan JMeter (.jmx)" . ($moduloArg ? " para el modulo: $moduloArg" : '') . "\n", '1;31');
        $disponibles = glob('tests/Performance/*/*.jmx') ?: [];
        if (!empty($disponibles)) {
            echo "Modulos disponibles en tests/Performance/:\n";
            foreach ($disponibles as $d) {
                echo "  - " . ansi(basename(dirname($d)), '1;33') . " (" . basename($d) . ")\n";
            }
        }
        exit(1);
    }

    $propsFile = 'tests/Performance/config/local.properties';
    $argProps = file_exists($propsFile) ? sprintf(' -q "%s"', realpath($propsFile)) : '';

    $totalPlanes = count($planes);
    $huboFallos = false;

    foreach ($planes as $indice => $planRel) {
        $moduloNombre = basename(dirname($planRel));
        $planAbs = realpath($planRel) ?: $planRel;
        $marcaTiempo = date('Ymd_His');
        $carpetaSalida = 'tests/Performance/resultados/' . $moduloNombre . '_' . $perfil['clave'] . '_' . $marcaTiempo;

        if (!is_dir($carpetaSalida)) {
            mkdir($carpetaSalida, 0777, true);
        }

        $archivoJtl = $carpetaSalida . '/resultados.jtl';
        $carpetaHtml = $carpetaSalida . '/dashboard_html';
        $archivoLog = $carpetaSalida . '/jmeter.log';

        echo "\n" . ansi("┌──────────────────────────────────────────────────────────────────────────────┐\n", '0;36');
        echo ansi("│ ", '0;36') . ansi(sprintf("[%d/%d] Modulo: %-20s  Perfil: %-31s", $indice + 1, $totalPlanes, strtoupper($moduloNombre), $perfil['nombre']), '1;37') . ansi(" │\n", '0;36');
        echo ansi("│ ", '0;36') . sprintf("Usuarios: %-6d | Ramp-up: %3ds | Duracion: %3ds | Think Time: %4dms         ", $perfil['usuarios'], $perfil['rampup'], $perfil['duracion'], $perfil['think_time']) . ansi("│\n", '0;36');
        echo ansi("│ ", '0;36') . sprintf("SLA Objetivo: Error <= %-4.1f%%   | Latencia P95 <= %-5d ms                      ", $perfil['sla_error_pct'], $perfil['sla_p95_ms']) . ansi("│\n", '0;36');
        echo ansi("└──────────────────────────────────────────────────────────────────────────────┘\n", '0;36');

        $cmd = sprintf(
            '%s -n -t "%s"%s -l "%s" -j "%s" -e -o "%s" -Jusuarios=%d -Jrampup=%d -Jduracion=%d -Jthink_time_ms=%d -Jthink_jitter_ms=%d',
            $binJMeter,
            $planAbs,
            $argProps,
            str_replace('/', DIRECTORY_SEPARATOR, $archivoJtl),
            str_replace('/', DIRECTORY_SEPARATOR, $archivoLog),
            str_replace('/', DIRECTORY_SEPARATOR, $carpetaHtml),
            $perfil['usuarios'],
            $perfil['rampup'],
            $perfil['duracion'],
            $perfil['think_time'],
            $perfil['think_jitter']
        );

        echo ansi("  [EN VIVO] Ejecutando JMeter (actualizacion cada 5s)...\n", '0;90');
        $codigoSalida = ejecutarJMeterEnVivo($cmd);

        if (!file_exists($archivoJtl) || filesize($archivoJtl) === 0) {
            echo ansi("  [ERROR] JMeter finalizo (codigo $codigoSalida) sin generar resultados en $archivoJtl\n", '1;31');
            echo "  Revisa el log tecnico en: $archivoLog\n";
            $huboFallos = true;
            continue;
        }

        $cumpleSla = renderizarReporteConsolaJMeter($archivoJtl, $perfil);
        if ($codigoSalida !== 0 || !$cumpleSla) {
            $huboFallos = true;
        }

        $htmlAbs = realpath($carpetaHtml . '/index.html');
        if ($htmlAbs) {
            $urlFile = 'file:///' . str_replace('\\', '/', $htmlAbs);
            echo "\n  " . ansi("Dashboard HTML interactivo (Ctrl+Click):", '1;36') . "\n";
            echo "  " . ansi($urlFile, '4;34') . "\n";
        }
    }

    echo "\n" . ansi("================================================================================\n", '1;36');
    if ($huboFallos) {
        echo ansi("  [RESULTADO FINAL: FALLIDO] Uno o mas modulos superaron el SLA o tuvieron fallos.\n", '1;31');
    } else {
        echo ansi("  [RESULTADO FINAL: EXITOSO] Todas las pruebas de rendimiento cumplieron el SLA.\n", '1;32');
    }
    echo ansi("================================================================================\n", '1;36');
    exit($huboFallos ? 1 : 0);
}

function ejecutarJMeterEnVivo(string $cmd): int
{
    $descriptores = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $proceso = proc_open($cmd, $descriptores, $pipes);
    if (!is_resource($proceso)) {
        echo ansi("  [ERROR] No se pudo iniciar el proceso de JMeter.\n", '1;31');
        return 1;
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $bufferOut = '';
    $bufferErr = '';

    while (true) {
        $estado = proc_get_status($proceso);
        $lectura = [];
        if (!feof($pipes[1])) {
            $lectura[] = $pipes[1];
        }
        if (!feof($pipes[2])) {
            $lectura[] = $pipes[2];
        }

        if (empty($lectura)) {
            if (!$estado['running']) {
                break;
            }
            usleep(50000);
            continue;
        }

        $escritura = null;
        $excepciones = null;
        @stream_select($lectura, $escritura, $excepciones, 0, 200000);

        foreach ($lectura as $stream) {
            $chunk = fread($stream, 4096);
            if ($chunk === false || $chunk === '') {
                continue;
            }
            if ($stream === $pipes[1]) {
                $bufferOut .= $chunk;
                while (($pos = strpos($bufferOut, "\n")) !== false) {
                    $linea = rtrim(substr($bufferOut, 0, $pos), "\r\n");
                    $bufferOut = substr($bufferOut, $pos + 1);
                    imprimirLineaJMeter($linea);
                }
            } else {
                $bufferErr .= $chunk;
                while (($pos = strpos($bufferErr, "\n")) !== false) {
                    $linea = rtrim(substr($bufferErr, 0, $pos), "\r\n");
                    $bufferErr = substr($bufferErr, $pos + 1);
                    if ($linea !== '' && stripos($linea, 'WARNING:') === false) {
                        echo ansi("  [stderr] $linea\n", '0;33');
                    }
                }
            }
        }

        if (!$estado['running'] && feof($pipes[1]) && feof($pipes[2])) {
            break;
        }
    }

    if (trim($bufferOut) !== '') {
        imprimirLineaJMeter(trim($bufferOut));
    }

    fclose($pipes[1]);
    fclose($pipes[2]);
    return proc_close($proceso);
}

function imprimirLineaJMeter(string $linea): void
{
    $trim = trim($linea);
    if ($trim === '') {
        return;
    }

    if (preg_match('/^summary\s+[+=]/i', $trim)) {
        $tieneErrores = preg_match('/Err:\s*([1-9]\d*)/i', $trim) === 1;
        $color = $tieneErrores ? '1;33' : '0;32';
        echo "  " . ansi("▸ " . $trim, $color) . "\n";
    } elseif (stripos($trim, 'error') !== false || stripos($trim, 'exception') !== false) {
        echo "  " . ansi("✖ " . $trim, '1;31') . "\n";
    } elseif (stripos($trim, 'end of run') !== false) {
        echo "  " . ansi("✔ Ejecucion de JMeter finalizada. Generando metricas...", '1;36') . "\n";
    }
}

function calcularPercentil(array $valoresOrdenados, float $percentil): int
{
    $n = count($valoresOrdenados);
    if ($n === 0) {
        return 0;
    }
    $indice = (int)ceil(($percentil / 100.0) * $n) - 1;
    $indice = max(0, min($n - 1, $indice));
    return (int)$valoresOrdenados[$indice];
}

function renderizarBarraLatencia(int $valorMs, int $maxRefMs, int $ancho = 10): string
{
    $ref = max(1, $maxRefMs);
    $proporcion = min(1.0, $valorMs / $ref);
    $llenos = (int)round($proporcion * $ancho);
    $vacios = max(0, $ancho - $llenos);
    $barra = str_repeat('█', $llenos) . str_repeat('░', $vacios);

    if ($valorMs > $maxRefMs) {
        return ansi($barra, '1;31');
    }
    if ($valorMs > ($maxRefMs * 0.7)) {
        return ansi($barra, '1;33');
    }
    return ansi($barra, '0;32');
}

function renderizarReporteConsolaJMeter(string $archivoJtl, array $perfil): bool
{
    $handle = fopen($archivoJtl, 'r');
    if (!$handle) {
        echo ansi("  [ERROR] No se pudo leer $archivoJtl\n", '1;31');
        return false;
    }

    $encabezados = fgetcsv($handle);
    if (!$encabezados) {
        fclose($handle);
        return false;
    }

    $idx = array_flip($encabezados);
    $grupos = [];
    $todosTiempos = [];
    $totalPeticiones = 0;
    $totalOk = 0;
    $minTs = PHP_INT_MAX;
    $maxTs = 0;
    $muestrasError = [];

    while (($fila = fgetcsv($handle)) !== false) {
        if (count($fila) < 8) {
            continue;
        }
        $ts = (int)($fila[$idx['timeStamp'] ?? 0] ?? 0);
        $elapsed = (int)($fila[$idx['elapsed'] ?? 1] ?? 0);
        $label = (string)($fila[$idx['label'] ?? 2] ?? 'Desconocido');
        $success = strtolower((string)($fila[$idx['success'] ?? 7] ?? 'false')) === 'true';
        $failureMsg = (string)($fila[$idx['failureMessage'] ?? 10] ?? '');

        if (!isset($grupos[$label])) {
            $grupos[$label] = [
                'tiempos' => [],
                'ok'      => 0,
                'fail'    => 0,
                'min_ts'  => PHP_INT_MAX,
                'max_ts'  => 0,
            ];
        }

        $grupos[$label]['tiempos'][] = $elapsed;
        $grupos[$label]['min_ts'] = min($grupos[$label]['min_ts'], $ts);
        $grupos[$label]['max_ts'] = max($grupos[$label]['max_ts'], $ts + $elapsed);

        if ($success) {
            $grupos[$label]['ok']++;
        } else {
            $grupos[$label]['fail']++;
            if ($failureMsg !== '' && count($muestrasError) < 5) {
                $muestrasError[$label . ': ' . $failureMsg] = true;
            }
        }

        if (!str_starts_with($label, '[Setup]') && !str_starts_with($label, '[TearDown]')) {
            $todosTiempos[] = $elapsed;
            $totalPeticiones++;
            if ($success) {
                $totalOk++;
            }
            $minTs = min($minTs, $ts);
            $maxTs = max($maxTs, $ts + $elapsed);
        }
    }
    fclose($handle);

    echo "\n" . ansi("  RESUMEN VISUAL POR ENDPOINT (JMeter)\n", '1;37');
    $sep = "  +----------------------------------+-------+-------+-------+-------+-------+-------+------------+--------+";
    echo ansi($sep . "\n", '0;90');
    echo ansi(
        sprintf(
            "  | %-32s | %5s | %5s | %5s | %5s | %5s | %5s | %-10s | %-6s |\n",
            'Endpoint / Operacion',
            'Total',
            'Err%',
            'Prom',
            'Min',
            'Max',
            'P95',
            'Latencia',
            'Estado'
        ),
        '1;36'
    );
    echo ansi($sep . "\n", '0;90');

    foreach ($grupos as $label => $g) {
        $tiempos = $g['tiempos'];
        sort($tiempos);
        $total = count($tiempos);
        $errPct = $total > 0 ? ($g['fail'] / $total) * 100.0 : 0.0;
        $prom = $total > 0 ? (int)round(array_sum($tiempos) / $total) : 0;
        $min = $tiempos[0] ?? 0;
        $max = $tiempos[$total - 1] ?? 0;
        $p95 = calcularPercentil($tiempos, 95);

        $esAux = str_starts_with($label, '[Setup]') || str_starts_with($label, '[TearDown]');
        $okEndpoint = ($errPct <= $perfil['sla_error_pct']) && ($esAux || $p95 <= $perfil['sla_p95_ms']);
        if ($esAux && $g['fail'] > 0) {
            $okEndpoint = false;
        }

        $badge = $okEndpoint ? ansi(' PASS ', '1;42;30') : ansi(' FAIL ', '1;41;37');
        $barra = renderizarBarraLatencia($p95, $perfil['sla_p95_ms']);
        $nombreCorto = strlen($label) > 32 ? substr($label, 0, 29) . '...' : $label;
        $colorErr = $errPct > 0 ? '1;31' : '0;32';

        printf(
            "  | %-32s | %5d | %s | %5d | %5d | %5d | %5d | %s | %s |\n",
            $nombreCorto,
            $total,
            ansi(sprintf('%4.1f%%', $errPct), $colorErr),
            $prom,
            $min,
            $max,
            $p95,
            $barra,
            $badge
        );
    }
    echo ansi($sep . "\n", '0;90');

    sort($todosTiempos);
    $totalFail = $totalPeticiones - $totalOk;
    $errGlobal = $totalPeticiones > 0 ? ($totalFail / $totalPeticiones) * 100.0 : 100.0;
    $promGlobal = $totalPeticiones > 0 ? (int)round(array_sum($todosTiempos) / $totalPeticiones) : 0;
    $p90Global = calcularPercentil($todosTiempos, 90);
    $p95Global = calcularPercentil($todosTiempos, 95);
    $p99Global = calcularPercentil($todosTiempos, 99);
    $duracionSeg = ($maxTs > $minTs) ? max(0.1, ($maxTs - $minTs) / 1000.0) : 1.0;
    $rps = $totalPeticiones / $duracionSeg;

    $setupFallo = false;
    foreach ($grupos as $label => $g) {
        if (str_starts_with($label, '[Setup]') && $g['fail'] > 0) {
            $setupFallo = true;
        }
    }

    $cumpleSla = !$setupFallo
        && $totalPeticiones > 0
        && $errGlobal <= $perfil['sla_error_pct']
        && $p95Global <= $perfil['sla_p95_ms'];

    echo "\n  " . ansi("METRICAS GLOBALES (Escenario Principal)", '1;37') . "\n";
    echo sprintf(
        "  Peticiones: %s  |  Exitosas: %s  |  Fallidas: %s  |  Throughput: %s\n",
        ansi((string)$totalPeticiones, '1;37'),
        ansi((string)$totalOk, '1;32'),
        ansi((string)$totalFail . sprintf(' (%.2f%%)', $errGlobal), $totalFail > 0 ? '1;31' : '0;32'),
        ansi(sprintf('%.2f req/s', $rps), '1;36')
    );
    echo sprintf(
        "  Latencia -> Prom: %dms | P90: %dms | P95: %s | P99: %dms\n",
        $promGlobal,
        $p90Global,
        ansi($p95Global . 'ms', $p95Global <= $perfil['sla_p95_ms'] ? '1;32' : '1;31'),
        $p99Global
    );

    if (!empty($muestrasError)) {
        echo "\n  " . ansi("Detalle de errores detectados (primeros 5):", '1;33') . "\n";
        foreach (array_keys($muestrasError) as $msg) {
            echo "   - " . ansi($msg, '0;31') . "\n";
        }
    }

    return $cumpleSla;
}

