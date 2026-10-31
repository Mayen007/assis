<?php
/**
 * Database & System Configuration
 * After-Sales Service Information System (ASSIS)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials (Standard XAMPP Defaults)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'assis_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Application Constants
define('APP_NAME', 'After-Sales Service Information System');
define('APP_SHORT_NAME', 'ASSIS');
define('APP_VERSION', '1.0.0');
define('CURRENCY', 'KES');

/**
 * Returns a shared PDO database connection.
 * @return PDO
 */
function get_db(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Error 1049: Unknown database. Automatically create assis_db and seed initial data.
        if ((int)$e->getCode() === 1049 || str_contains($e->getMessage(), '1049') || str_contains($e->getMessage(), 'Unknown database')) {
            try {
                $serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
                $serverPdo = new PDO($serverDsn, DB_USER, DB_PASS, $options);
                $serverPdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', DB_NAME));

                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

                // Initialize schema
                $schemaFile = __DIR__ . '/../database/schema.sql';
                if (file_exists($schemaFile)) {
                    execute_sql_file($pdo, $schemaFile);
                }

                return $pdo;
            } catch (Exception $initException) {
                die('Automatic Database Initialization Failed: ' . htmlspecialchars($initException->getMessage()));
            }
        }

        die('Database Connection Error: ' . htmlspecialchars($e->getMessage()));
    }

    return $pdo;
}

/**
 * Executes a SQL file containing multiple statements.
 */
function execute_sql_file(PDO $pdo, string $filePath): void {
    $sql = file_get_contents($filePath);
    $queries = preg_split('/;\s*[\r\n]+/', $sql);
    foreach ($queries as $query) {
        $trimmed = trim($query);
        if ($trimmed !== '' && !str_starts_with($trimmed, '--')) {
            $pdo->exec($trimmed);
        }
    }
}

/**
 * Generates an absolute web URL relative to the project root.
 * @param string $path
 * @return string
 */
function base_url(string $path = ''): string {
    // Detect subfolder relative to server document root
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    // Remove subdirectories if called from subfolders (e.g. /customers, /auth)
    $cleanDir = preg_replace('#/(auth|customers|products|requests|technicians|inventory|billing|feedback|reports|users).*$#', '', $scriptDir);
    $cleanDir = rtrim($cleanDir, '/\\');
    $path = ltrim($path, '/');
    return ($cleanDir === '' ? '' : $cleanDir) . '/' . $path;
}

/**
 * Sanitizes input string for safe HTML rendering.
 */
function e(?string $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Formats a numeric amount with the system currency (KES).
 */
function format_money(float|int|string|null $amount): string {
    $num = is_numeric($amount) ? (float)$amount : 0.0;
    return number_format($num, 2) . ' ' . CURRENCY;
}

/**
 * Sets a flash message for the next request.
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message,
    ];
}

/**
 * Retrieves and clears flash message.
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Generates or retrieves a CSRF token.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Renders a hidden CSRF input field.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Validates the submitted CSRF token.
 */
function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
