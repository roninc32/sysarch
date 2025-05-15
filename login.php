<?php
include 'config.php';

session_start();
if (isset($_SESSION["logout_message"])) {
    $logout_message = $_SESSION["logout_message"];
    unset($_SESSION["logout_message"]);
} else {
    $logout_message = "";
}

$passwordError = "";
$idError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check for admin credentials
    if ($username == 'admin' && $password == 'admin') {
        // Admin login successful
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin_dashboard.php");
        exit();
    }

    $id_number = $_POST["username"]; // Change to id_number
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE id_number='$id_number'"; // Change to id_number
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row["password"])) {
            $_SESSION["username"] = $id_number; // Change to id_number
            $_SESSION["is_admin"] = isset($row["is_admin"]) ? $row["is_admin"] : 0;
            
            if ($_SESSION["is_admin"] == 1) {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $passwordError = "Invalid password.";
        }
    } else {
        $idError = "No user found with that ID number.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | UC Sit-in Monitoring</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 font-['Inter']">
    <div class="min-h-screen flex flex-col">
        <!-- Navigation Bar -->
        <nav class="bg-gradient-to-r from-indigo-800 to-indigo-700 text-white py-4 px-6 relative">
            <div class="absolute inset-0 bg-black opacity-10 z-0"></div>
            <div class="relative z-10 container mx-auto">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <img src="assets/images/uc-main-logo.jpg" alt="UC Logo" class="w-12 h-12 rounded-full border-2 border-white/20 shadow-lg">
                        <div>
                            <h1 class="text-xl font-bold tracking-tight leading-tight">Sit-in Monitoring System</h1>
                            <p class="text-indigo-200 text-sm">College of Computer Studies</p>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-1 p-6 flex items-center justify-center">
            <div class="w-full max-w-sm">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="text-center mb-6">
                        <img src="assets/images/ccs-logo.png" alt="CCS Logo" class="w-20 h-20 mx-auto mb-4">
                        <h2 class="text-2xl font-bold text-gray-900 mb-1">Welcome Back!</h2>
                        <p class="text-gray-500 text-sm">Please sign in to continue</p>
                    </div>
                    
                    <?php if ($logout_message): ?>
                        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-3 rounded-md mb-4 text-sm" role="alert">
                            <p class="font-medium"><?php echo $logout_message; ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="login.php" class="space-y-4">
                        <div>
                            <div class="relative">
                                <input type="text" id="username" name="username" required 
                                    value="<?php echo isset($id_number) ? htmlspecialchars($id_number) : ''; ?>" 
                                    class="block w-full px-3 pt-4 pb-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm peer"
                                    placeholder=" ">
                                <label for="username" 
                                    class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-focus:text-indigo-600 peer-focus:dark:text-indigo-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">
                                    ID Number
                                </label>
                            </div>
                            <?php if ($idError): ?>
                                <p class="mt-1 text-xs text-red-600"><?php echo $idError; ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div class="relative">
                                <input type="password" id="password" name="password" required 
                                    class="block w-full px-3 pt-4 pb-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm peer"
                                    placeholder=" ">
                                <label for="password" 
                                    class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-focus:text-indigo-600 peer-focus:dark:text-indigo-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">
                                    Password
                                </label>
                            </div>
                            <?php if ($passwordError): ?>
                                <p class="mt-1 text-xs text-red-600"><?php echo $passwordError; ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <button type="submit" name="login" 
                                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-150 text-sm">
                                Sign In
                            </button>
                        </div>

                        <div class="text-center">
                            <a href="register.php" 
                                class="text-indigo-600 hover:text-indigo-700 text-xs font-medium transition-colors duration-150">
                                Don't have an account? Create one
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
