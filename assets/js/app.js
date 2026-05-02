/**
 * ============================================
 * WEBCREATOR - PUBLIC SITE JAVASCRIPT
 * ============================================
 * Handles: Contact form, navigation, animations, toasts
 */

// ============================================
// TOAST NOTIFICATION SYSTEM
// ============================================
const Toast = {
    container: null,

    init() {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    },

    show(message, type = 'info', duration = 4000) {
        if (!this.container) this.init();

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
        
        toast.innerHTML = `
            <span style="font-size:1.1rem;">${icons[type] || 'ℹ'}</span>
            <span>${message}</span>
            <span class="toast-close" onclick="this.parentElement.classList.add('removing'); setTimeout(() => this.parentElement.remove(), 300)">✕</span>
        `;

        this.container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    success(msg) { this.show(msg, 'success'); },
    error(msg) { this.show(msg, 'error', 6000); },
    warning(msg) { this.show(msg, 'warning'); },
    info(msg) { this.show(msg, 'info'); }
};

// ============================================
// HEADER SCROLL EFFECT
// ============================================
function initHeader() {
    const header = document.querySelector('.site-header');
    if (!header) return;

    let lastScroll = 0;

    window.addEventListener('scroll', () => {
        const currentScroll = window.scrollY;
        
        if (currentScroll > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }

        lastScroll = currentScroll;
    }, { passive: true });

    // Overlay menu toggle (Foster + Partners style)
    const toggle = document.querySelector('.nav-toggle');
    const overlay = document.getElementById('overlay-menu');
    const closeBtn = document.getElementById('overlay-menu-close');

    if (toggle && overlay) {
        const openMenu = () => {
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            toggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('menu-open');
        };
        const closeMenu = () => {
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('menu-open');
        };

        toggle.addEventListener('click', () => {
            overlay.classList.contains('is-open') ? closeMenu() : openMenu();
        });
        if (closeBtn) closeBtn.addEventListener('click', closeMenu);
        overlay.querySelectorAll('.overlay-menu-list a').forEach(a => {
            a.addEventListener('click', closeMenu);
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeMenu();
        });
    }

    // Active link based on scroll
    const sections = document.querySelectorAll('section[id]');
    const navAnchors = document.querySelectorAll('.nav-links a[href^="#"]');

    if (sections.length > 0 && navAnchors.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    navAnchors.forEach(a => a.classList.remove('active'));
                    const active = document.querySelector(`.nav-links a[href="#${entry.target.id}"]`);
                    if (active) active.classList.add('active');
                }
            });
        }, { rootMargin: '-50% 0px', threshold: 0 });

        sections.forEach(section => observer.observe(section));
    }
}

// ============================================
// SCROLL ANIMATIONS (Intersection Observer)
// ============================================
function initScrollAnimations() {
    const animatedElements = document.querySelectorAll('[data-animate]');
    if (animatedElements.length === 0) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const animation = el.dataset.animate || 'fadeInUp';
                const delay = el.dataset.delay || '0';
                
                el.style.animationDelay = `${delay}ms`;
                el.classList.add('animated', animation);
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    animatedElements.forEach(el => {
        el.style.opacity = '0';
        observer.observe(el);
    });

    // Add animation CSS
    if (!document.getElementById('scroll-animations-css')) {
        const style = document.createElement('style');
        style.id = 'scroll-animations-css';
        style.textContent = `
            .animated { animation-duration: 0.6s; animation-fill-mode: forwards; }
            .fadeInUp { animation-name: fadeInUp; }
            .fadeIn { animation-name: fadeIn; }
            .slideInLeft { animation-name: slideInLeft; }
            .slideInRight { animation-name: slideInRight; }
            @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
            @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
            @keyframes slideInLeft { from { opacity: 0; transform: translateX(-30px); } to { opacity: 1; transform: translateX(0); } }
            @keyframes slideInRight { from { opacity: 0; transform: translateX(30px); } to { opacity: 1; transform: translateX(0); } }
        `;
        document.head.appendChild(style);
    }
}

