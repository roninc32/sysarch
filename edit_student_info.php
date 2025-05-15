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

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['current_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify passwords match
    if ($new_password !== $confirm_password) {
        $_SESSION["update_error"] = "New passwords do not match.";
    }
    // Check password length (minimum 8 characters)
    elseif (strlen($new_password) < 8) {
        $_SESSION["update_error"] = "Password must be at least 8 characters long.";
    }
    else {
        // Get current password hash from database
        $sql = "SELECT password FROM users WHERE id_number='$id_number'";
        $result = $conn->query($sql);
        $user = $result->fetch_assoc();
        
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in database
            $update_sql = "UPDATE users SET password = ? WHERE id_number = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ss", $hashed_password, $id_number);
            
            if ($stmt->execute()) {
                $_SESSION["update_success"] = true;
                $_SESSION["password_updated"] = true;
            } else {
                $_SESSION["update_error"] = "Failed to update password. Please try again.";
            }
        } else {
            $_SESSION["update_error"] = "Current password is incorrect.";
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: edit_student_info.php");
    exit();
}

// Handle profile image upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    $file = $_FILES["profile_image"];
    
    // Validate file type and size
    if (!in_array($file["type"], $allowed_types)) {
        $_SESSION["update_error"] = "Only JPG, PNG and GIF images are allowed.";
    } elseif ($file["size"] > $max_size) {
        $_SESSION["update_error"] = "Image size must be less than 2MB.";
    } else {
        // Create uploads directory if it doesn't exist
        $upload_dir = "assets/uploads/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file["name"], PATHINFO_EXTENSION);
        $new_filename = 'profile_' . $id_number . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;
        
        // Upload file
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            // Update database with new image path
            $profile_image_path = $target_file;
            $update_sql = "UPDATE users SET profile_image = ? WHERE id_number = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ss", $profile_image_path, $id_number);
            
            if ($stmt->execute()) {
                $_SESSION["update_success"] = true;
                $_SESSION["profile_updated"] = true;
                $profile_image = $profile_image_path; // Update the current page's image
            } else {
                $_SESSION["update_error"] = "Failed to update profile image in database.";
            }
        } else {
            $_SESSION["update_error"] = "Failed to upload image. Please try again.";
        }
    }
}

