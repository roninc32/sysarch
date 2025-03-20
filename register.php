<?php
session_start();
include 'config.php';

$passwordError = "";
$accountError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = $_POST["id_number"];
    $last_name = $_POST["last_name"];
    $first_name = $_POST["first_name"];
    $middle_name = $_POST["middle_name"];
    $course_level = $_POST["course_level"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $email = $_POST["email"];
    $course = $_POST["course"];
    $address = $_POST["address"];
    $profile_image = 'assets/images/profile.jpg'; // Set default profile image

    if (strlen($password) < 8) {
        $passwordError = "Password must be at least 8 characters long.";
    } else if ($password !== $confirm_password) {
        $passwordError = "Passwords do not match.";
    } else {
        // Check if the account already exists
        $sql_check = "SELECT * FROM users WHERE id_number=? OR email=?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("ss", $id_number, $email);
        $stmt->execute();
        $result_check = $stmt->get_result();

        if ($result_check->num_rows > 0) {
            $accountError = "An account with this ID number or email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (id_number, last_name, first_name, middle_name, course_level, password, email, course, address, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssss", $id_number, $last_name, $first_name, $middle_name, $course_level, $hashed_password, $email, $course, $address, $profile_image);

            if ($stmt->execute()) {
                $_SESSION['registration_success'] = true;
                header("Location: login.php");
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | UC Sit-in Monitoring</title>
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
            <div class="w-full max-w-2xl">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="text-center mb-6">
                        <img src="assets/images/ccs-logo.png" alt="CCS Logo" class="w-20 h-20 mx-auto mb-4">
                        <h2 class="text-2xl font-bold text-gray-900 mb-1">Create Account</h2>
                        <p class="text-gray-500 text-sm">Please fill in the details to register</p>
                    </div>

                    <form method="post" action="register.php" onsubmit="return validateForm()" class="space-y-4">
                        <div class="relative">
                            <input type="text" id="id_number" name="id_number" required 
                                class="block w-full px-3 pt-4 pb-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm peer"
                                placeholder=" ">
                            <label for="id_number" class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-focus:text-indigo-600 peer-focus:dark:text-indigo-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">
                                ID Number
                            </label>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div class="relative">
                                <input type="text" id="last_name" name="last_name" required 
                                    class="block w-full px-3 pt-4 pb-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm peer"
                                    placeholder=" ">
                                <label for="last_name" class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-focus:text-indigo-600 peer-focus:dark:text-indigo-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">
                                    Last Name
                                </label>
                            </div>
                            <div class="relative">
                                <input type="text" id="first_name" name="first_name" required 
                                    class="block w-full px-3 pt-4 pb-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm peer"
                                    placeholder=" ">
                                <label for="first_name" class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-focus:text-indigo-600 peer-focus:dark:text-indigo-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">
                                    First Name
                                </label>
                            </div>
                            <div class="relative">
                                <input type="text" id="middle_name" name="middle_name"
                                    class="block w-full px-3 pt-4 pb-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm peer"
                                    placeholder=" ">
                                <label for="middle_name" class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-focus:text-indigo-600 peer-focus:dark:text-indigo-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">
                                    Middle Name
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="relative">
                                <select id="course_level" name="course_level" required 
                                    class="block w-full px-3 pt-4 pb-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm">
                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                    <option value="5th Year">5th Year</option>
                                </select>
                                <label class="absolute text-sm text-gray-500 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 left-1">
                                    Year Level
                                </label>
                            </div>
                            <div class="relative">
                                <select id="course" name="course" required 
                                    class="block w-full px-3 pt-4 pb-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm">
                                    <option value="Computer Science">BSCS</option>
                                    <option value="Information Technology">BSIT</option>
                                    <option value="Software Engineering">BSSE</option>
                                </select>
                                <label class="absolute text-sm text-gray-500 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 left-1">
                                    Course
                                </label>
                            </div>
                        </div>

                        <div class="relative">
                            <input type="email" id="email" name="email" required 
                                class="block w-full px-3 pt-4 pb-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm peer"
                                placeholder=" ">
                            <label for="email" class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-focus:text-indigo-600 peer-focus:dark:text-indigo-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">
                                Email
                            </label>
                        </div>

                        <div class="relative">
                            <textarea id="address" name="address" required 
                                class="block w-full px-3 pt-4 pb-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm peer min-h-[80px]"
                                placeholder=" "></textarea>
                            <label for="address" class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-focus:text-indigo-600 peer-focus:dark:text-indigo-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">
                                Address
                            </label>
                        </div>

                        <div class="relative">
                            <input type="password" id="password" name="password" required 
                                class="block w-full px-3 pt-4 pb-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm peer"
                                placeholder=" ">
                            <label for="password" class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-focus:text-indigo-600 peer-focus:dark:text-indigo-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">
                                Password
                            </label>
                        </div>

                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                class="block w-full px-3 pt-4 pb-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-sm peer"
                                placeholder=" ">
                            <label for="confirm_password" class="absolute text-sm text-gray-500 duration-300 transform -translate-y-4 scale-75 top-2 z-10 origin-[0] bg-white px-2 peer-focus:px-2 peer-focus:text-indigo-600 peer-focus:dark:text-indigo-500 peer-placeholder-shown:scale-100 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:top-1/2 peer-focus:top-2 peer-focus:scale-75 peer-focus:-translate-y-4 left-1">
                                Confirm Password
                            </label>
                        </div>

                        <?php if (isset($passwordError) || isset($accountError)): ?>
                            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded-md" role="alert">
                                <p class="text-sm"><?php echo $passwordError . $accountError; ?></p>
                            </div>
                        <?php endif; ?>

                        <div>
                            <button type="submit" 
                                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-150 text-sm">
                                Register
                            </button>
                        </div>

                        <div class="text-center">
                            <a href="login.php" 
                                class="text-indigo-600 hover:text-indigo-700 text-xs font-medium transition-colors duration-150">
                                Already have an account? Sign in
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function validateForm() {
            var password = document.getElementById("password").value;
            var confirm_password = document.getElementById("confirm_password").value;
            
            if (password.length < 8) {
                alert("Password must be at least 8 characters long.");
                return false;
            }
            if (password !== confirm_password) {
                alert("Passwords do not match.");
                return false;
            }
            return true;
        }

        // Check for success message in PHP session
        <?php
        if (isset($_SESSION['registration_success']) && $_SESSION['registration_success']) {
            echo 'alert("Registration successful! You can now login.");';
            unset($_SESSION['registration_success']);
        }
        ?>
    </script>
</body>
</html>