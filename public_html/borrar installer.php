<?php

/**
 * Web Installer for Laravel ERP Camiones (Robust Version)
 * 
 * Handles initial setup in shared hosting environments.
 * Resolves: .env directory conflict, missing .env.example fallback,
 * DB connection test, migration safety, and proper error reporting.
 * 
 * IMPORTANT: After installation, DELETE THIS FILE.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't leak errors to browser in production
ini_set('log_errors', 1);

// ── Configuration ────────────────────────────────────────────────────────────
$base_path     = realpath(__DIR__ . '/..') ?: dirname(__DIR__);
$env_path      = $base_path . '/.env';
$env_example   = $base_path . '/.env.example';
$artisan_path  = $base_path . '/artisan';

$required_php_version = '8.3.0';
$required_extensions = [
    'bcmath', 'ctype', 'fileinfo', 'hash', 'json', 'mbstring',
    'openssl', 'pcre', 'pdo', 'tokenizer', 'xml', 'pdo_mysql'
];
$required_functions = ['exec', 'shell_exec'];
$writable_paths = [
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];

// ── Helper Functions ─────────────────────────────────────────────────────────

function json_response($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error($message, $details = '') {
    json_response([
        'success' => false,
        'message' => $message,
        'details' => $details
    ]);
}

function json_ok($data = []) {
    json_response(array_merge(['success' => true], $data));
}

/**
 * Ensure a path is a file, not a directory.
 * If the path is a directory, recursively remove it first.
 */
function ensure_file_path($path) {
    if (is_dir($path)) {
        // Recursively delete the directory
        $it = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
        @rmdir($path);
        
        // If rmdir failed, try renaming
        if (is_dir($path)) {
            @rename($path, $path . '_backup_' . time());
        }
        
        // Final check
        if (is_dir($path)) {
            return false;
        }
    }
    return true;
}

/**
 * Create all required storage directories.
 */
function ensure_storage_dirs($base_path) {
    $dirs = [
        'storage/app/public',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs',
        'bootstrap/cache',
    ];
    foreach ($dirs as $dir) {
        $full = $base_path . '/' . $dir;
        if (!is_dir($full)) {
            @mkdir($full, 0755, true);
        }
    }
}

/**
 * Clear all cached configuration.
 */
function clear_cache($base_path) {
    $cache_files = [
        'bootstrap/cache/config.php',
        'bootstrap/cache/services.php',
        'bootstrap/cache/packages.php',
        'bootstrap/cache/routes-v7.php',
        'bootstrap/cache/events.php',
    ];
    foreach ($cache_files as $file) {
        $full = $base_path . '/' . $file;
        if (file_exists($full)) {
            @unlink($full);
        }
    }
}

/**
 * Test database connection with PDO directly (no Artisan needed).
 */
function test_db_connection($host, $port, $name, $user, $pass) {
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // Quick check: can we run a query?
        $stmt = $pdo->query('SELECT 1');
        $stmt->fetch();
        
        return ['success' => true, 'message' => 'Conexión exitosa a la base de datos.'];
    } catch (PDOException $e) {
        $code = $e->getCode();
        $msg = $e->getMessage();
        
        // Translate common errors to Spanish
        if ($code == 1045 || strpos($msg, 'Access denied') !== false) {
            return ['success' => false, 'message' => 'Acceso denegado: usuario o contraseña incorrectos.'];
        }
        if ($code == 1049 || strpos($msg, 'Unknown database') !== false) {
            return ['success' => false, 'message' => "La base de datos '{$name}' no existe. Cree la base de datos primero."];
        }
        if ($code == 2002 || strpos($msg, 'Connection refused') !== false) {
            return ['success' => false, 'message' => "No se puede conectar al servidor MySQL en {$host}:{$port}. Verifique host y puerto."];
        }
        
        return ['success' => false, 'message' => "Error de conexión: {$msg}"];
    }
}

/**
 * Build the .env file content from parameters.
 */
