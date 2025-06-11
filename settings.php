<?php
// settings.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: login.php");
    exit;
}
$active_page = 'settings'; 

// Fetch current user details to populate forms
require_once "db_connection/config.php";
$user_details = null;
$sql = "SELECT full_name, email, address, latitude, longitude, show_activities FROM users WHERE id = ?";
if($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user_details = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}
mysqli_close($link);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Foodie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen bg-gray-100">
        <?php require_once 'cdashboard_partial/sidebar.php'; ?>
        <div class="flex flex-col flex-1 overflow-y-auto">
            <?php require_once 'cdashboard_partial/header.php'; ?>
            <div class="p-4 md:p-6 pb-20 md:pb-6">
                
                <div class="max-w-4xl mx-auto">
                    <!-- Main Settings Menu (Initially Visible) -->
                    <div id="settings-menu">
                         <h1 class="text-3xl font-bold text-gray-800 mb-6">Settings</h1>
                         <div class="bg-white p-4 rounded-lg shadow-md space-y-1">
                            <a href="#" data-target="personal-view" class="settings-btn flex justify-between items-center p-4 hover:bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800">Personal Details</span><i data-lucide="chevron-right" class="text-gray-400"></i></a>
                            <a href="#" data-target="security-view" class="settings-btn flex justify-between items-center p-4 hover:bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800">Security</span><i data-lucide="chevron-right" class="text-gray-400"></i></a>
                            <a href="#" data-target="privacy-view" class="settings-btn flex justify-between items-center p-4 hover:bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800">Privacy</span><i data-lucide="chevron-right" class="text-gray-400"></i></a>
                         </div>
                    </div>

                    <!-- Personal Details View (Initially Hidden) -->
                    <div id="personal-view" class="settings-view hidden">
                        <button class="back-to-menu mb-6 flex items-center text-gray-600 hover:text-orange-600 font-semibold"><i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i> Back</button>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Personal Details</h2>
                            <form id="personal-form" class="space-y-6">
                                <input type="hidden" name="action" value="update_personal">
                                <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($user_details['latitude'] ?? ''); ?>">
                                <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($user_details['longitude'] ?? ''); ?>">
                                <div>
                                    <label for="full-name" class="block text-sm font-medium text-gray-500">Full Name</label>
                                    <input type="text" name="full_name" id="full-name" value="<?php echo htmlspecialchars($user_details['full_name']); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="address" class="block text-sm font-medium text-gray-500">Address</label>
                                    <textarea name="address" id="address" rows="3" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm"><?php echo htmlspecialchars($user_details['address'] ?? ''); ?></textarea>
                                    <button type="button" id="detect-location-btn" class="mt-2 text-sm text-orange-600 hover:underline">Detect My Location</button>
                                </div>
                                <div id="map-container" class="w-full h-64 bg-gray-200 rounded-lg hidden flex items-center justify-center text-gray-500">Map will be displayed here.</div>
                                <div id="personal-message" class="text-sm"></div>
                                <div class="text-right pt-4">
                                    <button type="submit" class="px-6 py-2 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Security View (Initially Hidden) -->
                    <div id="security-view" class="settings-view hidden">
                        <button class="back-to-menu mb-6 flex items-center text-gray-600 hover:text-orange-600 font-semibold"><i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i> Back</button>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Security</h2>
                             <form id="security-form" class="space-y-6">
                                <input type="hidden" name="action" value="update_security">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Email Address</label>
                                    <p class="text-lg text-gray-800 mt-1"><?php echo htmlspecialchars($user_details['email']); ?></p>
                                </div>
                                <div>
                                    <label for="current-password" class="block text-sm font-medium text-gray-500">Current Password</label>
                                    <input type="password" name="current_password" id="current-password" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm" placeholder="••••••••">
                                </div>
                                <div>
                                    <label for="new-password" class="block text-sm font-medium text-gray-500">New Password</label>
                                    <input type="password" name="new_password" id="new-password" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
                                </div>
                                <div id="security-message" class="text-sm"></div>
                                <div class="text-right pt-4">
                                    <button type="submit" class="px-6 py-2 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Privacy View (Initially Hidden) -->
                    <div id="privacy-view" class="settings-view hidden">
                       <button class="back-to-menu mb-6 flex items-center text-gray-600 hover:text-orange-600 font-semibold"><i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i> Back</button>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Privacy</h2>
                           <form id="privacy-form" class="space-y-6">
                                <input type="hidden" name="action" value="update_privacy">
                               <div class="flex justify-between items-center p-3 border rounded-lg">
                                   <div>
                                       <p class="font-medium text-gray-800">Show activities on profile?</p>
                                       <p class="text-sm text-gray-500">Allow others to see your reviews and favorites.</p>
                                   </div>
                                   <label class="relative inline-flex items-center cursor-pointer">
                                     <input type="checkbox" name="show_activities" class="sr-only peer" <?php echo ($user_details['show_activities'] ?? true) ? 'checked' : ''; ?>>
                                     <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-4 peer-focus:ring-orange-300 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                                   </label>
                               </div>
                               <div id="privacy-message" class="text-sm"></div>
                                <div class="text-right pt-4">
                                    <button type="submit" class="px-6 py-2 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700">Save Changes</button>
                                </div>
                           </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menu = document.getElementById('settings-menu');
            const views = document.querySelectorAll('.settings-view');
            const menuButtons = document.querySelectorAll('.settings-btn');
            const backButtons = document.querySelectorAll('.back-to-menu');
            const detectLocationBtn = document.getElementById('detect-location-btn');
            const addressTextarea = document.getElementById('address');
            const mapContainer = document.getElementById('map-container');
            const latInput = document.getElementById('latitude');
            const lonInput = document.getElementById('longitude');

            // --- Reusable Map and Geocoding Functions ---
            function updateMap(lat, lon) {
                // CORRECTED: Pre-calculate the bounding box values as numbers.
                const numLat = parseFloat(lat);
                const numLon = parseFloat(lon);

                if (isNaN(numLat) || isNaN(numLon)) {
                    mapContainer.innerHTML = 'Invalid coordinates provided.';
                    mapContainer.classList.remove('hidden');
                    return;
                }
                
                const lon1 = numLon - 0.01;
                const lat1 = numLat - 0.01;
                const lon2 = numLon + 0.01;
                const lat2 = numLat + 0.01;

                const bbox = `${lon1},${lat1},${lon2},${lat2}`;

                mapContainer.innerHTML = `<iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${numLat},${numLon}" style="border: 1px solid black; border-radius: 0.5rem;"></iframe>`;
                mapContainer.classList.remove('hidden');
            }
            
            async function geocodeAddress(address) {
                if (!address) return;
                mapContainer.classList.remove('hidden');
                mapContainer.innerHTML = 'Searching for address...';
                try {
                    const response = await fetch(`customer_dashboard_settings/geocode.php?address=${encodeURIComponent(address)}`);
                    const data = await response.json();
                    if (data && data.length > 0) {
                        updateMap(data[0].lat, data[0].lon);
                    } else {
                        mapContainer.innerHTML = 'Could not find the address on the map.';
                    }
                } catch (error) {
                    mapContainer.innerHTML = 'Could not load map due to a network error.';
                }
            }


            // --- View Switching Logic ---
            menuButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = button.dataset.target;
                    const targetView = document.getElementById(targetId);
                    menu.classList.add('hidden');
                    views.forEach(view => view.classList.add('hidden'));
                    if(targetView) {
                        targetView.classList.remove('hidden');
                        if(targetId === 'personal-view' && latInput.value && lonInput.value) {
                             updateMap(latInput.value, lonInput.value);
                        } else if (targetId === 'personal-view') {
                             mapContainer.innerHTML = 'Address not set. Use "Detect My Location" or enter an address manually.';
                             mapContainer.classList.remove('hidden');
                        }
                    }
                });
            });

            backButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    views.forEach(view => view.classList.add('hidden'));
                    menu.classList.remove('hidden');
                });
            });

            // "Detect My Location" Button Logic
            detectLocationBtn.addEventListener('click', () => {
                detectLocationBtn.textContent = 'Detecting...';
                fetch('https://ipapi.co/json/')
                    .then(response => response.json())
                    .then(data => {
                        if (data && !data.error) {
                            const fullAddress = `${data.city}, ${data.region}, ${data.country_name}`;
                            addressTextarea.value = fullAddress;
                            latInput.value = data.latitude;
                            lonInput.value = data.longitude;
                            updateMap(data.latitude, data.longitude);
                        } else {
                            alert('Could not detect location. Please enter manually.');
                        }
                    })
                    .catch(() => alert('Could not detect location due to a network issue.'))
                    .finally(() => detectLocationBtn.textContent = 'Detect My Location');
            });
            
            // Form submission handler
            function handleFormSubmit(formId, messageId) {
                const form = document.getElementById(formId);
                const messageDiv = document.getElementById(messageId);
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    messageDiv.textContent = '';
                    const formData = new FormData(this);

                    fetch('customer_dashboard_settings/manage_settings.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if(data.status === 'success') {
                            messageDiv.innerHTML = `<p class="text-green-600">${data.message}</p>`;
                            if (formId === 'security-form') form.reset();
                        } else {
                            messageDiv.innerHTML = `<p class="text-red-600">${data.message}</p>`;
                        }
                    });
                });
            }
            handleFormSubmit('personal-form', 'personal-message');
            handleFormSubmit('security-form', 'security-message');
            handleFormSubmit('privacy-form', 'privacy-message');
        });
    </script>
    <script src="js/script.js"></script>
</body>
</html>
