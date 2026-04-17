/**
 * ============================================
 * WEBCREATOR - ADMIN PANEL JAVASCRIPT
 * ============================================
 * Full CRM: Auth, Leads CRUD, Uploads, Dashboard
 */

// ============================================
// GLOBAL STATE
// ============================================
const AdminApp = {
    user: null,
    csrfToken: '',
    leads: [],
    pagination: {},
    currentFilters: { search: '', status: '', page: 1 },
    currentView: 'dashboard' // dashboard, leads
};

// ============================================
// API HELPER
// ============================================
async function api(url, options = {}) {
    const defaults = {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': AdminApp.csrfToken
        }
    };

    const config = { ...defaults, ...options };
    if (options.headers) {
        config.headers = { ...defaults.headers, ...options.headers };
    }



    try {
        const response = await fetch(url, config);
        const data = await response.json();
        
        if (response.status === 401) {
            // Session expired
            window.location.href = '/admin/';
            return null;
        }

        return data;
    } catch (error) {
        console.error('API Error:', error);
        Toast.error('Error de conexión con el servidor.');
        return null;
    }
}

// ============================================
// TOAST (reuse from public, but standalone here)
// ============================================
const Toast = {
    container: null,
    init() {
        if (this.container) return;
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    },
    show(message, type = 'info', duration = 4000) {
        this.init();
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
        toast.innerHTML = `
            <span style="font-size:1.1rem">${icons[type] || 'ℹ'}</span>
            <span>${message}</span>
            <span class="toast-close" onclick="this.parentElement.classList.add('removing');setTimeout(()=>this.parentElement.remove(),300)">✕</span>
        `;
        this.container.appendChild(toast);
        setTimeout(() => { toast.classList.add('removing'); setTimeout(() => toast.remove(), 300); }, duration);
    },
    success(m) { this.show(m, 'success'); },
    error(m) { this.show(m, 'error', 6000); },
    warning(m) { this.show(m, 'warning'); },
    info(m) { this.show(m, 'info'); }
};

// ============================================
// AUTH: LOGIN
// ============================================
function initLogin() {
    const form = document.getElementById('login-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = form.querySelector('#login-email').value.trim();
        const password = form.querySelector('#login-password').value;
        const submitBtn = form.querySelector('button[type="submit"]');

        if (!email || !password) {
            Toast.error('Ingrese email y contraseña.');
            return;
        }

        submitBtn.classList.add('btn-loading');
        submitBtn.disabled = true;

        const result = await api('/api/auth.php?action=login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });

        submitBtn.classList.remove('btn-loading');
        submitBtn.disabled = false;

        if (result && result.success) {
            AdminApp.csrfToken = result.csrf_token;
            AdminApp.user = result.user;
            window.location.href = '/admin/dashboard.php';
        } else {
            Toast.error(result?.error || 'Credenciales incorrectas.');
            form.querySelector('#login-password').value = '';
        }
    });
}

// ============================================
// AUTH: CHECK SESSION
// ============================================
async function checkAuth() {
    const result = await api('/api/auth.php?action=me_v2');
    
    if (!result || !result.success) {
        window.location.href = '/admin/';
        return false;
    }

    AdminApp.user = result.user;
    AdminApp.csrfToken = result.csrf_token;
    
    // Update UI with user info
    updateUserUI();
    return true;
}

function updateUserUI() {
    const user = AdminApp.user;
    if (!user) return;

    // Sidebar user
    const nameEl = document.querySelector('.sidebar-user .name');
    const roleEl = document.querySelector('.sidebar-user .role');
    const avatarEl = document.querySelector('.sidebar-user .avatar');

    if (nameEl) nameEl.textContent = user.full_name || user.username;
    if (roleEl) roleEl.textContent = user.role === 'superadmin' ? 'Super Admin' : 'Admin';
    if (avatarEl) avatarEl.textContent = (user.full_name || user.username).charAt(0).toUpperCase();
}

// ============================================
// AUTH: LOGOUT
// ============================================
async function logout() {
    await api('/api/auth.php?action=logout', { method: 'POST' });
    window.location.href = '/admin/';
}

// ============================================
// DASHBOARD: Load Stats
// ============================================
async function loadDashboard() {
    const chartContainer = document.getElementById('trend-chart');
    if (chartContainer) chartContainer.innerHTML = '<div class="spinner"></div>';

    const result = await api('/api/leads.php?action=stats_v2');
    if (!result || !result.success) {
        if (chartContainer) {
            chartContainer.innerHTML = `<p style="color:#ef4444;text-align:center;padding:2rem;font-size:13px;">${result?.error || 'Error al cargar datos del dashboard'}</p>`;
        }
        return;
    }

    const stats = result.stats;

    // Update stat cards
    setValue('#stat-total', stats.total || 0);
    setValue('#stat-new', stats.by_status?.new || 0);
    setValue('#stat-contacted', stats.by_status?.contacted || 0);
    setValue('#stat-converted', stats.by_status?.converted || 0);
    setValue('#stat-today', stats.today || 0);
    setValue('#stat-week', stats.this_week || 0);

    // Update sidebar leads badge (show new leads count)
    const newCount = stats.by_status?.new || 0;
    const badge = document.getElementById('leads-count');
    if (badge) {
        badge.textContent = newCount;
        badge.style.display = newCount > 0 ? 'inline-flex' : 'none';
    }

    // Render simple chart (daily trend)
    renderTrendChart(stats.daily_trend || []);
}

function setValue(selector, value) {
    const el = document.querySelector(selector);
    if (el) {
        animateNumber(el, 0, value, 600);
    }
}

function animateNumber(el, start, end, duration) {
    const startTime = performance.now();
    const diff = end - start;
    
    function frame(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
        el.textContent = Math.round(start + diff * eased);
        if (progress < 1) requestAnimationFrame(frame);
    }
    
    requestAnimationFrame(frame);
}

