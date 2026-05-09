<?php
/**
 * Deploy stamp — se muestra en el footer.
 *
 *   $deployVersion : MANUAL — bumpeá esto en cada release significativo.
 *                    Formato sugerido: "rNN-YYYY-MM-DD (descripción corta)".
 *   $deployTime   : AUTO — viene de filemtime() de este archivo, así cada
 *                    deploy real (push + pull en el server) lo refresca.
 *
 * Cómo usarlo en un footer:
 *   $deployVersion = @include __DIR__ . '/config/deploy-version.php';
 *   $deployTime    = @filemtime(__DIR__ . '/config/deploy-version.php');
 */
return 'r9-2026-05-09 (panel usuarios + footer dinámico + loader pulido)';
