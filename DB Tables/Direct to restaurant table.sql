-- Prompt this script directly to restaurant table

ALTER TABLE `restaurants`
ADD COLUMN `business_type` VARCHAR(100) NULL DEFAULT NULL AFTER `address`,
ADD COLUMN `details` TEXT NULL DEFAULT NULL AFTER `business_type`,
ADD COLUMN `business_permit_url` VARCHAR(255) NULL DEFAULT NULL AFTER `details`,
ADD COLUMN `latitude` DECIMAL(10, 8) NULL DEFAULT NULL AFTER `business_permit_url`,
ADD COLUMN `longitude` DECIMAL(11, 8) NULL DEFAULT NULL AFTER `latitude`;

