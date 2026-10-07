# 🔐 Auditoría Completa del Sistema de Inicio de Sesión

## Archivos Analizados

| Archivo | Rol |
|---------|-----|
| [index.php](file:///c:/xampp/htdocs/Proyecto_2026/public/index.php) | Entry point, configuración de sesión |
| [Inicio.php (Controlador)](file:///c:/xampp/htdocs/Proyecto_2026/app/controlador/Inicio.php) | Lógica de autenticación |
| [ModeloInicio.php](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/ModeloInicio.php) | Consultas BD del login |
| [Conexion.php](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/Conexion.php) | Singleton de conexión PDO |
| [Base.php](file:///c:/xampp/htdocs/Proyecto_2026/app/controlador/Base.php) | CSRF, validación, helpers |
| [inicio.js](file:///c:/xampp/htdocs/Proyecto_2026/public/js/inicio.js) | Frontend del login |
| [Inicio.php (Vista)](file:///c:/xampp/htdocs/Proyecto_2026/app/vista/Inicio.php) | HTML del formulario |
| [head.php](file:///c:/xampp/htdocs/Proyecto_2026/app/vista/complementos/head.php) | Meta tags, CSS/JS globales |
| [config.php](file:///c:/xampp/htdocs/Proyecto_2026/config/config.php) | Credenciales BD, constantes |
| [rutas.php](file:///c:/xampp/htdocs/Proyecto_2026/routes/rutas.php) | Router, cierre de sesión |
| [.htaccess](file:///c:/xampp/htdocs/Proyecto_2026/public/.htaccess) | Headers de seguridad |
| [ModeloRecuperacion.php](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/ModeloRecuperacion.php) | Recuperación de contraseña |

---

## 🚨 SECCIÓN 1: Vulnerabilidades Críticas Encontradas

### 🔴 VULN-01: Credenciales de BD en Texto Plano (Severidad: CRÍTICA)

**Archivo:** [config.php](file:///c:/xampp/htdocs/Proyecto_2026/config/config.php#L22-L32)

**Problema:** Las credenciales de la BD están hardcodeadas como constantes en el código fuente, ignorando completamente el `.env` que sí se carga. Además, el usuario es `root` sin contraseña.

```php
// ❌ ACTUAL - PELIGROSO
define('_DB_NAME_SG_', 'bds2');
define('_DB_HOST_SG_', 'localhost');
define('_DB_USER_SG_', 'root');
define('_DB_PASS_SG_', '');

define('_DB_NAME_', 'cannibalsbd2');
define('_DB_HOST_', 'localhost');
define('_DB_USER_', 'root');
define('_DB_PASS_', '');
```

```php
// ✅ CÓMO DEBERÍA SER
define('_DB_NAME_SG_', $_ENV['DB_NAME_SG'] ?? '');
define('_DB_HOST_SG_', $_ENV['DB_HOST_SG'] ?? 'localhost');
define('_DB_USER_SG_', $_ENV['DB_USER_SG'] ?? '');
define('_DB_PASS_SG_', $_ENV['DB_PASS_SG'] ?? '');

define('_DB_NAME_', $_ENV['DB_NAME'] ?? '');
define('_DB_HOST_', $_ENV['DB_HOST'] ?? 'localhost');
define('_DB_USER_', $_ENV['DB_USER'] ?? '');
define('_DB_PASS_', $_ENV['DB_PASS'] ?? '');
```

Y en tu `.env`:
```env
DB_NAME_SG=bds2
DB_HOST_SG=localhost
DB_USER_SG=app_user_sg
DB_PASS_SG=Cl4v3_S3gur4_SG!

DB_NAME=cannibalsbd2
DB_HOST=localhost
DB_USER=app_user
DB_PASS=Cl4v3_S3gur4_BD!
```

> [!CAUTION]
> **NUNCA** uses `root` sin contraseña en producción. Crea un usuario MySQL dedicado con los privilegios mínimos necesarios (SELECT, INSERT, UPDATE en las tablas que necesita).

---

### 🔴 VULN-02: reCAPTCHA Completamente Desactivado (Severidad: CRÍTICA)

**Archivos:** [Inicio.php controlador L68-97](file:///c:/xampp/htdocs/Proyecto_2026/app/controlador/Inicio.php#L68-L97), [inicio.js L37-41](file:///c:/xampp/htdocs/Proyecto_2026/public/js/inicio.js#L37-L41), [Vista Inicio.php L43-49](file:///c:/xampp/htdocs/Proyecto_2026/app/vista/Inicio.php#L43-L49)

**Problema:** El reCAPTCHA está **comentado** en los 3 archivos (backend, frontend y vista). Un atacante puede hacer ataques de fuerza bruta sin restricción.

```php
// ❌ ACTUAL - Completamente comentado en el controlador
/* $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    ...
    if (!$datos_recaptcha->success) { ... }
*/
```

```php
// ✅ CÓMO ACTIVARLO CORRECTAMENTE (controlador Inicio.php)
function ejecutarLogin($obj, $id_modulo, $bitacoraObj): void
{
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
    $recaptcha_secret = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';

    if (empty($recaptcha_response)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'accion' => 'error',
            'resultado' => 0,
            'mensaje' => 'Por favor, completa el CAPTCHA.'
        ]);
        exit();
    }

    // Validar con timeout y verificación SSL
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://www.google.com/recaptcha/api/siteverify',
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => http_build_query([
            'secret' => $recaptcha_secret,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,        // Timeout de 5 segundos
        CURLOPT_SSL_VERIFYPEER => true // Verificar SSL
    ]);
    $respuesta_curl = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$respuesta_curl) {
        // Si Google no responde, permitir login pero registrar alerta
        logs('Inicio', 'reCAPTCHA API no disponible', 'ejecutarLogin');
    } else {
        $datos_recaptcha = json_decode($respuesta_curl);
        if (!$datos_recaptcha->success) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'accion' => 'error',
                'resultado' => 0,
                'mensaje' => 'Validación de CAPTCHA fallida.'
            ]);
            exit();
        }
    }

    // ... resto del login
}
```

---

### 🔴 VULN-03: Enumeración de Usuarios (Severidad: ALTA)

**Archivo:** [ModeloInicio.php L44-46](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/ModeloInicio.php#L44-L46)

**Problema:** El sistema revela si una cédula existe o no en la BD, permitiendo a un atacante mapear todos los usuarios válidos.

```php
// ❌ ACTUAL - Revela que la cédula no existe
if (!$resultado) {
    return ['accion' => 'inicio', 'resultado' => 2, 'mensaje' => 'La cédula no existe'];
}
```

```php
// ✅ CORREGIDO - Mensaje genérico que NO revela información
if (!$resultado) {
    // Ejecutar password_verify con un hash dummy para igualar el tiempo de respuesta
    // Esto previene ataques de timing
    password_verify('dummy', '$2y$10$dummyhashfortimingatttackprevention12345678');
    return [
        'accion' => 'inicio',
        'resultado' => 0,
        'mensaje' => 'Las credenciales proporcionadas no son correctas.'
    ];
}

// También cambiar el mensaje de contraseña incorrecta (línea 67):
return [
    'accion' => 'inicio',
    'resultado' => 0,
    'mensaje' => 'Las credenciales proporcionadas no son correctas.'
];
```

> [!IMPORTANT]
> El mismo cambio debe aplicarse en [ModeloRecuperacion.php L63](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/ModeloRecuperacion.php#L63) donde dice `'Cedula no encontrada.'` — debería decir algo como `'Si la cédula está registrada, recibirás un correo.'`.

---

### 🔴 VULN-04: Sin Rate Limiting por IP (Severidad: ALTA)

**Problema:** Solo existe un bloqueo por usuario (3 intentos), pero NO hay limitación por dirección IP. Un atacante puede probar contraseñas con **diferentes cédulas** sin límite.

```php
// ✅ NUEVA CLASE: Rate Limiter basado en archivos (sin Redis)
// Crear archivo: app/servicios/RateLimiter.php

namespace App\servicios;

class RateLimiter
{
    private string $storageDir;
    private int $maxAttempts;
    private int $windowSeconds;

    public function __construct(int $maxAttempts = 20, int $windowSeconds = 60)
    {
        $this->storageDir = __DIR__ . '/../../storage/rate_limits/';
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0700, true);
        }
    }

    public function isLimited(string $ip): bool
    {
        $file = $this->getFilePath($ip);
        $this->cleanup($file);

        $attempts = $this->getAttempts($file);
        return count($attempts) >= $this->maxAttempts;
    }

    public function hit(string $ip): void
    {
        $file = $this->getFilePath($ip);
        $attempts = $this->getAttempts($file);
        $attempts[] = time();
        file_put_contents($file, json_encode($attempts), LOCK_EX);
    }

    public function getRemainingSeconds(string $ip): int
    {
        $file = $this->getFilePath($ip);
        $attempts = $this->getAttempts($file);
        if (empty($attempts)) return 0;

        $oldest = min($attempts);
        $remaining = ($oldest + $this->windowSeconds) - time();
        return max(0, $remaining);
    }

    private function getFilePath(string $ip): string
    {
        return $this->storageDir . md5($ip) . '.json';
    }

    private function getAttempts(string $file): array
    {
        if (!file_exists($file)) return [];

        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) return [];

        $now = time();
        // Solo conservar intentos dentro de la ventana de tiempo
        return array_values(array_filter($data, fn($t) => ($now - $t) < $this->windowSeconds));
    }

    private function cleanup(string $file): void
    {
        $attempts = $this->getAttempts($file);
        file_put_contents($file, json_encode($attempts), LOCK_EX);
    }
}
```

```php
// ✅ USO EN el controlador Inicio.php, dentro de ejecutarLogin():
$limiter = new \App\servicios\RateLimiter(20, 60); // 20 intentos por minuto por IP
$ip = $_SERVER['REMOTE_ADDR'];

if ($limiter->isLimited($ip)) {
    $segundos = $limiter->getRemainingSeconds($ip);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'accion' => 'error',
        'resultado' => 0,
        'mensaje' => "Demasiados intentos. Intente en {$segundos} segundos."
    ]);
    exit();
}

// Registrar intento ANTES de verificar credenciales
$limiter->hit($ip);
```

> [!TIP]
> **Para 1000 req/s:** En producción, reemplaza este rate limiter basado en archivos con **Redis** usando `INCR` + `EXPIRE`. Un solo Redis maneja 100K+ operaciones/segundo.

---

### 🔴 VULN-05: Sesión No se Regenera Tras Login (Severidad: ALTA)

**Archivo:** [Inicio.php controlador L110-132](file:///c:/xampp/htdocs/Proyecto_2026/app/controlador/Inicio.php#L110-L132)

**Problema:** Después de un login exitoso, el ID de sesión NO se regenera. Esto permite ataques de **Session Fixation** (un atacante pre-fija un session ID y espera a que el usuario inicie sesión con él).

```php
// ❌ ACTUAL - Se crean variables de sesión sin regenerar el ID
if (isset($respuesta['resultado']) && $respuesta['resultado'] == 1) {
    $_SESSION['id'] = $respuesta['datos']['idUsuario'];
    // ...
}
```

```php
// ✅ CORREGIDO - Regenerar ID de sesión antes de almacenar datos
if (isset($respuesta['resultado']) && $respuesta['resultado'] == 1) {
    // CRÍTICO: Prevenir Session Fixation
    session_regenerate_id(true);

    // Ahora sí almacenar datos del usuario
    $_SESSION['id']        = $respuesta['datos']['idUsuario'];
    $_SESSION['rol']       = $respuesta['datos']['nombre_rol'];
    $_SESSION['nombre']    = $respuesta['datos']['nombreUsuario'];
    $_SESSION['apellido']  = $respuesta['datos']['apellidoUsuario'];
    $_SESSION['telefono']  = $respuesta['datos']['telefonoUsuario'];
    $_SESSION['correo']    = $respuesta['datos']['correo'];
    $_SESSION['cedula']    = $respuesta['datos']['cedulaUsuario'];
    $_SESSION['nivel_rol'] = (int)$respuesta['datos']['nivel_rol'];
    $_SESSION['foto']      = $respuesta['datos']['foto'];
    
    // Metadata de seguridad
    $_SESSION['ip']         = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    // ...
}
```

---

### 🔴 VULN-06: Cookie de Sesión sin flag `Secure` ni `SameSite` (Severidad: ALTA)

**Archivo:** [index.php L2-6](file:///c:/xampp/htdocs/Proyecto_2026/public/index.php#L2-L6)

```php
// ❌ ACTUAL
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_name('SISTEMA_CBS_SESSION');
session_start();
```

```php
// ✅ MEJORADO - Configuración completa de sesión segura
if (session_status() === PHP_SESSION_NONE) {
    // Seguridad de cookie
    ini_set('session.cookie_httponly', 1);    // Impide acceso por JS
    ini_set('session.cookie_secure', 1);      // Solo enviar por HTTPS
    ini_set('session.cookie_samesite', 'Strict'); // Prevenir CSRF
    ini_set('session.use_only_cookies', 1);   // Solo cookies, no URL
    ini_set('session.use_strict_mode', 1);    // Rechazar IDs no generados por el servidor
    
    // Duración y gestión
    ini_set('session.gc_maxlifetime', 1800);  // 30 minutos de inactividad
    ini_set('session.cookie_lifetime', 0);    // Cookie expira al cerrar navegador
    
    // Entropy / seguridad del ID
    ini_set('session.sid_length', 48);        // ID más largo = más seguro
    ini_set('session.sid_bits_per_character', 6); // Más bits por carácter
    
    session_name('__Host-CBS_SID');  // Prefijo __Host- fuerza Secure + Path=/
    session_start();
}
```

---

### 🟡 VULN-07: Código de Recuperación sin Expiración (Severidad: MEDIA)

**Archivo:** [ModeloRecuperacion.php L89](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/ModeloRecuperacion.php#L86-L101)

**Problema:** El código de verificación se almacena en `$_SESSION['codigo_verificacion']` sin timestamp, por lo que **nunca expira** (el correo dice 30 minutos, pero el código vive toda la sesión).

```php
// ❌ ACTUAL
$_SESSION['codigo_verificacion'] = $codigo;

// Comprobación sin expiración:
if ($this->codigo == $_SESSION['codigo_verificacion']) { ... }
```

```php
// ✅ CORREGIDO - Almacenar con timestamp y limitar intentos
// Al GENERAR el código:
$_SESSION['codigo_verificacion'] = $codigo;
$_SESSION['codigo_timestamp'] = time();
$_SESSION['codigo_intentos'] = 0;

// Al VERIFICAR el código:
public function ComprobarCodigo(): array
{
    try {
        // Verificar expiración (30 minutos)
        $timestamp = $_SESSION['codigo_timestamp'] ?? 0;
        if ((time() - $timestamp) > 1800) {
            unset($_SESSION['codigo_verificacion'], $_SESSION['codigo_timestamp'], $_SESSION['codigo_intentos']);
            return ['accion' => 'error', 'mensaje' => 'El código ha expirado. Solicita uno nuevo.'];
        }

        // Limitar intentos de verificación
        $_SESSION['codigo_intentos'] = ($_SESSION['codigo_intentos'] ?? 0) + 1;
        if ($_SESSION['codigo_intentos'] > 5) {
            unset($_SESSION['codigo_verificacion'], $_SESSION['codigo_timestamp'], $_SESSION['codigo_intentos']);
            return ['accion' => 'error', 'mensaje' => 'Demasiados intentos. Solicita un nuevo código.'];
        }

        // Comparación segura con hash_equals
        if (hash_equals($_SESSION['codigo_verificacion'], $this->codigo)) {
            unset($_SESSION['codigo_verificacion'], $_SESSION['codigo_timestamp'], $_SESSION['codigo_intentos']);
            $_SESSION['verificacion'] = true;
            return ['accion' => 'comprobarCodigo', 'mensaje' => 'Código Validado.'];
        } else {
            return ['accion' => 'error', 'mensaje' => 'El código no es correcto.'];
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        return ['accion' => 'error', 'mensaje' => $e->getMessage()];
    }
}
```

---

### 🟡 VULN-08: Comparación Débil del Código de Verificación (Severidad: MEDIA)

**Archivo:** [ModeloRecuperacion.php L89](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/ModeloRecuperacion.php#L89)

```php
// ❌ ACTUAL - Comparación loose (==) vulnerable a type juggling
if ($this->codigo == $_SESSION['codigo_verificacion'])

// ✅ CORREGIDO - Comparación estricta y timing-safe
if (hash_equals($_SESSION['codigo_verificacion'], $this->codigo))
```

---

### 🟡 VULN-09: HSTS Desactivado (Severidad: MEDIA)

**Archivo:** [.htaccess L17](file:///c:/xampp/htdocs/Proyecto_2026/public/.htaccess#L17)

```apache
# ❌ ACTUAL - Comentado
# Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# ✅ EN PRODUCCIÓN - Descomentar obligatoriamente
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

---

### 🟡 VULN-10: Error Verbose Expone Stack del Sistema (Severidad: MEDIA)

**Archivo:** [config.php L16](file:///c:/xampp/htdocs/Proyecto_2026/config/config.php#L16)

```php
// ❌ ACTUAL - Revela ruta completa del servidor
die("Error crítico: No se encontró el archivo .env en la ruta: " . $rutaEnv);
```

```php
// ✅ CORREGIDO
if (!file_exists($rutaEnv)) {
    error_log("FATAL: .env no encontrado en: " . $rutaEnv);
    http_response_code(500);
    die("Error interno del servidor. Contacte al administrador.");
}
```

---

### 🟡 VULN-11: Sin Expiración de Sesión por Inactividad (Severidad: MEDIA)

**Problema:** Una sesión vive indefinidamente mientras el navegador esté abierto. No hay validación de inactividad.

```php
// ✅ Agregar al inicio de index.php, DESPUÉS de session_start()
if (isset($_SESSION['last_activity'])) {
    $inactivo = time() - $_SESSION['last_activity'];
    if ($inactivo > 1800) { // 30 minutos de inactividad
        session_unset();
        session_destroy();
        header('Location: Inicio');
        exit();
    }
}
$_SESSION['last_activity'] = time();

// Validación de IP y User-Agent (protección contra session hijacking)
if (isset($_SESSION['ip']) && $_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
    session_unset();
    session_destroy();
    header('Location: Inicio');
    exit();
}
```

---

## ⚡ SECCIÓN 2: Optimización de Rendimiento para 1000 Login/seg

### 🏗️ Arquitectura Actual vs. Requerida

```
📊 ANÁLISIS DE BOTTLENECKS ACTUALES:

1. Sesiones en archivos del sistema (lento bajo concurrencia)
2. Conexión PDO creada por cada request
3. bcrypt cuesta ~100ms por verificación → máx ~10 logins/seg por core
4. Consulta de permisos es JOIN pesado en cada login
5. verificarEvento() se ejecuta DENTRO del login (bloquea)
6. Sin connection pooling
```

### 📌 OPT-01: Usar `PASSWORD_ARGON2ID` + Costo Calibrado

**Archivo:** [ModeloInicio.php L54](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/ModeloInicio.php#L54), [ModeloUsuarios.php L63](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/ModeloUsuarios.php#L63)

```php
// ❌ ACTUAL
$this->contraseña = password_hash($datos['contraseña'], PASSWORD_BCRYPT);

// ✅ MEJORADO - Argon2id con parámetros calibrados
// Argon2id es más resistente a ataques por GPU que bcrypt
$this->contraseña = password_hash($datos['contraseña'], PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,  // 64 MB
    'time_cost'   => 3,      // 3 iteraciones
    'threads'     => 2       // 2 hilos paralelos
]);
```

```php
// ✅ Rehash automático si el algoritmo cambia (ModeloInicio.php, después de password_verify exitoso)
if (password_verify($this->clave, $resultado['pass_hash'])) {
    // Rehash si el hash usa un algoritmo/costo viejo
    if (password_needs_rehash($resultado['pass_hash'], PASSWORD_ARGON2ID, [
        'memory_cost' => 65536, 'time_cost' => 3, 'threads' => 2
    ])) {
        $nuevoHash = password_hash($this->clave, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, 'time_cost' => 3, 'threads' => 2
        ]);
        $rehash = $conex->prepare("UPDATE usuarios SET pass_hash = :hash WHERE idUsuario = :id");
        $rehash->execute([':hash' => $nuevoHash, ':id' => $resultado['idUsuario']]);
    }
    // ... continuar con login exitoso
}
```

---

### 📌 OPT-02: Sesiones en Redis (Esencial para 1000 req/s)

```php
// ✅ Configurar en index.php o php.ini
// Las sesiones en archivos tienen locks que son el cuello de botella #1
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://127.0.0.1:6379?auth=tu_password_redis');

// Alternativa sin Redis: sesiones en la BD (menos rápido pero mejor que archivos)
// Usar: Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
```

```
📊 Comparación de rendimiento:
┌──────────────────┬──────────────┬───────────────┐
│ Storage          │ Reads/seg    │ Writes/seg    │
├──────────────────┼──────────────┼───────────────┤
│ Archivos (actual)│ ~500         │ ~200          │
│ MySQL            │ ~2,000       │ ~1,000        │
│ Redis            │ ~100,000+    │ ~100,000+     │
│ Memcached        │ ~80,000+     │ ~80,000+      │
└──────────────────┴──────────────┴───────────────┘
```

---

### 📌 OPT-03: Connection Pooling en la BD

**Archivo:** [Conexion.php](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/Conexion.php#L141-L157)

```php
// ✅ MEJORADO - Conexión con opciones de rendimiento
private static function crearConexion($host, $name, $user, $pass)
{
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 3,
        // Deshabilitar persistent connections con PDO vanilla
        PDO::ATTR_PERSISTENT         => true,  // Reutiliza conexiones
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        // Configurar el charset a nivel de conexión
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        return $pdo;
    } catch (PDOException $e) {
        error_log('Error de Conexion: ' . $e->getMessage());
        throw new Exception(DB_CONNECTION);
    }
}
```

> [!TIP]
> Para producción con 1000 req/s, instala **ProxySQL** o **MySQL Connection Pooling** para evitar que cada worker de PHP abra/cierre conexiones.

---

### 📌 OPT-04: Mover `verificarEvento` Fuera del Login

**Archivo:** [ModeloInicio.php L116-124](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/ModeloInicio.php#L116-L124)

**Problema:** `verificarEvento()` se ejecuta **sincrónicamente** dentro del login. Si tarda 200ms, cada login se ralentiza 200ms.

```php
// ❌ ACTUAL - Bloquea el response del login
$verificador = new \App\servicios\verificarEvento();
$verificador->procesar();
```

```php
// ✅ OPCIÓN A: Ejecutar DESPUÉS de enviar la respuesta
// Mover al controlador, DESPUÉS de echo json_encode()
header('Content-Type: application/json; charset=utf-8');
echo json_encode($respuestaFinal);

// Cerrar la conexión con el cliente
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request(); // Envía la respuesta al usuario
}

// Ahora ejecutar tareas post-login sin bloquear al usuario
if ($respuestaFinal['resultado'] == 1) {
    $verificador = new \App\servicios\verificarEvento();
    $verificador->procesar();
}
exit();
```

```php
// ✅ OPCIÓN B (Mejor): Cron Job separado cada 5 minutos
// No ejecutar verificarEvento dentro del login en absoluto
// Crear un cron: */5 * * * * php /ruta/al/proyecto/cron/verificar_eventos.php
```

---

### 📌 OPT-05: Índices en la Base de Datos

```sql
-- ✅ Verificar que existan estos índices para las queries del login
-- En la tabla usuarios:
CREATE INDEX idx_usuarios_cedula_estatus ON usuarios(cedulaUsuario, estatus);
CREATE INDEX idx_usuarios_id_rol ON usuarios(id_rol);

-- En la tabla permisos_rol (para la consulta de permisos):
CREATE INDEX idx_permisos_rol_lookup ON permisos_rol(id_rol, id_permiso);

-- En la tabla excepciones:
CREATE INDEX idx_excepciones_lookup ON excepciones(id_usuario, id_permiso);

-- En la tabla permisos:
CREATE INDEX idx_permisos_modulo ON permisos(id_modulo, estatus);
```

---

### 📌 OPT-06: Caché de Permisos

**Problema:** La consulta de permisos en [ModeloInicio.php L77-110](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/ModeloInicio.php#L77-L110) es un JOIN triple que se ejecuta en **cada login**. Para roles con nivel 1 o 2, los permisos son estáticos.

```php
// ✅ Cachear permisos de roles estáticos (niveles 1 y 2)
// En ModeloInicio.php, dentro de IniciarSesion()

$cacheKey = "permisos_rol_" . $resultado['id_rol'];
$cachePath = __DIR__ . '/../../storage/cache/' . md5($cacheKey) . '.json';

if (file_exists($cachePath) && (time() - filemtime($cachePath) < 3600)) {
    // Cache hit: permisos válidos por 1 hora
    $permisos = json_decode(file_get_contents($cachePath), true);
} else {
    // Cache miss: consultar BD y guardar
    // ... consulta actual de permisos ...
    
    // Guardar en cache
    if (!is_dir(dirname($cachePath))) mkdir(dirname($cachePath), 0700, true);
    file_put_contents($cachePath, json_encode($permisos), LOCK_EX);
}
```

> [!TIP]
> Con Redis: `$redis->setex($cacheKey, 3600, serialize($permisos));`

---

## 🛡️ SECCIÓN 3: Mejoras de Seguridad Adicionales

### SEC-01: Headers de Seguridad Completos

**Archivo:** [.htaccess](file:///c:/xampp/htdocs/Proyecto_2026/public/.htaccess)

```apache
<IfModule mod_headers.c>
    # Prevenir Clickjacking
    Header always set X-Frame-Options "DENY"
    
    # Prevenir MIME-sniffing
    Header always set X-Content-Type-Options "nosniff"
    
    # XSS Protection (legacy, pero no hace daño)
    Header always set X-XSS-Protection "1; mode=block"
    
    # HSTS (activar en producción)
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    
    # Referrer Policy
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Permissions Policy (deshabilitar APIs innecesarias)
    Header always set Permissions-Policy "camera=(), microphone=(), geolocation=(), payment=()"
    
    # CSP mejorado
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; frame-src 'self' https://www.google.com; connect-src 'self'; base-uri 'self'; form-action 'self';"
    
    # Prevenir que el sitio sea embebido en otro
    Header always set Cross-Origin-Opener-Policy "same-origin"
    Header always set Cross-Origin-Resource-Policy "same-origin"
</IfModule>
```

> [!WARNING]
> Se eliminó `'unsafe-eval'` del `script-src`. Si algún JS depende de `eval()`, se debería refactorizar ese JS en vez de permitir eval.

---

### SEC-02: Validación del Token CSRF Mejorada

**Archivo:** [Base.php L27](file:///c:/xampp/htdocs/Proyecto_2026/app/controlador/Base.php#L27)

```php
// ❌ ACTUAL - Token se regenera en cada carga de vista
$_SESSION['token'] = bin2hex(random_bytes(32));

// ✅ MEJORADO - Token con expiración y rotación controlada
function generarTokenCSRF(): string
{
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_tokens'][] = [
        'token' => $token,
        'created' => time()
    ];
    
    // Mantener máximo 5 tokens activos (para múltiples tabs)
    if (count($_SESSION['csrf_tokens']) > 5) {
        array_shift($_SESSION['csrf_tokens']);
    }
    
    return $token;
}

function validarTokenCSRF(string $tokenRecibido): bool
{
    if (empty($tokenRecibido) || empty($_SESSION['csrf_tokens'])) {
        return false;
    }
    
    foreach ($_SESSION['csrf_tokens'] as $key => $data) {
        // Token expira después de 30 minutos
        if ((time() - $data['created']) > 1800) {
            unset($_SESSION['csrf_tokens'][$key]);
            continue;
        }
        
        if (hash_equals($data['token'], $tokenRecibido)) {
            // Consumir el token (one-time use)
            unset($_SESSION['csrf_tokens'][$key]);
            return true;
        }
    }
    
    return false;
}
```

---

### SEC-03: Proteger el Constructor de ModeloInicio

**Archivo:** [ModeloInicio.php L12](file:///c:/xampp/htdocs/Proyecto_2026/app/modelo/ModeloInicio.php#L12)

```php
// ❌ ACTUAL - Constructor vacío que omite llamar al padre
public function __construct() {}

// ✅ CORREGIDO
public function __construct()
{
    // Si necesitas la conexión, llama al padre:
    // parent::__construct();
    // Si no la necesitas en constructor, documéntalo:
}
```

> [!NOTE]
> `Conexion` tiene un constructor `protected`, y `ModeloInicio` extiende `Conexion`. Saltar `parent::__construct()` funciona técnicamente porque las conexiones se crean por método estático, pero es una práctica frágil.

---

### SEC-04: Sanitizar Output en el Frontend

**Archivo:** [inicio.js L103](file:///c:/xampp/htdocs/Proyecto_2026/public/js/inicio.js#L103)

```javascript
// ❌ ACTUAL - Inyecta HTML directamente (posible XSS si el error viene del server)
muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + request + status + err);

// ✅ CORREGIDO
muestraMensaje("error", 2000, "Error", "Error de conexión. Intente nuevamente.");
// NUNCA mostrar detalles técnicos al usuario
```

---

## 📐 SECCIÓN 4: Arquitectura para 1000 Login/seg

### Configuración PHP-FPM Recomendada

```ini
; /etc/php/8.x/fpm/pool.d/www.conf
[www]
pm = dynamic
pm.max_children = 200          ; Procesos máximos
pm.start_servers = 50          ; Procesos al iniciar
pm.min_spare_servers = 20      ; Mínimo libre
pm.max_spare_servers = 80      ; Máximo libre
pm.max_requests = 1000         ; Reciclar después de 1000 requests

; OPcache (OBLIGATORIO)
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 64
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1
opcache.jit_buffer_size = 128M
opcache.jit = 1255
```

### Configuración MySQL Recomendada

```ini
[mysqld]
# Buffer pool (70-80% de la RAM disponible)
innodb_buffer_pool_size = 2G
innodb_buffer_pool_instances = 4

# Conexiones
max_connections = 500
thread_cache_size = 100

# Log de queries lentas
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 0.5

# Performance
innodb_flush_log_at_trx_commit = 2  ; Mejor rendimiento a costa de durabilidad
innodb_log_buffer_size = 64M
```

### Nginx como Reverse Proxy (reemplazar Apache)

```nginx
upstream php_backend {
    server unix:/var/run/php/php8.2-fpm.sock;
    keepalive 64;
}

server {
    listen 443 ssl http2;
    server_name tu-dominio.com;
    
    # Rate Limiting a nivel de Nginx (MUCHO más eficiente que PHP)
    limit_req_zone $binary_remote_addr zone=login:10m rate=10r/s;
    
    location /Inicio {
        limit_req zone=login burst=20 nodelay;
        limit_req_status 429;
        
        fastcgi_pass php_backend;
        include fastcgi_params;
    }
}
```

---

## 📋 SECCIÓN 5: Checklist de Implementación (Priorizado)

### 🔴 Prioridad URGENTE (Semana 1)
- [ ] Activar reCAPTCHA (VULN-02) — descomentar en los 3 archivos
- [ ] `session_regenerate_id(true)` después del login (VULN-05)
- [ ] Mover credenciales de BD al `.env` (VULN-01)
- [ ] Crear usuario MySQL dedicado (no `root`)
- [ ] Mensajes genéricos en login fallido (VULN-03)
- [ ] Agregar flags `Secure` y `SameSite` a la cookie de sesión (VULN-06)

### 🟡 Prioridad ALTA (Semana 2-3)
- [ ] Implementar Rate Limiting por IP (VULN-04)
- [ ] Agregar expiración al código de recuperación (VULN-07)
- [ ] Usar `hash_equals` en comparación del código (VULN-08)
- [ ] Activar HSTS en producción (VULN-09)
- [ ] Agregar timeout de sesión por inactividad (VULN-11)
- [ ] Eliminar mensajes de error verbosos (VULN-10)
- [ ] Mover `verificarEvento()` a cron o post-response (OPT-04)

### 🟢 Prioridad MEDIA (Mes 1-2)
- [ ] Migrar sesiones a Redis (OPT-02)
- [ ] Agregar índices de BD faltantes (OPT-05)
- [ ] Cachear permisos por rol (OPT-06)
- [ ] Headers de seguridad completos (SEC-01)
- [ ] Considerar migración a Nginx + PHP-FPM
- [ ] Evaluar migración a Argon2id (OPT-01)
- [ ] Mejorar sistema CSRF para multi-tab (SEC-02)

### 🔵 Largo Plazo (Para escalar a 1000 req/s)
- [ ] Nginx como reverse proxy con rate limiting nativo
- [ ] PHP-FPM con pool configurado
- [ ] MySQL tuning (buffer pool, conexiones, slow log)
- [ ] OPcache + JIT habilitado
- [ ] ProxySQL para connection pooling de BD
- [ ] Monitoreo con herramientas como New Relic o Prometheus
- [ ] Load testing con Apache Benchmark o k6

---

## 🧮 Estimación de Capacidad

```
┌─────────────────────────────────┬──────────────┬──────────────┐
│ Componente                      │ Actual       │ Optimizado   │
├─────────────────────────────────┼──────────────┼──────────────┤
│ password_verify (bcrypt)        │ ~100ms       │ ~80ms (arg.) │
│ Query usuario + permisos        │ ~5-15ms      │ ~1-3ms       │
│ verificarEvento (bloqueante)    │ ~50-200ms    │ 0ms (async)  │
│ Session write (archivo)         │ ~2-5ms       │ ~0.1ms Redis │
│ Total por login                 │ ~170-330ms   │ ~85-90ms     │
│ Workers PHP (estimado)          │ 10-20        │ 100-200      │
│ Logins/seg estimados            │ ~30-120      │ ~1,100-2,200 │
└─────────────────────────────────┴──────────────┴──────────────┘
```

> [!IMPORTANT]
> El bottleneck principal para alcanzar 1000 login/seg es el **hashing de contraseñas** (~80-100ms por verificación). Con 100 workers de PHP-FPM y Redis para sesiones, puedes alcanzar ~1,000-1,250 logins/seg en un servidor con 8+ cores.
