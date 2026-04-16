# Changelog — WebCreator

Todas las versiones siguen [Semantic Versioning](https://semver.org/): `MAJOR.MINOR.PATCH`

- **MAJOR**: Cambios que rompen compatibilidad (restructuración de BD, cambios de API)
- **MINOR**: Funcionalidades nuevas retrocompatibles (plugins, nuevas vistas)
- **PATCH**: Correcciones de bugs y ajustes menores

---

## [1.0.0] — 2026-04-15

### 🎉 Release Inicial — Starter Kit Completo

#### Core
- **Instalador automático** (`/install/`) con wizard de 2 pasos
- **Base de datos** auto-generada: `users`, `leads`, `media`, `settings`, `activity_log`
- **Sistema de auto-migraciones** vía archivos `.sql` en `/migrations/`
- **Configuración dinámica** guardada en BD (no requiere editar archivos)
- **Detección automática de URL** del sitio (sin hardcodear dominio)

#### Backend / API
- `api/init.php` — Bootstrap: PDO singleton, CSRF, sesiones, rate limiting, helpers
- `api/auth.php` — Login/logout con bcrypt, timeout de sesión, regeneración de ID
- `api/leads.php` — CRUD completo de leads con estadísticas y paginación
- `api/settings.php` — Leer/escribir configuración del sitio (info, tema, logos)
- `api/upload.php` — Subida segura de imágenes con validación MIME
- `api/theme.css.php` — CSS dinámico generado desde la BD (colores, fuentes, bordes)
- `api/migrate.php` — Endpoint de migraciones manuales
- `api/plugins.php` — Motor de plugins: listar, activar, desactivar, upload ZIP

#### Panel Admin (`/admin/`)
- Dashboard con estadísticas de leads (hoy, semana, mes, total)
- Gestión de leads: tabla completa, filtros, búsqueda, edición inline
- Configuración del sitio: info general, logos (normal + negativo), drag & drop
- Configuración de tema: colores primario/secundario/acento, fuentes, border-radius
- Gestión de plugins: listado, activar/desactivar, subida ZIP, sidebar dinámico
- UI responsive con sidebar colapsable

#### Frontend Público
- Landing page responsive con secciones: Hero, Nosotros, Servicios, Stats, Contacto, Footer
- Formularios con honeypot + timing anti-spam
- Animaciones on-scroll
- Botón flotante de WhatsApp (configurable)
- CSS modular: `variables.css` → `base.css` → `components.css` → `public.css` → `theme.css.php`
- Integración con Google Fonts (Inter por defecto)

#### Sistema de Plugins
- **Motor de plugins** con discovery automático en `/plugins/`
- Activación ejecuta `install.sql`, desactivación ejecuta `uninstall.sql`
- Subida de plugins via ZIP con extracción automática
- Carga dinámica de CSS/JS del plugin en el admin
- Sidebar del admin se actualiza dinámicamente con plugins activos
- Protección `.htaccess` para archivos SQL internos

#### Plugin: Portafolio (incluido)
- CRUD de proyectos: título, slug, categoría, cliente, ubicación, año, m², descripción, tags
- Galería de imágenes con drag & drop, reordenamiento y featured
- Admin: grid de proyectos con filtros por categoría y buscador
- Frontend: `/portafolio` (grid + filtros), `/portafolio/proyecto?slug=X` (detalle + lightbox)
- Componente embebible para la página de inicio (auto-detectado)

#### Seguridad
- PDO con prepared statements (anti SQL injection)
- CSRF token en todas las operaciones write
- Bcrypt con cost 12 para passwords
- Rate limiting por sesión
- Honeypot + timing anti-spam en formularios
- Headers de seguridad: X-Frame-Options, X-XSS-Protection, X-Content-Type-Options
- Directorio `/uploads/` bloquea ejecución PHP
- Directorio `/config/` bloqueado vía .htaccess
- Instalador se auto-bloquea post-instalación

---

## Plantilla para versiones futuras

<!--
## [X.Y.Z] — YYYY-MM-DD

### Agregado
- Descripción de funcionalidades nuevas

### Cambiado
- Cambios en funcionalidad existente

### Corregido
- Bugs corregidos

### Eliminado
- Funcionalidades removidas

### Seguridad
- Correcciones de seguridad

### Migraciones
- Nuevas migraciones SQL requeridas (indicar si son automáticas)

### Breaking Changes
- Cambios que requieren acción manual al actualizar
-->
