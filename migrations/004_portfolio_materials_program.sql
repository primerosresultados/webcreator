-- Add Materialidades and Programa to portfolio_projects.
-- Stored as comma-separated text. Rendered as chip lists.

ALTER TABLE `portfolio_projects`
    ADD COLUMN `materials` TEXT DEFAULT NULL AFTER `area_m2`,
    ADD COLUMN `program`   TEXT DEFAULT NULL AFTER `materials`;