// ============================================
// CONTACT FORM HANDLER
// ============================================
function initContactForm() {
    const form = document.getElementById('contact-form');
    if (!form) return;

    // Set form load time for anti-spam
    const timeField = form.querySelector('input[name="_form_time"]');
    if (timeField) {
        timeField.value = Math.floor(Date.now() / 1000);
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Clear previous errors
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

        // Collect data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Client-side validation
        const errors = validateContactForm(data);
        if (errors.length > 0) {
            errors.forEach(err => showFieldError(form, err.field, err.message));
            return;
        }

        // Submit
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.classList.add('btn-loading');
        submitBtn.disabled = true;

        try {
            const response = await fetch('/api/leads.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = '/gracias.php';
                return;
            } else {
                Toast.error(result.error || 'Error al enviar el formulario.');
            }
        } catch (error) {
            Toast.error('Error de conexión. Intente nuevamente.');
            console.error('Form submission error:', error);
        } finally {
            submitBtn.classList.remove('btn-loading');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

function validateContactForm(data) {
    const errors = [];

    if (!data.name || data.name.trim().length < 2) {
        errors.push({ field: 'name', message: 'El nombre debe tener al menos 2 caracteres.' });
    }

    if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
        errors.push({ field: 'email', message: 'Ingrese un email válido.' });
    }

    if (data.phone && !/^[\+]?[0-9\s\-\(\)]{7,20}$/.test(data.phone)) {
        errors.push({ field: 'phone', message: 'Formato de teléfono inválido.' });
    }

    if (data.message && data.message.length > 2000) {
        errors.push({ field: 'message', message: 'El mensaje no puede exceder 2000 caracteres.' });
    }

    return errors;
}

function showFieldError(form, fieldName, message) {
    const field = form.querySelector(`[name="${fieldName}"]`);
    if (!field) return;

    field.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

// ============================================
// HERO CONTACT FORM HANDLER
// ============================================
function initHeroForm() {
    const form = document.getElementById('hero-contact-form');
    if (!form) return;

    // Set form load time for anti-spam
    const timeField = form.querySelector('input[name="_form_time"]');
    if (timeField) {
        timeField.value = Math.floor(Date.now() / 1000);
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Clear previous errors
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

        // Collect data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Client-side validation
        const errors = validateContactForm(data);
        if (errors.length > 0) {
            errors.forEach(err => showFieldError(form, err.field, err.message));
            return;
        }

        // Submit
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.classList.add('btn-loading');
        submitBtn.disabled = true;

        try {
            const response = await fetch('/api/leads.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = '/gracias.php';
                return;
            } else {
                Toast.error(result.error || 'Error al enviar el formulario.');
            }
        } catch (error) {
            Toast.error('Error de conexión. Intente nuevamente.');
            console.error('Hero form submission error:', error);
        } finally {
            submitBtn.classList.remove('btn-loading');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
}

// ============================================
// SMOOTH SCROLL FOR ANCHOR LINKS
// ============================================
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', (e) => {
            e.preventDefault();
            const target = document.querySelector(anchor.getAttribute('href'));
            if (target) {
                const offset = 80; // Header height
                const top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });
}

// ============================================
// WHATSAPP FLOATING BUTTON WITH LEAD CAPTURE
// ============================================
function initWhatsApp() {
    // Load site info from public endpoint (no auth required)
    fetch('/api/settings.php?public=1')
        .then(r => r.json())
        .then(d => {
            if (!d.success || !d.site_info || !d.site_info.whatsapp) return;

            const waNumber = d.site_info.whatsapp.replace(/[^0-9]/g, '');
            const btn = document.getElementById('whatsapp-btn');
            if (!btn) return;

            btn.style.display = 'flex';

            // Create modal
            const modal = document.createElement('div');
            modal.id = 'wa-modal';
            modal.innerHTML = `
                <div class="wa-modal-backdrop" onclick="closeWaModal()"></div>
                <div class="wa-modal-card">
                    <button class="wa-modal-close" onclick="closeWaModal()">&times;</button>
                    <div class="wa-modal-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor" style="width:28px;height:28px;color:#25d366;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </div>
                    <h3 style="color:#1a1d2e;font-weight:700;font-size:1.1rem;margin-bottom:4px;">Escríbenos por WhatsApp</h3>
                    <p style="color:#6b7280;font-size:0.85rem;margin-bottom:1.2rem;">Déjanos tus datos y te contactamos al instante</p>
                    <form id="wa-lead-form">
                        <input type="text" id="wa-name" placeholder="Tu nombre" required style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;margin-bottom:10px;outline:none;transition:border 0.2s;" onfocus="this.style.borderColor='#25d366'" onblur="this.style.borderColor='#e5e7eb'">
                        <input type="tel" id="wa-phone" placeholder="Tu teléfono" required style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;margin-bottom:14px;outline:none;transition:border 0.2s;" onfocus="this.style.borderColor='#25d366'" onblur="this.style.borderColor='#e5e7eb'">
                        <button type="submit" style="width:100%;padding:12px;background:linear-gradient(135deg,#25d366,#128c7e);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                            <svg viewBox="0 0 24 24" fill="currentColor" style="width:18px;height:18px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            Iniciar conversación
                        </button>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);

            // Open modal on button click
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                modal.classList.add('active');
            });

            // Form submit: capture lead then open WhatsApp
            document.getElementById('wa-lead-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const name = document.getElementById('wa-name').value.trim();
                const phone = document.getElementById('wa-phone').value.trim();
                if (!name || !phone) return;

                // Save lead in background — generate placeholder email since backend requires it
                const phoneClean = phone.replace(/[^0-9]/g, '');
                const placeholderEmail = `whatsapp-${phoneClean}@lead.local`;

                try {
                    await fetch('/api/leads.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ name, phone, email: placeholderEmail, source: 'whatsapp-button', message: 'Contacto vía WhatsApp' })
                    });
                } catch(err) {}

                // Open WhatsApp
                const msg = encodeURIComponent(`Hola, soy ${name}. Me gustaría obtener más información.`);
                window.open(`https://wa.me/${waNumber}?text=${msg}`, '_blank');
                closeWaModal();
            });
        })
        .catch(() => {});
}

function closeWaModal() {
    const m = document.getElementById('wa-modal');
    if (m) m.classList.remove('active');
}

// ============================================
// STAT COUNTER ANIMATION
// ============================================
function initCounters() {
    const counters = document.querySelectorAll('.stat-number[data-count]');
    if (counters.length === 0) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.dataset.count, 10);
                const duration = 2000;
                const start = performance.now();

                function animate(now) {
                    const elapsed = now - start;
                    const progress = Math.min(elapsed / duration, 1);
                    // Ease out cubic
                    const eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = Math.floor(target * eased);
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        el.textContent = target;
                    }
                }
                requestAnimationFrame(animate);
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.3 });

    counters.forEach(c => observer.observe(c));
}

