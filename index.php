<?php
$v = time();

// ══════════════════════════════════════════════
// LOAD SITE INFO FROM DATABASE (Single Source of Truth)
// ══════════════════════════════════════════════
// All pages should load this at the top and use $S['key'] for dynamic values.
// Defaults are used when nothing is configured yet.
$S = [
    'siteName'        => 'MiSitio',
    'siteDescription' => 'Soluciones profesionales para tu negocio',
    'phone'           => '+56 9 1234 5678',
    'email'           => 'contacto@tusitio.com',
    'whatsapp'        => '',
    'address'         => 'Santiago, Chile',
    'instagram'       => '',
    'facebook'        => '',
    'youtube'         => '',
    'linkedin'        => '',
    'twitter'         => '',
    'pinterest'       => '',
    'tiktok'          => '',
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
                if (is_array($loaded)) {
                    foreach ($loaded as $k => $val) {
                        if (!empty($val)) $S[$k] = $val;
                    }
                }
            } else {
                $S[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
} catch (Exception $e) {}

// Featured projects — loaded from portfolio plugin tables
// (First tag, comma-separated, is used as the visible status chip)
$featuredProjects = [];
try {
    if (isset($pdo)) {
        $fp = $pdo->query("SELECT slug, title, location, year, area_m2, featured_image, tags FROM portfolio_projects WHERE status = 'published' ORDER BY sort_order ASC, id ASC LIMIT 4");
        $featuredProjects = $fp->fetchAll();
    }
} catch (Exception $e) {}

// Helpers
$phoneClean = preg_replace('/[^0-9+]/', '', $S['phone']);
$h = function($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };
$logoNormal   = !empty($S['logo_normal'])   ? $S['logo_normal']   : '/assets/img/logo-negative.png';
$logoNegative = !empty($S['logo_negative']) ? $S['logo_negative'] : '/assets/img/logo-negative.png';
// Default SVG logo fallback
$svgLogo = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;"><path d="M12 2 L2 7 L12 12 L22 7 Z"/><path d="M2 17 L12 22 L22 17"/><path d="M2 12 L12 17 L22 12"/></svg>';

// Collect social links for footer
$socials = [];
if (!empty($S['facebook']))  $socials[] = ['url' => $S['facebook'],  'label' => 'Facebook',  'icon' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>'];
if (!empty($S['instagram'])) $socials[] = ['url' => $S['instagram'], 'label' => 'Instagram', 'icon' => '<rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>'];
if (!empty($S['linkedin']))  $socials[] = ['url' => $S['linkedin'],  'label' => 'LinkedIn',  'icon' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>'];
if (!empty($S['youtube']))   $socials[] = ['url' => $S['youtube'],   'label' => 'YouTube',   'icon' => '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19.13C5.12 19.56 12 19.56 12 19.56s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2A29 29 0 0 0 23 11.75a29 29 0 0 0-.46-5.33zM9.75 15.02V8.48l5.75 3.27-5.75 3.27z"/>'];
if (!empty($S['twitter']))   $socials[] = ['url' => $S['twitter'],   'label' => 'X/Twitter', 'icon' => '<path d="M4 4l11.733 16h4.267l-11.733 -16zM4 20l6.768 -6.768M15.232 10.232l4.768 -6.232"/>'];
if (!empty($S['tiktok']))    $socials[] = ['url' => $S['tiktok'],    'label' => 'TikTok',    'icon' => '<path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"/>'];
if (!empty($S['pinterest'])) $socials[] = ['url' => $S['pinterest'], 'label' => 'Pinterest', 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M8 12c0-2.2 1.8-4 4-4s4 1.8 4 4-1.8 4-4 4"/>'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?=$h($S['siteName'])?> — <?=$h($S['siteDescription'])?>">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#1a1a1a">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?=$h($S['siteName'])?> — <?=$h($S['siteDescription'])?>">
    <meta property="og:description" content="<?=$h($S['siteDescription'])?>">
    <meta property="og:type" content="website">
    
    <title><?=$h($S['siteName'])?> — <?=$h($S['siteDescription'])?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='18' fill='%231a1d2e'/><text x='50' y='68' text-anchor='middle' fill='%23c9a96e' font-size='52' font-weight='bold'><?=substr($S['siteName'],0,1)?></text></svg>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Raleway:wght@200;300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/variables.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/base.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/public.css?v=<?=$v?>">
    <!-- Dynamic theme (loaded from database settings) -->
    <link rel="stylesheet" href="/api/theme.css.php">
</head>
<body>

    <!-- ============================================ -->
    <!-- HEADER / NAVIGATION -->
    <!-- ============================================ -->
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
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </header>

    <!-- ============================================ -->
    <!-- OVERLAY MENU (estilo Foster + Partners) -->
    <!-- ============================================ -->
    <?php
    // Detectar imagen para el menú: primer proyecto del portfolio o hero-bg
    $menuImage = '/assets/img/hero-bg.png';
    $portfolioDir = __DIR__ . '/assets/img';
    foreach (['proyecto-1.png','proyecto-2.png','proyecto-3.png','proyecto-4.png'] as $p) {
        if (file_exists($portfolioDir.'/'.$p)) { $menuImage = '/assets/img/'.$p; break; }
    }
    $instagramUrl = $S['instagram'] ?? $S['socialInstagram'] ?? '';
    $linkedinUrl  = $S['linkedin']  ?? $S['socialLinkedin']  ?? '';
    ?>
    <div class="overlay-menu" id="overlay-menu" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Menú principal">
        <div class="overlay-menu-image" style="background-image:url('<?=$h($menuImage)?>');" aria-hidden="true"></div>
        <div class="overlay-menu-panel">
            <button class="overlay-menu-close" id="overlay-menu-close" aria-label="Cerrar menú">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="overlay-menu-inner">
                <span class="overlay-menu-eyebrow">Menú</span>
                <nav class="overlay-menu-list" aria-label="Navegación principal">
                    <a href="#nosotros">Estudio</a>
                    <a href="#proyectos">Proyectos</a>
                    <a href="#servicios">Servicios</a>
                    <a href="#contacto">Contacto</a>
                </nav>
                <div class="overlay-menu-social">
                    <?php if ($instagramUrl): ?>
                    <a href="<?=$h($instagramUrl)?>" target="_blank" rel="noopener" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($linkedinUrl): ?>
                    <a href="<?=$h($linkedinUrl)?>" target="_blank" rel="noopener" aria-label="LinkedIn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if (!$instagramUrl && !$linkedinUrl): ?>
                    <a href="#" aria-label="Instagram" onclick="return false;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                    </a>
                    <a href="#" aria-label="LinkedIn" onclick="return false;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- HERO SECTION — Video background loop -->
    <!-- ============================================ -->
    <?php
    // Detectar videos en /uploads/videos (mp4/webm). Fallback: imagen hero-bg.
    $videosDir = __DIR__ . '/uploads/videos';
    $heroVideos = [];
    if (is_dir($videosDir)) {
        foreach (scandir($videosDir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['mp4','webm','mov','m4v'], true)) {
                $heroVideos[] = '/uploads/videos/' . rawurlencode($f);
            }
        }
        sort($heroVideos);
    }
    ?>
    <section class="hero hero--video" id="inicio">
        <div class="hero-bg">
            <?php if (!empty($heroVideos)): ?>
                <div class="hero-videos" id="hero-videos">
                    <?php foreach ($heroVideos as $i => $src): ?>
                        <video class="hero-video<?= $i === 0 ? ' is-active' : '' ?>"
                               src="<?=$h($src)?>"
                               muted
                               playsinline
                               <?= count($heroVideos) === 1 ? 'loop' : '' ?>
                               <?= $i === 0 ? 'autoplay' : '' ?>
                               preload="<?= $i === 0 ? 'auto' : 'metadata' ?>"></video>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <img src="/assets/img/hero-bg.png" alt="" loading="eager">
            <?php endif; ?>
        </div>
    </section>

    <!-- ============================================ -->
    <!-- WELCOME / VALUE PROPOSITION -->
    <!-- ============================================ -->
    <section class="section welcome-section" id="nosotros">
        <div class="container">
            <div class="welcome-content" data-animate="fadeInUp">
                <span class="welcome-badge"><?=strtoupper($h($S['siteName']))?></span>
                <h2>
                    Nos enfocamos en el diseño de casas en el sur de Chile, donde cada proyecto nace del respeto por el entorno natural y la visión de quienes lo habitarán.
                </h2>
                <a href="#contacto" class="btn btn-dark btn-lg">Conversemos sobre tu Proyecto</a>
            </div>

            <!-- Servicios -->
            <div class="trust-grid" id="servicios" data-animate="fadeInUp" data-delay="200">
                <div class="trust-card">
                    <div class="trust-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <h4>Diseño</h4>
                    <ul class="trust-list">
                        <li>Anteproyecto</li>
                        <li>Proyecto</li>
                    </ul>
                </div>
                <div class="trust-card">
                    <div class="trust-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                    <h4>Gestión Integral</h4>
                    <ul class="trust-list">
                        <li>Tramitaciones municipales</li>
                        <li>Permisos de Edificación</li>
                        <li>Recepción final de Obras</li>
                    </ul>
                </div>
                <div class="trust-card">
                    <div class="trust-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    </div>
                    <h4>Visualización 3D</h4>
                    <ul class="trust-list">
                        <li>Modelos 3D</li>
                        <li>Renders fotorrealistas</li>
                    </ul>
                </div>
                <div class="trust-card">
                    <div class="trust-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <h4>Compromiso Total</h4>
                    <p>Acompañamiento desde la primera reunión hasta el término de la obra.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================ -->
    <!-- FEATURED PROJECTS -->
    <!-- ============================================ -->
    <section class="section projects-section" id="proyectos">
        <div class="container">
            <div class="projects-header" data-animate="fadeInUp">
                <span class="section-label">PROYECTOS DESTACADOS</span>
                <h2>Arquitectura para contemplar</h2>
            </div>
            <div class="projects-grid">
                <?php foreach ($featuredProjects as $i => $p): ?>
                <?php
                    $statusChip = '';
                    if (!empty($p['tags'])) {
                        $parts = explode(',', $p['tags']);
                        $statusChip = trim($parts[0]);
                    }
                    $surface = !empty($p['area_m2']) ? number_format((float)$p['area_m2'], 0, ',', '.') . ' m²' : '';
                    $img = !empty($p['featured_image']) ? $p['featured_image'] : '/assets/img/proyecto-' . $p['slug'] . '.png';
                ?>
                <a class="project-card" href="/proyecto/<?=$h($p['slug'])?>" data-animate="fadeInUp" data-delay="<?=$i * 80?>">
                    <div class="project-card-thumb">
                        <img src="<?=$h($img)?>" alt="<?=$h($p['title'])?>" loading="lazy">
                    </div>
                    <div class="project-card-body">
                        <?php if ($statusChip): ?><span class="project-card-status"><?=$h($statusChip)?></span><?php endif; ?>
                        <h3><?=$h($p['title'])?></h3>
                        <div class="project-card-meta">
                            <?php if ($surface): ?><span><?=$h($surface)?></span><span class="project-card-dot">·</span><?php endif; ?>
                            <span><?=$h($p['location'])?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ============================================ -->
    <!-- CONTACT / CTA FINAL -->
    <!-- ============================================ -->
    <section class="section contact-section" id="contacto">
        <div class="container">
            <div class="contact-grid">
                <div class="contact-info" data-animate="slideInLeft">
                    <h2>Hablemos de tu <span class="text-accent">próximo proyecto</span></h2>
                    <p>Contáctanos y conversemos sobre la casa que siempre has soñado. Estamos en Pucón, Chile.</p>

                    <div class="contact-detail">
                        <div class="contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
                        <div>
                            <strong>Email</strong>
                            <span><?=$h($S['email'])?></span>
                        </div>
                    </div>

                    <div class="contact-detail">
                        <div class="contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                        <div>
                            <strong>Teléfono</strong>
                            <span><a href="tel:<?=$phoneClean?>" style="color:inherit;text-decoration:none;"><?=$h($S['phone'])?></a></span>
                        </div>
                    </div>

                    <div class="contact-detail">
                        <div class="contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                        <div>
                            <strong>Ubicación</strong>
                            <span><?=$h($S['address'])?></span>
                        </div>
                    </div>
                </div>

                <div class="contact-form-wrap" data-animate="slideInRight">
                    <h3>Envíanos un mensaje</h3>
                    <form id="contact-form">
                        <!-- Honeypot anti-spam (hidden field) -->
                        <div style="position:absolute;left:-9999px;" aria-hidden="true">
                            <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                        </div>
                        <!-- Anti-spam: form load time -->
                        <input type="hidden" name="_form_time" value="">
                        <!-- Source tracking -->
                        <input type="hidden" name="source" value="website-contacto">

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="name">Nombre <span class="required">*</span></label>
                                <input type="text" id="name" name="name" class="form-input" placeholder="Tu nombre" required minlength="2">
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="email">Email <span class="required">*</span></label>
                                <input type="email" id="email" name="email" class="form-input" placeholder="tu@email.com" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="phone">Teléfono</label>
                            <input type="tel" id="phone" name="phone" class="form-input" placeholder="<?=$h($S['phone'])?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="message">Mensaje</label>
                            <textarea id="message" name="message" class="form-textarea" placeholder="Cuéntanos sobre tu proyecto..." rows="4"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            Enviar Mensaje →
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================ -->
    <!-- FOOTER -->
    <!-- ============================================ -->
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
                    <a href="#inicio">Inicio</a>
                    <a href="#nosotros">Nosotros</a>
                    <a href="#servicios">Servicios</a>
                    <a href="#contacto">Contacto</a>
                </div>

                <div class="footer-col">
                    <h4>Servicios</h4>
                    <a href="#servicios">Diseño de Proyectos</a>
                    <a href="#servicios">Obra Nueva</a>
                    <a href="#servicios">Ampliaciones</a>
                    <a href="#contacto">Tramitaciones</a>
                </div>

                <div class="footer-col">
                    <h4>Contacto</h4>
                    <a href="tel:<?=$phoneClean?>"><?=$h($S['phone'])?></a>
                    <a href="mailto:<?=$h($S['email'])?>"><?=$h($S['email'])?></a>
                    <span style="color:rgba(255,255,255,0.5);font-size:0.85rem;"><?=$h($S['address'])?></span>
                </div>
            </div>

            <div class="footer-bottom">
                <span>&copy; <?=date('Y')?> <?=$h($S['siteName'])?>. Todos los derechos reservados.</span>
                <div class="footer-social">
                    <?php foreach ($socials as $social): ?>
                    <a href="<?=$h($social['url'])?>" target="_blank" rel="noopener" aria-label="<?=$social['label']?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><?=$social['icon']?></svg></a>
                    <?php endforeach; ?>
                    <?php if (empty($socials)): ?>
                    <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor" style="width:18px;height:18px;"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                    <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>

    <!-- WhatsApp Floating Button -->
    <a id="whatsapp-btn" class="whatsapp-float" href="#" aria-label="Escríbenos por WhatsApp" style="display:none;">
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:28px;height:28px;">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>

    <!-- Scripts -->
    <script src="/assets/js/app.js?v=<?=$v?>"></script>
</body>
</html>
