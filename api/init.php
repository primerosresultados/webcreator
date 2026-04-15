<?php
/**
 * ============================================
 * API BOOTSTRAP / INITIALIZATION
 * ============================================
 * Include this file at the top of every API endpoint.
 * Handles: DB connection, sessions, CORS, CSRF, helpers.
 */

// Start session
session_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Never show errors to client
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('America/Santiago');

// CORS headers (adjust for production)
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Load config
$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) {
    http_response_code(503);
    echo json_encode([
        'success' => false, 
        'error' => 'Sistema no instalado. Visite /install/ para configurar.'
    ]);
    exit;
}

require_once $configPath;

// ============================================
// DATABASE CONNECTION (Singleton PDO)
// ============================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'"
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos.']);
            error_log('DB Connection Error: ' . $e->getMessage());
            exit;
        }
    }
    return $pdo;
}

// ============================================
// SECURITY HELPERS
// ============================================

/**
 * Generate a CSRF token for forms
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from request
 */
function validateCSRFToken(): bool {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token or die
 */
function requireCSRF(): void {
    if (!validateCSRFToken()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF inválido. Recargue la página.']);
        exit;
    }
}

/**
 * Check if the user is authenticated as admin
 */
function requireAuth(): array {
    if (empty($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'No autenticado. Inicie sesión.']);
        exit;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        session_destroy();
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Sesión expirada. Inicie sesión nuevamente.']);
        exit;
    }
    
    $_SESSION['last_activity'] = time();
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['user_role']
    ];
}

// ============================================
// INPUT HELPERS
// ============================================

/**
 * Sanitize string input (XSS prevention)
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get raw request body (cached to allow multiple reads)
 */
function getRawBody(): string {
    static $raw = null;
    if ($raw === null) {
        $raw = file_get_contents('php://input');
    }
    return $raw;
}

/**
 * Get JSON body from POST request (cached, _method stripped)
 */
function getJSONBody(): array {
    static $cached = null;
    if ($cached === null) {
        $cached = json_decode(getRawBody(), true);
        if (!is_array($cached)) $cached = [];
        // Strip _method override key — not real data
        unset($cached['_method']);
    }
    return $cached;
}

/**
 * Get request method (with _method override for shared hosting)
 */
function getMethod(): string {
    $method = strtoupper($_SERVER['REQUEST_METHOD']);
    
    // Support _method override for hosts that block PUT/DELETE
    if ($method === 'POST') {
        $body = json_decode(getRawBody(), true);
        if (is_array($body) && isset($body['_method'])) {
            return strtoupper($body['_method']);
        }
    }
    
    return $method;
}

// ============================================
// RESPONSE HELPERS
// ============================================

/**
 * Send JSON success response
 */
function jsonSuccess(array $data = [], int $code = 200): void {
    http_response_code($code);
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send JSON error response
 */
function jsonError(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// ACTIVITY LOG
// ============================================
function logActivity(string $action, string $entityType, ?int $entityId = null, ?array $details = null): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $entityType,
            $entityId,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    } catch (Exception $e) {
        error_log('Activity log error: ' . $e->getMessage());
    }
}

// ============================================
// RATE LIMITING (Simple in-memory via session)
// ============================================
function rateLimit(string $key, int $maxAttempts = 5, int $windowSeconds = 60): bool {
    $sessionKey = "rate_limit_{$key}";
    $now = time();
    
    if (!isset($_SESSION[$sessionKey])) {
        $_SESSION[$sessionKey] = [];
    }
    
    // Clean old entries
    $_SESSION[$sessionKey] = array_filter($_SESSION[$sessionKey], function($time) use ($now, $windowSeconds) {
        return ($now - $time) < $windowSeconds;
    });
    
    if (count($_SESSION[$sessionKey]) >= $maxAttempts) {
        return false; // Rate limited
    }
    
    $_SESSION[$sessionKey][] = $now;
    return true; // Allowed
}

// ============================================
// AUTO-MIGRATION SYSTEM
// ============================================
// Runs pending migrations automatically once per session.
// Checks are lightweight: compares a hash of migration filenames
// to avoid re-scanning on every request.
function runAutoMigrations(): void {
    $migrationsDir = __DIR__ . '/../migrations/';
    if (!is_dir($migrationsDir)) return;

    // Get all .sql files (skip files starting with _)
    $files = glob($migrationsDir . '[!_]*.sql');
    if (empty($files)) return;
    sort($files);

    // Build a hash of current migration files to detect changes
    $fileNames = array_map('basename', $files);
    $currentHash = md5(implode('|', $fileNames));

    // Skip if we already checked this exact set of files in this session
    if (isset($_SESSION['_migrations_hash']) && $_SESSION['_migrations_hash'] === $currentHash) {
        return;
    }

    try {
        $db = getDB();

        // Create migrations tracking table if needed
        $db->exec("CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `filename` VARCHAR(255) NOT NULL UNIQUE,
            `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Get already executed migrations
        $executed = $db->query("SELECT filename FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

        // Run pending migrations
        $ran = 0;
        foreach ($files as $file) {
            $filename = basename($file);
            if (in_array($filename, $executed)) continue;

            try {
                $sql = file_get_contents($file);
                $db->exec($sql);
                $db->prepare("INSERT INTO migrations (filename) VALUES (?)")->execute([$filename]);
                $ran++;
                error_log("Auto-migration executed: {$filename}");
            } catch (PDOException $e) {
                error_log("Auto-migration error ({$filename}): " . $e->getMessage());
            }
        }

        // Cache the hash so we don't re-check until files change
        $_SESSION['_migrations_hash'] = $currentHash;

    } catch (Exception $e) {
        error_log('Auto-migration system error: ' . $e->getMessage());
    }
}

// Run auto-migrations if admin is logged in
if (!empty($_SESSION['user_id'])) {
    runAutoMigrations();
}
