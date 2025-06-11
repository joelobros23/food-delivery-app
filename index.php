<?php
// index.php - The main landing page for the food delivery website.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Delivery - Order Your Favorite Meals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-50">

    <!-- Header / Navigation -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-orange-600">Foodie</a>
            <div class="hidden md:flex items-center space-x-6">
                <a href="user_dashboard.php" class="text-gray-600 hover:text-orange-600">Home</a>
                <a href="#restaurants" class="text-gray-600 hover:text-orange-600">Restaurants</a>
                <a href="rider_dashboard.php" class="text-gray-600 hover:text-orange-600">Become a Rider</a>
                <a href="store_dashboard.php" class="text-gray-600 hover:text-orange-600">List Your Restaurant</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="login.php" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-full hover:bg-gray-300">Login</a>
                <a href="signup.php" class="px-4 py-2 text-white bg-orange-600 rounded-full hover:bg-orange-700">Sign Up</a>
                <button class="md:hidden flex items-center" id="mobile-menu-button">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>
        </nav>
        <!-- Mobile Menu -->
        <div class="hidden md:hidden" id="mobile-menu">
            <a href="user_dashboard.php" class="block py-2 px-4 text-sm hover:bg-gray-100">Home</a>
            <a href="#restaurants" class="block py-2 px-4 text-sm hover:bg-gray-100">Restaurants</a>
            <a href="rider_dashboard.php" class="block py-2 px-4 text-sm hover:bg-gray-100">Become a Rider</a>
            <a href="store_dashboard.php" class="block py-2 px-4 text-sm hover:bg-gray-100">List Your Restaurant</a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="bg-black bg-opacity-40">
            <div class="container mx-auto px-6 py-32 text-center text-white">
                <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-4">Craving something delicious?</h1>
                <p class="text-lg md:text-xl mb-8">Order from the best restaurants and have it delivered to your doorstep.</p>
                <div class="max-w-2xl mx-auto">
                    <div class="flex items-center bg-white rounded-full shadow-lg p-2">
                        <input type="text" placeholder="Enter your address or favorite restaurant" class="w-full py-3 px-4 text-gray-700 rounded-full focus:outline-none">
                        <button class="bg-orange-600 text-white rounded-full p-3 hover:bg-orange-700 ml-2">
                            <i data-lucide="search" class="w-6 h-6"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="py-16">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">How It Works</h2>
            <div class="grid md:grid-cols-3 gap-8 text-center">
                <div class="p-6">
                    <div class="flex items-center justify-center h-16 w-16 rounded-full bg-orange-100 text-orange-600 mx-auto mb-4">
                        <i data-lucide="map-pin" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">1. Find Restaurants</h3>
                    <p class="text-gray-600">Enter your address to see which restaurants are available in your area.</p>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-center h-16 w-16 rounded-full bg-orange-100 text-orange-600 mx-auto mb-4">
                        <i data-lucide="utensils-crossed" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">2. Choose Your Meal</h3>
                    <p class="text-gray-600">Browse through menus and pick your favorite dishes.</p>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-center h-16 w-16 rounded-full bg-orange-100 text-orange-600 mx-auto mb-4">
                        <i data-lucide="bike" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">3. Fast Delivery</h3>
                    <p class="text-gray-600">Get your food delivered quickly to your home or office.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Restaurants -->
    <section id="restaurants" class="bg-white py-16">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">Featured Restaurants</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php
                // This would be populated from a database in a real application
                $restaurants = [
                    ['name' => 'The Golden Spoon', 'cuisine' => 'Filipino', 'rating' => 4.5, 'image' => 'https://placehold.co/600x400/FFC107/FFFFFF?text=Restaurant+1'],
                    ['name' => 'Pizza Palace', 'cuisine' => 'Italian', 'rating' => 4.8, 'image' => 'https://placehold.co/600x400/E91E63/FFFFFF?text=Restaurant+2'],
                    ['name' => 'Burger Hub', 'cuisine' => 'American', 'rating' => 4.3, 'image' => 'https://placehold.co/600x400/4CAF50/FFFFFF?text=Restaurant+3'],
                    ['name' => 'Sushi Central', 'cuisine' => 'Japanese', 'rating' => 4.9, 'image' => 'https://placehold.co/600x400/2196F3/FFFFFF?text=Restaurant+4'],
                ];
                foreach ($restaurants as $resto) {
                ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:-translate-y-2 transition-transform duration-300">
                    <img src="<?php echo htmlspecialchars($resto['image']); ?>" alt="<?php echo htmlspecialchars($resto['name']); ?>" class="w-full h-48 object-cover">
                    <div class="p-6">
                        <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($resto['name']); ?></h3>
                        <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($resto['cuisine']); ?></p>
                        <div class="flex items-center">
                            <i data-lucide="star" class="w-5 h-5 text-yellow-500 fill-current"></i>
                            <span class="text-gray-700 font-semibold ml-1"><?php echo htmlspecialchars($resto['rating']); ?></span>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- App Promotion -->
    <section class="bg-orange-50 py-16">
        <div class="container mx-auto px-6">
            <div class="flex flex-wrap items-center">
                <div class="w-full md:w-1/2">
                    <img src="https://placehold.co/600x600/FFFFFF/FF6B00?text=App+Screenshot" alt="App Screenshot" class="max-w-sm mx-auto rounded-lg shadow-xl">
                </div>
                <div class="w-full md:w-1/2 mt-8 md:mt-0 md:pl-12">
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">Get the Foodie App</h2>
                    <p class="text-gray-600 mb-6">Order food on the go with our mobile app. Get real-time order tracking and exclusive app-only deals.</p>
                    <div class="flex space-x-4">
                        <a href="#"><img src="https://placehold.co/180x60/000000/FFFFFF?text=App+Store" alt="App Store" class="h-12"></a>
                        <a href="#"><img src="https://placehold.co/180x60/000000/FFFFFF?text=Google+Play" alt="Google Play" class="h-12"></a>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">Foodie</h3>
                    <p class="text-gray-400">The best food delivery service in town.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul>
                        <li><a href="#" class="text-gray-400 hover:text-white">About Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Contact</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Legal</h3>
                    <ul>
                        <li><a href="#" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white"><i data-lucide="facebook"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i data-lucide="twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white"><i data-lucide="instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-500">
                &copy; <?php echo date("Y"); ?> Foodie. All rights reserved.
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>
