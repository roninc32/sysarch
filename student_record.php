<?php
include 'config.php';

// Add form processing code at the top
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_number"])) {
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
    $profile_image = 'assets/images/profile.jpg';

    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!');</script>";
    } else {
        // Check if user already exists
        $sql_check = "SELECT * FROM users WHERE id_number=? OR email=?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("ss", $id_number, $email);
        $stmt->execute();
        $result_check = $stmt->get_result();

        if ($result_check->num_rows > 0) {
            echo "<script>alert('An account with this ID number or email already exists.');</script>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (id_number, last_name, first_name, middle_name, course_level, password, confirm_password, email, course, address, profile_image, sessions_left) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 30)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssssss", 
                $id_number, 
                $last_name, 
                $first_name, 
                $middle_name, 
                $course_level, 
                $hashed_password, 
                $hashed_password, // Using the same hashed password for confirm_password
                $email, 
                $course, 
                $address, 
                $profile_image
            );

            if ($stmt->execute()) {
                echo "<script>alert('Student registered successfully!');</script>";
            } else {
                echo "<script>alert('Error registering student: " . $stmt->error . "');</script>";
            }
        }
    }
}

// Handle individual session reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_individual_session'])) {
    $student_id = $_POST['student_id'];
    
    $reset_sql = "UPDATE users SET sessions_left = 30 WHERE id_number = ?";
    $stmt = $conn->prepare($reset_sql);
    $stmt->bind_param("s", $student_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Sessions reset to 30 successfully for student ID: " . htmlspecialchars($student_id) . "');</script>";
        // Redirect to refresh the page
        echo "<script>window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Error resetting sessions: " . $stmt->error . "');</script>";
    }
}

// Handle reset all sessions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_sessions'])) {
    $reset_sql = "UPDATE users SET sessions_left = 30";
    $stmt = $conn->prepare($reset_sql);
    
    if ($stmt->execute()) {
        echo "<script>alert('All sessions have been reset to 30 successfully!');</script>";
        // Redirect to refresh the page
        echo "<script>window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Error resetting sessions: " . $stmt->error . "');</script>";
    }
}

// Handle student deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];
    
    $delete_sql = "DELETE FROM users WHERE id_number = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("s", $student_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Student deleted successfully!');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Error deleting student: " . $stmt->error . "');</script>";
    }
}

// Handle student update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_student'])) {
    $student_id = $_POST['student_id'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $course_level = $_POST['course_level'];
    $email = $_POST['email'];
    $course = $_POST['course'];
    $address = $_POST['address'];

    $update_sql = "UPDATE users SET 
                   last_name = ?, 
                   first_name = ?, 
                   middle_name = ?, 
                   course_level = ?, 
                   email = ?, 
                   course = ?, 
                   address = ? 
                   WHERE id_number = ?";
                   
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssssssss", 
        $last_name, 
        $first_name, 
        $middle_name, 
        $course_level, 
        $email, 
        $course, 
        $address, 
        $student_id
    );
    
    if ($stmt->execute()) {
        echo "<script>alert('Student information updated successfully!');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Error updating student: " . $stmt->error . "');</script>";
    }
}

// Handle point granting
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['grant_points'])) {
    $student_id = $_POST['student_id'];
    $points_to_add = (int)$_POST['points_to_add'];
    
    if ($points_to_add <= 0) {
        echo "<script>alert('Please enter a valid number of points.');</script>";
    } else {
        $update_sql = "UPDATE users SET points = points + ? WHERE id_number = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("is", $points_to_add, $student_id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Points added successfully!');</script>";
            echo "<script>window.location.href = window.location.href;</script>";
        } else {
            echo "<script>alert('Error adding points: " . $stmt->error . "');</script>";
        }
    }
}