function renderTrendChart(data) {
    const chartContainer = document.getElementById('trend-chart');
    if (!chartContainer) return;

    if (data.length === 0) {
        chartContainer.innerHTML = '<p style="color:var(--text-tertiary);text-align:center;padding:2rem;">Sin datos para el período</p>';
        return;
    }

    const maxCount = Math.max(...data.map(d => d.count), 1);
    const barWidth = Math.floor(100 / Math.max(data.length, 1));

    let html = '<div style="display:flex;align-items:flex-end;gap:4px;height:160px;padding:0 8px;">';
    
    data.forEach((day, i) => {
        const height = Math.max((day.count / maxCount) * 100, 4);
        const dateStr = new Date(day.date + 'T12:00:00').toLocaleDateString('es', { weekday: 'short', day: 'numeric' });
        html += `
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;min-width:0;">
                <span style="font-size:0.7rem;color:var(--text-primary);font-weight:600;">${day.count}</span>
                <div style="width:100%;height:${height}%;background:linear-gradient(180deg,var(--color-primary),var(--color-secondary));border-radius:4px 4px 0 0;transition:height 0.5s ease;min-height:3px;" 
                     title="${dateStr}: ${day.count} leads"></div>
                <span style="font-size:0.65rem;color:var(--text-tertiary);white-space:nowrap;">${dateStr}</span>
            </div>
        `;
    });

    html += '</div>';
    chartContainer.innerHTML = html;
}

// ============================================
// LEADS: Load & Render
// ============================================
async function loadLeads() {
    const tbody = document.getElementById('leads-tbody');
    if (tbody) tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:3rem;"><div class="spinner" style="margin:0 auto;"></div></td></tr>`;

    const { search, status, page } = AdminApp.currentFilters;
    let url = `/api/leads.php?page=${page}&limit=25`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;

    const result = await api(url);
    if (!result || !result.success) {
        if (tbody) tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:3rem;color:#ef4444;font-size:13px;">${result?.error || 'Error al cargar leads'}</td></tr>`;
        return;
    }

    AdminApp.leads = result.leads;
    AdminApp.pagination = result.pagination;

    renderLeadsTable();
    renderPagination();
}

function renderLeadsTable() {
    const tbody = document.getElementById('leads-tbody');
    if (!tbody) return;

    if (AdminApp.leads.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align:center;padding:3rem;">
                    <div class="empty-state" style="padding:2rem 0;">
                        <div class="icon" style="color:var(--text-tertiary);margin-bottom:var(--space-4);">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px;margin:0 auto;display:block;"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
                        </div>
                        <h3>No se encontraron leads</h3>
                        <p>Aún no hay leads registrados o tu búsqueda no produjo resultados.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = AdminApp.leads.map(lead => `
        <tr data-id="${lead.id}">
            <td>
                <strong>${escapeHtml(lead.name)}</strong>
            </td>
            <td>${escapeHtml(lead.email)}</td>
            <td>${escapeHtml(lead.phone || '—')}</td>
            <td>
                <select class="lead-status-select status-${lead.status}" onchange="changeLeadStatus(${lead.id}, this.value)" title="Cambiar estado">
                    <option value="new" ${lead.status === 'new' ? 'selected' : ''}>Nuevo</option>
                    <option value="contacted" ${lead.status === 'contacted' ? 'selected' : ''}>Contactado</option>
                    <option value="qualified" ${lead.status === 'qualified' ? 'selected' : ''}>Calificado</option>
                    <option value="converted" ${lead.status === 'converted' ? 'selected' : ''}>Convertido</option>
                    <option value="lost" ${lead.status === 'lost' ? 'selected' : ''}>Perdido</option>
                </select>
            </td>
            <td>${escapeHtml(lead.source || 'web')}</td>
            <td>${formatDate(lead.created_at)}</td>
            <td>
                <div class="flex gap-2">
                    <button class="btn btn-ghost btn-icon" onclick="viewLead(${lead.id})" title="Ver/Editar detalle">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                    <button class="btn btn-ghost btn-icon" onclick="deleteLead(${lead.id})" title="Eliminar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:#ef4444;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function renderPagination() {
    const container = document.getElementById('leads-pagination');
    if (!container) return;

    const { total, page, pages } = AdminApp.pagination;
    if (pages <= 1) { container.innerHTML = ''; return; }

    let html = `<button ${page <= 1 ? 'disabled' : ''} onclick="goToPage(${page - 1})">←</button>`;

    for (let i = 1; i <= pages; i++) {
        if (i === 1 || i === pages || Math.abs(i - page) <= 1) {
            html += `<button class="${i === page ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
        } else if (Math.abs(i - page) === 2) {
            html += `<button disabled>…</button>`;
        }
    }

    html += `<button ${page >= pages ? 'disabled' : ''} onclick="goToPage(${page + 1})">→</button>`;
    container.innerHTML = html;
}

function goToPage(page) {
    AdminApp.currentFilters.page = page;
    loadLeads();
}

