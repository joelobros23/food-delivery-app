-- ====================================
-- USERS
-- ====================================
INSERT INTO `users` (`full_name`, `email`, `password`, `role`, `address`, `city`, `latitude`, `longitude`, `show_activities`, `account_type`)
VALUES
('Juan Dela Cruz', 'juan@example.com', 'hashed_pass_juan', 'customer', 'Zone 1, Kabankalan', 'Kabankalan', 9.9901, 122.8145, TRUE, 'free'),
('Maria Santos', 'maria@example.com', 'hashed_pass_maria', 'customer', 'Zone 2, Kabankalan', 'Kabankalan', 9.9911, 122.8149, TRUE, 'free'),
('Pedro Rider', 'pedro@example.com', 'hashed_pass_pedro', 'rider', 'Zone 5, Kabankalan', 'Kabankalan', 9.9921, 122.8151, TRUE, 'free'),
('Mang Kiko', 'kiko@grillhouse.com', 'hashed_pass_kiko', 'store', 'Zone 3, Kabankalan', 'Kabankalan', 9.9931, 122.8156, TRUE, 'premium'),
('Aling Nena', 'nena@halotreats.com', 'hashed_pass_nena', 'store', 'Zone 4, Kabankalan', 'Kabankalan', 9.9941, 122.8158, TRUE, 'premium');

-- ====================================
-- RESTAURANTS
-- ====================================
INSERT INTO `restaurants` (`user_id`, `name`, `cuisine`, `address`, `business_type`, `details`, `latitude`, `longitude`, `operating_hours`, `is_open`)
VALUES
(4, 'Kabankalan Grill House', 'Filipino', 'Zone 3, Kabankalan', 'Dine-in', 'Authentic grilled dishes and silog meals', 9.9931, 122.8156, '10:00 AM - 9:00 PM', TRUE),
(5, 'HaloHalo Treats', 'Desserts', 'Zone 4, Kabankalan', 'Take-out', 'Special halo-halo and cold snacks', 9.9941, 122.8158, '1:00 PM - 8:00 PM', TRUE);

-- ====================================
-- MENU ITEMS
-- ====================================
INSERT INTO `menu_items` (`restaurant_id`, `name`, `description`, `price`, `image_url`, `category`, `is_available`)
VALUES
(1, 'Chicken Inasal', 'Grilled chicken with vinegar marinade', 120.00, NULL, 'Main Course', TRUE),
(1, 'Pork BBQ', '2 sticks of sweet pork barbecue', 60.00, NULL, 'Main Course', TRUE),
(1, 'Garlic Rice', 'Perfect with inasal or BBQ', 20.00, NULL, 'Side Dish', TRUE),
(2, 'Classic Halo-Halo', 'Mix of shaved ice, leche flan, and ube', 70.00, NULL, 'Dessert', TRUE),
(2, 'Mais con Yelo', 'Sweet corn over crushed ice with milk', 50.00, NULL, 'Dessert', TRUE);

-- ====================================
-- ORDERS
-- ====================================
INSERT INTO `orders` (`group_order_id`, `customer_id`, `restaurant_id`, `rider_id`, `delivery_address`, `total_amount`, `status`, `payment_method`, `delivery_notes`)
VALUES
('ORD-001', 1, 1, 3, 'Zone 1, Kabankalan', 200.00, 'pending', 'cod', 'Add extra vinegar please'),
('ORD-002', 2, 2, 3, 'Zone 2, Kabankalan', 120.00, 'out_for_delivery', 'gcash', 'Keep halo-halo cold');

-- ====================================
-- ORDER ITEMS
-- ====================================
INSERT INTO `order_items` (`order_id`, `item_id`, `quantity`, `price_per_item`, `is_prepared`)
VALUES
(1, 1, 1, 120.00, TRUE),
(1, 3, 2, 20.00, TRUE),
(2, 4, 1, 70.00, TRUE),
(2, 5, 1, 50.00, TRUE);

-- ====================================
-- MESSAGES
-- ====================================
INSERT INTO `messages` (`order_id`, `sender_id`, `receiver_id`, `message`)
VALUES
(1, 1, 3, 'Thank you, kuya! Please call me when you arrive.'),
(1, 3, 1, 'Noted! I’m 5 minutes away.'),
(2, 2, 3, 'Please keep the dessert cold, thanks!');

-- ====================================
-- REVIEWS
-- ====================================
INSERT INTO `reviews` (`order_id`, `customer_id`, `restaurant_id`, `rating`, `comment`)
VALUES
(1, 1, 1, 5, 'Sobrang sarap ng inasal. Rider was fast too.'),
(2, 2, 2, 4, 'Good halo-halo! Just a bit melted already.');

-- ====================================
-- FAVORITES
-- ====================================
INSERT INTO `favorite_restaurants` (`customer_id`, `restaurant_id`)
VALUES
(1, 1),
(2, 2),
(1, 2); -- Juan loves both!
