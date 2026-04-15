<?php
/**
 * DIAGNOSTIC ENDPOINT - Delete after debugging
 * Visit: https://tusitio.com/api/debug_settings.php
 */
require_once __DIR__ . '/init.php';

echo "<h2>Settings Diagnostic</h2>";
echo "<pre>";

// 1. Check if settings table exists
try {
    $db = getDB();
    echo "✓ Database connection OK\n\n";
    
    $result = $db->query("SHOW TABLES LIKE 'settings'");
    $tableExists = $result->rowCount() > 0;
    echo $tableExists ? "✓ Table 'settings' EXISTS\n" : "✗ Table 'settings' DOES NOT EXIST\n";
    
    if ($tableExists) {
        // 2. List all settings
        $stmt = $db->query("SELECT setting_key, setting_type, LENGTH(setting_value) as val_length, LEFT(setting_value, 100) as val_preview FROM settings ORDER BY setting_key");
        $rows = $stmt->fetchAll();
        
        echo "\nTotal settings: " . count($rows) . "\n\n";
        
        foreach ($rows as $row) {
            echo "  [{$row['setting_type']}] {$row['setting_key']} = {$row['val_preview']}" . 
                 ($row['val_length'] > 100 ? "... (total: {$row['val_length']} chars)" : "") . "\n";
        }
        
        // 3. Check theme_config specifically
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'theme_config'");
        $stmt->execute();
        $theme = $stmt->fetch();
        
        echo "\n--- theme_config ---\n";
        if ($theme) {
            $decoded = json_decode($theme['setting_value'], true);
            if ($decoded) {
                echo "✓ Valid JSON with " . count($decoded) . " keys:\n";
                foreach ($decoded as $k => $v) {
                    echo "  {$k}: " . (is_array($v) ? json_encode($v) : $v) . "\n";
                }
            } else {
                echo "✗ Invalid JSON in theme_config\n";
                echo "Raw value: " . $theme['setting_value'] . "\n";
            }
        } else {
            echo "✗ No theme_config found in settings table\n";
        }
    }
    
    // 4. Check migrations table
    $result = $db->query("SHOW TABLES LIKE 'migrations'");
    if ($result->rowCount() > 0) {
        $stmt = $db->query("SELECT * FROM migrations ORDER BY executed_at DESC");
        $migs = $stmt->fetchAll();
        echo "\n--- Migrations (" . count($migs) . ") ---\n";
        foreach ($migs as $m) {
            echo "  ✓ {$m['filename']} ({$m['executed_at']})\n";
        }
    } else {
        echo "\n✗ No migrations table\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
