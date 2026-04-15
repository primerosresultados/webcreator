# 📐 Guía para Nuevas Páginas — WebCreator

> **IMPORTANTE:** Sigue estas reglas al crear cualquier página nueva para que herede
> automáticamente los colores, tipografía y diseño configurados desde el panel admin.

---

## ✅ Checklist para cada página nueva

### 1. Incluir los CSS en orden correcto

```html
<head>
    <!-- 1. Variables (tokens de diseño) -->
    <link rel="stylesheet" href="/assets/css/variables.css">
    <!-- 2. Reset y estilos base -->
    <link rel="stylesheet" href="/assets/css/base.css">
    <!-- 3. Componentes reutilizables (botones, cards, modales, etc.) -->
    <link rel="stylesheet" href="/assets/css/components.css">
    <!-- 4. Estilos del sitio público -->
    <link rel="stylesheet" href="/assets/css/public.css">
    <!-- 5. OBLIGATORIO: Tema dinámico (lee la BD y genera CSS) -->
    <link rel="stylesheet" href="/api/theme.css.php">
</head>
```

> ⚠️ **Sin `theme.css.php`** la página NO heredará los colores, fuentes ni bordes
> configurados desde el panel de administración.

---

### 2. Usar variables CSS, NUNCA colores hardcoded

```css
/* ❌ MAL — color fijo, no cambia con el admin */
.mi-boton { background: #c9a96e; }

/* ✅ BIEN — usa la variable, se actualiza desde el panel */
.mi-boton { background: var(--color-secondary); }
```

#### Variables de color disponibles:
| Variable | Uso |
|----------|-----|
| `var(--color-primary)` | Color principal (botones, links, CTAs) |
| `var(--color-primary-hover)` | Hover del primario |
| `var(--color-secondary)` | Color secundario/acento (dorado por defecto) |
| `var(--color-accent)` | Color terciario de acento |
| `rgba(var(--color-primary-rgb), 0.1)` | Primario con transparencia |
| `rgba(var(--color-secondary-rgb), 0.1)` | Secundario con transparencia |

#### Variables de tipografía:
| Variable | Uso |
|----------|-----|
| `var(--font-primary)` | Fuente del cuerpo (body) |
| `var(--font-headings)` | Fuente de títulos (H1-H6) — solo si se configura |
| `var(--font-menu)` | Fuente del menú — solo si se configura |

#### Variables de bordes:
| Variable | Uso |
|----------|-----|
| `var(--radius-sm)` | Bordes suaves (4px aprox) |
| `var(--radius-md)` | Bordes medianos |
| `var(--radius-lg)` | Bordes grandes (el valor base del admin) |
| `var(--radius-xl)` | Bordes extra grandes |
| `var(--radius-2xl)` | Bordes muy redondeados |
| `var(--radius-full)` | Totalmente redondo (píldora) |

---

### 3. Incluir el JS público si hay formularios

```html
<!-- Antes del cierre de </body> -->
<script src="/assets/js/app.js"></script>
```

El `app.js` maneja:
- Envío de formularios a `/api/leads.php`
- Notificaciones Toast
- Animaciones on-scroll
- Menú móvil

---

### 4. Mantener la estructura HTML del header y footer

Copiar el `<header>` y `<footer>` de `index.html` para mantener consistencia en navegación.

---

### 5. Formularios: campos ocultos obligatorios

Todo formulario que envíe leads debe incluir:

```html
<form id="mi-formulario">
    <!-- Anti-spam: honeypot -->
    <div style="position:absolute;left:-9999px;" aria-hidden="true">
        <input type="text" name="website_url" tabindex="-1" autocomplete="off">
    </div>
    <!-- Anti-spam: tiempo de carga -->
    <input type="hidden" name="_form_time" value="">
    <!-- Trazabilidad: de dónde viene el lead -->
    <input type="hidden" name="source" value="pagina-nueva">

    <!-- Tus campos aquí... -->
</form>
```

---

### 6. Clases CSS reutilizables (ya definidas)

| Clase | Qué hace |
|-------|----------|
| `.container` | Centra el contenido (max 1280px) |
| `.btn .btn-primary` | Botón principal |
| `.btn .btn-accent` | Botón con color secundario |
| `.btn-outline-light` | Botón borde blanco (para fondos oscuros) |
| `.btn-lg` / `.btn-sm` | Tamaños de botón |
| `.btn-block` | Botón ancho 100% |
| `.card` | Tarjeta con borde y sombra |
| `.form-input` | Input estilizado |
| `.form-textarea` | Textarea estilizado |
| `.form-label` | Label de formulario |
| `.form-group` | Wrapper de campo (con margin-bottom) |
| `.form-row` | Grid de 2 columnas para campos |
| `.section` | Padding vertical estándar (6rem) |

---

### 7. Ejemplo: Página nueva completa

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Descripción de esta página">
    <title>MiSitio — Nombre de Página</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/assets/css/variables.css">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/public.css">
    <link rel="stylesheet" href="/api/theme.css.php">
</head>
<body>
    <!-- Copiar header de index.html -->
    <header class="site-header" id="header">
        <!-- ... navegación ... -->
    </header>

    <!-- Tu contenido -->
    <section class="section" style="padding-top:calc(var(--header-height) + 3rem);">
        <div class="container">
            <h1>Título de la Página</h1>
            <p>Contenido aquí...</p>
        </div>
    </section>

    <!-- Copiar footer de index.html -->
    <footer class="site-footer">
        <!-- ... footer ... -->
    </footer>

    <script src="/assets/js/app.js"></script>
</body>
</html>
```

---

## 📁 Estructura de archivos

```
webcreator/
├── index.html              ← Landing page principal
├── pagina-nueva.html       ← Tus páginas nuevas aquí
├── admin/
│   ├── index.html          ← Login admin
│   └── dashboard.html      ← Panel admin
├── api/
│   ├── init.php            ← Bootstrap de API
│   ├── leads.php           ← CRUD de leads
│   ├── auth.php            ← Autenticación
│   ├── upload.php          ← Subida de archivos
│   ├── settings.php        ← Configuraciones (tema, logos)
│   └── theme.css.php       ← CSS dinámico desde BD
├── assets/
│   ├── css/
│   │   ├── variables.css   ← Tokens de diseño (NO tocar en proyectos)
│   │   ├── base.css        ← Reset y estilos base
│   │   ├── components.css  ← Botones, cards, modales, etc.
│   │   ├── public.css      ← Estilos del sitio público
│   │   └── admin.css       ← Estilos del panel admin
│   ├── js/
│   │   ├── app.js          ← JS del sitio público
│   │   └── admin.js        ← JS del panel admin
│   └── img/                ← Imágenes del proyecto
├── config/
│   └── database.php        ← Credenciales (NO subir a Git)
├── uploads/                ← Archivos subidos por el admin
└── install/                ← Instalador (se bloquea después)
```
