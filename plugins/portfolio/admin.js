/**
 * ============================================
 * PLUGIN: Portfolio — Admin JavaScript
 * ============================================
 * Handles CRUD for portfolio projects, image management, and video management.
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
                    ${p.video_url || (p.video_count && parseInt(p.video_count) > 0) ? `
                    <span class="portfolio-admin-video-badge" title="Tiene video">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px;">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                    </span>` : ''}
                </div>
                <div class="portfolio-admin-info" onclick="PortfolioAdmin.openEditor(${p.id})">
                    <h4>${escapeHtml(p.title)}</h4>
                    <div class="portfolio-admin-meta">
                        <span class="badge" style="background:rgba(99,102,241,0.08);color:#6366f1;font-size:10px;padding:2px 8px;border-radius:6px;">
                            ${categoryLabels[p.category] || p.category}
                        </span>
                        ${p.location ? `<span style="color:#8b90a6;font-size:11px;">📍 ${escapeHtml(p.location)}</span>` : ''}
                        ${p.project_date ? `<span style="color:#8b90a6;font-size:11px;">📅 ${this.formatDate(p.project_date)}</span>` : 
                          (p.year ? `<span style="color:#8b90a6;font-size:11px;">${p.year}</span>` : '')}
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;margin-top:2px;">
                        <span style="color:#aaa;font-size:11px;">${p.image_count || 0} fotos</span>
                        ${(p.video_count && parseInt(p.video_count) > 0) ? `<span style="color:#aaa;font-size:11px;">· ${p.video_count} videos</span>` : ''}
                    </div>
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
    // FORMAT DATE HELPER
    // ============================================
    formatDate(dateStr) {
        if (!dateStr) return '';
        try {
            const d = new Date(dateStr + 'T00:00:00');
            return d.toLocaleDateString('es-CL', { day: '2-digit', month: 'short', year: 'numeric' });
        } catch(e) { return dateStr; }
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
        document.getElementById('pf-video-preview').style.display = 'none';
        document.getElementById('pf-video-preview').innerHTML = '';

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
            document.getElementById('pf-date').value = p.project_date || '';
            document.getElementById('pf-year').value = p.year || '';
            document.getElementById('pf-area').value = p.area_m2 || '';
            document.getElementById('pf-status').value = p.status || 'draft';
            document.getElementById('pf-description').value = p.description || '';
            document.getElementById('pf-materials').value = p.materials || '';
            document.getElementById('pf-program').value = p.program || '';
            document.getElementById('pf-tags').value = p.tags || '';
            document.getElementById('pf-video-url').value = p.video_url || '';

            // Show video preview if URL exists
            if (p.video_url) {
                this.showVideoPreview(p.video_url, 'pf-video-preview');
            }

            // Show gallery
            this.showGallery(p.id, p.images || [], p.featured_image);

            // Show videos section
            this.showVideos(p.id, p.videos || []);
        } else {
            title.textContent = 'Nuevo Proyecto';
            // Show notice about saving first
            document.getElementById('pf-gallery-notice').style.display = 'block';
            document.getElementById('pf-gallery-grid').style.display = 'none';
            document.getElementById('pf-drop-zone').style.display = 'none';
            document.getElementById('pf-upload-btn').style.display = 'none';
            document.getElementById('pf-videos-section').style.display = 'none';
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
        const dateVal = document.getElementById('pf-date').value;
        const yearVal = document.getElementById('pf-year').value;

        const data = {
            title: document.getElementById('pf-title').value.trim(),
            category: document.getElementById('pf-category').value,
            client_name: document.getElementById('pf-client').value.trim(),
            location: document.getElementById('pf-location').value.trim(),
            project_date: dateVal || null,
            year: yearVal || (dateVal ? new Date(dateVal).getFullYear() : ''),
            area_m2: document.getElementById('pf-area').value,
            status: document.getElementById('pf-status').value,
            description: document.getElementById('pf-description').value.trim(),
            materials: document.getElementById('pf-materials').value.trim(),
            program: document.getElementById('pf-program').value.trim(),
            tags: document.getElementById('pf-tags').value.trim(),
            video_url: document.getElementById('pf-video-url').value.trim()
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
            
            // If creating, update the ID and show gallery + videos
            if (!id && result.id) {
                document.getElementById('pf-id').value = result.id;
                this.editingId = result.id;
                this.showGallery(result.id, []);
                this.showVideos(result.id, []);
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
        if (!confirm(`¿Eliminar el proyecto "${title}"?\nEsta acción eliminará también todas sus imágenes y videos.`)) return;

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
                                title="${img.image_url === featuredImage ? 'Imagen destacada (portada)' : 'Destacar como portada'}"
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
    async uploadImages(filesOrInput) {
        // Re-entrance guard: bloquea uploads concurrentes (ej: drop disparando varias veces).
        if (this._uploading) return;
        this._uploading = true;
        try {
            await this._uploadImagesInner(filesOrInput);
        } finally {
            this._uploading = false;
        }
    },

    async _uploadImagesInner(filesOrInput) {
        const projectId = document.getElementById('pf-id').value;
        if (!projectId) {
            Toast.warning('Guarda el proyecto primero.');
            return;
        }

        // Leer config desde el data-attribute inyectado por admin-view.php.
        // (No podemos usar window.CLOUDINARY_CFG porque admin-view se carga vía
        // innerHTML y los <script> no se ejecutan en ese caso.)
        let cfg = window.CLOUDINARY_CFG;
        if (!cfg) {
            const node = document.getElementById('portfolio-cloudinary-cfg');
            if (node && node.dataset.cfg) {
                try { cfg = JSON.parse(node.dataset.cfg); window.CLOUDINARY_CFG = cfg; } catch (e) {}
            }
        }
        if (!cfg || !cfg.cloudName || !cfg.uploadPreset) {
            Toast.error('Cloudinary no está configurado. Revisá config/cloudinary.php');
            return;
        }

        // Aceptar tanto un <input type=file> como una FileList/Array de File
        let files, sourceInput = null;
        if (filesOrInput && filesOrInput.tagName === 'INPUT') {
            sourceInput = filesOrInput;
            files = Array.from(filesOrInput.files);
        } else {
            files = Array.from(filesOrInput || []);
        }
        if (files.length === 0) return;
        Toast.info(`Subiendo ${files.length} imagen(es)...`);

        let okCount = 0;
        for (const file of files) {
            if (!file.type.startsWith('image/')) {
                Toast.warning(`"${file.name}" no es una imagen.`);
                continue;
            }
            if (file.size > 10 * 1024 * 1024) {
                Toast.warning(`"${file.name}" excede 10MB.`);
                continue;
            }

            try {
                // 1) Subida directa a Cloudinary (unsigned)
                const cloudData = new FormData();
                cloudData.append('file', file);
                cloudData.append('upload_preset', cfg.uploadPreset);
                if (cfg.folder) cloudData.append('folder', cfg.folder);

                const cloudRes = await fetch(`https://api.cloudinary.com/v1_1/${cfg.cloudName}/image/upload`, {
                    method: 'POST',
                    body: cloudData
                });
                const cloudJson = await cloudRes.json();

                if (!cloudRes.ok || !cloudJson.secure_url) {
                    Toast.error(`Cloudinary rechazó "${file.name}": ${cloudJson.error?.message || 'error desconocido'}`);
                    continue;
                }

                // 2) Avisar al backend para que registre la imagen en DB
                const dbRes = await fetch(`/api/plugins.php?action=api&plugin=portfolio&p_action=upload_image&project_id=${projectId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        project_id: projectId,
                        image_url: cloudJson.secure_url,
                        public_id: cloudJson.public_id,
                        caption: ''
                    })
                });
                const dbJson = await dbRes.json();
                if (!dbJson.success) {
                    Toast.error(dbJson.error || `Error al guardar "${file.name}" en la base.`);
                    continue;
                }
                okCount++;
            } catch (err) {
                Toast.error(`Error de red al subir "${file.name}".`);
            }
        }

        if (okCount > 0) Toast.success(`${okCount} imagen(es) subida(s) a Cloudinary.`);
        if (sourceInput) sourceInput.value = '';

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
        const grid     = document.getElementById('pf-gallery-grid');
        if (!dropZone) return;

        // Click sobre la zona = abrir file picker (idempotente: onclick reemplaza)
        dropZone.onclick = () => document.getElementById('pf-image-input').click();

        // Soporta drop tanto en la dropzone como en el grid (UX más amplia).
        // Marcamos cada nodo con _dndInit para no apilar listeners al re-render.
        const filterImages = (filesList) => {
            return Array.from(filesList || []).filter(f => f && f.type && f.type.startsWith('image/'));
        };

        const setupDnD = (node) => {
            if (!node || node._dndInit) return;
            node._dndInit = true;

            node.addEventListener('dragover', (e) => {
                if (!e.dataTransfer || !Array.from(e.dataTransfer.types || []).includes('Files')) return;
                e.preventDefault();
                node.classList.add('is-dragover');
            });
            node.addEventListener('dragleave', (e) => {
                // Solo quitar el highlight si salimos del nodo (no de un hijo)
                if (e.relatedTarget && node.contains(e.relatedTarget)) return;
                node.classList.remove('is-dragover');
            });
            node.addEventListener('drop', (e) => {
                e.preventDefault();
                node.classList.remove('is-dragover');
                const files = filterImages(e.dataTransfer && e.dataTransfer.files);
                if (files.length === 0) {
                    Toast.warning('Solo se aceptan imágenes.');
                    return;
                }
                this.uploadImages(files);
            });
        };

        setupDnD(dropZone);
        setupDnD(grid);
    },

    // ============================================
    // VIDEO: Show Videos List
    // ============================================
    showVideos(projectId, videos = []) {
        const section = document.getElementById('pf-videos-section');
        const list = document.getElementById('pf-videos-list');
        const empty = document.getElementById('pf-videos-empty');

        section.style.display = 'block';

        if (videos.length === 0) {
            list.style.display = 'none';
            empty.style.display = 'block';
        } else {
            list.style.display = 'block';
            empty.style.display = 'none';

            list.innerHTML = videos.map(v => {
                const thumbnail = this.getVideoThumbnail(v.video_url, v.video_type);
                const typeLabel = v.video_type === 'youtube' ? 'YouTube' : (v.video_type === 'vimeo' ? 'Vimeo' : 'Video');
                const typeColor = v.video_type === 'youtube' ? '#dc2626' : (v.video_type === 'vimeo' ? '#1ab7ea' : '#6366f1');

                return `
                <div class="portfolio-video-item" data-id="${v.id}">
                    <div class="portfolio-video-thumb" onclick="window.open('${escapeHtml(v.video_url)}', '_blank')">
                        ${thumbnail ? `<img src="${thumbnail}" alt="" loading="lazy">` : 
                            `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#1a1d2e;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5" style="width:24px;height:24px;"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                            </div>`
                        }
                        <div class="portfolio-video-play">
                            <svg viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px;color:#fff;">
                                <polygon points="5 3 19 12 5 21 5 3"/>
                            </svg>
                        </div>
                    </div>
                    <div class="portfolio-video-info">
                        <span class="portfolio-video-title">${escapeHtml(v.title || 'Sin título')}</span>
                        <span class="portfolio-video-type" style="color:${typeColor};">${typeLabel}</span>
                        <span class="portfolio-video-url">${escapeHtml(v.video_url)}</span>
                    </div>
                    <div class="portfolio-video-actions">
                        <button type="button" class="btn btn-ghost btn-icon" onclick="PortfolioAdmin.toggleFeaturedVideo(${v.id}, ${v.is_featured ? 'true' : 'false'})" title="${v.is_featured ? 'Quitar de cabecera' : 'Usar como video de cabecera'}" style="color:${v.is_featured ? '#f59e0b' : 'inherit'};">
                            <svg viewBox="0 0 24 24" fill="${v.is_featured ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        </button>
                        <button type="button" class="btn btn-ghost btn-icon" onclick="window.open('${escapeHtml(v.video_url)}', '_blank')" title="Abrir video">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </button>
                        <button type="button" class="btn btn-ghost btn-icon" onclick="PortfolioAdmin.deleteVideo(${v.id})" title="Eliminar video" style="color:#dc2626;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </div>
                </div>`;
            }).join('');
        }
    },

    // ============================================
    // VIDEO: Add Video Dialog
    // ============================================
    openAddVideoDialog() {
        document.getElementById('pf-new-video-url').value = '';
        document.getElementById('pf-new-video-title').value = '';
        document.getElementById('pf-add-video-modal').classList.add('active');
    },

    closeAddVideoDialog() {
        document.getElementById('pf-add-video-modal').classList.remove('active');
    },

    // Subir archivo de video directo a Cloudinary y registrarlo como video tipo "upload"
    async uploadVideoFile(input) {
        if (this._uploadingVideo) return;
        this._uploadingVideo = true;
        try {
            const projectId = document.getElementById('pf-id').value;
            if (!projectId) { Toast.warning('Guarda el proyecto primero.'); return; }

            const file = input.files && input.files[0];
            if (!file) return;
            if (!file.type.startsWith('video/')) {
                Toast.warning('El archivo no es un video.');
                return;
            }
            // Cloudinary plan free permite hasta 100MB por archivo en uploads unsigned
            if (file.size > 100 * 1024 * 1024) {
                Toast.warning('El video excede 100MB.');
                return;
            }

            // Cargar config Cloudinary
            let cfg = window.CLOUDINARY_CFG;
            if (!cfg) {
                const node = document.getElementById('portfolio-cloudinary-cfg');
                if (node && node.dataset.cfg) {
                    try { cfg = JSON.parse(node.dataset.cfg); window.CLOUDINARY_CFG = cfg; } catch (e) {}
                }
            }
            if (!cfg || !cfg.cloudName || !cfg.uploadPreset) {
                Toast.error('Cloudinary no está configurado. Revisá config/cloudinary.php');
                return;
            }

            Toast.info('Subiendo video... puede tardar.');

            const cloudData = new FormData();
            cloudData.append('file', file);
            cloudData.append('upload_preset', cfg.uploadPreset);
            if (cfg.folder) cloudData.append('folder', cfg.folder);

            const cloudRes = await fetch(`https://api.cloudinary.com/v1_1/${cfg.cloudName}/video/upload`, {
                method: 'POST',
                body: cloudData
            });
            const cloudJson = await cloudRes.json();

            if (!cloudRes.ok || !cloudJson.secure_url) {
                Toast.error(`Cloudinary rechazó el video: ${cloudJson.error?.message || 'error desconocido'}`);
                return;
            }

            // Registrar en backend con tipo "upload"
            const result = await this.api('add_video', {
                method: 'POST',
                body: JSON.stringify({
                    project_id: parseInt(projectId),
                    video_url: cloudJson.secure_url,
                    video_type: 'upload',
                    title: file.name.replace(/\.[^.]+$/, '')
                })
            });

            if (result && result.success) {
                Toast.success('Video subido a Cloudinary.');
                input.value = '';
                const projResult = await this.api(`get&id=${projectId}`);
                if (projResult && projResult.success) {
                    this.showVideos(projectId, projResult.project.videos || []);
                }
            } else {
                Toast.error(result?.error || 'Error al guardar el video.');
            }
        } catch (err) {
            Toast.error('Error de red al subir el video.');
        } finally {
            this._uploadingVideo = false;
        }
    },

    async addVideo() {
        const projectId = document.getElementById('pf-id').value;
        if (!projectId) {
            Toast.warning('Guarda el proyecto primero.');
            return;
        }

        const videoUrl = document.getElementById('pf-new-video-url').value.trim();
        const videoTitle = document.getElementById('pf-new-video-title').value.trim();

        if (!videoUrl) {
            Toast.error('La URL del video es obligatoria.');
            return;
        }

        const result = await this.api('add_video', {
            method: 'POST',
            body: JSON.stringify({ project_id: parseInt(projectId), video_url: videoUrl, title: videoTitle })
        });

        if (result && result.success) {
            Toast.success('Video agregado.');
            this.closeAddVideoDialog();

            // Reload videos
            const projResult = await this.api(`get&id=${projectId}`);
            if (projResult && projResult.success) {
                this.showVideos(projectId, projResult.project.videos || []);
            }
            this.loadProjects(); // refresh counts
        } else {
            Toast.error(result?.error || 'Error al agregar video.');
        }
    },

    // ============================================
    // VIDEO: Delete
    // ============================================
    // Marcar/desmarcar video como destacado (cabecera del proyecto)
    async toggleFeaturedVideo(videoId, currentlyFeatured) {
        const projectId = document.getElementById('pf-id').value;
        if (!projectId) return;
        const newVideoId = currentlyFeatured ? null : videoId;
        const result = await this.api('set_featured_video', {
            method: 'POST',
            body: JSON.stringify({ project_id: parseInt(projectId), video_id: newVideoId })
        });
        if (result && result.success) {
            Toast.success(newVideoId ? 'Video usado como cabecera.' : 'Cabecera vuelve a la imagen.');
            const projResult = await this.api(`get&id=${projectId}`);
            if (projResult && projResult.success) {
                this.showVideos(projectId, projResult.project.videos || []);
            }
        } else {
            Toast.error(result?.error || 'Error al actualizar.');
        }
    },

    async deleteVideo(videoId) {
        if (!confirm('¿Eliminar este video?')) return;

        const projectId = document.getElementById('pf-id').value;

        const result = await this.api(`delete_video&id=${videoId}`, {
            method: 'POST',
            body: JSON.stringify({ _method: 'DELETE' })
        });

        if (result && result.success) {
            Toast.success('Video eliminado.');
            if (projectId) {
                const projResult = await this.api(`get&id=${projectId}`);
                if (projResult && projResult.success) {
                    this.showVideos(projectId, projResult.project.videos || []);
                }
            }
            this.loadProjects();
        } else {
            Toast.error(result?.error || 'Error al eliminar video.');
        }
    },

    // ============================================
    // VIDEO: Get Thumbnail
    // ============================================
    getVideoThumbnail(url, type) {
        if (type === 'youtube' || /youtube\.com|youtu\.be/i.test(url)) {
            const match = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/))([a-zA-Z0-9_-]{11})/);
            if (match) return `https://img.youtube.com/vi/${match[1]}/mqdefault.jpg`;
        }
        return null; // Vimeo requires API call, skip for now
    },

    // ============================================
    // VIDEO: Extract Embed URL
    // ============================================
    getEmbedUrl(url) {
        // YouTube
        const ytMatch = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/))([a-zA-Z0-9_-]{11})/);
        if (ytMatch) return `https://www.youtube.com/embed/${ytMatch[1]}`;

        // Vimeo
        const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
        if (vimeoMatch) return `https://player.vimeo.com/video/${vimeoMatch[1]}`;

        return null;
    },

    // ============================================
    // VIDEO: Show Preview (iframe)
    // ============================================
    showVideoPreview(url, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const embedUrl = this.getEmbedUrl(url);
        if (embedUrl) {
            container.innerHTML = `<iframe src="${embedUrl}" style="width:100%;height:100%;border:none;" allowfullscreen></iframe>`;
            container.style.display = 'block';
        } else {
            container.innerHTML = `<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#8b90a6;font-size:13px;padding:2rem;">URL no reconocida como YouTube o Vimeo</div>`;
            container.style.display = 'block';
        }
    },

    // ============================================
    // VIDEO: Preview Main Video
    // ============================================
    previewMainVideo() {
        const url = document.getElementById('pf-video-url').value.trim();
        const container = document.getElementById('pf-video-preview');
        if (!url) {
            container.style.display = 'none';
            container.innerHTML = '';
            return;
        }
        this.showVideoPreview(url, 'pf-video-preview');
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

        // Auto-fill year when date is picked
        const dateInput = document.getElementById('pf-date');
        if (dateInput) {
            dateInput.addEventListener('change', (e) => {
                if (e.target.value) {
                    const yearInput = document.getElementById('pf-year');
                    if (yearInput && !yearInput.value) {
                        yearInput.value = new Date(e.target.value).getFullYear();
                    }
                }
            });
        }
    }
};

// Auto-init when loaded
if (typeof PortfolioAdmin !== 'undefined') {
    // Will be called by PluginManager when the view loads
}
