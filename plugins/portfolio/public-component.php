<?php
/**
 * ============================================
 * PLUGIN: Portfolio — Homepage Component
 * ============================================
 * Embeddable section that shows featured projects on the main page.
 * Include in index.php:
 *   <?php include __DIR__ . '/plugins/portfolio/public-component.php'; ?>
 */

// Check if plugin is active
$portfolioActive = false;
try {
    if (isset($pdo)) {
        $chk = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'active_plugins'");
        $chk->execute();
        $row = $chk->fetch();
        if ($row) {
            $active = json_decode($row['setting_value'], true);
            $portfolioActive = is_array($active) && in_array('portfolio', $active);
        }
    }
} catch (Exception $e) {}

if (!$portfolioActive) return; // Don't render if plugin is not active

// Load featured projects
$portfolioProjects = [];
try {
    if (isset($pdo)) {
        $pStmt = $pdo->prepare("
            SELECT id, title, slug, category, featured_image, location, year
            FROM portfolio_projects 
            WHERE status = 'published' 
            ORDER BY sort_order ASC, created_at DESC 
            LIMIT 6
        ");
        $pStmt->execute();
        $portfolioProjects = $pStmt->fetchAll();
    }
} catch (Exception $e) {}

if (empty($portfolioProjects)) return; // Don't render if no projects

$catLabels = [
    'residencial' => 'Residencial', 'comercial' => 'Comercial', 'institucional' => 'Institucional',
    'interiorismo' => 'Interiorismo', 'paisajismo' => 'Paisajismo', 'restauracion' => 'Restauración',
    'industrial' => 'Industrial', 'otro' => 'Otro'
];
?>

<!-- ============================================ -->
<!-- PORTFOLIO SECTION (Plugin Component) -->
<!-- ============================================ -->
<link rel="stylesheet" href="/plugins/portfolio/public.css?v=<?=$v?>">
<section class="section portfolio-home-section" id="portafolio" data-animate="fadeInUp">
    <div class="container">
        <div class="portfolio-home-header">
            <span class="welcome-badge">PORTAFOLIO</span>
            <h2>Proyectos Destacados</h2>
            <p>Conoce algunos de nuestros trabajos más recientes y representativos.</p>
        </div>

        <div class="portfolio-home-grid">
            <?php foreach ($portfolioProjects as $i => $pp): ?>
            <a href="/portafolio/proyecto?slug=<?=urlencode($pp['slug'])?>" 
               class="portfolio-home-item" 
               data-animate="fadeInUp" 
               data-delay="<?=($i * 100)?>">
                <div class="portfolio-home-thumb">
                    <?php if ($pp['featured_image']): ?>
                    <img src="<?=$h($pp['featured_image'])?>" alt="<?=$h($pp['title'])?>" loading="lazy">
                    <?php else: ?>
                    <div class="portfolio-home-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="width:40px;height:40px;color:rgba(255,255,255,0.2);">
                            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                    <div class="portfolio-home-overlay">
                        <span class="portfolio-home-cat"><?=$catLabels[$pp['category']] ?? $pp['category']?></span>
                        <h3><?=$h($pp['title'])?></h3>
                        <?php if ($pp['location']): ?>
                        <span class="portfolio-home-loc">📍 <?=$h($pp['location'])?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center;margin-top:var(--space-8);">
            <a href="/portafolio" class="btn btn-outline-light btn-lg" style="color:var(--text-primary);border-color:var(--border-color);">
                Ver todos los proyectos →
            </a>
        </div>
    </div>
</section>