function build_env_content($params) {
    return implode("\n", [
        'APP_NAME="' . addslashes($params['app_name']) . '"',
        'APP_ENV=production',
        'APP_KEY=',
        'APP_DEBUG=false',
        'APP_URL=' . $params['app_url'],
        '',
        'DB_CONNECTION=mysql',
        'DB_HOST=' . $params['db_host'],
        'DB_PORT=' . $params['db_port'],
        'DB_DATABASE=' . $params['db_name'],
        'DB_USERNAME=' . $params['db_user'],
        'DB_PASSWORD="' . addslashes($params['db_pass']) . '"',
        '',
        'CACHE_STORE=file',
        'SESSION_DRIVER=database',
        'QUEUE_CONNECTION=sync',
        '',
        'ADMIN_EMAIL="' . $params['admin_email'] . '"',
        'ADMIN_PASSWORD="' . addslashes($params['admin_pass']) . '"',
        '',
    ]);
}

/**
 * Run an artisan command safely.
 */
function run_artisan($base_path, $command) {
    $artisan = $base_path . '/artisan';
    
    if (!file_exists($artisan)) {
        return ['success' => false, 'output' => 'Archivo artisan no encontrado en: ' . $base_path];
    }

    // Detect PHP binary
    $php = (defined('PHP_BINARY') && PHP_BINARY && str_contains(PHP_BINARY, 'php'))
        ? '"' . PHP_BINARY . '"'
        : 'php';

    // Read .env vars to inject via environment
    $env_vars = [];
    $env_file = $base_path . '/.env';
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') === false) continue;
            [$key, $val] = explode('=', $line, 2);
            $env_vars[trim($key)] = trim($val, " \"'");
        }
    }
    
    // Force critical settings for installer context
    $env_vars['CACHE_STORE'] = 'array';
    $env_vars['CACHE_DRIVER'] = 'array';
    $env_vars['SESSION_DRIVER'] = 'array';
    $env_vars['DB_CONNECTION'] = 'mysql';

    // Build putenv bridge
    $bridge = '';
    foreach ($env_vars as $k => $v) {
        $v = addslashes($v);
        $bridge .= "putenv('{$k}={$v}'); \$_ENV['{$k}']='{$v}'; \$_SERVER['{$k}']='{$v}'; ";
    }

    // Add --database=mysql flag for migrate/seed commands
    if (preg_match('/(migrate|db:seed)/', $command) && strpos($command, '--database') === false) {
        $command .= ' --database=mysql';
    }

    $escaped_base = escapeshellarg($base_path);
    $escaped_code = escapeshellarg($bridge . "chdir('{$base_path}'); require 'artisan';");
    $full_cmd = "cd {$escaped_base} && {$php} -r {$escaped_code} {$command} --no-interaction 2>&1";

    $output = [];
    $return_var = -1;
    exec($full_cmd, $output, $return_var);

    $output_str = implode("\n", $output);
    
    return [
        'success' => ($return_var === 0),
        'output'  => $output_str,
    ];
}

