<?php
/**
 * ============================================
 * SETTINGS API
 * ============================================
 * GET  /api/settings.php              - Get all settings (admin)
 * GET  /api/settings.php?key=X        - Get specific setting (admin)
 * PUT  /api/settings.php              - Update settings (admin)
 * GET  /api/settings.php?public=1     - Get public theme settings (no auth)
 */

require_once __DIR__ . '/init.php';

$method = getMethod();

switch ($method) {

    case 'GET':
        // Public theme settings (no auth required)
        if (isset($_GET['public']) && $_GET['public'] == '1') {
            $db = getDB();
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('theme_config', 'site_info')");
            $stmt->execute();
            $response = ['theme' => null, 'site_info' => null];
            while ($row = $stmt->fetch()) {
                if ($row['setting_key'] === 'theme_config' && $row['setting_value']) {
                    $response['theme'] = json_decode($row['setting_value'], true);
                }
                if ($row['setting_key'] === 'site_info' && $row['setting_value']) {
                    $response['site_info'] = json_decode($row['setting_value'], true);
                }
            }

            header('Content-Type: application/json');
            header('Cache-Control: public, max-age=300');
            echo json_encode(['success' => true] + $response);
            exit;
        }

        // Admin: get all settings
        $user = requireAuth();
        $db = getDB();

        if (isset($_GET['key'])) {
            $stmt = $db->prepare("SELECT * FROM settings WHERE setting_key = ?");
            $stmt->execute([sanitize($_GET['key'])]);
            $setting = $stmt->fetch();
            jsonSuccess(['setting' => $setting]);
        }

        $stmt = $db->query("SELECT * FROM settings ORDER BY setting_key");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $val = $row['setting_value'];
            if ($row['setting_type'] === 'json') $val = json_decode($val, true);
            if ($row['setting_type'] === 'number') $val = (float)$val;
            if ($row['setting_type'] === 'boolean') $val = ($val === 'true' || $val === '1');
            $settings[$row['setting_key']] = $val;
        }

        jsonSuccess(['settings' => $settings]);
        break;

    case 'PUT':
    case 'PATCH':
        $user = requireAuth();
        requireCSRF();
        $db = getDB();
        $data = getJSONBody();

        if (empty($data)) {
            jsonError('No se recibieron datos.');
        }

        // Auto-create settings table if it doesn't exist (defensive)
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS `settings` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                `setting_value` LONGTEXT,
                `setting_type` ENUM('string','number','boolean','json') DEFAULT 'string',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Exception $e) {
            // Table probably already exists, continue
        }

        $updated = 0;

        foreach ($data as $key => $value) {
            $key = sanitize($key);
            
            // Determine type
            $type = 'string';
            if (is_array($value) || is_object($value)) {
                $type = 'json';
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($value)) {
                $type = 'boolean';
                $value = $value ? 'true' : 'false';
            } elseif (is_numeric($value) && !is_string($value)) {
                $type = 'number';
                $value = (string)$value;
            }

            // Upsert
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)");
            $stmt->execute([$key, $value, $type]);
            $updated++;
        }

        logActivity('update', 'settings', null, ['keys' => array_keys($data)]);
        jsonSuccess(['message' => "Se actualizaron {$updated} configuraciones.", 'updated' => $updated]);
        break;

    default:
        jsonError('Método no permitido.', 405);
}
