<?php
/**
 * ============================================
 * PLUGIN: Portfolio — Project Detail Page
 * ============================================
 * Design aligned with the homepage: dark background, Raleway light
 * typography, SVG icons (no emojis). Reached via /proyecto/{slug}.
 */
$v = time();
$slug = $_GET['slug'] ?? '';

// Site info defaults
$S = [
    'siteName' => 'MiSitio', 'siteDescription' => 'Soluciones profesionales para tu negocio',
    'phone' => '+56 9 1234 5678', 'email' => 'contacto@tusitio.com',
    'whatsapp' => '', 'address' => 'Santiago, Chile',
    'instagram' => '', 'facebook' => '', 'youtube' => '', 'linkedin' => '',
    'twitter' => '', 'pinterest' => '', 'tiktok' => '',
];
$project = null;
$others = [];
try {
    $cfgPath = __DIR__ . '/../../config/database.php';
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

        if ($slug) {
            $pStmt = $pdo->prepare("SELECT * FROM portfolio_projects WHERE slug = ? AND status = 'published'");
            $pStmt->execute([$slug]);
            $project = $pStmt->fetch();

            if ($project) {
                $imgStmt = $pdo->prepare("SELECT * FROM portfolio_images WHERE project_id = ? ORDER BY sort_order ASC, id ASC");
                $imgStmt->execute([$project['id']]);
                $project['images'] = $imgStmt->fetchAll();

                $vidStmt = $pdo->prepare("SELECT * FROM portfolio_videos WHERE project_id = ? ORDER BY sort_order ASC, id ASC");
                $vidStmt->execute([$project['id']]);
                $project['videos'] = $vidStmt->fetchAll();

                $oStmt = $pdo->prepare("SELECT slug, title, location, area_m2, featured_image, tags FROM portfolio_projects WHERE status = 'published' AND id != ? ORDER BY sort_order ASC, id ASC LIMIT 3");
                $oStmt->execute([$project['id']]);
                $others = $oStmt->fetchAll();
            }
        }
    }
} catch (Exception $e) {}

$phoneClean = preg_replace('/[^0-9+]/', '', $S['phone']);
$h = function($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };

