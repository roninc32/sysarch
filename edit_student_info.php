<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$id_number = $_SESSION["username"];
$sql = "SELECT * FROM users WHERE id_number='$id_number'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $profile_image = isset($row["profile_image"]) && !empty($row["profile_image"]) ? $row["profile_image"] : 'assets/images/profile.jpg';
    $last_name = $row["last_name"];
    $first_name = $row["first_name"];
    $middle_name = $row["middle_name"];
    $course_level = $row["course_level"];
    $email = $row["email"];
    $course = $row["course"];
    $address = $row["address"];
} else {
    echo "No user found.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $last_name = $_POST["last_name"];
    $first_name = $_POST["first_name"];
    $middle_name = $_POST["middle_name"];
    $course_level = $_POST["course_level"];
    $email = $_POST["email"];
    $course = $_POST["course"];
    $address = $_POST["address"];

    $sql = "UPDATE users SET last_name='$last_name', first_name='$first_name', middle_name='$middle_name', course_level='$course_level', email='$email', course='$course', address='$address' WHERE id_number='$id_number'";

    if ($conn->query($sql) === TRUE) {
        $_SESSION["update_success"] = true;
        // Redefine the query to fetch the updated information from the database
        $sql = "SELECT * FROM users WHERE id_number='$id_number'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $profile_image = isset($row["profile_image"]) && !empty($row["profile_image"]) ? $row["profile_image"] : 'assets/images/profile.jpg';
            $last_name = $row["last_name"];
            $first_name = $row["first_name"];
            $middle_name = $row["middle_name"];
            $course_level = $row["course_level"];
            $email = $row["email"];
            $course = $row["course"];
            $address = $row["address"];
        }
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
        /* Dark mode variables */
        :root {
            --bg-primary: #f0f9ff;
            --bg-secondary: #dbeafe;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
            --card-bg: #eff6ff;
            --card-header: #bfdbfe;
            --nav-bg: #ffffff;
            --nav-text: #111827;
            --nav-hover-bg: #374151;
            --nav-hover-text: #ffffff;
            --button-primary: #3b82f6;
            --button-hover: #2563eb;
            --button-text: #ffffff;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --input-bg: #ffffff;
            --input-border: #d1d5db;
            --input-text: #111827;
            --input-focus: #3b82f6;
        }

        .dark {
            --bg-primary: #111827;
            --bg-secondary: #1f2937;
            --text-primary: #f9fafb;
            --text-secondary: #e5e7eb;
            --card-bg: #1f2937;
            --card-header: #374151;
            --nav-bg: #111827;
            --nav-text: #f9fafb;
            --nav-hover-bg: #60a5fa;
            --nav-hover-text: #111827;
            --button-primary: #3b82f6;
            --button-hover: #60a5fa;
            --button-text: #f9fafb;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --input-bg: #374151;
            --input-border: #4b5563;
            --input-text: #f9fafb;
            --input-focus: #60a5fa;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
        }

        nav {
            background-color: var(--nav-bg);
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .nav-link {
            color: var(--nav-text);
        }

        .nav-link:hover {
            background-color: var(--nav-hover-bg);
            color: var(--nav-hover-text);
        }

        .card {
            background-color: var(--card-bg);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px var(--shadow-color);
        }

        .card-header {
            background-color: var(--card-header);
        }

        .btn-primary {
            background-color: var(--button-primary);
            color: var(--button-text);
        }

        .btn-primary:hover {
            background-color: var(--button-hover);
        }
        
        input, select, textarea {
            background-color: var(--input-bg);
            border-color: var(--input-border);
            color: var(--input-text);
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: var(--input-focus);
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }
        
        label {
            color: var(--text-secondary);
        }
        
        /* Custom animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        /* Success message animation */
        @keyframes slideInDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .success-message {
            animation: slideInDown 0.5s ease forwards;
        }
        
        /* Toggle switch styling */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 52px;
            height: 26px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #3b82f6;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        /* Modal styling */
        .modal {
            transition: all 0.3s ease-out;
        }
        
        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }
        
        /* Form input focus animations */
        .form-input-animate {
            transition: all 0.3s;
        }
        
        .form-input-animate:focus {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <nav class="sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative flex items-center justify-between h-16">
                <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
                    <button type="button"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                        aria-controls="mobile-menu" aria-expanded="false" id="mobile-menu-button">
                        <span class="sr-only">Open main menu</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 flex items-center justify-center sm:items-stretch sm:justify-start">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-xl font-bold hidden lg:block">Student Portal</span>
                    </div>
                    <div class="hidden sm:block sm:ml-6">
                        <div class="flex space-x-4">
                            <a href="dashboard.php"
                                class="nav-link px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center">
                                <i class="fas fa-home mr-2"></i> Home
                            </a>
                            <a href="edit_student_info.php"
                                class="nav-link px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center bg-blue-500 text-white">
                                <i class="fas fa-user-edit mr-2"></i> Profile
                            </a>
                            <a href="history.php"
                                class="nav-link px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center">
                                <i class="fas fa-history mr-2"></i> History
                            </a>
                            <a href="reservation.php"
                                class="nav-link px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center">
                                <i class="fas fa-calendar-alt mr-2"></i> Reservation
                            </a>
                        </div>
                    </div>
                </div>
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0 space-x-3">
                    <!-- Dark Mode Toggle -->
                    <div class="flex items-center mr-4">
                        <span class="mr-2 text-sm"><i class="fas fa-sun"></i></span>
                        <label class="toggle-switch">
                            <input type="checkbox" id="darkModeToggle">
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="ml-2 text-sm"><i class="fas fa-moon"></i></span>
                    </div>
                    
                    <!-- Notifications -->
                    <div class="relative">
                        <button
                            class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white"
                            id="notifications-menu" aria-expanded="false" aria-haspopup="true">
                            <span class="sr-only">View notifications</span>
                            <i class="fas fa-bell text-lg"></i>
                        </button>
                        <!-- Dropdown content -->
                        <div class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg py-1 bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 hidden notifications-dropdown"
                            role="menu" aria-orientation="vertical" aria-labelledby="notifications-menu">
                            <div class="px-4 py-2 text-center border-b border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-medium">Notifications</p>
                            </div>
                            <div class="px-4 py-2 text-sm text-center text-gray-500 dark:text-gray-400">
                                No new notifications
                            </div>
                        </div>
                    </div>
                    
                    <!-- User menu -->
                    <div class="relative ml-3">
                        <div>
                            <button type="button" class="flex text-sm rounded-full focus:outline-none" id="user-menu-button">
                                <img class="h-8 w-8 rounded-full object-cover border-2 border-blue-500" src="<?php echo $profile_image; ?>" alt="Profile">
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sm:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="dashboard.php"
                    class="nav-link block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-home mr-2"></i> Home
                </a>
                <a href="edit_student_info.php"
                    class="nav-link block px-3 py-2 rounded-md text-base font-medium bg-blue-500 text-white">
                    <i class="fas fa-user-edit mr-2"></i> Profile
                </a>
                <a href="history.php"
                    class="nav-link block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-history mr-2"></i> History
                </a>
                <a href="reservation.php"
                    class="nav-link block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-calendar-alt mr-2"></i> Reservation
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4 flex-grow">
        <div class="max-w-4xl mx-auto">
            <div class="card rounded-lg shadow-lg overflow-hidden animate-fadeIn">
                <div class="card-header p-6">
                    <h1 class="text-xl md:text-2xl font-bold text-center flex items-center justify-center">
                        <i class="fas fa-user-edit mr-3"></i>Edit Student Profile
                    </h1>
                </div>
                
                <?php if (isset($_SESSION["update_success"]) && $_SESSION["update_success"]): ?>
                <div id="success-message" class="bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 p-4 mb-6 mx-6 rounded success-message">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-500 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm md:text-base font-medium text-green-800 dark:text-green-200">
                                Your profile has been updated successfully!
                            </p>
                        </div>
                        <button type="button" class="ml-auto text-green-500" onclick="document.getElementById('success-message').classList.add('hidden')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <?php unset($_SESSION["update_success"]); ?>
                <?php endif; ?>
                
                <div class="p-6">
                    <div class="flex justify-center mb-8">
                        <div class="relative group">
                            <img src="<?php echo $profile_image; ?>" alt="Profile Image" 
                                class="w-36 h-36 rounded-full object-cover border-4 border-blue-300 dark:border-blue-700 transition-all duration-300 group-hover:opacity-80">
                            <button onclick="toggleEditProfile()" class="absolute bottom-0 right-0 bg-blue-500 text-white rounded-full w-10 h-10 flex items-center justify-center opacity-90 hover:opacity-100 transition-all hover:bg-blue-600">
                                <i class="fas fa-camera"></i>
                            </button>
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300">
                                <span class="bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm">Change Photo</span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="post" action="edit_student_info.php" class="space-y-6">
                        <div class="bg-white/50 dark:bg-gray-800/50 p-5 rounded-lg">
                            <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-200 dark:border-gray-700 flex items-center">
                                <i class="fas fa-id-card mr-2 text-blue-500"></i>
                                Personal Information
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="last_name" class="block text-sm font-medium mb-1">
                                        Last Name
                                    </label>
                                    <input type="text" id="last_name" name="last_name" required 
                                        value="<?php echo htmlspecialchars($last_name); ?>" 
                                        class="form-input-animate w-full px-3 py-2 border rounded-md shadow-sm focus:ring-2 focus:ring-opacity-50">
                                </div>
                                <div>
                                    <label for="first_name" class="block text-sm font-medium mb-1">
                                        First Name
                                    </label>
                                    <input type="text" id="first_name" name="first_name" required 
                                        value="<?php echo htmlspecialchars($first_name); ?>" 
                                        class="form-input-animate w-full px-3 py-2 border rounded-md shadow-sm focus:ring-2 focus:ring-opacity-50">
                                </div>
                                <div>
                                    <label for="middle_name" class="block text-sm font-medium mb-1">
                                        Middle Name
                                    </label>
                                    <input type="text" id="middle_name" name="middle_name" 
                                        value="<?php echo htmlspecialchars($middle_name); ?>" 
                                        class="form-input-animate w-full px-3 py-2 border rounded-md shadow-sm focus:ring-2 focus:ring-opacity-50">
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium mb-1">
                                        Email Address
                                    </label>
                                    <input type="email" id="email" name="email" required 
                                        value="<?php echo htmlspecialchars($email); ?>" 
                                        class="form-input-animate w-full px-3 py-2 border rounded-md shadow-sm focus:ring-2 focus:ring-opacity-50">
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white/50 dark:bg-gray-800/50 p-5 rounded-lg">
                            <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-200 dark:border-gray-700 flex items-center">
                                <i class="fas fa-graduation-cap mr-2 text-blue-500"></i>
                                Academic Information
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="course" class="block text-sm font-medium mb-1">
                                        Course
                                    </label>
                                    <select id="course" name="course" required 
                                        class="form-input-animate w-full px-3 py-2 border rounded-md shadow-sm focus:ring-2 focus:ring-opacity-50">
                                        <option value="BSCS" <?php if ($course == 'BSCS') echo 'selected'; ?>>Bachelor of Science in Computer Science</option>
                                        <option value="BSIT" <?php if ($course == 'BSIT') echo 'selected'; ?>>Bachelor of Science in Information Technology</option>
                                        <option value="BSSE" <?php if ($course == 'BSSE') echo 'selected'; ?>>Bachelor of Science in Software Engineering</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="course_level" class="block text-sm font-medium mb-1">
                                        Year Level
                                    </label>
                                    <select id="course_level" name="course_level" required 
                                        class="form-input-animate w-full px-3 py-2 border rounded-md shadow-sm focus:ring-2 focus:ring-opacity-50">
                                        <option value="1st Year" <?php if ($course_level == '1st Year') echo 'selected'; ?>>1st Year</option>
                                        <option value="2nd Year" <?php if ($course_level == '2nd Year') echo 'selected'; ?>>2nd Year</option>
                                        <option value="3rd Year" <?php if ($course_level == '3rd Year') echo 'selected'; ?>>3rd Year</option>
                                        <option value="4th Year" <?php if ($course_level == '4th Year') echo 'selected'; ?>>4th Year</option>
                                        <option value="5th Year" <?php if ($course_level == '5th Year') echo 'selected'; ?>>5th Year</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white/50 dark:bg-gray-800/50 p-5 rounded-lg">
                            <h3 class="font-semibold text-lg mb-4 pb-2 border-b border-gray-200 dark:border-gray-700 flex items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>
                                Contact Information
                            </h3>
                            
                            <div>
                                <label for="address" class="block text-sm font-medium mb-1">
                                    Complete Address
                                </label>
                                <textarea id="address" name="address" required rows="3"
                                    class="form-input-animate w-full px-3 py-2 border rounded-md shadow-sm focus:ring-2 focus:ring-opacity-50"><?php echo htmlspecialchars($address); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="flex justify-between pt-4">
                            <a href="dashboard.php" class="px-4 py-2 rounded-md bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back to Dashboard
                            </a>
                            <button type="submit" class="btn-primary px-6 py-2 rounded-md flex items-center">
                                <i class="fas fa-save mr-2"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Profile Picture Modal -->
    <div id="editProfileModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
        <div class="modal-content bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold flex items-center">
                    <i class="fas fa-camera mr-2 text-blue-500"></i>
                    Update Profile Picture
                </h2>
                <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" onclick="toggleEditProfile()">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <form method="post" action="edit_profile.php" enctype="multipart/form-data" class="space-y-4">
                <div class="flex flex-col items-center justify-center py-4">
                    <div class="preview-container mb-4 h-40 w-40 rounded-full bg-gray-200 dark:bg-gray-700 border-2 border-dashed border-gray-400 dark:border-gray-500 flex items-center justify-center overflow-hidden">
                        <img id="imagePreview" src="<?php echo $profile_image; ?>" alt="Preview" class="h-full w-full object-cover hidden">
                        <span id="previewPlaceholder" class="text-gray-500 dark:text-gray-400 text-center">
                            <i class="fas fa-image text-3xl mb-2"></i><br>
                            Image Preview
                        </span>
                    </div>
                    
                    <label for="profile_image" class="btn-primary cursor-pointer px-4 py-2 rounded-md inline-flex items-center">
                        <i class="fas fa-upload mr-2"></i>
                        Choose Image
                    </label>
                    <input type="file" id="profile_image" name="profile_image" required class="hidden" accept="image/*" onchange="previewImage()">
                    
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Recommended: Square image, max 2MB.
                    </p>
                </div>
                
                <div class="flex justify-end space-x-3 pt-2">
                    <button type="button" onclick="toggleEditProfile()" 
                        class="px-4 py-2 rounded-md bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="btn-primary px-4 py-2 rounded-md">
                        Upload & Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-auto py-4">
        <div class="container mx-auto px-4">
            <div class="text-center text-sm">
                <p>&copy; <?php echo date('Y'); ?> Student Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
        
        // Dark mode toggle functionality
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;
        
        // Check for saved theme preference or use system preference
        const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const savedTheme = localStorage.getItem('theme');
        
        if (savedTheme === 'dark' || (!savedTheme && darkModeMediaQuery.matches)) {
            html.classList.add('dark');
            darkModeToggle.checked = true;
        }
        
        // Toggle theme when button is clicked
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        });
        
        // Notifications toggle
        document.getElementById('notifications-menu').addEventListener('click', function() {
            document.querySelector('.notifications-dropdown').classList.toggle('hidden');
        });
        
        // Close dropdowns when clicking outside
        window.addEventListener('click', function(event) {
            const notificationsMenu = document.getElementById('notifications-menu');
            const notificationsDropdown = document.querySelector('.notifications-dropdown');
            
            if (!notificationsMenu.contains(event.target) && !notificationsDropdown.contains(event.target)) {
                notificationsDropdown.classList.add('hidden');
            }
        });
        
        // Toggle profile picture edit modal
        function toggleEditProfile() {
            const modal = document.getElementById('editProfileModal');
            modal.classList.toggle('hidden');
            
            // Prevent scrolling when modal is open
            if (!modal.classList.contains('hidden')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        }
        
        // Image preview functionality
        function previewImage() {
            const input = document.getElementById('profile_image');
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('previewPlaceholder');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Auto-hide success message after 5 seconds
        if (document.getElementById('success-message')) {
            setTimeout(function() {
                const successMessage = document.getElementById('success-message');
                successMessage.style.opacity = '0';
                successMessage.style.transform = 'translateY(-20px)';
                successMessage.style.transition = 'opacity 0.5s, transform 0.5s';
                
                setTimeout(function() {
                    successMessage.classList.add('hidden');
                }, 500);
            }, 5000);
        }
    </script>
</body>
</html>