// ============================================
// LEADS: Actions
// ============================================
async function viewLead(id) {
    const result = await api(`/api/leads.php?id=${id}`);
    if (!result || !result.success) return;

    const lead = result.lead;
    
    const modalBody = document.getElementById('lead-modal-body');
    if (!modalBody) return;

    modalBody.innerHTML = `
        <form class="lead-detail-form" id="lead-edit-form" onsubmit="saveLead(event, ${lead.id})">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:var(--space-4);margin-bottom:var(--space-4);">
                <div class="config-control" style="margin-bottom:0;">
                    <label class="config-label">Nombre</label>
                    <input type="text" id="edit-lead-name" class="form-input" value="${escapeHtml(lead.name)}" required style="background:#fff;border:1.5px solid #e8eaf2;border-radius:8px;padding:8px 12px;font-size:13px;width:100%;">
                </div>
                <div class="config-control" style="margin-bottom:0;">
                    <label class="config-label">Email</label>
                    <input type="email" id="edit-lead-email" class="form-input" value="${escapeHtml(lead.email)}" required style="background:#fff;border:1.5px solid #e8eaf2;border-radius:8px;padding:8px 12px;font-size:13px;width:100%;">
                </div>
                <div class="config-control" style="margin-bottom:0;">
                    <label class="config-label">Teléfono</label>
                    <input type="text" id="edit-lead-phone" class="form-input" value="${escapeHtml(lead.phone || '')}" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:8px;padding:8px 12px;font-size:13px;width:100%;">
                </div>
                <div class="config-control" style="margin-bottom:0;">
                    <label class="config-label">Estado</label>
                    <select id="edit-lead-status" class="config-select form-input" style="background:#fff;border:1.5px solid #e8eaf2;border-radius:8px;padding:8px 12px;font-size:13px;width:100%;">
                        <option value="new" ${lead.status === 'new' ? 'selected' : ''}>Nuevo</option>
                        <option value="contacted" ${lead.status === 'contacted' ? 'selected' : ''}>Contactado</option>
                        <option value="qualified" ${lead.status === 'qualified' ? 'selected' : ''}>Calificado</option>
                        <option value="converted" ${lead.status === 'converted' ? 'selected' : ''}>Convertido</option>
                        <option value="lost" ${lead.status === 'lost' ? 'selected' : ''}>Perdido</option>
                    </select>
                </div>
            </div>

            <div class="detail-row">
                <span class="detail-label">Fuente / Fecha</span>
                <span class="detail-value" style="color:var(--text-secondary);font-size:var(--text-sm);">
                    ${escapeHtml(lead.source || 'website')} &middot; ${formatDate(lead.created_at, true)}
                </span>
            </div>

            ${lead.message ? `
                <div class="config-control" style="margin-top:var(--space-4);">
                    <label class="config-label">Mensaje del cliente (original)</label>
                    <div style="background:var(--bg-tertiary);padding:var(--space-3);border-radius:8px;font-size:13px;color:var(--text-secondary);">
                        ${escapeHtml(lead.message).replace(/\\n/g, '<br>')}
                    </div>
                </div>
            ` : ''}

            <div class="config-control" style="margin-top:var(--space-4);">
                <label class="config-label">Notas internas (Solo visible en admin)</label>
                <textarea id="edit-lead-notes" class="form-input" rows="4" placeholder="Observaciones, acuerdos..." style="background:#fff;border:1.5px solid #e8eaf2;border-radius:8px;padding:10px 14px;font-size:13px;width:100%;font-family:inherit;resize:vertical;">${escapeHtml(lead.notes || '')}</textarea>
            </div>

            <div class="detail-row" style="margin-top:var(--space-4);padding-top:var(--space-3);border-top:1px solid var(--border-light);">
                <span class="detail-label">IP Address</span>
                <span class="detail-value" style="font-family:var(--font-mono);font-size:var(--text-xs);color:var(--text-tertiary);">${escapeHtml(lead.ip_address || '—')}</span>
            </div>

            ${lead.log && lead.log.length > 0 ? `
            <div class="config-control" style="margin-top:var(--space-5);">
                <label class="config-label">Bitácora de Cliente (Historial)</label>
                <div style="background:#f7f8fc;border:1px solid var(--border-color);border-radius:8px;padding:var(--space-4);max-height:200px;overflow-y:auto;">
                    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:16px;">
                        ${lead.log.map(item => `
                            <li style="display:flex;gap:12px;font-size:12px;">
                                <div style="flex-shrink:0;color:var(--color-primary);margin-top:2px;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </div>
                                <div style="flex:1;">
                                    <div style="color:var(--text-primary);font-weight:600;margin-bottom:2px;">
                                        ${item.action === 'create' ? 'Lead registrado' : (item.action === 'update' ? 'Lead modificado' : escapeHtml(item.action))}
                                        <span style="color:var(--text-tertiary);font-weight:400;margin-left:4px;">por ${escapeHtml(item.user_name || 'Sistema')}</span>
                                    </div>
                                    <div style="color:var(--text-secondary);font-size:11px;margin-bottom:4px;">
                                        ${formatDate(item.created_at, true)}
                                    </div>
                                    ${item.details ? `<div style="color:var(--text-secondary);font-family:var(--font-mono);font-size:10px;background:#fff;padding:4px 8px;border-radius:4px;border:1px solid #eef0f6;display:inline-block;">Cambios: ${escapeHtml(item.details)}</div>` : ''}
                                </div>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            </div>
            ` : ''}

            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:var(--space-5);">
                <button type="button" class="btn btn-secondary" onclick="closeModal('lead-modal')" style="background:#f0f2f8;color:var(--text-primary);border:1px solid var(--border-color);">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="save-lead-btn" style="background:var(--color-primary);color:#fff;">Guardar Cambios</button>
            </div>
        </form>
    `;

    document.getElementById('lead-modal-title').textContent = `CRM: Editar Lead #${lead.id}`;
    
    const modalFooter = document.querySelector('#lead-modal .modal-footer');
    if (modalFooter) modalFooter.style.display = 'none';

    openModal('lead-modal');
    
    // Restaurar el footer cuando se cierre el modal
    const checkForClose = setInterval(() => {
        const myModal = document.getElementById('lead-modal');
        if (!myModal || !myModal.classList.contains('active')) {
            if (modalFooter) modalFooter.style.display = 'flex';
            clearInterval(checkForClose);
        }
    }, 500);
}

async function saveLead(event, id) {
    event.preventDefault();
    
    const btn = document.getElementById('save-lead-btn');
    if (btn) {
        btn.classList.add('btn-loading');
        btn.disabled = true;
    }

    const data = {
        name: document.getElementById('edit-lead-name').value.trim(),
        email: document.getElementById('edit-lead-email').value.trim(),
        phone: document.getElementById('edit-lead-phone').value.trim(),
        status: document.getElementById('edit-lead-status').value,
        notes: document.getElementById('edit-lead-notes').value.trim()
    };

    const result = await api(`/api/leads.php?id=${id}`, {
        method: 'POST',
        body: JSON.stringify({ _method: 'PUT', ...data })
    });

    if (btn) {
        btn.classList.remove('btn-loading');
        btn.disabled = false;
    }

    if (result && result.success) {
        Toast.success('Lead actualizado exitosamente.');
        closeModal('lead-modal');
        const modalFooter = document.querySelector('#lead-modal .modal-footer');
        if (modalFooter) modalFooter.style.display = 'flex';
        loadLeads();
    } else {
        Toast.error(result?.error || 'Error al actualizar el lead.');
    }
}

async function changeLeadStatus(id, newStatus) {
    const result = await api(`/api/leads.php?id=${id}`, {
        method: 'POST',
        body: JSON.stringify({ _method: 'PUT', status: newStatus })
    });

    if (result && result.success) {
        Toast.success(`Estado actualizado a: ${getStatusLabel(newStatus)}`);
        // Update local data to refresh the select styling without full reload
        const lead = AdminApp.leads.find(l => l.id == id);
        if (lead) lead.status = newStatus;
        renderLeadsTable();
    } else {
        Toast.error(result?.error || 'Error al actualizar.');
        renderLeadsTable(); // Revert select to original value
    }
}

async function deleteLead(id) {
    if (!confirm('¿Estás seguro de eliminar este lead? Esta acción no se puede deshacer.')) return;

    const result = await api(`/api/leads.php?id=${id}`, {
        method: 'POST',
        body: JSON.stringify({ _method: 'DELETE' })
    });

    if (result && result.success) {
        Toast.success('Lead eliminado exitosamente.');
        loadLeads();
    } else {
        Toast.error(result?.error || 'Error al eliminar.');
    }
}

// ============================================
// MODAL SYSTEM
// ============================================
function openModal(id) {
    const modal = document.getElementById(id);
    const backdrop = document.getElementById('modal-backdrop');
    if (modal) modal.classList.add('active');
    if (backdrop) backdrop.classList.add('active');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    const backdrop = document.getElementById('modal-backdrop');
    if (modal) modal.classList.remove('active');
    if (backdrop) backdrop.classList.remove('active');
}

// Close modal on backdrop click
document.addEventListener('click', (e) => {
    if (e.target.id === 'modal-backdrop') {
        document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
        e.target.classList.remove('active');
    }
});

// Close modal with Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
        const backdrop = document.getElementById('modal-backdrop');
        if (backdrop) backdrop.classList.remove('active');
    }
});

