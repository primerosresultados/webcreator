<?php
/**
 * Cloudinary — Configuración pública
 * ─────────────────────────────────────────────
 * Estos valores son PÚBLICOS (van al frontend). No incluir API_SECRET acá.
 *
 * Cómo configurar:
 *   1. Cloud Name → Dashboard de Cloudinary, abajo del logo.
 *   2. Upload Preset (unsigned) → Settings → Upload → Add upload preset:
 *        - Signing Mode: Unsigned
 *        - Folder: fare/portfolio (opcional)
 *        - Guardar y copiar el nombre acá.
 */

return [
    'cloudName'    => 'dt7raeikn',
    'uploadPreset' => 'fare_portfolio_unsigned',
    // Carpeta destino dentro de Cloudinary (si el preset no la fija). Opcional.
    'folder'       => 'fare/portfolio',
    // Transformación por defecto al servir imágenes (mismo formato que Cloudinary)
    'transform'    => 'q_auto,f_auto',
];
