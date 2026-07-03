/**
 * ============================================
 * WEBCREATOR - PUBLIC SITE JAVASCRIPT
 * ============================================
 * Handles: Contact form, navigation, animations, toasts
 */

// ============================================
// PAGE LOADER — animar logo del centro al header
// ============================================
(function () {
    const loaderEl = document.getElementById('page-loader');
    const loaderLogo = document.getElementById('page-loader-logo');
    if (!loaderLogo || !loaderEl) return;

    // Gate: solo mostrar la primera vez por sesión.
    // Si ya se mostró, ocultamos sin animar y dejamos el sitio listo.
    let alreadyShown = false;
    try { alreadyShown = sessionStorage.getItem('loaderShown') === '1'; } catch (e) {}

    if (alreadyShown) {
        // Skip animación: ocultar loader inmediatamente, header-logo visible
        loaderEl.style.transition = 'none';
        loaderEl.style.opacity = '0';
        loaderEl.style.visibility = 'hidden';
        loaderEl.style.pointerEvents = 'none';
        document.body.classList.add('is-loaded');
        document.body.classList.remove('is-loading');
        return;
    }

    const MIN_DISPLAY = 2000;
    const start = performance.now();
    try { sessionStorage.setItem('loaderShown', '1'); } catch (e) {}

    // Devuelve el logo del header que está REALMENTE visible (no display:none).
    function getVisibleHeaderLogo() {
        const candidates = document.querySelectorAll('.site-header .nav-brand img, .site-header .nav-brand svg');
        for (const el of candidates) {
            const cs = getComputedStyle(el);
            if (cs.display !== 'none' && cs.visibility !== 'hidden') {
                const r = el.getBoundingClientRect();
                if (r.width > 0 && r.height > 0) return el;
            }
        }
        return null;
    }

    function finalize() {
        // Quitamos is-loading rápido (200ms) para que la transición del header-logo
        // (que se dispara con is-loaded) corra sin que el !important / opacity:0
        // de is-loading la bloquee. El overflow:hidden ya no hace falta porque
        // el bg negro sigue tapando todo via #page-loader.
        setTimeout(() => document.body.classList.remove('is-loading'), 200);
    }

    function runExitAnimation() {
        // Esperar a que la imagen del loader esté decoded para que su bounding-rect sea exacto
        const ready = loaderLogo.complete ? Promise.resolve() : new Promise(r => loaderLogo.addEventListener('load', r, { once: true }));
        ready.then(() => {
            const headerLogo = getVisibleHeaderLogo();
            if (!headerLogo) {
                document.body.classList.add('is-loaded');
                finalize();
                return;
            }

            const fromRect = loaderLogo.getBoundingClientRect();
            const toRect = headerLogo.getBoundingClientRect();
            if (fromRect.width === 0 || toRect.width === 0) {
                document.body.classList.add('is-loaded');
                finalize();
                return;
            }

            // FLIP con transform-origin: top left → translate al top-left del destino
            // y scale por la razón de anchos. Así la caja final coincide exactamente.
            const tx = toRect.left - fromRect.left;
            const ty = toRect.top - fromRect.top;
            const scale = toRect.width / fromRect.width;

            loaderLogo.style.setProperty('--loader-tx', tx.toFixed(2) + 'px');
            loaderLogo.style.setProperty('--loader-ty', ty.toFixed(2) + 'px');
            loaderLogo.style.setProperty('--loader-scale', scale.toFixed(4));

            // Forzar reflow para que el navegador registre las custom props antes del transition
            // (sin esto a veces el primer frame "salta")
            void loaderLogo.offsetWidth;

            requestAnimationFrame(() => {
                document.body.classList.add('is-loaded');
                finalize();
            });
        });
    }

    function tryStart() {
        const elapsed = performance.now() - start;
        const wait = Math.max(0, MIN_DISPLAY - elapsed);
        setTimeout(runExitAnimation, wait);
    }

    if (document.readyState === 'complete') {
        tryStart();
    } else {
        window.addEventListener('load', tryStart, { once: true });
    }

    // Failsafe: nunca dejar el loader colgado
    setTimeout(() => {
        if (!document.body.classList.contains('is-loaded')) {
            document.body.classList.add('is-loaded');
            document.body.classList.remove('is-loading');
        }
    }, 6000);
})();

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
        const menuVideo = overlay.querySelector('.overlay-menu-image--video video');
        const openMenu = () => {
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            toggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('menu-open');
            if (menuVideo) {
                try { menuVideo.currentTime = 0; menuVideo.play().catch(() => {}); } catch (e) {}
            }
        };
        const closeMenu = () => {
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('menu-open');
            if (menuVideo) { try { menuVideo.pause(); } catch (e) {} }
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
            // El href puede cambiar después de cargar (botones dinámicos):
            // solo interceptar si sigue siendo un anchor interno con destino real.
            const href = anchor.getAttribute('href');
            if (!href || !href.startsWith('#') || href === '#') return;
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                const offset = 80; // Header height
                const top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });
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

    const hero = document.getElementById('inicio');
    const bullets = hero ? Array.from(hero.querySelectorAll('.hero-bullet')) : [];

    let idx = 0;

    const updateBulletDuration = (i) => {
        const v = videos[i];
        const b = bullets[i];
        if (!b) return;
        const dur = isFinite(v.duration) && v.duration > 0 ? v.duration : 6;
        // restart fill animation by toggling
        b.style.setProperty('--hero-bullet-duration', dur + 's');
        const fill = b.querySelector('.hero-bullet-fill');
        if (fill) {
            fill.style.animation = 'none';
            // force reflow
            void fill.offsetWidth;
            fill.style.animation = '';
        }
    };

    const playAt = (i) => {
        idx = (i + videos.length) % videos.length;
        videos.forEach((v, k) => {
            if (k === idx) {
                v.classList.add('is-active');
                try { v.currentTime = 0; } catch (e) {}
                const p = v.play();
                if (p && p.catch) p.catch(() => {});
            } else {
                v.classList.remove('is-active');
                v.pause();
            }
        });
        bullets.forEach((b, k) => {
            const active = k === idx;
            b.classList.toggle('is-active', active);
            b.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        // wait for metadata to know duration
        const cur = videos[idx];
        if (isFinite(cur.duration) && cur.duration > 0) {
            updateBulletDuration(idx);
        } else {
            cur.addEventListener('loadedmetadata', () => updateBulletDuration(idx), { once: true });
        }
    };

    videos.forEach((v, i) => {
        v.addEventListener('ended', () => playAt(i + 1));
    });

    if (hero) {
        const prev = hero.querySelector('[data-hero-prev]');
        const next = hero.querySelector('[data-hero-next]');
        if (prev) prev.addEventListener('click', () => playAt(idx - 1));
        if (next) next.addEventListener('click', () => playAt(idx + 1));
        bullets.forEach((b, i) => b.addEventListener('click', () => playAt(i)));

        // Keyboard arrows when hero is in viewport
        document.addEventListener('keydown', (e) => {
            if (document.body.classList.contains('menu-open')) return;
            if (window.scrollY > window.innerHeight) return;
            if (e.key === 'ArrowLeft') playAt(idx - 1);
            else if (e.key === 'ArrowRight') playAt(idx + 1);
        });
    }

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
    initPageTransitions();
});