// Handle point conversion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['convert_points'])) {
    $student_id = $_POST['student_id'];
    $points_to_convert = (int)$_POST['points_to_convert'];
    $sessions_to_add = floor($points_to_convert / 3); // 3 points = 1 session
    
    if ($points_to_convert < 3) {
        echo "<script>alert('You need at least 3 points to convert to a session.');</script>";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Verify student has enough points
            $verify_sql = "SELECT points FROM users WHERE id_number = ? AND points >= ?";
            $stmt = $conn->prepare($verify_sql);
            $stmt->bind_param("si", $student_id, $points_to_convert);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Deduct points and add sessions
                $update_sql = "UPDATE users SET points = points - ?, sessions_left = sessions_left + ? WHERE id_number = ?";
                $stmt->bind_param("iis", $points_to_convert, $sessions_to_add, $student_id);
                $stmt->execute();
                
                $conn->commit();
                echo "<script>alert('Successfully converted " . $points_to_convert . " points to " . $sessions_to_add . " sessions!');</script>";
                echo "<script>window.location.href = window.location.href;</script>";
            } else {
                $conn->rollback();
                echo "<script>alert('Error: Student doesn\\'t have enough points.');</script>";
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('Error processing points conversion: " . $e->getMessage() . "');</script>";
        }
    }
}

