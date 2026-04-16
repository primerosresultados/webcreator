# WebCreator — Contexto para Agentes AI

## Regla Principal: Configuración Primero, Código Después

Cuando el usuario pida cambios de **diseño, estilo, o información del sitio**, **NO modifiques directamente** archivos CSS, HTML o PHP.
En su lugar, **usa el panel de administración** (`/admin/dashboard.php` → Configuración) para aplicar los cambios a través de la API de configuración.

Solo modifica código fuente si:
1. La funcionalidad solicitada **no existe** en el sistema de configuración.
2. El usuario pide explícitamente cambiar código.
3. Se trata de un **bug fix** o nueva funcionalidad que no tiene equivalente configurable.

---

## Arquitectura del Sistema de Configuración

### API de Configuración
- **Endpoint**: `PUT /api/settings.php`
- **Autenticación**: Requiere sesión admin + CSRF token
- **Almacenamiento**: Tabla `settings` en MySQL (key-value con tipos: string, number, boolean, json)

### CSS Dinámico
- **Endpoint**: `GET /api/theme.css.php`
- El sitio público carga este archivo como stylesheet
- Genera CSS custom properties (`:root`) a partir de `theme_config` guardado en la BD
- Los cambios se reflejan inmediatamente al recargar la página pública

### Dos claves principales en settings:
1. **`theme_config`** (JSON) — Diseño visual del sitio
2. **`site_info`** (JSON) — Información de la empresa

---

## Configuraciones Disponibles (theme_config)

### Colores
| Clave | Descripción | Ejemplo |
|-------|-------------|---------|
| `colorPrimary` | Color principal del sitio | `#6366f1` |
| `colorPrimaryHover` | Hover del color principal | `#4f46e5` |
| `colorSecondary` | Color secundario/acento | `#c9a96e` |
| `colorAccent` | Color de acento adicional | `#06b6d4` |

### Botones
| Clave | Descripción | Ejemplo |
|-------|-------------|---------|
| `btnColor` | Color de fondo de botones | `#c9a96e` |
| `btnHoverColor` | Color hover de botones | `#b8944f` |
| `btnRadius` | Radio de bordes de botones | `8px` |

### Bordes
| Clave | Descripción | Ejemplo |
|-------|-------------|---------|
| `borderRadius` | Radio global de bordes (cards, inputs, etc.) | `12px` |

### Tipografía
| Clave | Descripción | Opciones disponibles |
|-------|-------------|---------------------|
| `fontHeadings` | Fuente para H1-H6 | Inter, Roboto, Outfit, Poppins, Montserrat, Playfair Display, DM Sans, Space Grotesk, Sora, Raleway |
| `fontMenu` | Fuente del menú de navegación | Inter, Roboto, Outfit, Poppins, Montserrat, DM Sans, Space Grotesk |
| `fontBody` | Fuente del texto general | Inter, Roboto, Open Sans, Lato, DM Sans, Nunito, Source Sans 3 |

### Estilos por Heading (H1-H6)
| Clave | Descripción | Ejemplo |
|-------|-------------|---------|
| `h1Size` - `h6Size` | Tamaño de fuente | `3rem`, `2rem`, `1.5rem` |
| `h1Weight` - `h6Weight` | Peso de fuente | `300`, `400`, `600`, `700`, `800` |
| `h1Color` - `h6Color` | Color del texto | `#ffffff`, `#1a1d2e` |

---

## Configuraciones Disponibles (site_info)

| Clave | Descripción |
|-------|-------------|
| `siteName` | Nombre del sitio/empresa |
| `siteDescription` | Slogan o descripción |
| `phone` | Teléfono de contacto |
| `email` | Email de contacto |
| `whatsapp` | Número WhatsApp (con código país) |
| `address` | Dirección física |
| `instagram` | URL de Instagram |
| `facebook` | URL de Facebook |
| `youtube` | URL de YouTube |
| `linkedin` | URL de LinkedIn |
| `twitter` | URL de X/Twitter |
| `pinterest` | URL de Pinterest |
| `tiktok` | URL de TikTok |

---

## Configuración de Página de Agradecimiento (thank_you_config)

| Clave | Descripción |
|-------|-------------|
| `title` | Título de la página |
| `message` | Mensaje de agradecimiento |
| `ctaText` | Texto del botón |
| `ctaUrl` | URL del botón |
| `youtubeUrl` | Video de YouTube (opcional) |
| `showSocial` | Mostrar redes sociales (boolean) |

---

## Logos

Se gestionan via upload en el panel de admin:
- **Logo Principal** (`logo_normal`): Para fondos claros
- **Logo Negativo** (`logo_negative`): Para fondos oscuros (header/footer)

---

## Cómo Aplicar Cambios de Diseño

### Mediante la interfaz web (preferido):
1. Ir a `/admin/dashboard.php`
2. Click en "Configuración" en el sidebar
3. Modificar los valores deseados
4. Guardar

### Mediante API directa (cuando no hay acceso al navegador):
```bash
# Ejemplo: cambiar color primario y fuente de títulos
curl -X POST /api/settings.php \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: <token>" \
  -d '{"theme_config": {"colorPrimary": "#10b981", "fontHeadings": "Montserrat"}}'
```

---

## Estructura de Archivos Clave

```
webcreator/
├── admin/
│   ├── dashboard.php          # Panel admin (todas las vistas)
│   └── index.php              # Login
├── api/
│   ├── init.php               # Bootstrap: DB, auth, CSRF, helpers
│   ├── auth.php               # Login/logout/session
│   ├── leads.php              # CRUD de leads/CRM
│   ├── settings.php           # Configuración del sitio
│   ├── theme.css.php          # CSS dinámico generado desde BD
│   ├── upload.php             # Upload de imágenes
│   └── plugins.php            # Gestión de plugins
├── assets/
│   ├── css/
│   │   ├── variables.css      # Variables CSS base (defaults)
│   │   ├── base.css           # Estilos base
│   │   ├── components.css     # Componentes UI
│   │   └── admin.css          # Estilos del panel admin
│   └── js/
│       ├── admin.v2.js        # JavaScript del panel admin
│       └── app.js             # JavaScript del sitio público
├── config/
│   └── database.php           # Credenciales BD (generado por installer)
├── plugins/                   # Sistema modular de plugins
├── index.php                  # Sitio público principal
└── gracias.php                # Página post-formulario
```

---

## Hosting

- **Plataforma**: Hostinger (shared hosting)
- **Despliegue**: Git push a `main` → auto-deploy
- **PHP**: El hosting no soporta PUT/DELETE HTTP nativo, se usa `_method` override via POST
- **Base de datos**: MySQL remota en Hostinger

---

## Notas Importantes

1. **`admin.js` y `admin.v2.js`** deben mantenerse sincronizados (copiar v2 → admin.js al hacer cambios)
2. El sistema de **migraciones automáticas** ejecuta archivos `.sql` de `/migrations/` al primer request de admin
3. Los **plugins** tienen su propia estructura con `manifest.json`, `install.sql`, y archivos admin/public
4. El CSS dinámico (`theme.css.php`) tiene **precedencia** sobre `variables.css` porque se carga después
5. Cambios en la configuración del admin se reflejan **inmediatamente** en el sitio público (no requiere deploy)