// ============================================
// HERO VIDEO LOOP — rotate through videos in sequence
// ============================================
function initHeroVideos() {
    const wrap = document.getElementById('hero-videos');
    if (!wrap) return;
    const videos = Array.from(wrap.querySelectorAll('video'));
    if (videos.length === 0) return;

    videos.forEach(v => { v.muted = true; v.setAttribute('playsinline',''); });

    if (videos.length === 1) {
        videos[0].loop = true;
        const p = videos[0].play();
        if (p && p.catch) p.catch(() => {});
        return;
    }

    let idx = 0;
    const playAt = (i) => {
        videos.forEach((v, k) => {
            if (k === i) {
                v.classList.add('is-active');
                try { v.currentTime = 0; } catch (e) {}
                const p = v.play();
                if (p && p.catch) p.catch(() => {});
            } else {
                v.classList.remove('is-active');
                v.pause();
            }
        });
    };

    videos.forEach((v, i) => {
        v.addEventListener('ended', () => {
            idx = (i + 1) % videos.length;
            playAt(idx);
        });
    });

    playAt(0);
}

// ============================================
// PAGE TRANSITIONS (subtle app-like fade)
// ============================================
function initPageTransitions() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link) return;
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        const href = link.getAttribute('href');
        if (!href) return;
        if (link.target && link.target !== '_self') return;
        if (link.hasAttribute('download')) return;
        if (/^(mailto:|tel:|javascript:|#)/i.test(href)) return;

        let url;
        try { url = new URL(link.href, window.location.href); } catch { return; }
        if (url.origin !== window.location.origin) return;
        // Same-page hash navigation → skip
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;

        e.preventDefault();
        document.body.classList.add('is-leaving');
        setTimeout(() => { window.location.href = link.href; }, 240);
    });

    // Restore state when returning via back/forward cache
    window.addEventListener('pageshow', (e) => {
        if (e.persisted) document.body.classList.remove('is-leaving');
    });
}

// ============================================
// INITIALIZE
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    initHeader();
    initScrollAnimations();
    initCounters();
    initContactForm();
    initHeroForm();
    initHeroVideos();
    initSmoothScroll();
    initWhatsApp();
    initPageTransitions();
});
