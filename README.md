# 🚀 WebCreator Starter Kit

**Plantilla universal PHP/MySQL para sitios web con CRM integrado.**  
Diseñada para despliegue en **Hostinger** con instalación automática.

---

## 📁 Estructura del Proyecto

```
webcreator/
├── index.html                  # Sitio público (landing page)
├── .htaccess                   # Seguridad Apache + caché + headers
├── .gitignore                  # Archivos a ignorar en Git
│
├── config/
│   ├── .htaccess               # Bloquea acceso web a config
│   └── database.example.php    # Plantilla de configuración
│
├── install/
│   ├── index.php               # 🔧 Wizard de instalación
│   └── schema.sql              # Script SQL completo
│
├── api/
│   ├── init.php                # Bootstrap: PDO, CSRF, Auth, Helpers
│   ├── auth.php                # Login / Logout / Sesión
│   ├── leads.php               # CRUD completo de Leads
│   └── upload.php              # Subida segura de imágenes
│
├── admin/
│   ├── index.html              # Página de login
│   └── dashboard.html          # Panel CRM completo
│
├── assets/
│   ├── css/
│   │   ├── variables.css       # 🎨 Design tokens (colores, fuentes)
│   │   ├── base.css            # Reset + tipografía base
│   │   ├── components.css      # Componentes UI reutilizables
│   │   ├── public.css          # Estilos del sitio público
│   │   └── admin.css           # Estilos del panel admin
│   └── js/
│       ├── app.js              # JS del sitio público
│       └── admin.js            # JS del panel admin (CRM)
│
└── uploads/                    # Directorio de archivos subidos
    └── .htaccess               # Bloquea ejecución PHP en uploads
```

## 🚀 Cómo Instalar

1. **Sube la carpeta** completa a tu hosting en Hostinger (vía FTP o Administrador de Archivos)
2. **Visita** `https://tusitio.com/install/` en tu navegador
3. **Completa** el formulario con tus datos de MySQL de Hostinger
4. **Listo!** El sistema crea las tablas, el admin, y se auto-bloquea

## 🎨 Cómo Personalizar para un Nuevo Proyecto

Solo modifica estos archivos:

| Archivo | Qué cambiar |
|---------|-------------|
| `assets/css/variables.css` | Colores, fuentes, espaciados |
| `index.html` | Contenido del sitio público |
| `assets/css/public.css` | Estilos visuales del sitio |

**El backend (api/, admin/, config/) se mantiene idéntico.**

## 🔒 Seguridad Incluida

- ✅ PDO con Prepared Statements (anti SQL Injection)
- ✅ CSRF Token en todas las operaciones
- ✅ Password hashing con bcrypt (cost 12)
- ✅ Rate limiting por sesión
- ✅ Sanitización de inputs (anti XSS)
- ✅ Honeypot + timing anti-spam en formularios
- ✅ Directorio uploads bloquea ejecución PHP
- ✅ Headers de seguridad (X-Frame, X-XSS, etc.)
- ✅ Sesiones con timeout y regeneración de ID

## 📊 Base de Datos

| Tabla | Descripción |
|-------|-------------|
| `users` | Cuentas de administrador |
| `leads` | Contactos captados (CRM) |
| `media` | Archivos/imágenes subidas |
| `settings` | Configuración key-value |
| `activity_log` | Registro de auditoría |

## 🔌 API Endpoints

### Auth
- `GET  /api/auth.php?action=csrf` — Obtener token CSRF
- `POST /api/auth.php?action=login` — Iniciar sesión
- `POST /api/auth.php?action=logout` — Cerrar sesión
- `GET  /api/auth.php?action=me` — Usuario actual

### Leads
- `GET    /api/leads.php` — Listar leads (paginado)
- `GET    /api/leads.php?id=X` — Detalle de un lead
- `GET    /api/leads.php?action=stats` — Estadísticas
- `POST   /api/leads.php` — Crear lead (público)
- `PUT    /api/leads.php?id=X` — Actualizar lead
- `DELETE /api/leads.php?id=X` — Eliminar lead

### Upload
- `POST   /api/upload.php` — Subir archivo
- `GET    /api/upload.php` — Listar archivos
- `DELETE /api/upload.php?id=X` — Eliminar archivo
