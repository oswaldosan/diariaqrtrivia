<?php
session_start();

// ============================================================
// CONFIGURACIÓN DB — en producción usá variables de entorno (DB_HOST, DB_USER, DB_PASS, DB_NAME)
// ============================================================
$ldl_env = function ($key, $default) {
    $v = getenv($key);
    return ($v !== false && $v !== '') ? $v : $default;
};

/** Modo local: dev.enable o LDL_DEV=1 — evita socket Unix de "localhost" en Linux/WSL */
$ldl_dev_local = getenv('LDL_DEV') === '1' || file_exists(__DIR__ . '/dev.enable');
$dbHost = $ldl_env('DB_HOST', $ldl_dev_local ? '127.0.0.1' : 'localhost');
if ($ldl_dev_local && ($dbHost === 'localhost' || $dbHost === '::1')) {
    $dbHost = '127.0.0.1';
}
define('DB_HOST', $dbHost);
define('DB_USER', $ldl_env('DB_USER', 'root'));
define('DB_PASS', $ldl_env('DB_PASS', ''));
define('DB_NAME', $ldl_env('DB_NAME', 'test'));
define('DB_PORT', (int) $ldl_env('DB_PORT', '3306'));
unset($ldl_env);

define('TRIVIA_QUESTIONS_COUNT', 6);
define('APP_NAME', 'La Diaria Loto');

/** Vista previa (preview.php): dev.enable o LDL_DEV=1 */
define('LDL_PREVIEW_ALLOWED', $ldl_dev_local);
define('LDL_DEV_LOCAL', $ldl_dev_local);

// ============================================================
// CONEXIÓN DB
// ============================================================
function getDB() {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        } catch (mysqli_sql_exception $e) {
            if (LDL_DEV_LOCAL) {
                http_response_code(503);
                header('Content-Type: text/html; charset=UTF-8');
                $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>DB no disponible</title>';
                echo '<style>body{font-family:system-ui,sans-serif;max-width:36rem;margin:2rem auto;padding:0 1rem;line-height:1.5}code{background:#eee;padding:.1rem .3rem;border-radius:4px}</style></head><body>';
                echo '<h1>No se pudo conectar a MySQL</h1>';
                echo '<p><strong>Mensaje:</strong> ' . $msg . '</p>';
                echo '<p>En WSL/Linux, <code>localhost</code> usa socket Unix; con <code>dev.enable</code> se usa <code>127.0.0.1</code>.</p>';
                echo '<ul><li>MySQL en marcha y base creada.</li>';
                echo '<li>Ajustá <code>DB_*</code> en <code>config.php</code> o variables de entorno.</li>';
                echo '<li>Solo diseño: <a href="preview.php">preview.php</a></li></ul>';
                echo '</body></html>';
                exit;
            }
            throw $e;
        }
        if ($conn->connect_error) {
            die('Error de conexión DB: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// ============================================================
// HELPERS
// ============================================================
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function getPost($key, $default = '') {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function flash($type, $message) {
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash() {
    $flash = getFlash();
    if ($flash) {
        echo '<div class="alert alert-' . $flash['type'] . '">' . sanitize($flash['message']) . '</div>';
    }
}

/**
 * Carpeta URL de la app si no está en la raíz del dominio (ej. /subcarpeta).
 * En hosting: SetEnv LDL_BASE /ruta o vacío si está en la raíz.
 */
function ldl_base_path() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $override = getenv('LDL_BASE');
    if ($override !== false && $override !== '') {
        $cached = rtrim(str_replace('\\', '/', $override), '/');
        if ($cached !== '' && $cached[0] !== '/') {
            $cached = '/' . $cached;
        }
        return $cached;
    }
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
    $dir = dirname(str_replace('\\', '/', $script));
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        $cached = '';
    } else {
        $cached = rtrim($dir, '/');
    }
    return $cached;
}

function ldl_asset($relativePath) {
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $base = ldl_base_path();
    if ($base === '') {
        return '/' . $relativePath;
    }
    return $base . '/' . $relativePath;
}

function ldl_css_href() {
    $path = __DIR__ . '/assets/style.css';
    $v = is_readable($path) ? filemtime($path) : time();
    return ldl_asset('assets/style.css') . '?v=' . $v;
}

require_once __DIR__ . '/includes/decor.php';
