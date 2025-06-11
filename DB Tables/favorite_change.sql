-- This script renames the current favorites table and adds a new one for restaurants.

-- Step 1: Rename the existing table to clarify it's for items.
RENAME TABLE `favorites` TO `favorite_items`;

-- Step 2: Create a new table specifically for favoriting restaurants.
CREATE TABLE `favorite_restaurants` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NOT NULL,
  `restaurant_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_restaurant_favorite` (`customer_id`, `restaurant_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
);
