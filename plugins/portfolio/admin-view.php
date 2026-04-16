<!-- ============================================
     PLUGIN: Portfolio — Admin View
     ============================================
     Injected dynamically into the dashboard when the plugin is active.
     Uses the same admin CSS classes and design patterns. -->

<!-- Toolbar -->
<div class="toolbar">
    <div class="toolbar-search">
        <div class="search-wrapper">
            <input type="text" class="search-input" id="portfolio-search" placeholder="Buscar proyectos...">
        </div>
    </div>

    <div class="toolbar-filters">
        <button class="filter-btn active" data-category="" onclick="PortfolioAdmin.filterCategory(this, '')">Todos</button>
        <button class="filter-btn" data-category="residencial" onclick="PortfolioAdmin.filterCategory(this, 'residencial')">Residencial</button>
        <button class="filter-btn" data-category="comercial" onclick="PortfolioAdmin.filterCategory(this, 'comercial')">Comercial</button>
        <button class="filter-btn" data-category="institucional" onclick="PortfolioAdmin.filterCategory(this, 'institucional')">Institucional</button>
        <button class="filter-btn" data-category="interiorismo" onclick="PortfolioAdmin.filterCategory(this, 'interiorismo')">Interiorismo</button>
    </div>

    <button class="btn btn-primary btn-sm" onclick="PortfolioAdmin.openEditor()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        Nuevo Proyecto
    </button>
</div>

<!-- Projects Grid -->
<div id="portfolio-projects-grid" class="portfolio-admin-grid">
    <div style="text-align:center;padding:3rem;">
        <div class="spinner" style="margin:0 auto;"></div>
    </div>
</div>

<!-- Pagination -->
<div class="pagination" id="portfolio-pagination"></div>

<!-- ============================================ -->
<!-- MODAL: Project Editor -->
<!-- ============================================ -->
<div class="modal-backdrop" id="portfolio-modal-backdrop"></div>
<div class="modal portfolio-editor-modal" id="portfolio-editor-modal" style="max-width:900px;">
    <div class="modal-header">
        <h3 id="portfolio-editor-title">Nuevo Proyecto</h3>
        <button class="modal-close" onclick="PortfolioAdmin.closeEditor()">✕</button>
    </div>
    <div class="modal-body" style="max-height:75vh;overflow-y:auto;">
        <form id="portfolio-editor-form" onsubmit="PortfolioAdmin.saveProject(event)">
            <input type="hidden" id="pf-id" value="">

            <!-- Row 1: Title + Category -->
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-4);margin-bottom:var(--space-4);">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="config-label">Título del Proyecto *</label>
                    <input type="text" id="pf-title" class="form-input" placeholder="Casa Moderna La Dehesa" required
                           style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="config-label">Categoría</label>
                    <select id="pf-category" class="config-select">
                        <option value="residencial">Residencial</option>
                        <option value="comercial">Comercial</option>
                        <option value="institucional">Institucional</option>
                        <option value="interiorismo">Interiorismo</option>
                        <option value="paisajismo">Paisajismo</option>
                        <option value="restauracion">Restauración</option>
                        <option value="industrial">Industrial</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
            </div>

            <!-- Row 2: Client + Location -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-4);">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="config-label">Cliente</label>
                    <input type="text" id="pf-client" class="form-input" placeholder="Familia Pérez"
                           style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="config-label">Ubicación</label>
                    <input type="text" id="pf-location" class="form-input" placeholder="Santiago, Chile"
                           style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                </div>
            </div>

            <!-- Row 3: Year + Area + Status -->
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--space-4);margin-bottom:var(--space-4);">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="config-label">Año</label>
                    <input type="number" id="pf-year" class="form-input" placeholder="2024" min="1900" max="2100"
                           style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="config-label">Superficie (m²)</label>
                    <input type="number" id="pf-area" class="form-input" placeholder="250" step="0.01" min="0"
                           style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="config-label">Estado</label>
                    <select id="pf-status" class="config-select">
                        <option value="draft">Borrador</option>
                        <option value="published">Publicado</option>
                    </select>
                </div>
            </div>

            <!-- Description -->
            <div class="form-group" style="margin-bottom:var(--space-4);">
                <label class="config-label">Descripción</label>
                <textarea id="pf-description" class="form-input" rows="4" placeholder="Describe el proyecto, materiales, diseño..."
                          style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;resize:vertical;font-family:inherit;"></textarea>
            </div>

            <!-- Tags -->
            <div class="form-group" style="margin-bottom:var(--space-5);">
                <label class="config-label">Tags (separados por coma)</label>
                <input type="text" id="pf-tags" class="form-input" placeholder="moderno, minimalista, hormigón"
                       style="background:#fff;border:1.5px solid #e8eaf2;border-radius:10px;padding:10px 14px;font-size:13px;">
            </div>

            <!-- Image Gallery Section -->
            <div style="border-top:1.5px solid #eef0f6;padding-top:var(--space-5);margin-top:var(--space-2);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-4);">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" style="width:18px;height:18px;">
                            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                        </svg>
                        <span style="font-size:14px;font-weight:700;color:#1a1d2e;">Galería de Imágenes</span>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('pf-image-input').click()" id="pf-upload-btn" style="display:none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        Subir Imagen
                    </button>
                    <input type="file" id="pf-image-input" accept="image/*" multiple style="display:none;" onchange="PortfolioAdmin.uploadImages(this)">
                </div>

                <!-- Upload zone (shown when no project ID yet) -->
                <div id="pf-gallery-notice" style="background:#fef3c7;border:1px solid #fbbf24;border-radius:10px;padding:12px 16px;font-size:12px;color:#92400e;">
                    💡 Guarda el proyecto primero para poder subir imágenes.
                </div>

                <!-- Gallery grid (shown after save) -->
                <div id="pf-gallery-grid" class="portfolio-gallery-grid" style="display:none;">
                    <!-- Images injected here -->
                </div>

                <!-- Drop zone for uploading -->
                <div id="pf-drop-zone" class="portfolio-drop-zone" style="display:none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:36px;height:36px;color:#aaa;">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <span>Arrastra imágenes aquí o haz click en "Subir Imagen"</span>
                    <span style="font-size:11px;color:#aaa;">JPG, PNG, WebP — máx 10MB cada una</span>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer" style="display:flex;gap:var(--space-3);justify-content:flex-end;">
        <button class="btn btn-secondary" onclick="PortfolioAdmin.closeEditor()">Cancelar</button>
        <button class="btn btn-primary" id="pf-save-btn" onclick="PortfolioAdmin.saveProject(event)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
            </svg>
            Guardar Proyecto
        </button>
    </div>
</div>
