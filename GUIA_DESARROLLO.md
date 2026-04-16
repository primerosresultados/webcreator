# Guía de Desarrollo — WebCreator v1.0.0

> **Esta guía es para desarrolladores** que extienden o personalizan el sistema.
> Para instalación, ver [`COMO_INSTALAR.md`](COMO_INSTALAR.md).
> Para referencia general, ver [`README.md`](README.md).

---

## Arquitectura General

```
┌─────────────────────────────────────────────────────┐
│                   FRONTEND PÚBLICO                   │
│  index.php → CSS (variables → base → components →   │
│  public → theme.css.php) + app.js                   │
└──────────────────────┬──────────────────────────────┘
                       │ fetch()
┌──────────────────────▼──────────────────────────────┐
│                      API LAYER                       │
│  api/init.php (bootstrap) → auth │ leads │ settings │
│  │ upload │ plugins │ migrate │ theme.css            │
└──────────────────────┬──────────────────────────────┘
                       │ PDO
┌──────────────────────▼──────────────────────────────┐
│                    MySQL / MariaDB                    │
│  users │ leads │ media │ settings │ activity_log     │
│  + tablas de plugins activos                         │
└─────────────────────────────────────────────────────┘
```

---

## Reglas de CSS — Tema Dinámico

### Orden de carga obligatorio

```html
<link rel="stylesheet" href="/assets/css/variables.css">  <!-- 1. Tokens -->
<link rel="stylesheet" href="/assets/css/base.css">        <!-- 2. Reset -->
<link rel="stylesheet" href="/assets/css/components.css">  <!-- 3. Componentes -->
<link rel="stylesheet" href="/assets/css/public.css">      <!-- 4. Sitio público -->
<link rel="stylesheet" href="/api/theme.css.php">          <!-- 5. ⚡ Tema dinámico -->
```

> ⚠️ Sin `theme.css.php` la página NO heredará los colores/fuentes del admin.

### Variables CSS disponibles

#### Colores
| Variable | Uso |
|----------|-----|
| `--color-primary` | Color principal (botones, links, CTAs) |
| `--color-primary-hover` | Hover del primario |
| `--color-secondary` | Color secundario/acento |
| `--color-accent` | Color terciario |
| `rgba(--color-primary-rgb, 0.1)` | Primario con transparencia |
| `rgba(--color-secondary-rgb, 0.1)` | Secundario con transparencia |

#### Tipografía
| Variable | Uso |
|----------|-----|
| `--font-primary` | Fuente del body |
| `--font-headings` | Fuente de H1-H6 (si se configura) |
| `--font-menu` | Fuente del menú (si se configura) |

#### Bordes
| Variable | Uso |
|----------|-----|
| `--radius-sm` | ~4px |
| `--radius-md` | Bordes medianos |
| `--radius-lg` | Valor base del admin |
| `--radius-xl` | Grandes |
| `--radius-2xl` | Muy redondeados |
| `--radius-full` | Píldora (9999px) |

### Regla de oro

```css
/* ❌ MAL — hardcoded */
.mi-boton { background: #c9a96e; }

/* ✅ BIEN — dinámico */
.mi-boton { background: var(--color-secondary); }
```

---

## Crear Páginas Nuevas

### Checklist

1. ✅ Incluir los 5 CSS en orden correcto
2. ✅ Usar variables CSS, nunca colores hardcoded
3. ✅ Copiar header y footer de `index.php`
4. ✅ Incluir `app.js` antes del `</body>`
5. ✅ Usar clases reutilizables (`.container`, `.btn`, `.card`, etc.)
6. ✅ Formularios con honeypot + `_form_time` + `source`

### Clases CSS reutilizables

| Clase | Qué hace |
|-------|----------|
| `.container` | Centra el contenido (max 1280px) |
| `.btn .btn-primary` | Botón principal |
| `.btn .btn-accent` | Botón secundario |
| `.btn-outline-light` | Botón borde blanco (fondos oscuros) |
| `.btn-lg` / `.btn-sm` | Tamaños |
| `.btn-block` | 100% ancho |
| `.card` | Tarjeta con borde y sombra |
| `.form-input` | Input estilizado |
| `.form-textarea` | Textarea estilizado |
| `.form-label` | Label de formulario |
| `.form-group` | Wrapper (margin-bottom) |
| `.form-row` | Grid 2 columnas |
| `.section` | Padding vertical estándar |

---

## Formularios de Contacto

Todo formulario que capture leads debe incluir:

```html
<form id="mi-formulario">
    <!-- Anti-spam: honeypot (oculto) -->
    <div style="position:absolute;left:-9999px;" aria-hidden="true">
        <input type="text" name="website_url" tabindex="-1" autocomplete="off">
    </div>
    <!-- Anti-spam: tiempo de carga -->
    <input type="hidden" name="_form_time" value="">
    <!-- Trazabilidad -->
    <input type="hidden" name="source" value="pagina-nueva">

    <!-- Campos visibles -->
    <input type="text" name="name" class="form-input" required>
    <input type="email" name="email" class="form-input" required>
    <textarea name="message" class="form-textarea"></textarea>
    <button type="submit" class="btn btn-primary btn-block">Enviar</button>
</form>
```

El `app.js` intercepta automáticamente el submit y envía a `/api/leads.php`.

---

## Sistema de Migraciones

### Cómo agregar tablas

1. Crear archivo en `/migrations/`:

```
migrations/
├── 001_create_settings.sql     ← Ya ejecutada
├── 002_create_products.sql     ← Nueva (se ejecutará sola)
└── _ejemplo_products.sql       ← Ignorada (prefijo _)
```

