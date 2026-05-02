-- Allow marking a project video as "featured" so it can be used as the
-- background of the project detail header. Only one featured video per
-- project (enforced in API).

ALTER TABLE `portfolio_videos`
    ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `title`,
    ADD INDEX `idx_portfolio_vid_featured` (`project_id`, `is_featured`);