// Handle regular profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_FILES["profile_image"])) {
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
        /* Color variables for light/dark mode */
        :root {
            --bg-primary: #f9fafb;
            --bg-secondary: #f3f4f6;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --accent-color: #2563eb;
            --accent-hover: #1d4ed8;
            --accent-light: #dbeafe;
            --card-bg: #ffffff;
            --card-border: #e5e7eb;
            --nav-bg: #ffffff;
            --nav-border: #e5e7eb;
            --button-bg: #2563eb;
            --button-hover: #1d4ed8;
            --button-text: #ffffff;
            --input-border: #d1d5db;
            --input-bg: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --red: #ef4444;
            --green: #10b981;
            --yellow: #f59e0b;
        }

        .dark {
            --bg-primary: #111827;
            --bg-secondary: #1f2937;
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --accent-color: #3b82f6;
            --accent-hover: #60a5fa;
            --accent-light: #1e3a8a;
            --card-bg: #1f2937;
            --card-border: #374151;
            --nav-bg: #111827;
            --nav-border: #374151;
            --button-bg: #3b82f6;
            --button-hover: #60a5fa;
            --button-text: #ffffff;
            --input-border: #4b5563;
            --input-bg: #374151;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.4), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
            --red: #f87171;
            --green: #34d399;
            --yellow: #fbbf24;
        }
        
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        /* Simple card design */
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: var(--shadow-md);
        }
        
        /* Clean navigation */
        nav {
            background-color: var(--nav-bg);
            border-bottom: 1px solid var(--nav-border);
        }
        
        .nav-link {
            color: var(--text-secondary);
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            color: var(--accent-color);
            background-color: var(--bg-secondary);
        }
        
        .nav-link.active {
            color: var(--accent-color);
            font-weight: 600;
        }
        
        /* Button styles */
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary {
            background-color: var(--button-bg);
            color: var(--button-text);
        }
        
        .btn-primary:hover {
            background-color: var(--button-hover);
        }
        
        .btn-outline {
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
            background-color: transparent;
        }
        
        .btn-outline:hover {
            background-color: var(--accent-light);
            color: var(--accent-color);
        }
        
        /* Toggle switch */
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
            background-color: var(--input-border);
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
            background-color: var(--accent-color);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        /* Form input styles */
        input, select, textarea {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-primary);
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            width: 100%;
            transition: all 0.2s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        /* Section header */
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .section-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        .section-header .icon {
            margin-right: 0.5rem;
            color: var(--accent-color);
        }
        
        /* Simple fade in animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Image upload area */
        .upload-area {
            border: 2px dashed var(--input-border);
            border-radius: 1rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .upload-area:hover {
            border-color: var(--accent-color);
            background-color: rgba(37, 99, 235, 0.05);
        }
        
        .upload-area.dragover {
            border-color: var(--accent-color);
            background-color: rgba(37, 99, 235, 0.1);
        }
        
        /* Modal styling */
        .modal {
            transition: all 0.3s ease-out;
        }
        
        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="min-h-screen flex flex-col">
    <!-- Simple Navigation Bar -->
    <nav class="sticky top-0 z-50 px-4 py-2">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-lg font-semibold hidden md:block">Student Portal</span>
                <div class="hidden md:flex items-center ml-8 space-x-1">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home mr-2"></i> Home
                    </a>
                    <a href="edit_student_info.php" class="nav-link active">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <a href="history.php" class="nav-link">
                        <i class="fas fa-history mr-2"></i> History
                    </a>
                    <a href="reservation.php" class="nav-link">
                        <i class="fas fa-calendar mr-2"></i> Reservation
                    </a>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Dark Mode Toggle -->
                <div class="flex items-center">
                    <span class="mr-2 text-sm"><i class="fas fa-sun"></i></span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="darkModeToggle">
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="ml-2 text-sm"><i class="fas fa-moon"></i></span>
                </div>
                
                <!-- Logout Button -->
                <a href="logout.php" class="btn btn-primary bg-red-500 hover:bg-red-600 hidden md:flex">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
                
                <!-- Mobile menu button -->
                <button id="mobile-menu-button" class="md:hidden p-2 rounded-md focus:outline-none">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden hidden mt-2 pb-2">
            <a href="dashboard.php" class="nav-link block mb-1">
                <i class="fas fa-home mr-2"></i> Home
            </a>
            <a href="edit_student_info.php" class="nav-link block mb-1 active">
                <i class="fas fa-user mr-2"></i> Profile
            </a>
            <a href="history.php" class="nav-link block mb-1">
                <i class="fas fa-history mr-2"></i> History
            </a>
            <a href="reservation.php" class="nav-link block mb-1">
                <i class="fas fa-calendar mr-2"></i> Reservation
            </a>
            <!-- Logout Button in mobile menu -->
            <a href="logout.php" class="nav-link block mb-1 text-red-600 dark:text-red-400">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-6 flex-grow">
        <div class="mb-6 fade-in">
            <h1 class="text-2xl font-bold">Edit Profile</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Update your personal information</p>
        </div>

        <div class="max-w-4xl mx-auto">
            <?php if (isset($_SESSION["update_success"]) && $_SESSION["update_success"]): ?>
            <div id="success-message" class="mb-6 p-4 border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20 rounded-md fade-in">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">
                            <?php if (isset($_SESSION["profile_updated"]) && $_SESSION["profile_updated"]): ?>
                                Your profile picture has been updated successfully!
                                <?php unset($_SESSION["profile_updated"]); ?>
                            <?php elseif (isset($_SESSION["password_updated"]) && $_SESSION["password_updated"]): ?>
                                Your password has been updated successfully!
                                <?php unset($_SESSION["password_updated"]); ?>
                            <?php else: ?>
                                Your profile has been updated successfully!
                            <?php endif; ?>
                        </p>
                    </div>
                    <button type="button" class="ml-auto text-green-500" onclick="document.getElementById('success-message').classList.add('hidden')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <?php unset($_SESSION["update_success"]); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION["update_error"])): ?>
            <div id="error-message" class="mb-6 p-4 border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 rounded-md fade-in">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">
                            <?php echo $_SESSION["update_error"]; ?>
                        </p>
                    </div>
                    <button type="button" class="ml-auto text-red-500" onclick="document.getElementById('error-message').classList.add('hidden')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <?php unset($_SESSION["update_error"]); ?>
            <?php endif; ?>
                
            <div class="card mb-6">
                <div class="p-6">
                    <div class="flex justify-center mb-6">
                        <div class="relative group cursor-pointer" onclick="toggleEditProfile()">
                            <img src="<?php echo $profile_image; ?>?v=<?php echo time(); ?>" alt="Profile Image" 
                                class="w-32 h-32 rounded-full object-cover border-2 border-blue-500 transition-all duration-300 group-hover:opacity-80">
                            <div class="absolute bottom-0 right-0 bg-blue-500 text-white rounded-full w-8 h-8 flex items-center justify-center">
                                <i class="fas fa-camera"></i>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300">
                                <span class="bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-xs">Change Photo</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mb-4">
                        <h3 class="text-lg font-semibold"><?php echo $first_name . ' ' . $last_name; ?></h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo $id_number; ?></p>
                    </div>
                </div>
            </div>
                    
            <form method="post" action="edit_student_info.php" class="space-y-6">
                <div class="card p-6 mb-6">
                    <div class="section-header mb-4">
                        <i class="fas fa-user-circle icon"></i>
                        <h2>Personal Information</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Last Name
                            </label>
                            <input type="text" id="last_name" name="last_name" required 
                                value="<?php echo htmlspecialchars($last_name); ?>">
                        </div>
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                First Name
                            </label>
                            <input type="text" id="first_name" name="first_name" required 
                                value="<?php echo htmlspecialchars($first_name); ?>">
                        </div>
                        <div>
                            <label for="middle_name" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Middle Name
                            </label>
                            <input type="text" id="middle_name" name="middle_name" 
                                value="<?php echo htmlspecialchars($middle_name); ?>">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Email Address
                            </label>
                            <input type="email" id="email" name="email" required 
                                value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="card p-6 mb-6">
                    <div class="section-header mb-4">
                        <i class="fas fa-graduation-cap icon"></i>
                        <h2>Academic Information</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="course" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Course
                            </label>
                            <select id="course" name="course" required>
                                <option value="BSCS" <?php if ($course == 'BSCS') echo 'selected'; ?>>Bachelor of Science in Computer Science</option>
                                <option value="BSIT" <?php if ($course == 'BSIT') echo 'selected'; ?>>Bachelor of Science in Information Technology</option>
                                <option value="BSSE" <?php if ($course == 'BSSE') echo 'selected'; ?>>Bachelor of Science in Software Engineering</option>
                            </select>
                        </div>
                        <div>
                            <label for="course_level" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Year Level
                            </label>
                            <select id="course_level" name="course_level" required>
                                <option value="1st Year" <?php if ($course_level == '1st Year') echo 'selected'; ?>>1st Year</option>
                                <option value="2nd Year" <?php if ($course_level == '2nd Year') echo 'selected'; ?>>2nd Year</option>
                                <option value="3rd Year" <?php if ($course_level == '3rd Year') echo 'selected'; ?>>3rd Year</option>
                                <option value="4th Year" <?php if ($course_level == '4th Year') echo 'selected'; ?>>4th Year</option>
                                <option value="5th Year" <?php if ($course_level == '5th Year') echo 'selected'; ?>>5th Year</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="card p-6 mb-6">
                    <div class="section-header mb-4">
                        <i class="fas fa-map-marker-alt icon"></i>
                        <h2>Contact Information</h2>
                    </div>
                    
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Complete Address
                        </label>
                        <textarea id="address" name="address" required rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                    </div>
                </div>
                
                <div class="flex justify-between">
                    <a href="dashboard.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>
                        Save Changes
                    </button>
                </div>
            </form>
            
            <!-- New Password Reset Section -->
            <div class="card p-6 mb-6">
                <div class="section-header mb-4">
                    <i class="fas fa-lock icon"></i>
                    <h2>Change Password</h2>
                </div>
                
                <form method="post" action="edit_student_info.php" id="password-form" class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                            Current Password
                        </label>
                        <input type="password" id="current_password" name="current_password" required 
                            class="w-full">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                New Password
                            </label>
                            <input type="password" id="new_password" name="new_password" required 
                                class="w-full" minlength="8">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Minimum 8 characters
                            </p>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Confirm New Password
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                class="w-full">
                        </div>
                    </div>
                    
                    <div class="pt-2">
                        <div class="flex items-center" id="password-validation">
                            <i class="fas fa-info-circle text-yellow-500 mr-2"></i>
                            <span class="text-xs text-gray-600 dark:text-gray-400">Password requirements will be checked when you submit</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key mr-2"></i>
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Updated Profile Picture Modal -->
    <div id="editProfileModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
        <div class="modal-content bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold flex items-center">
                    <i class="fas fa-camera text-blue-500 mr-2"></i>
                    Update Profile Picture
                </h3>
                <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" onclick="toggleEditProfile()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="post" action="edit_student_info.php" enctype="multipart/form-data" id="profile-image-form">
                <div class="flex flex-col items-center justify-center py-4">
                    <div id="upload-area" class="upload-area w-full h-40 flex flex-col items-center justify-center mb-4">
                        <div class="preview-container h-36 w-36 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                            <img id="imagePreview" src="" alt="Preview" class="h-full w-full object-cover hidden">
                            <div id="previewPlaceholder" class="text-gray-500 dark:text-gray-400 text-center p-4">
                                <i class="fas fa-cloud-upload-alt text-2xl mb-2"></i><br>
                                Drag & drop image or click to browse
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col items-center">
                        <label for="profile_image" class="btn btn-primary cursor-pointer">
                            <i class="fas fa-upload mr-2"></i>
                            Select Image
                        </label>
                        <input type="file" id="profile_image" name="profile_image" required class="hidden" accept="image/*" onchange="previewImage(this)">
                        
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            Supported formats: JPG, PNG, GIF (max 2MB)
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-4">
                    <button type="button" onclick="toggleEditProfile()" 
                        class="btn btn-outline">
                        Cancel
                    </button>
                    <button type="submit" id="upload-button"
                        class="btn btn-primary disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Upload & Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-8 py-6 border-t border-gray-200 dark:border-gray-800">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    &copy; <?php echo date('Y'); ?> Student Portal | College of Computer Studies
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                    Version 2.0
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
        
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;
        
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
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
        
        // Toggle profile picture edit modal
        function toggleEditProfile() {
            const modal = document.getElementById('editProfileModal');
            modal.classList.toggle('hidden');
            
            // Prevent scrolling when modal is open
            if (!modal.classList.contains('hidden')) {
                document.body.style.overflow = 'hidden';
                
                // Reset form and preview
                document.getElementById('profile-image-form').reset();
                document.getElementById('imagePreview').classList.add('hidden');
                document.getElementById('previewPlaceholder').classList.remove('hidden');
                document.getElementById('upload-button').disabled = true;
            } else {
                document.body.style.overflow = 'auto';
            }
        }
        
        // Image preview functionality
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('previewPlaceholder');
            const uploadButton = document.getElementById('upload-button');
            
            if (input.files && input.files[0]) {
                // Validate file size
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (input.files[0].size > maxSize) {
                    alert('Image size must be less than 2MB.');
                    input.value = '';
                    return;
                }
                
                // Validate file type
                const fileType = input.files[0].type;
                if (!fileType.match('image/jpeg') && !fileType.match('image/png') && !fileType.match('image/gif')) {
                    alert('Only JPG, PNG and GIF images are allowed.');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    uploadButton.disabled = false;
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.classList.add('hidden');
                placeholder.classList.remove('hidden');
                uploadButton.disabled = true;
            }
        }
        
        // Drag and drop functionality
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('upload-area');
            const fileInput = document.getElementById('profile_image');
            
            // Open file browser when clicking on the upload area
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            // Prevent default behavior for drag events
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });
            
            // Add visual feedback when dragging
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, function() {
                    uploadArea.classList.add('dragover');
                }, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, function() {
                    uploadArea.classList.remove('dragover');
                }, false);
            });
            
            // Handle dropped files
            uploadArea.addEventListener('drop', function(e) {
                fileInput.files = e.dataTransfer.files;
                previewImage(fileInput);
            }, false);
            
            // Auto-hide messages after 5 seconds
            setTimeout(function() {
                const successMessage = document.getElementById('success-message');
                const errorMessage = document.getElementById('error-message');
                
                if (successMessage) {
                    successMessage.style.opacity = '0';
                    successMessage.style.transform = 'translateY(-10px)';
                    successMessage.style.transition = 'opacity 0.5s, transform 0.5s';
                    setTimeout(() => successMessage.classList.add('hidden'), 500);
                }
                
                if (errorMessage) {
                    errorMessage.style.opacity = '0';
                    errorMessage.style.transform = 'translateY(-10px)';
                    errorMessage.style.transition = 'opacity 0.5s, transform 0.5s';
                    setTimeout(() => errorMessage.classList.add('hidden'), 500);
                }
            }, 5000);
        });
        
        // Password validation
        document.addEventListener('DOMContentLoaded', function() {
            const passwordForm = document.getElementById('password-form');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const validationMessage = document.getElementById('password-validation');
            
            // Check passwords match when typing in confirm field
            confirmPassword.addEventListener('input', function() {
                if (newPassword.value && confirmPassword.value) {
                    if (newPassword.value !== confirmPassword.value) {
                        validationMessage.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-2"></i><span class="text-xs text-red-600 dark:text-red-400">Passwords do not match</span>';
                    } else {
                        validationMessage.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-2"></i><span class="text-xs text-green-600 dark:text-green-400">Passwords match</span>';
                    }
                }
            });
            
            // Check password length when typing
            newPassword.addEventListener('input', function() {
                if (newPassword.value.length > 0 && newPassword.value.length < 8) {
                    validationMessage.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-2"></i><span class="text-xs text-red-600 dark:text-red-400">Password must be at least 8 characters</span>';
                } else if (newPassword.value.length >= 8) {
                    validationMessage.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-2"></i><span class="text-xs text-green-600 dark:text-green-400">Password length is good</span>';
                    
                    // Also check if passwords match if confirm field has a value
                    if (confirmPassword.value) {
                        if (newPassword.value !== confirmPassword.value) {
                            validationMessage.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-2"></i><span class="text-xs text-red-600 dark:text-red-400">Passwords do not match</span>';
                        } else {
                            validationMessage.innerHTML = '<i class="fas fa-check-circle text-green-500 mr-2"></i><span class="text-xs text-green-600 dark:text-green-400">Passwords match</span>';
                        }
                    }
                }
            });
            
            // Form validation before submit
            passwordForm.addEventListener('submit', function(e) {
                if (newPassword.value !== confirmPassword.value) {
                    e.preventDefault();
                    validationMessage.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-2"></i><span class="text-xs text-red-600 dark:text-red-400">Passwords do not match</span>';
                    alert('Passwords do not match');
                } else if (newPassword.value.length < 8) {
                    e.preventDefault();
                    validationMessage.innerHTML = '<i class="fas fa-times-circle text-red-500 mr-2"></i><span class="text-xs text-red-600 dark:text-red-400">Password must be at least 8 characters</span>';
                    alert('Password must be at least 8 characters long');
                }
            });
        });
    </script>
</body>
</html>