// Fetch student records with pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$total_records_query = "SELECT COUNT(*) as count FROM users";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch student records
$sql_students = "SELECT * FROM users ORDER BY id_number LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql_students);
if ($stmt) {
    $stmt->bind_param("ii", $records_per_page, $offset);
    $stmt->execute();
    $result_students = $stmt->get_result();
    $students = $result_students->fetch_all(MYSQLI_ASSOC);
} else {
    echo "<script>alert('Error preparing statement: " . $conn->error . "');</script>";
    $students = [];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.8.2/dist/alpine.min.js" defer></script>
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

        /* Fix action dropdown menu appearance */
        .action-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .action-dropdown-menu {
            position: absolute;
            right: 0;
            z-index: 30;
            min-width: 12rem;
            padding: 0.5rem 0;
            font-size: 0.875rem;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
            display: none;
        }
        
        .action-dropdown-menu.show {
            display: block;
        }
        
        .action-dropdown-item {
            display: block;
            width: 100%;
            padding: 0.5rem 1rem;
            clear: both;
            font-weight: 500;
            text-align: left;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
            color: var(--text-primary);
            cursor: pointer;
        }
        
        .action-dropdown-item:hover {
            background-color: var(--bg-secondary);
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
            color: var (--text-primary);
        }
        
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        /* Dropdown action menu styling */
        .dropdown-menu {
            min-width: 10rem;
            padding: 0.5rem 0;
            margin: 0;
            font-size: 0.875rem;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        }

        .dropdown-item {
            display: block;
            width: 100%;
            padding: 0.5rem 1rem;
            clear: both;
            text-align: inherit;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
            color: var(--text-primary);
            transition: background-color 0.15s ease-in-out;
        }

        .dropdown-item:hover, .dropdown-item:focus {
            background-color: var(--bg-secondary);
            text-decoration: none;
        }

        /* Fix table cell background */
        table tbody tr {
            background-color: var(--card-bg);
            color: var(--text-primary);
        }

        /* Improve dropdown button styling */
        .action-dropdown-btn {
            width: 100%;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .action-dropdown {
            position: relative;
            display: inline-block;
        }

        .action-menu {
            position: absolute;
            right: 0;
            z-index: 10;
            min-width: 10rem;
            margin-top: 0.125rem;
            transform-origin: top right;
        }
        
        .content-area {
            padding: 24px;
            flex: 1;
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

        /* Student records specific styles */
        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        table {
            border-color: var(--border-color);
        }

        table thead {
            background-color: var(--bg-secondary);
        }

        table thead th {
            color: var(--text-primary);
            font-weight: 600;
        }

        table tbody tr {
            background-color: var (--card-bg);
            color: var(--text-primary);
        }

        table tbody tr:hover {
            background-color: var(--bg-secondary);
        }

        .btn-primary {
            background-color: var(--accent-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background-color: var(--accent-hover);
        }
        
        input, select, textarea {
            background-color: var(--card-bg);
            color: var(--text-primary);
            border-color: var(--border-color);
            transition: border-color 0.2s, box-shadow 0.2s;
            border-radius: 0.375rem;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
            outline: none;
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
            
            .menu-toggle {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar with categories -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <div class="logo-text">SIT-IN Admin</div>
            </div>
        </div>
        
        <div class="sidebar-content">
            <div class="sidebar-section">
                <a href="admin_dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="section-title">Management</div>
                <a href="student_record.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'student_record.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-user-graduate"></i></div>
                    <span>Students</span>
                </a>
                <a href="admin_reservation.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reservation.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-calendar-alt"></i></div>
                    <span>Reservations</span>
                </a>
                <a href="sit_in_records.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'sit_in_records.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-clipboard-list"></i></div>
                    <span>Sit-in Records</span>
                </a>
                <a href="search_student.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-search"></i></div>
                    <span>Search</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="section-title">Features</div>
                <a href="schedule.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-calendar-week"></i></div>
                    <span>Schedules</span>
                </a>
                <a href="feedback.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-comments"></i></div>
                    <span>Feedback</span>
                </a>
                <a href="resources.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-cube"></i></div>
                    <span>Resources</span>
                </a>
            </div>
        </div>
        
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar">A</div>
                <div class="user-details">
                    <div class="user-name">Admin User</div>
                    <div class="user-role">System Administrator</div>
                </div>
                <div>
                    <a href="admin_logout.php" title="Logout">
                        <i class="fas fa-sign-out-alt text-gray-400 hover:text-red-500"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="overlay" id="overlay"></div>
    
    <!-- Main content area -->
    <div class="main-content">
        <div class="topbar">
            <div class="flex items-center">
                <button class="menu-toggle mr-4" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Student Records</h1>
            </div>
            
            <div class="topbar-actions">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" placeholder="Search students" class="search-input" id="studentSearchInput">
                </div>
                
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
            <div class="card shadow-lg p-6">
                <div class="mb-6 flex flex-wrap justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <h2 class="text-xl font-bold mb-2 flex items-center">
                            <i class="fas fa-user-graduate mr-3 text-blue-500"></i>
                            Manage Student Records
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Add, update, or remove student information</p>
                    </div>
                    <div class="flex flex-wrap space-x-2">
                        <button onclick="openModal()" class="btn-primary mb-2 md:mb-0">
                            <i class="fas fa-user-plus mr-2"></i>Add Student
                        </button>
                        <form method="post" class="inline" onsubmit="return confirmResetAll()">
                            <input type="hidden" name="reset_sessions" value="1">
                            <button type="submit" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 font-medium py-2 px-4 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors mb-2 md:mb-0">
                                <i class="fas fa-redo mr-2"></i>Reset All Sessions
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 mb-6">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">First Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Middle Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Last Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Sessions Left</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Points</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700" id="studentTableBody">
                            <?php if (!empty($students)): ?>
                                <?php foreach ($students as $student): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($student['id_number']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($student['first_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($student['middle_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($student['last_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php if ($student['sessions_left'] <= 5): ?>
                                                <span class="text-red-600 dark:text-red-400"><?php echo htmlspecialchars($student['sessions_left']); ?></span>
                                            <?php else: ?>
                                                <span><?php echo htmlspecialchars($student['sessions_left']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <span class="bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 py-1 px-2 rounded-full">
                                                <?php echo isset($student['points']) ? htmlspecialchars($student['points']) : '0'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="relative inline-block text-left">
                                                <button onclick="toggleDropdown('dropdown-<?php echo htmlspecialchars($student['id_number']); ?>')" class="inline-flex justify-center w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-3 py-1.5 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none">
                                                    Actions
                                                    <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                <div id="dropdown-<?php echo htmlspecialchars($student['id_number']); ?>" class="hidden absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-50">
                                                    <div class="py-1">
                                                        <!-- Reset Sessions Button -->
                                                        <form method="post" class="block" onsubmit="return confirmIndividualReset('<?php echo htmlspecialchars($student['id_number']); ?>')">
                                                            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['id_number']); ?>">
                                                            <input type="hidden" name="reset_individual_session" value="1">
                                                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                                <i class="fas fa-redo mr-2 text-blue-500"></i>Reset Sessions
                                                            </button>
                                                        </form>
                                                        
                                                        <!-- Grant Points Button -->
                                                        <button onclick='openPointsModal("<?php echo htmlspecialchars($student['id_number']); ?>", "<?php echo htmlspecialchars($student['first_name']); ?> <?php echo htmlspecialchars($student['last_name']); ?>")' 
                                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                            <i class="fas fa-gift mr-2 text-yellow-500"></i>Grant Points
                                                        </button>
                                                        
                                                        <!-- Convert Points Button -->
                                                        <button onclick='openConvertModal("<?php echo htmlspecialchars($student['id_number']); ?>", "<?php echo htmlspecialchars($student['first_name']); ?> <?php echo htmlspecialchars($student['last_name']); ?>", <?php echo isset($student['points']) ? (int)$student['points'] : 0; ?>)' 
                                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                            <i class="fas fa-exchange-alt mr-2 text-green-500"></i>Convert Points
                                                        </button>
                                                        
                                                        <!-- Edit Button -->
                                                        <button onclick='openEditModal(<?php echo json_encode($student); ?>)' 
                                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                            <i class="fas fa-edit mr-2 text-gray-500"></i>Edit Student
                                                        </button>
                                                        
                                                        <!-- Delete Button -->
                                                        <form method="post" class="block" onsubmit="return confirmDelete('<?php echo htmlspecialchars($student['id_number']); ?>')">
                                                            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['id_number']); ?>">
                                                            <input type="hidden" name="delete_student" value="1">
                                                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                                <i class="fas fa-trash mr-2"></i>Delete Student
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        No student records found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-6 flex justify-center">
                        <nav class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo ($page - 1); ?>" class="px-3 py-1 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    Previous
                                </a>
                            <?php endif; ?>

                            <?php
                            // Calculate range of page numbers to show
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            // Show first page if we're not starting at 1
                            if ($start_page > 1) {
                                echo '<a href="?page=1" class="px-3 py-1 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="px-3 py-1">...</span>';
                                }
                            }

                            // Show page numbers
                            for ($i = $start_page; $i <= $end_page; $i++) {
                                if ($i == $page) {
                                    echo '<span class="px-3 py-1 bg-blue-500 text-white rounded-md">' . $i . '</span>';
                                } else {
                                    echo '<a href="?page=' . $i . '" class="px-3 py-1 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">' . $i . '</a>';
                                }
                            }

                            // Show last page if we're not ending at total_pages
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="px-3 py-1">...</span>';
                                }
                                echo '<a href="?page=' . $total_pages . '" class="px-3 py-1 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">' . $total_pages . '</a>';
                            }
                            ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo ($page + 1); ?>" class="px-3 py-1 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    Next
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>

                <!-- Records info display -->
                <div class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
                    Showing <?php echo min(($page - 1) * $records_per_page + 1, $total_records); ?> to 
                    <?php echo min($page * $records_per_page, $total_records); ?> of 
                    <?php echo $total_records; ?> records
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Modal -->
    <div id="registrationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
            <div class="mt-3">
                <h3 class="text-lg font-bold mb-4 border-b pb-2 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white">Register New Student</h3>
                <form method="post" class="space-y-4" onsubmit="return confirmRegistration()">
                    <div>
                        <label class="block text-sm font-medium mb-1">ID Number</label>
                        <input type="text" name="id_number" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Last Name</label>
                        <input type="text" name="last_name" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">First Name</label>
                        <input type="text" name="first_name" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Middle Name</label>
                        <input type="text" name="middle_name" class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Course Level</label>
                        <select name="course_level" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Course</label>
                        <select name="course" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Computer Science">BSCS</option>
                            <option value="Information Technology">BSIT</option>
                            <option value="Software Engineering">BSSE</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Password</label>
                        <input type="password" name="password" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Confirm Password</label>
                        <input type="password" name="confirm_password" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Address</label>
                        <textarea name="address" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
                        <button type="submit" class="btn-primary">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
            <div class="mt-3">
                <h3 class="text-lg font-bold mb-4 border-b pb-2 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white">Edit Student Information</h3>
                <form method="post" class="space-y-4" id="editForm" onsubmit="return confirmEdit()">
                    <input type="hidden" name="update_student" value="1">
                    <input type="hidden" name="student_id" id="edit_student_id">
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">ID Number</label>
                        <input type="text" id="edit_id_number" disabled class="mt-1 block w-full px-3 py-2 border rounded-md bg-gray-100 dark:bg-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Last Name</label>
                        <input type="text" name="last_name" id="edit_last_name" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">First Name</label>
                        <input type="text" name="first_name" id="edit_first_name" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Middle Name</label>
                        <input type="text" name="middle_name" id="edit_middle_name" class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Course Level</label>
                        <select name="course_level" id="edit_course_level" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Course</label>
                        <select name="course" id="edit_course" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Computer Science">BSCS</option>
                            <option value="Information Technology">BSIT</option>
                            <option value="Software Engineering">BSSE</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email" id="edit_email" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Address</label>
                        <textarea name="address" id="edit_address" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
                        <button type="submit" class="btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Points Modal -->
    <div id="pointsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
            <div class="mt-3">
                <h3 class="text-lg font-bold mb-4 border-b pb-2 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white">Grant Points</h3>
                <form method="post" class="space-y-4" onsubmit="return confirmPointsGrant()">
                    <input type="hidden" name="grant_points" value="1">
                    <input type="hidden" name="student_id" id="points_student_id">
                    
                    <div class="mb-4">
                        <p class="text-sm">Granting points to: <strong id="student_name_display"></strong></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Number of Points to Add</label>
                        <input type="number" name="points_to_add" min="1" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">3 points can be converted to 1 session by the student.</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closePointsModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
                        <button type="submit" class="btn-primary bg-yellow-500 hover:bg-yellow-600">Grant Points</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Convert Points Modal -->
    <div id="convertModal" class="fixed inset-0 bg-black bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700">
            <div class="mt-3">
                <h3 class="text-lg font-bold mb-4 border-b pb-2 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white">Convert Points to Sessions</h3>
                <form method="post" class="space-y-4" onsubmit="return confirmPointsConversion()">
                    <input type="hidden" name="convert_points" value="1">
                    <input type="hidden" name="student_id" id="convert_student_id">
                    
                    <div class="mb-4">
                        <p class="text-sm">Converting points for: <strong id="convert_student_name_display"></strong></p>
                        <p class="text-sm">Available points: <strong id="available_points_display"></strong></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Number of Points to Convert</label>
                        <input type="number" name="points_to_convert" min="3" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">3 points can be converted to 1 session.</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeConvertModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
                        <button type="submit" class="btn-primary bg-green-500 hover:bg-green-600">Convert Points</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
        
    <script>
        // Initialize theme handling
        function initTheme() {
            // Check for saved theme preference or default to system preference
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
                document.getElementById('darkModeToggle').checked = true;
            } else {
                document.documentElement.classList.remove('dark');
                document.getElementById('darkModeToggle').checked = false;
            }
        }

        // Toggle dark mode
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }
        
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
        
        // Modal functions
        function openModal() {
            document.getElementById('registrationModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeModal() {
            document.getElementById('registrationModal').classList.add('hidden');
            document.body.style.overflow = ''; // Allow scrolling
        }
        
        function openEditModal(student) {
            document.getElementById('edit_student_id').value = student.id_number;
            document.getElementById('edit_id_number').value = student.id_number;
            document.getElementById('edit_last_name').value = student.last_name;
            document.getElementById('edit_first_name').value = student.first_name;
            document.getElementById('edit_middle_name').value = student.middle_name || '';
            document.getElementById('edit_course_level').value = student.course_level;
            document.getElementById('edit_course').value = student.course;
            document.getElementById('edit_email').value = student.email;
            document.getElementById('edit_address').value = student.address;
            
            document.getElementById('editModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.body.style.overflow = ''; // Allow scrolling
        }
        
        // Points modal functions
        function openPointsModal(studentId, studentName) {
            document.getElementById('points_student_id').value = studentId;
            document.getElementById('student_name_display').textContent = studentName;
            document.getElementById('pointsModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closePointsModal() {
            document.getElementById('pointsModal').classList.add('hidden');
            document.body.style.overflow = ''; // Allow scrolling
        }

        // Convert points modal functions
        function openConvertModal(studentId, studentName, availablePoints) {
            document.getElementById('convert_student_id').value = studentId;
            document.getElementById('convert_student_name_display').textContent = studentName;
            document.getElementById('available_points_display').textContent = availablePoints;
            document.getElementById('convertModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeConvertModal() {
            document.getElementById('convertModal').classList.add('hidden');
            document.body.style.overflow = ''; // Allow scrolling
        }
        
        // Confirmation functions
        function confirmRegistration() {
            return confirm('Are you sure you want to register this student?');
        }
        
        function confirmIndividualReset(studentId) {
            return confirm('Are you sure you want to reset sessions to 30 for student ID: ' + studentId + '?');
        }
        
        function confirmResetAll() {
            return confirm('Are you sure you want to reset sessions to 30 for ALL students? This cannot be undone.');
        }

        function confirmEdit() {
            return confirm('Are you sure you want to update this student\'s information?');
        }

        function confirmDelete(studentId) {
            return confirm('Are you sure you want to delete this student? This action cannot be undone.');
        }
        
        function confirmPointsGrant() {
            return confirm('Are you sure you want to grant these points to the student?');
        }

        function confirmPointsConversion() {
            return confirm('Are you sure you want to convert these points to sessions?');
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize theme
            initTheme();
            
            // Add dark mode toggle listener
            document.getElementById('darkModeToggle').addEventListener('change', toggleTheme);
            
            // Search functionality
            document.getElementById('studentSearchInput').addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const tableRows = document.getElementById('studentTableBody').getElementsByTagName('tr');
                
                for (let i = 0; i < tableRows.length; i++) {
                    let found = false;
                    const cells = tableRows[i].getElementsByTagName('td');
                    
                    for (let j = 0; j < cells.length - 1; j++) { // Skip the actions column
                        const cellText = cells[j].textContent.toLowerCase();
                        if (cellText.includes(searchTerm)) {
                            found = true;
                            break;
                        }
                    }
                    
                    tableRows[i].style.display = found ? '' : 'none';
                }
            });
        });
        
        // Handle action dropdown menus
        function toggleDropdown(dropdownId) {
            // Get the dropdown element
            const dropdown = document.getElementById(dropdownId);
            
            // Close all other dropdowns first
            document.querySelectorAll('[id^="dropdown-"]').forEach(menu => {
                if (menu.id !== dropdownId) {
                    menu.classList.add('hidden');
                }
            });
            
            // Toggle the selected dropdown
            dropdown.classList.toggle('hidden');
            
            // Stop event from reaching document immediately
            event.stopPropagation();
            
            // Handle outside clicks
            if (!dropdown.classList.contains('hidden')) {
                const closeDropdown = function(e) {
                    if (!dropdown.contains(e.target) && 
                        !e.target.matches(`button[onclick*="${dropdownId}"]`) && 
                        !e.target.closest(`button[onclick*="${dropdownId}"]`)) {
                        dropdown.classList.add('hidden');
                        document.removeEventListener('click', closeDropdown);
                    }
                };
                
                // Add the event listener after a short delay to avoid immediate triggering
                setTimeout(() => {
                    document.addEventListener('click', closeDropdown);
                }, 0);
            }
        }
        
        // Add additional CSS styles for proper dropdown positioning and display
        document.addEventListener('DOMContentLoaded', function() {
            // Apply custom styles for dropdown positioning
            const style = document.createElement('style');
            style.textContent = `
                .relative.inline-block.text-left {
                    position: relative;
                }
                [id^="dropdown-"] {
                    position: absolute;
                    right: 0;
                    z-index: 100;
                    margin-top: 0.5rem;
                    min-width: 14rem;
                    transform-origin: top right;
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                    max-height: none !important;
                    overflow: visible !important;
                }
                [id^="dropdown-"] .py-1 {
                    display: flex;
                    flex-direction: column;
                    padding-top: 0.25rem;
                    padding-bottom: 0.25rem;
                }
                [id^="dropdown-"] .py-1 > * {
                    width: 100%;
                }
                [id^="dropdown-"] form {
                    width: 100%;
                }
                @media (max-width: 640px) {
                    [id^="dropdown-"] {
                        right: auto;
                        left: 0;
                        transform-origin: top left;
                    }
                }
            `;
            document.head.append(style);
        });
        
        // Cleanup - make sure dropdowns are hidden when ESC is pressed
        // ...existing code...
    </script>
</body>
</html>
