-- This script adds new columns to the 'users' table for address and privacy settings.

ALTER TABLE `users`
ADD COLUMN `address` VARCHAR(255) NULL DEFAULT NULL AFTER `role`,
ADD COLUMN `show_activities` BOOLEAN NOT NULL DEFAULT TRUE AFTER `address`;
ADD COLUMN `latitude` DECIMAL(10, 8) NULL DEFAULT NULL AFTER `address`,
ADD COLUMN `longitude` DECIMAL(11, 8) NULL DEFAULT NULL AFTER `latitude`;
ADD COLUMN `account_type` VARCHAR(50) NOT NULL DEFAULT 'personal' AFTER `show_activities`;

