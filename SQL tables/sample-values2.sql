-- ====================================
-- MORE RESTAURANTS
-- ====================================
INSERT INTO `restaurants` (`user_id`, `name`, `cuisine`, `address`, `business_type`, `details`, `latitude`, `longitude`, `operating_hours`, `is_open`)
VALUES
(4, 'Sisig ni Mang Kiko', 'Filipino', 'Zone 6, Kabankalan', 'Take-out', 'Famous sizzling pork sisig and rice meals', 9.9951, 122.8157, '11:00 AM - 8:00 PM', TRUE),
(5, 'Nena’s Fruit Shakes', 'Beverages', 'Zone 7, Kabankalan', 'Take-out', 'Fresh fruit smoothies and juices', 9.9960, 122.8154, '9:00 AM - 6:00 PM', TRUE),
(4, 'Kiko’s Lugawan', 'Filipino', 'Zone 8, Kabankalan', 'Dine-in', 'Classic Filipino rice porridge', 9.9967, 122.8159, '6:00 AM - 12:00 PM', TRUE);

-- ====================================
-- MORE MENU ITEMS
-- ====================================
-- For 'Sisig ni Mang Kiko'
INSERT INTO `menu_items` (`restaurant_id`, `name`, `description`, `price`, `image_url`, `category`, `is_available`)
VALUES
(3, 'Pork Sisig', 'Sizzling pork sisig with egg and calamansi', 110.00, NULL, 'Main Course', TRUE),
(3, 'Sisig Rice Bowl', 'Chopped sisig on garlic rice', 90.00, NULL, 'Main Course', TRUE),
(3, 'Coke Mismo', 'Cold bottled Coca-Cola', 25.00, NULL, 'Beverage', TRUE);

-- For 'Nena’s Fruit Shakes'
INSERT INTO `menu_items` (`restaurant_id`, `name`, `description`, `price`, `image_url`, `category`, `is_available`)
VALUES
(4, 'Mango Shake', 'Fresh mango blended with ice and milk', 60.00, NULL, 'Beverage', TRUE),
(4, 'Buko Juice', 'Natural coconut water', 40.00, NULL, 'Beverage', TRUE),
(4, 'Strawberry Smoothie', 'Strawberries, yogurt, and crushed ice', 75.00, NULL, 'Beverage', TRUE);

-- For 'Kiko’s Lugawan'
INSERT INTO `menu_items` (`restaurant_id`, `name`, `description`, `price`, `image_url`, `category`, `is_available`)
VALUES
(5, 'Lugaw with Egg', 'Hot rice porridge with hard-boiled egg', 35.00, NULL, 'Breakfast', TRUE),
(5, 'Goto Special', 'Beef tripe porridge with garlic topping', 50.00, NULL, 'Breakfast', TRUE),
(5, 'Tokwa\'t Baboy', 'Fried tofu and pork with soy-vinegar dip', 45.00, NULL, 'Side Dish', TRUE);