// ── Handle AJAX Actions ──────────────────────────────────────────────────────
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    // ── Step 1: Check Requirements ───────────────────────────────────────
    if ($action === 'check_requirements') {
        $results = [
            'php' => [
                'current'  => PHP_VERSION,
                'required' => $required_php_version,
                'status'   => version_compare(PHP_VERSION, $required_php_version, '>='),
            ],
            'extensions' => [],
            'functions'  => [],
            'permissions' => [],
        ];
        
        foreach ($required_extensions as $ext) {
            $results['extensions'][$ext] = extension_loaded($ext);
        }
        
        foreach ($required_functions as $func) {
            $results['functions'][$func] = function_exists($func) && !in_array($func, explode(',', ini_get('disable_functions')));
        }
        
        // Ensure storage dirs exist first
        ensure_storage_dirs($base_path);
        
        foreach ($writable_paths as $rel) {
            $full = $base_path . '/' . $rel;
            $results['permissions'][$rel] = is_dir($full) && is_writable($full);
        }

        json_response($results);
    }

    // ── Step 2a: Test DB Connection ──────────────────────────────────────
    if ($action === 'test_db') {
        $host = $_POST['db_host'] ?? '127.0.0.1';
        $port = $_POST['db_port'] ?? '3306';
        $name = $_POST['db_name'] ?? '';
        $user = $_POST['db_user'] ?? '';
        $pass = $_POST['db_pass'] ?? '';

        if (empty($name) || empty($user)) {
            json_error('Nombre de base de datos y usuario son obligatorios.');
        }

        $result = test_db_connection($host, $port, $name, $user, $pass);
        json_response($result);
    }

    // ── Step 2b: Setup .env ──────────────────────────────────────────────
    if ($action === 'setup_env') {
        $params = [
            'app_name'    => $_POST['app_name'] ?? 'ERP Camiones',
            'app_url'     => $_POST['app_url'] ?? 'http://localhost',
            'db_host'     => $_POST['db_host'] ?? '127.0.0.1',
            'db_port'     => $_POST['db_port'] ?? '3306',
            'db_name'     => $_POST['db_name'] ?? '',
            'db_user'     => $_POST['db_user'] ?? '',
            'db_pass'     => $_POST['db_pass'] ?? '',
            'admin_email' => $_POST['admin_email'] ?? 'admin@example.com',
            'admin_pass'  => $_POST['admin_pass'] ?? '',
        ];

        // Validate required fields
        if (empty($params['db_name']) || empty($params['db_user']) || empty($params['admin_pass'])) {
            json_error('Faltan datos requeridos: nombre DB, usuario DB y contraseña admin son obligatorios.');
        }

        if (strlen($params['admin_pass']) < 8) {
            json_error('La contraseña del administrador debe tener al menos 8 caracteres.');
        }

        // FIX CRITICAL: .env might be a directory — remove it first
        if (!ensure_file_path($env_path)) {
            json_error(
                'No se pudo eliminar el directorio .env existente.',
                'Acceda al servidor por FTP/SSH y elimine manualmente la carpeta .env del directorio raíz del proyecto.'
            );
        }

        // Delete existing .env file if any
        if (file_exists($env_path)) {
            @unlink($env_path);
        }

        // Clear all cached config BEFORE writing new .env
        clear_cache($base_path);

        // Build .env content from scratch (don't depend on .env.example)
        $env_content = build_env_content($params);

        if (!@file_put_contents($env_path, $env_content)) {
            // Try with a different approach
            $fp = @fopen($env_path, 'w');
            if ($fp) {
                fwrite($fp, $env_content);
                fclose($fp);
            } else {
                json_error(
                    'No se pudo escribir el archivo .env',
                    'Verifique que el directorio raíz del proyecto tenga permisos de escritura (755 o 775).'
                );
            }
        }
        
        // Verify file was written correctly
        if (!file_exists($env_path) || !is_file($env_path)) {
            json_error('El archivo .env no se creó correctamente. Puede haber un problema de permisos.');
        }

        $written = file_get_contents($env_path);
        if (strpos($written, $params['db_name']) === false) {
            json_error('El archivo .env fue creado pero no contiene los datos correctos.');
        }

        // Ensure storage directories exist
        ensure_storage_dirs($base_path);

        json_ok(['message' => 'Archivo .env creado correctamente.']);
    }

    // ── Step 3: Run Installation Steps ───────────────────────────────────
    if ($action === 'install') {
        $step = trim($_GET['step'] ?? '');
        
        // Normalize step ID: support ALL possible formats from any frontend version
        $step_aliases = [
            // New clean IDs
            'config_clear'   => 'config:clear',
            'key_generate'   => 'key:generate --force',
            'migrate'        => 'migrate --force',
            'seed'           => 'db:seed --class=ProductionSeeder --force',
            'storage_link'   => 'storage:link',
            'optimize'       => '__optimize__',
            
            // Legacy short aliases (old frontend versions)
            'key'            => 'key:generate --force',
            'link'           => 'storage:link',
            'config'         => 'config:clear',
            
            // Full artisan command strings (another old frontend format)
            'config:clear'                              => 'config:clear',
            'key:generate --force'                      => 'key:generate --force',
            'migrate --force'                           => 'migrate --force',
            'db:seed --class=ProductionSeeder --force'   => 'db:seed --class=ProductionSeeder --force',
            'storage:link'                              => 'storage:link',
        ];

        // Try to find the command for this step
        $command = $step_aliases[$step] ?? null;
        
        // If not found, check if it contains a known keyword
        if ($command === null) {
            if (stripos($step, 'config') !== false && stripos($step, 'clear') !== false) {
                $command = 'config:clear';
            } elseif (stripos($step, 'key') !== false) {
                $command = 'key:generate --force';
            } elseif (stripos($step, 'migrate') !== false) {
                $command = 'migrate --force';
            } elseif (stripos($step, 'seed') !== false) {
                $command = 'db:seed --class=ProductionSeeder --force';
            } elseif (stripos($step, 'link') !== false || stripos($step, 'storage') !== false) {
                $command = 'storage:link';
            } elseif (stripos($step, 'optim') !== false) {
                $command = '__optimize__';
            }
        }

        if ($command === null) {
            json_error('Paso de instalación no reconocido: ' . $step);
        }

        // Special case: optimize runs multiple commands
        if ($command === '__optimize__') {
            $results = [];
            foreach (['config:cache', 'route:cache', 'view:cache'] as $cmd) {
                $r = run_artisan($base_path, $cmd);
                $results[] = $cmd . ': ' . ($r['success'] ? 'OK' : $r['output']);
            }
            json_ok(['output' => implode("\n", $results)]);
        }

        // Special case: migrate with fresh option if tables exist partially
        if (stripos($command, 'migrate') !== false) {
            // First try normal migrate
            $res = run_artisan($base_path, 'migrate --force');
            
            if (!$res['success']) {
                // If it failed because tables already exist, try migrate:fresh
                if (strpos($res['output'], 'already exists') !== false || 
                    strpos($res['output'], 'Base table or view already exists') !== false) {
                    $res = run_artisan($base_path, 'migrate:fresh --force');
                    if ($res['success']) {
                        $res['output'] = "migrate:fresh ejecutado (tablas previas detectadas)\n" . $res['output'];
                    }
                }
            }
            
            json_response($res);
        }

        // Normal command
        $res = run_artisan($base_path, $command);
        json_response($res);
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador — ERP Camiones</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --primary: #6c63ff;
            --primary-dark: #5a52d5;
            --primary-light: #f0efff;
            --success: #10b981;
            --success-light: #d1fae5;
            --error: #ef4444;
            --error-light: #fee2e2;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.1), 0 4px 6px -4px rgba(0,0,0,.1);
        }
        
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        
        .installer-container {
            width: 100%;
            max-width: 680px;
        }
        
        .installer-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .installer-header h1 {
            color: #fff;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,.2);
        }
        
        .installer-header p {
            color: rgba(255,255,255,.8);
            font-size: 14px;
            margin-top: 8px;
        }
        
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        
        /* ── Navigation Steps ── */
        .step-nav {
            display: flex;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            padding: 0;
        }
        
        .step-nav-item {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-400);
            transition: all .3s;
            position: relative;
        }
        
        .step-nav-item.active {
            color: var(--primary);
            font-weight: 600;
        }
        
        .step-nav-item.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 20%;
            right: 20%;
            height: 3px;
            background: var(--primary);
            border-radius: 3px 3px 0 0;
        }
        
        .step-nav-item.done {
            color: var(--success);
        }
        
        .step-badge {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid currentColor;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .step-nav-item.done .step-badge::after {
            content: '✓';
        }
        
        /* ── Panels ── */
        .panel {
            padding: 32px;
            display: none;
        }
        
        .panel.visible {
            display: block;
            animation: fadeIn .3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .panel h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 24px;
        }
        
        /* ── Requirements ── */
        .req-group {
            margin-bottom: 20px;
        }
        
        .req-group-title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--gray-500);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .req-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 14px;
            color: var(--gray-700);
            transition: background .2s;
        }
        
        .req-item:hover {
            background: var(--gray-50);
        }
        
        .req-icon {
            margin-right: 10px;
            font-size: 16px;
        }
        
        /* ── Form ── */
        .form-section {
            margin-bottom: 28px;
            padding-bottom: 28px;
            border-bottom: 1px solid var(--gray-100);
        }
        
        .form-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .form-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 16px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .form-field {
            display: flex;
            flex-direction: column;
        }
        
        .form-field.full {
            grid-column: span 2;
        }
        
        .form-field label {
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-600);
            margin-bottom: 6px;
        }
        
        .form-field input {
            padding: 10px 14px;
            border: 1.5px solid var(--gray-200);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        
        .form-field input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(108,99,255,.15);
        }
        
        /* ── Buttons ── */
        .btn {
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: none;
            transition: all .2s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        
        .btn-primary:hover:not(:disabled) {
            background: var(--primary-dark);
            box-shadow: 0 4px 12px rgba(108,99,255,.4);
        }
        
        .btn-primary:disabled {
            opacity: .5;
            cursor: not-allowed;
        }
        
        .btn-ghost {
            background: transparent;
            color: var(--gray-500);
        }
        
        .btn-ghost:hover {
            color: var(--gray-700);
        }
        
        .btn-test {
            background: var(--gray-100);
            color: var(--gray-700);
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .btn-test:hover {
            background: var(--gray-200);
        }
        
        .btn-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 24px;
        }
        
        /* ── Progress ── */
        .progress-track {
            width: 100%;
            height: 6px;
            background: var(--gray-100);
            border-radius: 6px;
            margin-bottom: 24px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), #a78bfa);
            border-radius: 6px;
            width: 0%;
            transition: width .5s ease;
        }
        
        /* ── Log ── */
        .log-box {
            background: var(--gray-900);
            border-radius: 10px;
            padding: 16px;
            max-height: 240px;
            overflow-y: auto;
            font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
            font-size: 12px;
            line-height: 1.8;
        }
        
        .log-box::-webkit-scrollbar {
            width: 6px;
        }
        
        .log-box::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .log-box::-webkit-scrollbar-thumb {
            background: var(--gray-600);
            border-radius: 3px;
        }
        
        .log-entry {
            color: var(--gray-400);
        }
        
        .log-entry.ok {
            color: var(--success);
        }
        
        .log-entry.err {
            color: var(--error);
        }
        
        .log-entry.warn {
            color: var(--warning);
        }
        
        /* ── Success Panel ── */
        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--success-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .success-icon svg {
            width: 40px;
            height: 40px;
            color: var(--success);
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 13px;
            line-height: 1.6;
            margin: 20px 0;
        }
        
        .alert-warning {
            background: var(--warning-light);
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .alert-error {
            background: var(--error-light);
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .db-test-result {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-top: 12px;
            display: none;
        }
        
        .db-test-result.ok {
            background: var(--success-light);
            color: #065f46;
            display: block;
        }
        
        .db-test-result.err {
            background: var(--error-light);
            color: #991b1b;
            display: block;
        }
        
        .center { text-align: center; }
        
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-field.full { grid-column: span 1; }
            .step-nav-item span { display: none; }
        }
    </style>
</head>
<body>
<div class="installer-container">
    <div class="installer-header">
        <h1>🚛 Instalador ERP Camiones</h1>
        <p>Configura tu sistema en pocos pasos</p>
    </div>
    
    <div class="card">
        <!-- Step Navigation -->
        <div class="step-nav">
            <div class="step-nav-item active" id="nav-1">
                <div class="step-badge">1</div>
                <span>Requisitos</span>
            </div>
            <div class="step-nav-item" id="nav-2">
                <div class="step-badge">2</div>
                <span>Configuración</span>
            </div>
            <div class="step-nav-item" id="nav-3">
                <div class="step-badge">3</div>
                <span>Instalación</span>
            </div>
            <div class="step-nav-item" id="nav-4">
                <div class="step-badge">4</div>
                <span>Finalizar</span>
            </div>
        </div>

        <!-- STEP 1: Requirements -->
        <div class="panel visible" id="panel-1">
            <h2>Verificación del Sistema</h2>
            <div id="requirements-container">
                <div class="center" style="padding:20px;color:var(--gray-400)">⏳ Verificando requisitos...</div>
            </div>
            <div class="btn-row">
                <div></div>
                <button class="btn btn-primary" id="btn-next-1" disabled>Siguiente →</button>
            </div>
        </div>

        <!-- STEP 2: Configuration -->
        <div class="panel" id="panel-2">
            <h2>Configuración del Entorno</h2>
            <form id="config-form">
                <div class="form-section">
                    <h3>🏢 Aplicación</h3>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Nombre de la App</label>
                            <input type="text" name="app_name" value="ERP Camiones" required>
                        </div>
                        <div class="form-field">
                            <label>URL del Sitio</label>
                            <input type="url" name="app_url" id="field-app-url" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>🗄 Base de Datos</h3>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Host</label>
                            <input type="text" name="db_host" value="127.0.0.1" required>
                        </div>
                        <div class="form-field">
                            <label>Puerto</label>
                            <input type="text" name="db_port" value="3306" required>
                        </div>
                        <div class="form-field full">
                            <label>Nombre de la Base de Datos</label>
                            <input type="text" name="db_name" required placeholder="ej: erp_camiones">
                        </div>
                        <div class="form-field">
                            <label>Usuario</label>
                            <input type="text" name="db_user" required>
                        </div>
                        <div class="form-field">
                            <label>Contraseña</label>
                            <input type="password" name="db_pass">
                        </div>
                    </div>
                    <div style="margin-top:12px">
                        <button type="button" class="btn btn-test" id="btn-test-db">🔌 Probar Conexión</button>
                    </div>
                    <div class="db-test-result" id="db-test-result"></div>
                </div>
                
                <div class="form-section">
                    <h3>👤 Administrador</h3>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>Email del Admin</label>
                            <input type="email" name="admin_email" required placeholder="admin@empresa.com">
                        </div>
                        <div class="form-field">
                            <label>Contraseña (mín. 8 caracteres)</label>
                            <input type="password" name="admin_pass" required minlength="8">
                        </div>
                    </div>
                </div>
                
                <div class="btn-row">
                    <button type="button" class="btn btn-ghost" onclick="goToStep(1)">← Atrás</button>
                    <button type="submit" class="btn btn-primary" id="btn-install">Instalar Ahora 🚀</button>
                </div>
            </form>
        </div>

        <!-- STEP 3: Installation -->
        <div class="panel" id="panel-3">
            <h2 class="center">Instalando Sistema...</h2>
            <div class="progress-track">
                <div class="progress-fill" id="progress-bar"></div>
            </div>
            <div class="log-box" id="log-box"></div>
        </div>

        <!-- STEP 4: Complete -->
        <div class="panel" id="panel-4">
            <div class="center">
                <div class="success-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h2>¡Instalación Exitosa! 🎉</h2>
                <p style="color:var(--gray-500);margin-bottom:24px">Tu ERP Camiones está listo para usar.</p>
                
                <div class="alert alert-warning">
                    <strong>⚠️ Acción requerida:</strong> Elimine el archivo <code>public/installer.php</code> por seguridad.
                </div>
                
                <div id="credentials-display" style="background:var(--gray-50);padding:16px;border-radius:10px;text-align:left;font-size:13px;margin:16px 0">
                    <strong>Credenciales de acceso:</strong><br>
                    <span id="cred-email" style="color:var(--primary);font-weight:600"></span><br>
                    <span style="color:var(--gray-400);font-size:12px">La contraseña es la que ingresaste en el paso anterior.</span>
                </div>
                
                <a href="/" class="btn btn-primary" style="display:inline-block;text-decoration:none;margin-top:12px">Ir al Sistema →</a>
            </div>
        </div>
    </div>
</div>

<script>
    // ── State ─────────────────────────────────────────────────────────────
    let currentStep = 1;
    let dbTested = false;

    // ── Navigation ────────────────────────────────────────────────────────
    function goToStep(n) {
        currentStep = n;
        document.querySelectorAll('.panel').forEach(p => p.classList.remove('visible'));
        document.getElementById('panel-' + n).classList.add('visible');
        
        for (let i = 1; i <= 4; i++) {
            const nav = document.getElementById('nav-' + i);
            nav.classList.remove('active', 'done');
            if (i < n) nav.classList.add('done');
            if (i === n) nav.classList.add('active');
        }
    }

    // ── Step 1: Requirements ──────────────────────────────────────────────
    async function checkRequirements() {
        try {
            const r = await fetch('?action=check_requirements');
            const d = await r.json();
            const container = document.getElementById('requirements-container');
            let html = '';
            let allOk = true;
            
            // PHP Version
            html += '<div class="req-group">';
            html += '<div class="req-group-title">Versión PHP</div>';
            html += `<div class="req-item">
                <span class="req-icon">${d.php.status ? '✅' : '❌'}</span>
                PHP ≥ ${d.php.required} (actual: ${d.php.current})
            </div>`;
            if (!d.php.status) allOk = false;
            html += '</div>';
            
            // Extensions
            html += '<div class="req-group">';
            html += '<div class="req-group-title">Extensiones PHP</div>';
            for (const [ext, ok] of Object.entries(d.extensions)) {
                html += `<div class="req-item">
                    <span class="req-icon">${ok ? '✅' : '❌'}</span>
                    ${ext}
                </div>`;
                if (!ok) allOk = false;
            }
            html += '</div>';

            // Functions
            html += '<div class="req-group">';
            html += '<div class="req-group-title">Funciones PHP</div>';
            for (const [fn, ok] of Object.entries(d.functions)) {
                html += `<div class="req-item">
                    <span class="req-icon">${ok ? '✅' : '⚠️'}</span>
                    ${fn} ${!ok ? '<small style="color:var(--gray-400)">(no crítica si usa SSH)</small>' : ''}
                </div>`;
            }
            html += '</div>';
            
            // Permissions
            html += '<div class="req-group">';
            html += '<div class="req-group-title">Permisos de directorios</div>';
            for (const [path, ok] of Object.entries(d.permissions)) {
                html += `<div class="req-item">
                    <span class="req-icon">${ok ? '✅' : '❌'}</span>
                    ${path}
                </div>`;
                if (!ok) allOk = false;
            }
            html += '</div>';
            
            container.innerHTML = html;
            
            const btn = document.getElementById('btn-next-1');
            btn.disabled = !allOk;
            btn.onclick = () => goToStep(2);
            
        } catch (e) {
            document.getElementById('requirements-container').innerHTML = 
                '<div class="alert alert-error">Error al verificar requisitos: ' + e.message + '</div>';
        }
    }

    // ── Step 2: Test DB ───────────────────────────────────────────────────
    document.getElementById('btn-test-db').addEventListener('click', async () => {
        const form = document.getElementById('config-form');
        const fd = new FormData(form);
        const resultEl = document.getElementById('db-test-result');
        
        resultEl.className = 'db-test-result';
        resultEl.textContent = '⏳ Probando conexión...';
        resultEl.style.display = 'block';
        resultEl.style.background = 'var(--gray-100)';
        resultEl.style.color = 'var(--gray-600)';
        
        try {
            const r = await fetch('?action=test_db', { method: 'POST', body: fd });
            const d = await r.json();
            
            if (d.success) {
                resultEl.className = 'db-test-result ok';
                resultEl.textContent = '✅ ' + d.message;
                dbTested = true;
            } else {
                resultEl.className = 'db-test-result err';
                resultEl.textContent = '❌ ' + d.message;
                dbTested = false;
            }
        } catch (e) {
            resultEl.className = 'db-test-result err';
            resultEl.textContent = '❌ Error de red: ' + e.message;
        }
    });

    // ── Step 2: Submit Form ───────────────────────────────────────────────
    document.getElementById('config-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = document.getElementById('btn-install');
        btn.disabled = true;
        btn.textContent = '⏳ Guardando...';
        
        const fd = new FormData(e.target);
        
        try {
            const r = await fetch('?action=setup_env', { method: 'POST', body: fd });
            const d = await r.json();
            
            if (d.success) {
                // Store admin email for display later
                window._adminEmail = fd.get('admin_email');
                startInstallation();
            } else {
                alert('❌ ' + d.message + (d.details ? '\n\n' + d.details : ''));
                btn.disabled = false;
                btn.textContent = 'Instalar Ahora 🚀';
            }
        } catch (e) {
            alert('Error de red: ' + e.message);
            btn.disabled = false;
            btn.textContent = 'Instalar Ahora 🚀';
        }
    });

    // ── Step 3: Run Installation ──────────────────────────────────────────
    async function startInstallation() {
        goToStep(3);
        
        const steps = [
            { id: 'config_clear',   label: 'Limpiando configuración...' },
            { id: 'key_generate',   label: 'Generando clave de seguridad...' },
            { id: 'migrate',        label: 'Creando tablas en la base de datos...' },
            { id: 'seed',           label: 'Instalando datos iniciales...' },
            { id: 'storage_link',   label: 'Enlazando directorio de archivos...' },
            { id: 'optimize',       label: 'Optimizando el sistema...' },
        ];
        
        let completed = 0;
        let hasError = false;
        
        for (const step of steps) {
            addLog(step.label, 'normal');
            
            try {
                const r = await fetch(`?action=install&step=${step.id}`);
                const d = await r.json();
                
                if (d.success) {
                    addLog('✓ Completado', 'ok');
                } else {
                    addLog('✗ Error: ' + (d.output || d.message || 'Error desconocido'), 'err');
                    
                    // Migration is critical — stop on failure
                    if (step.id === 'migrate') {
                        addLog('⛔ No se puede continuar sin la base de datos.', 'err');
                        hasError = true;
                        break;
                    }
                    
                    // Seed failure is also critical
                    if (step.id === 'seed') {
                        addLog('⚠ Los datos iniciales no se instalaron. Puede ejecutar manualmente: php artisan db:seed --class=ProductionSeeder', 'warn');
                    }
                }
            } catch (err) {
                addLog('✗ Error de conexión: ' + err.message, 'err');
                if (step.id === 'migrate') {
                    hasError = true;
                    break;
                }
            }
            
            completed++;
            document.getElementById('progress-bar').style.width = (completed / steps.length * 100) + '%';
        }
        
        if (!hasError) {
            document.getElementById('progress-bar').style.width = '100%';
            addLog('', 'normal');
            addLog('🎉 ¡Instalación completada con éxito!', 'ok');
            setTimeout(() => {
                goToStep(4);
                document.getElementById('cred-email').textContent = window._adminEmail || 'admin@erp.com';
            }, 1500);
        } else {
            addLog('', 'normal');
            addLog('Corrija los errores y recargue esta página para reintentar.', 'warn');
        }
    }
    
    function addLog(msg, type = 'normal') {
        const box = document.getElementById('log-box');
        const line = document.createElement('div');
        line.className = 'log-entry ' + (type === 'ok' ? 'ok' : type === 'err' ? 'err' : type === 'warn' ? 'warn' : '');
        line.textContent = msg ? '› ' + msg : '';
        box.appendChild(line);
        box.scrollTop = box.scrollHeight;
    }
    
    // ── Init ──────────────────────────────────────────────────────────────
    document.getElementById('field-app-url').value = window.location.origin;
    checkRequirements();
</script>
</body>
</html>