// ============================================
// IMAGE UPLOAD
// ============================================
async function uploadImage(fileInput) {
    const file = fileInput.files[0];
    if (!file) return null;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', AdminApp.csrfToken);

    try {
        const response = await fetch('/api/upload.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            Toast.success('Imagen subida exitosamente.');
            return result.media;
        } else {
            Toast.error(result.error || 'Error al subir imagen.');
            return null;
        }
    } catch (err) {
        Toast.error('Error de conexión al subir imagen.');
        return null;
    }
}

// ============================================
// LEADS TOOLBAR: Search & Filter
// ============================================
function initLeadsToolbar() {
    // Search
    const searchInput = document.getElementById('leads-search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                AdminApp.currentFilters.search = e.target.value;
                AdminApp.currentFilters.page = 1;
                loadLeads();
            }, 400); // Debounce 400ms
        });
    }

    // Status filters
    document.querySelectorAll('.filter-btn[data-status]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filter-btn[data-status]').forEach(b => b.classList.remove('active'));
            
            const status = btn.dataset.status;
            if (AdminApp.currentFilters.status === status) {
                AdminApp.currentFilters.status = '';
            } else {
                btn.classList.add('active');
                AdminApp.currentFilters.status = status;
            }
            
            AdminApp.currentFilters.page = 1;
            loadLeads();
        });
    });
}

// ============================================
// SIDEBAR NAVIGATION
// ============================================
function initSidebar() {
    // Toggle sidebar on mobile
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    // Navigation links
    document.querySelectorAll('.sidebar-link[data-view]').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const view = link.dataset.view;
            switchView(view);
        });
    });
}

function switchView(viewName) {
    // Update active sidebar link
    document.querySelectorAll('.sidebar-link[data-view]').forEach(l => l.classList.remove('active'));
    document.querySelector(`.sidebar-link[data-view="${viewName}"]`)?.classList.add('active');

    // Hide all views, show target
    document.querySelectorAll('.admin-view').forEach(v => v.classList.add('hidden'));
    const targetView = document.getElementById(`view-${viewName}`);
    if (targetView) targetView.classList.remove('hidden');

    // Update header title
    const titles = { dashboard: 'Dashboard', leads: 'Gestión de Leads', settings: 'Configuración del Sitio', plugins: 'Gestionar Plugins' };
    const headerTitle = document.querySelector('.admin-header h1');
    if (headerTitle) headerTitle.textContent = titles[viewName] || viewName;

    AdminApp.currentView = viewName;

    // Load data for the view
    if (viewName === 'dashboard') loadDashboard();
    if (viewName === 'leads') loadLeads();
    if (viewName === 'settings') { loadSiteInfo(); loadThankYouConfig(); loadSavedLogos(); loadThemeConfig(); }
    if (viewName === 'plugins') PluginManager.loadPlugins();

    // Handle dynamic plugin views (e.g., view-plugin-portfolio)
    if (viewName.startsWith('plugin-')) {
        const pluginId = viewName.replace('plugin-', '');
        PluginManager.loadPluginView(pluginId);
    }
}

// ============================================
// UTILITIES
// ============================================
function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function getStatusLabel(status) {
    const labels = {
        new: 'Nuevo',
        contacted: 'Contactado',
        qualified: 'Calificado',
        converted: 'Convertido',
        lost: 'Perdido'
    };
    return labels[status] || status;
}

function formatDate(dateStr, includeTime = false) {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    const options = { day: '2-digit', month: 'short', year: 'numeric' };
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    return date.toLocaleDateString('es-CL', options);
}

