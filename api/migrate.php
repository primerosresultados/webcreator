<?php
/**
 * ============================================
 * MIGRATION SYSTEM - Database Updates
 * ============================================
 * Cuando necesites agregar tablas nuevas (ej: productos),
 * crea un archivo .sql en /migrations/ y visita:
 * https://tusitio.com/api/migrate.php
 * 
 * El sistema ejecuta solo las migraciones pendientes.
 */

require_once __DIR__ . '/init.php';

// Only superadmin can run migrations, and only via POST with CSRF
if (getMethod() !== 'POST') {
    jsonError('Las migraciones deben ejecutarse via POST.', 405);
}

$user = requireAuth();
requireCSRF();

if ($user['role'] !== 'superadmin') {
    jsonError('Solo el superadmin puede ejecutar migraciones.', 403);
}

$db = getDB();
$migrationsDir = __DIR__ . '/../migrations/';

// Create migrations tracking table if it doesn't exist
$db->exec("CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL UNIQUE,
    `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Get already executed migrations
$executed = $db->query("SELECT filename FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

// Get migration files
if (!is_dir($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
    jsonSuccess(['message' => 'No hay migraciones. Carpeta /migrations/ creada.', 'executed' => []]);
}

// Exclude example files prefixed with _ (same pattern as auto-migration)
$files = glob($migrationsDir . '[!_]*.sql');
sort($files); // Execute in order

$results = [];
$newMigrations = 0;

foreach ($files as $file) {
    $filename = basename($file);
    
    // Skip if already executed
    if (in_array($filename, $executed)) {
        $results[] = ['file' => $filename, 'status' => 'already_executed'];
        continue;
    }

    // Execute migration
    try {
        $sql = file_get_contents($file);
        $db->exec($sql);
        
        // Mark as executed
        $db->prepare("INSERT INTO migrations (filename) VALUES (?)")->execute([$filename]);
        
        $results[] = ['file' => $filename, 'status' => 'success'];
        $newMigrations++;
        
        logActivity('migrate', 'database', null, ['file' => $filename]);
        
    } catch (PDOException $e) {
        $results[] = ['file' => $filename, 'status' => 'error', 'error' => $e->getMessage()];
        error_log("Migration error ({$filename}): " . $e->getMessage());
    }
}

jsonSuccess([
    'message' => $newMigrations > 0 
        ? "{$newMigrations} migración(es) ejecutada(s) exitosamente." 
        : "No hay migraciones pendientes.",
    'results' => $results
]);
