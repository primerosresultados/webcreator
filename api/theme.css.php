<?php
/**
 * ============================================
 * DYNAMIC THEME CSS GENERATOR
 * ============================================
 * GET /api/theme.css.php
 * Generates CSS custom properties from saved theme settings.
 * Loaded by the public site as a stylesheet.
 */

// Load config
$configPath = __DIR__ . '/../config/database.php';
if (!file_exists($configPath)) { 
    header('Content-Type: text/css');
    echo '/* No config found */'; 
    exit; 
}

require_once $configPath;

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=300');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'theme_config'");
    $stmt->execute();
    $row = $stmt->fetch();

    if (!$row || empty($row['setting_value'])) {
        echo '/* No theme config saved */';
        exit;
    }

    $theme = json_decode($row['setting_value'], true);
    if (!$theme) {
        echo '/* Invalid theme JSON */';
        exit;
    }

    // Helper to safely output a CSS value
    function cssVal($val, $fallback = '') {
        return htmlspecialchars($val ?: $fallback, ENT_QUOTES, 'UTF-8');
    }

    // Generate :root overrides
    echo "/* Auto-generated theme — " . date('Y-m-d H:i:s') . " */\n";
    echo ":root {\n";

    // Colors
    if (!empty($theme['colorPrimary'])) {
        $hex = $theme['colorPrimary'];
        echo "    --color-primary: {$hex};\n";
        // Generate RGB version
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        echo "    --color-primary-rgb: {$r}, {$g}, {$b};\n";
        echo "    --color-primary-light: rgba({$r}, {$g}, {$b}, 0.1);\n";
    }
    if (!empty($theme['colorPrimaryHover'])) {
        echo "    --color-primary-hover: " . cssVal($theme['colorPrimaryHover']) . ";\n";
    }
    if (!empty($theme['colorSecondary'])) {
        echo "    --color-secondary: " . cssVal($theme['colorSecondary']) . ";\n";
    }
    if (!empty($theme['colorSecondaryHover'])) {
        echo "    --color-secondary-hover: " . cssVal($theme['colorSecondaryHover']) . ";\n";
    }
    if (!empty($theme['colorAccent'])) {
        echo "    --color-accent: " . cssVal($theme['colorAccent']) . ";\n";
    }

    // Border radius
    if (!empty($theme['borderRadius'])) {
        $r = cssVal($theme['borderRadius']);
        echo "    --radius-sm: calc({$r} * 0.5);\n";
        echo "    --radius-md: calc({$r} * 0.67);\n";
        echo "    --radius-lg: {$r};\n";
        echo "    --radius-xl: calc({$r} * 1.33);\n";
        echo "    --radius-2xl: calc({$r} * 2);\n";
    }

    // Typography - Headings font
    if (!empty($theme['fontHeadings'])) {
        echo "    --font-headings: " . cssVal($theme['fontHeadings']) . ", system-ui, sans-serif;\n";
    }

    // Typography - Body font
    if (!empty($theme['fontBody'])) {
        echo "    --font-primary: " . cssVal($theme['fontBody']) . ", system-ui, sans-serif;\n";
    }

    // Typography - Menu font
    if (!empty($theme['fontMenu'])) {
        echo "    --font-menu: " . cssVal($theme['fontMenu']) . ", system-ui, sans-serif;\n";
    }

    echo "}\n\n";

    // Apply heading fonts if set
    if (!empty($theme['fontHeadings'])) {
        echo "h1, h2, h3, h4, h5, h6 {\n";
        echo "    font-family: var(--font-headings);\n";
        echo "}\n\n";
    }

    // Apply menu font if set
    if (!empty($theme['fontMenu'])) {
        echo ".nav-links, .nav-links a {\n";
        echo "    font-family: var(--font-menu);\n";
        echo "}\n\n";
    }

    // Individual heading styles
    for ($i = 1; $i <= 6; $i++) {
        $rules = [];
        if (!empty($theme["h{$i}Size"])) $rules[] = "font-size: " . cssVal($theme["h{$i}Size"]);
        if (!empty($theme["h{$i}Weight"])) $rules[] = "font-weight: " . cssVal($theme["h{$i}Weight"]);
        if (!empty($theme["h{$i}Color"])) $rules[] = "color: " . cssVal($theme["h{$i}Color"]);

        if (!empty($rules)) {
            echo "h{$i} {\n";
            foreach ($rules as $rule) {
                echo "    {$rule};\n";
            }
            echo "}\n\n";
        }
    }

    // Body text customization
    if (!empty($theme['bodySize'])) {
        echo "body { font-size: " . cssVal($theme['bodySize']) . "; }\n";
    }
    if (!empty($theme['bodyColor'])) {
        echo "body { --text-primary: " . cssVal($theme['bodyColor']) . "; }\n";
    }

    // Button styles
    if (!empty($theme['btnRadius'])) {
        echo ".btn { border-radius: " . cssVal($theme['btnRadius']) . "; }\n";
    }

} catch (Exception $e) {
    echo "/* Theme error: " . htmlspecialchars($e->getMessage()) . " */\n";
}
