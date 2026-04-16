<?php
/**
 * ============================================
 * PLUGIN: Portfolio — Project Detail Page
 * ============================================
 * Shows a single project with gallery lightbox, tech details, and navigation.
 * URL: /portafolio/proyecto?slug=nombre-proyecto
 */
$v = time();
$slug = $_GET['slug'] ?? '';

// Load site info
$S = [
    'siteName' => 'MiSitio', 'siteDescription' => 'Soluciones profesionales para tu negocio',
    'phone' => '+56 9 1234 5678', 'email' => 'contacto@tusitio.com',
    'whatsapp' => '', 'address' => 'Santiago, Chile',
    'instagram' => '', 'facebook' => '', 'youtube' => '', 'linkedin' => '',
    'twitter' => '', 'pinterest' => '', 'tiktok' => '',
];
$project = null;
try {
    $cfgPath = __DIR__ . '/../../config/database.php';
    if (file_exists($cfgPath)) {
        require_once $cfgPath;
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // Load site info
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

        // Load project by slug
        if ($slug) {
            $pStmt = $pdo->prepare("SELECT * FROM portfolio_projects WHERE slug = ? AND status = 'published'");
            $pStmt->execute([$slug]);
            $project = $pStmt->fetch();

            if ($project) {
                // Load images
                $imgStmt = $pdo->prepare("SELECT * FROM portfolio_images WHERE project_id = ? ORDER BY sort_order ASC, id ASC");
                $imgStmt->execute([$project['id']]);
                $project['images'] = $imgStmt->fetchAll();

                // Prev/Next
                $prevStmt = $pdo->prepare("SELECT slug, title FROM portfolio_projects WHERE status = 'published' AND (sort_order < ? OR (sort_order = ? AND id < ?)) ORDER BY sort_order DESC, id DESC LIMIT 1");
                $prevStmt->execute([$project['sort_order'], $project['sort_order'], $project['id']]);
                $project['prev'] = $prevStmt->fetch() ?: null;

                $nextStmt = $pdo->prepare("SELECT slug, title FROM portfolio_projects WHERE status = 'published' AND (sort_order > ? OR (sort_order = ? AND id > ?)) ORDER BY sort_order ASC, id ASC LIMIT 1");
                $nextStmt->execute([$project['sort_order'], $project['sort_order'], $project['id']]);
                $project['next'] = $nextStmt->fetch() ?: null;
            }
        }
    }
} catch (Exception $e) {}

$phoneClean = preg_replace('/[^0-9+]/', '', $S['phone']);
$h = function($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };
$logoNormal   = !empty($S['logo_normal'])   ? $S['logo_normal']   : '';
$logoNegative = !empty($S['logo_negative']) ? $S['logo_negative'] : '';
$svgLogo = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;"><path d="M12 2 L2 7 L12 12 L22 7 Z"/><path d="M2 17 L12 22 L22 17"/><path d="M2 12 L12 17 L22 12"/></svg>';

$categoryLabels = [
    'residencial' => 'Residencial', 'comercial' => 'Comercial', 'institucional' => 'Institucional',
    'interiorismo' => 'Interiorismo', 'paisajismo' => 'Paisajismo', 'restauracion' => 'Restauración',
    'industrial' => 'Industrial', 'otro' => 'Otro'
];

// 404 if not found
if (!$project) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($project): ?>
    <meta name="description" content="<?=$h($project['title'])?> — <?=$h($S['siteName'])?>. <?=$h(substr($project['description'] ?? '', 0, 150))?>">
    <title><?=$h($project['title'])?> — <?=$h($S['siteName'])?></title>
    <?php else: ?>
    <meta name="description" content="Proyecto no encontrado — <?=$h($S['siteName'])?>">
    <title>Proyecto no encontrado — <?=$h($S['siteName'])?></title>
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/assets/css/variables.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/base.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/public.css?v=<?=$v?>">
    <link rel="stylesheet" href="/plugins/portfolio/public.css?v=<?=$v?>">
    <link rel="stylesheet" href="/api/theme.css.php">
