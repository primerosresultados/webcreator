<?php
/**
 * Hero videos (Cloudinary)
 * ─────────────────────────────────────────────
 * Para agregar/sacar videos del hero, editá los public IDs en $publicIds.
 * Los videos se reproducen en loop sin sonido y van rotando en orden.
 *
 * Cómo conseguir el public ID:
 *   1. Subí el video a Cloudinary (Media Library).
 *   2. Hacé click en el archivo → copiá el "Public ID" (ej: "fare/hero-1" o "15194819_3840_2160_60fps_zgbcgf").
 *
 * Cómo cambiar el cloud name:
 *   Editá $cloudName más abajo.
 */

return [
    'cloudName' => 'dt7raeikn',

    // Transformaciones aplicadas a TODOS los videos (calidad/formato auto).
    // f_auto = formato óptimo según el navegador (mp4/webm/av1)
    // q_auto = bitrate adaptativo según contenido
    'transform' => 'q_auto,f_auto',

    // Lista de public IDs (sin extensión). Se reproducen en orden.
    'publicIds' => [
        '15194819_3840_2160_60fps_zgbcgf',
        '13052294_3840_2160_24fps_bv0iau',
        '13504352_3840_2160_30fps_q2frhf',
    ],
];
