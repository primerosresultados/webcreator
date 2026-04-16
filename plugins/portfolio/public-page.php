<?php
/**
 * ============================================
 * PLUGIN: Portfolio — Public Page (/portafolio)
 * ============================================
 * Displays all published projects in a responsive masonry grid.
 */
$v = time();

// Load site info (same pattern as index.php)
$S = [
    'siteName' => 'MiSitio', 'siteDescription' => 'Soluciones profesionales para tu negocio',
    'phone' => '+56 9 1234 5678', 'email' => 'contacto@tusitio.com',
    'whatsapp' => '', 'address' => 'Santiago, Chile',
    'instagram' => '', 'facebook' => '', 'youtube' => '', 'linkedin' => '',
    'twitter' => '', 'pinterest' => '', 'tiktok' => '',
];
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
    }
} catch (Exception $e) {}

$phoneClean = preg_replace('/[^0-9+]/', '', $S['phone']);
$h = function($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };
$logoNormal   = !empty($S['logo_normal'])   ? $S['logo_normal']   : '';
$logoNegative = !empty($S['logo_negative']) ? $S['logo_negative'] : '';
$svgLogo = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;"><path d="M12 2 L2 7 L12 12 L22 7 Z"/><path d="M2 17 L12 22 L22 17"/><path d="M2 12 L12 17 L22 12"/></svg>';

