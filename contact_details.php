<?php
// contact_details.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: login.php");
    exit;
}
$active_page = 'settings'; 

require_once "app_config.php";
$user_details = null;
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Fetch phone number and complete address
$sql = "SELECT phone_number, complete_address FROM users WHERE id = ?";
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
    <title>Contact Details - Foodie</title>
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
                     <a href="settings.php" class="mb-6 flex items-center text-gray-600 hover:text-orange-600 font-semibold"><i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i> Back to Settings</a>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6">Contact Details</h2>
                        <form id="contact-form" class="space-y-6">
                            <input type="hidden" name="action" value="update_contact">
                            <div>
                                <label for="phone-number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="tel" name="phone_number" id="phone-number" value="<?php echo htmlspecialchars($user_details['phone_number'] ?? ''); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm" placeholder="e.g., 09123456789">
                            </div>
                            <div>
                                <label for="complete-address" class="block text-sm font-medium text-gray-700">Complete Address</label>
                                <textarea name="complete_address" id="complete-address" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm" placeholder="House number, Street, Barangay"><?php echo htmlspecialchars($user_details['complete_address'] ?? ''); ?></textarea>
                            </div>
                            <div class="text-right pt-4">
                                <button type="submit" class="px-6 py-2 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php require_once 'customer_dashboard_settings/bottom_nav.php'; ?>
        </div>
    </div>
    
    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                <i data-lucide="check" class="h-6 w-6 text-green-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900">Successfully Saved!</h3>
            <div class="mt-4">
                <button type="button" id="modal-ok-btn" class="w-full px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700">Okay</button>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contact-form');
            const successModal = document.getElementById('success-modal');
            const modalOkBtn = document.getElementById('modal-ok-btn');

            modalOkBtn.addEventListener('click', () => successModal.classList.add('hidden'));

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                fetch('customer_dashboard_settings/manage_contact.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            successModal.classList.remove('hidden');
                            lucide.createIcons();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
            });
        });
    </script>
</body>
</html>
