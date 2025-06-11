<?php
// create_store.php
session_start();
// Security check: Must be a logged-in store owner
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'store') {
    header("location: login.php");
    exit;
}

require_once "db_connection/config.php";

$user_id = $_SESSION['id'];

// Security check: If they already have a restaurant, redirect to dashboard
$sql_check = "SELECT id FROM restaurants WHERE user_id = ?";
if ($stmt_check = mysqli_prepare($link, $sql_check)) {
    mysqli_stmt_bind_param($stmt_check, "i", $user_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        header("location: store/index.php");
        exit;
    }
    mysqli_stmt_close($stmt_check);
}

$error_msg = '';

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $restaurant_name = trim($_POST['restaurant_name']);
    $business_type = trim($_POST['business_type']);
    $address = trim($_POST['address']);
    $details = trim($_POST['details']);
    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);
    $permit_url = null;

    // --- Validation ---
    if (empty($restaurant_name) || empty($business_type) || empty($address)) {
        $error_msg = "Please fill in all required fields.";
    } else {
        // --- Optional File Upload ---
        if (isset($_FILES["business_permit"]) && $_FILES["business_permit"]["error"] == 0) {
            $target_dir = "uploads/permits/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
            $file_ext = strtolower(pathinfo($_FILES["business_permit"]["name"], PATHINFO_EXTENSION));
            $new_filename = uniqid('permit_', true) . '.' . $file_ext;
            $target_file = $target_dir . $new_filename;

            // Allow certain file formats
            $allowed_types = ['jpg', 'png', 'jpeg', 'pdf'];
            if (in_array($file_ext, $allowed_types)) {
                if (move_uploaded_file($_FILES["business_permit"]["tmp_name"], $target_file)) {
                    $permit_url = $target_file;
                } else {
                    $error_msg = "Sorry, there was an error uploading your file.";
                }
            } else {
                $error_msg = "Sorry, only JPG, JPEG, PNG & PDF files are allowed.";
            }
        }

        // --- Insert into database ---
        if (empty($error_msg)) {
            $sql = "INSERT INTO restaurants (user_id, name, cuisine, address, business_type, details, business_permit_url, latitude, longitude, operating_hours, is_open) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '9:00 AM - 9:00 PM', 1)";
            
            if ($stmt = mysqli_prepare($link, $sql)) {
                // cuisine is not in the form, so let's use business_type for now.
                $cuisine = $business_type; 
                mysqli_stmt_bind_param($stmt, "issssssdd", $user_id, $restaurant_name, $cuisine, $address, $business_type, $details, $permit_url, $latitude, $longitude);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Redirect to store dashboard on success
                    header("location: store/index.php");
                    exit;
                } else {
                    $error_msg = "Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
    mysqli_close($link);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Your Store - Foodie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-2xl w-full bg-white p-8 md:p-10 rounded-xl shadow-lg">
            <div class="text-center">
                <a href="index.php" class="text-3xl font-bold text-orange-600">Foodie</a>
                <h2 class="mt-4 text-2xl font-extrabold text-gray-900">Welcome! Let's set up your store.</h2>
                <p class="mt-2 text-gray-600">Complete your profile to start receiving orders.</p>
            </div>
            <?php if(!empty($error_msg)): ?>
                <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert"><?php echo $error_msg; ?></div>
            <?php endif; ?>
            <form class="mt-8 space-y-6" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
                <div>
                    <label for="restaurant-name" class="block text-sm font-medium text-gray-700">Restaurant Name</label>
                    <input type="text" name="restaurant_name" id="restaurant-name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-orange-500 focus:border-orange-500">
                </div>
                 <div>
                    <label for="business-type" class="block text-sm font-medium text-gray-700">Business Type</label>
                    <select id="business-type" name="business_type" required class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                        <option>Restaurant</option>
                        <option>Catering</option>
                        <option>RestoBar</option>
                        <option>Karenderya</option>
                        <option>Self-Employed</option>
                    </select>
                </div>
                 <div>
                    <label for="business-permit" class="block text-sm font-medium text-gray-700">Business Permit (Optional)</label>
                    <input type="file" name="business_permit" id="business-permit" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                </div>
                 <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">Location</label>
                    <textarea name="address" id="address" rows="3" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500"></textarea>
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                    <button type="button" id="detect-location-btn" class="mt-2 text-sm text-orange-600 hover:underline">Detect My Location</button>
                </div>
                <div id="map-container" class="w-full h-64 bg-gray-200 rounded-lg hidden flex items-center justify-center text-gray-500">Map will be displayed here.</div>
                 <div>
                    <label for="details" class="block text-sm font-medium text-gray-700">Details about your Business</label>
                    <textarea name="details" id="details" rows="4" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500" placeholder="e.g., We serve the best authentic Filipino breakfast..."></textarea>
                </div>
                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">Submit & Go to Dashboard</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Geolocation and Map Logic
            const detectLocationBtn = document.getElementById('detect-location-btn');
            const addressTextarea = document.getElementById('address');
            const mapContainer = document.getElementById('map-container');
            const latInput = document.getElementById('latitude');
            const lonInput = document.getElementById('longitude');

            function updateMap(lat, lon) {
                latInput.value = lat;
                lonInput.value = lon;
                const bbox = `${lon - 0.01},${lat - 0.01},${lon + 0.01},${lat + 0.01}`;
                mapContainer.innerHTML = `<iframe width="100%" height="100%" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat},${lon}" style="border: 1px solid black; border-radius: 0.5rem;"></iframe>`;
                mapContainer.classList.remove('hidden');
            }

            detectLocationBtn.addEventListener('click', () => {
                detectLocationBtn.textContent = 'Detecting...';
                fetch('https://ipapi.co/json/')
                    .then(response => response.json())
                    .then(data => {
                        if (data && !data.error) {
                            const fullAddress = `${data.city}, ${data.region}, ${data.country_name}`;
                            addressTextarea.value = fullAddress;
                            updateMap(data.latitude, data.longitude);
                        } else {
                            alert('Could not detect location.');
                        }
                    })
                    .catch(() => alert('Could not detect location.'))
                    .finally(() => detectLocationBtn.textContent = 'Detect My Location');
            });
        });
    </script>
     <script src="js/script.js"></script>
</body>
</html>