// Social links
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
    <meta name="description" content="Portafolio de proyectos — <?=$h($S['siteName'])?>">
    <title>Portafolio — <?=$h($S['siteName'])?></title>

    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='18' fill='%231a1d2e'/><text x='50' y='68' text-anchor='middle' fill='%23c9a96e' font-size='52' font-weight='bold'><?=substr($S['siteName'],0,1)?></text></svg>">

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

    <!-- HERO BANNER -->
    <section class="portfolio-hero">
        <div class="container">
            <span class="portfolio-hero-badge">PORTAFOLIO</span>
            <h1>Nuestros Proyectos</h1>
            <p>Cada proyecto refleja nuestra pasión por el diseño, la innovación y la excelencia constructiva.</p>
        </div>
    </section>

    <!-- FILTERS -->
    <section class="portfolio-filters-section">
        <div class="container">
            <div class="portfolio-filter-bar" id="portfolio-filters">
                <button class="portfolio-filter-btn active" data-cat="">Todos</button>
                <button class="portfolio-filter-btn" data-cat="residencial">Residencial</button>
                <button class="portfolio-filter-btn" data-cat="comercial">Comercial</button>
                <button class="portfolio-filter-btn" data-cat="institucional">Institucional</button>
                <button class="portfolio-filter-btn" data-cat="interiorismo">Interiorismo</button>
                <button class="portfolio-filter-btn" data-cat="paisajismo">Paisajismo</button>
                <button class="portfolio-filter-btn" data-cat="restauracion">Restauración</button>
                <button class="portfolio-filter-btn" data-cat="industrial">Industrial</button>
            </div>
        </div>
    </section>

    <!-- PROJECTS GRID -->
    <section class="section portfolio-section">
        <div class="container">
            <div class="portfolio-grid" id="portfolio-grid">
                <div style="text-align:center;padding:4rem;grid-column:1/-1;">
                    <div class="spinner" style="margin:0 auto;"></div>
                </div>
            </div>

            <div id="portfolio-empty" style="display:none;text-align:center;padding:4rem 2rem;">
                <div style="font-size:3rem;margin-bottom:1rem;">🏗️</div>
                <h3 style="color:var(--text-primary);margin-bottom:0.5rem;">Próximamente</h3>
                <p style="color:var(--text-secondary);">Estamos preparando nuestro portafolio de proyectos.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="portfolio-cta">
        <div class="container" style="text-align:center;">
            <h2>¿Tienes un proyecto en mente?</h2>
            <p>Conversemos sobre cómo podemos hacer realidad tu visión arquitectónica.</p>
            <a href="/#contacto" class="btn btn-accent btn-lg">Solicitar Presupuesto →</a>
        </div>
    </section>

    <!-- FOOTER (same as index.php) -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="/" class="nav-brand">
                        <?php if ($logoNegative): ?>
                            <img src="<?=$h($logoNegative)?>" alt="<?=$h($S['siteName'])?>" style="height:36px;width:auto;">
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
                    <a href="/portafolio">Portafolio</a>
                    <a href="/#contacto">Contacto</a>
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
                </div>
            </div>
        </div>
    </footer>

    <!-- WhatsApp Button -->
    <?php if (!empty($S['whatsapp'])): ?>
    <a class="whatsapp-float" href="https://wa.me/<?=$h($S['whatsapp'])?>" target="_blank" aria-label="WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor" style="width:28px;height:28px;">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>
    <?php endif; ?>

    <script src="/assets/js/app.js?v=<?=$v?>"></script>
    <script>
    // Portfolio public page logic
    (function() {
        const categoryLabels = {
            residencial: 'Residencial', comercial: 'Comercial', institucional: 'Institucional',
            interiorismo: 'Interiorismo', paisajismo: 'Paisajismo', restauracion: 'Restauración',
            industrial: 'Industrial', otro: 'Otro'
        };

        let allProjects = [];

        async function loadProjects(category = '') {
            try {
                let url = '/api/plugins.php?action=api&plugin=portfolio&p_action=public_list';
                if (category) url += '&category=' + encodeURIComponent(category);
                
                const res = await fetch(url);
                const data = await res.json();
                
                if (data.success && data.projects) {
                    allProjects = data.projects;
                    renderGrid(data.projects);
                } else {
                    document.getElementById('portfolio-grid').innerHTML = '';
                    document.getElementById('portfolio-empty').style.display = 'block';
                }
            } catch (err) {
                console.error('Error loading portfolio:', err);
                document.getElementById('portfolio-grid').innerHTML = '';
                document.getElementById('portfolio-empty').style.display = 'block';
            }
        }

        function renderGrid(projects) {
            const grid = document.getElementById('portfolio-grid');
            const empty = document.getElementById('portfolio-empty');

            if (projects.length === 0) {
                grid.innerHTML = '';
                empty.style.display = 'block';
                return;
            }

            empty.style.display = 'none';
            grid.innerHTML = projects.map((p, i) => `
                <a href="/portafolio/proyecto?slug=${encodeURIComponent(p.slug)}" 
                   class="portfolio-item" 
                   data-category="${p.category}"
                   style="animation-delay: ${i * 60}ms;">
                    <div class="portfolio-item-image">
                        ${p.featured_image 
                            ? `<img src="${escapeHtml(p.featured_image)}" alt="${escapeHtml(p.title)}" loading="lazy">`
                            : `<div class="portfolio-item-placeholder">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width:48px;height:48px;color:rgba(255,255,255,0.3);">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                                </svg>
                               </div>`
                        }
                        <div class="portfolio-item-overlay">
                            <span class="portfolio-item-cat">${categoryLabels[p.category] || p.category}</span>
                            <h3>${escapeHtml(p.title)}</h3>
                            <div class="portfolio-item-meta">
                                ${p.location ? `<span>📍 ${escapeHtml(p.location)}</span>` : ''}
                                ${p.year ? `<span>${p.year}</span>` : ''}
                            </div>
                            <span class="portfolio-item-cta">Ver Proyecto →</span>
                        </div>
                    </div>
                </a>
            `).join('');
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        // Filter buttons
        document.getElementById('portfolio-filters').addEventListener('click', (e) => {
            const btn = e.target.closest('.portfolio-filter-btn');
            if (!btn) return;

            document.querySelectorAll('.portfolio-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadProjects(btn.dataset.cat);
        });

        // Initial load
        loadProjects();
    })();
    </script>
</body>
</html>
