-- ============================================
-- Migration: Create settings table
-- ============================================
-- This creates the settings table if it doesn't exist
-- (for installations that were done before this feature)

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') NOT NULL DEFAULT 'string',
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('site_name', 'Mi Sitio Web', 'string'),
('site_description', 'Descripción de mi sitio web', 'string'),
('contact_email', 'admin@tusitio.com', 'string'),
('leads_per_page', '25', 'number'),
('enable_notifications', 'true', 'boolean'),
('smtp_config', '{}', 'json')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
