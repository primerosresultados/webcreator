-- ============================================
-- Plugin: Portfolio — MIGRATION v1.1
-- Run this manually if the plugin was already activated
-- before the video/date update.
-- ============================================

-- Add new columns to portfolio_projects (safe with IF NOT EXISTS pattern)
ALTER TABLE `portfolio_projects` ADD COLUMN IF NOT EXISTS `project_date` DATE DEFAULT NULL AFTER `year`;
ALTER TABLE `portfolio_projects` ADD COLUMN IF NOT EXISTS `video_url` VARCHAR(500) DEFAULT NULL AFTER `featured_image`;

-- Create portfolio_videos table
CREATE TABLE IF NOT EXISTS `portfolio_videos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `video_url` VARCHAR(500) NOT NULL,
    `video_type` ENUM('youtube','vimeo','upload','other') NOT NULL DEFAULT 'youtube',
    `title` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_portfolio_vid_project` (`project_id`),
    CONSTRAINT `fk_portfolio_videos_project` FOREIGN KEY (`project_id`)
        REFERENCES `portfolio_projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
