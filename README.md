# WebCreator Starter Kit

**v1.0.0** — Plantilla universal PHP/MySQL para sitios web profesionales con CRM y sistema de plugins.  
Diseñada para despliegue rápido en **Hostinger** con instalación automática.

---

## Características

| Módulo | Funcionalidades |
|--------|----------------|
| **Instalador** | Wizard automático, crea BD + tablas + admin en un paso |
| **CRM (Leads)** | CRUD completo, estadísticas, filtros, notas, estados |
| **Admin Panel** | Dashboard, configuración de sitio, tema visual, logos |
| **Plugins** | Motor modular, activar/desactivar, upload ZIP, sidebar dinámico |
| **Frontend** | Landing responsive, formularios, animaciones, WhatsApp |
| **Seguridad** | CSRF, bcrypt, rate limiting, honeypot, headers, XSS/SQLi protection |
| **Tema Dinámico** | Colores, fuentes y bordes editables desde el panel sin tocar código |

---

## Estructura del Proyecto

```
webcreator/
├── index.php                   # Landing page pública (dinámica)
├── gracias.php                 # Página de agradecimiento
├── .htaccess                   # Seguridad, caché, rewrite rules
├── VERSION                     # Versión actual del sistema
├── CHANGELOG.md                # Historial de versiones
│
├── config/
│   ├── .htaccess               # Bloquea acceso directo
│   └── database.example.php    # Plantilla de configuración
│
├── install/
│   ├── index.php               # Wizard de instalación
│   └── schema.sql              # Schema completo de la BD
│
├── api/
│   ├── init.php                # Bootstrap: PDO, CSRF, Auth, Helpers
│   ├── auth.php                # Login / Logout / Sesión
│   ├── leads.php               # CRUD de Leads
│   ├── settings.php            # Configuración del sitio
│   ├── upload.php              # Subida de archivos
│   ├── theme.css.php           # CSS dinámico desde BD
│   ├── migrate.php             # Migraciones manuales
│   └── plugins.php             # Motor de plugins
│
├── admin/
│   ├── index.php               # Página de login
│   └── dashboard.php           # Panel admin completo
│
├── assets/
│   ├── css/
│   │   ├── variables.css       # Design tokens (colores, fuentes)
│   │   ├── base.css            # Reset + tipografía
│   │   ├── components.css      # Botones, cards, modales
│   │   ├── public.css          # Estilos del sitio público
│   │   └── admin.css           # Estilos del panel admin
│   ├── js/
│   │   ├── app.js              # JS del sitio público
│   │   └── admin.js            # JS del panel admin + PluginManager
│   └── img/                    # Imágenes del template
│
├── plugins/
│   ├── .htaccess               # Protege archivos SQL internos
│   └── portfolio/              # Plugin incluido: Portafolio
│       ├── plugin.json         # Metadata del plugin
│       ├── install.sql         # Tablas (se ejecuta al activar)
│       ├── uninstall.sql       # DROP tables (al desactivar)
│       ├── api.php             # API del plugin
│       ├── admin-view.php      # Vista admin
│       ├── admin.js            # JS admin del plugin
│       ├── admin.css           # Estilos admin del plugin
│       ├── public-page.php     # Página /portafolio
│       ├── public-detail.php   # Detalle /portafolio/proyecto
│       ├── public-component.php # Componente embebible
│       └── public.css          # Estilos públicos
│
├── migrations/                 # SQL auto-ejecutables
│   ├── 001_create_settings.sql
│   └── _ejemplo_products.sql   # Ignorado (prefijo _)
│
└── uploads/                    # Archivos subidos (excluido de git)
    └── .htaccess               # Bloquea ejecución PHP
```

---

## Instalación Rápida

1. **Duplicar** la carpeta para cada proyecto nuevo
2. **Subir** a Hostinger (FTP, File Manager, o Git Deploy)
3. **Visitar** `https://tusitio.com/install/`
4. **Completar** el wizard (BD + admin) → ¡Listo!

> Guía detallada en [`COMO_INSTALAR.md`](COMO_INSTALAR.md)

---

## Base de Datos

### Tablas Core (schema.sql)

| Tabla | Descripción |
|-------|-------------|
| `users` | Cuentas admin (superadmin, admin, editor) |
| `leads` | Contactos captados del formulario |
| `media` | Archivos subidos |
| `settings` | Configuración del sitio (key-value + JSON) |
| `activity_log` | Registro de auditoría |
| `migrations` | Control de migraciones ejecutadas |

### Tablas de Plugins (se crean/eliminan al activar/desactivar)

| Plugin | Tablas |
|--------|--------|
| Portfolio | `portfolio_projects`, `portfolio_images` |

---

## API Endpoints

### Auth
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/auth.php?action=csrf` | Obtener token CSRF |
| `POST` | `/api/auth.php?action=login` | Iniciar sesión |
| `POST` | `/api/auth.php?action=logout` | Cerrar sesión |
| `GET` | `/api/auth.php?action=me` | Usuario actual |

### Leads
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/leads.php` | Listar leads (paginado) |
| `GET` | `/api/leads.php?id=X` | Detalle de un lead |
| `GET` | `/api/leads.php?action=stats` | Estadísticas |
| `POST` | `/api/leads.php` | Crear lead (público) |
| `PUT` | `/api/leads.php?id=X` | Actualizar lead |
| `DELETE` | `/api/leads.php?id=X` | Eliminar lead |

