-- Add Cloudinary public_id to portfolio_images so we can manage assets later
-- (delete from Cloudinary, generate transformations, etc.)

ALTER TABLE `portfolio_images`
    ADD COLUMN `public_id` VARCHAR(500) DEFAULT NULL AFTER `image_url`;
