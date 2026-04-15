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
            window.location.href = '/admin/dashboard.html';
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
    const result = await api('/api/auth.php?action=me');
    
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
    const result = await api('/api/leads.php?action=stats');
    if (!result || !result.success) return;

    const stats = result.stats;

    // Update stat cards
    setValue('#stat-total', stats.total || 0);
    setValue('#stat-new', stats.by_status?.new || 0);
    setValue('#stat-contacted', stats.by_status?.contacted || 0);
    setValue('#stat-converted', stats.by_status?.converted || 0);
    setValue('#stat-today', stats.today || 0);
    setValue('#stat-week', stats.this_week || 0);

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
    const { search, status, page } = AdminApp.currentFilters;
    let url = `/api/leads.php?page=${page}&limit=25`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (status) url += `&status=${encodeURIComponent(status)}`;

    const result = await api(url);
    if (!result || !result.success) return;

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
                        <div class="icon">📋</div>
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
            <td><span class="badge badge-${lead.status}">${getStatusLabel(lead.status)}</span></td>
            <td>${escapeHtml(lead.source || 'web')}</td>
            <td>${formatDate(lead.created_at)}</td>
            <td>
                <div class="flex gap-2">
                    <button class="btn btn-ghost btn-icon" onclick="viewLead(${lead.id})" title="Ver detalle">👁️</button>
                    <button class="btn btn-ghost btn-icon" onclick="cycleLead(${lead.id})" title="Cambiar estado">🔄</button>
                    <button class="btn btn-ghost btn-icon" onclick="deleteLead(${lead.id})" title="Eliminar">🗑️</button>
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
        <div class="lead-detail">
            <div class="detail-row">
                <span class="detail-label">Nombre</span>
                <span class="detail-value">${escapeHtml(lead.name)}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email</span>
                <span class="detail-value"><a href="mailto:${escapeHtml(lead.email)}">${escapeHtml(lead.email)}</a></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Teléfono</span>
                <span class="detail-value">${lead.phone ? `<a href="tel:${escapeHtml(lead.phone)}">${escapeHtml(lead.phone)}</a>` : '—'}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Estado</span>
                <span class="detail-value"><span class="badge badge-${lead.status}">${getStatusLabel(lead.status)}</span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Fuente</span>
                <span class="detail-value">${escapeHtml(lead.source || 'website')}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha</span>
                <span class="detail-value">${formatDate(lead.created_at, true)}</span>
            </div>
            ${lead.message ? `
                <div class="detail-row detail-message">
                    <span class="detail-label">Mensaje</span>
                    <p>${escapeHtml(lead.message)}</p>
                </div>
            ` : ''}
            ${lead.notes ? `
                <div class="detail-row detail-message">
                    <span class="detail-label">Notas internas</span>
                    <p>${escapeHtml(lead.notes)}</p>
                </div>
            ` : ''}
            <div class="detail-row">
                <span class="detail-label">IP</span>
                <span class="detail-value" style="font-family:var(--font-mono);font-size:var(--text-xs);">${escapeHtml(lead.ip_address || '—')}</span>
            </div>
        </div>
    `;

    document.getElementById('lead-modal-title').textContent = `Lead #${lead.id}`;
    openModal('lead-modal');
}

const statusCycle = ['new', 'contacted', 'qualified', 'converted', 'lost'];

async function cycleLead(id) {
    const lead = AdminApp.leads.find(l => l.id == id);
    if (!lead) return;

    const currentIdx = statusCycle.indexOf(lead.status);
    const nextStatus = statusCycle[(currentIdx + 1) % statusCycle.length];

    const result = await api(`/api/leads.php?id=${id}`, {
        method: 'PUT',
        body: JSON.stringify({ status: nextStatus })
    });

    if (result && result.success) {
        Toast.success(`Estado actualizado a: ${getStatusLabel(nextStatus)}`);
        loadLeads(); // Refresh
    } else {
        Toast.error(result?.error || 'Error al actualizar.');
    }
}

async function deleteLead(id) {
    if (!confirm('¿Estás seguro de eliminar este lead? Esta acción no se puede deshacer.')) return;

    const result = await api(`/api/leads.php?id=${id}`, {
        method: 'DELETE'
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
    const titles = { dashboard: 'Dashboard', leads: 'Gestión de Leads' };
    const headerTitle = document.querySelector('.admin-header h1');
    if (headerTitle) headerTitle.textContent = titles[viewName] || viewName;

    AdminApp.currentView = viewName;

    // Load data for the view
    if (viewName === 'dashboard') loadDashboard();
    if (viewName === 'leads') loadLeads();
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
// INITIALIZE DASHBOARD PAGE
// ============================================
async function initDashboard() {
    const authenticated = await checkAuth();
    if (!authenticated) return;

    initSidebar();
    initLeadsToolbar();
    
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
