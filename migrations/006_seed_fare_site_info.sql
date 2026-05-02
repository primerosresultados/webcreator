-- Sembrar/actualizar la info real del sitio (FARE Arquitectura).
-- Si ya hay un site_info guardado desde el admin, este UPDATE lo pisa.
-- Para que el admin pueda editarlo después, basta con guardar en /admin → Configuración.

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`)
VALUES (
    'site_info',
    '{"siteName":"FARE Arquitectura","siteDescription":"Arquitectura para el sur de Chile","phone":"+56 9 9766 2138","email":"farearquitectura@gmail.com","whatsapp":"56997662138","address":"Pucón, Chile","instagram":"https://instagram.com/FARE_Arquitectura","facebook":"","youtube":"","linkedin":"","twitter":"","pinterest":"","tiktok":""}',
    'json'
)
ON DUPLICATE KEY UPDATE
    `setting_value` = VALUES(`setting_value`),
    `setting_type` = VALUES(`setting_type`);
