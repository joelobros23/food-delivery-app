<?php
// settings.php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'customer') {
    header("location: login.php");
    exit;
}
$active_page = 'settings'; 

require_once "app_config.php";
$user_details = null;
$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// FIX: Fetch all necessary columns including phone_number and complete_address
$sql = "SELECT full_name, email, phone_number, complete_address, show_activities FROM users WHERE id = ?";
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
                    <!-- Main Settings Menu -->
                    <div id="settings-menu">
                         <h1 class="text-3xl font-bold text-gray-800 mb-6">Settings</h1>
                         <div class="bg-white p-4 rounded-lg shadow-md space-y-1">
                            <a href="#" data-target="personal-view" class="settings-btn flex justify-between items-center p-4 hover:bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800">Personal Details</span><i data-lucide="chevron-right" class="text-gray-400"></i></a>
                            <a href="contact_details.php" class="flex justify-between items-center p-4 hover:bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800">Contact Details</span><i data-lucide="chevron-right" class="text-gray-400"></i></a>
                            <a href="#" data-target="security-view" class="settings-btn flex justify-between items-center p-4 hover:bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800">Security</span><i data-lucide="chevron-right" class="text-gray-400"></i></a>
                            <a href="#" data-target="privacy-view" class="settings-btn flex justify-between items-center p-4 hover:bg-gray-50 rounded-lg"><span class="font-semibold text-gray-800">Privacy</span><i data-lucide="chevron-right" class="text-gray-400"></i></a>
                         </div>
                    </div>

                    <!-- Personal Details View -->
                    <div id="personal-view" class="settings-view hidden">
                        <button class="back-to-menu mb-6 flex items-center text-gray-600 hover:text-orange-600 font-semibold"><i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i> Back</button>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Personal Details</h2>
                            <form id="personal-form" class="space-y-6">
                                <input type="hidden" name="action" value="update_personal">
                                <div>
                                    <label for="full-name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <input type="text" name="full_name" id="full-name" value="<?php echo htmlspecialchars($user_details['full_name']); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
                                </div>
                                <div id="personal-message" class="text-sm"></div>
                                <div class="text-right pt-4">
                                    <button type="submit" class="px-6 py-2 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- RESTORED: Security View -->
                    <div id="security-view" class="settings-view hidden">
                        <button class="back-to-menu mb-6 flex items-center text-gray-600 hover:text-orange-600 font-semibold"><i data-lucide="arrow-left" class="w-5 h-5 mr-2"></i> Back</button>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6">Security</h2>
                             <form id="security-form" class="space-y-6">
                                <input type="hidden" name="action" value="update_security">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Email Address</label>
                                    <p class="text-lg text-gray-800 mt-1"><?php echo htmlspecialchars($user_details['email']); ?></p>
                                </div>
                                <div>
                                    <label for="current-password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                    <input type="password" name="current_password" id="current-password" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm" placeholder="••••••••">
                                </div>
                                <div>
                                    <label for="new-password" class="block text-sm font-medium text-gray-700">New Password</label>
                                    <input type="password" name="new_password" id="new-password" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm">
                                </div>
                                <div id="security-message" class="text-sm"></div>
                                <div class="text-right pt-4">
                                    <button type="submit" class="px-6 py-2 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- RESTORED: Privacy View -->
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
    
    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                <i data-lucide="check" class="h-6 w-6 text-green-600"></i>
            </div>
            <h3 id="modal-message" class="text-lg font-medium text-gray-900">Successfully Saved!</h3>
            <div class="mt-4">
                <button type="button" id="modal-ok-btn" class="w-full px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700">Okay</button>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menu = document.getElementById('settings-menu');
            const views = document.querySelectorAll('.settings-view');
            const menuButtons = document.querySelectorAll('.settings-btn');
            const backButtons = document.querySelectorAll('.back-to-menu');
            const successModal = document.getElementById('success-modal');
            const modalMessage = document.getElementById('modal-message');
            const modalOkBtn = document.getElementById('modal-ok-btn');

            modalOkBtn.addEventListener('click', () => successModal.classList.add('hidden'));
            
            menuButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = button.dataset.target;
                    const targetView = document.getElementById(targetId);
                    menu.classList.add('hidden');
                    views.forEach(view => view.classList.add('hidden'));
                    if(targetView) {
                        targetView.classList.remove('hidden');
                    }
                });
            });

            backButtons.forEach(button => {
                button.addEventListener('click', (e) => { e.preventDefault(); views.forEach(view => view.classList.add('hidden')); menu.classList.remove('hidden'); });
            });

            function handleFormSubmit(formId, messageId) {
                const form = document.getElementById(formId);
                const messageDiv = document.getElementById(messageId);
                
                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        if(messageDiv) messageDiv.textContent = '';
                        const formData = new FormData(this);

                        fetch('customer_dashboard_settings/manage_settings.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if(data.status === 'success') {
                                modalMessage.textContent = data.message;
                                successModal.classList.remove('hidden');
                                lucide.createIcons();
                                if (formId === 'security-form') form.reset();
                            } else {
                                if(messageDiv) messageDiv.innerHTML = `<p class="text-red-600">${data.message}</p>`;
                            }
                        });
                    });
                }
            }

            handleFormSubmit('personal-form', 'personal-message');
            handleFormSubmit('security-form', 'security-message');
            handleFormSubmit('privacy-form', 'privacy-message');
        });
    </script>
</body>
</html>
