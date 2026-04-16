<?php
/**
 * ============================================
 * PLUGIN SYSTEM API
 * ============================================
 * Manages plugin discovery, activation, deactivation, and ZIP upload.
 * 
 * Endpoints:
 *   GET  ?action=list                — List all detected plugins with status
 *   POST ?action=activate&plugin=ID  — Activate a plugin (run install.sql)
 *   POST ?action=deactivate&plugin=ID — Deactivate a plugin (run uninstall.sql)
 *   POST ?action=upload              — Upload plugin ZIP
 */

require_once __DIR__ . '/init.php';

$action = $_GET['action'] ?? 'list';

// All actions require authentication
$user = requireAuth();

switch ($action) {

    // ============================================
    // LIST ALL PLUGINS
    // ============================================
    case 'list':
        $pluginsDir = __DIR__ . '/../plugins/';
        $activePlugins = getActivePlugins();
        $plugins = [];

        if (is_dir($pluginsDir)) {
            $dirs = scandir($pluginsDir);
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..' || $dir === '.htaccess') continue;
                $pluginPath = $pluginsDir . $dir;
                if (!is_dir($pluginPath)) continue;

                $metaFile = $pluginPath . '/plugin.json';
                if (!file_exists($metaFile)) continue;

                $meta = json_decode(file_get_contents($metaFile), true);
                if (!$meta || empty($meta['id'])) continue;

                $meta['is_active'] = in_array($meta['id'], $activePlugins);
                $meta['folder'] = $dir;
                $plugins[] = $meta;
            }
        }

        jsonSuccess(['plugins' => $plugins]);
        break;

    // ============================================
    // ACTIVATE PLUGIN
    // ============================================
    case 'activate':
        $pluginId = $_GET['plugin'] ?? '';
        if (empty($pluginId)) jsonError('ID de plugin requerido.', 400);

        // Validate plugin folder exists
        $pluginPath = __DIR__ . '/../plugins/' . basename($pluginId);
        if (!is_dir($pluginPath)) jsonError('Plugin no encontrado.', 404);

        // Read plugin metadata
        $metaFile = $pluginPath . '/plugin.json';
        if (!file_exists($metaFile)) jsonError('plugin.json no encontrado.', 400);

        $meta = json_decode(file_get_contents($metaFile), true);
        if (!$meta || empty($meta['id'])) jsonError('plugin.json inválido.', 400);

        // Check if already active
        $activePlugins = getActivePlugins();
        if (in_array($meta['id'], $activePlugins)) jsonError('El plugin ya está activo.', 400);

        // Execute install.sql
        $installFile = $pluginPath . '/install.sql';
        if (file_exists($installFile)) {
            try {
                $db = getDB();
                $sql = file_get_contents($installFile);
                // Execute each statement separately
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $stmt) {
                    if (!empty($stmt)) {
                        $db->exec($stmt);
                    }
                }
            } catch (PDOException $e) {
                error_log("Plugin install error ({$meta['id']}): " . $e->getMessage());
                jsonError('Error al crear tablas del plugin: ' . $e->getMessage(), 500);
            }
        }

        // Register as active
        $activePlugins[] = $meta['id'];
        saveActivePlugins($activePlugins);

        logActivity('plugin_activate', 'plugin', null, ['plugin_id' => $meta['id'], 'name' => $meta['name']]);
        jsonSuccess(['message' => "Plugin '{$meta['name']}' activado correctamente."]);
        break;

    // ============================================
    // DEACTIVATE PLUGIN
    // ============================================
    case 'deactivate':
        $pluginId = $_GET['plugin'] ?? '';
        if (empty($pluginId)) jsonError('ID de plugin requerido.', 400);

        $pluginPath = __DIR__ . '/../plugins/' . basename($pluginId);
        $metaFile = $pluginPath . '/plugin.json';
        
        $meta = null;
        if (file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
        }

        $pluginName = $meta['name'] ?? $pluginId;

        // Check if active
        $activePlugins = getActivePlugins();
        if (!in_array($pluginId, $activePlugins)) jsonError('El plugin no está activo.', 400);

        // Execute uninstall.sql (DROP TABLES)
        $uninstallFile = $pluginPath . '/uninstall.sql';
        if (file_exists($uninstallFile)) {
            try {
                $db = getDB();
                $sql = file_get_contents($uninstallFile);
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $stmt) {
                    if (!empty($stmt)) {
                        $db->exec($stmt);
                    }
                }
            } catch (PDOException $e) {
                error_log("Plugin uninstall error ({$pluginId}): " . $e->getMessage());
                jsonError('Error al eliminar tablas del plugin: ' . $e->getMessage(), 500);
            }
        }

        // Remove from active list
        $activePlugins = array_values(array_filter($activePlugins, fn($id) => $id !== $pluginId));
        saveActivePlugins($activePlugins);

        logActivity('plugin_deactivate', 'plugin', null, ['plugin_id' => $pluginId, 'name' => $pluginName]);
        jsonSuccess(['message' => "Plugin '{$pluginName}' desactivado. Todas sus tablas y datos han sido eliminados."]);
        break;

    // ============================================
    // UPLOAD PLUGIN ZIP
    // ============================================
    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método no permitido.', 405);
        
        // Only superadmin can upload plugins
        if ($user['role'] !== 'superadmin') {
            jsonError('Solo el Super Admin puede subir plugins.', 403);
        }

        if (empty($_FILES['plugin_zip'])) jsonError('No se recibió archivo ZIP.', 400);

        $file = $_FILES['plugin_zip'];
        if ($file['error'] !== UPLOAD_ERR_OK) jsonError('Error al subir archivo.', 400);

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])) {
            jsonError('Solo se aceptan archivos ZIP.', 400);
        }

        // Create temp extraction directory
        $tempDir = sys_get_temp_dir() . '/wc_plugin_' . uniqid();
        mkdir($tempDir, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            jsonError('No se pudo abrir el archivo ZIP.', 400);
        }

        $zip->extractTo($tempDir);
        $zip->close();

        // Find plugin.json (might be in root or in a subfolder)
        $pluginJsonPath = null;
        $pluginRoot = null;

        if (file_exists($tempDir . '/plugin.json')) {
            $pluginJsonPath = $tempDir . '/plugin.json';
            $pluginRoot = $tempDir;
        } else {
            // Check one level deep
            $subdirs = scandir($tempDir);
            foreach ($subdirs as $subdir) {
                if ($subdir === '.' || $subdir === '..') continue;
                $subPath = $tempDir . '/' . $subdir;
                if (is_dir($subPath) && file_exists($subPath . '/plugin.json')) {
                    $pluginJsonPath = $subPath . '/plugin.json';
                    $pluginRoot = $subPath;
                    break;
                }
            }
        }

        if (!$pluginJsonPath) {
            // Cleanup
            exec("rm -rf " . escapeshellarg($tempDir));
            jsonError('El ZIP no contiene un plugin válido (falta plugin.json).', 400);
        }

        $meta = json_decode(file_get_contents($pluginJsonPath), true);
        if (!$meta || empty($meta['id'])) {
            exec("rm -rf " . escapeshellarg($tempDir));
            jsonError('plugin.json inválido o sin ID.', 400);
        }

        // Sanitize plugin ID
        $safeId = preg_replace('/[^a-z0-9_-]/', '', strtolower($meta['id']));
        if (empty($safeId)) {
            exec("rm -rf " . escapeshellarg($tempDir));
            jsonError('ID de plugin inválido.', 400);
        }

        // Move to plugins directory
        $destDir = __DIR__ . '/../plugins/' . $safeId;
        if (is_dir($destDir)) {
            // Plugin already exists — check if active, prevent overwrite if active
            $active = getActivePlugins();
            if (in_array($safeId, $active)) {
                exec("rm -rf " . escapeshellarg($tempDir));
                jsonError('El plugin ya existe y está activo. Desactívelo primero para reemplazarlo.', 400);
            }
            // Remove old version
            exec("rm -rf " . escapeshellarg($destDir));
        }

        // Move plugin files
        rename($pluginRoot, $destDir);

        // Cleanup temp
        if (is_dir($tempDir)) exec("rm -rf " . escapeshellarg($tempDir));

        logActivity('plugin_upload', 'plugin', null, ['plugin_id' => $safeId, 'name' => $meta['name'] ?? $safeId]);
        jsonSuccess([
            'message' => "Plugin '{$meta['name']}' subido correctamente. Puedes activarlo desde el panel.",
            'plugin' => $meta
        ]);
        break;

    // ============================================
    // ROUTE PLUGIN API CALLS
    // ============================================
    case 'api':
        $pluginId = $_GET['plugin'] ?? '';
        if (empty($pluginId)) jsonError('ID de plugin requerido.', 400);

        // Verify plugin is active
        $activePlugins = getActivePlugins();
        if (!in_array($pluginId, $activePlugins)) jsonError('Plugin no activo.', 403);

        // Route to plugin's api.php
        $pluginApiFile = __DIR__ . '/../plugins/' . basename($pluginId) . '/api.php';
        if (!file_exists($pluginApiFile)) jsonError('El plugin no tiene API.', 404);

        // Pass through to plugin API
        require $pluginApiFile;
        break;

    default:
        jsonError('Acción no válida.', 400);
}

// ============================================
// HELPER: Get active plugins from settings
// ============================================
function getActivePlugins(): array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'active_plugins'");
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row && $row['setting_value']) {
            $plugins = json_decode($row['setting_value'], true);
            return is_array($plugins) ? $plugins : [];
        }
    } catch (Exception $e) {
        error_log('getActivePlugins error: ' . $e->getMessage());
    }
    return [];
}

// ============================================
// HELPER: Save active plugins to settings
// ============================================
function saveActivePlugins(array $plugins): void {
    try {
        $db = getDB();
        $json = json_encode(array_values(array_unique($plugins)));
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES ('active_plugins', ?, 'json')
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$json]);
    } catch (Exception $e) {
        error_log('saveActivePlugins error: ' . $e->getMessage());
    }
}
