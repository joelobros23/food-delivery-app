-- This script contains SQL commands to create the necessary tables for the food delivery application.
-- These tables handle restaurants, menu items, orders, reviews, and favorites.

--
-- Table structure for table `restaurants`
-- Stores information about the restaurants or stores partnered with the service.
--
CREATE TABLE `restaurants` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL, -- Foreign key to the users table (for the store owner)
  `name` VARCHAR(255) NOT NULL,
  `cuisine` VARCHAR(100) DEFAULT NULL, -- e.g., 'Filipino', 'Italian', 'Japanese'
  `address` VARCHAR(255) NOT NULL,
  `banner_image_url` VARCHAR(255) DEFAULT NULL,
  `operating_hours` VARCHAR(100) DEFAULT NULL, -- e.g., '9:00 AM - 10:00 PM'
  `is_open` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);


--
-- Table structure for table `menu_items`
-- Stores all the food and drink items available from each restaurant.
--
CREATE TABLE `menu_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `restaurant_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `category` VARCHAR(100) DEFAULT 'Main Course', -- e.g., 'Appetizer', 'Main Course', 'Dessert', 'Beverage'
  `is_available` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
);


--
-- Table structure for table `orders`
-- Main table to track all customer orders.
--
CREATE TABLE `orders` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `group_order_id` VARCHAR(255) NULL DEFAULT NULL,
  `customer_id` INT NOT NULL,
  `restaurant_id` INT NOT NULL,
  `rider_id` INT DEFAULT NULL, -- Can be NULL initially until a rider accepts the order
  `delivery_address` VARCHAR(255) NOT NULL,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pending', 'preparing', 'out_for_delivery', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cod',
  `delivery_notes` TEXT DEFAULT NULL,
  `order_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`rider_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);


--
-- Table structure for table `order_items`
-- A junction table to store the specific items included in each order.
--
CREATE TABLE `order_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `item_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `price_per_item` DECIMAL(10, 2) NOT NULL, -- Storing price at time of order
  `is_prepared` BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
);

CREATE TABLE `messages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` BOOLEAN NOT NULL DEFAULT FALSE,
  `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

--
-- Table structure for table `reviews`
-- Allows customers to leave reviews for restaurants after an order is delivered.
--
CREATE TABLE `reviews` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `restaurant_id` INT NOT NULL,
  `rating` TINYINT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5), -- Rating from 1 to 5
  `comment` TEXT DEFAULT NULL,
  `review_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_review` (`order_id`), -- A user can only review an order once
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
);


--
-- Table structure for table `favorites`
-- Allows users to save their favorite restaurants for easy access.
--
CREATE TABLE `favorites` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NOT NULL,
  `restaurant_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_restaurant_favorite` (`customer_id`, `restaurant_id`), -- Prevents duplicate entries
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
);

