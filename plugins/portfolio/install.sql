-- ============================================
-- Plugin: Portfolio — INSTALL
-- Creates tables for portfolio projects, images, and videos.
-- Executed automatically when the plugin is activated.
-- ============================================

CREATE TABLE IF NOT EXISTS `portfolio_projects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `category` ENUM('residencial','comercial','institucional','interiorismo','paisajismo','restauracion','industrial','otro') NOT NULL DEFAULT 'residencial',
    `description` TEXT DEFAULT NULL,
    `client_name` VARCHAR(255) DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `year` SMALLINT UNSIGNED DEFAULT NULL,
    `project_date` DATE DEFAULT NULL,
    `area_m2` DECIMAL(10,2) DEFAULT NULL,
    `materials` TEXT DEFAULT NULL,
    `program` TEXT DEFAULT NULL,
    `status` ENUM('draft','published') NOT NULL DEFAULT 'draft',
    `featured_image` VARCHAR(500) DEFAULT NULL,
    `video_url` VARCHAR(500) DEFAULT NULL,
    `tags` VARCHAR(500) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_portfolio_status` (`status`),
    INDEX `idx_portfolio_category` (`category`),
    INDEX `idx_portfolio_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `portfolio_images` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `image_url` VARCHAR(500) NOT NULL,
    `caption` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_portfolio_img_project` (`project_id`),
    CONSTRAINT `fk_portfolio_images_project` FOREIGN KEY (`project_id`)
        REFERENCES `portfolio_projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `portfolio_videos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `video_url` VARCHAR(500) NOT NULL,
    `video_type` ENUM('youtube','vimeo','upload','other') NOT NULL DEFAULT 'youtube',
    `title` VARCHAR(255) DEFAULT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_portfolio_vid_project` (`project_id`),
    INDEX `idx_portfolio_vid_featured` (`project_id`, `is_featured`),
    CONSTRAINT `fk_portfolio_videos_project` FOREIGN KEY (`project_id`)
        REFERENCES `portfolio_projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
