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
    <title>Edit Profile</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --text-primary: #334155;
            --text-secondary: #64748b;
            --accent-color: #3b82f6;
            --accent-hover: #2563eb;
            --accent-light: #dbeafe;
            --sidebar-width: 280px;
            --header-height: 64px;
            --border-color: #e2e8f0;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --card-bg: #fff;
            --section-title-color: #94a3b8;
        }

        .dark {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --accent-light: #1e3a8a;
            --accent-hover: #60a5fa;
            --border-color: #334155;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.4);
            --card-bg: #1e293b;
            --section-title-color: #64748b;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            transition: background-color 0.2s, color 0.2s;
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--card-bg);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-header {
            height: 70px;
            padding: 0 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            background-color: var(--card-bg);
            z-index: 10;
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .logo-text {
            font-weight: 700;
            font-size: 18px;
            letter-spacing: -0.01em;
            color: var(--text-primary);
        }
        
        .sidebar-content {
            flex: 1;
            padding: 16px 12px;
        }
        
        .sidebar-section {
            margin-bottom: 24px;
        }
        
        .section-title {
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 600;
            color: var(--section-title-color);
            letter-spacing: 0.05em;
            padding: 0 12px;
            margin-bottom: 8px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 4px;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .nav-item:hover {
            background-color: var(--bg-secondary);
        }
        
        .nav-item.active {
            background-color: var(--accent-light);
            color: var(--accent-color);
        }
        
        .nav-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: var(--text-secondary);
        }
        
        .nav-item.active .nav-icon {
            color: var(--accent-color);
        }
        
        .user-section {
            padding: 16px;
            border-top: 1px solid var(--border-color);
            margin-top: auto;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .user-info:hover {
            background-color: var(--bg-secondary);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--accent-light);
            color: var(--accent-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }
        
        .user-details {
            flex: 1;
            min-width: 0;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-role {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .main-content {
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .topbar {
            height: 70px;
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 24px;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .page-title {
            font-weight: 600;
            font-size: 18px;
            color: var(--text-primary);
        }
        
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .theme-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 22px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--bg-secondary);
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--accent-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(22px);
        }
        
        .content-area {
            padding: 24px;
            flex: 1;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
            border: 1px solid var(--border-color);
        }
        
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-title i {
            color: var(--accent-color);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 500;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: var(--accent-hover);
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        
        .btn-outline:hover {
            background-color: var(--bg-secondary);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--card-bg);
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }
        
        .modal {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            max-width: 500px;
            width: 100%;
            margin: 20px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 20px;
            cursor: pointer;
        }
        
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 90;
        }
        
        @media (max-width: 768px) {
            body {
                overflow-y: auto;
            }
            
            .sidebar {
                position: fixed;
                left: -280px;
                z-index: 100;
                box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
            }
            
            .sidebar.open {
                left: 0;
            }
            
            .main-content {
                width: 100%;
            }
            
            .topbar {
                padding: 0 16px;
            }
            
            .menu-toggle {
                display: block !important;
            }
        }
        
        /* Upload area styling */
        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: var(--accent-color);
            background-color: var(--bg-secondary);
        }
        
        .upload-area.dragover {
            border-color: var(--accent-color);
            background-color: rgba(59, 130, 246, 0.1);
        }
        
        .profile-img-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }
        
        .profile-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent-color);
        }
        
        .edit-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: var(--accent-color);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <div class="logo-text">SIT-IN Portal</div>
            </div>
        </div>
        
        <div class="sidebar-content">
            <div class="sidebar-section">
                <a href="dashboard.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-home"></i></div>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="section-title">Account</div>
                <a href="edit_student_info.php" class="nav-item active">
                    <div class="nav-icon"><i class="fas fa-user"></i></div>
                    <span>Profile</span>
                </a>
                <a href="history.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-history"></i></div>
                    <span>Session History</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="section-title">Actions</div>
                <a href="reservation.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-calendar"></i></div>
                    <span>Make a Reservation</span>
                </a>
                <a href="view_schedule.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-calendar-week"></i></div>
                    <span>View Schedules</span>
                </a>
                <a href="view_resources.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-cube"></i></div>
                    <span>Browse Resources</span>
                </a>
            </div>
        </div>
        
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar"><?php echo substr($first_name, 0, 1); ?></div>
                <div class="user-details">
                    <div class="user-name"><?php echo $first_name . ' ' . $last_name; ?></div>
                    <div class="user-role"><?php echo $course . ' - ' . $course_level; ?></div>
                </div>
                <div>
                    <a href="logout.php" title="Logout">
                        <i class="fas fa-sign-out-alt text-gray-400 hover:text-red-500"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="overlay" id="overlay"></div>
    
    <!-- Main content -->
    <div class="main-content">
        <div class="topbar">
            <div class="flex items-center">
                <button class="menu-toggle mr-4" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Edit Profile</h1>
            </div>
            
            <div class="topbar-actions">
                <div class="theme-toggle">
                    <i class="fas fa-sun"></i>
                    <label class="switch">
                        <input type="checkbox" id="darkModeToggle">
                        <span class="slider"></span>
                    </label>
                    <i class="fas fa-moon"></i>
                </div>
            </div>
        </div>
        
        <div class="content-area">
            <!-- Status Messages -->
            <?php if (isset($_SESSION["update_success"]) && $_SESSION["update_success"]): ?>
            <div id="success-message" class="mb-6 p-4 border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20 rounded-md">
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
            <div id="error-message" class="mb-6 p-4 border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 rounded-md">
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
            
            <!-- Profile Overview Card -->
            <div class="card mb-6">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-user"></i>
                        <span>Profile Overview</span>
                    </h2>
                </div>
                
                <div class="p-6">
                    <div class="flex flex-col items-center">
                        <div class="profile-img-container mb-4" onclick="toggleProfileModal()">
                            <img src="<?php echo $profile_image; ?>?v=<?php echo time(); ?>" alt="Profile" class="profile-img">
                            <div class="edit-overlay">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        
                        <div class="text-center mb-4">
                            <h3 class="text-xl font-semibold"><?php echo $first_name . ' ' . $last_name; ?></h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo $id_number; ?></p>
                            <div class="mt-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                    <?php echo $course . ' - ' . $course_level; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Personal Information Form -->
            <div class="card mb-6">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-user-circle"></i>
                        <span>Personal Information</span>
                    </h2>
                </div>
                
                <div class="p-6">
                    <form method="post" action="edit_student_info.php">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" required 
                                    value="<?php echo htmlspecialchars($last_name); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" required 
                                    value="<?php echo htmlspecialchars($first_name); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" class="form-control" 
                                    value="<?php echo htmlspecialchars($middle_name); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" required 
                                    value="<?php echo htmlspecialchars($email); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="course" class="form-label">Course</label>
                                <select id="course" name="course" class="form-control" required>
                                    <option value="BSCS" <?php if ($course == 'BSCS') echo 'selected'; ?>>Bachelor of Science in Computer Science</option>
                                    <option value="BSIT" <?php if ($course == 'BSIT') echo 'selected'; ?>>Bachelor of Science in Information Technology</option>
                                    <option value="BSSE" <?php if ($course == 'BSSE') echo 'selected'; ?>>Bachelor of Science in Software Engineering</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="course_level" class="form-label">Year Level</label>
                                <select id="course_level" name="course_level" class="form-control" required>
                                    <option value="1st Year" <?php if ($course_level == '1st Year') echo 'selected'; ?>>1st Year</option>
                                    <option value="2nd Year" <?php if ($course_level == '2nd Year') echo 'selected'; ?>>2nd Year</option>
                                    <option value="3rd Year" <?php if ($course_level == '3rd Year') echo 'selected'; ?>>3rd Year</option>
                                    <option value="4th Year" <?php if ($course_level == '4th Year') echo 'selected'; ?>>4th Year</option>
                                    <option value="5th Year" <?php if ($course_level == '5th Year') echo 'selected'; ?>>5th Year</option>
                                </select>
                            </div>
                            
                            <div class="form-group col-span-1 md:col-span-2">
                                <label for="address" class="form-label">Complete Address</label>
                                <textarea id="address" name="address" class="form-control" required rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="flex justify-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Password Change Card -->
            <div class="card mb-6">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-lock"></i>
                        <span>Change Password</span>
                    </h2>
                </div>
                
                <div class="p-6">
                    <form method="post" action="edit_student_info.php" id="password-form">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group col-span-1 md:col-span-2">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8">
                                <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="flex items-center" id="password-validation">
                                <i class="fas fa-info-circle text-yellow-500 mr-2"></i>
                                <span class="text-xs text-gray-600 dark:text-gray-400">Password requirements will be checked when you submit</span>
                            </div>
                        </div>
                        
                        <div class="flex justify-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key mr-2"></i>
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Profile Picture Upload Modal -->
    <div id="profileModal" class="modal-backdrop hidden" style="display:none">
        <div class="modal p-6">
            <div class="flex justify-between items-center mb-4 border-b border-gray-200 dark:border-gray-700 pb-3">
                <h3 class="text-lg font-semibold flex items-center">
                    <i class="fas fa-camera text-blue-500 mr-2"></i>
                    Update Profile Picture
                </h3>
                <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200" onclick="toggleProfileModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="post" action="edit_student_info.php" enctype="multipart/form-data" id="profile-image-form">
                <div id="upload-area" class="upload-area mb-4">
                    <div class="flex flex-col items-center justify-center p-4">
                        <div class="w-32 h-32 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden mb-4 relative">
                            <img id="imagePreview" src="#" alt="Preview" class="h-full w-full object-cover hidden">
                            <div id="previewPlaceholder" class="absolute inset-0 flex items-center justify-center text-center text-gray-400">
                                <div>
                                    <i class="fas fa-cloud-upload-alt text-3xl mb-2"></i>
                                    <p class="text-sm">Image preview</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex flex-col items-center">
                            <label for="profile_image" class="btn btn-primary cursor-pointer mb-2">
                                <i class="fas fa-upload mr-2"></i>
                                Select Image
                            </label>
                            <input type="file" id="profile_image" name="profile_image" required class="hidden" accept="image/*" onchange="previewImage(this)">
                            
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                Supported formats: JPG, PNG, GIF (max 2MB)
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" class="btn btn-outline" onclick="toggleProfileModal()">
                        Cancel
                    </button>
                    <button type="submit" id="upload-button" class="btn btn-primary" disabled>
                        <i class="fas fa-save mr-2"></i>
                        Save New Picture
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle mobile menu
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').style.display = 
                document.getElementById('sidebar').classList.contains('open') ? 'block' : 'none';
        });
        
        document.getElementById('overlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('open');
            this.style.display = 'none';
        });
        
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;
        
        // Check for saved theme preference or use system preference
        const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const savedTheme = localStorage.getItem('theme');
        
        if (savedTheme === 'dark' || (!savedTheme && darkModeMediaQuery.matches)) {
            html.classList.add('dark');
            darkModeToggle.checked = true;
        }
        
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        });
        
        // Profile modal toggle
        function toggleProfileModal() {
            const modal = document.getElementById('profileModal');
            
            if (modal.classList.contains('hidden')) {
                // Show modal
                modal.classList.remove('hidden');
                modal.style.display = 'flex'; // Explicitly set display to flex
                
                // Reset form when opening modal
                document.getElementById('profile-image-form').reset();
                document.getElementById('imagePreview').classList.add('hidden');
                document.getElementById('previewPlaceholder').classList.remove('hidden');
                document.getElementById('upload-button').disabled = true;
            } else {
                // Hide modal
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        }

        // Set up drag and drop for image upload
        document.addEventListener('DOMContentLoaded', function() {
            // IMPORTANT: Hide the modal immediately when the DOM loads - first priority
            const profileModal = document.getElementById('profileModal');
            profileModal.classList.add('hidden');
            
            // All other initialization code goes after
            const uploadArea = document.getElementById('upload-area');
            const fileInput = document.getElementById('profile_image');
            
            if (uploadArea) {
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
            }
            
            // Auto-hide messages after 5 seconds
            setTimeout(function() {
                const successMessage = document.getElementById('success-message');
                const errorMessage = document.getElementById('error-message');
                
                if (successMessage) {
                    successMessage.classList.add('opacity-0');
                    successMessage.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => successMessage.classList.add('hidden'), 500);
                }
                
                if (errorMessage) {
                    errorMessage.classList.add('opacity-0');
                    errorMessage.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => errorMessage.classList.add('hidden'), 500);
                }
            }, 5000);
            
            // ... existing code for password validation ...
        });
        
        // Image preview functionality
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('previewPlaceholder');
            const uploadButton = document.getElementById('upload-button');
            
            if (input.files && input.files[0]) {
                // ... existing code ...
            }
        }
    </script></body></html>