</head>
<body>

    <!-- HEADER -->
    <header class="site-header scrolled" id="header">
        <nav class="nav container">
            <a href="/" class="nav-brand">
                <?php if ($logoNegative || $logoNormal): ?>
                    <?php if ($logoNegative): ?><img src="<?=$h($logoNegative)?>" alt="<?=$h($S['siteName'])?>" class="brand-logo brand-logo-negative" style="height:40px;width:auto;"><?php endif; ?>
                    <?php if ($logoNormal): ?><img src="<?=$h($logoNormal)?>" alt="<?=$h($S['siteName'])?>" class="brand-logo brand-logo-normal" style="height:40px;width:auto;"><?php endif; ?>
                <?php else: ?>
                    <?=$svgLogo?>
                    <span><?=$h($S['siteName'])?></span>
                <?php endif; ?>
            </a>
            <div class="nav-links" id="nav-links">
                <a href="/">Inicio</a>
                <a href="/#nosotros">Nosotros</a>
                <a href="/#servicios">Servicios</a>
                <a href="/portafolio" class="active">Portafolio</a>
                <a href="/#contacto">Contacto</a>
            </div>
            <button class="nav-toggle" id="nav-toggle" aria-label="Abrir menú" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </nav>
    </header>

    <?php if ($project): ?>

    <!-- PROJECT HERO -->
    <section class="portfolio-detail-hero">
        <?php if ($project['featured_image']): ?>
        <div class="portfolio-detail-hero-bg">
            <img src="<?=$h($project['featured_image'])?>" alt="<?=$h($project['title'])?>">
            <div class="portfolio-detail-hero-overlay"></div>
        </div>
        <?php endif; ?>
        <div class="container portfolio-detail-hero-content">
            <a href="/portafolio" class="portfolio-back-link">← Volver al Portafolio</a>
            <span class="portfolio-detail-cat"><?=$categoryLabels[$project['category']] ?? $project['category']?></span>
            <h1><?=$h($project['title'])?></h1>
            <div class="portfolio-detail-hero-meta">
                <?php if ($project['location']): ?><span>📍 <?=$h($project['location'])?></span><?php endif; ?>
                <?php if ($project['year']): ?><span>📅 <?=$h($project['year'])?></span><?php endif; ?>
                <?php if ($project['area_m2']): ?><span>📐 <?=number_format($project['area_m2'], 0, ',', '.')?> m²</span><?php endif; ?>
                <?php if ($project['client_name']): ?><span>👤 <?=$h($project['client_name'])?></span><?php endif; ?>
            </div>
        </div>
    </section>

    <!-- PROJECT CONTENT -->
    <section class="section portfolio-detail-content">
        <div class="container">
            <div class="portfolio-detail-grid">
                <!-- Description -->
                <div class="portfolio-detail-body">
                    <?php if ($project['description']): ?>
                    <div class="portfolio-detail-desc">
                        <h2>Sobre el Proyecto</h2>
                        <?=nl2br($h($project['description']))?>
                    </div>
                    <?php endif; ?>

                    <!-- Tech Card -->
                    <div class="portfolio-tech-card">
                        <h3>Ficha Técnica</h3>
                        <div class="portfolio-tech-grid">
                            <?php if ($project['client_name']): ?>
                            <div class="portfolio-tech-item"><span class="label">Cliente</span><span class="value"><?=$h($project['client_name'])?></span></div>
                            <?php endif; ?>
                            <?php if ($project['location']): ?>
                            <div class="portfolio-tech-item"><span class="label">Ubicación</span><span class="value"><?=$h($project['location'])?></span></div>
                            <?php endif; ?>
                            <?php if ($project['year']): ?>
                            <div class="portfolio-tech-item"><span class="label">Año</span><span class="value"><?=$h($project['year'])?></span></div>
                            <?php endif; ?>
                            <?php if ($project['area_m2']): ?>
                            <div class="portfolio-tech-item"><span class="label">Superficie</span><span class="value"><?=number_format($project['area_m2'], 0, ',', '.')?> m²</span></div>
                            <?php endif; ?>
                            <div class="portfolio-tech-item"><span class="label">Categoría</span><span class="value"><?=$categoryLabels[$project['category']] ?? $project['category']?></span></div>
                        </div>
                        <?php if ($project['tags']): ?>
                        <div class="portfolio-tags">
                            <?php foreach (explode(',', $project['tags']) as $tag): ?>
                            <span class="portfolio-tag"><?=$h(trim($tag))?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Gallery -->
                <?php if (!empty($project['images'])): ?>
                <div class="portfolio-detail-gallery">
                    <h2>Galería</h2>
                    <div class="portfolio-gallery-grid-public">
                        <?php foreach ($project['images'] as $i => $img): ?>
                        <div class="portfolio-gallery-thumb" onclick="openLightbox(<?=$i?>)">
                            <img src="<?=$h($img['image_url'])?>" alt="<?=$h($img['caption'] ?: $project['title'])?>" loading="lazy">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Project Navigation -->
            <div class="portfolio-nav">
                <?php if ($project['prev']): ?>
                <a href="/portafolio/proyecto?slug=<?=urlencode($project['prev']['slug'])?>" class="portfolio-nav-link portfolio-nav-prev">
                    <span class="portfolio-nav-dir">← Anterior</span>
                    <span class="portfolio-nav-title"><?=$h($project['prev']['title'])?></span>
                </a>
                <?php else: ?>
                <div></div>
                <?php endif; ?>

                <?php if ($project['next']): ?>
                <a href="/portafolio/proyecto?slug=<?=urlencode($project['next']['slug'])?>" class="portfolio-nav-link portfolio-nav-next">
                    <span class="portfolio-nav-dir">Siguiente →</span>
                    <span class="portfolio-nav-title"><?=$h($project['next']['title'])?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="portfolio-cta">
        <div class="container" style="text-align:center;">
            <h2>¿Te gustaría un proyecto como este?</h2>
            <p>Conversemos sobre cómo hacer realidad tu idea.</p>
            <a href="/#contacto" class="btn btn-accent btn-lg">Solicitar Presupuesto →</a>
        </div>
    </section>

    <!-- LIGHTBOX -->
    <?php if (!empty($project['images'])): ?>
    <div class="portfolio-lightbox" id="lightbox">
        <button class="lightbox-close" onclick="closeLightbox()">✕</button>
        <button class="lightbox-prev" onclick="lightboxNav(-1)">‹</button>
        <button class="lightbox-next" onclick="lightboxNav(1)">›</button>
        <div class="lightbox-img-wrap">
            <img id="lightbox-img" src="" alt="">
            <p id="lightbox-caption" style="color:rgba(255,255,255,0.7);text-align:center;margin-top:12px;font-size:14px;"></p>
        </div>
    </div>
    <script>
    const lbImages = <?=json_encode(array_map(function($img) { return ['url' => $img['image_url'], 'caption' => $img['caption'] ?? '']; }, $project['images']))?>;
    let lbIndex = 0;
    function openLightbox(i) {
        lbIndex = i;
        document.getElementById('lightbox-img').src = lbImages[i].url;
        document.getElementById('lightbox-caption').textContent = lbImages[i].caption || '';
        document.getElementById('lightbox').classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        document.getElementById('lightbox').classList.remove('active');
        document.body.style.overflow = '';
    }
    function lightboxNav(dir) {
        lbIndex = (lbIndex + dir + lbImages.length) % lbImages.length;
        document.getElementById('lightbox-img').src = lbImages[lbIndex].url;
        document.getElementById('lightbox-caption').textContent = lbImages[lbIndex].caption || '';
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
    </script>
    <?php endif; ?>

    <?php else: ?>
    <!-- 404: Project Not Found -->
    <section class="section" style="padding-top:calc(var(--header-height, 80px) + 6rem);text-align:center;min-height:60vh;display:flex;align-items:center;justify-content:center;">
        <div>
            <div style="font-size:5rem;margin-bottom:1rem;">🔍</div>
            <h1 style="color:var(--text-primary);">Proyecto no encontrado</h1>
            <p style="color:var(--text-secondary);margin:1rem 0 2rem;">El proyecto que buscas no existe o aún no ha sido publicado.</p>
            <a href="/portafolio" class="btn btn-primary btn-lg">← Ver todos los proyectos</a>
        </div>
    </section>
    <?php endif; ?>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-bottom">
                <span>&copy; <?=date('Y')?> <?=$h($S['siteName'])?>. Todos los derechos reservados.</span>
            </div>
        </div>
    </footer>

    <script src="/assets/js/app.js?v=<?=$v?>"></script>
</body>
</html>
