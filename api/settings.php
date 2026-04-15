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
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'theme_config'");
            $stmt->execute();
            $row = $stmt->fetch();

            if ($row && $row['setting_value']) {
                header('Content-Type: application/json');
                header('Cache-Control: public, max-age=300');
                echo json_encode(['success' => true, 'theme' => json_decode($row['setting_value'], true)]);
            } else {
                jsonSuccess(['theme' => null]);
            }
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
        $user = requireAuth();
        requireCSRF();
        $db = getDB();
        $data = getJSONBody();

        if (empty($data)) {
            jsonError('No se recibieron datos.');
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
