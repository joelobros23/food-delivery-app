<?php
// signup.php - User registration page

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
session_start();

if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if ($_SESSION["role"] === 'customer') { header("location: customer_dashboard.php"); } 
    elseif ($_SESSION["role"] === 'rider') { header("location: rider_dashboard.php"); } 
    elseif ($_SESSION["role"] === 'store') { header("location: store/index.php"); } 
    else { header("location: index.php"); }
    exit;
}

require_once "db_connection/config.php";

$name = $email = $password = $confirm_password = $role = "";
$name_err = $email_err = $password_err = $confirm_password_err = $role_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    // --- Validation logic (as before) ---
    if(empty(trim($_POST["name"]))){ $name_err = "Please enter your full name."; } else{ $name = trim($_POST["name"]); }
    if(empty(trim($_POST["email"]))){ $email_err = "Please enter an email."; } else { $sql = "SELECT id FROM users WHERE email = ?"; if($stmt = mysqli_prepare($link, $sql)){ mysqli_stmt_bind_param($stmt, "s", $param_email); $param_email = trim($_POST["email"]); if(mysqli_stmt_execute($stmt)){ mysqli_stmt_store_result($stmt); if(mysqli_stmt_num_rows($stmt) == 1){ $email_err = "This email is already taken."; } else{ $email = trim($_POST["email"]); } } else{ echo "Oops! Something went wrong."; } mysqli_stmt_close($stmt); } }
    if(empty(trim($_POST["password"]))){ $password_err = "Please enter a password."; } elseif(strlen(trim($_POST["password"])) < 6){ $password_err = "Password must have at least 6 characters."; } else{ $password = trim($_POST["password"]); }
    if(empty(trim($_POST["confirm-password"]))){ $confirm_password_err = "Please confirm password."; } else{ $confirm_password = trim($_POST["confirm-password"]); if(empty($password_err) && ($password != $confirm_password)){ $confirm_password_err = "Password did not match."; } }
    if(empty($_POST["role"])){ $role_err = "Please select a role."; } else{ $role = $_POST["role"]; }
    
    // --- Database Insertion ---
    if(empty($name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($role_err)){
        $sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ssss", $param_name, $param_email, $param_password, $param_role);
            $param_name = $name; $param_email = $email; $param_password = password_hash($password, PASSWORD_DEFAULT); $param_role = $role;
            if(mysqli_stmt_execute($stmt)){
                $user_id = mysqli_insert_id($link);
                $_SESSION["loggedin"] = true; $_SESSION["id"] = $user_id; $_SESSION["name"] = $name; $_SESSION["email"] = $email; $_SESSION["role"] = $role;                            
                
                // CORRECTED: Redirect logic for store owners
                if ($role === 'store') {
                    header("location: create_store.php");
                } elseif ($role === 'customer') {
                    header("location: customer_dashboard.php");
                } elseif ($role === 'rider') {
                    header("location: rider_dashboard.php");
                }
                exit;
            } else{ echo "Oops! Something went wrong."; }
            mysqli_stmt_close($stmt);
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
    <title>Create Account - Foodie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg">
            <div>
                <a href="index.php" class="flex justify-center text-3xl font-bold text-orange-600">Foodie</a>
                <h2 class="mt-6 text-center text-2xl font-extrabold text-gray-900">
                    Create your account
                </h2>
            </div>
            <form class="mt-8 space-y-6" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" novalidate>
                <div class="rounded-md shadow-sm -space-y-px">
                     <div>
                        <label for="full-name" class="sr-only">Full Name</label>
                        <input id="full-name" name="name" type="text" autocomplete="name" required class="appearance-none rounded-none relative block w-full px-3 py-3 border <?php echo (!empty($name_err)) ? 'border-red-500' : 'border-gray-300'; ?> placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-orange-500 focus:border-orange-500 focus:z-10 sm:text-sm" placeholder="Full Name" value="<?php echo $name; ?>">
                        <span class="text-red-500 text-xs italic"><?php echo $name_err;?></span>
                    </div>
                    <div>
                        <label for="email-address" class="sr-only">Email address</label>
                        <input id="email-address" name="email" type="email" autocomplete="email" required class="appearance-none rounded-none relative block w-full px-3 py-3 border <?php echo (!empty($email_err)) ? 'border-red-500' : 'border-gray-300'; ?> placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-orange-500 focus:border-orange-500 focus:z-10 sm:text-sm" placeholder="Email address" value="<?php echo $email; ?>">
                         <span class="text-red-500 text-xs italic"><?php echo $email_err;?></span>
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required class="appearance-none rounded-none relative block w-full px-3 py-3 border <?php echo (!empty($password_err)) ? 'border-red-500' : 'border-gray-300'; ?> placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-orange-500 focus:border-orange-500 focus:z-10 sm:text-sm" placeholder="Password">
                         <span class="text-red-500 text-xs italic"><?php echo $password_err;?></span>
                    </div>
                     <div>
                        <label for="confirm-password" class="sr-only">Confirm Password</label>
                        <input id="confirm-password" name="confirm-password" type="password" autocomplete="new-password" required class="appearance-none rounded-none relative block w-full px-3 py-3 border <?php echo (!empty($confirm_password_err)) ? 'border-red-500' : 'border-gray-300'; ?> placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-orange-500 focus:border-orange-500 focus:z-10 sm:text-sm" placeholder="Confirm Password">
                         <span class="text-red-500 text-xs italic"><?php echo $confirm_password_err;?></span>
                    </div>
                </div>
                
                <div class="pt-2">
                     <label class="block text-sm font-medium text-gray-700">Register as a</label>
                    <div class="mt-2 flex items-center space-x-6">
                        <div class="flex items-center">
                            <input id="role-customer" name="role" type="radio" value="customer" checked class="focus:ring-orange-500 h-4 w-4 text-orange-600 border-gray-300">
                            <label for="role-customer" class="ml-2 block text-sm text-gray-900">
                                Customer
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input id="role-rider" name="role" type="radio" value="rider" class="focus:ring-orange-500 h-4 w-4 text-orange-600 border-gray-300">
                            <label for="role-rider" class="ml-2 block text-sm text-gray-900">
                                Rider
                            </label>
                        </div>
                         <div class="flex items-center">
                            <input id="role-store" name="role" type="radio" value="store" class="focus:ring-orange-500 h-4 w-4 text-orange-600 border-gray-300">
                            <label for="role-store" class="ml-2 block text-sm text-gray-900">
                                Store
                            </label>
                        </div>
                    </div>
                    <span class="text-red-500 text-xs italic"><?php echo $role_err;?></span>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                       Create Account
                    </button>
                </div>
            </form>
             <p class="mt-2 text-center text-sm text-gray-600">
                Already have an account?
                <a href="login.php" class="font-medium text-orange-600 hover:text-orange-500">
                    Sign in
                </a>
            </p>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>