// Hero videos del home (para el menú overlay)
$menuHeroVideoUrl = null;
$menuHeroPoster = null;
try {
    $cloudCfgFile = @include __DIR__ . '/../../config/cloudinary.php';
    $heroCloud = (is_array($cloudCfgFile) && !empty($cloudCfgFile['cloudName'])) ? $cloudCfgFile['cloudName'] : null;
    $heroTx = (is_array($cloudCfgFile) && !empty($cloudCfgFile['transform'])) ? $cloudCfgFile['transform'] : 'q_auto,f_auto';
    $heroPid = null;
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
        $heroCfg = @include __DIR__ . '/../../config/hero-videos.php';
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
$logoNormal   = !empty($S['logo_normal'])   ? $S['logo_normal']   : '/assets/img/logo-negative.png';
$logoNegative = !empty($S['logo_negative']) ? $S['logo_negative'] : '/assets/img/logo-negative.png';
$svgLogo = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;"><path d="M12 2 L2 7 L12 12 L22 7 Z"/><path d="M2 17 L12 22 L22 17"/><path d="M2 12 L12 17 L22 12"/></svg>';

$socials = [];
if (!empty($S['facebook']))  $socials[] = ['url' => $S['facebook'],  'label' => 'Facebook',  'icon' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>'];
if (!empty($S['instagram'])) $socials[] = ['url' => $S['instagram'], 'label' => 'Instagram', 'icon' => '<rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>'];
if (!empty($S['linkedin']))  $socials[] = ['url' => $S['linkedin'],  'label' => 'LinkedIn',  'icon' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>'];
if (!empty($S['youtube']))   $socials[] = ['url' => $S['youtube'],   'label' => 'YouTube',   'icon' => '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19.13C5.12 19.56 12 19.56 12 19.56s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2A29 29 0 0 0 23 11.75a29 29 0 0 0-.46-5.33zM9.75 15.02V8.48l5.75 3.27-5.75 3.27z"/>'];

// SVG icon helpers (24×24 viewBox, stroke currentColor)
$icon = [
    'pin'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
    'calendar' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    'ruler'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 15.3 8.7 2.7a1 1 0 0 0-1.4 0L2.7 7.3a1 1 0 0 0 0 1.4l12.6 12.6a1 1 0 0 0 1.4 0l4.6-4.6a1 1 0 0 0 0-1.4z"/><path d="m7.5 10.5 1 1"/><path d="m10.5 7.5 1 1"/><path d="m13.5 10.5 1 1"/><path d="m10.5 13.5 1 1"/><path d="m16.5 13.5 1 1"/></svg>',
    'user'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'tag'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
    'arrow_l'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>',
    'arrow_r'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>',
    'close'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    'chev_l'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>',
    'chev_r'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>',
    'search'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
];

if (!$project) http_response_code(404);

$pageTitle = $project
    ? $project['title'] . ' — ' . $S['siteName']
    : 'Proyecto no encontrado — ' . $S['siteName'];

// Split tags; first one becomes the status chip
$statusChip = '';
$otherTags  = [];
if ($project && !empty($project['tags'])) {
    $parts = array_map('trim', explode(',', $project['tags']));
    $parts = array_values(array_filter($parts));
    if (!empty($parts)) {
        $statusChip = array_shift($parts);
        $otherTags  = $parts;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?=$project ? $h(substr($project['description'] ?? '', 0, 160)) : 'Proyecto no encontrado'?>">
    <meta name="theme-color" content="#0a0a0a">
    <title><?=$h($pageTitle)?></title>

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
<body class="project-page is-loading">
    <!-- Page loader -->
    <div id="page-loader" aria-hidden="true">
        <img id="page-loader-logo" src="<?=$h($logoNegative ?: $logoNormal ?: '/assets/img/logo-negative.png')?>" alt="">
    </div>

    <!-- HEADER (matches home: solo marca + hamburguesa) -->
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

    <!-- OVERLAY MENU (Foster + Partners style) -->
    <?php
    // Imagen del menú: foto del proyecto actual si tiene, fallback hero-bg
    $menuImage = !empty($project['featured_image']) ? $project['featured_image'] : '/assets/img/hero-bg.png';
    $instagramUrl = $S['instagram'] ?? '';
    $linkedinUrl  = $S['linkedin']  ?? '';
    ?>
    <div class="overlay-menu" id="overlay-menu" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Menú principal">
        <?php if ($menuHeroVideoUrl): ?>
        <div class="overlay-menu-image overlay-menu-image--video" aria-hidden="true">
            <video src="<?=$h($menuHeroVideoUrl)?>"
                   <?= $menuHeroPoster ? 'poster="'.$h($menuHeroPoster).'"' : '' ?>
                   muted loop playsinline preload="metadata"></video>
        </div>
        <?php else: ?>
        <div class="overlay-menu-image" style="background-image:url('<?=$h($menuImage)?>');" aria-hidden="true"></div>
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
                    <a href="<?= $instagramUrl ? $h($instagramUrl) : 'https://instagram.com/FARE_Arquitectura' ?>" target="_blank" rel="noopener" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    <?php if ($linkedinUrl): ?>
                    <a href="<?=$h($linkedinUrl)?>" target="_blank" rel="noopener" aria-label="LinkedIn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$project): ?>
    <section class="pd-notfound">
        <div class="container">
            <div class="pd-notfound-icon"><?=$icon['search']?></div>
            <span class="section-label">404</span>
            <h1>Proyecto no encontrado</h1>
            <p>El proyecto que buscas no existe o fue movido.</p>
            <a href="/#proyectos" class="btn btn-dark btn-lg">Ver proyectos destacados</a>
        </div>
    </section>
    <?php else: ?>

    <!-- HERO -->
    <?php
    // Buscar video destacado (is_featured=1) en este proyecto
    $heroVideo = null;
    if (!empty($project['videos'])) {
        foreach ($project['videos'] as $vv) {
            if (!empty($vv['is_featured'])) { $heroVideo = $vv; break; }
        }
    }
    // Solo soportamos como bg los que se pueden reproducir en background:
    // Cloudinary upload o MP4 directo. YouTube/Vimeo iframe no se usa.
    $heroVideoUrl = null;
    if ($heroVideo) {
        $type = $heroVideo['video_type'] ?? 'other';
        if (in_array($type, ['upload', 'other'], true) && preg_match('#\.(mp4|webm|mov)$|res\.cloudinary\.com/.+/video/upload/#i', $heroVideo['video_url'])) {
            $heroVideoUrl = $heroVideo['video_url'];
        }
    }
    ?>
    <section class="pd-hero<?= $heroVideoUrl ? ' pd-hero--video' : '' ?>">
        <?php if ($heroVideoUrl): ?>
        <div class="pd-hero-bg">
            <video src="<?=$h($heroVideoUrl)?>"
                   <?php if (!empty($project['featured_image'])): ?>poster="<?=$h($project['featured_image'])?>"<?php endif; ?>
                   autoplay muted loop playsinline preload="auto"></video>
            <div class="pd-hero-overlay"></div>
        </div>
        <?php elseif (!empty($project['featured_image'])): ?>
        <div class="pd-hero-bg">
            <img src="<?=$h($project['featured_image'])?>" alt="<?=$h($project['title'])?>">
            <div class="pd-hero-overlay"></div>
        </div>
        <?php endif; ?>
        <div class="container pd-hero-inner">
            <div class="pd-hero-text">
                <nav class="pd-breadcrumb" aria-label="breadcrumb">
                    <a href="/">Inicio</a>
                    <span class="pd-breadcrumb-sep">/</span>
                    <a href="/#proyectos">Proyectos</a>
                    <span class="pd-breadcrumb-sep">/</span>
                    <span class="pd-breadcrumb-current"><?=$h($project['title'])?></span>
                </nav>
                <h1><?=$h($project['title'])?></h1>
                <div class="pd-meta">
                    <?php if ($project['location']): ?>
                    <span class="pd-meta-item"><?=$icon['pin']?><?=$h($project['location'])?></span>
                    <?php endif; ?>
                    <?php if ($project['area_m2']): ?>
                    <span class="pd-meta-item"><?=$icon['ruler']?><?=number_format($project['area_m2'], 0, ',', '.')?> m²</span>
                    <?php endif; ?>
                    <?php if ($project['client_name']): ?>
                    <span class="pd-meta-item"><?=$icon['user']?><?=$h($project['client_name'])?></span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </section>

    <!-- CONTENT -->
    <?php
    $materials = !empty($project['materials']) ? array_filter(array_map('trim', explode(',', $project['materials']))) : [];
    $program   = !empty($project['program'])   ? array_filter(array_map('trim', explode(',', $project['program'])))   : [];

    // Helpers para videos
    $videoThumb = function($v) {
        $url = $v['video_url']; $type = $v['video_type'] ?? 'other';
        // YouTube
        if (preg_match('#(?:youtube\.com.*[?&]v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})#', $url, $m)) {
            return "https://i.ytimg.com/vi/{$m[1]}/hqdefault.jpg";
        }
        // Cloudinary upload — preservar el path completo del public_id (incluye carpetas)
        if (preg_match('#^(https?://res\.cloudinary\.com/[^/]+/video/upload/)(.+?)\.[a-z0-9]+(\?.*)?$#i', $url, $m)) {
            $base = $m[1];
            $rest = $m[2]; // puede contener "v1234/folder/file" o "transform/v1234/folder/file"
            // Quitar versión "vNNN/" si está al inicio
            $rest = preg_replace('#^v\d+/#', '', $rest);
            // Quitar transformaciones previas (segmento con coma) hasta llegar al public_id real
            // ej: "q_auto,f_auto/v1234/folder/file" → "v1234/folder/file" → "folder/file"
            while (preg_match('#^[a-z]_[^/]+(?:,[a-z]_[^/]+)*/(.+)$#i', $rest, $mm)) {
                $rest = $mm[1];
                $rest = preg_replace('#^v\d+/#', '', $rest);
            }
            return $base . 'q_auto,f_auto,so_0,w_800,h_600,c_fill/' . $rest . '.jpg';
        }
        return null;
    };
    $videos = !empty($project['videos']) ? $project['videos'] : [];
    ?>
    <section class="pd-content">
        <div class="container">
            <?php if ($project['description']): ?>
            <div class="pd-intro">
                <span class="section-label">Sobre el proyecto</span>
                <?php foreach (preg_split('/\R\R+/u', trim($project['description'])) as $paragraph): ?>
                <p><?=$h($paragraph)?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="pd-content-grid">
            <div class="pd-body">
                <?php if (!empty($project['images']) || !empty($videos)): ?>
                <div class="pd-media">
                    <?php $hasImages = !empty($project['images']); $hasVideos = !empty($videos); ?>
                    <?php if ($hasImages && $hasVideos): ?>
                    <div class="pd-media-tabs" role="tablist">
                        <button type="button" class="pd-media-tab is-active" data-tab="images" role="tab" aria-selected="true">Imágenes</button>
                        <button type="button" class="pd-media-tab" data-tab="videos" role="tab" aria-selected="false">Videos</button>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasImages): ?>
                    <div class="pd-media-panel is-active" data-panel="images">
                        <div class="pd-gallery-grid">
                            <?php foreach ($project['images'] as $i => $img): ?>
                            <button type="button" class="pd-gallery-item" onclick="openLightbox(<?=$i?>)" aria-label="Ver imagen <?=($i+1)?>">
                                <img src="<?=$h($img['image_url'])?>" alt="<?=$h($img['caption'] ?: $project['title'])?>" loading="lazy">
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasVideos): ?>
                    <div class="pd-media-panel<?= !$hasImages ? ' is-active' : '' ?>" data-panel="videos">
                        <div class="pd-gallery-grid">
                            <?php foreach ($videos as $i => $vid): $thumb = $videoThumb($vid); ?>
                            <button type="button" class="pd-gallery-item pd-video-item" onclick="openVideoLightbox(<?=$i?>)" aria-label="Ver video <?=($i+1)?>">
                                <?php if ($thumb): ?>
                                    <img src="<?=$h($thumb)?>" alt="<?=$h($vid['title'] ?: 'Video')?>" loading="lazy">
                                <?php else: ?>
                                    <video src="<?=$h($vid['video_url'])?>" preload="metadata" muted playsinline></video>
                                <?php endif; ?>
                                <span class="pd-video-play" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="6 4 20 12 6 20 6 4"/></svg>
                                </span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <aside class="pd-aside">
                <div class="pd-card">
                    <span class="section-label">Ficha técnica</span>
                    <dl class="pd-datasheet">
                        <?php if ($project['location']): ?>
                        <div class="pd-datasheet-row"><dt>Ubicación</dt><dd><?=$h($project['location'])?></dd></div>
                        <?php endif; ?>
                        <?php if ($project['area_m2']): ?>
                        <div class="pd-datasheet-row"><dt>Superficie</dt><dd><?=number_format($project['area_m2'], 0, ',', '.')?> m²</dd></div>
                        <?php endif; ?>
                    </dl>

                    <?php if (!empty($materials)): ?>
                    <div class="pd-spec-block">
                        <span class="section-label">Materialidades</span>
                        <div class="pd-tags">
                            <?php foreach ($materials as $m): ?>
                            <span class="pd-tag"><?=$h($m)?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($program)): ?>
                    <div class="pd-spec-block">
                        <span class="section-label">Programa</span>
                        <div class="pd-tags">
                            <?php foreach ($program as $pg): ?>
                            <span class="pd-tag"><?=$h($pg)?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </aside>
            </div><!-- /.pd-content-grid -->
        </div>
    </section>

    <?php if (!empty($others)): ?>
    <!-- OTHER PROJECTS -->
    <section class="pd-others">
        <div class="container">
            <div class="projects-header" data-animate="fadeInUp">
                <span class="section-label">Otros proyectos</span>
                <h2>Continúa explorando</h2>
            </div>
            <div class="projects-grid">
                <?php foreach ($others as $i => $p): ?>
                <?php
                    $oStatus = '';
                    if (!empty($p['tags'])) {
                        $parts = explode(',', $p['tags']);
                        $oStatus = trim($parts[0]);
                    }
                    $oSurface = !empty($p['area_m2']) ? number_format((float)$p['area_m2'], 0, ',', '.') . ' m²' : '';
                    $oImg = !empty($p['featured_image']) ? $p['featured_image'] : '/assets/img/proyecto-' . $p['slug'] . '.png';
                ?>
                <a class="project-card" href="/proyecto/<?=$h($p['slug'])?>" data-animate="fadeInUp" data-delay="<?=$i * 80?>">
                    <div class="project-card-thumb">
                        <img src="<?=$h($oImg)?>" alt="<?=$h($p['title'])?>" loading="lazy">
                    </div>
                    <div class="project-card-body">
                        <?php if ($oStatus): ?><span class="project-card-status"><?=$h($oStatus)?></span><?php endif; ?>
                        <h3><?=$h($p['title'])?></h3>
                        <div class="project-card-meta">
                            <?php if ($oSurface): ?><span><?=$h($oSurface)?></span><span class="project-card-dot">·</span><?php endif; ?>
                            <span><?=$h($p['location'])?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- LIGHTBOX (imágenes y videos) -->
    <?php if (!empty($project['images']) || !empty($videos)): ?>
    <div class="pd-lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Galería">
        <button class="pd-lightbox-btn pd-lightbox-close" onclick="closeLightbox()" aria-label="Cerrar"><?=$icon['close']?></button>
        <button class="pd-lightbox-btn pd-lightbox-prev" onclick="lightboxNav(-1)" aria-label="Anterior"><?=$icon['chev_l']?></button>
        <button class="pd-lightbox-btn pd-lightbox-next" onclick="lightboxNav(1)" aria-label="Siguiente"><?=$icon['chev_r']?></button>
        <div class="pd-lightbox-content">
            <img id="lightbox-img" src="" alt="" style="display:none;">
            <div id="lightbox-video" style="display:none;width:min(90vw,1280px);aspect-ratio:16/9;"></div>
            <p id="lightbox-caption"></p>
        </div>
    </div>
    <script>
    const lbImages = <?=json_encode(array_map(function($img) { return ['url' => $img['image_url'], 'caption' => $img['caption'] ?? '']; }, $project['images'] ?? []))?>;
    const lbVideos = <?=json_encode(array_map(function($v) { return ['url' => $v['video_url'], 'type' => $v['video_type'] ?? 'other', 'title' => $v['title'] ?? '']; }, $videos))?>;
    let lbMode = 'image'; // 'image' | 'video'
    let lbIndex = 0;

    function lbRender() {
        const imgEl = document.getElementById('lightbox-img');
        const vidEl = document.getElementById('lightbox-video');
        const capEl = document.getElementById('lightbox-caption');
        vidEl.innerHTML = '';
        if (lbMode === 'image') {
            const it = lbImages[lbIndex];
            imgEl.src = it.url;
            imgEl.style.display = '';
            vidEl.style.display = 'none';
            capEl.textContent = it.caption || '';
        } else {
            const v = lbVideos[lbIndex];
            imgEl.style.display = 'none';
            vidEl.style.display = '';
            let html = '';
            if (v.type === 'youtube') {
                const m = v.url.match(/(?:v=|youtu\.be\/|\/embed\/)([A-Za-z0-9_-]{6,})/);
                if (m) html = `<iframe src="https://www.youtube.com/embed/${m[1]}?autoplay=1&rel=0" allow="autoplay; encrypted-media" allowfullscreen style="width:100%;height:100%;border:0;border-radius:8px;"></iframe>`;
            } else if (v.type === 'vimeo') {
                const m = v.url.match(/vimeo\.com\/(\d+)/);
                if (m) html = `<iframe src="https://player.vimeo.com/video/${m[1]}?autoplay=1" allow="autoplay; fullscreen" allowfullscreen style="width:100%;height:100%;border:0;border-radius:8px;"></iframe>`;
            }
            if (!html) {
                html = `<video src="${v.url}" controls autoplay playsinline style="width:100%;height:100%;border-radius:8px;background:#000;"></video>`;
            }
            vidEl.innerHTML = html;
            capEl.textContent = v.title || '';
        }
    }

    function openLightbox(i) { lbMode = 'image'; lbIndex = i; lbRender(); document.getElementById('lightbox').classList.add('active'); document.body.style.overflow = 'hidden'; }
    function openVideoLightbox(i) { lbMode = 'video'; lbIndex = i; lbRender(); document.getElementById('lightbox').classList.add('active'); document.body.style.overflow = 'hidden'; }
    function closeLightbox() {
        document.getElementById('lightbox').classList.remove('active');
        document.getElementById('lightbox-video').innerHTML = '';
        document.body.style.overflow = '';
    }
    function lightboxNav(dir) {
        const arr = lbMode === 'image' ? lbImages : lbVideos;
        if (!arr.length) return;
        lbIndex = (lbIndex + dir + arr.length) % arr.length;
        lbRender();
    }
    document.getElementById('lightbox').addEventListener('click', (e) => {
        if (e.target.id === 'lightbox') closeLightbox();
    });
    document.addEventListener('keydown', (e) => {
        if (!document.getElementById('lightbox').classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') lightboxNav(-1);
        if (e.key === 'ArrowRight') lightboxNav(1);
    });

    // Tabs Imágenes / Videos
    document.querySelectorAll('.pd-media-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            document.querySelectorAll('.pd-media-tab').forEach(t => {
                const active = t.dataset.tab === target;
                t.classList.toggle('is-active', active);
                t.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            document.querySelectorAll('.pd-media-panel').forEach(p => {
                p.classList.toggle('is-active', p.dataset.panel === target);
            });
        });
    });
    </script>
    <?php endif; ?>

    <?php endif; // end $project ?>

    <!-- FOOTER (matches home) -->
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
                    <a href="/#servicios">Servicios</a>
                    <a href="/#contacto">Contacto</a>
                </div>
                <div class="footer-col">
                    <h4>Servicios</h4>
                    <a href="/#servicios">Diseño de Proyectos</a>
                    <a href="/#servicios">Obra Nueva</a>
                    <a href="/#servicios">Ampliaciones</a>
                    <a href="/#contacto">Tramitaciones</a>
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
