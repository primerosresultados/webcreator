/**
 * ============================================
 * PLUGIN: Portfolio — Admin JavaScript
 * ============================================
 * Handles CRUD for portfolio projects and image management.
 */

const PortfolioAdmin = {
    projects: [],
    currentFilters: { search: '', category: '', page: 1 },
    editingId: null,

    // ============================================
    // API Helper (routes through plugin system)
    // ============================================
    async api(pAction, options = {}) {
        const url = `/api/plugins.php?action=api&plugin=portfolio&p_action=${pAction}`;
        return await api(url, options);
    },

    // ============================================
    // LOAD PROJECTS
    // ============================================
    async loadProjects() {
        const { search, category, page } = this.currentFilters;
        let params = `&page=${page}&limit=20`;
        if (search) params += `&search=${encodeURIComponent(search)}`;
        if (category) params += `&category=${encodeURIComponent(category)}`;

        const result = await this.api('list' + params);
        if (!result || !result.success) return;

        this.projects = result.projects;
        this.renderGrid();
        this.renderPagination(result.pagination);
    },

    // ============================================
    // RENDER PROJECTS GRID
    // ============================================
    renderGrid() {
        const container = document.getElementById('portfolio-projects-grid');
        if (!container) return;

        if (this.projects.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="padding:4rem 2rem;text-align:center;">
                    <div style="color:var(--text-tertiary);margin-bottom:var(--space-4);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px;margin:0 auto;display:block;"><polygon points="1 22 4 22 4 2 20 2 20 22 23 22"></polygon><polyline points="14 2 14 12 20 12"></polyline><line x1="10" y1="10" x2="10" y2="10"></line><line x1="10" y1="6" x2="10" y2="6"></line><line x1="10" y1="14" x2="10" y2="14"></line><line x1="10" y1="18" x2="10" y2="18"></line></svg>
                    </div>
                    <h3 style="color:#1a1d2e;margin-bottom:var(--space-2);">Sin proyectos aún</h3>
                    <p style="color:#8b90a6;font-size:var(--text-sm);margin-bottom:var(--space-4);">Crea tu primer proyecto del portafolio.</p>
                    <button class="btn btn-primary btn-sm" onclick="PortfolioAdmin.openEditor()">
                        + Nuevo Proyecto
                    </button>
                </div>
            `;
            return;
        }

        const categoryLabels = {
            residencial: 'Residencial', comercial: 'Comercial', institucional: 'Institucional',
            interiorismo: 'Interiorismo', paisajismo: 'Paisajismo', restauracion: 'Restauración',
            industrial: 'Industrial', otro: 'Otro'
        };

        container.innerHTML = this.projects.map(p => `
            <div class="portfolio-admin-card" data-id="${p.id}">
                <div class="portfolio-admin-thumb" onclick="PortfolioAdmin.openEditor(${p.id})">
                    ${p.featured_image 
                        ? `<img src="${escapeHtml(p.featured_image)}" alt="${escapeHtml(p.title)}" loading="lazy">`
                        : `<div class="portfolio-no-image">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:32px;height:32px;color:#ccc;">
                                <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
                            </svg>
                           </div>`
                    }
                    <span class="portfolio-admin-status badge badge-${p.status === 'published' ? 'converted' : 'new'}">
                        ${p.status === 'published' ? 'Publicado' : 'Borrador'}
                    </span>
                </div>
                <div class="portfolio-admin-info" onclick="PortfolioAdmin.openEditor(${p.id})">
                    <h4>${escapeHtml(p.title)}</h4>
                    <div class="portfolio-admin-meta">
                        <span class="badge" style="background:rgba(99,102,241,0.08);color:#6366f1;font-size:10px;padding:2px 8px;border-radius:6px;">
                            ${categoryLabels[p.category] || p.category}
                        </span>
                        ${p.location ? `<span style="color:#8b90a6;font-size:11px;">📍 ${escapeHtml(p.location)}</span>` : ''}
                        ${p.year ? `<span style="color:#8b90a6;font-size:11px;">${p.year}</span>` : ''}
                    </div>
                    <span style="color:#aaa;font-size:11px;">${p.image_count || 0} imágenes</span>
                </div>
                <div class="portfolio-admin-actions">
                    <button class="btn btn-ghost btn-icon" onclick="PortfolioAdmin.openEditor(${p.id})" title="Editar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    </button>
                    <button class="btn btn-ghost btn-icon" onclick="PortfolioAdmin.deleteProject(${p.id}, '${escapeHtml(p.title)}')" title="Eliminar" style="color:#dc2626;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </div>
            </div>
        `).join('');
    },

    // ============================================
    // RENDER PAGINATION
    // ============================================
    renderPagination(pagination) {
        const container = document.getElementById('portfolio-pagination');
        if (!container || !pagination) return;

        const { total, page, pages } = pagination;
        if (pages <= 1) { container.innerHTML = ''; return; }

        let html = `<button ${page <= 1 ? 'disabled' : ''} onclick="PortfolioAdmin.goToPage(${page - 1})">←</button>`;
        for (let i = 1; i <= pages; i++) {
            if (i === 1 || i === pages || Math.abs(i - page) <= 1) {
                html += `<button class="${i === page ? 'active' : ''}" onclick="PortfolioAdmin.goToPage(${i})">${i}</button>`;
            } else if (Math.abs(i - page) === 2) {
                html += `<button disabled>…</button>`;
            }
        }
        html += `<button ${page >= pages ? 'disabled' : ''} onclick="PortfolioAdmin.goToPage(${page + 1})">→</button>`;
        container.innerHTML = html;
    },

    goToPage(page) {
        this.currentFilters.page = page;
        this.loadProjects();
    },

    // ============================================
    // FILTER BY CATEGORY
    // ============================================
    filterCategory(btn, category) {
        document.querySelectorAll('#view-plugin-portfolio .filter-btn[data-category]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        this.currentFilters.category = category;
        this.currentFilters.page = 1;
        this.loadProjects();
    },

    // ============================================
    // SEARCH
    // ============================================
    initSearch() {
        const input = document.getElementById('portfolio-search');
        if (!input) return;
        let timeout;
        input.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                this.currentFilters.search = e.target.value;
                this.currentFilters.page = 1;
                this.loadProjects();
            }, 400);
        });
    },

    // ============================================
    // EDITOR: Open
    // ============================================
    async openEditor(projectId = null) {
        this.editingId = projectId;
        const title = document.getElementById('portfolio-editor-title');
        const form = document.getElementById('portfolio-editor-form');

        // Reset form
        form.reset();
        document.getElementById('pf-id').value = '';

        if (projectId) {
            title.textContent = 'Editar Proyecto';
            // Load project data
            const result = await this.api(`get&id=${projectId}`);
            if (!result || !result.success) return;

            const p = result.project;
            document.getElementById('pf-id').value = p.id;
            document.getElementById('pf-title').value = p.title || '';
            document.getElementById('pf-category').value = p.category || 'residencial';
            document.getElementById('pf-client').value = p.client_name || '';
            document.getElementById('pf-location').value = p.location || '';
            document.getElementById('pf-year').value = p.year || '';
            document.getElementById('pf-area').value = p.area_m2 || '';
            document.getElementById('pf-status').value = p.status || 'draft';
            document.getElementById('pf-description').value = p.description || '';
            document.getElementById('pf-tags').value = p.tags || '';

            // Show gallery
            this.showGallery(p.id, p.images || [], p.featured_image);
        } else {
            title.textContent = 'Nuevo Proyecto';
            // Show notice about saving first
            document.getElementById('pf-gallery-notice').style.display = 'block';
            document.getElementById('pf-gallery-grid').style.display = 'none';
            document.getElementById('pf-drop-zone').style.display = 'none';
            document.getElementById('pf-upload-btn').style.display = 'none';
        }

        // Open modal
        document.getElementById('portfolio-modal-backdrop').classList.add('active');
        document.getElementById('portfolio-editor-modal').classList.add('active');
    },

    // ============================================
    // EDITOR: Close
    // ============================================
    closeEditor() {
        document.getElementById('portfolio-modal-backdrop').classList.remove('active');
        document.getElementById('portfolio-editor-modal').classList.remove('active');
        this.editingId = null;
    },

    // ============================================
    // SAVE PROJECT (create or update)
    // ============================================
    async saveProject(e) {
        if (e) e.preventDefault();

        const id = document.getElementById('pf-id').value;
        const data = {
            title: document.getElementById('pf-title').value.trim(),
            category: document.getElementById('pf-category').value,
            client_name: document.getElementById('pf-client').value.trim(),
            location: document.getElementById('pf-location').value.trim(),
            year: document.getElementById('pf-year').value,
            area_m2: document.getElementById('pf-area').value,
            status: document.getElementById('pf-status').value,
            description: document.getElementById('pf-description').value.trim(),
            tags: document.getElementById('pf-tags').value.trim()
        };

        if (!data.title) {
            Toast.error('El título es obligatorio.');
            return;
        }

        const btn = document.getElementById('pf-save-btn');
        btn.classList.add('btn-loading');
        btn.disabled = true;

        let result;
        if (id) {
            result = await this.api(`update&id=${id}`, {
                method: 'POST',
                body: JSON.stringify({ _method: 'PUT', ...data })
            });
        } else {
            result = await this.api('create', {
                method: 'POST',
                body: JSON.stringify(data)
            });
        }

        btn.classList.remove('btn-loading');
        btn.disabled = false;

        if (result && result.success) {
            Toast.success(id ? 'Proyecto actualizado.' : 'Proyecto creado.');
            
            // If creating, update the ID and show gallery
            if (!id && result.id) {
                document.getElementById('pf-id').value = result.id;
                this.editingId = result.id;
                this.showGallery(result.id, []);
                document.getElementById('portfolio-editor-title').textContent = 'Editar Proyecto';
            }

            this.loadProjects(); // Refresh list in background
        } else {
            Toast.error(result?.error || 'Error al guardar.');
        }
    },

    // ============================================
    // DELETE PROJECT
    // ============================================
    async deleteProject(id, title) {
        if (!confirm(`¿Eliminar el proyecto "${title}"?\nEsta acción eliminará también todas sus imágenes.`)) return;

        const result = await this.api(`delete&id=${id}`, {
            method: 'POST',
            body: JSON.stringify({ _method: 'DELETE' })
        });

        if (result && result.success) {
            Toast.success('Proyecto eliminado.');
            this.loadProjects();
        } else {
            Toast.error(result?.error || 'Error al eliminar.');
        }
    },

    // ============================================
    // GALLERY: Show 
    // ============================================
    showGallery(projectId, images = [], featuredImage = '') {
        document.getElementById('pf-gallery-notice').style.display = 'none';
        document.getElementById('pf-upload-btn').style.display = 'inline-flex';
        
        const grid = document.getElementById('pf-gallery-grid');
        const dropZone = document.getElementById('pf-drop-zone');

        if (images.length === 0) {
            grid.style.display = 'none';
            dropZone.style.display = 'flex';
        } else {
            grid.style.display = 'grid';
            dropZone.style.display = 'flex';

            grid.innerHTML = images.map(img => `
                <div class="portfolio-gallery-item ${img.image_url === featuredImage ? 'is-featured' : ''}" data-id="${img.id}">
                    <img src="${escapeHtml(img.image_url)}" alt="${escapeHtml(img.caption || '')}" loading="lazy">
                    <div class="portfolio-gallery-actions">
                        <button type="button" class="btn btn-ghost btn-icon" 
                                onclick="PortfolioAdmin.setFeatured(${projectId}, '${escapeHtml(img.image_url)}')" 
                                title="Establecer como destacada"
                                style="color:${img.image_url === featuredImage ? '#f59e0b' : '#fff'};">
                            <svg viewBox="0 0 24 24" fill="${img.image_url === featuredImage ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        </button>
                        <button type="button" class="btn btn-ghost btn-icon" 
                                onclick="PortfolioAdmin.deleteImage(${img.id}, ${projectId})" 
                                title="Eliminar imagen" style="color:#fff;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </button>
                    </div>
                    ${img.image_url === featuredImage ? '<span class="portfolio-featured-badge">Destacada</span>' : ''}
                </div>
            `).join('');
        }

        // Setup drop zone
        this.initDropZone(projectId);
    },

    // ============================================
    // IMAGE UPLOAD
    // ============================================
    async uploadImages(input) {
        const projectId = document.getElementById('pf-id').value;
        if (!projectId) {
            Toast.warning('Guarda el proyecto primero.');
            return;
        }

        const files = Array.from(input.files);
        if (files.length === 0) return;

        for (const file of files) {
            if (!file.type.startsWith('image/')) {
                Toast.warning(`"${file.name}" no es una imagen.`);
                continue;
            }
            if (file.size > 10 * 1024 * 1024) {
                Toast.warning(`"${file.name}" excede 10MB.`);
                continue;
            }

            const formData = new FormData();
            formData.append('image', file);
            formData.append('project_id', projectId);
            formData.append('csrf_token', AdminApp.csrfToken);

            try {
                const response = await fetch(`/api/plugins.php?action=api&plugin=portfolio&p_action=upload_image&project_id=${projectId}`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (!result.success) {
                    Toast.error(result.error || `Error al subir "${file.name}".`);
                }
            } catch (err) {
                Toast.error(`Error de conexión al subir "${file.name}".`);
            }
        }

        Toast.success(`${files.length} imagen(es) subida(s).`);
        input.value = '';

        // Reload gallery
        const result = await this.api(`get&id=${projectId}`);
        if (result && result.success) {
            this.showGallery(projectId, result.project.images || [], result.project.featured_image);
        }
    },

    // ============================================
    // DELETE IMAGE
    // ============================================
    async deleteImage(imageId, projectId) {
        if (!confirm('¿Eliminar esta imagen?')) return;

        const result = await this.api(`delete_image&id=${imageId}`, {
            method: 'POST',
            body: JSON.stringify({ _method: 'DELETE' })
        });

        if (result && result.success) {
            Toast.success('Imagen eliminada.');
            // Reload gallery
            const projResult = await this.api(`get&id=${projectId}`);
            if (projResult && projResult.success) {
                this.showGallery(projectId, projResult.project.images || [], projResult.project.featured_image);
            }
        } else {
            Toast.error(result?.error || 'Error al eliminar imagen.');
        }
    },

    // ============================================
    // SET FEATURED IMAGE
    // ============================================
    async setFeatured(projectId, imageUrl) {
        const result = await this.api('set_featured', {
            method: 'POST',
            body: JSON.stringify({ project_id: projectId, image_url: imageUrl })
        });

        if (result && result.success) {
            Toast.success('Imagen destacada actualizada.');
            const projResult = await this.api(`get&id=${projectId}`);
            if (projResult && projResult.success) {
                this.showGallery(projectId, projResult.project.images || [], projResult.project.featured_image);
            }
        }
    },

    // ============================================
    // DROP ZONE
    // ============================================
    initDropZone(projectId) {
        const dropZone = document.getElementById('pf-drop-zone');
        if (!dropZone) return;

        dropZone.onclick = () => document.getElementById('pf-image-input').click();

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#6366f1';
            dropZone.style.background = 'rgba(99,102,241,0.04)';
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.style.borderColor = '';
            dropZone.style.background = '';
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '';
            dropZone.style.background = '';
            
            if (e.dataTransfer.files.length > 0) {
                const input = document.getElementById('pf-image-input');
                const dt = new DataTransfer();
                Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
                input.files = dt.files;
                this.uploadImages(input);
            }
        });
    },

    // ============================================
    // INIT
    // ============================================
    init() {
        this.initSearch();
        this.loadProjects();

        // Close modal on backdrop click
        const backdrop = document.getElementById('portfolio-modal-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', () => this.closeEditor());
        }
    }
};

// Auto-init when loaded
if (typeof PortfolioAdmin !== 'undefined') {
    // Will be called by PluginManager when the view loads
}
