<?php
/**
 * ============================================
 * PÁGINA: Estudio (Sobre Nosotros)
 * ============================================
 * Acceso: /estudio (rewrite en .htaccess) → /estudio.php
 * Foto del fundador: Cloudinary public_id `FOTO_ALVARO_ppel5z`
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$v = time();

// Site info defaults
$S = [
    'siteName' => 'MiSitio', 'siteDescription' => 'Soluciones profesionales para tu negocio',
    'phone' => '+56 9 1234 5678', 'email' => 'contacto@tusitio.com',
    'whatsapp' => '', 'address' => 'Santiago, Chile',
    'instagram' => '', 'facebook' => '', 'youtube' => '', 'linkedin' => '',
    'twitter' => '', 'pinterest' => '', 'tiktok' => '',
];
try {
    $cfgPath = __DIR__ . '/config/database.php';
    if (file_exists($cfgPath)) {
        require_once $cfgPath;
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_info','logo_normal','logo_negative')");
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            if ($row['setting_key'] === 'site_info') {
                $loaded = json_decode($row['setting_value'], true);
                if (is_array($loaded)) foreach ($loaded as $k => $val) { if (!empty($val)) $S[$k] = $val; }
            } else {
                $S[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
} catch (Exception $e) {}

$h = function($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };
$phoneClean = preg_replace('/[^0-9+]/', '', $S['phone']);
$logoNormal   = !empty($S['logo_normal'])   ? $S['logo_normal']   : '/assets/img/logo-negative.png';
$logoNegative = !empty($S['logo_negative']) ? $S['logo_negative'] : '/assets/img/logo-negative.png';
$svgLogo = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;"><path d="M12 2 L2 7 L12 12 L22 7 Z"/><path d="M2 17 L12 22 L22 17"/><path d="M2 12 L12 17 L22 12"/></svg>';

// Cloudinary config para construir URL de la foto
$cloudCfgFile = @include __DIR__ . '/config/cloudinary.php';
$cloudName = (is_array($cloudCfgFile) && !empty($cloudCfgFile['cloudName'])) ? $cloudCfgFile['cloudName'] : 'dt7raeikn';
$cloudTx   = (is_array($cloudCfgFile) && !empty($cloudCfgFile['transform'])) ? $cloudCfgFile['transform'] : 'q_auto,f_auto';
$alvaroImg = "https://res.cloudinary.com/{$cloudName}/image/upload/{$cloudTx}/FOTO_ALVARO_ppel5z";

// Hero videos del home (para el menú overlay)
$menuHeroVideoUrl = null;
$menuHeroPoster = null;
try {
    $heroPid = null;
    $heroCloud = $cloudName;
    $heroTx = $cloudTx;
    if (isset($pdo)) {
        $sH = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'hero_videos'");
        $sH->execute();
        $rH = $sH->fetch();
        if ($rH && !empty($rH['setting_value'])) {
            $arr = json_decode($rH['setting_value'], true);
            if (is_array($arr) && !empty($arr[0]['public_id'])) $heroPid = trim($arr[0]['public_id']);
        }
    }
    if (!$heroPid) {
        $heroCfg = @include __DIR__ . '/config/hero-videos.php';
        if (is_array($heroCfg) && !empty($heroCfg['cloudName']) && !empty($heroCfg['publicIds'])) {
            $heroCloud = $heroCfg['cloudName'];
            if (!empty($heroCfg['transform'])) $heroTx = $heroCfg['transform'];
            $heroPid = trim($heroCfg['publicIds'][0]);
        }
    }
    if ($heroCloud && $heroPid) {
        $menuHeroVideoUrl = "https://res.cloudinary.com/{$heroCloud}/video/upload/{$heroTx}/{$heroPid}.mp4";
        $menuHeroPoster   = "https://res.cloudinary.com/{$heroCloud}/video/upload/q_auto,f_auto,so_0/{$heroPid}.jpg";
    }
} catch (Exception $e) {}

$socials = [];
if (!empty($S['facebook']))  $socials[] = ['url' => $S['facebook'],  'label' => 'Facebook',  'icon' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>'];
if (!empty($S['instagram'])) $socials[] = ['url' => $S['instagram'], 'label' => 'Instagram', 'icon' => '<rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>'];
if (!empty($S['linkedin']))  $socials[] = ['url' => $S['linkedin'],  'label' => 'LinkedIn',  'icon' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Estudio FARE Arquitectura. Diseño de viviendas en el sur de Chile que integran arquitectura contemporánea con técnicas tradicionales.">
    <meta name="theme-color" content="#0a0a0a">
    <title>Estudio — <?=$h($S['siteName'])?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='18' fill='%231a1d2e'/><text x='50' y='68' text-anchor='middle' fill='%23c9a96e' font-size='52' font-weight='bold'><?=substr($S['siteName'],0,1)?></text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Raleway:wght@200;300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/variables.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/base.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/public.css?v=<?=$v?>">
    <link rel="stylesheet" href="/api/theme.css.php">
</head>
<body class="project-page">

    <!-- HEADER -->
    <header class="site-header" id="header">
        <nav class="nav container">
            <a href="/" class="nav-brand">
                <?php if ($logoNegative || $logoNormal): ?>
                    <?php if ($logoNegative): ?><img src="<?=$h($logoNegative)?>" alt="<?=$h($S['siteName'])?>" class="brand-logo brand-logo-negative" style="height:36px;width:auto;display:block;"><?php endif; ?>
                    <?php if ($logoNormal): ?><img src="<?=$h($logoNormal)?>" alt="<?=$h($S['siteName'])?>" class="brand-logo brand-logo-normal" style="height:36px;width:auto;"><?php endif; ?>
                <?php else: ?>
                    <?=$svgLogo?>
                    <span><?=$h($S['siteName'])?></span>
                <?php endif; ?>
            </a>
            <button class="nav-toggle" id="nav-toggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="overlay-menu">
                <span></span><span></span><span></span>
            </button>
        </nav>
    </header>

    <!-- OVERLAY MENU -->
    <div class="overlay-menu" id="overlay-menu" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Menú principal">
        <?php if ($menuHeroVideoUrl): ?>
        <div class="overlay-menu-image overlay-menu-image--video" aria-hidden="true">
            <video src="<?=$h($menuHeroVideoUrl)?>"
                   <?= $menuHeroPoster ? 'poster="'.$h($menuHeroPoster).'"' : '' ?>
                   muted loop playsinline preload="metadata"></video>
        </div>
        <?php else: ?>
        <div class="overlay-menu-image" style="background-image:url('/assets/img/hero-bg.png');" aria-hidden="true"></div>
        <?php endif; ?>
        <div class="overlay-menu-panel">
            <button class="overlay-menu-close" id="overlay-menu-close" aria-label="Cerrar menú">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="overlay-menu-inner">
                <span class="overlay-menu-eyebrow">Menú</span>
                <nav class="overlay-menu-list" aria-label="Navegación principal">
                    <a href="/">Inicio</a>
                    <a href="/estudio">Estudio</a>
                    <a href="/#proyectos">Proyectos</a>
                    <a href="/#servicios">Servicios</a>
                    <a href="/#contacto">Contacto</a>
                </nav>
                <div class="overlay-menu-social">
                    <?php if (!empty($S['instagram'])): ?>
                    <a href="<?=$h($S['instagram'])?>" target="_blank" rel="noopener" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($S['linkedin'])): ?>
                    <a href="<?=$h($S['linkedin'])?>" target="_blank" rel="noopener" aria-label="LinkedIn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ESTUDIO HERO + BIO -->
    <section class="estudio-section">
        <div class="container">
            <nav class="pd-breadcrumb" aria-label="breadcrumb" style="padding-top:8rem;">
                <a href="/">Inicio</a>
                <span class="pd-breadcrumb-sep">/</span>
                <span class="pd-breadcrumb-current">Estudio</span>
            </nav>

            <div class="estudio-grid">
                <div class="estudio-text">
                    <span class="section-label">Estudio</span>
                    <h1 class="estudio-title">Sobre <?=$h($S['siteName'])?></h1>

                    <p class="estudio-lead">
                        <strong>Álvaro Vergara J.</strong> es el arquitecto fundador de Estudio FARE Arquitectura,
                        ubicado en Pucón, en la zona sur de Chile.
                    </p>

                    <div class="estudio-block">
                        <span class="estudio-eyebrow">Nuestro enfoque</span>
                        <p>
                            En el estudio diseñamos viviendas que generan integración con su entorno natural,
                            combinando técnicas y detalles tradicionales con innovaciones contemporáneas. Cada
                            proyecto busca eficiencia energética, confort y resistencia a las condiciones
                            climáticas locales del sur de Chile.
                        </p>
                    </div>

                    <div class="estudio-block">
                        <span class="estudio-eyebrow">Cómo trabajamos</span>
                        <p>
                            Como estudio nos dedicamos a acompañar a los clientes desde el inicio de cada
                            proyecto, encargándonos del trabajo completo: diseño, entrega de proyecto listo
                            para construir, permisos y recepciones municipales, visitas a terreno y seguimiento
                            de obra.
                        </p>
                    </div>

                    <div class="estudio-meta">
                        <div>
                            <span class="estudio-eyebrow">Ubicación</span>
                            <p>Pucón, Región de la Araucanía — Chile</p>
                        </div>
                        <div>
                            <span class="estudio-eyebrow">Servicios</span>
                            <p>Diseño · Gestión integral · Visualización 3D</p>
                        </div>
                    </div>

                    <div class="estudio-cta">
                        <a href="/#contacto" class="btn-outline-light">Conversemos sobre tu proyecto</a>
                    </div>
                </div>

                <div class="estudio-image">
                    <img src="<?=$h($alvaroImg)?>" alt="Álvaro Vergara J., arquitecto fundador de <?=$h($S['siteName'])?>" loading="eager">
                    <span class="estudio-image-caption">Álvaro Vergara J. — Arquitecto fundador</span>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER (igual al home) -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="/" class="nav-brand">
                        <?php if ($logoNegative): ?>
                            <img src="<?=$h($logoNegative)?>" alt="<?=$h($S['siteName'])?>" style="height:36px;width:auto;">
                        <?php elseif ($logoNormal): ?>
                            <img src="<?=$h($logoNormal)?>" alt="<?=$h($S['siteName'])?>" style="height:36px;width:auto;">
                        <?php else: ?>
                            <?=$svgLogo?>
                            <span><?=$h($S['siteName'])?></span>
                        <?php endif; ?>
                    </a>
                    <p><?=$h($S['siteDescription'])?></p>
                </div>
                <div class="footer-col">
                    <h4>Navegación</h4>
                    <a href="/">Inicio</a>
                    <a href="/estudio">Estudio</a>
                    <a href="/#proyectos">Proyectos</a>
                    <a href="/#contacto">Contacto</a>
                </div>
                <div class="footer-col">
                    <h4>Servicios</h4>
                    <a href="/#servicios">Diseño</a>
                    <a href="/#servicios">Gestión Integral</a>
                    <a href="/#servicios">Visualización 3D</a>
                </div>
                <div class="footer-col">
                    <h4>Contacto</h4>
                    <?php if (!empty($S['email'])): ?>
                    <a href="mailto:<?=$h($S['email'])?>"><?=$h($S['email'])?></a>
                    <?php endif; ?>
                    <?php if (!empty($S['phone'])): ?>
                    <a href="tel:<?=$phoneClean?>"><?=$h($S['phone'])?></a>
                    <?php endif; ?>
                    <?php if (!empty($S['address'])): ?>
                    <span style="color:rgba(255,255,255,0.5);font-size:0.85rem;"><?=$h($S['address'])?></span>
                    <?php endif; ?>
                    <?php
                    $rawIg = trim($S['instagram'] ?? '');
                    $igHandle = '';
                    $igUrl = '';
                    if ($rawIg) {
                        if (preg_match('#instagram\.com/([^/?#]+)#i', $rawIg, $mIg)) {
                            $igHandle = $mIg[1];
                            $igUrl = $rawIg;
                        } else {
                            $igHandle = ltrim($rawIg, '@');
                            $igUrl = 'https://instagram.com/' . $igHandle;
                        }
                    }
                    ?>
                    <?php if ($igHandle): ?>
                    <a href="<?=$h($igUrl)?>" target="_blank" rel="noopener" class="footer-ig">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                        <span>@<?=$h($igHandle)?></span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; <?=date('Y')?> <?=$h($S['siteName'])?>. Todos los derechos reservados.</span>
                <div class="footer-social">
                    <?php foreach ($socials as $social): ?>
                    <a href="<?=$h($social['url'])?>" target="_blank" rel="noopener" aria-label="<?=$social['label']?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><?=$social['icon']?></svg></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </footer>

    <script src="/assets/js/app.js?v=<?=$v?>"></script>
</body>
</html>