// ============================================
// EXPORT LEADS TO CSV
// ============================================
async function exportLeadsCSV() {
    const result = await api('/api/leads.php?limit=1000');
    if (!result || !result.success) return;

    const leads = result.leads;
    if (leads.length === 0) {
        Toast.warning('No hay leads para exportar.');
        return;
    }

    const headers = ['ID', 'Nombre', 'Email', 'Teléfono', 'Estado', 'Fuente', 'Mensaje', 'Fecha'];
    const rows = leads.map(l => [
        l.id, l.name, l.email, l.phone || '', getStatusLabel(l.status), l.source || '', 
        (l.message || '').replace(/"/g, '""'), l.created_at
    ]);

    let csv = headers.join(',') + '\n';
    rows.forEach(row => {
        csv += row.map(val => `"${val}"`).join(',') + '\n';
    });

    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `leads_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();

    Toast.success(`${leads.length} leads exportados exitosamente.`);
}

// ============================================
// LOGO MANAGEMENT (stored in DB via settings API)
// ============================================
function previewLogo(input, type) {
    const file = input.files[0];
    if (!file) return;

    // Validate
    if (file.size > 2 * 1024 * 1024) {
        Toast.error('El logo no puede pesar más de 2MB.');
        input.value = '';
        return;
    }

    if (!file.type.startsWith('image/')) {
        Toast.error('Solo se permiten archivos de imagen.');
        input.value = '';
        return;
    }

    const previewArea = document.getElementById(`logo-${type}-preview`);
    const placeholder = document.getElementById(`logo-${type}-placeholder`);
    const removeBtn = document.getElementById(`logo-${type}-remove`);

    const reader = new FileReader();
    reader.onload = (e) => {
        if (placeholder) placeholder.style.display = 'none';
        
        const existingImg = previewArea.querySelector('img');
        if (existingImg) existingImg.remove();

        const img = document.createElement('img');
        img.src = e.target.result;
        img.alt = type === 'normal' ? 'Logo Principal' : 'Logo Negativo';
        previewArea.appendChild(img);

        if (removeBtn) removeBtn.style.display = 'inline-flex';

        // Upload to server and save path in DB
        uploadLogo(file, type);
    };
    reader.readAsDataURL(file);
}

async function uploadLogo(file, type) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', AdminApp.csrfToken);
    formData.append('alt_text', `logo-${type}`);

    try {
        const response = await fetch('/api/upload.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            const logoKey = type === 'normal' ? 'logo_normal' : 'logo_negative';
            const logoUrl = result.media.url || '/' + result.media.path;
            
            // Save to database settings
            const settingsData = {};
            settingsData[logoKey] = logoUrl;
            
            await api('/api/settings.php', {
                method: 'POST',
                body: JSON.stringify({ _method: 'PUT', ...settingsData })
            });
            
            Toast.success(`Logo ${type === 'normal' ? 'principal' : 'negativo'} actualizado y guardado.`);
        } else {
            Toast.error(result.error || 'Error al subir el logo.');
        }
    } catch (err) {
        Toast.error('Error de conexión al subir el logo.');
    }
}

async function removeLogo(type) {
    const previewArea = document.getElementById(`logo-${type}-preview`);
    const placeholder = document.getElementById(`logo-${type}-placeholder`);
    const removeBtn = document.getElementById(`logo-${type}-remove`);
    const fileInput = document.getElementById(`logo-${type}-input`);

    const img = previewArea.querySelector('img');
    if (img) img.remove();

    if (placeholder) placeholder.style.display = 'flex';
    if (removeBtn) removeBtn.style.display = 'none';
    if (fileInput) fileInput.value = '';

    // Clear from database
    const logoKey = type === 'normal' ? 'logo_normal' : 'logo_negative';
    const settingsData = {};
    settingsData[logoKey] = '';
    
    await api('/api/settings.php', {
        method: 'POST',
        body: JSON.stringify({ _method: 'PUT', ...settingsData })
    });

    Toast.info(`Logo ${type === 'normal' ? 'principal' : 'negativo'} eliminado.`);
}

async function loadSavedLogos() {
    const result = await api('/api/settings.php');
    if (!result || !result.success) return;

    const settings = result.settings || {};

    ['normal', 'negative'].forEach(type => {
        const logoKey = type === 'normal' ? 'logo_normal' : 'logo_negative';
        const savedUrl = settings[logoKey];
        
        if (savedUrl) {
            const previewArea = document.getElementById(`logo-${type}-preview`);
            const placeholder = document.getElementById(`logo-${type}-placeholder`);
            const removeBtn = document.getElementById(`logo-${type}-remove`);

            if (previewArea && placeholder) {
                placeholder.style.display = 'none';
                
                const existingImg = previewArea.querySelector('img');
                if (existingImg) existingImg.remove();

                const img = document.createElement('img');
                img.src = savedUrl;
                img.alt = type === 'normal' ? 'Logo Principal' : 'Logo Negativo';
                previewArea.appendChild(img);

                if (removeBtn) removeBtn.style.display = 'inline-flex';
            }
        }
    });
}

// Drag and drop support for logo areas
function initLogoDragDrop() {
    ['normal', 'negative'].forEach(type => {
        const previewArea = document.getElementById(`logo-${type}-preview`);
        if (!previewArea) return;

        previewArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            previewArea.style.borderColor = '#6366f1';
            previewArea.style.transform = 'scale(1.01)';
        });

        previewArea.addEventListener('dragleave', () => {
            previewArea.style.borderColor = '';
            previewArea.style.transform = '';
        });

        previewArea.addEventListener('drop', (e) => {
            e.preventDefault();
            previewArea.style.borderColor = '';
            previewArea.style.transform = '';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = document.getElementById(`logo-${type}-input`);
                // Create a new DataTransfer to set to the input
                const dt = new DataTransfer();
                dt.items.add(files[0]);
                input.files = dt.files;
                previewLogo(input, type);
            }
        });
    });
}

// ============================================
// SITE INFO CONFIGURATION
// ============================================
const SITE_INFO_KEYS = [
    'siteName', 'siteDescription', 'phone', 'email', 'whatsapp', 'address',
    'instagram', 'facebook', 'youtube', 'linkedin', 'twitter', 'pinterest', 'tiktok'
];

async function loadSiteInfo() {
    const result = await api('/api/settings.php?key=site_info');
    if (result && result.success && result.setting && result.setting.setting_value) {
        try {
            const info = JSON.parse(result.setting.setting_value);
            SITE_INFO_KEYS.forEach(key => {
                const el = document.getElementById(`info-${key}`);
                if (el && info[key]) el.value = info[key];
            });
        } catch (e) {
            console.warn('Could not parse site_info:', e);
        }
    }
}

async function saveSiteInfo() {
    const info = {};
    SITE_INFO_KEYS.forEach(key => {
        const el = document.getElementById(`info-${key}`);
        if (el) info[key] = el.value.trim();
    });

    const result = await api('/api/settings.php', {
        method: 'POST',
        body: JSON.stringify({ _method: 'PUT', site_info: info })
    });

    if (result && result.success) {
        Toast.success('Información del sitio guardada correctamente.');
        const brand = document.getElementById('sidebar-site-name');
        if (brand && info.siteName) brand.textContent = info.siteName;
    } else {
        Toast.error(result?.error || 'Error al guardar la información.');
    }
}

// ============================================
// THANK YOU PAGE CONFIGURATION
// ============================================
const THANK_YOU_KEYS = ['title', 'message', 'youtubeUrl', 'ctaText', 'ctaUrl'];

async function loadThankYouConfig() {
    const result = await api('/api/settings.php?key=thank_you_config');
    if (result && result.success && result.setting && result.setting.setting_value) {
        try {
            const config = JSON.parse(result.setting.setting_value);
            THANK_YOU_KEYS.forEach(key => {
                const el = document.getElementById(`ty-${key}`);
                if (el && config[key]) el.value = config[key];
            });
            const showSocial = document.getElementById('ty-showSocial');
            if (showSocial) showSocial.checked = !!config.showSocial;
        } catch (e) {
            console.warn('Could not parse thank_you_config:', e);
        }
    }
}

async function saveThankYouConfig() {
    const config = {};
    THANK_YOU_KEYS.forEach(key => {
        const el = document.getElementById(`ty-${key}`);
        if (el) config[key] = el.value.trim();
    });
    const showSocial = document.getElementById('ty-showSocial');
    config.showSocial = showSocial ? showSocial.checked : false;

    const result = await api('/api/settings.php', {
        method: 'POST',
        body: JSON.stringify({ _method: 'PUT', thank_you_config: config })
    });

    if (result && result.success) {
        Toast.success('Página de agradecimiento guardada.');
    } else {
        Toast.error(result?.error || 'Error al guardar.');
    }
}

// ============================================
// THEME CONFIGURATION
// ============================================
const THEME_CONFIG_KEYS = [
    'colorPrimary', 'colorPrimaryHover', 'colorSecondary', 'colorAccent',
    'borderRadius', 'btnRadius', 'btnColor', 'btnHoverColor',
    'fontHeadings', 'fontMenu', 'fontBody',
    'h1Size', 'h1Weight', 'h1Color',
    'h2Size', 'h2Weight', 'h2Color',
    'h3Size', 'h3Weight', 'h3Color',
    'h4Size', 'h4Weight', 'h4Color',
    'h5Size', 'h5Weight', 'h5Color',
    'h6Size', 'h6Weight', 'h6Color',
];

const THEME_DEFAULTS = {
    colorPrimary: '#6366f1',
    colorPrimaryHover: '#4f46e5',
    colorSecondary: '#c9a96e',
    colorAccent: '#06b6d4',
    borderRadius: '12',
    btnRadius: '8',
    btnColor: '#c9a96e',
    btnHoverColor: '#b8944f',
    fontHeadings: 'Montserrat',
    fontMenu: 'Montserrat',
    fontBody: 'Montserrat',
    h1Size: '48px', h1Weight: '700', h1Color: '#ffffff',
    h2Size: '36px', h2Weight: '700', h2Color: '#ffffff',
    h3Size: '28px', h3Weight: '600', h3Color: '#ffffff',
    h4Size: '22px', h4Weight: '600', h4Color: '#ffffff',
    h5Size: '18px', h5Weight: '600', h5Color: '#ffffff',
    h6Size: '16px', h6Weight: '600', h6Color: '#ffffff',
};

function getThemeConfigFromUI() {
    const config = {};
    THEME_CONFIG_KEYS.forEach(key => {
        const el = document.getElementById(`cfg-${key}`);
        if (el) config[key] = el.value;
    });
    // Always add px unit for radius values (even if 0)
    if (config.borderRadius !== undefined) config.borderRadius = config.borderRadius + 'px';
    if (config.btnRadius !== undefined) config.btnRadius = config.btnRadius + 'px';
    // Ensure font size values have px
    for (let i = 1; i <= 6; i++) {
        const sizeKey = `h${i}Size`;
        if (config[sizeKey] && !config[sizeKey].endsWith('px')) {
            config[sizeKey] = config[sizeKey] + 'px';
        }
    }
    return config;
}

function setThemeConfigToUI(config) {
    THEME_CONFIG_KEYS.forEach(key => {
        const el = document.getElementById(`cfg-${key}`);
        if (!el) return;

        // Use config value if it exists (even if "0" or "#000000"), otherwise default
        let val = (config[key] !== undefined && config[key] !== null && config[key] !== '') 
            ? config[key] 
            : THEME_DEFAULTS[key];
        
        // Strip px/rem for range/number inputs (keep 0 as valid!)
        if ((key === 'borderRadius' || key === 'btnRadius') && typeof val === 'string') {
            const parsed = parseInt(val);
            val = isNaN(parsed) ? THEME_DEFAULTS[key] : parsed;
        }
        
        // Strip units from font size values for number inputs
        if (key.match(/^h[1-6]Size$/) && typeof val === 'string') {
            const parsed = parseInt(val);
            if (!isNaN(parsed)) val = parsed + 'px';
        }

        el.value = val;

        // Sync hex text inputs for color pickers
        const hexInput = document.getElementById(`cfg-${key}-hex`);
        if (hexInput) hexInput.value = val;

        // Update range value labels (only for radius fields)
        const valLabel = document.getElementById(`cfg-${key}-val`);
        if (valLabel) valLabel.textContent = val + 'px';
    });
}

async function loadThemeConfig() {
    const result = await api('/api/settings.php?key=theme_config');
    console.log('[Theme Load] API response:', result);
    if (result && result.success && result.setting && result.setting.setting_value) {
        try {
            const config = JSON.parse(result.setting.setting_value);
            setThemeConfigToUI(config);
        } catch (e) {
            console.warn('Could not parse theme config:', e);
        }
    }
}

async function saveThemeConfig() {
    const btn = document.getElementById('save-theme-btn');
    if (btn) { btn.classList.add('btn-loading'); btn.disabled = true; }

    const config = getThemeConfigFromUI();
    console.log('[Theme Save] Sending config:', config);

    const result = await api('/api/settings.php', {
        method: 'POST',
        body: JSON.stringify({ _method: 'PUT', theme_config: config })
    });
    console.log('[Theme Save] API response:', result);

    if (btn) { btn.classList.remove('btn-loading'); btn.disabled = false; }

    if (result && result.success) {
        Toast.success('Configuración del tema guardada. Los cambios se reflejarán en el sitio público.');
    } else {
        Toast.error(result?.error || 'Error al guardar la configuración.');
    }
}

function resetThemeConfig() {
    if (!confirm('¿Restablecer toda la configuración de diseño a los valores por defecto?')) return;
    setThemeConfigToUI(THEME_DEFAULTS);
    Toast.info('Valores restablecidos. Haz clic en "Guardar" para aplicar.');
}

function initThemeConfigControls() {
    // Sync color pickers with hex inputs
    const colorKeys = ['colorPrimary', 'colorPrimaryHover', 'colorSecondary', 'colorAccent', 'btnColor', 'btnHoverColor'];
    colorKeys.forEach(key => {
        const picker = document.getElementById(`cfg-${key}`);
        const hex = document.getElementById(`cfg-${key}-hex`);
        if (!picker || !hex) return;

        picker.addEventListener('input', () => { hex.value = picker.value; });
        hex.addEventListener('input', () => {
            if (/^#[0-9a-fA-F]{6}$/.test(hex.value)) {
                picker.value = hex.value;
            }
        });
    });

    // Range sliders value display
    ['borderRadius', 'btnRadius'].forEach(key => {
        const range = document.getElementById(`cfg-${key}`);
        const label = document.getElementById(`cfg-${key}-val`);
        if (range && label) {
            range.addEventListener('input', () => { label.textContent = range.value + 'px'; });
        }
    });
}

// ============================================
// PLUGIN MANAGER
// ============================================
const PluginManager = {
    plugins: [],
    loadedAssets: new Set(),

    // Icons for plugins (SVG paths by icon name)
    icons: {
        briefcase: '<path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>',
        box: '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
        layers: '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>',
        image: '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>',
        default: '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>'
    },

    getIcon(name) {
        return this.icons[name] || this.icons.default;
    },

    // ── Load all plugins ──
    async loadPlugins() {
        const result = await api('/api/plugins.php?action=list');
        if (!result || !result.success) return;

        this.plugins = result.plugins;
        this.renderGrid();
        this.injectSidebarLinks();
        this.loadActivePluginAssets();
    },

    // ── Render plugin cards ──
    renderGrid() {
        const grid = document.getElementById('plugins-grid');
        if (!grid) return;

        if (this.plugins.length === 0) {
            grid.innerHTML = `
                <div style="text-align:center;padding:4rem 2rem;grid-column:1/-1;">
                    <div style="font-size:3rem;margin-bottom:1rem;">🧩</div>
                    <h3 style="color:#1a1d2e;margin-bottom:0.5rem;">Sin plugins instalados</h3>
                    <p style="color:#8b90a6;font-size:var(--text-sm);">Arrastra un archivo ZIP de plugin arriba o coloca la carpeta del plugin en <code>/plugins/</code>.</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = this.plugins.map(p => `
            <div class="plugin-card ${p.is_active ? 'plugin-active' : ''}">
                <div class="plugin-card-header">
                    <div class="plugin-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            ${this.getIcon(p.icon || 'default')}
                        </svg>
                    </div>
                    <span class="plugin-badge ${p.is_active ? 'plugin-badge-active' : 'plugin-badge-inactive'}">
                        ${p.is_active ? 'Activo' : 'Inactivo'}
                    </span>
                </div>
                <h3 class="plugin-name">${escapeHtml(p.name)}</h3>
                <p class="plugin-desc">${escapeHtml(p.description || '')}</p>
                <div class="plugin-meta">
                    <span>v${escapeHtml(p.version || '1.0')}</span>
                    <span>por ${escapeHtml(p.author || 'Desconocido')}</span>
                </div>
                <div class="plugin-actions">
                    ${p.is_active 
                        ? `<button class="btn btn-danger btn-sm" onclick="PluginManager.deactivate('${escapeHtml(p.id)}', '${escapeHtml(p.name)}')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                            Desactivar
                           </button>
                           <button class="btn btn-primary btn-sm" onclick="switchView('plugin-${escapeHtml(p.id)}')">
                            Configurar →
                           </button>`
                        : `<button class="btn btn-primary btn-sm" onclick="PluginManager.activate('${escapeHtml(p.id)}', '${escapeHtml(p.name)}')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polyline points="20 6 9 17 4 12"/></svg>
                            Activar
                           </button>`
                    }
                </div>
            </div>
        `).join('');
    },

    // ── Inject active plugin links into sidebar ──
    injectSidebarLinks() {
        const container = document.getElementById('plugin-sidebar-links');
        const containerPrincipal = document.getElementById('plugin-sidebar-principal');

        const activePlugins = this.plugins.filter(p => p.is_active);
        
        let normalHtml = '';
        let principalHtml = '';

        activePlugins.forEach(p => {
            const linkHtml = `
            <a class="sidebar-link" data-view="plugin-${p.id}" href="#">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    ${this.getIcon(p.icon || 'default')}
                </svg>
                ${escapeHtml(p.sidebar_label || p.name)}
            </a>
            `;

            if (p.id === 'portfolio' || p.sidebar_group === 'principal') {
                principalHtml += linkHtml;
            } else {
                normalHtml += linkHtml;
            }
        });

        if (container) container.innerHTML = normalHtml;
        if (containerPrincipal) containerPrincipal.innerHTML = principalHtml;

        // Re-bind click events for new links
        document.querySelectorAll('.sidebar-link[data-view^="plugin-"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                switchView(link.dataset.view);
            });
        });
    },

    // ── Load CSS and JS for active plugins ──
    loadActivePluginAssets() {
        const activePlugins = this.plugins.filter(p => p.is_active);
        
        activePlugins.forEach(p => {
            const cssKey = `css-${p.id}`;
            const jsKey = `js-${p.id}`;

            if (!this.loadedAssets.has(cssKey)) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = `/plugins/${p.folder || p.id}/admin.css?v=${Date.now()}`;
                link.id = `plugin-css-${p.id}`;
                document.head.appendChild(link);
                this.loadedAssets.add(cssKey);
            }

            if (!this.loadedAssets.has(jsKey)) {
                const script = document.createElement('script');
                script.src = `/plugins/${p.folder || p.id}/admin.js?v=${Date.now()}`;
                script.id = `plugin-js-${p.id}`;
                document.body.appendChild(script);
                this.loadedAssets.add(jsKey);
            }
        });
    },

    // ── Load a plugin's admin view ──
    async loadPluginView(pluginId) {
        const viewId = `view-plugin-${pluginId}`;
        let viewEl = document.getElementById(viewId);

        // Create view container if it doesn't exist
        if (!viewEl) {
            viewEl = document.createElement('div');
            viewEl.id = viewId;
            viewEl.className = 'admin-content admin-view';
            document.getElementById('plugin-views-container').appendChild(viewEl);

            // Fetch admin-view.php content
            try {
                const response = await fetch(`/plugins/${pluginId}/admin-view.php?v=${Date.now()}`);
                if (response.ok) {
                    viewEl.innerHTML = await response.text();
                } else {
                    viewEl.innerHTML = '<div style="text-align:center;padding:4rem;"><p style="color:#8b90a6;">No se pudo cargar la vista del plugin.</p></div>';
                }
            } catch (err) {
                viewEl.innerHTML = '<div style="text-align:center;padding:4rem;"><p style="color:#dc2626;">Error al cargar el plugin.</p></div>';
            }
        }

        // Show this view, hide others
        document.querySelectorAll('.admin-view').forEach(v => v.classList.add('hidden'));
        viewEl.classList.remove('hidden');

        // Update header title
        const plugin = this.plugins.find(p => p.id === pluginId);
        const headerTitle = document.querySelector('.admin-header h1');
        if (headerTitle && plugin) headerTitle.textContent = plugin.sidebar_label || plugin.name;

        // Initialize plugin JS if available
        if (pluginId === 'portfolio' && typeof PortfolioAdmin !== 'undefined') {
            PortfolioAdmin.init();
        }
    },

    // ── Activate plugin ──
    async activate(pluginId, name) {
        if (!confirm(`¿Activar el plugin "${name}"?\nSe crearán las tablas necesarias en la base de datos.`)) return;

        const result = await api(`/api/plugins.php?action=activate&plugin=${encodeURIComponent(pluginId)}`, {
            method: 'POST',
            body: JSON.stringify({})
        });

        if (result && result.success) {
            Toast.success(result.message || 'Plugin activado.');
            await this.loadPlugins();
        } else {
            Toast.error(result?.error || 'Error al activar plugin.');
        }
    },

    // ── Deactivate plugin ──
    async deactivate(pluginId, name) {
        if (!confirm(`⚠️ ¿Desactivar el plugin "${name}"?\n\nEsta acción ELIMINARÁ todas las tablas y datos del plugin.\nEsta acción NO se puede deshacer.`)) return;

        const result = await api(`/api/plugins.php?action=deactivate&plugin=${encodeURIComponent(pluginId)}`, {
            method: 'POST',
            body: JSON.stringify({})
        });

        if (result && result.success) {
            Toast.success(result.message || 'Plugin desactivado.');
            
            // Remove plugin view
            const viewEl = document.getElementById(`view-plugin-${pluginId}`);
            if (viewEl) viewEl.remove();

            // Remove loaded assets
            const cssEl = document.getElementById(`plugin-css-${pluginId}`);
            const jsEl = document.getElementById(`plugin-js-${pluginId}`);
            if (cssEl) cssEl.remove();
            if (jsEl) jsEl.remove();
            this.loadedAssets.delete(`css-${pluginId}`);
            this.loadedAssets.delete(`js-${pluginId}`);

            await this.loadPlugins();
        } else {
            Toast.error(result?.error || 'Error al desactivar plugin.');
        }
    },

    // ── Upload ZIP ──
    async uploadZip(input) {
        const file = input.files[0];
        if (!file) return;

        if (!file.name.endsWith('.zip')) {
            Toast.error('Solo se permiten archivos ZIP.');
            input.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('plugin_zip', file);
        formData.append('csrf_token', AdminApp.csrfToken);

        try {
            const response = await fetch('/api/plugins.php?action=upload', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                Toast.success(result.message || 'Plugin subido.');
                await this.loadPlugins();
            } else {
                Toast.error(result.error || 'Error al subir plugin.');
            }
        } catch (err) {
            Toast.error('Error de conexión al subir plugin.');
        }

        input.value = '';
    },

    // ── Init upload zone drag & drop ──
    initUploadZone() {
        const zone = document.getElementById('plugin-upload-zone');
        if (!zone) return;

        zone.addEventListener('click', () => {
            document.getElementById('plugin-zip-input').click();
        });

        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.style.borderColor = '#6366f1';
            zone.style.background = 'rgba(99,102,241,0.04)';
        });

        zone.addEventListener('dragleave', () => {
            zone.style.borderColor = '';
            zone.style.background = '';
        });

        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.style.borderColor = '';
            zone.style.background = '';

            if (e.dataTransfer.files.length > 0) {
                const input = document.getElementById('plugin-zip-input');
                const dt = new DataTransfer();
                dt.items.add(e.dataTransfer.files[0]);
                input.files = dt.files;
                this.uploadZip(input);
            }
        });
    }
};

// ============================================
// INITIALIZE DASHBOARD PAGE
// ============================================
async function initDashboard() {
    const authenticated = await checkAuth();
    if (!authenticated) return;

    initSidebar();
    initLeadsToolbar();
    loadSavedLogos();
    initLogoDragDrop();
    initThemeConfigControls();
    
    // Init Plugin Manager
    PluginManager.initUploadZone();
    PluginManager.loadPlugins();
    
    // Load initial view
    loadDashboard();
}

// Auto-init based on page
document.addEventListener('DOMContentLoaded', () => {
    // Login page
    if (document.getElementById('login-form')) {
        initLogin();
    }
    
    // Dashboard page
    if (document.querySelector('.admin-layout')) {
        initDashboard();
    }
});