2. Usar `CREATE TABLE IF NOT EXISTS`:

```sql
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

3. Deploy → se ejecuta automáticamente al login de admin.

### Reglas
- Archivos con prefijo `_` son **ignorados**
- Orden: alfabético
- Tabla `migrations` registra cuáles ya se ejecutaron
- Trigger: automático al login, o manual vía `/api/migrate.php`

---

## Sistema de Plugins

### Estructura de un plugin

```
plugins/mi-plugin/
├── plugin.json             # Metadata (requerido)
├── install.sql             # Tablas a crear (requerido)
├── uninstall.sql           # DROP tables (requerido)
├── api.php                 # API del plugin (requerido si has_admin)
├── admin-view.php          # HTML del panel admin
├── admin.js                # JS del admin
├── admin.css               # Estilos del admin
├── public-page.php         # Página pública (opcional)
├── public-detail.php       # Detalle público (opcional)
├── public-component.php    # Componente embebible (opcional)
└── public.css              # Estilos públicos (opcional)
```

### plugin.json

```json
{
    "id": "mi-plugin",
    "name": "Mi Plugin",
    "version": "1.0.0",
    "description": "Descripción corta del plugin",
    "author": "Tu nombre",
    "icon": "box",
    "sidebar_label": "Mi Plugin",
    "has_admin": true,
    "has_public": true
}
```

### Ciclo de vida

1. **Discovery**: Carpeta existe en `/plugins/` con `plugin.json` válido
2. **Activación**: `install.sql` se ejecuta → tablas creadas → aparece en sidebar
3. **Uso**: Admin y frontend cargan archivos del plugin dinámicamente
4. **Desactivación**: `uninstall.sql` se ejecuta → ⚠️ **datos eliminados**

### API del plugin

El `api.php` recibe las peticiones vía router centralizado:

```
/api/plugins.php?action=api&plugin=ID&route=RUTA
```

Dentro del `api.php`:

```php
<?php
// El router ya hizo require de init.php y verificó auth
$action = $_GET['route'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ("$method $action") {
    case 'GET projects':
        // listar...
        break;
    case 'POST projects':
        // crear...
        break;
}
```

### Rutas públicas

Agregar en `.htaccess` raíz:

```apache
# Plugin routes
RewriteRule ^mi-ruta/?$ /plugins/mi-plugin/public-page.php [L]
```

### Componente embebible

Para que un componente se muestre en `index.php`:

```php
<!-- En index.php -->
<?php
$path = __DIR__ . '/plugins/mi-plugin/public-component.php';
if (file_exists($path)) include $path;
?>
```

El componente debe verificar internamente si el plugin está activo.

---

## Convenciones del Backend

### Respuestas API

```php
// Éxito
jsonSuccess(['data' => $data]);              // 200
jsonSuccess(['data' => $data], 201);         // 201 Created

// Error
jsonError('Mensaje de error');               // 400
jsonError('No autorizado', 401);             // 401
jsonError('No encontrado', 404);             // 404
```

### Leer body JSON

```php
$body = getJSONBody();
$name = $body['name'] ?? '';
```

### Log de actividad

```php
logActivity('create', 'project', $projectId, ['title' => $title]);
```

### Proteger endpoint

```php
require_once __DIR__ . '/init.php';  // incluye todo
requireAuth();                        // requiere sesión activa
```

---

## Flujo de Instalación

```
┌─ Visitar /install/ ─────────────────────────────────┐
│                                                       │
│  ¿Existe .installed.lock?                            │
│  ├─ SÍ → Muestra "Ya instalado" + link al admin     │
│  └─ NO → Continúa...                                │
│                                                       │
│  Step 1: Verifica requisitos (PHP, PDO, permisos)    │
│  Step 2: Form (BD + admin)                           │
│  Procesa:                                            │
│    1. Conecta a MySQL                                │
│    2. CREATE DATABASE IF NOT EXISTS                   │
│    3. Ejecuta schema.sql (tablas + defaults)         │
│    4. INSERT admin en users                          │
│    5. Genera config/database.php                     │
│    6. Crea uploads/ + .htaccess                      │
│    7. Escribe .installed.lock                        │
│                                                       │
│  ✅ Éxito → Link al panel admin                      │
└──────────────────────────────────────────────────────┘
```

### ¿Para reinstalar?
1. Eliminar `install/.installed.lock`
2. Eliminar `config/database.php`
3. Visitar `/install/` de nuevo

### ¿Segunda instalación del wizard?
Si el lock file existe, se detiene inmediatamente. El archivo **NO se borra** — se bloquea.

---

## Versionado

| Archivo | Propósito |
|---------|-----------|
| `VERSION` | Número de versión actual (ej: `1.0.0`) |
| `CHANGELOG.md` | Historial detallado de cada versión |

Al hacer cambios significativos en un proyecto:

1. Actualizar `VERSION` con el nuevo número
2. Agregar entrada en `CHANGELOG.md` con fecha y cambios
3. Commit: `git commit -m "release: vX.Y.Z — descripción"`

---

## Notas Importantes

- **`config/database.php`** nunca se sube a Git (`.gitignore`)
- **`.installed.lock`** nunca se sube a Git (`.gitignore`)
- **`/uploads/`** contenido excluido de Git (solo `.htaccess` y `.gitkeep`)
- Al duplicar para un nuevo proyecto, el instalador funciona limpio
- Cada proyecto puede divergir del starter kit según necesidades del cliente
