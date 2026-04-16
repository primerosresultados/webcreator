# 🚀 Cómo Instalar WebCreator en Hostinger

## Paso 1: Crear la Base de Datos en Hostinger

1. Entra al **Panel de Hostinger** → [hpanel.hostinger.com](https://hpanel.hostinger.com)
2. Ve a **Bases de datos** → **MySQL Databases**
3. Crea una nueva base de datos:
   - **Nombre de la BD**: elige un nombre (ej: `u123_miweb`)
   - **Usuario**: se genera automático
   - **Contraseña**: pon una contraseña segura
4. **Anota estos 4 datos** (los necesitarás en el Paso 3):
   - Host (ej: `srv1234.hstgr.io`)
   - Nombre de la BD
   - Usuario de la BD
   - Contraseña de la BD

---

## Paso 2: Subir los Archivos

### Opción A: Administrador de Archivos (más fácil)
1. Panel de Hostinger → **Administrador de Archivos**
2. Entra a la carpeta `public_html/`
3. **Sube todos los archivos** del proyecto (puedes comprimir en .zip y subirlo, luego extraer)

### Opción B: FTP con FileZilla
1. Descarga FileZilla: [filezilla-project.org](https://filezilla-project.org)
2. En Hostinger → **Cuentas FTP** → copia las credenciales
3. Conéctate y sube los archivos a `public_html/`

### Opción C: Git Deploy (recomendado para actualizaciones)
1. Crea un repo en GitHub con el proyecto
2. En Hostinger → **Git** → conecta tu repositorio
3. Cada `git push` actualiza el sitio automáticamente

---

## Paso 3: Ejecutar el Instalador

1. Abre tu navegador y visita: `https://tusitio.com/install/`
2. Verás una pantalla de bienvenida que verifica los requisitos
3. Click en **"Comenzar Instalación"**
4. Completa el formulario:
   - **Base de datos**: pega los 4 datos del Paso 1
   - **Nombre del sitio**: el nombre de tu proyecto
   - **URL del sitio**: `https://tusitio.com`
   - **Admin**: elige usuario, email y contraseña
5. Click en **"Instalar Ahora"**
6. ✅ ¡Listo! El sistema crea las tablas y tu cuenta de admin

---

## Paso 4: Usar tu Sitio

| URL | Qué es |
|-----|--------|
| `https://tusitio.com` | Tu sitio web público |
| `https://tusitio.com/admin/` | Login del panel admin |
| `https://tusitio.com/admin/dashboard.php` | Dashboard CRM |

### Credenciales de admin:
Las que elegiste en el Paso 3 (email + contraseña).

---

## 🎨 Personalizar para un Nuevo Proyecto

### Cambiar colores y fuentes:
Edita `assets/css/variables.css` — ahí están TODOS los colores del sitio.

```css
/* Ejemplo: cambiar a rojo/naranja */
--color-primary: #e11d48;
--color-primary-hover: #be123c;
--color-secondary: #f97316;
```

### Cambiar contenido:
Edita `index.php` — cambia los textos, secciones, datos de contacto.

### Lo que NO necesitas tocar:
- `/api/` — backend (funciona igual siempre)
- `/admin/` — panel CRM (funciona igual siempre)
- `/install/` — instalador (funciona igual siempre)
- `/config/` — se genera automáticamente

---

## 📋 Flujo para Cada Nuevo Cliente

```
1. Copiar la carpeta webcreator/ → carpeta-del-cliente/
2. Editar index.php (contenido del cliente)
3. Editar colores desde el panel admin (o assets/css/variables.css)
4. Crear repo en GitHub para ese cliente
5. Crear BD en Hostinger para ese cliente
6. Subir archivos a Hostinger
7. Visitar /install/ → configurar
8. Configurar site info, logos y tema desde el admin
9. Activar plugins necesarios (ej: Portfolio)
10. Entregar sitio al cliente
```

---

## ⚠️ Notas Importantes

- **El instalador se bloquea solo** después de usarlo. Si necesitas reinstalar, elimina `install/.installed.lock` y `config/database.php`
- **No subas `config/database.php` a GitHub** — el `.gitignore` ya lo excluye automáticamente
- **Hostinger Free** no soporta PHP/MySQL — necesitas el plan **Premium** o superior
- Las imágenes subidas desde el admin se guardan en `/uploads/` organizadas por año/mes