### Settings
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/settings.php?action=site_info` | Info del sitio |
| `POST` | `/api/settings.php?action=save_site_info` | Guardar info |
| `GET` | `/api/settings.php?action=theme_config` | Config del tema |
| `POST` | `/api/settings.php?action=save_theme_config` | Guardar tema |

### Upload
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/upload.php` | Subir archivo |
| `GET` | `/api/upload.php` | Listar archivos |
| `DELETE` | `/api/upload.php?id=X` | Eliminar archivo |

### Plugins
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/plugins.php?action=list` | Listar plugins |
| `POST` | `/api/plugins.php?action=activate&plugin=ID` | Activar plugin |
| `POST` | `/api/plugins.php?action=deactivate&plugin=ID` | Desactivar plugin |
| `POST` | `/api/plugins.php?action=upload` | Subir ZIP de plugin |

### Plugin: Portfolio
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/plugins.php?action=api&plugin=portfolio&route=projects` | Listar proyectos |
| `POST` | `/api/plugins.php?action=api&plugin=portfolio&route=projects` | Crear proyecto |
| `PUT` | `/api/plugins.php?action=api&plugin=portfolio&route=projects&id=X` | Editar proyecto |
| `DELETE` | `/api/plugins.php?action=api&plugin=portfolio&route=projects&id=X` | Eliminar proyecto |
| `POST` | `/api/plugins.php?action=api&plugin=portfolio&route=images` | Subir imagen |

---

## Sistema de Plugins

### Crear un plugin nuevo

1. Crear carpeta en `/plugins/mi-plugin/`
2. Crear `plugin.json`:

```json
{
    "id": "mi-plugin",
    "name": "Mi Plugin",
    "version": "1.0.0",
    "description": "Descripción corta",
    "author": "Tu nombre",
    "icon": "box",
    "sidebar_label": "Mi Plugin",
    "has_admin": true,
    "has_public": true
}
```

3. Crear `install.sql` (tablas a crear) y `uninstall.sql` (DROP)
4. Crear `api.php`, `admin-view.php`, `admin.js`, `admin.css`
5. Opcional: `public-page.php`, `public.css`, `public-component.php`

### Iconos disponibles para plugins
`briefcase`, `box`, `layers`, `image`

### Ciclo de vida
- **Descubierto**: La carpeta existe en `/plugins/` con `plugin.json`
- **Activado**: Se ejecuta `install.sql`, se registra en settings, aparece en sidebar
- **Desactivado**: Se ejecuta `uninstall.sql` (⚠️ ELIMINA datos), se quita del sidebar

---

## Migraciones

Los archivos `.sql` en `/migrations/` se ejecutan **automáticamente** al login de admin.

| Regla | Detalle |
|-------|---------|
| Prefijo `_` | Se ignora (para templates) |
| Orden | Alfabético por nombre |
| Idempotencia | Usar `IF NOT EXISTS` / `ON DUPLICATE KEY` |
| Control | Tabla `migrations` registra archivos ejecutados |
| Trigger | Automático al login, o manual vía `/api/migrate.php` |

---

## Personalización por Proyecto

### Qué modificar

| Archivo | Qué cambiar |
|---------|-------------|
| `index.php` | Textos, secciones, servicios, imágenes |
| `assets/img/` | Imágenes del hero, servicios |
| Panel Admin → Config | Colores, fuentes, logos, info de contacto |

### Qué NO tocar

| Directorio | Razón |
|-----------|-------|
| `/api/` | Backend universal |
| `/admin/` | Panel universal |
| `/install/` | Instalador universal |
| `/config/` | Se genera automáticamente |

---

## Seguridad

- ✅ PDO con Prepared Statements (anti SQL Injection)
- ✅ CSRF Token en todas las operaciones write
- ✅ Password hashing con bcrypt (cost 12)
- ✅ Rate limiting por sesión (max 30 intentos / 15 min)
- ✅ Honeypot + timing anti-spam en formularios
- ✅ Headers: X-Frame-Options, X-XSS-Protection, X-Content-Type-Options
- ✅ `/uploads/` bloquea ejecución PHP
- ✅ `/config/` bloqueado vía .htaccess
- ✅ Instalador se auto-bloquea post-instalación
- ✅ Sesiones con timeout y regeneración de ID

---

## Flujo para Nuevo Cliente

```
1. Duplicar carpeta webcreator/ → nombre-cliente/
2. Crear repo en GitHub para ese cliente
3. Editar index.php (contenido específico)
4. Reemplazar imágenes en assets/img/
5. Crear BD en Hostinger
6. Subir archivos a hosting
7. Visitar /install/ → configurar BD + admin
8. Desde el panel: configurar colores, logos, info
9. Activar plugins necesarios (ej: Portfolio)
10. Entregar sitio al cliente
```

---

## Requisitos del Servidor

| Requisito | Mínimo |
|-----------|--------|
| PHP | 7.4+ |
| MySQL | 5.7+ / MariaDB 10.3+ |
| Extensiones PHP | PDO MySQL, JSON, mbstring |
| Apache | mod_rewrite habilitado |
| Disco | ~15 MB (sin uploads) |

---

## Versionado

- Versión actual: ver archivo `VERSION`
- Historial completo: ver `CHANGELOG.md`
- Cada proyecto puede divergir del starter kit; actualizar `VERSION` y `CHANGELOG.md` al implementar cambios significativos

> Guía de desarrollo detallada: [`GUIA_DESARROLLO.md`](GUIA_DESARROLLO.md)
