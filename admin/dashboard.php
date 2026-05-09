<?php
$v = time();
$gitCommit = 'Unknown';
if (function_exists('exec')) {
    $commit = @exec('git rev-parse --short HEAD 2>/dev/null');
    if ($commit) {
        $gitCommit = $commit;
    }
}

// Cloudinary config (público) para inyectar en data-attr
$cloudCfg = @include __DIR__ . '/../config/cloudinary.php';
if (!is_array($cloudCfg)) $cloudCfg = [];
$cloudCfgJson = htmlspecialchars(json_encode($cloudCfg, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');

// Load site name for sidebar brand
$siteName = 'WebCreator';
try {
    $cfgPath = __DIR__ . '/../config/database.php';
    if (file_exists($cfgPath)) {
        require_once $cfgPath;
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_info'");
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $info = json_decode($row['setting_value'], true);
            if (is_array($info) && !empty($info['siteName'])) {
                $siteName = $info['siteName'];
            }
        }
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin — Dashboard</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%236366f1'/><text x='50' y='68' text-anchor='middle' fill='white' font-size='54' font-weight='bold'>W</text></svg>">
    <link rel="stylesheet" href="/assets/css/variables.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/base.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/components.css?v=<?=$v?>">
    <link rel="stylesheet" href="/assets/css/admin.css?v=<?=$v?>">
    <!-- Force light theme for admin -->
    <style>
        :root {
            --bg-primary: #f7f8fc !important;
            --bg-secondary: #ffffff !important;
            --bg-tertiary: #f0f2f8 !important;
            --bg-card: #ffffff !important;
            --bg-input: #f7f8fc !important;
            --bg-hover: #f0f2f8 !important;
            --border-color: #e5e7ef !important;
            --border-light: #f0f2f8 !important;
            --text-primary: #1a1d2e !important;
            --text-secondary: #5a607a !important;
            --text-tertiary: #8b90a6 !important;
            --text-inverse: #ffffff !important;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.04) !important;
            --shadow-md: 0 4px 12px rgba(0,0,0,0.05) !important;
            --shadow-lg: 0 8px 30px rgba(0,0,0,0.07) !important;
            --shadow-xl: 0 16px 50px rgba(0,0,0,0.08) !important;
            --color-primary: #6366f1 !important;
            --color-primary-hover: #4f46e5 !important;
            --color-primary-light: rgba(99,102,241,0.08) !important;
            --color-primary-rgb: 99,102,241 !important;
        }
        /* SVG icon sizing */
        .sidebar-link svg, .sidebar-brand svg, .dropdown-item svg, .section-icon svg, .admin-section-title svg { width: 18px; height: 18px; flex-shrink: 0; }
        .sidebar-brand svg { width: 28px; height: 28px; }
    </style>
</head>
<body>
    <div id="admin-cloudinary-cfg" data-cfg="<?=$cloudCfgJson?>" hidden></div>
    <div class="admin-layout">

        <!-- ============================================ -->
        <!-- SIDEBAR -->
        <!-- ============================================ -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 L2 7 L12 12 L22 7 Z"/><path d="M2 17 L12 22 L22 17"/><path d="M2 12 L12 17 L22 12"/></svg>
                <div>
                    <h2 id="sidebar-site-name"><?=htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8')?></h2>
                    <small>Panel Admin</small>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Principal</div>
                    <a class="sidebar-link active" data-view="dashboard" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                        Dashboard
                    </a>
                    <a class="sidebar-link" data-view="leads" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Leads / CRM
                        <span class="badge-count" id="leads-count">0</span>
                    </a>
                    <!-- Dynamic principal plugin links injected here -->
                    <div id="plugin-sidebar-principal"></div>
                </div>

                <div class="sidebar-section">
                    <div class="sidebar-section-title">Herramientas</div>
                    <a class="sidebar-link" data-view="settings" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        Configuración
                    </a>
                    <a class="sidebar-link" data-view="users" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Usuarios
                    </a>
                    <a class="sidebar-link" href="#" onclick="exportLeadsCSV(); return false;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Exportar CSV
                    </a>
                </div>

                <div class="sidebar-section">
                    <div class="sidebar-section-title">Plugins</div>
                    <a class="sidebar-link" data-view="plugins" href="#">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        Gestionar Plugins
                    </a>
                    <!-- Dynamic plugin links injected here by JS -->
                    <div id="plugin-sidebar-links"></div>
                </div>

                <div class="sidebar-section">
                    <div class="sidebar-section-title">Sitio</div>
                    <a class="sidebar-link" href="/" target="_blank">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        Ver Sitio Público
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer" style="flex-direction:column;gap:12px;align-items:stretch;">
                <div class="sidebar-user" style="margin-bottom:0;">
                    <div class="avatar">A</div>
                    <div class="info">
                        <span class="name">Admin</span>
                        <span class="role">Super Admin</span>
                    </div>
                    <button onclick="logout()" title="Cerrar Sesión" style="background:none;border:none;cursor:pointer;color:var(--text-tertiary);padding:6px;border-radius:8px;transition:all 0.2s;" onmouseover="this.style.color='#ef4444';this.style.background='rgba(239,68,68,0.08)'" onmouseout="this.style.color='var(--text-tertiary)';this.style.background='none'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </button>
                </div>
                <div style="font-size:10px;text-align:center;color:var(--text-tertiary);font-family:var(--font-mono, monospace);opacity:0.7;">
                    Commit: <?=$gitCommit?>
                </div>
            </div>
        </aside>

        <!-- ============================================ -->
        <!-- MAIN CONTENT -->
        <!-- ============================================ -->
        <main class="admin-main">

            <!-- Header bar -->
            <div class="admin-header">
                <div class="flex items-center gap-4">
                    <button class="btn btn-ghost btn-icon" id="sidebar-toggle" style="display:none;">☰</button>
                    <h1>Dashboard</h1>
                </div>
                <div class="admin-header-actions">
                    <span style="font-size:var(--text-sm);color:var(--text-tertiary);" id="current-date"></span>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- VIEW: DASHBOARD -->
            <!-- ============================================ -->
            <div class="admin-content admin-view" id="view-dashboard">

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="card stat-card">
                        <span class="stat-label">Total Leads</span>
                        <span class="stat-value" id="stat-total">0</span>
                    </div>
                    <div class="card stat-card">
                        <span class="stat-label">Nuevos</span>
                        <span class="stat-value" id="stat-new" style="color:var(--color-info);">0</span>
                    </div>
                    <div class="card stat-card">
                        <span class="stat-label">Contactados</span>
                        <span class="stat-value" id="stat-contacted" style="color:var(--color-warning);">0</span>
                    </div>
                    <div class="card stat-card">
                        <span class="stat-label">Convertidos</span>
                        <span class="stat-value" id="stat-converted" style="color:var(--color-success);">0</span>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-8);">
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">Hoy</span>
                        </div>
                        <span class="stat-value" id="stat-today" style="font-size:var(--text-4xl);">0</span>
                        <span class="stat-label">leads recibidos</span>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">Esta Semana</span>
                        </div>
                        <span class="stat-value" id="stat-week" style="font-size:var(--text-4xl);">0</span>
                        <span class="stat-label">leads recibidos</span>
                    </div>
                </div>

                <!-- Trend Chart -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Tendencia (Últimos 7 días)</span>
                    </div>
                    <div id="trend-chart" style="min-height:180px;display:flex;align-items:center;justify-content:center;">
                        <div class="spinner"></div>
                    </div>
                </div>

            </div>

            <!-- ============================================ -->
            <!-- VIEW: LEADS / CRM -->
            <!-- ============================================ -->
            <div class="admin-content admin-view hidden" id="view-leads">

                <!-- Toolbar -->
                <div class="toolbar">
                    <div class="toolbar-search">
                        <div class="search-wrapper">
                            <input type="text" class="search-input" id="leads-search" placeholder="Buscar leads...">
                        </div>
                    </div>

                    <div class="toolbar-filters">
                        <button class="filter-btn active" data-status="">Todos</button>
                        <button class="filter-btn" data-status="new">Nuevos</button>
                        <button class="filter-btn" data-status="contacted">Contactados</button>
                        <button class="filter-btn" data-status="qualified">Calificados</button>
                        <button class="filter-btn" data-status="converted">Convertidos</button>
                        <button class="filter-btn" data-status="lost">Perdidos</button>
                    </div>
                </div>

                <!-- Leads Table -->
                <div class="card" style="padding:0;overflow:hidden;">
                    <div style="overflow-x:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Estado</th>
                                    <th>Fuente</th>
                                    <th>Fecha</th>
                                    <th style="width:120px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="leads-tbody">
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:3rem;">
                                        <div class="spinner" style="margin:0 auto;"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="pagination" id="leads-pagination"></div>

            </div>

            <!-- ============================================ -->
            <!-- VIEW: CONFIGURACIÓN DEL SITIO -->
            <!-- ============================================ -->
            <div class="admin-content admin-view hidden" id="view-settings">

                <!-- Información General -->
                <div class="admin-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;color:#6366f1;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>Información General</span>
                </div>

                <div class="card" style="margin-bottom:var(--space-6);">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:var(--space-5);">
                        <div class="config-control">
                            <label class="config-label">Nombre del Sitio</label>
                            <input type="text" id="info-siteName" class="form-input" placeholder="Mi Empresa" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                        </div>
                        <div class="config-control">
                            <label class="config-label">Descripción / Slogan</label>
                            <input type="text" id="info-siteDescription" class="form-input" placeholder="Arquitectura & Construcción Premium" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:var(--space-5);margin-top:var(--space-5);">
                        <div class="config-control">
                            <label class="config-label">Teléfono</label>
                            <input type="text" id="info-phone" class="form-input" placeholder="+56 9 1234 5678" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                        </div>
                        <div class="config-control">
                            <label class="config-label">Email de Contacto</label>
                            <input type="text" id="info-email" class="form-input" placeholder="contacto@tusitio.com" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:var(--space-5);margin-top:var(--space-5);">
                        <div class="config-control">
                            <label class="config-label">WhatsApp (con código de país, ej: 56912345678)</label>
                            <input type="text" id="info-whatsapp" class="form-input" placeholder="56912345678" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                        </div>
                        <div class="config-control">
                            <label class="config-label">Dirección</label>
                            <input type="text" id="info-address" class="form-input" placeholder="Santiago, Chile" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                        </div>
                    </div>

                    <div style="border-top:1.5px solid #eef0f6;margin-top:var(--space-6);padding-top:var(--space-5);">
                        <p style="font-size:11px;font-weight:600;color:#8b90a6;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:var(--space-4);">Redes Sociales (URLs completas)</p>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:var(--space-4);">
                            <div class="config-control">
                                <label class="config-label">Instagram</label>
                                <input type="text" id="info-instagram" class="form-input" placeholder="https://instagram.com/tuempresa" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                            </div>
                            <div class="config-control">
                                <label class="config-label">Facebook</label>
                                <input type="text" id="info-facebook" class="form-input" placeholder="https://facebook.com/tuempresa" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                            </div>
                            <div class="config-control">
                                <label class="config-label">YouTube</label>
                                <input type="text" id="info-youtube" class="form-input" placeholder="https://youtube.com/@tuempresa" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                            </div>
                            <div class="config-control">
                                <label class="config-label">LinkedIn</label>
                                <input type="text" id="info-linkedin" class="form-input" placeholder="https://linkedin.com/company/tuempresa" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                            </div>
                            <div class="config-control">
                                <label class="config-label">X / Twitter</label>
                                <input type="text" id="info-twitter" class="form-input" placeholder="https://x.com/tuempresa" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                            </div>
                            <div class="config-control">
                                <label class="config-label">Pinterest</label>
                                <input type="text" id="info-pinterest" class="form-input" placeholder="https://pinterest.com/tuempresa" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                            </div>
                            <div class="config-control">
                                <label class="config-label">TikTok</label>
                                <input type="text" id="info-tiktok" class="form-input" placeholder="https://tiktok.com/@tuempresa" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:var(--space-5);display:flex;gap:var(--space-3);justify-content:flex-end;">
                        <button class="btn btn-primary btn-sm" onclick="saveSiteInfo()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Guardar Información
                        </button>
                    </div>
                </div>

                <!-- Página de Agradecimiento -->
                <div class="admin-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;color:#6366f1;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Página de Agradecimiento</span>
                </div>

                <div class="card" style="margin-bottom:var(--space-6);">
                    <p style="font-size:12px;color:#8b90a6;margin-bottom:var(--space-4);">Personaliza la página que se muestra después de enviar un formulario (/gracias.php)</p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:var(--space-5);">
                        <div class="config-control">
                            <label class="config-label">Título</label>
                            <input type="text" id="ty-title" class="form-input" placeholder="¡Gracias por contactarnos!" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                        </div>
                        <div class="config-control">
                            <label class="config-label">URL Video YouTube (opcional)</label>
                            <input type="text" id="ty-youtubeUrl" class="form-input" placeholder="https://youtube.com/watch?v=..." style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                        </div>
                    </div>

                    <div style="margin-top:var(--space-4);">
                        <div class="config-control">
                            <label class="config-label">Mensaje</label>
                            <textarea id="ty-message" class="form-input" rows="3" placeholder="Hemos recibido tu mensaje y nos pondremos en contacto contigo a la brevedad." style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;resize:vertical;font-family:inherit;"></textarea>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:var(--space-5);margin-top:var(--space-4);">
                        <div class="config-control">
                            <label class="config-label">Texto del Botón</label>
                            <input type="text" id="ty-ctaText" class="form-input" placeholder="Volver al inicio" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                        </div>
                        <div class="config-control">
                            <label class="config-label">URL del Botón</label>
                            <input type="text" id="ty-ctaUrl" class="form-input" placeholder="/" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                        </div>
                    </div>

                    <div style="margin-top:var(--space-4);">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:var(--text-secondary);">
                            <input type="checkbox" id="ty-showSocial" style="width:16px;height:16px;accent-color:#6366f1;">
                            Mostrar enlaces de redes sociales en la página de agradecimiento
                        </label>
                    </div>

                    <div style="margin-top:var(--space-5);display:flex;gap:var(--space-3);justify-content:flex-end;">
                        <button class="btn btn-primary btn-sm" onclick="saveThankYouConfig()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Guardar Página de Agradecimiento
                        </button>
                    </div>
                </div>

                <!-- Logo Upload Section -->
                <div class="admin-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;color:#6366f1;"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span>Logo del Sitio</span>
                </div>

                <div class="card" style="margin-bottom:var(--space-6);">
                    <div class="logo-upload-grid">
                        <!-- Normal Logo -->
                        <div class="logo-upload-card">
                            <h4>Logo Principal</h4>
                            <p class="logo-subtitle">Versión a color para fondos claros</p>
                            <div class="logo-preview-area" id="logo-normal-preview" onclick="document.getElementById('logo-normal-input').click()">
                                <div class="logo-placeholder" id="logo-normal-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:32px;height:32px;color:#aaa;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    <span>Click o arrastra tu logo aquí</span>
                                    <span style="font-size:0.65rem;color:#aaa;">PNG, SVG, JPG — máx 2MB</span>
                                </div>
                            </div>
                            <input type="file" id="logo-normal-input" accept="image/*" style="display:none;" onchange="previewLogo(this, 'normal')">
                            <div class="logo-upload-actions">
                                <button class="btn btn-primary btn-sm" onclick="document.getElementById('logo-normal-input').click()">
                                    Subir Logo
                                </button>
                                <button class="btn btn-danger btn-sm" id="logo-normal-remove" style="display:none;" onclick="removeLogo('normal')">
                                    Eliminar
                                </button>
                            </div>
                        </div>

                        <!-- Negative / Inverted Logo -->
                        <div class="logo-upload-card">
                            <h4>Logo Negativo</h4>
                            <p class="logo-subtitle">Versión clara para fondos oscuros</p>
                            <div class="logo-preview-area dark-bg" id="logo-negative-preview" onclick="document.getElementById('logo-negative-input').click()">
                                <div class="logo-placeholder" id="logo-negative-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:32px;height:32px;color:rgba(255,255,255,0.4);"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    <span>Click o arrastra tu logo aquí</span>
                                    <span style="font-size:0.65rem;color:rgba(255,255,255,0.35);">PNG, SVG, JPG — máx 2MB</span>
                                </div>
                            </div>
                            <input type="file" id="logo-negative-input" accept="image/*" style="display:none;" onchange="previewLogo(this, 'negative')">
                            <div class="logo-upload-actions">
                                <button class="btn btn-primary btn-sm" onclick="document.getElementById('logo-negative-input').click()">
                                    Subir Logo
                                </button>
                                <button class="btn btn-danger btn-sm" id="logo-negative-remove" style="display:none;" onclick="removeLogo('negative')">
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hero Videos -->
                <div class="admin-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;color:#6366f1;"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                    <span>Videos del Hero</span>
                </div>

                <div class="card" style="margin-bottom:var(--space-6);">
                    <p style="font-size:12px;color:#8b90a6;margin-bottom:var(--space-4);">
                        Videos de fondo del Hero (home). Se reproducen en loop, sin sonido, en el orden listado. Se suben directo a Cloudinary.
                        <br><strong>Máx 100MB por archivo</strong> (límite del plan free).
                    </p>

                    <div id="hero-videos-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:var(--space-4);">
                        <!-- Lista renderizada por JS -->
                    </div>

                    <div id="hero-videos-empty" style="text-align:center;padding:1.5rem;color:#8b90a6;font-size:13px;border:1.5px dashed #e8eaf2;border-radius:10px;margin-bottom:var(--space-4);display:none;">
                        Sin videos cargados. Subí uno o más archivos.
                    </div>

                    <input type="file" id="hero-video-input" accept="video/*" multiple style="display:none;" onchange="uploadHeroVideos(this)">

                    <div style="display:flex;gap:var(--space-3);justify-content:space-between;flex-wrap:wrap;">
                        <button class="btn btn-secondary btn-sm" onclick="document.getElementById('hero-video-input').click()" id="hero-upload-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            Subir video(s)
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="saveHeroVideos()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            Guardar Videos del Hero
                        </button>
                    </div>
                </div>

                <!-- Colores Principales -->
                <div class="admin-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;color:#6366f1;"><circle cx="13.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="10.5" r="2.5"/><circle cx="8.5" cy="7.5" r="2.5"/><circle cx="6.5" cy="12.5" r="2.5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.04-.23-.29-.38-.63-.38-1.04 0-.828.672-1.42 1.5-1.42H16c3.31 0 6-2.69 6-6 0-5.52-4.48-9-10-10z"/></svg>
                    <span>Colores del Sitio</span>
                </div>

                <div class="card" style="margin-bottom:var(--space-6);">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--space-6);">
                        <div class="config-control">
                            <label class="config-label">Color Primario</label>
                            <div class="color-picker-wrap">
                                <input type="color" id="cfg-colorPrimary" value="#6366f1" class="color-input">
                                <input type="text" id="cfg-colorPrimary-hex" value="#6366f1" class="color-hex-input" maxlength="7">
                            </div>
                        </div>
                        <div class="config-control">
                            <label class="config-label">Color Primario Hover</label>
                            <div class="color-picker-wrap">
                                <input type="color" id="cfg-colorPrimaryHover" value="#4f46e5" class="color-input">
                                <input type="text" id="cfg-colorPrimaryHover-hex" value="#4f46e5" class="color-hex-input" maxlength="7">
                            </div>
                        </div>
                        <div class="config-control">
                            <label class="config-label">Color Secundario</label>
                            <div class="color-picker-wrap">
                                <input type="color" id="cfg-colorSecondary" value="#c9a96e" class="color-input">
                                <input type="text" id="cfg-colorSecondary-hex" value="#c9a96e" class="color-hex-input" maxlength="7">
                            </div>
                        </div>
                        <div class="config-control">
                            <label class="config-label">Color Acento</label>
                            <div class="color-picker-wrap">
                                <input type="color" id="cfg-colorAccent" value="#06b6d4" class="color-input">
                                <input type="text" id="cfg-colorAccent-hex" value="#06b6d4" class="color-hex-input" maxlength="7">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bordes -->
                <div class="admin-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;color:#6366f1;"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                    <span>Bordes y Botones</span>
                </div>

                <div class="card" style="margin-bottom:var(--space-6);">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:var(--space-6);">
                        <div class="config-control">
                            <label class="config-label">Radio de Bordes Global</label>
                            <div class="range-wrap">
                                <input type="range" id="cfg-borderRadius" min="0" max="24" step="1" value="12" class="range-input">
                                <span class="range-value" id="cfg-borderRadius-val">12px</span>
                            </div>
                        </div>
                        <div class="config-control">
                            <label class="config-label">Radio de Botones</label>
                            <div class="range-wrap">
                                <input type="range" id="cfg-btnRadius" min="0" max="30" step="1" value="8" class="range-input">
                                <span class="range-value" id="cfg-btnRadius-val">8px</span>
                            </div>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--space-6);margin-top:var(--space-5);">
                        <div class="config-control">
                            <label class="config-label">Color de Botón</label>
                            <div class="color-picker-wrap">
                                <input type="color" id="cfg-btnColor" value="#c9a96e" class="color-input">
                                <input type="text" id="cfg-btnColor-hex" class="color-hex-input" value="#c9a96e">
                            </div>
                        </div>
                        <div class="config-control">
                            <label class="config-label">Color de Botón Hover</label>
                            <div class="color-picker-wrap">
                                <input type="color" id="cfg-btnHoverColor" value="#b8944f" class="color-input">
                                <input type="text" id="cfg-btnHoverColor-hex" class="color-hex-input" value="#b8944f">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tipografía -->
                <div class="admin-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;color:#6366f1;"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
                    <span>Tipografía</span>
                </div>

                <div class="card" style="margin-bottom:var(--space-6);">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:var(--space-6);margin-bottom:var(--space-6);">
                        <div class="config-control">
                            <label class="config-label">Fuente de Títulos (H1-H6)</label>
                            <select id="cfg-fontHeadings" class="config-select">
                                <option value="Inter">Inter</option>
                                <option value="Roboto">Roboto</option>
                                <option value="Outfit">Outfit</option>
                                <option value="Poppins">Poppins</option>
                                <option value="Montserrat">Montserrat</option>
                                <option value="Playfair Display">Playfair Display</option>
                                <option value="DM Sans">DM Sans</option>
                                <option value="Space Grotesk">Space Grotesk</option>
                                <option value="Sora">Sora</option>
                                <option value="Raleway">Raleway</option>
                            </select>
                        </div>
                        <div class="config-control">
                            <label class="config-label">Fuente del Menú</label>
                            <select id="cfg-fontMenu" class="config-select">
                                <option value="Inter">Inter</option>
                                <option value="Roboto">Roboto</option>
                                <option value="Outfit">Outfit</option>
                                <option value="Poppins">Poppins</option>
                                <option value="Montserrat">Montserrat</option>
                                <option value="DM Sans">DM Sans</option>
                                <option value="Space Grotesk">Space Grotesk</option>
                            </select>
                        </div>
                        <div class="config-control">
                            <label class="config-label">Fuente del Cuerpo</label>
                            <select id="cfg-fontBody" class="config-select">
                                <option value="Inter">Inter</option>
                                <option value="Roboto">Roboto</option>
                                <option value="Open Sans">Open Sans</option>
                                <option value="Lato">Lato</option>
                                <option value="DM Sans">DM Sans</option>
                                <option value="Nunito">Nunito</option>
                                <option value="Source Sans 3">Source Sans 3</option>
                            </select>
                        </div>
                    </div>

                    <!-- Heading sizes -->
                    <p style="font-size:var(--text-xs);font-weight:var(--font-bold);text-transform:uppercase;letter-spacing:0.06em;color:#8b90a6;margin-bottom:var(--space-4);">Tamaño, peso y color por título</p>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:var(--space-4);">
                        <!-- H1 -->
                        <div class="heading-config-row">
                            <span class="heading-label">H1</span>
                            <input type="text" id="cfg-h1Size" class="config-input-sm" placeholder="3rem" value="3rem">
                            <select id="cfg-h1Weight" class="config-select-sm">
                                <option value="300">Light</option>
                                <option value="400">Regular</option>
                                <option value="600">Semi</option>
                                <option value="700" selected>Bold</option>
                                <option value="800">Extra</option>
                            </select>
                            <input type="color" id="cfg-h1Color" value="#ffffff" class="color-input-sm">
                        </div>
                        <!-- H2 -->
                        <div class="heading-config-row">
                            <span class="heading-label">H2</span>
                            <input type="text" id="cfg-h2Size" class="config-input-sm" placeholder="2rem" value="2rem">
                            <select id="cfg-h2Weight" class="config-select-sm">
                                <option value="300">Light</option>
                                <option value="400">Regular</option>
                                <option value="600">Semi</option>
                                <option value="700" selected>Bold</option>
                                <option value="800">Extra</option>
                            </select>
                            <input type="color" id="cfg-h2Color" value="#ffffff" class="color-input-sm">
                        </div>
                        <!-- H3 -->
                        <div class="heading-config-row">
                            <span class="heading-label">H3</span>
                            <input type="text" id="cfg-h3Size" class="config-input-sm" placeholder="1.5rem" value="1.5rem">
                            <select id="cfg-h3Weight" class="config-select-sm">
                                <option value="300">Light</option>
                                <option value="400">Regular</option>
                                <option value="600" selected>Semi</option>
                                <option value="700">Bold</option>
                                <option value="800">Extra</option>
                            </select>
                            <input type="color" id="cfg-h3Color" value="#ffffff" class="color-input-sm">
                        </div>
                        <!-- H4 -->
                        <div class="heading-config-row">
                            <span class="heading-label">H4</span>
                            <input type="text" id="cfg-h4Size" class="config-input-sm" placeholder="1.25rem" value="1.25rem">
                            <select id="cfg-h4Weight" class="config-select-sm">
                                <option value="300">Light</option>
                                <option value="400">Regular</option>
                                <option value="600" selected>Semi</option>
                                <option value="700">Bold</option>
                                <option value="800">Extra</option>
                            </select>
                            <input type="color" id="cfg-h4Color" value="#ffffff" class="color-input-sm">
                        </div>
                        <!-- H5 -->
                        <div class="heading-config-row">
                            <span class="heading-label">H5</span>
                            <input type="text" id="cfg-h5Size" class="config-input-sm" placeholder="1rem" value="1rem">
                            <select id="cfg-h5Weight" class="config-select-sm">
                                <option value="300">Light</option>
                                <option value="400">Regular</option>
                                <option value="600" selected>Semi</option>
                                <option value="700">Bold</option>
                                <option value="800">Extra</option>
                            </select>
                            <input type="color" id="cfg-h5Color" value="#ffffff" class="color-input-sm">
                        </div>
                        <!-- H6 -->
                        <div class="heading-config-row">
                            <span class="heading-label">H6</span>
                            <input type="text" id="cfg-h6Size" class="config-input-sm" placeholder="0.875rem" value="0.875rem">
                            <select id="cfg-h6Weight" class="config-select-sm">
                                <option value="300">Light</option>
                                <option value="400">Regular</option>
                                <option value="600" selected>Semi</option>
                                <option value="700">Bold</option>
                                <option value="800">Extra</option>
                            </select>
                            <input type="color" id="cfg-h6Color" value="#ffffff" class="color-input-sm">
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div style="display:flex;gap:var(--space-3);justify-content:flex-end;margin-top:var(--space-6);">
                    <button class="btn btn-secondary" onclick="resetThemeConfig()" style="width:auto;padding:0.75rem 2rem;">Restablecer</button>
                    <button class="btn btn-primary" id="save-theme-btn" onclick="saveThemeConfig()" style="width:auto;padding:0.75rem 2rem;">Guardar Configuración</button>
                </div>

            </div>

            <!-- ============================================ -->
            <!-- VIEW: USUARIOS -->
            <!-- ============================================ -->
            <div class="admin-content admin-view hidden" id="view-users">
                <div class="admin-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;color:#6366f1;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Mi cuenta</span>
                </div>

                <div class="card" style="margin-bottom:var(--space-6);">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--space-4);">
                        <div class="config-control">
                            <label class="config-label">Mi email</label>
                            <input type="email" id="me-email" class="form-input" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                        </div>
                        <div class="config-control">
                            <label class="config-label">Mi nombre</label>
                            <input type="text" id="me-name" class="form-input" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                        </div>
                    </div>
                    <div style="margin-top:var(--space-4);display:flex;justify-content:flex-end;">
                        <button class="btn btn-primary btn-sm" onclick="UsersAdmin.saveMe()">Guardar mi info</button>
                    </div>

                    <details style="margin-top:var(--space-5);padding-top:var(--space-4);border-top:1px solid #eef0f6;">
                        <summary style="cursor:pointer;font-size:13px;color:var(--text-secondary);font-weight:600;">Cambiar mi contraseña</summary>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--space-3);margin-top:var(--space-4);">
                            <input type="password" id="me-current-pw" placeholder="Contraseña actual" class="form-input" autocomplete="current-password" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                            <input type="password" id="me-new-pw" placeholder="Nueva contraseña (mín 8)" class="form-input" autocomplete="new-password" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                            <button class="btn btn-primary btn-sm" onclick="UsersAdmin.changeMyPassword()">Cambiar</button>
                        </div>
                    </details>
                </div>

                <!-- Lista de usuarios (solo superadmin) -->
                <div id="users-superadmin-section" style="display:none;">
                    <div class="admin-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;color:#6366f1;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Administradores</span>
                        <button class="btn btn-primary btn-sm" onclick="UsersAdmin.openCreate()" style="margin-left:auto;">+ Nuevo administrador</button>
                    </div>
                    <div class="card" style="padding:0;overflow:hidden;">
                        <div id="users-list-table">
                            <div style="padding:1.5rem;text-align:center;color:#8b90a6;">Cargando…</div>
                        </div>
                    </div>
                </div>

                <!-- Modal: crear/editar usuario -->
                <div class="modal-backdrop" id="users-modal-backdrop"></div>
                <div class="modal" id="users-modal" style="max-width:520px;">
                    <div class="modal-header">
                        <h3 id="users-modal-title">Nuevo administrador</h3>
                        <button class="modal-close" onclick="UsersAdmin.closeModal()">✕</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="usr-id" value="">
                        <div class="config-control">
                            <label class="config-label">Usuario (sin espacios)</label>
                            <input type="text" id="usr-username" class="form-input" placeholder="ej: alvaro" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:8px;padding:8px 12px;font-size:13px;">
                        </div>
                        <div class="config-control" style="margin-top:var(--space-3);">
                            <label class="config-label">Email</label>
                            <input type="email" id="usr-email" class="form-input" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:8px;padding:8px 12px;font-size:13px;">
                        </div>
                        <div class="config-control" style="margin-top:var(--space-3);">
                            <label class="config-label">Nombre completo</label>
                            <input type="text" id="usr-fullname" class="form-input" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:8px;padding:8px 12px;font-size:13px;">
                        </div>
                        <div class="config-control" style="margin-top:var(--space-3);">
                            <label class="config-label">Rol</label>
                            <select id="usr-role" class="config-select">
                                <option value="admin">Admin</option>
                                <option value="superadmin">Super Admin</option>
                                <option value="editor">Editor</option>
                            </select>
                        </div>
                        <div class="config-control" id="usr-password-wrap" style="margin-top:var(--space-3);">
                            <label class="config-label">Contraseña (mín 8 caracteres)</label>
                            <input type="password" id="usr-password" class="form-input" autocomplete="new-password" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:8px;padding:8px 12px;font-size:13px;">
                        </div>
                        <div id="usr-edit-extras" style="display:none;margin-top:var(--space-4);padding-top:var(--space-3);border-top:1px solid #eef0f6;">
                            <p style="font-size:12px;color:#8b90a6;margin-bottom:var(--space-2);">Cambiar contraseña de este usuario</p>
                            <div style="display:flex;gap:8px;">
                                <input type="password" id="usr-newpw" placeholder="Nueva contraseña" autocomplete="new-password" class="form-input" style="flex:1;background:#fff;border:1.5px solid #e8eaf2;border-radius:8px;padding:8px 12px;font-size:13px;">
                                <button class="btn btn-secondary btn-sm" onclick="UsersAdmin.adminResetPassword()">Cambiar</button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="UsersAdmin.closeModal()">Cancelar</button>
                        <button class="btn btn-primary" id="usr-save-btn" onclick="UsersAdmin.save()">Guardar</button>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- VIEW: PLUGINS MANAGER -->
            <!-- ============================================ -->
            <div class="admin-content admin-view hidden" id="view-plugins">

                <!-- Upload Zone -->
                <div class="admin-section-title">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:20px;height:20px;color:#6366f1;"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                    <span>Plugins Instalados</span>
                </div>

                <div class="plugin-upload-zone" id="plugin-upload-zone">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:#aaa;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <span style="font-weight:600;color:var(--text-secondary);">Arrastra un archivo ZIP de plugin aquí</span>
                    <span style="font-size:12px;color:#aaa;">o haz click para seleccionar</span>
                    <input type="file" id="plugin-zip-input" accept=".zip" style="display:none;" onchange="PluginManager.uploadZip(this)">
                </div>

                <!-- Plugins Grid -->
                <div class="plugin-grid" id="plugins-grid">
                    <div style="text-align:center;padding:3rem;grid-column:1/-1;">
                        <div class="spinner" style="margin:0 auto;"></div>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- DYNAMIC PLUGIN VIEWS CONTAINER -->
            <!-- ============================================ -->
            <div id="plugin-views-container">
                <!-- Plugin admin views are injected here dynamically -->
            </div>

        </main>
    </div>

    <!-- ============================================ -->
    <!-- MODAL: Lead Detail -->
    <!-- ============================================ -->
    <div class="modal-backdrop" id="modal-backdrop"></div>
    <div class="modal" id="lead-modal">
        <div class="modal-header">
            <h3 id="lead-modal-title">Detalle del Lead</h3>
            <button class="modal-close" onclick="closeModal('lead-modal')">✕</button>
        </div>
        <div class="modal-body" id="lead-modal-body">
            <!-- Dynamic content -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('lead-modal')">Cerrar</button>
        </div>
    </div>

    <!-- Current date script -->
    <script>
        document.getElementById('current-date').textContent = new Date().toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        // Show mobile toggle on small screens
        if (window.innerWidth <= 1024) {
            document.getElementById('sidebar-toggle').style.display = 'flex';
        }
        window.addEventListener('resize', () => {
            document.getElementById('sidebar-toggle').style.display = window.innerWidth <= 1024 ? 'flex' : 'none';
        });
    </script>
    <script src="/assets/js/admin.v2.js?v=<?=$v?>"></script>
    <!-- Plugin JS files are loaded dynamically by PluginManager -->
</body>
</html>
