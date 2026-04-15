<?php
/**
 * ============================================
 * DYNAMIC THEME CSS GENERATOR
 * ============================================
 * GET /api/theme.css.php
 * Generates CSS custom properties from saved theme settings.
 * Loaded by the public site as a stylesheet.
 */

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

    function cssVal($val, $fallback = '') {
        return htmlspecialchars($val ?: $fallback, ENT_QUOTES, 'UTF-8');
    }

    // --- Google Fonts Import ---
    $fonts = [];
    if (!empty($theme['fontHeadings'])) $fonts[] = $theme['fontHeadings'];
    if (!empty($theme['fontMenu']) && !in_array($theme['fontMenu'], $fonts)) $fonts[] = $theme['fontMenu'];
    if (!empty($theme['fontBody']) && !in_array($theme['fontBody'], $fonts)) $fonts[] = $theme['fontBody'];

    if (!empty($fonts)) {
        $fontFamilies = [];
        foreach ($fonts as $f) {
            $fontFamilies[] = 'family=' . str_replace(' ', '+', $f) . ':wght@300;400;500;600;700;800';
        }
        $googleUrl = 'https://fonts.googleapis.com/css2?' . implode('&', $fontFamilies) . '&display=swap';
        echo "@import url('{$googleUrl}');\n\n";
    }

    // --- :root overrides ---
    echo "/* Auto-generated theme — " . date('Y-m-d H:i:s') . " */\n";
    echo ":root {\n";

    // Colors
    if (!empty($theme['colorPrimary'])) {
        $hex = $theme['colorPrimary'];
        echo "    --color-primary: {$hex};\n";
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
        $hex = $theme['colorSecondary'];
        echo "    --color-secondary: {$hex};\n";
        $r = hexdec(substr($hex, 1, 2));
        $g = hexdec(substr($hex, 3, 2));
        $b = hexdec(substr($hex, 5, 2));
        echo "    --color-secondary-rgb: {$r}, {$g}, {$b};\n";
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
        echo "    --font-headings: '" . cssVal($theme['fontHeadings']) . "', system-ui, sans-serif;\n";
    }

    // Typography - Body font
    if (!empty($theme['fontBody'])) {
        echo "    --font-primary: '" . cssVal($theme['fontBody']) . "', system-ui, sans-serif;\n";
    }

    // Typography - Menu font
    if (!empty($theme['fontMenu'])) {
        echo "    --font-menu: '" . cssVal($theme['fontMenu']) . "', system-ui, sans-serif;\n";
    }

    echo "}\n\n";

    // --- Apply font families ---
    if (!empty($theme['fontBody'])) {
        echo "body { font-family: var(--font-primary); }\n\n";
    }

    if (!empty($theme['fontHeadings'])) {
        echo "h1, h2, h3, h4, h5, h6 { font-family: var(--font-headings); }\n\n";
    }

    if (!empty($theme['fontMenu'])) {
        echo ".nav-links, .nav-links a, .nav-brand { font-family: var(--font-menu); }\n\n";
    }

    // --- Individual heading styles ---
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

    // --- Button radius ---
    if (!empty($theme['btnRadius'])) {
        echo ".btn, .btn-outline-light, .btn-dark, .btn-accent { border-radius: " . cssVal($theme['btnRadius']) . "; }\n\n";
    }

    // --- Apply secondary color to all accent elements on public site ---
    if (!empty($theme['colorSecondary'])) {
        $s = cssVal($theme['colorSecondary']);
        echo "/* Secondary/accent color overrides for public site */\n";
        echo ".nav-links a.active::after { background: {$s}; }\n";
        echo ".nav-phone { color: {$s} !important; }\n";
        echo ".btn-accent { background: {$s}; }\n";
        echo ".btn-accent:hover { background: {$s}; filter: brightness(1.1); box-shadow: 0 6px 20px rgba(0,0,0,0.2); }\n";
        echo ".hero-form-card::before { background: linear-gradient(90deg, {$s}, rgba(255,255,255,0.4), {$s}); background-size: 200% 100%; animation: shimmer 3s ease-in-out infinite; }\n";
        echo ".hero-form-card .form-input:focus, .hero-form-card .form-textarea:focus, .hero-form-card select.form-input:focus { border-color: {$s}; box-shadow: 0 0 0 3px rgba(var(--color-secondary-rgb), 0.15); }\n";
        echo ".welcome-badge { border-color: {$s}; color: {$s}; }\n";
        echo ".trust-card:hover { border-color: {$s}; }\n";
        echo ".trust-icon { color: {$s}; }\n";
        echo ".stat-number, .stat-suffix { color: {$s}; }\n";
        echo ".text-accent { color: {$s}; }\n";
        echo ".contact-icon { background: rgba(var(--color-secondary-rgb), 0.1); border-color: rgba(var(--color-secondary-rgb), 0.2); }\n";
        echo ".contact-form-wrap .form-input:focus, .contact-form-wrap .form-textarea:focus { border-color: {$s}; box-shadow: 0 0 0 3px rgba(var(--color-secondary-rgb), 0.12); }\n";
        echo ".contact-form-wrap .form-label .required { color: {$s}; }\n";
        echo ".footer-col a:hover { color: {$s}; }\n";
        echo ".footer-social a:hover { color: {$s}; }\n\n";
    }

    // --- Logo URLs from settings ---
    $logoStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'logo_normal'");
    $logoStmt->execute();
    $logoRow = $logoStmt->fetch();
    // Logos are handled via JS, not CSS

} catch (Exception $e) {
    echo "/* Theme error: " . htmlspecialchars($e->getMessage()) . " */\n";
}
